<?php

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

namespace TYPO3\CMS\Workspaces\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Act upon changes of sys_workspace and sys_workspace_stage records.
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class DataHandlerInternalWorkspaceTablesHook
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Trigger top bar update if a sys_workspace record has been changed
     * or created to renew state of the workspace toolbar item.
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        if (isset($dataHandler->datamap['sys_workspace'])) {
            BackendUtility::setUpdateSignal('updateTopbar');
        }
    }

    /**
     * Act upon deletion of sys_workspace and sys_workspace_stage rows:
     * * When a sys_workspace is deleted, *all* records of this workspace
     *   are discarded.
     * * When a sys_workspace_stage is deleted, *all* workspace records
     *   of this stage are set to "editing" stage.
     */
    public function processCmdmap_postProcess(string $command, string $table, int $uid, $_, DataHandler $dataHandler): void
    {
        if ($command === 'delete') {
            if ($table === 'sys_workspace_stage') {
                $this->setRecordsInStageToEditing($uid, $dataHandler);
            } elseif ($table === 'sys_workspace') {
                $this->discardRecordsOfWorkspace($uid);
                BackendUtility::setUpdateSignal('updateTopbar');
            }
        }
    }

    private function setRecordsInStageToEditing(int $uid, DataHandler $dataHandler): void
    {
        $affectedRecordsSum = 0;
        $affectedTables = 0;
        foreach ($this->tcaSchemaFactory->all() as $tcaTable => $schema) {
            if (!$schema->isWorkspaceAware()) {
                continue;
            }
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tcaTable);
            $affectedRecords = $queryBuilder->update($tcaTable)
                ->set('t3ver_stage', 0, true, Connection::PARAM_INT)
                ->where(
                    $queryBuilder->expr()->eq('t3ver_stage', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
                    // This is a better-safe-than-sorry restriction: Never touch live records here.
                    $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeStatement();
            if ($affectedRecords > 0) {
                $affectedRecordsSum += $affectedRecords;
                $affectedTables++;
            }
        }
        if ($affectedRecordsSum > 0) {
            $dataHandler->log(
                'sys_workspace_stage',
                $uid,
                SystemLogDatabaseAction::DELETE,
                null,
                SystemLogErrorClassification::MESSAGE,
                'Record "sys_workspace_stage:{uid}" has been deleted. "{affectedRecordsSum}" record(s) with "{affectedTables}" affected table(s) have been set to stage "editing".',
                null,
                ['uid' => $uid, 'affectedRecordsSum' => $affectedRecordsSum, 'affectedTables' => $affectedTables]
            );
        }
    }

    /**
     * @todo: This solution is quite expensive and similar to the "discard entire workspace"
     *        code reachable from the workspace BE module. It *may* be better to establish a
     *        dedicated DataHandler command for this.
     */
    private function discardRecordsOfWorkspace(int $workspaceUid): void
    {
        $command = [];
        foreach ($this->tcaSchemaFactory->all() as $tcaTable => $schema) {
            if (!$schema->isWorkspaceAware()) {
                continue;
            }
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tcaTable);
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder
                ->select('uid')
                ->from($tcaTable)
                ->where(
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($workspaceUid, Connection::PARAM_INT)
                    ),
                    // t3ver_oid >= 0 basically omits placeholder records here, those would otherwise
                    // fail to delete later in DH->discard() and would create "can't do that" log entries.
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->gt(
                            't3ver_oid',
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)
                        )
                    )
                )
                ->orderBy('uid')
                ->executeQuery();
            while (($recordId = $result->fetchOne()) !== false) {
                $command[$tcaTable][$recordId]['version']['action'] = 'flush';
            }
        }
        if (!empty($command)) {
            // Execute the command array via DataHandler to flush all records from this workspace.
            // Switch to target workspace temporarily, otherwise DH->discard() do not
            // operate on correct workspace if fetching additional records.
            $backendUser = $GLOBALS['BE_USER'];
            $savedWorkspace = $backendUser->workspace;
            $backendUser->workspace = $workspaceUid;
            // @todo: DH->discard() has no dependency to Context anymore, has it?
            $context = GeneralUtility::makeInstance(Context::class);
            $savedWorkspaceContext = $context->getAspect('workspace');
            $context->setAspect('workspace', new WorkspaceAspect($workspaceUid));
            /** @var DataHandler $dataHandler */
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $command, $backendUser);
            $dataHandler->process_cmdmap();
            $backendUser->workspace = $savedWorkspace;
            $context->setAspect('workspace', $savedWorkspaceContext);
        }
    }
}
