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
class MethodCallMatcherFixture
{
    public function aMethod()
    {
        // Match: confirmMsg() needs at least 4 args
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4');
        // Match: confirmMsg() can be called with 5 args (1 optional)
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4', 'arg5');
        // Match: With argument unpacking we don't know how many args are actually given
        $args = [ 'arg1', 'arg2' ];
        $foo->confirmMsg(...$args);
        // Match: Too many args but some could be empty arrays
        $foo->confirmMsg(...$arg1, ...$arg2, ...$arg3, ...$arg4, ...$arg5, ...$arg6);

        \confirmMsg();

        // No match: Only 3 args given
        $foo->confirmMsg('arg1', 'arg2', 'arg3');
        // No match: Too many arguments given
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4', 'arg5', 'arg6');
        // No match: Only 3 args given and called statically
        $foo::confirmMsg('arg1', 'arg2', 'arg3');
        // No match: Called statically
        $foo::confirmMsg('arg1', 'arg2', 'arg3', 'arg4');
        // No match: Called statically
        $foo::confirmMsg('arg1', 'arg2', 'arg3', 'arg4', 'arg5');
        // No match: Line ignored
        // @extensionScannerIgnoreLine
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4');
        // @extensionScannerIgnoreLine
        // No match: Line ignored and annotation belongs to code line below
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4');
        // No match since @extensionScannerIgnoreLine annotation is used
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4');
        // @extensionScannerIgnoreLine
        $foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4', 'arg5');
        // @extensionScannerIgnoreLine
        $bar->bar($foo->confirmMsg('arg1', 'arg2', 'arg3', 'arg4', 'arg5'));
    }
}
