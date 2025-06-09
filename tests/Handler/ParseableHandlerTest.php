<?php

declare(strict_types=1);

namespace SenzaIdee\Tests\Handler;

use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SenzaIdee\Handler\ParseableHandler;
use SenzaIdee\Http\HttpClientInterface;

class ParseableHandlerTest extends TestCase
{
    private string $host = 'https://parseable.example.com';
    private string $stream = 'test-stream';
    private string $username = 'test-user';
    private string $password = 'test-password';
    private string $fullEndpoint = 'https://parseable.example.com:8000/api/v1/ingest';
    private int $port = 8000;
    private HttpClientInterface $mockHttpClient;
    private ParseableHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);

        $this->handler = new ParseableHandler(
            $this->host,
            $this->stream,
            $this->username,
            $this->password,
            $this->port,
            Level::Debug,
            true,
            $this->mockHttpClient
        );
    }

    public function testConstructorSetsProperties(): void
    {
        $reflection = new ReflectionClass($this->handler);

        $hostProperty = $reflection->getProperty('host');
        $streamProperty = $reflection->getProperty('stream');
        $usernameProperty = $reflection->getProperty('username');
        $passwordProperty = $reflection->getProperty('password');

        $this->assertEquals($this->host, $hostProperty->getValue($this->handler));
        $this->assertEquals($this->stream, $streamProperty->getValue($this->handler));
        $this->assertEquals($this->username, $usernameProperty->getValue($this->handler));
        $this->assertEquals($this->password, $passwordProperty->getValue($this->handler));
    }

    public function testWriteSendsFormattedRecord(): void
    {
        $formattedData = '{"message":"Test message","level":"error"}';

        $this->mockHttpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($this->fullEndpoint),
                $this->callback(function($headers) {
                    return in_array('Content-Type: application/json', $headers)
                        && in_array('X-P-Stream: ' . $this->stream, $headers)
                        && in_array('Authorization: Basic ' . base64_encode($this->username . ':' . $this->password), $headers);
                }),
                $this->equalTo($formattedData)
            );

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Error,
            'Test message',
            [],
            []
        );
        $record['formatted'] = $formattedData;

        $writeMethod = new ReflectionMethod(ParseableHandler::class, 'write');
        $writeMethod->invoke($this->handler, $record);
    }

    public function testHandleBatchFiltersRecordsByLevel(): void
    {
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $handler = new ParseableHandler(
            $this->host,
            $this->stream,
            $this->username,
            $this->password,
            $this->port,
            Level::Info,
            true,
            $mockHttpClient
        );

        $mockFormatter = $this->getMockBuilder(JsonFormatter::class)
            ->getMock();
        $handler->setFormatter($mockFormatter);

        $records = [
            new LogRecord(
                new \DateTimeImmutable(),
                'channel',
                Level::Debug,  // Should be filtered out
                'Debug message',
                [],
                []
            ),
            new LogRecord(
                new \DateTimeImmutable(),
                'channel',
                Level::Info,   // Should be included
                'Info message',
                [],
                []
            ),
            new LogRecord(
                new \DateTimeImmutable(),
                'channel',
                Level::Error,  // Should be included
                'Error message',
                [],
                []
            ),
        ];

        $mockFormatter->expects($this->once())
            ->method('formatBatch')
            ->with($this->callback(function($recordsParam) {
                if (count($recordsParam) !== 2) {
                    return false;
                }

                $error = array_pop($recordsParam);
                $info = array_pop($recordsParam);

                return $error instanceof LogRecord
                    && $error->level === Level::Error
                    && $info instanceof LogRecord
                    && $info->level === Level::Info;
            }))
            ->willReturn(json_encode(['batch' => 'test']));

        $mockHttpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($this->fullEndpoint),
                $this->anything(),
                $this->equalTo(json_encode(['batch' => 'test']))
            );

        $handler->handleBatch($records);
    }

    public function testDefaultFormatterIsJsonFormatter(): void
    {
        $method = new ReflectionMethod(ParseableHandler::class, 'getDefaultFormatter');

        $formatter = $method->invoke($this->handler);

        $this->assertInstanceOf(JsonFormatter::class, $formatter);
    }

    public function testSendMethodSetsCorrectHeaders(): void
    {
        $testData = '{"message":"test"}';

        // Set up the mock to expect a call to send with the correct headers
        $this->mockHttpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($this->fullEndpoint),
                $this->callback(function($headers) {
                    return in_array('Content-Type: application/json', $headers)
                        && in_array('X-P-Stream: ' . $this->stream, $headers)
                        && in_array('Authorization: Basic ' . base64_encode($this->username . ':' . $this->password), $headers);
                }),
                $this->equalTo($testData)
            );

        // Call the send method via reflection
        $method = new ReflectionMethod(ParseableHandler::class, 'send');
        $method->invoke($this->handler, $testData);
    }
}