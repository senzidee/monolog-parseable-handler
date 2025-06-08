<?php

declare(strict_types=1);

namespace SenzaIdee\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use SenzaIdee\Http\HttpClientInterface;
use SenzaIdee\Http\CurlHttpClient;

/**
 * @psalm-suppress ArgumentTypeCoercion,UnusedClass
 */
final class ParseableHandler extends AbstractProcessingHandler
{
    private const INGESTION_PATH = 'api/v1/ingest';

    private HttpClientInterface $httpClient;
    private string $host;

    public function __construct(
        string $host,
        private readonly string $stream,
        private readonly string $username,
        private readonly string $password,
        private readonly int $port = 8000,
        int|string|Level        $level = Level::Debug,
        bool                    $bubble = true,
        ?HttpClientInterface    $httpClient = null
    ) {
        parent::__construct($level, $bubble);
        $this->httpClient = $httpClient ?? new CurlHttpClient();
        $this->host = rtrim($host, '/');
    }

    #[\Override]
    protected function write(LogRecord $record): void
    {
        if (is_string($record['formatted'])) {
            $this->send($record['formatted']);
        }
    }

    protected function send(string $data): void
    {
        $httpHeaders = [
            'Content-Type: application/json',
            sprintf('X-P-Stream: %s', $this->stream),
            sprintf('Authorization: Basic %s', base64_encode($this->username . ':' . $this->password)),
        ];

        $this->httpClient->send(
            sprintf('%s:%d/%s', $this->host, $this->port, self::INGESTION_PATH),
            $httpHeaders,
            $data
        );
    }

    #[\Override]
    public function handleBatch(array $records): void
    {
        $level = $this->level;
        $records = array_filter(
            $records,
            static function (LogRecord $record) use ($level): bool {
                return ($record->level->value >= $level->value);
            }
        );

        if ($records) {
            $this->send(
                (string) $this->getFormatter()
                    ->formatBatch($records)
            );
        }
    }

    #[\Override]
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new JsonFormatter();
    }
}
