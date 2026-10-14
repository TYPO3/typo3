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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\DataHandler\CommandMap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Scenarios for the "change_stage" command, which is keyed by the versioned record
 * uid, and its parity with the legacy "version" command with action "setStage".
 */
final class ChangeStageCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    protected array $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv'];

    private function setUpWorkspace(int $workspaceId): ActionService
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $backendUser->workspace = $workspaceId;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
        return new ActionService();
    }

    private function processCommandMap(array $commandMap): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler;
    }

    private function resolveVersionId(string $table, int $liveId, int $workspaceId): int
    {
        $row = $this->getConnectionPool()->getConnectionForTable($table)
            ->select(['uid'], $table, ['t3ver_oid' => $liveId, 't3ver_wsid' => $workspaceId])
            ->fetchAssociative();
        self::assertIsArray($row, sprintf('no workspace version found for %s:%d', $table, $liveId));
        return (int)$row['uid'];
    }

    private function fetchStage(string $table, int $uid): int
    {
        return (int)$this->getConnectionPool()->getConnectionForTable($table)
            ->select(['t3ver_stage'], $table, ['uid' => $uid])
            ->fetchOne();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchStageChangeHistory(string $table, int $uid): array
    {
        $rows = $this->getConnectionPool()->getConnectionForTable('sys_history')
            ->select(['history_data'], 'sys_history', ['actiontype' => RecordHistoryStore::ACTION_STAGECHANGE, 'tablename' => $table, 'recuid' => $uid])
            ->fetchAllAssociative();
        return array_map(static fn(array $row): array => json_decode($row['history_data'], true), $rows);
    }

    #[Test]
    public function stageIsChangedByVersionedUid(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->processCommandMap(['tt_content' => [$versionId => ['change_stage' => ['stage' => -10]]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $versionId));
    }

    #[Test]
    public function commentAndRecipientsAreWrittenToHistory(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->processCommandMap(['tt_content' => [$versionId => ['change_stage' => [
            'stage' => -10,
            'comment' => 'Ready for review',
            'notificationAlternativeRecipients' => [['email' => 'editor@example.com']],
        ]]]]);

        $history = $this->fetchStageChangeHistory('tt_content', $versionId);
        self::assertCount(1, $history);
        self::assertSame(0, $history[0]['current']);
        self::assertSame(-10, $history[0]['next']);
        self::assertSame('Ready for review', $history[0]['comment']);
        self::assertSame([['email' => 'editor@example.com']], $history[0]['recipients']);
    }

    #[Test]
    public function legacySetStageCommandStillChangesStage(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->processCommandMap(['tt_content' => [$versionId => ['version' => [
            'action' => 'setStage',
            'stageId' => -10,
            'comment' => 'Legacy',
        ]]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $versionId));
        self::assertSame('Legacy', $this->fetchStageChangeHistory('tt_content', $versionId)[0]['comment']);
    }

    /**
     * Both commands are keyed by the versioned uid, so they resolve to the same
     * dependency element and the stage may only be changed once.
     */
    #[Test]
    public function legacyAndNewCommandForTheSameRecordChangeTheStageOnce(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->processCommandMap(['tt_content' => [$versionId => [
            'change_stage' => ['stage' => -10],
            'version' => ['action' => 'setStage', 'stageId' => -10],
        ]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $versionId));
        self::assertCount(1, $this->fetchStageChangeHistory('tt_content', $versionId));
    }

    /**
     * An invalid "change_stage" still wins over a legacy command for the same record:
     * it is rejected and logged, and the legacy command is not executed as fallback.
     */
    #[Test]
    public function invalidChangeStageCommandTakesPrecedenceOverLegacyCommand(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $dataHandler = $this->processCommandMap(['tt_content' => [$versionId => [
            'change_stage' => ['comment' => 'No stage given'],
            'version' => ['action' => 'setStage', 'stageId' => -10],
        ]]]);

        self::assertSame(0, $this->fetchStage('tt_content', $versionId));
        self::assertCount(0, $this->fetchStageChangeHistory('tt_content', $versionId));
        self::assertCount(1, $dataHandler->errorLog);
        self::assertStringEndsWith('Attempt to set stage for record failed: No target stage given', $dataHandler->errorLog[0]);
    }

    #[Test]
    public function dependentInlineChildIsStagedAlongWithItsParent(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Hotel'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $hotelVersionId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->processCommandMap(['tt_content' => [$contentVersionId => ['change_stage' => ['stage' => -10, 'comment' => 'With child']]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $contentVersionId));
        self::assertSame(-10, $this->fetchStage('tx_testirrecsv_hotel', $hotelVersionId));
        self::assertSame('With child', $this->fetchStageChangeHistory('tx_testirrecsv_hotel', $hotelVersionId)[0]['comment']);
    }

    #[Test]
    public function legacyDependentInlineChildIsStagedAlongWithItsParent(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Hotel'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $hotelVersionId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->processCommandMap(['tt_content' => [$contentVersionId => ['version' => ['action' => 'setStage', 'stageId' => -10]]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $contentVersionId));
        self::assertSame(-10, $this->fetchStage('tx_testirrecsv_hotel', $hotelVersionId));
    }

    /**
     * Without a target stage the command is not dispatched by CommandMap, but the
     * hook must not fall back to stage 0 ("editing") either.
     */
    #[Test]
    public function commandWithoutStageDoesNotResetStage(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);
        $this->processCommandMap(['tt_content' => [$versionId => ['change_stage' => ['stage' => -10]]]]);

        $dataHandler = $this->processCommandMap(['tt_content' => [$versionId => ['change_stage' => ['comment' => 'No stage given']]]]);

        self::assertSame(-10, $this->fetchStage('tt_content', $versionId));
        self::assertCount(1, $this->fetchStageChangeHistory('tt_content', $versionId));
        self::assertCount(1, $dataHandler->errorLog);
        self::assertStringEndsWith('Attempt to set stage for record failed: No target stage given', $dataHandler->errorLog[0]);
    }

    /**
     * processCmdmap_beforeStart rewrites the command map before any other hook sees
     * it. A legacy command must reach third-party processCmdmap* hooks unchanged.
     */
    #[Test]
    public function legacySetStageCommandIsNotRewrittenForOtherHooks(): void
    {
        $this->setUpWorkspace(91);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $commandMap = $this->get(CommandMap::class)->process(
            ['tt_content' => [$versionId => ['version' => ['action' => 'setStage', 'stageId' => -10]]]],
            91
        );

        self::assertSame(['version'], array_keys($commandMap['tt_content'][$versionId]));
    }

    /**
     * Dependent elements pulled in by the resolver take the shape of the command
     * the parent was requested with.
     */
    #[Test]
    public function legacySetStageCommandWithDependentChildIsNotRewrittenForOtherHooks(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Hotel'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $hotelVersionId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $commandMap = $this->get(CommandMap::class)->process(
            ['tt_content' => [$contentVersionId => ['version' => ['action' => 'setStage', 'stageId' => -10, 'comment' => 'Legacy']]]],
            91
        );

        $expected = ['version' => ['action' => 'setStage', 'stageId' => -10, 'comment' => 'Legacy']];
        self::assertSame($expected, $commandMap['tt_content'][$contentVersionId]);
        self::assertSame($expected, $commandMap['tx_testirrecsv_hotel'][$hotelVersionId]);
    }

    #[Test]
    public function changeStageCommandIsPassedOnToDependentChild(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Hotel'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $hotelVersionId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $commandMap = $this->get(CommandMap::class)->process(
            ['tt_content' => [$contentVersionId => ['change_stage' => ['stage' => -10, 'comment' => 'New']]]],
            91
        );

        $expected = ['change_stage' => ['stage' => -10, 'comment' => 'New']];
        self::assertSame($expected, $commandMap['tt_content'][$contentVersionId]);
        self::assertSame($expected, $commandMap['tx_testirrecsv_hotel'][$hotelVersionId]);
    }
}
