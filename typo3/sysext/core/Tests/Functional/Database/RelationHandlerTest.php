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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
                    'fieldname' => 'categories',
                    'sorting' => 0,
                    'sorting_foreign' => 0,
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 1,
                    'sorting_foreign' => 0,
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 2,
                    'sorting_foreign' => 1,
                ],
                3 => [
                    'id' => 1020,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 3,
                    'sorting_foreign' => 0,
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
                    'fieldname' => 'categories',
                    'sorting' => 0,
                    'sorting_foreign' => 0,
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 1,
                    'sorting_foreign' => 0,
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 2,
                    'sorting_foreign' => 1,
                ],
                4 => [
                    'id' => 1021,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 4,
                    'sorting_foreign' => 0,
                ],
                5 => [
                    'id' => 1031,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 5,
                    'sorting_foreign' => 0,
                ],
                6 => [
                    'id' => 1040,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 6,
                    'sorting_foreign' => 0,
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
                    'fieldname' => 'categories',
                    'sorting' => 0,
                    'sorting_foreign' => 0,
                ],
                1 => [
                    'id' => 1000,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 1,
                    'sorting_foreign' => 0,
                ],
                2 => [
                    'id' => 1010,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 2,
                    'sorting_foreign' => 1,
                ],
                3 => [
                    'id' => 1020,
                    'table' => 'pages',
                    'fieldname' => 'categories',
                    'sorting' => 3,
                    'sorting_foreign' => 0,
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

    #[Test]
    public function purgeItemArrayReturnsFalseIfVersioningForTableIsDisabled(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationHandler/readMmSysCategoryRelationsImport.csv');

        $GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = false;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = new RelationHandler();
        $subject->tableArray = [
            'sys_category' => [1, 10, 20],
        ];

        self::assertFalse($subject->purgeItemArray(0));
    }

    #[Test]
    public function purgeItemArrayReturnsTrueIfItemsHaveBeenPurged(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RelationHandler/readMmSysCategoryRelationsImport.csv');

        $GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = new RelationHandler();
        $subject->tableArray = [
            'sys_category' => [1, 10, 20],
        ];

        self::assertTrue($subject->purgeItemArray(1));
    }
}
