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

namespace TYPO3\CMS\Recycler\Tests\Functional\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeletedRecordsPaginationTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['recycler'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/tt_content.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function makeInstanceReturnsFreshInstancesForDeletedRecords(): void
    {
        $first = GeneralUtility::makeInstance(DeletedRecords::class);
        $second = GeneralUtility::makeInstance(DeletedRecords::class);
        self::assertNotSame($first, $second, 'Each makeInstance call must return a separate instance');
    }

    #[Test]
    public function getTotalCountReturnsSameValueOnRepeatedCalls(): void
    {
        $model = GeneralUtility::makeInstance(DeletedRecords::class);
        $firstTotal = $model->getTotalCount(1, '', 999, '');
        self::assertGreaterThan(0, $firstTotal, 'Fixtures should contain deleted records');

        // Calling loadData + getTotalCount on separate instances (as the controller does)
        // must produce the same total.
        $loadModel = GeneralUtility::makeInstance(DeletedRecords::class);
        $loadModel->loadData(1, '', 999, '0,3', '');

        $countModel = GeneralUtility::makeInstance(DeletedRecords::class);
        $secondTotal = $countModel->getTotalCount(1, '', 999, '');

        self::assertSame(
            $firstTotal,
            $secondTotal,
            'getTotalCount must return the same value regardless of other makeInstance/loadData calls'
        );
    }

    #[Test]
    public function paginationAcrossMultipleTablesCoversAllRecordsWithoutDuplicates(): void
    {
        $pageSize = 3;

        // Get the correct total from a fresh instance (first container access)
        $model = GeneralUtility::makeInstance(DeletedRecords::class);
        $expectedTotal = $model->getTotalCount(1, '', 999, '');
        self::assertGreaterThan($pageSize, $expectedTotal, 'Need more records than page size to test pagination');

        $allRecords = [];
        $offset = 0;
        $maxIterations = 20;

        while ($offset < $expectedTotal && $maxIterations-- > 0) {
            $pageModel = GeneralUtility::makeInstance(DeletedRecords::class);
            $pageModel->loadData(1, '', 999, $offset . ',' . $pageSize, '');
            $rows = $pageModel->getDeletedRows();

            $pageRecords = [];
            foreach ($rows as $table => $tableRows) {
                foreach ($tableRows as $row) {
                    $pageRecords[] = $table . ':' . $row['uid'];
                }
            }

            // Each full page must have exactly $pageSize records
            if ($offset + $pageSize <= $expectedTotal) {
                self::assertCount(
                    $pageSize,
                    $pageRecords,
                    'Page at offset ' . $offset . ' should have exactly ' . $pageSize . ' records'
                );
            } else {
                // Last page: should have the remainder
                $expectedRemainder = $expectedTotal - $offset;
                self::assertCount(
                    $expectedRemainder,
                    $pageRecords,
                    'Last page at offset ' . $offset . ' should have ' . $expectedRemainder . ' records'
                );
            }

            $allRecords = array_merge($allRecords, $pageRecords);
            $offset += $pageSize;
        }

        // No duplicates across pages
        self::assertCount(
            count($allRecords),
            array_unique($allRecords),
            'No duplicate records should appear across pages'
        );

        // All records covered
        self::assertCount(
            $expectedTotal,
            $allRecords,
            'Pagination must cover all ' . $expectedTotal . ' deleted records'
        );
    }
}
