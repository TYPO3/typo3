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

namespace TYPO3\CMS\Extensionmanager\Tests\Functional\Updates;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\IntegerType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Extensionmanager\Updates\FeLoginModeExtractionUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FeLoginModeExtractionUpdateTest extends FunctionalTestCase
{
    #[Test]
    public function columnDoesNotExistTest(): void
    {
        // "updateNecessary" will return FALSE since the "fe_login_mode" column is no longer defined by TYPO3
        self::assertFalse((new FeLoginModeExtractionUpdate($this->get(ConnectionPool::class)))->updateNecessary());
    }

    #[DataProvider('functionalityUsedTestDataProvider')]
    #[Test]
    public function functionalityUsedTest(string $csvDataSet, bool $updateNecessary): void
    {
        $schemaManager = $this->get(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->createSchemaManager();

        $pagesDetails = $schemaManager->introspectTable('pages');
        if ($pagesDetails->hasColumn('fe_login_mode') === false) {
            $schemaManager->alterTable(
                new TableDiff(
                    $pagesDetails,
                    ['fe_login_mode' => new Column('fe_login_mode', new IntegerType(), ['default' => 0, 'unsigned' => true])],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                ),
            );
        }

        $this->importCSVDataSet(__DIR__ . '/Fixtures/' . $csvDataSet . '.csv');
        self::assertEquals($updateNecessary, (new FeLoginModeExtractionUpdate($this->get(ConnectionPool::class)))->updateNecessary());
    }

    public static function functionalityUsedTestDataProvider(): \Generator
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
