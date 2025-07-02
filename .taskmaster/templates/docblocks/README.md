# 📚 XGATE PHP SDK - Docblock Templates

This directory contains standardized PHPDoc templates for consistent documentation across the XGATE PHP SDK. These templates ensure all code follows the same documentation patterns and integrates seamlessly with PHPStan validation and PHP CS Fixer formatting rules.

## 🎯 Template Categories

### Core Templates
- **[class.md](class.md)** - Class-level documentation templates
- **[interface.md](interface.md)** - Interface documentation templates  
- **[method.md](method.md)** - Method and function documentation templates
- **[property.md](property.md)** - Property and constant documentation templates

### Specialized Templates
- **[exception.md](exception.md)** - Exception class documentation templates
- **[dto.md](dto.md)** - Data Transfer Object documentation templates
- **[resource.md](resource.md)** - API Resource class documentation templates
- **[configuration.md](configuration.md)** - Configuration class documentation templates

### Code Examples
- **[examples.md](examples.md)** - Complete docblock examples from the codebase
- **[patterns.md](patterns.md)** - Common documentation patterns and best practices

## 🔧 Integration with Tools

### PHPStan Validation
All templates are designed to pass PHPStan level 8 validation with our custom configuration:
- Strict type checking enabled
- Missing parameter/return type detection
- Invalid PHPDoc format detection
- Custom tag validation

### PHP CS Fixer Formatting
Templates follow PHP CS Fixer rules for automatic formatting:
- Vertical alignment of PHPDoc tags
- Proper tag ordering and grouping
- Consistent spacing and indentation
- Automatic annotation formatting

## 📋 Usage Guidelines

### 1. Copy Template Structure
Copy the relevant template structure and customize for your specific use case:

```php
/**
 * [Brief one-line description ending with period]
 *
 * [Detailed description explaining purpose, behavior, and context.
 * Can span multiple lines and include implementation details.]
 *
 * @package XGate\[Namespace]
 * @author XGate PHP SDK Contributors
 * @since 1.0.0
 *
 * @example [Description of example]
 * ```php
 * // Code example here
 * ```
 */
```

### 2. Follow Tag Ordering
Always use this tag order (enforced by PHP CS Fixer):
1. `@package`, `@author`, `@since`, `@version`
2. `@param`, `@return`
3. `@throws`, `@exception`
4. `@api`, `@internal`, `@deprecated`
5. `@see`, `@link`, `@example`
6. `@todo`, `@fixme`

### 3. Type Annotations
Use precise type annotations compatible with PHPStan:

```php
/**
 * @param array<string, mixed> $data    Associative array of data
 * @param string|null          $value   Optional string value
 * @param int<1, 100>         $limit    Limit between 1 and 100
 * @return Customer[]                   Array of Customer objects
 */
```

### 4. Exception Documentation
Always document all possible exceptions:

```php
/**
 * @throws ApiException     When API returns error response
 * @throws NetworkException When network request fails
 * @throws \InvalidArgumentException When parameters are invalid
 */
```

## 🎨 Formatting Standards

### Alignment
Use vertical alignment for better readability:

```php
/**
 * @param string      $name        Customer name
 * @param string      $email       Customer email
 * @param string|null $phone       Optional phone number
 * @param array       $metadata    Additional metadata
 * @return Customer                Created customer instance
 */
```

### Examples
Always include practical examples for public methods:

```php
/**
 * @example Basic usage
 * ```php
 * $customer = $resource->create('John Doe', 'john@example.com');
 * echo "Created: " . $customer->name;
 * ```
 *
 * @example With optional parameters
 * ```php
 * $customer = $resource->create(
 *     name: 'Jane Smith',
 *     email: 'jane@example.com',
 *     phone: '+1234567890',
 *     metadata: ['source' => 'website']
 * );
 * ```
 */
```

### Package Organization
Use consistent package naming:
- `@package XGate\Model` - Data Transfer Objects
- `@package XGate\Resource` - API Resource classes
- `@package XGate\Exception` - Exception classes
- `@package XGate\Configuration` - Configuration classes
- `@package XGate\Authentication` - Authentication classes
- `@package XGate\Http` - HTTP client classes

## 🔍 Quality Checks

### Validation Commands
Use these commands to validate documentation:

```bash
# Check documentation formatting
composer docs-check

# Apply documentation fixes
composer docs-fix

# Full documentation validation
composer docs-validate

# PHPStan with documentation rules
composer phpstan-docs
```

### Common Issues
- Missing `@param` or `@return` tags
- Incorrect type annotations
- Misaligned PHPDoc tags
- Missing examples for public methods
- Inconsistent package naming

## 📖 Best Practices

1. **Be Descriptive**: Write clear, helpful descriptions that explain WHY, not just WHAT
2. **Include Context**: Explain how the method fits into the larger system
3. **Provide Examples**: Always include practical usage examples
4. **Document Edge Cases**: Mention special behaviors, limitations, or requirements
5. **Keep Updated**: Update documentation when code changes
6. **Use Consistent Language**: Follow the same terminology throughout the project

## 🚀 Quick Start

1. Choose the appropriate template for your code element
2. Copy the template structure
3. Fill in the specific details for your implementation
4. Run `composer docs-check` to validate formatting
5. Run `composer phpstan-docs` to validate types and structure

For detailed examples and patterns, see the individual template files in this directory. 