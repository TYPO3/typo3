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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A new record needs a page to be stored on. Where its "pid" points to a NEW id
 * nobody created, for example because an import failed to write the parent page,
 * where it points behind a record that does not exist, or where it carries no
 * "pid" at all, the record cannot be stored.
 */
final class NewRecordWithoutResolvablePidTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function recordOnAnUnresolvableNewParentIsSkippedAndLogged(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => [
                    'NEW1' => ['pid' => 'NEWnotCreated', 'title' => 'Orphan'],
                    'NEW2' => ['pid' => 1, 'title' => 'Sibling'],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        self::assertRecordIsSkippedAndLogged($dataHandler, 'pages');
        self::assertArrayHasKey('NEW2', $dataHandler->substNEWwithIDs);
    }

    #[Test]
    public function recordWithoutPidIsSkippedAndLogged(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => ['NEW1' => ['title' => 'Nowhere']]], []);
        $dataHandler->process_datamap();

        self::assertRecordIsSkippedAndLogged($dataHandler, 'pages');
    }

    #[Test]
    public function recordBehindAMissingRecordOfASortedTableIsSkippedAndLogged(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['pages' => ['NEW1' => ['pid' => -999, 'title' => 'Behind nothing']]], []);
        $dataHandler->process_datamap();

        self::assertRecordIsSkippedAndLogged($dataHandler, 'pages');
    }

    #[Test]
    public function recordBehindAMissingRecordOfAnUnsortedTableIsSkippedAndLogged(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['sys_file_collection' => ['NEW1' => ['pid' => -999, 'title' => 'Behind nothing']]], []);
        $dataHandler->process_datamap();

        self::assertRecordIsSkippedAndLogged($dataHandler, 'sys_file_collection');
    }

    private static function assertRecordIsSkippedAndLogged(DataHandler $dataHandler, string $table): void
    {
        self::assertArrayNotHasKey('NEW1', $dataHandler->substNEWwithIDs);
        self::assertContains(
            '[1.1]: Attempt to insert record "' . $table . ':NEW1" failed: the page to store it on could not be resolved',
            $dataHandler->errorLog
        );
    }
}
