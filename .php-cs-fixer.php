<?php

declare(strict_types=1);

/**
 * PHP CS Fixer Configuration with Enhanced Documentation Rules
 * 
 * This configuration extends the base .php-cs-fixer.dist.php with
 * comprehensive docblock and documentation formatting rules.
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
        // Base rule sets
        '@PSR12' => true,
        '@PHP81Migration' => true,
        '@PhpCsFixer' => true,
        
        // Array and syntax rules
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        
        // Spacing and formatting
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=' => 'single_space',
                '=>' => 'single_space',
            ],
        ],
        'blank_line_before_statement' => [
            'statements' => [
                'break', 'continue', 'declare', 'return', 'throw', 'try',
                'if', 'for', 'foreach', 'while', 'switch', 'do',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'single_trait_insert_per_statement' => true,
        
        // ===== COMPREHENSIVE PHPDOC/DOCUMENTATION RULES =====
        
        // PHPDoc alignment and formatting
        'phpdoc_align' => [
            'align' => 'vertical',
            'tags' => [
                'param', 'return', 'throws', 'type', 'var', 'property',
                'property-read', 'property-write', 'method',
            ],
        ],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => [
            'tags' => ['example', 'id', 'internal', 'inheritdoc', 'inheritDoc', 'link', 'source', 'toc', 'tutorial'],
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
        'phpdoc_no_package' => false, // Allow @package tags
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order' => [
            'order' => [
                'param', 'return', 'throws',
                'api', 'internal', 'deprecated', 'since', 'author',
                'see', 'link', 'example', 'todo', 'fixme',
            ],
        ],
        'phpdoc_order_by_value' => [
            'annotations' => ['author', 'covers', 'coversNothing', 'dataProvider', 'depends', 'group', 'internal', 'method', 'property', 'property-read', 'property-write', 'requires', 'throws', 'uses'],
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
        
        // General docblock rules
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['expectedException', 'expectedExceptionMessage', 'expectedExceptionMessageRegExp'],
        ],
        'general_phpdoc_tag_rename' => [
            'fix_annotation' => true,
            'fix_inline' => true,
            'replacements' => [
                'inheritDocs' => 'inheritDoc',
            ],
            'case_sensitive' => false,
        ],
        
        // Comment and documentation formatting
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
        
        // Class and method documentation
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
                'case' => 'none',
            ],
        ],
        'class_definition' => [
            'single_line' => false,
            'single_item_single_line' => false,
            'multi_line_extends_each_single_line' => false,
            'space_before_parenthesis' => false,
        ],
        
        // Type declarations and hints
        'declare_strict_types' => true,
        'native_function_type_declaration_casing' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'types_spaces' => [
            'space' => 'none',
        ],
        
        // String and concatenation
        'concat_space' => ['spacing' => 'one'],
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'simple_to_complex_string_variable' => true,
        
        // Control structures
        'control_structure_continuation_position' => [
            'position' => 'same_line',
        ],
        'elseif' => true,
        'include' => true,
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_control_parentheses' => [
            'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'],
        ],
        'no_useless_else' => true,
        'switch_continue_to_break' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
        
        // Function and method rules
        'function_declaration' => [
            'closure_function_spacing' => 'one',
        ],
        'lambda_not_used_import' => true,
        'method_chaining_indentation' => true,
        'no_spaces_after_function_name' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'regular_callable_call' => true,
        'single_line_throw' => false,
        'static_lambda' => true,
        'use_arrow_functions' => true,
        
        // Array and list formatting
        'array_indentation' => true,
        'list_syntax' => ['syntax' => 'short'],
        'normalize_index_brace' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        
        // Operator and expression rules
        'assign_null_coalescing_to_coalesce_equal' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'explicit_indirect_variable' => true,
        'increment_style' => ['style' => 'post'],
        'logical_operators' => true,
        'no_useless_concat_operator' => true,
        'object_operator_without_whitespace' => true,
        'operator_linebreak' => [
            'only_booleans' => true,
            'position' => 'beginning',
        ],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'ternary_to_elvis_operator' => true,
        'ternary_to_null_coalescing' => true,
        
        // Namespace and use statements
        'blank_line_after_namespace' => true,
        'clean_namespace' => true,
        'no_leading_namespace_whitespace' => true,
        
        // Semicolon and statement rules
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line',
        ],
        'no_empty_statement' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'semicolon_after_instruction' => true,
        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],
        
        // Whitespace and line ending rules
        'array_push' => true,
        'blank_line_after_opening_tag' => true,
        'compact_nullable_typehint' => true,
        'linebreak_after_opening_tag' => true,
        'no_closing_tag' => true,
        'no_multiple_statements_per_line' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        
        // Miscellaneous rules
        'cast_spaces' => ['space' => 'single'],
        'echo_tag_syntax' => [
            'format' => 'long',
            'long_function' => 'echo',
            'shorten_simple_statements_only' => false,
        ],
        'magic_constant_casing' => true,
        'magic_method_casing' => true,
        'native_function_casing' => true,
        'no_alias_functions' => true,
        'no_homoglyph_names' => true,
        'non_printable_character' => [
            'use_escape_sequences_in_strings' => false,
        ],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => [
            'target' => 'newest',
        ],
        'php_unit_expectation' => [
            'target' => 'newest',
        ],
        'php_unit_mock' => [
            'target' => 'newest',
        ],
        'php_unit_namespaced' => [
            'target' => 'newest',
        ],
        'psr_autoloading' => true,
        'self_accessor' => true,
        'short_scalar_cast' => true,
        'visibility_required' => [
            'elements' => ['const', 'method', 'property'],
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setUnsupportedPhpVersionAllowed(true); 