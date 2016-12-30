<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework;

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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * DataHandler Actions
 */
class ActionService
{
    /**
     * @var DataHandler
     */
    protected $dataHandler;

    /**
     * @return DataHandler
     */
    public function getDataHandler()
    {
        return $this->dataHandler;
    }

    /**
     * @param string $tableName
     * @param int $pageId
     * @param array $recordData
     * @return array
     */
    public function createNewRecord($tableName, $pageId, array $recordData)
    {
        return $this->createNewRecords($pageId, [$tableName => $recordData]);
    }

    /**
     * @param int $pageId
     * @param array $tableRecordData
     * @return array
     */
    public function createNewRecords($pageId, array $tableRecordData)
    {
        $dataMap = [];
        $newTableIds = [];
        $currentUid = null;
        $previousTableName = null;
        $previousUid = null;
        foreach ($tableRecordData as $tableName => $recordData) {
            $recordData = $this->resolvePreviousUid($recordData, $currentUid);
            if (!isset($recordData['pid'])) {
                $recordData['pid'] = $pageId;
            }
            $currentUid = StringUtility::getUniqueId('NEW');
            $newTableIds[$tableName][] = $currentUid;
            $dataMap[$tableName][$currentUid] = $recordData;
            if ($previousTableName !== null && $previousUid !== null) {
                $dataMap[$previousTableName][$previousUid] = $this->resolveNextUid(
                    $dataMap[$previousTableName][$previousUid],
                    $currentUid
                );
            }
            $previousTableName = $tableName;
            $previousUid = $currentUid;
        }
        $this->createDataHandler();
        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();

        foreach ($newTableIds as $tableName => &$ids) {
            foreach ($ids as &$id) {
                if (!empty($this->dataHandler->substNEWwithIDs[$id])) {
                    $id = $this->dataHandler->substNEWwithIDs[$id];
                }
            }
        }

        return $newTableIds;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param array $recordData
     * @param NULL|array $deleteTableRecordIds
     */
    public function modifyRecord($tableName, $uid, array $recordData, array $deleteTableRecordIds = null)
    {
        $dataMap = [
            $tableName => [
                $uid => $recordData,
            ],
        ];
        $commandMap = [];
        if (!empty($deleteTableRecordIds)) {
            foreach ($deleteTableRecordIds as $tableName => $recordIds) {
                foreach ($recordIds as $recordId) {
                    $commandMap[$tableName][$recordId]['delete'] = true;
                }
            }
        }
        $this->createDataHandler();
        $this->dataHandler->start($dataMap, $commandMap);
        $this->dataHandler->process_datamap();
        if (!empty($commandMap)) {
            $this->dataHandler->process_cmdmap();
        }
    }

    /**
     * @param int $pageId
     * @param array $tableRecordData
     */
    public function modifyRecords($pageId, array $tableRecordData)
    {
        $dataMap = [];
        $currentUid = null;
        $previousTableName = null;
        $previousUid = null;
        foreach ($tableRecordData as $tableName => $recordData) {
            if (empty($recordData['uid'])) {
                continue;
            }
            $recordData = $this->resolvePreviousUid($recordData, $currentUid);
            $currentUid = $recordData['uid'];
            if ($recordData['uid'] === '__NEW') {
                $recordData['pid'] = $pageId;
                $currentUid = StringUtility::getUniqueId('NEW');
            }
            unset($recordData['uid']);
            $dataMap[$tableName][$currentUid] = $recordData;
            if ($previousTableName !== null && $previousUid !== null) {
                $dataMap[$previousTableName][$previousUid] = $this->resolveNextUid(
                    $dataMap[$previousTableName][$previousUid],
                    $currentUid
                );
            }
            $previousTableName = $tableName;
            $previousUid = $currentUid;
        }
        $this->createDataHandler();
        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    public function deleteRecord($tableName, $uid)
    {
        return $this->deleteRecords(
            [
                $tableName => [$uid],
            ]
        );
    }

    /**
     * @param array $tableRecordIds
     * @return array
     */
    public function deleteRecords(array $tableRecordIds)
    {
        $commandMap = [];
        foreach ($tableRecordIds as $tableName => $ids) {
            foreach ($ids as $uid) {
                $commandMap[$tableName][$uid] = [
                    'delete' => true,
                ];
            }
        }
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        // Deleting workspace records is actually a copy(!)
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     */
    public function clearWorkspaceRecord($tableName, $uid)
    {
        $this->clearWorkspaceRecords(
            [
                $tableName => [$uid],
            ]
        );
    }

    /**
     * @param array $tableRecordIds
     */
    public function clearWorkspaceRecords(array $tableRecordIds)
    {
        $commandMap = [];
        foreach ($tableRecordIds as $tableName => $ids) {
            foreach ($ids as $uid) {
                $commandMap[$tableName][$uid] = [
                    'version' => [
                        'action' => 'clearWSID',
                    ]
                ];
            }
        }
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $pageId
     * @param NULL|array $recordData
     * @return array
     */
    public function copyRecord($tableName, $uid, $pageId, array $recordData = null)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'copy' => $pageId,
                ],
            ],
        ];
        if ($recordData !== null) {
            $commandMap[$tableName][$uid]['copy'] = [
                'action' => 'paste',
                'target' => $pageId,
                'update' => $recordData,
            ];
        }
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $pageId
     * @param NULL|array $recordData
     * @return array
     */
    public function moveRecord($tableName, $uid, $pageId, array $recordData = null)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'move' => $pageId,
                ],
            ],
        ];
        if ($recordData !== null) {
            $commandMap[$tableName][$uid]['move'] = [
                'action' => 'paste',
                'target' => $pageId,
                'update' => $recordData,
            ];
        }
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    public function localizeRecord($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'localize' => $languageId,
                ],
            ],
        ];
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param int $languageId
     * @return array
     */
    public function copyRecordToLanguage($tableName, $uid, $languageId)
    {
        $commandMap = [
            $tableName => [
                $uid => [
                    'copyToLanguage' => $languageId,
                ],
            ],
        ];
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
        return $this->dataHandler->copyMappingArray;
    }

    /**
     * @param string $tableName
     * @param int $uid
     * @param string $fieldName
     * @param array $referenceIds
     */
    public function modifyReferences($tableName, $uid, $fieldName, array $referenceIds)
    {
        $dataMap = [
            $tableName => [
                $uid => [
                    $fieldName => implode(',', $referenceIds),
                ],
            ]
        ];
        $this->createDataHandler();
        $this->dataHandler->start($dataMap, []);
        $this->dataHandler->process_datamap();
    }

    /**
     * @param string $tableName
     * @param int $liveUid
     * @param bool $throwException
     */
    public function publishRecord($tableName, $liveUid, $throwException = true)
    {
        $this->publishRecords([$tableName => [$liveUid]], $throwException);
    }

    /**
     * @param array $tableLiveUids
     * @param bool $throwException
     * @throws \TYPO3\CMS\Core\Tests\Exception
     */
    public function publishRecords(array $tableLiveUids, $throwException = true)
    {
        $commandMap = [];
        foreach ($tableLiveUids as $tableName => $liveUids) {
            foreach ($liveUids as $liveUid) {
                $versionedUid = $this->getVersionedId($tableName, $liveUid);
                if (empty($versionedUid)) {
                    if ($throwException) {
                        throw new \TYPO3\CMS\Core\Tests\Exception('Versioned UID could not be determined');
                    } else {
                        continue;
                    }
                }

                $commandMap[$tableName][$liveUid] = [
                    'version' => [
                        'action' => 'swap',
                        'swapWith' => $versionedUid,
                        'notificationAlternativeRecipients' => [],
                    ],
                ];
            }
        }
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
    }

    /**
     * @param int $workspaceId
     */
    public function publishWorkspace($workspaceId)
    {
        $commandMap = $this->getWorkspaceService()->getCmdArrayForPublishWS($workspaceId, false);
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
    }

    /**
     * @param int $workspaceId
     */
    public function swapWorkspace($workspaceId)
    {
        $commandMap = $this->getWorkspaceService()->getCmdArrayForPublishWS($workspaceId, true);
        $this->createDataHandler();
        $this->dataHandler->start([], $commandMap);
        $this->dataHandler->process_cmdmap();
    }

    /**
     * @param array $recordData
     * @param NULL|string|int $previousUid
     * @return array
     */
    protected function resolvePreviousUid(array $recordData, $previousUid)
    {
        if ($previousUid === null) {
            return $recordData;
        }
        foreach ($recordData as $fieldName => $fieldValue) {
            if (strpos($fieldValue, '__previousUid') === false) {
                continue;
            }
            $recordData[$fieldName] = str_replace('__previousUid', $previousUid, $fieldValue);
        }
        return $recordData;
    }

    /**
     * @param array $recordData
     * @param NULL|string|int $nextUid
     * @return array
     */
    protected function resolveNextUid(array $recordData, $nextUid)
    {
        if ($nextUid === null) {
            return $recordData;
        }
        foreach ($recordData as $fieldName => $fieldValue) {
            if (strpos($fieldValue, '__nextUid') === false) {
                continue;
            }
            $recordData[$fieldName] = str_replace('__nextUid', $nextUid, $fieldValue);
        }
        return $recordData;
    }

    /**
     * @param string $tableName
     * @param int $liveUid
     * @param bool $useDeleteClause
     * @return NULL|int
     */
    protected function getVersionedId($tableName, $liveUid, $useDeleteClause = false)
    {
        $versionedId = null;
        $liveUid = (int)$liveUid;
        $workspaceId = (int)$this->getBackendUser()->workspace;
        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            $tableName,
            'pid=-1 AND t3ver_oid=' . $liveUid . ' AND t3ver_wsid=' . $workspaceId .
            ($useDeleteClause ? \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName) : '')
        );
        if (!empty($row['uid'])) {
            $versionedId = (int)$row['uid'];
        }
        return $versionedId;
    }

    /**
     * @return DataHandler
     */
    protected function createDataHandler()
    {
        $this->dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(DataHandler::class);
        $backendUser = $this->getBackendUser();
        if (isset($backendUser->uc['copyLevels'])) {
            $this->dataHandler->copyTree = $backendUser->uc['copyLevels'];
        }
        return $this->dataHandler;
    }

    /**
     * @return \TYPO3\CMS\Workspaces\Service\WorkspaceService
     */
    protected function getWorkspaceService()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Workspaces\Service\WorkspaceService::class
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
