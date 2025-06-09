# Monolog Parseable Handler

A PHP library that provides a [Monolog](https://github.com/Seldaek/monolog) handler for sending logs to [Parseable](https://parseable.com/) - a cloud-native log analytics platform.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](https://php.net/)

## Features

- **Clean Architecture**: Built with dependency injection and hexagonal architecture principles
- **PSR-3 Compliant**: Fully compatible with PSR-3 logging standards through Monolog
- **Batch Processing**: Supports both individual and batch log processing
- **Configurable**: Flexible configuration for different Parseable instances
- **Type Safe**: Full PHP 8.1+ type declarations with Psalm static analysis
- **Testable**: Comprehensive test suite with mocked dependencies

## Requirements

- PHP 8.1 or higher
- ext-curl
- Monolog 3.0+

## Installation

Install via Composer:

```bash
composer require senzidee/monolog-parseable-handler
```

## Quick Start

```php
<?php

use Monolog\Logger;
use SenzaIdee\Handler\ParseableHandler;

// Create the handler
$handler = new ParseableHandler(
    host: 'https://your-parseable-instance.com',
    stream: 'application-logs',
    username: 'your-username',
    password: 'your-password',
    port: 8000
);

// Create logger and add handler
$logger = new Logger('app');
$logger->pushHandler($handler);

// Start logging
$logger->info('Application started');
$logger->error('Something went wrong', ['error_code' => 500]);
```

## Configuration

### Constructor Parameters

The `ParseableHandler` constructor accepts the following parameters:

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `host` | `string` | Yes | - | Parseable server hostname (without trailing slash) |
| `stream` | `string` | Yes | - | Target log stream name |
| `username` | `string` | Yes | - | Authentication username |
| `password` | `string` | Yes | - | Authentication password |
| `port` | `int` | No | `8000` | Parseable server port |
| `level` | `Level\|int\|string` | No | `Level::Debug` | Minimum log level to handle |
| `bubble` | `bool` | No | `true` | Whether to bubble logs to next handler |
| `httpClient` | `HttpClientInterface` | No | `null` | Custom HTTP client (uses cURL by default) |

### Example Configurations

#### Basic Configuration
```php
$handler = new ParseableHandler(
    host: 'https://logs.company.com',
    stream: 'api-logs',
    username: 'api-user',
    password: 'secure-password'
);
```

#### Production Configuration
```php
$handler = new ParseableHandler(
    host: 'https://prod-logs.company.com',
    stream: 'production-app',
    username: $_ENV['PARSEABLE_USERNAME'],
    password: $_ENV['PARSEABLE_PASSWORD'],
    port: 443,
    level: Level::Warning, // Only log warnings and above
    bubble: false
);
```

For more advanced usage examples, see [USAGE.md](USAGE.md).

## Development

### Local Development with Docker

If you don't have PHP installed locally, use the provided Docker setup:

```bash
# Build the development container
docker build -t parseable-handler-dev .

# Run commands in container
docker run --rm -v $(pwd):/app parseable-handler-dev composer install
docker run --rm -v $(pwd):/app parseable-handler-dev composer test
```

### Available Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Fix code style
composer cs-fixer

# Run static analysis
composer psalm

# All quality checks
composer test && composer cs-fixer && composer psalm
```

For detailed development information, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/senzidee/monolog-parseable-handler/issues)
- **Documentation**: [Parseable Documentation](https://docs.parseable.com/)
- **Monolog**: [Monolog Documentation](https://github.com/Seldaek/monolog/blob/main/README.md)

## Related Projects

- [Monolog](https://github.com/Seldaek/monolog) - The PHP logging library this handler extends
- [Parseable](https://parseable.com/) - The log analytics platform this handler targets
- [PSR-3](https://www.php-fig.org/psr/psr-3/) - PHP logging interface standard