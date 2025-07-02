# 🔧 IDE Setup Guide for XGATE PHP SDK

This guide helps you configure your IDE for optimal development experience with the XGATE PHP SDK, including PHPStan integration, PHP CS Fixer, and comprehensive debugging support.

## 🎯 Quick Setup

### VS Code (Recommended)
1. Open the project in VS Code
2. Install recommended extensions (VS Code will prompt you)
3. Reload the window to activate all configurations
4. Everything should work out of the box!

### PhpStorm/IntelliJ IDEA
1. Open the project in PhpStorm
2. The `.phpstorm.meta.php` file will be automatically detected
3. Configure PHP interpreter and enable PHPStan/PHP CS Fixer plugins
4. Import code style settings from `.php-cs-fixer.php`

## 📋 Prerequisites

### Required Software
- **PHP 8.1+** with the following extensions:
  - `json`, `curl`, `mbstring`, `openssl`
  - `xdebug` (for debugging)
- **Composer** (latest version)
- **Git** (for version control)

### Development Dependencies
All development tools are already configured in `composer.json`:
```bash
composer install  # Installs PHPStan, PHP CS Fixer, PHPUnit, etc.
```

## 🎨 VS Code Configuration

### Recommended Extensions
The project includes `.vscode/extensions.json` with curated extensions:

**Core PHP Development:**
- `bmewburn.vscode-intelephense-client` - Advanced PHP IntelliSense
- `xdebug.php-debug` - Xdebug integration
- `junstyle.php-cs-fixer` - PHP CS Fixer integration
- `sanderRonde.phpstan-vscode` - PHPStan integration
- `recca0120.vscode-phpunit` - PHPUnit integration

**Documentation & Quality:**
- `neilbrayfield.php-docblocker` - Automatic PHPDoc generation
- `ms-vscode.vscode-todo-highlight` - TODO/FIXME highlighting

### Workspace Settings
The `.vscode/settings.json` file includes:
- **Automatic formatting** with PHP CS Fixer on save
- **PHPStan integration** with real-time error checking
- **File associations** for PHP and configuration files
- **Search exclusions** for vendor directories
- **Editor settings** optimized for PHP development

### Available Tasks
Access via `Ctrl+Shift+P` → "Tasks: Run Task":

- **PHPStan: Analyze** - Run static analysis
- **PHP CS Fixer: Fix** - Format code automatically
- **PHP CS Fixer: Check** - Check formatting without changes
- **PHPUnit: Run All Tests** - Execute test suite
- **Quality: Full Check** - Run all quality checks
- **Documentation: Validate** - Validate documentation standards

### Debug Configurations
The `.vscode/launch.json` provides:
- **Listen for Xdebug** - Debug web requests
- **Launch current script** - Debug the currently open PHP file
- **Launch PHPUnit Tests** - Debug test execution
- **Launch Example Script** - Debug example scripts with environment variables

## 🧠 PhpStorm Configuration

### Type Hints & Autocompletion
The `.phpstorm.meta.php` file provides:
- **Resource method returns** - Proper type hints for `getCustomerResource()`, etc.
- **Expected arguments** - Autocompletion for method parameters
- **Expected return values** - Enum-like values for status, type fields
- **Exception types** - Better error handling with exception hierarchy

### Code Style Integration
1. **Settings** → **Editor** → **Code Style** → **PHP**
2. **Import Scheme** → **PHP CS Fixer** → Select `.php-cs-fixer.php`
3. **Apply** the imported settings

### PHPStan Integration
1. **Settings** → **PHP** → **Quality Tools** → **PHPStan**
2. **Configuration file**: `phpstan.neon`
3. **Custom ruleset**: Enable for enhanced validation

## 🔍 Debugging Setup

### Xdebug Configuration
Add to your `php.ini` or create a separate `xdebug.ini`:

