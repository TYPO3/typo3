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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class GeneralUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function compileSelectedGetVarsFromArrayFiltersIncomingData(): void
    {
        $filter = 'foo,bar';
        $getArray = ['foo' => 1, 'cake' => 'lie'];
        $expected = ['foo' => 1];
        $result = GeneralUtility::compileSelectedGetVarsFromArray($filter, $getArray, false);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function compileSelectedGetVarsFromArrayUsesGetPostDataFallback(): void
    {
        $_GET['bar'] = '2';
        $filter = 'foo,bar';
        $getArray = ['foo' => 1, 'cake' => 'lie'];
        $expected = ['foo' => 1, 'bar' => '2'];
        $result = GeneralUtility::compileSelectedGetVarsFromArray($filter, $getArray, true);
        self::assertSame($expected, $result);
    }
}
