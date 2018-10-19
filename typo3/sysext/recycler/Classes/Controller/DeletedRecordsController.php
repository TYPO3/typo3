<?php

namespace TYPO3\CMS\Recycler\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Recycler\Utility\RecyclerUtility;

/**
 * Deleted Records View
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class DeletedRecordsController
{
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $runtimeCache;

    /**
     * @var DataHandler
     */
    protected $tce;

    public function __construct()
    {
        $this->runtimeCache = $this->getMemoryCache();
        $this->tce = GeneralUtility::makeInstance(DataHandler::class);
    }

    /**
     * Transforms the rows for the deleted records
     *
     * @param array $deletedRowsArray Array with table as key and array with all deleted rows
     * @return array JSON array
     */
    public function transform($deletedRowsArray)
    {
        $jsonArray = [
            'rows' => []
        ];

        if (is_array($deletedRowsArray)) {
            $lang = $this->getLanguageService();
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

            foreach ($deletedRowsArray as $table => $rows) {
                foreach ($rows as $row) {
                    $pageTitle = $this->getPageTitle((int)$row['pid']);
                    $backendUserName = $this->getBackendUser((int)$row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']]);
                    $userIdWhoDeleted = $this->getUserWhoDeleted($table, (int)$row['uid']);

                    $jsonArray['rows'][] = [
                        'uid' => $row['uid'],
                        'pid' => $row['pid'],
                        'icon' => $iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render(),
                        'pageTitle' => $pageTitle,
                        'table' => $table,
                        'crdate' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['crdate']]),
                        'tstamp' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['tstamp']]),
                        'owner' => htmlspecialchars($backendUserName),
                        'owner_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']],
                        'tableTitle' => $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']),
                        'title' => htmlspecialchars(BackendUtility::getRecordTitle($table, $row)),
                        'path' => RecyclerUtility::getRecordPath($row['pid']),
                        'delete_user_uid' => $userIdWhoDeleted,
                        'delete_user' => $this->getBackendUser($userIdWhoDeleted),
                        'isParentDeleted' => $table === 'pages' ? RecyclerUtility::isParentPageDeleted($row['pid']) : false
                    ];
                }
            }
        }
        return $jsonArray;
    }

    /**
     * Gets the page title of the given page id
     *
     * @param int $pageId
     * @return string
     */
    protected function getPageTitle($pageId)
    {
        $cacheId = 'recycler-pagetitle-' . $pageId;
        if ($this->runtimeCache->has($cacheId)) {
            $pageTitle = $this->runtimeCache->get($cacheId);
        } else {
            if ($pageId === 0) {
                $pageTitle = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
            } else {
                $recordInfo = $this->tce->recordInfo('pages', $pageId, 'title');
                $pageTitle = $recordInfo['title'];
            }
            $this->runtimeCache->set($cacheId, $pageTitle);
        }
        return $pageTitle;
    }

    /**
     * Gets the username of a given backend user
     *
     * @param int $userId uid of user
     * @return string
     */
    protected function getBackendUser(int $userId): string
    {
        if ($userId === 0) {
            return '';
        }
        $cacheId = 'recycler-user-' . $userId;
        if ($this->runtimeCache->has($cacheId)) {
            $username = $this->runtimeCache->get($cacheId);
        } else {
            $backendUser = BackendUtility::getRecord('be_users', $userId, 'username', '', false);
            if ($backendUser === null) {
                $username = sprintf(
                    '[%s]',
                    LocalizationUtility::translate('LLL:EXT:recycler/Resources/Private/Language/locallang.xlf:record.deleted')
                );
            } else {
                $username = $backendUser['username'];
            }
            $this->runtimeCache->set($cacheId, $username);
        }
        return $username;
    }

    /**
     * Get the user uid of the user who deleted the record
     *
     * @param string $table table name
     * @param int $uid uid of record
     * @return int
     */
    protected function getUserWhoDeleted(string $table, int $uid): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_history');
        $queryBuilder->select('userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'usertype',
                    $queryBuilder->createNamedParameter('BE', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'recuid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'actiontype',
                    $queryBuilder->createNamedParameter(RecordHistoryStore::ACTION_DELETE, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        return (int)$queryBuilder->execute()->fetchColumn(0);
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Create and returns an instance of the CacheManager
     *
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Gets an instance of the memory cache.
     *
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getMemoryCache()
    {
        return $this->getCacheManager()->getCache('cache_runtime');
    }
}
