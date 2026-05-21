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

namespace TYPO3\CMS\Frontend\Tests\Unit\Preview;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Preview\PreviewSimulatorCalculator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PreviewSimulatorCalculatorTest extends UnitTestCase
{
    public static function getUserGroupIdsDataProvider(): array
    {
        return [
            'empty string' => ['', []],
            'zero' => ['0', []],
            'single group' => ['1', [1]],
            'multiple groups' => ['1,2,3', [1, 2, 3]],
            'zero and empty values are removed' => ['0,,2', [2]],
            'duplicates are removed' => ['2,1,2', [2, 1]],
            'whitespace is ignored' => [' 1 , 2 ', [1, 2]],
            'negative group is kept' => ['-2', [-2]],
            'non-numeric value is removed' => ['foo,3', [3]],
        ];
    }

    #[DataProvider('getUserGroupIdsDataProvider')]
    #[Test]
    public function getUserGroupIdsReturnsGroupIds(string $simulatedUserGroups, array $expected): void
    {
        self::assertSame($expected, new PreviewSimulatorCalculator()->getUserGroupIds($simulatedUserGroups));
    }

    public static function mergeUserGroupsDataProvider(): array
    {
        return [
            'nothing to merge' => [['', ''], ''],
            'only first value' => [['1', ''], '1'],
            'only second value' => [['', '2'], '2'],
            'same groups' => [['1', '1'], '1'],
            'different groups' => [['1', '2'], '1,2'],
            'overlapping groups' => [['1,2', '2,3'], '1,2,3'],
            'duplicates are removed' => [['1,1', '1,2'], '1,2'],
        ];
    }

    #[DataProvider('mergeUserGroupsDataProvider')]
    #[Test]
    public function mergeUserGroupsReturnsUniqueGroupIdsInOrder(array $simulatedUserGroups, string $expected): void
    {
        self::assertSame($expected, new PreviewSimulatorCalculator()->mergeUserGroups(...$simulatedUserGroups));
    }
}
