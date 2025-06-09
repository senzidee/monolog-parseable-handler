# Usage Guide

This document provides comprehensive examples and best practices for using the Monolog Parseable Handler in your PHP applications.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Configuration Examples](#configuration-examples)
- [Framework Integration](#framework-integration)
- [Advanced Usage](#advanced-usage)
- [Custom HTTP Clients](#custom-http-clients)
- [Error Handling](#error-handling)
- [Performance Considerations](#performance-considerations)
- [Production Best Practices](#production-best-practices)

## Basic Usage

### Simple Logger Setup

```php
<?php

use Monolog\Logger;
use Monolog\Level;
use SenzaIdee\Handler\ParseableHandler;

// Create the Parseable handler
$parseableHandler = new ParseableHandler(
    host: 'https://your-parseable-instance.com',
    stream: 'application-logs',
    username: 'your-username',
    password: 'your-password'
);

// Create logger and add the handler
$logger = new Logger('app');
$logger->pushHandler($parseableHandler);

// Log messages
$logger->info('Application started successfully');
$logger->warning('Low disk space detected', ['disk_usage' => '85%']);
$logger->error('Database connection failed', [
    'host' => 'db.example.com',
    'port' => 5432,
    'error_code' => 'CONNECTION_TIMEOUT'
]);
```

### Multiple Handlers Configuration

Combine Parseable with other handlers for comprehensive logging:

```php
<?php

use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use SenzaIdee\Handler\ParseableHandler;

$logger = new Logger('app');

// Local file logging for development
$logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));

// Rotating file handler for persistent local logs
$logger->pushHandler(new RotatingFileHandler(
    filename: '/var/log/app.log',
    maxFiles: 30,
    level: Level::Info
));

// Parseable handler for centralized logging
$logger->pushHandler(new ParseableHandler(
    host: $_ENV['PARSEABLE_HOST'],
    stream: 'production-app',
    username: $_ENV['PARSEABLE_USERNAME'],
    password: $_ENV['PARSEABLE_PASSWORD'],
    level: Level::Warning // Only send warnings and errors to Parseable
));
```

## Configuration Examples

### Environment-Based Configuration

```php
<?php

class LoggerFactory
{
    public static function create(string $environment): Logger
    {
        $logger = new Logger('app');
        
        return match ($environment) {
            'development' => self::createDevelopmentLogger($logger),
            'staging' => self::createStagingLogger($logger),
            'production' => self::createProductionLogger($logger),
            default => throw new InvalidArgumentException("Unknown environment: $environment")
        };
    }
    
    private static function createDevelopmentLogger(Logger $logger): Logger
    {
        // Console output for development
        $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        return $logger;
    }
    
    private static function createStagingLogger(Logger $logger): Logger
    {
        // File + Parseable for staging
        $logger->pushHandler(new StreamHandler('/var/log/app.log', Level::Info));
        $logger->pushHandler(new ParseableHandler(
            host: $_ENV['PARSEABLE_STAGING_HOST'],
            stream: 'staging-app',
            username: $_ENV['PARSEABLE_USERNAME'],
            password: $_ENV['PARSEABLE_PASSWORD'],
            level: Level::Info
        ));
        return $logger;
    }
    
    private static function createProductionLogger(Logger $logger): Logger
    {
        // Only critical logs to Parseable in production
        $logger->pushHandler(new ParseableHandler(
            host: $_ENV['PARSEABLE_PROD_HOST'],
            stream: 'production-app',
            username: $_ENV['PARSEABLE_USERNAME'],
            password: $_ENV['PARSEABLE_PASSWORD'],
            port: 443,
            level: Level::Error,
            bubble: false // Don't pass to other handlers
        ));
        return $logger;
    }
}

// Usage
$logger = LoggerFactory::create($_ENV['APP_ENV'] ?? 'development');
```

### Stream-Specific Configuration

Organize logs by application component using different streams:

```php
<?php

class ComponentLoggerFactory
{
    private string $host;
    private string $username;
    private string $password;
    
    public function __construct(string $host, string $username, string $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }
    
    public function createApiLogger(): Logger
    {
        $logger = new Logger('api');
        $logger->pushHandler(new ParseableHandler(
            host: $this->host,
            stream: 'api-requests',
            username: $this->username,
            password: $this->password,
            level: Level::Info
        ));
        return $logger;
    }
    
    public function createDatabaseLogger(): Logger
    {
        $logger = new Logger('database');
        $logger->pushHandler(new ParseableHandler(
            host: $this->host,
            stream: 'database-queries',
            username: $this->username,
            password: $this->password,
            level: Level::Warning
        ));
        return $logger;
    }
    
    public function createSecurityLogger(): Logger
    {
        $logger = new Logger('security');
        $logger->pushHandler(new ParseableHandler(
            host: $this->host,
            stream: 'security-events',
            username: $this->username,
            password: $this->password,
            level: Level::Notice
        ));
        return $logger;
    }
}
```

## Framework Integration

### Laravel Integration

Create a custom log channel in `config/logging.php`:

```php
<?php

// config/logging.php
return [
    'channels' => [
        'parseable' => [
            'driver' => 'custom',
            'via' => App\Logging\ParseableLoggerFactory::class,
            'host' => env('PARSEABLE_HOST'),
            'stream' => env('PARSEABLE_STREAM', 'laravel-app'),
            'username' => env('PARSEABLE_USERNAME'),
            'password' => env('PARSEABLE_PASSWORD'),
            'port' => env('PARSEABLE_PORT', 8000),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
    ],
];
```

```php
<?php

// app/Logging/ParseableLoggerFactory.php
namespace App\Logging;

use Monolog\Logger;
use SenzaIdee\Handler\ParseableHandler;

class ParseableLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('laravel');
        
        $handler = new ParseableHandler(
            host: $config['host'],
            stream: $config['stream'],
            username: $config['username'],
            password: $config['password'],
            port: $config['port'] ?? 8000,
            level: $config['level'] ?? 'debug'
        );
        
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
```

Usage in Laravel:

```php
<?php

// In your Laravel application
Log::channel('parseable')->info('User registered', ['user_id' => 123]);

// Or set as default
Log::info('Default channel message'); // Will use parseable if set as default
```

### Symfony Integration

Configure in `config/packages/monolog.yaml`:

```yaml
monolog:
    handlers:
        parseable:
            type: service
            id: App\Log\ParseableHandler
            level: info
        
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            channels: ["!event"]
```

```php
<?php

// src/Log/ParseableHandler.php
namespace App\Log;

use SenzaIdee\Handler\ParseableHandler as BaseParseableHandler;

class ParseableHandler extends BaseParseableHandler
{
    public function __construct()
    {
        parent::__construct(
            host: $_ENV['PARSEABLE_HOST'],
            stream: $_ENV['PARSEABLE_STREAM'],
            username: $_ENV['PARSEABLE_USERNAME'],
            password: $_ENV['PARSEABLE_PASSWORD']
        );
    }
}
```

## Advanced Usage

### Batch Logging for Performance

Process multiple log entries efficiently:

```php
<?php

use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Level;
use SenzaIdee\Handler\ParseableHandler;

$handler = new ParseableHandler(
    host: 'https://parseable.example.com',
    stream: 'batch-processing',
    username: 'user',
    password: 'pass'
);

$logger = new Logger('batch-processor');
$logger->pushHandler($handler);

// Collect multiple log records
$records = [];
foreach ($dataToProcess as $item) {
    $records[] = new LogRecord(
        datetime: new \DateTimeImmutable(),
        channel: 'batch',
        level: Level::Info,
        message: "Processed item {$item['id']}",
        context: ['item_data' => $item],
        extra: []
    );
}

// Send all records in a batch
$handler->handleBatch($records);
```

### Structured Logging with Context

Make logs searchable and analyzable:

```php
<?php

class UserActionLogger
{
    private Logger $logger;
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    
    public function logUserAction(string $action, int $userId, array $metadata = []): void
    {
        $context = [
            'user_id' => $userId,
            'action' => $action,
            'timestamp' => time(),
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'metadata' => $metadata
        ];
        
        $this->logger->info("User action: {$action}", $context);
    }
    
    public function logApiRequest(string $endpoint, string $method, int $responseCode, float $duration): void
    {
        $context = [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $responseCode,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => time()
        ];
        
        $level = $responseCode >= 400 ? Level::Warning : Level::Info;
        $this->logger->log($level, "API request to {$endpoint}", $context);
    }
}

// Usage
$userLogger = new UserActionLogger($logger);
$userLogger->logUserAction('login', 123, ['login_method' => 'oauth']);
$userLogger->logApiRequest('/api/users', 'GET', 200, 0.045);
```

## Custom HTTP Clients

### Guzzle HTTP Client Implementation

For more advanced HTTP features, implement a custom client:

```php
<?php

namespace App\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SenzaIdee\Http\HttpClientInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    private Client $client;
    
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);
    }
    
    public function send(string $url, array $headers, string $data, array $options = []): string
    {
        try {
            $response = $this->client->post($url, [
                'headers' => $this->parseHeaders($headers),
                'body' => $data,
                'timeout' => $options['timeout'] ?? 10,
            ]);
            
            return $response->getBody()->getContents();
        } catch (GuzzleException $e) {
            throw new \RuntimeException("HTTP client error: " . $e->getMessage());
        }
    }
    
    private function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $header) {
            [$key, $value] = explode(': ', $header, 2);
            $parsed[$key] = $value;
        }
        return $parsed;
    }
}

// Usage with custom client
$customHttpClient = new GuzzleHttpClient();
$handler = new ParseableHandler(
    host: 'https://parseable.example.com',
    stream: 'custom-client',
    username: 'user',
    password: 'pass',
    httpClient: $customHttpClient
);
```

### Retry Logic HTTP Client

Implement automatic retries for reliability:

```php
<?php

namespace App\Http;

use SenzaIdee\Http\HttpClientInterface;

class RetryHttpClient implements HttpClientInterface
{
    private HttpClientInterface $innerClient;
    private int $maxRetries;
    private int $retryDelay;
    
    public function __construct(
        HttpClientInterface $innerClient,
        int $maxRetries = 3,
        int $retryDelay = 1000 // milliseconds
    ) {
        $this->innerClient = $innerClient;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }
    
    public function send(string $url, array $headers, string $data, array $options = []): string
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $this->innerClient->send($url, $headers, $data, $options);
            } catch (\Exception $e) {
                $lastException = $e;
                
                if ($attempt < $this->maxRetries) {
                    usleep($this->retryDelay * 1000 * $attempt); // Exponential backoff
                }
            }
        }
        
        throw new \RuntimeException(
            "HTTP request failed after {$this->maxRetries} attempts: " . $lastException->getMessage()
        );
    }
}
```

## Error Handling

### Graceful Error Handling

Handle network issues without breaking your application:

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use SenzaIdee\Handler\ParseableHandler;

class ResilientLoggerFactory
{
    public static function create(): Logger
    {
        $logger = new Logger('resilient-app');
        
        // Always have a fallback handler
        $fallbackHandler = new StreamHandler('/var/log/app-fallback.log');
        $logger->pushHandler($fallbackHandler);
        
        try {
            // Try to set up Parseable handler
            $parseableHandler = new ParseableHandler(
                host: $_ENV['PARSEABLE_HOST'],
                stream: 'production-app',
                username: $_ENV['PARSEABLE_USERNAME'],
                password: $_ENV['PARSEABLE_PASSWORD']
            );
            
            // Add Parseable handler but let it bubble to fallback
            $logger->pushHandler($parseableHandler);
            
        } catch (\Exception $e) {
            // Log the configuration error to fallback
            $logger->error('Failed to configure Parseable handler', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $logger;
    }
}
```

### Dead Letter Queue Pattern

Implement a pattern to handle failed log deliveries:

```php
<?php

namespace App\Log;

use SenzaIdee\Handler\ParseableHandler;
use SenzaIdee\Http\HttpClientInterface;

class DeadLetterParseableHandler extends ParseableHandler
{
    private string $deadLetterPath;
    
    public function __construct(
        string $host,
        string $stream,
        string $username,
        string $password,
        string $deadLetterPath = '/var/log/failed-parseable.log',
        int $port = 8000,
        $level = Level::Debug,
        bool $bubble = true,
        ?HttpClientInterface $httpClient = null
    ) {
        parent::__construct($host, $stream, $username, $password, $port, $level, $bubble, $httpClient);
        $this->deadLetterPath = $deadLetterPath;
    }
    
    protected function send(string $data): void
    {
        try {
            parent::send($data);
        } catch (\Exception $e) {
            // Write failed logs to dead letter file
            $failedLog = [
                'timestamp' => date('c'),
                'error' => $e->getMessage(),
                'data' => $data
            ];
            
            file_put_contents(
                $this->deadLetterPath,
                json_encode($failedLog) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
    }
}
```

## Performance Considerations

### Asynchronous Logging

For high-throughput applications, consider async logging:

```php
<?php

namespace App\Log;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use SenzaIdee\Handler\ParseableHandler;

class AsyncParseableHandler extends AbstractProcessingHandler
{
    private ParseableHandler $parseableHandler;
    private array $buffer = [];
    private int $bufferSize;
    
    public function __construct(ParseableHandler $parseableHandler, int $bufferSize = 100)
    {
        parent::__construct();
        $this->parseableHandler = $parseableHandler;
        $this->bufferSize = $bufferSize;
        
        // Register shutdown function to flush remaining logs
        register_shutdown_function([$this, 'flush']);
    }
    
    protected function write(LogRecord $record): void
    {
        $this->buffer[] = $record;
        
        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }
    
    public function flush(): void
    {
        if (!empty($this->buffer)) {
            $this->parseableHandler->handleBatch($this->buffer);
            $this->buffer = [];
        }
    }
}
```

### Memory-Efficient Batch Processing

Handle large log volumes efficiently:

```php
<?php

class LogBatchProcessor
{
    private ParseableHandler $handler;
    private int $batchSize;
    
    public function __construct(ParseableHandler $handler, int $batchSize = 1000)
    {
        $this->handler = $handler;
        $this->batchSize = $batchSize;
    }
    
    public function processLogFile(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }
        
        $batch = [];
        $lineNumber = 0;
        
        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                
                $logData = json_decode(trim($line), true);
                if ($logData === null) {
                    continue; // Skip invalid JSON lines
                }
                
                $record = new LogRecord(
                    datetime: new \DateTimeImmutable($logData['datetime'] ?? 'now'),
                    channel: $logData['channel'] ?? 'imported',
                    level: Level::fromName($logData['level'] ?? 'info'),
                    message: $logData['message'] ?? '',
                    context: $logData['context'] ?? [],
                    extra: $logData['extra'] ?? []
                );
                
                $batch[] = $record;
                
                if (count($batch) >= $this->batchSize) {
                    $this->handler->handleBatch($batch);
                    $batch = [];
                }
            }
            
            // Process remaining records
            if (!empty($batch)) {
                $this->handler->handleBatch($batch);
            }
            
        } finally {
            fclose($handle);
        }
    }
}
```

## Production Best Practices

### Configuration Management

```php
<?php

class ParseableConfig
{
    private string $host;
    private string $stream;
    private string $username;
    private string $password;
    private int $port;
    private Level $level;
    
    public function __construct()
    {
        $this->host = $this->getRequiredEnv('PARSEABLE_HOST');
        $this->stream = $this->getRequiredEnv('PARSEABLE_STREAM');
        $this->username = $this->getRequiredEnv('PARSEABLE_USERNAME');
        $this->password = $this->getRequiredEnv('PARSEABLE_PASSWORD');
        $this->port = (int) ($_ENV['PARSEABLE_PORT'] ?? 8000);
        $this->level = Level::fromName($_ENV['LOG_LEVEL'] ?? 'info');
    }
    
    public function createHandler(?HttpClientInterface $httpClient = null): ParseableHandler
    {
        return new ParseableHandler(
            host: $this->host,
            stream: $this->stream,
            username: $this->username,
            password: $this->password,
            port: $this->port,
            level: $this->level,
            httpClient: $httpClient
        );
    }
    
    private function getRequiredEnv(string $key): string
    {
        $value = $_ENV[$key] ?? null;
        if ($value === null) {
            throw new \RuntimeException("Required environment variable {$key} is not set");
        }
        return $value;
    }
}
```

### Health Check Integration

Monitor your logging pipeline:

```php
<?php

class LoggingHealthCheck
{
    private ParseableHandler $handler;
    
    public function __construct(ParseableHandler $handler)
    {
        $this->handler = $handler;
    }
    
    public function check(): array
    {
        $status = [
            'parseable_logging' => 'unknown',
            'last_check' => date('c'),
            'details' => []
        ];
        
        try {
            // Send a test log
            $testRecord = new LogRecord(
                datetime: new \DateTimeImmutable(),
                channel: 'health-check',
                level: Level::Info,
                message: 'Health check test log',
                context: ['check_id' => uniqid()],
                extra: []
            );
            
            $this->handler->handle($testRecord);
            
            $status['parseable_logging'] = 'healthy';
            $status['details']['message'] = 'Successfully sent test log to Parseable';
            
        } catch (\Exception $e) {
            $status['parseable_logging'] = 'unhealthy';
            $status['details']['error'] = $e->getMessage();
        }
        
        return $status;
    }
}
```

### Monitoring and Metrics

Track logging performance:

```php
<?php

namespace App\Log;

use SenzaIdee\Handler\ParseableHandler;
use SenzaIdee\Http\HttpClientInterface;

class MetricsParseableHandler extends ParseableHandler
{
    private array $metrics = [
        'logs_sent' => 0,
        'logs_failed' => 0,
        'total_bytes_sent' => 0,
        'avg_response_time' => 0
    ];
    
    protected function send(string $data): void
    {
        $startTime = microtime(true);
        
        try {
            parent::send($data);
            
            $this->metrics['logs_sent']++;
            $this->metrics['total_bytes_sent'] += strlen($data);
            
            $duration = microtime(true) - $startTime;
            $this->updateAverageResponseTime($duration);
            
        } catch (\Exception $e) {
            $this->metrics['logs_failed']++;
            throw $e;
        }
    }
    
    public function getMetrics(): array
    {
        return $this->metrics;
    }
    
    private function updateAverageResponseTime(float $newTime): void
    {
        $totalLogs = $this->metrics['logs_sent'];
        $currentAvg = $this->metrics['avg_response_time'];
        
        $this->metrics['avg_response_time'] = (($currentAvg * ($totalLogs - 1)) + $newTime) / $totalLogs;
    }
}
```
