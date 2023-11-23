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

namespace TYPO3\CMS\Core\Tests\Functional\DataScenarios\Uuid\WorkspacesPublish;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Tests\Functional\DataScenarios\Uuid\AbstractActionWorkspacesTestCase;

/**
 * Some tests are special, because we cannot compare the UUIDs directly,
 * as they are unique for each new test. That's why we have to compare them manually
 */
final class ActionTest extends AbstractActionWorkspacesTestCase
{
    #[Test]
    public function verifyCleanReferenceIndex(): void
    {
        // Fix refindex, then compare with import csv again to verify nothing changed.
        // This is to make sure the import csv is 'clean' - important for the other tests.
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $this->assertCSVDataSet(self::SCENARIO_DataSet);
    }

    #[Test]
    public function createEmptyRecord(): void
    {
        $GLOBALS['TCA'][self::TABLE_Element]['columns'][self::FIELD_Uuid]['required'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        parent::createEmptyRecord();
        $this->actionService->publishRecords(
            [
                self::TABLE_Element => [$this->recordIds['newElementIdFirst']],
            ]
        );
        $records = $this->getAllRecords(self::TABLE_Element);
        self::assertNotEmpty($records[1][self::FIELD_Uuid]);
        self::assertNotSame(self::VALUE_OriginalUuid, $records[1][self::FIELD_Uuid]);
        self::assertTrue(Uuid::isValid($records[1][self::FIELD_Uuid]));
    }

    #[Test]
    public function createNewPrefilledRecord(): void
    {
        parent::createNewPrefilledRecord();
        $this->actionService->publishRecords(
            [
                self::TABLE_Element => [$this->recordIds['newElementIdFirst']],
            ]
        );
        $records = $this->getAllRecords(self::TABLE_Element);
        self::assertNotSame(self::VALUE_OriginalUuid, $records[1][self::FIELD_Uuid]);
        self::assertTrue(Uuid::isValid($records[1][self::FIELD_Uuid]));
    }

    #[Test]
    public function copyRecord(): void
    {
        parent::copyRecord();
        $this->actionService->publishRecords(
            [
                self::TABLE_Element => [$this->recordIds['newElementIdFirst']],
            ]
        );
        $records = $this->getAllRecords(self::TABLE_Element);
        self::assertEquals(self::VALUE_OriginalUuid, $records[0][self::FIELD_Uuid]);
        self::assertNotSame(self::VALUE_OriginalUuid, $records[1][self::FIELD_Uuid]);
    }

    #[Test]
    public function localizeRecord(): void
    {
        parent::localizeRecord();
        $this->actionService->publishRecords(
            [
                self::TABLE_Element => [$this->recordIds['newElementIdFirst']],
            ]
        );
        $records = $this->getAllRecords(self::TABLE_Element);
        self::assertEquals(self::VALUE_LanguageId, $records[1]['sys_language_uid']);
        self::assertNotSame(self::VALUE_OriginalUuid, $records[1][self::FIELD_Uuid]);
    }

    #[Test]
    public function moveRecord(): void
    {
        parent::moveRecord();
        $this->actionService->publishRecords(
            [
                self::TABLE_Element => [self::VALUE_ElementIdFirst],
            ]
        );
        $this->assertCSVDataSet(__DIR__ . '/DataSet/moveRecord.csv');
    }
}
