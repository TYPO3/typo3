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

namespace TYPO3\CMS\Core\Tests\Functional\Database;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RelationHandlerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    public static function readMmSysCategoryRelationsDataProvider(): \Generator
    {
        yield 'live relations' => [
            'categoryUid' => 10,
            'workspaceUid' => 0,
            'expected' => [
                0 => [
                    'id' => 1000,
                    'table' => 'tt_content',
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                ],
                3 => [
                    'id' => 1020,
                    'table' => 'pages',
                ],
            ],
        ];
        yield 'workspace 1 relations' => [
            'categoryUid' => 10,
            'workspaceUid' => 1,
            'expected' => [
                0 => [
                    'id' => 1000,
                    'table' => 'tt_content',
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                ],
                4 => [
                    'id' => 1021,
                    'table' => 'pages',
                ],
                5 => [
                    'id' => 1031,
                    'table' => 'pages',
                ],
                6 => [
                    'id' => 1040,
                    'table' => 'pages',
                ],
            ],
        ];
        yield 'workspace 2 relations' => [
            'categoryUid' => 10,
            'workspaceUid' => 2,
            'expected' => [
                0 => [
                    'id' => 1000,
                    'table' => 'tt_content',
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                ],
                3 => [
                    'id' => 1020,
                    'table' => 'pages',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('readMmSysCategoryRelationsDataProvider')]
    public function readMmSysCategoryRelations(int $categoryUid, int $workspaceUid, array $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationHandler/readMmSysCategoryRelationsImport.csv');
        $fieldConfig = $GLOBALS['TCA']['sys_category']['columns']['items']['config'];
        $subject = new RelationHandler();
        $subject->setWorkspaceId($workspaceUid);
        $subject->start('', '*', 'sys_category_record_mm', $categoryUid, 'sys_category', $fieldConfig);
        self::assertSame($expected, $subject->itemArray);
    }
}
