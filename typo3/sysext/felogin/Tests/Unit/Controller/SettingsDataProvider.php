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

namespace TYPO3\CMS\Felogin\Tests\Unit\Controller;

class SettingsDataProvider
{
    /**
     * 0
     * ├── 1
     * │   ├── 2
     * │   │   └── 3
     * │   └── 4
     * └── 5
     *     └── 6
     * @return array[] [$settingsPages, $settingsRecursive, $expected]
     */
    public static function storageFoldersDataProvider(): array
    {
        return [
            ['1', 1, [1, 2]],
            ['2', 0, [2]],
            ['1, 5', 0, [1, 5]],
            [' 1', 250, [1, 2, 3, 4]],
            ['1,5 ', 250, [1, 2, 3, 4, 5, 6]],
        ];
    }

    /**
     * partly mocks @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getTreeList
     * for the @see storageFoldersDataProvider scenario
     */
    public static function treeListMethodMock($id, $depth): string
    {
        $map = [
            1 => [
                0 => '',
                1 => '2',
                250 => '2,3,4',
            ],
            -1 => [
                0 => '1',
                1 => '1,2',
                250 => '1,2,3,4',
            ],
            2 => [
                0 => '',
                1 => '3',
                250 => '3',
            ],
            -2 => [
                0 => '2',
                1 => '2,3',
                250 => '2,3',
            ],
            5 => [
                0 => '',
                1 => '6',
                250 => '6',
            ],
            -5 => [
                0 => '5',
                1 => '5,6',
                250 => '5,6',
            ],
        ];
        return $map[$id][$depth];
    }
}
