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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

/**
 * Fixture file
 */
class MethodArgumentDroppedMatcherFixture
{
    public function aMethod()
    {
        // Match: euc_char_mapping() shouldn't be called with more than 2 args
        $foo->euc_char_mapping('arg1', 'arg2', 'arg3');

        // No match: Two args is ok
        $foo->euc_char_mapping('arg1', 'arg2');
        // No match: Static call
        $foo::euc_char_mapping('arg1', 'arg2', 'arg3');
        // No match: With argument unpacking we don't know how many args are actually given
        $args = [ 'arg1', 'arg2', 'arg3' ];
        $foo->euc_char_mapping(...$args);
        // No match: Too many args, but with argument unpacking we don't know about empty arrays
        $args1 = [ 'arg1', 'arg2', 'arg3' ];
        $args2 = [ 'arg4', 'arg5', 'arg6' ];
        $args3 = [ 'arg7', 'arg8', 'arg9' ];
        $foo->euc_char_mapping(...$args1, ...$args2, ...$args3);
        // No match: Only 2 args given is ok
        $foo->euc_char_mapping('arg1', 'arg2');
        // @extensionScannerIgnoreLine
        $foo->euc_char_mapping('arg1', 'arg2', 'arg3');
    }
}
