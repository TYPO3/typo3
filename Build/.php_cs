<?php
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
 * This file represents the configuration for Code Sniffing PSR-2-related
 * automatic checks of coding guidelines
 * Install @fabpot's great php-cs-fixer tool via
 *
 *  $ composer global require friendsofphp/php-cs-fixer
 *
 * And then simply run
 *
 *  $ php-cs-fixer fix --config ../Build/.php_cs
 *
 * inside the TYPO3 directory. Warning: This may take up to 10 minutes.
 *
 * For more information read:
 * 	 http://www.php-fig.org/psr/psr-2/
 * 	 http://cs.sensiolabs.org
 */
if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}
// Define in which folders to search and which folders to exclude
// Exclude some directories that are excluded by Git anyways to speed up the sniffing
$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->exclude('typo3conf')
    ->exclude('typo3temp')
    ->notName('install.php')
    ->notName('index.php')
    ->in(__DIR__ . '/../');
// Return a Code Sniffing configuration using
// all sniffers needed for PSR-2
// and additionally:
//  - Remove leading slashes in use clauses.
//  - PHP single-line arrays should not have trailing comma.
//  - Single-line whitespace before closing semicolon are prohibited.
//  - Remove unused use statements in the PHP source code
//  - Ensure Concatenation to have at least one whitespace around
//  - Remove trailing whitespace at the end of blank lines.
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        'no_leading_import_slash' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_unused_imports' => true,
        'concat_space' => ['spacing' => 'one'],
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => true,
        'single_quote' => true,
        'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'no_blank_lines_after_phpdoc' => true,
        'array_syntax' => ['syntax' => 'short'],
        'whitespace_after_comma_in_array' => true,
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'no_alias_functions' => true,
        'lowercase_cast' => true,
        'no_leading_namespace_whitespace' => true,
        'native_function_casing' => true,
        'self_accessor' => true,
        'no_short_bool_cast' => true,
        'no_unneeded_control_parentheses' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_trim' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
    ])
    ->setFinder($finder);
