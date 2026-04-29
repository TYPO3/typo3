<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This file represents the configuration for Code Sniffing PER-related
 * automatic checks of coding guidelines.
 *
 * Run it using runTests.sh, see 'runTests.sh -h' for more options.
 *
 * Fix entire core:
 * > Build/Scripts/runTests.sh -s cgl
 *
 * Fix your current patch:
 * > Build/Scripts/runTests.sh -s cglGit
 */
if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

// Return a Code Sniffing configuration using
// all sniffers needed for PER Coding Style 3.0
// and additionally:
//  - PHP single-line arrays should not have trailing comma.
//  - Single-line whitespace before closing semicolon are prohibited.
//  - Remove unused use statements in the PHP source code
return (new \PhpCsFixer\Config())
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->ignoreVCSIgnored(true)
            ->in([
                __DIR__ . '/../../typo3/sysext',
                __DIR__ . '/../../Build',
            ])
            ->exclude([
                'node_modules',
                'composer',
                'ExtensionScanner/Php/Matcher/Fixtures',
            ]),
    )
    ->setRiskyAllowed(true)
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PER-CS3x0' => true,
        // Override PER-CS3x0 default (single) to keep no space after cast operators
        'cast_spaces' => ['space' => 'none'],
        'declare_parentheses' => true,
        'dir_constant' => true,
        'function_to_constant' => [
            'functions' => [
                'get_called_class',
                'get_class',
                'get_class_this',
                'php_sapi_name',
                'phpversion',
                'pi',
            ],
        ],
        'type_declaration_spaces' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'list_syntax' => ['syntax' => 'short'],
        'modernize_strpos' => true,
        'modernize_types_casting' => true,
        'native_function_casing' => true,
        'native_function_invocation' => [
            'include' => [],
            'scope' => 'all',
            'strict' => true,
        ],
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_namespace_whitespace' => true,
        'no_null_property_initialization' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_nullsafe_operator' => true,
        // Override PER-CS3x0 default (union) to keep ?Type shorthand syntax
        'nullable_type_declaration' => [
            'syntax' => 'question_mark',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => ['order' => ['use_trait', 'case', 'constant', 'property']],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'php_unit_construct' => ['assertions' => ['assertEquals', 'assertSame', 'assertNotEquals', 'assertNotSame']],
        'php_unit_mock_short_will_return' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
            'methods' => [
                'any' => 'this',
                'atLeast' => 'this',
                'atLeastOnce' => 'this',
                'atMost' => 'this',
                'exactly' => 'this',
                'never' => 'this',
                'onConsecutiveCalls' => 'this',
                'once' => 'this',
                'returnArgument' => 'this',
                'returnCallback' => 'this',
                'returnSelf' => 'this',
                'returnValue' => 'this',
                'returnValueMap' => 'this',
                'throwException' => 'this',
            ],
        ],
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'protected_to_private' => true,
        'single_quote' => true,
        'single_line_comment_style' => ['comment_types' => ['hash']],
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    ]);
