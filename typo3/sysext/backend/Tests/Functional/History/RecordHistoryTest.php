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

namespace TYPO3\CMS\Backend\Tests\Functional\History;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordHistoryTest extends FunctionalTestCase
{
    public static function findEventsForCorrelationWorksAsExpectedDataProvider(): array
    {
        return [
            'da625644-8e50-47ee-8c11-bb19552a79e4' => ['da625644-8e50-47ee-8c11-bb19552a79e4', 2],
            '3479dc73-7bc8-4d3b-a8bc-0370a68cedd2' => ['3479dc73-7bc8-4d3b-a8bc-0370a68cedd2', 1],
        ];
    }

    #[DataProvider('findEventsForCorrelationWorksAsExpectedDataProvider')]
    #[Test]
    public function findEventsForCorrelationWorksAsExpected(string $correlationId, int $amountOfEntries): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_history.csv');
        $subject = new RecordHistory();
        self::assertCount($amountOfEntries, $subject->findEventsForCorrelation($correlationId));
    }

    #[Test]
    public function getCreationInformationForRecordReturnsTheFirstAddEntry(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordLifecycleEntries.csv');
        $subject = new RecordHistory();

        // uid 5 is a later "add" entry of the same record, the creation is uid 1
        self::assertSame(11, (int)$subject->getCreationInformationForRecord('pages', ['uid' => 5])['userid']);
    }

    #[Test]
    public function getUserIdFromDeleteActionForRecordReturnsTheLatestDeleteEntry(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordLifecycleEntries.csv');
        $subject = new RecordHistory();

        // The record was deleted, undeleted and deleted again, uid 4 is the current deletion
        self::assertSame(14, $subject->getUserIdFromDeleteActionForRecord('pages', 5));
    }

    #[Test]
    public function findEventsForScopeReturnsEveryEntryOfTheOperation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OperationScopes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = new RecordHistory();

        // Three records were touched by scope "s_1", uid 5 belongs to a workspace
        self::assertSame([3, 2, 1], array_keys($subject->findEventsForScope('s_1')));
    }

    #[Test]
    public function findEventsForScopeDoesNotTreatUnderscoresAsWildcards(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OperationScopes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
        $subject = new RecordHistory();

        // A scope is base64url encoded, so it can contain the LIKE wildcard "_".
        // Without escaping, "s_1" would match the entry of scope "sX1" as well.
        self::assertSame([4], array_keys($subject->findEventsForScope('sX1')));
        self::assertNotContains(4, array_keys($subject->findEventsForScope('s_1')));
    }

    #[Test]
    public function findEventsForScopeSkipsRecordsTheUserMustNotSee(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OperationScopes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_with_editor.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_groups_with_editor.csv');
        $backendUser = $this->setUpBackendUser(9);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $subject = new RecordHistory();

        // uid 3 belongs to a page the editor has no permission for
        self::assertSame([2, 1], array_keys($subject->findEventsForScope('s_1')));
    }

    #[Test]
    public function findEventsForScopeReturnsWorkspaceEntriesOnlyInThatWorkspace(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/OperationScopes.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $subject = new RecordHistory();

        self::assertSame([5, 3, 2, 1], array_keys($subject->findEventsForScope('s_1')));
    }

    #[Test]
    public function getScopeOfEventReadsTheScopeAndIgnoresUnparsableValues(): void
    {
        $subject = new RecordHistory();

        self::assertSame('s_1', $subject->getScopeOfEvent(['correlation_id' => '0400$s_1:aa']));
        self::assertNull($subject->getScopeOfEvent(['correlation_id' => '0400$withoutScope']));
        self::assertNull($subject->getScopeOfEvent(['correlation_id' => '']));
        self::assertNull($subject->getScopeOfEvent(['correlation_id' => 'not a correlation id']));
        self::assertNull($subject->getScopeOfEvent([]));
    }

    #[Test]
    public function maxStepsLimitsTheMergedChangeLogOfAPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SubElementEntries.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $subject = new RecordHistory('pages:2');
        $subject->setShowSubElements(true);
        $subject->setMaxSteps(2);
        $changeLog = $subject->getChangeLog();

        // Five entries exist across the page and its two sub pages, the two newest are wanted
        self::assertCount(2, $changeLog);
        self::assertSame([1700000500, 1700000400], array_column($changeLog, 'tstamp'));
    }
}
