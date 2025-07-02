<?php

declare(strict_types=1);

/**
 * PHPStan Bootstrap File
 * 
 * This file is loaded before PHPStan analysis begins.
 * It sets up custom rules and configurations for docblock validation.
 * 
 * @package XGate\PHPStan
 */

// Register custom autoloader for PHPStan rules if needed
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Define custom docblock validation constants
define('PHPSTAN_XGATE_REQUIRE_PARAM_DOCS', true);
define('PHPSTAN_XGATE_REQUIRE_RETURN_DOCS', true);
define('PHPSTAN_XGATE_REQUIRE_THROWS_DOCS', true);

// Custom docblock tags that PHPStan should recognize
$customTags = [
    'api',           // Marks public API methods
    'internal',      // Marks internal methods
    'experimental',  // Marks experimental features
    'deprecated',    // Marks deprecated methods
    'todo',          // TODO items
    'fixme',         // FIXME items
    'security',      // Security-related notes
    'performance',   // Performance-related notes
    'example',       // Usage examples
    'link',          // External links
    'see',           // See also references
    'since',         // Version information
    'author',        // Author information
    'copyright',     // Copyright information
    'license',       // License information
    'package',       // Package information
    'subpackage',    // Subpackage information
    'version',       // Version information
    'psalm-type',    // Psalm type definitions
    'phpstan-type',  // PHPStan type definitions
];

// Make custom tags available globally
foreach ($customTags as $tag) {
    if (!defined('PHPSTAN_CUSTOM_TAG_' . strtoupper($tag))) {
        define('PHPSTAN_CUSTOM_TAG_' . strtoupper($tag), $tag);
    }
}

// Initialize custom error reporting
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

// Set up memory limit for large codebases
ini_set('memory_limit', '512M');

// Custom function to validate docblock patterns
function validateDocblockPattern(string $docblock, string $pattern): bool
{
    return preg_match($pattern, $docblock) === 1;
}

// Register shutdown function for cleanup
register_shutdown_function(function () {
    // Cleanup any temporary files or resources
    // This is called after PHPStan analysis completes
}); 