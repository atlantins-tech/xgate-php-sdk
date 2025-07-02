# 📖 Documentation Standards for XGATE PHP SDK

This document outlines the comprehensive documentation standards, tools, and processes for maintaining high-quality code documentation in the XGATE PHP SDK project.

## 🎯 Overview

Our documentation strategy focuses on:
- **Consistency**: Standardized formatting and structure across all code
- **Completeness**: Comprehensive coverage of all public APIs and complex logic
- **Quality**: Accurate, up-to-date, and helpful documentation
- **Automation**: Automated validation and enforcement through tooling

## 📋 PHPDoc Standards

### Required Documentation
All public classes, methods, and properties **MUST** have PHPDoc documentation. Private and protected members **SHOULD** be documented when their purpose is not immediately clear.

### Documentation Structure
Follow this order for PHPDoc tags:
1. **Description** (required)
2. **Long description** (if needed)
3. **@param** tags (for methods)
4. **@return** tag (for methods that return values)
5. **@throws** tags (for methods that can throw exceptions)
6. **@example** tags (for complex methods)
7. **Other tags** (@since, @deprecated, @see, etc.)

### Class Documentation Template
```php
/**
 * Brief description of the class purpose.
 *
 * Longer description explaining the class responsibilities,
 * usage patterns, and any important implementation details.
 *
 * @package Xgate\Resources
 * @since 1.0.0
 * @example
 * ```php
 * $resource = new CustomerResource($httpClient, $logger);
 * $customer = $resource->create(['name' => 'John Doe']);
 * ```
 */
class CustomerResource extends AbstractResource
{
    // Class implementation
}
```

### Method Documentation Template
```php
/**
 * Brief description of what the method does.
 *
 * Longer description explaining the method behavior,
 * side effects, and usage patterns.
 *
 * @param string $id The unique identifier for the resource
 * @param array<string, mixed> $data Optional data to include in the request
 * @param array<string, string> $headers Optional HTTP headers
 * @return Customer The retrieved customer object
 * @throws ValidationException When the ID format is invalid
 * @throws ApiException When the API request fails
 * @throws NetworkException When network connectivity issues occur
 * @example
 * ```php
 * $customer = $resource->get('cust_123', ['include' => 'transactions']);
 * echo $customer->getName();
 * ```
 */
public function get(string $id, array $data = [], array $headers = []): Customer
{
    // Method implementation
}
```

### Property Documentation Template
```php
/**
 * Brief description of the property purpose.
 *
 * @var HttpClientInterface The HTTP client for API communication
 * @since 1.0.0
 */
private HttpClientInterface $httpClient;

/**
 * Configuration options for the API client.
 *
 * @var array<string, mixed> {
 *     @type string $api_key The API authentication key
 *     @type string $base_url The base URL for API requests
 *     @type int $timeout Request timeout in seconds
 *     @type bool $verify_ssl Whether to verify SSL certificates
 * }
 */
private array $config;
```

## 🔧 Type Declarations

### Scalar Types
- Use specific scalar types: `string`, `int`, `float`, `bool`
- Use `mixed` only when truly necessary
- Prefer union types over `mixed`: `string|int` instead of `mixed`

### Array Types
- Use generic array syntax: `array<string, mixed>`
- Be specific about key and value types: `array<int, Customer>`
- Document array structure in complex cases:
```php
/**
 * @param array<string, mixed> $data {
 *     @type string $name Customer name
 *     @type string $email Customer email
 *     @type array<string, string> $metadata Optional metadata
 * }
 */
```

### Object Types
- Use fully qualified class names: `\Xgate\DTOs\Customer`
- Use interfaces when appropriate: `\Psr\Log\LoggerInterface`
- Document nullable types: `?Customer` or `Customer|null`

### Collections
- Use specific collection types: `array<int, Customer>`
- Document collection behavior:
```php
/**
 * @return array<int, Customer> Array of customer objects indexed by position
 */
```

## 🎨 Formatting Standards

### Line Length
- Keep PHPDoc lines under **80 characters** when possible
- Break long descriptions into multiple lines
- Align parameter descriptions for readability

### Alignment
- Align `@param` descriptions at column 40
- Align `@return` descriptions consistently
- Use consistent spacing between tags

### Example:
```php
/**
 * Create a new customer with the provided data.
 *
 * @param string                $name     Customer full name
 * @param string                $email    Customer email address
 * @param string                $document Customer CPF or CNPJ
 * @param array<string, mixed>  $metadata Optional customer metadata
 * @return Customer                       The created customer object
 * @throws ValidationException            When validation fails
 */
```

## 📝 Description Guidelines

### Writing Style
- Use **present tense** for descriptions: "Creates a customer" not "Will create"
- Be **concise but complete**: Explain what, not how
- Use **active voice**: "Validates input" not "Input is validated"
- Start with **action verbs** for methods: "Retrieves", "Creates", "Updates"

### Content Requirements
- **What**: Clearly state what the method/class does
- **When**: Explain under what conditions it's used
- **Why**: Provide context for complex logic
- **How**: Include usage examples for complex APIs

### Examples
```php
// ✅ Good
/**
 * Retrieves a customer by their unique identifier.
 */

// ❌ Bad
/**
 * Gets customer.
 */

// ✅ Good
/**
 * Validates customer data against business rules and API constraints.
 * 
 * Performs comprehensive validation including email format, document
 * number validation (CPF/CNPJ), and required field checks.
 */

// ❌ Bad
/**
 * Validates data.
 */
```

## 🔍 Exception Documentation

