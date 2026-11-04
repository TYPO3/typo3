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
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Scenarios for the "version" command with action "setStage".
 */
final class ChangeStageCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/workspaces/Tests/Functional/Fixtures/Extensions/test_workspace_dependencies',
    ];

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

    private function setStage(string $table, int $versionId, int $stageId): DataHandler
    {
        return $this->processCommandMap([$table => [$versionId => ['version' => ['action' => 'setStage', 'stageId' => $stageId]]]]);
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
     * sys_refindex points to the live uid of a record that is changed in the workspace,
     * the stage of its workspace version is changed along.
     */
    #[Test]
    public function changedRecordReferencedInFlexFormIsStagedAlong(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $referencingId = (int)$actionService->createNewRecord('tt_content', 1, ['header' => 'Referencing'])['tt_content'][0];
        $referencedId = (int)$actionService->createNewRecord('tt_content', 1, ['header' => 'Referenced'])['tt_content'][0];
        $actionService->publishRecords(['tt_content' => [$referencingId, $referencedId]]);

        $actionService->modifyRecord('tt_content', $referencedId, ['header' => 'Referenced changed']);
        $actionService->modifyRecord('tt_content', $referencingId, [
            'tx_testworkspacedependencies_flex' => [
                'data' => ['sDEF' => ['lDEF' => ['related' => ['vDEF' => 'tt_content_' . $referencedId]]]],
            ],
        ]);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $referencingVersionId = $this->resolveVersionId('tt_content', $referencingId, 91);
        $referencedVersionId = $this->resolveVersionId('tt_content', $referencedId, 91);

        $dataHandler = $this->setStage('tt_content', $referencingVersionId, -10);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(-10, $this->fetchStage('tt_content', $referencingVersionId));
        self::assertSame(-10, $this->fetchStage('tt_content', $referencedVersionId));
        self::assertSame(0, $this->fetchStage('tt_content', $referencedId));
    }

    /**
     * sys_file is not workspace aware and has no stage to be changed.
     */
    #[Test]
    public function fileReferencedInFlexFormIsNotStagedAlong(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file.csv');
        $versionId = (int)$actionService->createNewRecord('tt_content', 1, [
            'header' => 'Linking a file',
            'tx_testworkspacedependencies_flex' => [
                'data' => ['sDEF' => ['lDEF' => ['link' => ['vDEF' => 't3://file?uid=1']]]],
            ],
        ])['tt_content'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $dataHandler = $this->setStage('tt_content', $versionId, -10);

        self::assertSame([], $dataHandler->errorLog);
        self::assertSame(-10, $this->fetchStage('tt_content', $versionId));
    }

    #[Test]
    public function setStageCommandForMissingRecordIsRejected(): void
    {
        $this->setUpWorkspace(91);

        $dataHandler = $this->setStage('tt_content', 9999, -10);

        self::assertCount(1, $dataHandler->errorLog);
        self::assertStringEndsWith('Attempt to set stage for record failed: No Record', $dataHandler->errorLog[0]);
    }
}
