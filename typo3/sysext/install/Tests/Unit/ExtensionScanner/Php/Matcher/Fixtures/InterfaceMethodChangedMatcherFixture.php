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
class InterfaceMethodChangedMatcherFixture
{
    /**
     * Match: Argument number 3 has been removed.
     *
     * @param $arg1
     * @param $arg2
     * @param $arg3
     */
    public function like1($arg1, $arg2, $arg3)
    {
        // Match: Call to ->like() with three arguments has been removed
        $foo->like1('arg1', 'arg2', 'arg3');
    }

    /**
     * Match: ignored
     *
     * @param $arg1
     * @param $arg2
     * @param $arg3
     * @extensionScannerIgnoreLine
     */
    public function like2($arg1, $arg2, $arg3)
    {
        // @extensionScannerIgnoreLine
        // Match: Call to ->like() with three arguments has been removed
        $foo->like2('arg1', 'arg2', 'arg3');
    }

    /**
     * No match: Only two arguments is ok.
     *
     * @param $arg1
     * @param $arg2
     */
    public function like3($arg1, $arg2)
    {
        // No match: Two arguments is ok
        $foo->like1('arg1', 'arg2');
        // No match: Static call is fine for interface methods
        Bar::like1('arg1', 'arg2', 'arg3');
    }

    /**
     * No match: Static does not make sense on interfaces we're looking for here
     *
     * @param $arg1
     * @param $arg2
     * @param $arg3
     */
    public static function like4($arg1, $arg2, $arg3)
    {
    }

    /**
     * No match: Protected
     *
     * @param $arg1
     * @param $arg2
     * @param $arg3
     */
    protected function like5($arg1, $arg2, $arg3)
    {
    }
}
