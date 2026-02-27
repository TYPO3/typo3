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

namespace TYPO3\CMS\Recycler\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;

/**
 * Provides data-fetching logic for the recycler module.
 * Used by both RecyclerModuleController (initial server-side render)
 * and RecyclerAjaxController (subsequent AJAX requests).
 *
 * @internal This class is a specific Backend service implementation and is not considered part of the Public TYPO3 API.
 */
final readonly class RecyclerService
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private IconFactory $iconFactory,
        private ConnectionPool $connectionPool,
        private RecordHistory $recordHistory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Returns the list of tables that contain deleted records, with counts.
     *
     * Each entry is an array of [tableName, deletedCount, tableTitle].
     * The first entry is always the "all record types" summary.
     *
     * @param int $startUid UID of the selected page
     * @param int $depth How many levels to recurse
     * @return list<array{0: string, 1: int, 2: string}>
     */
    public function getAvailableTables(int $startUid, int $depth): array
    {
        $deletedRecordsTotal = 0;
        $lang = $this->getLanguageService();
        $tables = [];

        foreach ($this->getRelevantSchemata() as $tableName => $schema) {
            $deletedField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();

            $deletedCount = $queryBuilder->count('uid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->neq(
                        $deletedField,
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();

            if (!$deletedCount) {
                continue;
            }
            $deletedDataObject = GeneralUtility::makeInstance(DeletedRecords::class);
            $deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
            if (isset($deletedData[$tableName]) && $deletedRecordsInTable = count($deletedData[$tableName])) {
                $deletedRecordsTotal += $deletedRecordsInTable;
                $tables[] = [
                    $tableName,
                    $deletedRecordsInTable,
                    $schema->getTitle($lang->sL(...)) ?: $tableName,
                ];
            }
        }

        array_unshift($tables, [
            '',
            $deletedRecordsTotal,
            $lang->sL('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:label_allrecordtypes'),
        ]);
        return $tables;
    }

    /**
     * Loads deleted records, flattens them, paginates and transforms for display.
     *
     * @param int $startUid UID of the selected page
     * @param string $table Table name filter (empty = all tables)
     * @param int $depth How many levels to recurse
     * @param string $filterTxt Search filter text
     * @param int $currentPage Current page number (1-based)
     * @param int $itemsPerPage Number of items per page
     * @return array{groupedRecords: array, totalItems: int}
     */
    public function getDeletedRecords(int $startUid, string $table, int $depth, string $filterTxt, int $currentPage, int $itemsPerPage): array
    {
        $model = GeneralUtility::makeInstance(DeletedRecords::class);
        $model->loadData($startUid, $table, $depth, $filterTxt);

        $flatRecords = [];
        foreach ($model->getDeletedRows() as $tableName => $rows) {
            foreach ($rows as $row) {
                $flatRecords[] = ['_table' => $tableName, ...$row];
            }
        }

        $paginator = new ArrayPaginator($flatRecords, $currentPage, $itemsPerPage);

        $paginatedGrouped = [];
        foreach ($paginator->getPaginatedItems() as $item) {
            $tableName = $item['_table'];
            unset($item['_table']);
            $paginatedGrouped[$tableName][] = $item;
        }

        return [
            'groupedRecords' => $this->transform($paginatedGrouped),
            'totalItems' => count($flatRecords),
        ];
    }

    /**
     * Transforms the rows for the deleted records by grouping them
     * by their corresponding table and processing the raw record data.
     *
     * @param array<string, array> $deletedRowsArray
     */
    private function transform(array $deletedRowsArray): array
    {
        $groupedRecords = [];
        $lang = $this->getLanguageService();

        foreach ($deletedRowsArray as $table => $rows) {
            $schema = $this->tcaSchemaFactory->get($table);
            $groupedRecords[$table]['information'] = [
                'table' => $table,
                'title' => $schema->getTitle($lang->sL(...)) ?: BackendUtility::getNoRecordTitle(),
            ];
            foreach ($rows as $row) {
                $pageTitle = $this->getPageTitle((int)$row['pid']);
                $ownerInformation = $this->recordHistory->getCreationInformationForRecord($table, $row);
                $ownerUid = (int)(is_array($ownerInformation) && $ownerInformation['usertype'] === 'BE' ? $ownerInformation['userid'] : 0);
                $deleteUserUid = $this->recordHistory->getUserIdFromDeleteActionForRecord($table, (int)$row['uid']);

                $creationDate = '';
                if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)) {
                    $creationDate = BackendUtility::datetime($row[$schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName()]);
                }
                $lastUpdateDate = '';
                if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
                    $lastUpdateDate = BackendUtility::datetime($row[$schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName()]);
                }
                $groupedRecords[$table]['records'][] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'icon' => $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL)->render(),
                    'pageTitle' => $pageTitle,
                    'crdate' => $creationDate,
                    'tstamp' => $lastUpdateDate,
                    'backendUser' => $this->getBackendUserInformation($ownerUid),
                    'title' => BackendUtility::getRecordTitle($table, $row),
                    'path' => $this->getRecordPath((int)$row['pid']),
                    'deletedBackendUser' => $this->getBackendUserInformation($deleteUserUid),
                    'isParentDeleted' => $table === 'pages' && $this->isParentPageDeleted((int)$row['pid']),
                ];
            }
        }

        return $groupedRecords;
    }

    private function getPageTitle(int $pageId): string
    {
        $cacheId = 'recycler-pagetitle-' . $pageId;
        $pageTitle = $this->runtimeCache->get($cacheId);
        if ($pageTitle === false) {
            if ($pageId === 0) {
                $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            } else {
                $recordInfo = BackendUtility::getRecord('pages', $pageId, '*', '', false);
                $pageTitle = $recordInfo['title'] ?? '';
            }
            $this->runtimeCache->set($cacheId, $pageTitle);
        }
        return $pageTitle;
    }

    private function getBackendUserInformation(int $userId): array
    {
        if ($userId === 0) {
            return [];
        }
        $cacheId = 'recycler-user-' . $userId;
        $userData = $this->runtimeCache->get($cacheId);
        if ($userData === false) {
            $userData = BackendUtility::getRecord('be_users', $userId, 'uid, username, realName', '', false);
            $this->runtimeCache->set($cacheId, $userData);
        }
        return $userData ?? [];
    }

    private function getRecordPath(int $uid): string
    {
        $output = '/';
        if ($uid === 0) {
            return $output;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $loopCheck = 100;
        while ($loopCheck > 0) {
            $loopCheck--;

            $row = $queryBuilder
                ->select('uid', 'pid', 'title', 'deleted', 't3ver_oid', 't3ver_wsid', 't3ver_state')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();
            if (is_array($row)) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $uid = (int)$row['pid'];
                    $output = '/' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], 1000)) . $output;
                    if ($row['deleted']) {
                        $output = '<span class="text-danger">' . $output . '</span>';
                    }
                } else {
                    break;
                }
            } else {
                break;
            }
        }
        return $output;
    }

    private function isParentPageDeleted(int $pid): bool
    {
        if ($pid === 0) {
            return false;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        $deleted = $queryBuilder
            ->select('deleted')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        return (bool)$deleted;
    }

    /**
     * @return TcaSchema[]
     */
    private function getRelevantSchemata(): array
    {
        $schemata = [];
        $tables = explode(',', $this->getBackendUser()->groupData['tables_modify']);
        foreach ($this->tcaSchemaFactory->all() as $name => $schema) {
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            if ($this->getBackendUser()->isAdmin()) {
                $schemata[$name] = $schema;
                continue;
            }
            if (in_array($name, $tables, true)) {
                $schemata[$name] = $schema;
            }
        }
        return $schemata;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
