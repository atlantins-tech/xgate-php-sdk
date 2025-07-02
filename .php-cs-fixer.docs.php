<?php

declare(strict_types=1);

/**
 * PHP CS Fixer Configuration - Documentation Focus
 * 
 * This configuration file focuses specifically on documentation
 * and PHPDoc formatting rules. Use this for documentation-only fixes.
 * 
 * @package XGate\SDK
 * @author XGate PHP SDK Contributors
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->in(__DIR__ . '/examples')
    ->exclude('vendor')
    ->exclude('.phpunit.cache')
    ->exclude('.php-cs-fixer.cache')
    ->name('*.php')
    ->notName('*.blade.php')
    ->notName('*.twig')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        // Only documentation and PHPDoc related rules
        
        // ===== CORE PHPDOC FORMATTING RULES =====
        'phpdoc_align' => [
            'align' => 'vertical',
            'tags' => [
                'param', 'return', 'throws', 'type', 'var', 'property',
                'property-read', 'property-write', 'method',
                'api', 'internal', 'deprecated', 'since', 'author',
                'see', 'link', 'example', 'todo', 'fixme',
            ],
        ],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => [
            'tags' => [
                'example', 'id', 'internal', 'inheritdoc', 'inheritDoc', 
                'link', 'source', 'toc', 'tutorial',
            ],
        ],
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => [
            'replacements' => [
                'property-read' => 'property',
                'property-write' => 'property',
                'type' => 'var',
                'link' => 'see',
            ],
        ],
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => false, // Allow @package tags for namespace organization
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => [
            'order' => [
                'param', 'return', 'throws',
                'api', 'internal', 'deprecated', 'since', 'author',
                'see', 'link', 'example', 'todo', 'fixme',
            ],
        ],
        'phpdoc_order_by_value' => [
            'annotations' => [
                'author', 'covers', 'coversNothing', 'dataProvider', 'depends', 
                'group', 'internal', 'method', 'property', 'property-read', 
                'property-write', 'requires', 'throws', 'uses',
            ],
        ],
        'phpdoc_return_self_reference' => [
            'replacements' => [
                'this' => '$this',
                '@this' => '$this',
                '$self' => 'self',
                '@self' => 'self',
                '$static' => 'static',
                '@static' => 'static',
            ],
        ],
        'phpdoc_scalar' => [
            'types' => ['boolean', 'double', 'integer', 'real', 'str'],
        ],
        'phpdoc_separation' => [
            'groups' => [
                ['deprecated', 'link', 'see', 'since'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['property', 'property-read', 'property-write'],
                ['param', 'return'],
                ['throws', 'exception'],
                ['api', 'internal'],
                ['example', 'todo', 'fixme'],
            ],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_tag_casing' => [
            'tags' => [
                'inheritDoc' => 'inheritdoc',
                'inheritdoc' => 'inheritdoc',
            ],
        ],
        'phpdoc_tag_type' => [
            'tags' => [
                'api' => 'annotation',
                'author' => 'annotation',
                'copyright' => 'annotation',
                'deprecated' => 'annotation',
                'example' => 'annotation',
                'global' => 'annotation',
                'inheritDoc' => 'annotation',
                'internal' => 'annotation',
                'license' => 'annotation',
                'method' => 'annotation',
                'package' => 'annotation',
                'param' => 'annotation',
                'property' => 'annotation',
                'property-read' => 'annotation',
                'property-write' => 'annotation',
                'return' => 'annotation',
                'see' => 'annotation',
                'since' => 'annotation',
                'throws' => 'annotation',
                'todo' => 'annotation',
                'uses' => 'annotation',
                'var' => 'annotation',
                'version' => 'annotation',
            ],
        ],
        'phpdoc_to_comment' => [
            'ignored_tags' => [
                'api', 'internal', 'deprecated', 'todo', 'fixme',
                'example', 'security', 'performance',
            ],
        ],
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types' => [
            'groups' => ['simple', 'alias', 'meta'],
        ],
        'phpdoc_types_order' => [
            'sort_algorithm' => 'alpha',
            'null_adjustment' => 'always_last',
        ],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name' => true,
        
        // ===== GENERAL DOCBLOCK RULES =====
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'expectedException', 
                'expectedExceptionMessage', 
                'expectedExceptionMessageRegExp',
            ],
        ],
        'general_phpdoc_tag_rename' => [
            'fix_annotation' => true,
            'fix_inline' => true,
            'replacements' => [
                'inheritDocs' => 'inheritDoc',
            ],
            'case_sensitive' => false,
        ],
        
        // ===== COMMENT FORMATTING =====
        'comment_to_phpdoc' => [
            'ignored_tags' => [
                'todo', 'fixme', 'note', 'hack', 'xxx',
            ],
        ],
        'multiline_comment_opening_closing' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => false,
            'remove_inheritdoc' => false,
        ],
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        
        // ===== MINIMAL FORMATTING FOR DOCUMENTATION CONTEXT =====
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw'],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],
        'visibility_required' => [
            'elements' => ['const', 'method', 'property'],
        ],
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true) // Allow risky rules for documentation improvements
    ->setUsingCache(true)
    ->setUnsupportedPhpVersionAllowed(true); 