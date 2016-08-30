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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recycler\Utility\RecyclerUtility;

/**
 * Deleted Records View
 */
class DeletedRecordsController
{
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected $runtimeCache = null;

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
     * @param int $totalDeleted Number of deleted records in total
     * @return string JSON array
     */
    public function transform($deletedRowsArray, $totalDeleted)
    {
        $total = 0;
        $jsonArray = [
            'rows' => []
        ];

        if (is_array($deletedRowsArray)) {
            $lang = $this->getLanguageService();
            $backendUser = $this->getBackendUser();
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

            foreach ($deletedRowsArray as $table => $rows) {
                $total += count($deletedRowsArray[$table]);
                foreach ($rows as $row) {
                    $pageTitle = $this->getPageTitle((int)$row['pid']);
                    $backendUser = BackendUtility::getRecord('be_users', $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']], 'username', '', false);
                    $jsonArray['rows'][] = [
                        'uid' => $row['uid'],
                        'pid' => $row['pid'],
                        'icon' => $iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render(),
                        'pageTitle' => RecyclerUtility::getUtf8String($pageTitle),
                        'table' => $table,
                        'crdate' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['crdate']]),
                        'tstamp' => BackendUtility::datetime($row[$GLOBALS['TCA'][$table]['ctrl']['tstamp']]),
                        'owner' => htmlspecialchars($backendUser['username']),
                        'owner_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['cruser_id']],
                        'tableTitle' => RecyclerUtility::getUtf8String($lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
                        'title' => htmlspecialchars(RecyclerUtility::getUtf8String(BackendUtility::getRecordTitle($table, $row))),
                        'path' => RecyclerUtility::getRecordPath($row['pid'])
                    ];
                }
            }
        }
        $jsonArray['total'] = $totalDeleted;
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
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Create and returns an instance of the CacheManager
     *
     * @return \TYPO3\CMS\Core\Cache\CacheManager
     */
    protected function getCacheManager()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
    }

    /**
     * Gets an instance of the memory cache.
     *
     * @return \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected function getMemoryCache()
    {
        return $this->getCacheManager()->getCache('cache_runtime');
    }
}
