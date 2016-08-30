<?php
namespace TYPO3\CMS\IndexedSearch\Domain\Repository;

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
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dbal\Database\DatabaseConnection;
use TYPO3\CMS\IndexedSearch\FileContentParser;

/**
 * Administration repository
 */
class AdministrationRepository
{
    /**
     * List of fileContentParsers
     *
     * @var FileContentParser[]
     */
    public $external_parsers = [];

    /**
     * @var array
     */
    protected $allPhashListed = [];

    /**
     * @var array
     */
    protected $iconFileNameCache = [];

    /**
     * Get group list information
     *
     * @param int $phash
     * @return array
     */
    public function getGrlistRecord($phash)
    {
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery('index_grlist.*', 'index_grlist', 'phash=' . (int)$phash);
        $allRows = [];
        $numberOfRows = $db->sql_num_rows($res);
        while ($row = $db->sql_fetch_assoc($res)) {
            $row['pcount'] = $numberOfRows;
            $allRows[] = $row;
        }
        $db->sql_free_result($res);
        return $allRows;
    }

    /**
     * Get number of fulltext records
     *
     * @param int $phash
     * @return int|bool
     */
    public function getNumberOfFulltextRecords($phash)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows('phash', 'index_fulltext', 'phash=' . (int)$phash);
    }

    /**
     * Get number of words
     *
     * @param int $phash
     * @return int|bool
     */
    public function getNumberOfWords($phash)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows('*', 'index_rel', 'phash=' . (int)$phash);
    }

    /**
     * Get statistic of external documents
     *
     * @return array
     */
    public function getExternalDocumentsStatistic()
    {
        $result = [];

        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            'count(*) AS pcount,index_phash.*',
            'index_phash',
            'item_type<>\'0\'',
            'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId',
            'item_type'
        );
        while ($row = $db->sql_fetch_assoc($res)) {
            $this->addAdditionalInformation($row);

            $result[] = $row;

            if ($row['pcount'] > 1) {
                $res2 = $db->exec_SELECTquery(
                    'index_phash.*',
                    'index_phash',
                    'phash_grouping=' . (int)$row['phash_grouping'] . ' AND phash<>' . (int)$row['phash']
                );
                while ($row2 = $db->sql_fetch_assoc($res2)) {
                    $this->addAdditionalInformation($row2);
                    $result[] = $row2;
                }
                $db->sql_free_result($res2);
            }
        }
        $db->sql_free_result($res);

        return $result;
    }

    /**
     * Get count of the tables used for indexed_search
     *
     * @return array
     */
    public function getRecordsNumbers()
    {
        $tables = [
            'index_phash',
            'index_words',
            'index_rel',
            'index_grlist',
            'index_section',
            'index_fulltext',
        ];
        $recordList = [];
        foreach ($tables as $tableName) {
            $recordList[$tableName] = $this->getDatabaseConnection()->exec_SELECTcountRows('*', $tableName);
        }
        return $recordList;
    }

    /**
     * Get hash types
     *
     * @return array
     */
    public function getPageHashTypes()
    {
        $counts = [];
        $types = [
            'html' => 1,
            'htm' => 1,
            'pdf' => 2,
            'doc' => 3,
            'txt' => 4
        ];
        $revTypes = array_flip($types);
        $revTypes[0] = 'TYPO3 page';
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery('count(*),item_type', 'index_phash', '', 'item_type', 'item_type');
        while ($row = $db->sql_fetch_row($res)) {
            $itemType = $row[1];
            $counts[] = [
                'count' => $row[0],
                'name' => $revTypes[$itemType],
                'type' => $itemType,
                'uniqueCount' => $this->countUniqueTypes($itemType),
            ];
        }
        $db->sql_free_result($res);

        return $counts;
    }

    /**
     * Count unique types
     *
     * @param string $itemType
     * @return int
     */
    protected function countUniqueTypes($itemType)
    {
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            'count(*)',
            'index_phash',
            'item_type=' . $db->fullQuoteStr($itemType, 'index_phash'),
            'phash_grouping'
        );
        $items = [];
        while ($row = $db->sql_fetch_row($res)) {
            $items[] = $row;
        }
        $db->sql_free_result($res);

        return count($items);
    }

    /**
     * Get number of section records
     *
     * @param int $pageHash
     * @return int
     */
    public function getNumberOfSections($pageHash)
    {
        return $this->getDatabaseConnection()->exec_SELECTcountRows('phash', 'index_section', 'phash=' . (int)$pageHash);
    }

    /**
     * Get page statistic
     *
     * @return array
     */
    public function getPageStatistic()
    {
        $result = [];
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            'count(*) AS pcount,index_phash.*',
            'index_phash',
            'data_page_id<>0',
            'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId',
            'data_page_id'
        );
        while ($row = $db->sql_fetch_assoc($res)) {
            $this->addAdditionalInformation($row);
            $result[] = $row;

            if ($row['pcount'] > 1) {
                $res2 = $db->exec_SELECTquery(
                    'index_phash.*',
                    'index_phash',
                    'phash_grouping=' . (int)$row['phash_grouping'] . ' AND phash<>' . (int)$row['phash']
                );
                while ($row2 = $db->sql_fetch_assoc($res2)) {
                    $this->addAdditionalInformation($row2);
                    $result[] = $row2;
                }
                $db->sql_free_result($res2);
            }
        }
        $db->sql_free_result($res);

        return $result;
    }

    /**
     * Get general statistic
     *
     * @param string $additionalWhere
     * @param int $pageUid
     * @param int $max
     * @return array|NULL
     */
    public function getGeneralSearchStatistic($additionalWhere, $pageUid, $max = 50)
    {
        $queryParts = [
            'SELECT' => 'word, COUNT(*) AS c',
            'FROM' => 'index_stat_word',
            'WHERE' => sprintf('pageid= %d ' . $additionalWhere, $pageUid),
            'GROUPBY' => 'word',
            'ORDERBY' => '',
            'LIMIT' => (int)$max
        ];
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            $queryParts['SELECT'],
            $queryParts['FROM'],
            $queryParts['WHERE'],
            $queryParts['GROUPBY'],
            $queryParts['ORDERBY'],
            $queryParts['LIMIT']
        );

        $count = 0;
        if ($res) {
            $count = $db->sql_num_rows($res);
        }

        $db->sql_free_result($res);

        // exist several statistics for this page?
        if ($count == 0) {
            // Limit access to pages of the current site
            $secureAddWhere = ' AND pageid IN (' . $this->extGetTreeList((int)$pageUid, 100, 0, '1=1') . ') ';
            $queryParts['WHERE'] = '1=1 ' . $additionalWhere . $secureAddWhere;
        }

        return $db->exec_SELECTgetRows(
            $queryParts['SELECT'],
            $queryParts['FROM'],
            $queryParts['WHERE'],
            $queryParts['GROUPBY'],
            $queryParts['ORDERBY'],
            $queryParts['LIMIT']
        );
    }

    /**
     * Add additional information to the result row
     *
     * @param array $row
     * @return void
     */
    protected function addAdditionalInformation(array &$row)
    {
        $grListRec = $this->getGrlistRecord($row['phash']);
        $unserializedCHashParams = unserialize($row['cHashParams']);

        $row['numberOfWords'] = $this->getNumberOfWords($row['phash']);
        $row['numberOfSections'] = $this->getNumberOfSections($row['phash']);
        $row['numberOfFulltext'] = $this->getNumberOfFulltextRecords($row['phash']);
        $row['cHashParams'] = !empty($unserializedCHashParams) ? $unserializedCHashParams : '';
        $row['grList'] = $grListRec;
    }

    /**
     * Get the page tree by using \TYPO3\CMS\Backend\Tree\View\PageTreeView
     *
     * @param int $pageId
     * @param int $depth
     * @param string $mode
     * @return array
     */
    public function getTree($pageId, $depth = 4, $mode)
    {
        $allLines = [];
        $pageRecord = BackendUtility::getRecord('pages', (int)$pageId);
        if (!$pageRecord) {
            return $allLines;
        }
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(1);
        $tree->init('AND ' . $perms_clause);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $HTML = '<span title="' . htmlspecialchars($pageRecord['title']) . '">' . $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
        $tree->tree[] = [
            'row' => $pageRecord,
            'HTML' => $HTML
        ];

        if ($depth > 0) {
            $tree->getTree((int)$pageId, $depth, '');
        }
        $db = $this->getDatabaseConnection();
        foreach ($tree->tree as $singleLine) {
            $res = $db->exec_SELECTquery(
                'ISEC.phash_t3, ISEC.rl0, ISEC.rl1, ISEC.rl2, ISEC.page_id, ISEC.uniqid, ' .
                'IP.phash, IP.phash_grouping, IP.cHashParams, IP.data_filename, IP.data_page_id, ' .
                'IP.data_page_reg1, IP.data_page_type, IP.data_page_mp, IP.gr_list, IP.item_type, ' .
                'IP.item_title, IP.item_description, IP.item_mtime, IP.tstamp, IP.item_size, ' .
                'IP.contentHash, IP.crdate, IP.parsetime, IP.sys_language_uid, IP.item_crdate, ' .
                'IP.externalUrl, IP.recordUid, IP.freeIndexUid, IP.freeIndexSetId, count(*) AS count_val',
                'index_phash IP, index_section ISEC',
                'IP.phash = ISEC.phash AND ISEC.page_id = ' . (int)$singleLine['row']['uid'],
                'IP.phash,IP.phash_grouping,IP.cHashParams,IP.data_filename,IP.data_page_id,IP.data_page_reg1,IP.data_page_type,IP.data_page_mp,IP.gr_list,IP.item_type,IP.item_title,IP.item_description,IP.item_mtime,IP.tstamp,IP.item_size,IP.contentHash,IP.crdate,IP.parsetime,IP.sys_language_uid,IP.item_crdate,ISEC.phash,ISEC.phash_t3,ISEC.rl0,ISEC.rl1,ISEC.rl2,ISEC.page_id,ISEC.uniqid,IP.externalUrl,IP.recordUid,IP.freeIndexUid,IP.freeIndexSetId',
                'IP.item_type, IP.tstamp',
                10 + 1
            );
            $lines = [];
            // Collecting phash values (to remove local indexing for)
            // Traverse the result set of phash rows selected:
            while ($row = $db->sql_fetch_assoc($res)) {
                $this->allPhashListed[] = $row['phash'];
                // Adds a display row:
                $row['icon'] = $this->makeItemTypeIcon($row['item_type']);
                $row['wordCount'] = count($db->exec_SELECTgetRows(
                    'index_words.baseword, index_rel.*',
                    'index_rel, index_words',
                    'index_rel.phash = ' . (int)$row['phash'] . ' AND index_words.wid = index_rel.wid',
                    '',
                    '',
                    '',
                    'baseword'
                ));

                if ($mode === 'content') {
                    $row['fulltextData'] = $db->exec_SELECTgetSingleRow(
                        '*',
                        'index_fulltext',
                        'phash = ' . $row['phash']);
                    $wordRecords = $db->exec_SELECTgetRows(
                        'index_words.baseword, index_rel.*',
                        'index_rel, index_words',
                        'index_rel.phash = ' . (int)$row['phash'] . ' AND index_words.wid = index_rel.wid',
                        '', '', '', 'baseword');
                    if (is_array($wordRecords)) {
                        $indexed_words = array_keys($wordRecords);
                        sort($indexed_words);
                        $row['allWords'] = $indexed_words;
                    }
                }

                $lines[] = $row;
            }

            $singleLine['lines'] = $lines;
            $allLines[] = $singleLine;
        }

        return $allLines;
    }

    /**
     * Generates a list of Page-uid's from $id.
     * The only pages excluded from the list are deleted pages.
     *
     * @param int $id page id
     * @param int $depth to traverse down the page tree.
     * @param int $begin is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
     * @param string $perms_clause
     * @return string Returns the list with a comma in the end + id itself
     */
    protected function extGetTreeList($id, $depth, $begin = 0, $perms_clause)
    {
        $list = GeneralUtility::makeInstance(FrontendBackendUserAuthentication::class)->extGetTreeList($id, $depth, $begin, $perms_clause);

        if (empty($list)) {
            $list = $id;
        } else {
            $list = rtrim($list, ',') . ',' . $id;
        }

        return $list;
    }

    /**
     * Remove indexed phash row
     *
     * @param string $phashList
     * @param int $pageId
     * @param int $depth
     * @return void
     */
    public function removeIndexedPhashRow($phashList, $pageId, $depth = 4)
    {
        if ($phashList === 'ALL') {
            $this->getTree($pageId, $depth, '');
            $phashRows = $this->allPhashListed;
            $this->allPhashListed = [];
        } else {
            $phashRows = GeneralUtility::trimExplode(',', $phashList, true);
        }

        $db = $this->getDatabaseConnection();
        foreach ($phashRows as $phash) {
            $phash = (int)$phash;
            if ($phash > 0) {
                $idList = [];
                $res = $db->exec_SELECTquery('page_id', 'index_section', 'phash=' . $phash);
                while ($row = $db->sql_fetch_assoc($res)) {
                    $idList[] = (int)$row['page_id'];
                }
                $db->sql_free_result($res);

                if (!empty($idList)) {
                    /** @var FrontendInterface $pageCache */
                    $pageCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
                    foreach ($idList as $pageId) {
                        $pageCache->flushByTag('pageId_' . $pageId);
                    }
                }

                // Removing old registrations for all tables.
                $tableArr = ['index_phash', 'index_rel', 'index_section', 'index_grlist', 'index_fulltext', 'index_debug'];
                foreach ($tableArr as $table) {
                    $db->exec_DELETEquery($table, 'phash=' . $phash);
                }
            }
        }
    }

    /**
     * Save stop words
     *
     * @param array $words stop words
     * @return void
     */
    public function saveStopWords(array $words)
    {
        foreach ($words as $wid => $state) {
            $fieldArray = [
                'is_stopword' => (int)$state
            ];
            $this->getDatabaseConnection()->exec_UPDATEquery('index_words', 'wid=' . (int)$wid, $fieldArray);
        }
    }

    /**
     * Save keywords
     *
     * @param array $words keywords
     * @param int $pageId page id
     * @return void
     */
    public function saveKeywords(array $words, $pageId)
    {
        // Get pages current keywords
        $pageRec = BackendUtility::getRecord('pages', $pageId);
        if (!is_array($pageRec)) {
            return;
        }
        $keywords = array_flip(GeneralUtility::trimExplode(',', $pageRec['keywords'], true));
        // Merge keywords:
        foreach ($words as $key => $v) {
            if ($v) {
                $keywords[$key] = 1;
            } else {
                unset($keywords[$key]);
            }
        }
        // Compile new list:
        $data = [];
        $data['pages'][$pageId]['keywords'] = implode(', ', array_keys($keywords));
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->stripslashes_values = 0;
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
    }

    /**
     * Collect the type icons
     *
     * @param string $itemType
     * @return string
     */
    protected function makeItemTypeIcon($itemType)
    {
        if (!isset($this->iconFileNameCache[$itemType])) {
            $icon = '';
            if ($itemType === '0') {
                $icon = 'EXT:indexed_search/Resources/Public/Icons/FileTypes/pages.gif';
            } elseif ($this->external_parsers[$itemType]) {
                $icon = $this->external_parsers[$itemType]->getIcon($itemType);
            }
            $this->iconFileNameCache[$itemType] = $icon;
        }
        return $this->iconFileNameCache[$itemType];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