### Required Exception Documentation
Document **all** exceptions that can be thrown:
```php
/**
 * @throws ValidationException When input data fails validation
 * @throws AuthenticationException When API key is invalid or missing
 * @throws RateLimitException When API rate limit is exceeded
 * @throws NetworkException When network request fails
 * @throws ApiException When API returns an error response
 */
```

### Exception Hierarchy
Document the exception hierarchy in base classes:
```php
/**
 * Base exception for all XGATE SDK exceptions.
 *
 * Exception hierarchy:
 * - XgateException (base)
 *   - ValidationException (input validation errors)
 *   - AuthenticationException (authentication failures)
 *   - RateLimitException (rate limiting)
 *   - NetworkException (network connectivity issues)
 *   - ApiException (API response errors)
 */
```

## 📚 Example Documentation

### When to Include Examples
- **Complex methods** with multiple parameters
- **Non-obvious usage** patterns
- **Common use cases** that developers will need
- **Error handling** patterns

### Example Format
Use fenced code blocks with PHP syntax highlighting:
```php
/**
 * @example Basic usage
 * ```php
 * $client = new XgateClient(['api_key' => 'your-key']);
 * $customer = $client->getCustomerResource()->create([
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com'
 * ]);
 * ```
 * 
 * @example Error handling
 * ```php
 * try {
 *     $customer = $resource->get($id);
 * } catch (ValidationException $e) {
 *     echo "Invalid ID: " . $e->getMessage();
 * } catch (ApiException $e) {
 *     echo "API Error: " . $e->getMessage();
 * }
 * ```
 */
```

## 🛠️ Tooling Integration

### PHPStan Configuration
Our PHPStan configuration enforces:
- **Level 8** strict analysis
- **Docblock validation** for all public methods
- **Type coverage** requirements
- **Custom rules** for documentation quality

### PHP CS Fixer Rules
Documentation-specific formatting rules:
- **Docblock alignment** and spacing
- **Tag ordering** enforcement
- **Line length** limits
- **Consistent formatting** across the codebase

### IDE Integration
- **Template insertion** for new classes/methods
- **Real-time validation** with PHPStan
- **Automatic formatting** with PHP CS Fixer
- **Type hints** and autocompletion

## 📋 Quality Checklist

Before committing code, ensure:

### Documentation Completeness
- [ ] All public classes have PHPDoc blocks
- [ ] All public methods have PHPDoc blocks
- [ ] All parameters are documented with types
- [ ] Return types are documented
- [ ] All thrown exceptions are documented

### Documentation Quality
- [ ] Descriptions are clear and concise
- [ ] Types are specific and accurate
- [ ] Examples are provided for complex methods
- [ ] Grammar and spelling are correct
- [ ] Formatting follows project standards

### Tool Validation
- [ ] PHPStan analysis passes (level 8)
- [ ] PHP CS Fixer formatting is applied
- [ ] No documentation-related warnings
- [ ] IDE integration works correctly

## 🚀 Automation

### Pre-commit Hooks Setup

Set up automated quality enforcement:
```bash
# One-time setup
./scripts/setup-hooks.sh
```

**Pre-commit Hook Checks:**
1. **PHP Syntax Validation** - Ensures all PHP files are syntactically valid
2. **Code Style Enforcement** - Runs PHP CS Fixer to ensure consistent formatting
3. **Static Analysis** - Executes PHPStan to catch potential issues and documentation gaps
4. **Documentation Validation** - Checks for missing PHPDoc on public methods
5. **Critical Tests** - Runs unit tests to prevent broken functionality
6. **Final Validation** - Checks for debug statements and TODO comments

**Commit Message Hook:**
- Enforces conventional commit format: `type(scope): description`
- Validates commit message length and format
- Provides helpful error messages with examples

### Manual Quality Commands

```bash
# Run all quality checks
composer run quality

# Documentation-specific checks
composer run docs-validate

# Fix documentation formatting
composer run docs-fix

# Check documentation formatting
composer run docs-check

# Skip hooks in emergencies only
git commit --no-verify
SKIP_TESTS=1 git commit
```

### CI/CD Integration
Continuous integration checks:
- **Documentation coverage** reporting
- **Link validation** in documentation
- **Example code** validation
- **API documentation** generation

## 📖 Templates and Examples

### Available Templates
Located in `.taskmaster/templates/docblocks/`:
- **class.md** - Class documentation patterns
- **method.md** - Method documentation templates  
- **property.md** - Property documentation standards
- **examples.md** - Example code formatting

### Using Templates
1. **IDE Integration**: Use PHP DocBlocker or similar extensions
2. **Manual Creation**: Follow template patterns from documentation
3. **Validation**: Run quality checks to ensure compliance

## 🔄 Maintenance

### Regular Reviews
- **Monthly documentation audits** for accuracy
- **Quarterly template updates** based on new patterns
- **Annual standards review** for improvements

### Continuous Improvement
- **Developer feedback** integration
- **Tool updates** and configuration refinement
- **Best practice evolution** based on industry standards

## 📞 Support

### Getting Help
- **IDE Setup**: See `.taskmaster/docs/IDE_SETUP.md`
- **Quality Issues**: Run `composer run docs-validate`
- **Template Questions**: Check `.taskmaster/templates/docblocks/`
- **Tool Problems**: See troubleshooting in IDE setup guide

### Contributing
- **Standards Updates**: Submit pull requests for improvements
- **Template Additions**: Add new patterns as they emerge
- **Tool Enhancements**: Suggest configuration improvements

---

*These documentation standards ensure consistent, high-quality documentation across the XGATE PHP SDK. All developers are expected to follow these guidelines to maintain professional code documentation.* 