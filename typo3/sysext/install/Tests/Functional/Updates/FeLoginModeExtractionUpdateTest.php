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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\IntegerType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\FeLoginModeExtractionUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FeLoginModeExtractionUpdateTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function columnDoesNotExistTest(): void
    {
        // "updateNecessary" will return FALSE since the "fe_login_mode" column is no longer defined by TYPO3
        self::assertFalse((new FeLoginModeExtractionUpdate())->updateNecessary());
    }

    /**
     * @test
     * @dataProvider functionalityUsedTestDataProvider
     */
    public function functionalityUsedTest(string $csvDataSet, bool $updateNecessary): void
    {
        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->createSchemaManager();

        if (!isset($schemaManager->listTableColumns('pages')['fe_login_mode'])) {
            $schemaManager->alterTable(
                new TableDiff(
                    'pages',
                    [
                        new Column('fe_login_mode', new IntegerType(), ['default' => 0, 'unsigned' => true]),
                    ]
                )
            );
        }

        $this->importCSVDataSet(__DIR__ . '/Fixtures/' . $csvDataSet . '.csv');
        self::assertEquals($updateNecessary, (new FeLoginModeExtractionUpdate())->updateNecessary());
    }

    public function functionalityUsedTestDataProvider(): \Generator
    {
        yield 'Column exist but functionality not used' => [
            'FeLoginModeNotUsed',
            false,
        ];
        yield 'Column exist and is in use' => [
            'FeLoginModeUsed',
            true,
        ];
    }
}
