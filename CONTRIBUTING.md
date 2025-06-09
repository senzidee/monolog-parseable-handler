# Contributing to Monolog Parseable Handler

Thank you for considering contributing to this project! We welcome contributions from everyone.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your feature or bugfix
4. Make your changes
5. Run the test suite
6. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Docker (optional, for local development without PHP)

### Local Development

```bash
# Clone your fork
git clone https://github.com/your-username/monolog-parseable-handler.git
cd monolog-parseable-handler

# Install dependencies
composer install

# Run tests to ensure everything is working
composer test
```

### Using Docker

If you don't have PHP installed locally:

```bash
# Build the development container
docker build -t parseable-handler-dev .

# Install dependencies
docker run --rm -v $(pwd):/app parseable-handler-dev composer install

# Run tests
docker run --rm -v $(pwd):/app parseable-handler-dev composer test
```

## Development Workflow

### Quality Checks

Before submitting a pull request, ensure all quality checks pass:

```bash
# Run all quality checks
composer test && composer cs-fixer && composer psalm
```

### Individual Commands

```bash
# Run tests
composer test
# or
vendor/bin/phpunit

# Fix code style
composer cs-fixer
# or
vendor/bin/php-cs-fixer fix src

# Run static analysis
composer psalm
# or
vendor/bin/psalm
```

### Running Tests with Coverage

```bash
# Generate HTML coverage report
vendor/bin/phpunit --coverage-html coverage/

# View coverage report
open coverage/index.html
```

## Code Standards

### PHP Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use strict typing: `declare(strict_types=1);`
- Use PHP 8.1+ features appropriately (readonly properties, named parameters, etc.)
- Write self-documenting code with meaningful variable and method names

### Architecture Principles

This project follows **hexagonal architecture** and **clean code** principles:

#### Dependency Injection
```php
// ✅ Good - Dependencies injected
public function __construct(
    private readonly HttpClientInterface $httpClient
) {}

// ❌ Bad - Hard dependency
public function __construct()
{
    $this->httpClient = new CurlHttpClient();
}
```

#### Interface Segregation
```php
// ✅ Good - Focused interface
interface HttpClientInterface
{
    public function send(string $url, array $headers, string $data): string;
}

// ❌ Bad - Too many responsibilities
interface HttpClientInterface
{
    public function send(string $url, array $headers, string $data): string;
    public function configure(array $config): void;
    public function getStats(): array;
    public function clearCache(): void;
}
```

#### Immutability
```php
// ✅ Good - Readonly properties
public function __construct(
    private readonly string $host,
    private readonly string $stream
) {}

// ❌ Bad - Mutable state
public function __construct(string $host, string $stream)
{
    $this->host = $host;
    $this->stream = $stream;
}
```

### Testing Standards

- Write tests for all new functionality
- Aim for high test coverage (>90%)
- Use descriptive test method names
- Follow the Arrange-Act-Assert pattern
- Mock external dependencies

```php
public function testWriteSendsFormattedRecord(): void
{
    // Arrange
    $formattedData = '{"message":"Test message"}';
    $this->mockHttpClient->expects($this->once())
        ->method('send')
        ->with(/* expected parameters */);

    // Act
    $this->handler->write($record);

    // Assert - implicit through mock expectations
}
```

## Pull Request Process

### Before Submitting

1. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes** following the coding standards

3. **Write or update tests** for your changes

4. **Run quality checks**:
   ```bash
   composer test && composer cs-fixer && composer psalm
   ```

5. **Update documentation** if needed

6. **Commit your changes** with a descriptive message:
   ```bash
   git commit -m "Add support for custom timeout configuration"
   ```

### Pull Request Guidelines

- **Title**: Use a clear, descriptive title
- **Description**: Explain what changes you made and why
- **Link issues**: Reference any related issues
- **Screenshots**: Include screenshots for UI changes (if applicable)

### Pull Request Template

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] I have run the full test suite

## Checklist
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
```

## Types of Contributions

### Bug Reports

When reporting bugs, please include:

- **Environment**: PHP version, OS, Monolog version
- **Expected behavior**: What you expected to happen
- **Actual behavior**: What actually happened
- **Steps to reproduce**: Minimal code example
- **Error messages**: Full error output

### Feature Requests

When suggesting features:

- **Use case**: Explain why this feature would be useful
- **Implementation**: Suggest how it could be implemented
- **Alternatives**: Consider alternative solutions
- **Breaking changes**: Note if this would break existing code

### Documentation

- Fix typos and grammar errors
- Improve code examples
- Add missing documentation
- Clarify confusing sections

## Code Review Process

### What We Look For

- **Correctness**: Does the code work as intended?
- **Architecture**: Does it follow hexagonal architecture principles?
- **Testing**: Are there appropriate tests?
- **Performance**: Are there any performance implications?
- **Security**: Are there any security concerns?
- **Documentation**: Is the code well-documented?

### Review Timeline

- **Initial response**: Within 48 hours
- **Full review**: Within 1 week
- **Follow-up**: Within 24 hours of updates

## Community Guidelines

### Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Assume positive intent

### Communication

- **Issues**: Use GitHub issues for bug reports and feature requests
- **Discussions**: Use GitHub discussions for general questions
- **Security**: Email security issues privately

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

1. Update version in `composer.json`
2. Run full test suite
3. Update documentation
4. Create release notes
5. Tag release
6. Publish to Packagist

## Questions?

If you have questions about contributing:

1. Check existing issues and documentation
2. Open a GitHub discussion
3. Reach out to maintainers

Thank you for contributing to making this project better!