```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

### Environment Variables
Create a `.env` file in the project root:
```env
XGATE_API_KEY=your-sandbox-api-key
XGATE_BASE_URL=https://sandbox.xgate.com.br/api/v1
XGATE_DEBUG=true
XGATE_LOG_LEVEL=debug
```

### Debugging Examples
1. Set breakpoints in your code
2. Use the "Launch Example Script" debug configuration
3. The debugger will automatically start with proper environment variables

## 📚 Documentation Tools

### PHPDoc Templates
The project includes comprehensive docblock templates in `.taskmaster/templates/docblocks/`:
- **class.md** - Class documentation patterns
- **method.md** - Method documentation templates
- **property.md** - Property documentation standards
- **examples.md** - Example code formatting

### Automatic Documentation
Use the PHP DocBlocker extension (VS Code) or built-in features (PhpStorm):
1. Type `/**` above a class/method/property
2. Press `Enter` to generate a template
3. Fill in the details following the project templates

## ⚙️ Quality Tools Configuration

### PHPStan (Static Analysis)
- **Configuration**: `phpstan.neon`
- **Level**: 8 (maximum strictness)
- **Custom rules**: Enhanced docblock validation
- **Memory limit**: 1GB for large codebases

**Run commands:**
```bash
composer run phpstan          # Basic analysis
composer run phpstan-baseline # Generate baseline for existing issues
```

### PHP CS Fixer (Code Formatting)
- **Main config**: `.php-cs-fixer.php` (general code)
- **Docs config**: `.php-cs-fixer.docs.php` (documentation-focused)
- **Style**: PSR-12 with custom documentation rules

**Run commands:**
```bash
composer run cs-fix       # Fix all files
composer run cs-check     # Check without fixing
composer run docs-fix     # Fix documentation only
```

### PHPUnit (Testing)
- **Configuration**: `phpunit.xml.dist`
- **Coverage**: HTML reports in `coverage/` directory
- **Integration**: Full IDE integration for running/debugging tests

**Run commands:**
```bash
composer run test              # Run all tests
composer run test-coverage     # Run with coverage report
```

## 🚀 Productivity Tips

### Code Snippets
Create custom snippets for common patterns:
- **Resource method**: Quick template for API resource methods
- **Exception handling**: Standard try-catch blocks
- **PHPDoc blocks**: Consistent documentation format

### File Templates
Use IDE file templates for:
- **New resource classes** with proper inheritance
- **DTO classes** with validation and serialization
- **Test classes** with proper setup and teardown

### Live Templates (PhpStorm)
Configure live templates for:
- **API method signatures**
- **Exception throwing patterns**
- **Logging statements**

## 🔧 Troubleshooting

### Common Issues

**PHPStan not running:**
- Check PHP version compatibility (8.1+)
- Increase memory limit: `php -d memory_limit=2G vendor/bin/phpstan analyse`
- Clear cache: `vendor/bin/phpstan clear-result-cache`

**PHP CS Fixer not working:**
- Verify configuration file exists and is valid
- Check file permissions
- Run with verbose output: `vendor/bin/php-cs-fixer fix --verbose`

**Xdebug not connecting:**
- Verify Xdebug is installed: `php -m | grep xdebug`
- Check port configuration (default: 9003)
- Ensure firewall allows connections

**IntelliSense not working:**
- Rebuild project index
- Clear IDE caches
- Verify PHP interpreter is correctly configured

### Performance Optimization
- **Exclude vendor directories** from indexing
- **Use SSD storage** for better performance
- **Increase IDE memory** allocation if needed
- **Configure file watchers** to ignore build artifacts

## 📖 Additional Resources

### Official Documentation
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [PHP CS Fixer Documentation](https://cs.symfony.com/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Xdebug Documentation](https://xdebug.org/docs/)

### IDE-Specific Guides
- [VS Code PHP Development](https://code.visualstudio.com/docs/languages/php)
- [PhpStorm PHP Development](https://www.jetbrains.com/help/phpstorm/php.html)

### Community Resources
- [PHP The Right Way](https://phptherightway.com/)
- [Modern PHP Development](https://modernphp.com/)

---

*This setup guide ensures optimal development experience with the XGATE PHP SDK. For questions or improvements, please open an issue or contribute to the documentation.* 