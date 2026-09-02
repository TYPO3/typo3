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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Hook;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The workspace hook writes history entries outside of DataHandler->updateDB(), so it has to
 * hand over the correlation id of the current run itself.
 */
final class DataHandlerHookHistoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/historyCorrelationId.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
    }

    /**
     * Creates a workspace version of tt_content:1 and returns its uid.
     */
    private function createWorkspaceVersion(): int
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [1 => ['header' => 'CE one changed']]], []);
        $dataHandler->process_datamap();

        return (int)$this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->executeQuery('SELECT uid FROM tt_content WHERE t3ver_oid = 1 AND t3ver_wsid = 1')
            ->fetchOne();
    }

    private function getCorrelationScopeOfLatestEntry(int $actionType, string $table = 'tt_content'): ?string
    {
        $correlationId = $this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery(
                'SELECT correlation_id FROM sys_history WHERE actiontype = ? AND tablename = ? ORDER BY uid DESC',
                [$actionType, $table]
            )
            ->fetchOne();
        self::assertNotFalse($correlationId, 'No history entry of action type ' . $actionType . ' was written');

        return CorrelationId::fromString($correlationId)->getScope();
    }

    #[Test]
    public function stageChangeSharesTheCorrelationIdScopeOfItsDataHandlerRun(): void
    {
        $versionId = $this->createWorkspaceVersion();

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [$versionId => ['version' => [
            'action' => 'setStage',
            'stageId' => 1,
            'comment' => 'Please review',
        ]]]]);
        $dataHandler->process_cmdmap();

        self::assertSame(
            $dataHandler->getCorrelationId()->getScope(),
            $this->getCorrelationScopeOfLatestEntry(RecordHistoryStore::ACTION_STAGECHANGE)
        );
    }

    #[Test]
    public function publishSharesTheCorrelationIdScopeOfItsDataHandlerRun(): void
    {
        $versionId = $this->createWorkspaceVersion();

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['tt_content' => [1 => ['version' => [
            'action' => 'publish',
            'swapWith' => $versionId,
            'comment' => 'Go live',
        ]]]]);
        $dataHandler->process_cmdmap();

        self::assertSame(
            $dataHandler->getCorrelationId()->getScope(),
            $this->getCorrelationScopeOfLatestEntry(RecordHistoryStore::ACTION_PUBLISH)
        );
    }

    #[Test]
    public function discardingAWorkspaceSharesTheCorrelationIdScopeOfItsDataHandlerRun(): void
    {
        $this->createWorkspaceVersion();

        // Deleting the workspace record happens in live and discards all of its records
        $GLOBALS['BE_USER']->workspace = 0;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(0));
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['sys_workspace' => [1 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        self::assertSame(
            $dataHandler->getCorrelationId()->getScope(),
            $this->getCorrelationScopeOfLatestEntry(RecordHistoryStore::ACTION_DELETE)
        );
    }
}
