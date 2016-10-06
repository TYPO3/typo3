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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_grlist');
        $result = $queryBuilder
            ->select('*')
            ->from('index_grlist')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($phash, \PDO::PARAM_INT)
                )
            )
            ->execute();
        $numberOfRows = $result->rowCount();
        $allRows = [];
        while ($row = $result->fetch()) {
            $row['pcount'] = $numberOfRows;
            $allRows[] = $row;
        }
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_fulltext');
        return $queryBuilder
            ->count('phash')
            ->from('index_fulltext')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($phash, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get number of words
     *
     * @param int $phash
     * @return int|bool
     */
    public function getNumberOfWords($phash)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_rel');
        return $queryBuilder
            ->count('*')
            ->from('index_rel')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($phash, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get statistic of external documents
     *
     * @return array
     */
    public function getExternalDocumentsStatistic()
    {
        $result = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        $res = $queryBuilder
            ->select('index_phash.*')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'pcount'))
            ->from('index_phash')
            ->where($queryBuilder->expr()->neq('item_type', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->groupBy(
                'phash_grouping',
                'phash',
                'cHashParams',
                'data_filename',
                'data_page_id',
                'data_page_reg1',
                'data_page_type',
                'data_page_mp',
                'gr_list',
                'item_type',
                'item_title',
                'item_description',
                'item_mtime',
                'tstamp',
                'item_size',
                'contentHash',
                'crdate',
                'parsetime',
                'sys_language_uid',
                'item_crdate',
                'externalUrl',
                'recordUid',
                'freeIndexUid',
                'freeIndexSetId'
            )
            ->orderBy('item_type')
            ->execute();

        while ($row = $res->fetch()) {
            $this->addAdditionalInformation($row);

            $result[] = $row;

            if ($row['pcount'] > 1) {
                $res2 = $queryBuilder
                    ->select('*')
                    ->from('index_phash')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'phash_grouping',
                            $queryBuilder->createNamedParameter($row['phash_grouping'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->neq(
                            'phash',
                            $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
                while ($row2 = $res2->fetch()) {
                    $this->addAdditionalInformation($row2);
                    $result[] = $row2;
                }
            }
        }
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
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
            $recordList[$tableName] = $queryBuilder
                ->count('*')
                ->from($tableName)
                ->execute()
                ->fetchColumn(0);
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        $res = $queryBuilder
            ->select('item_type')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'count'))
            ->from('index_phash')
            ->groupBy('item_type')
            ->orderBy('item_type')
            ->execute();

        while ($row = $res->fetch()) {
            $itemType = $row['item_type'];
            $counts[] = [
                'count' => $row['count'],
                'name' => $revTypes[$itemType],
                'type' => $itemType,
                'uniqueCount' => $this->countUniqueTypes($itemType),
            ];
        }
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        $items = $queryBuilder
            ->count('*')
            ->from('index_phash')
            ->where(
                $queryBuilder->expr()->eq(
                    'item_type',
                    $queryBuilder->createNamedParameter($itemType, \PDO::PARAM_STR)
                )
            )
            ->groupBy('phash_grouping')
            ->execute()
            ->fetchAll();

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_section');
        return (int)$queryBuilder
            ->count('phash')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'phash',
                    $queryBuilder->createNamedParameter($pageHash, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * Get page statistic
     *
     * @return array
     */
    public function getPageStatistic()
    {
        $result = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
        $res = $queryBuilder
            ->select('index_phash.*')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'pcount'))
            ->from('index_phash')
            ->where($queryBuilder->expr()->neq('data_page_id', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->groupBy(
                'phash_grouping',
                'phash',
                'cHashParams',
                'data_filename',
                'data_page_id',
                'data_page_reg1',
                'data_page_type',
                'data_page_mp',
                'gr_list',
                'item_type',
                'item_title',
                'item_description',
                'item_mtime',
                'tstamp',
                'item_size',
                'contentHash',
                'crdate',
                'parsetime',
                'sys_language_uid',
                'item_crdate',
                'externalUrl',
                'recordUid',
                'freeIndexUid',
                'freeIndexSetId'
            )
            ->orderBy('data_page_id')
            ->execute();

        while ($row = $res->fetch()) {
            $this->addAdditionalInformation($row);
            $result[] = $row;

            if ($row['pcount'] > 1) {
                $res2 = $queryBuilder
                    ->select('*')
                    ->from('index_phash')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'phash_grouping',
                            $queryBuilder->createNamedParameter($row['phash_grouping'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->neq(
                            'phash',
                            $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
                while ($row2 = $res2->fetch()) {
                    $this->addAdditionalInformation($row2);
                    $result[] = $row2;
                }
            }
        }
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_stat_word');
        $queryBuilder
            ->select('word')
            ->from('index_stat_word')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'c'))
            ->where(
                $queryBuilder->expr()->eq(
                    'pageid',
                    $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                )
            )
            ->groupBy('word')
            ->setMaxResults((int)$max);

        if (!empty($additionalWhere)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($additionalWhere));
        }

        $result = $queryBuilder->execute();
        $count = (int)$result->rowCount();
        $result->closeCursor();

        // exist several statistics for this page?
        if ($count === 0) {
            // Limit access to pages of the current site
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    'pageid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $this->extGetTreeList((int)$pageUid, 100, 0, '1=1'), true),
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                QueryHelper::stripLogicalOperatorPrefix($additionalWhere)
            );
        }

        return $queryBuilder->execute()->fetchAll();
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

        foreach ($tree->tree as $singleLine) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_phash');
            $result = $queryBuilder->select(
                'ISEC.phash_t3',
                'ISEC.rl0',
                'ISEC.rl1',
                'ISEC.rl2',
                'ISEC.page_id',
                'ISEC.uniqid',
                'IP.phash',
                'IP.phash_grouping',
                'IP.cHashParams',
                'IP.data_filename',
                'IP.data_page_id',
                'IP.data_page_reg1',
                'IP.data_page_type',
                'IP.data_page_mp',
                'IP.gr_list',
                'IP.item_type',
                'IP.item_title',
                'IP.item_description',
                'IP.item_mtime',
                'IP.tstamp',
                'IP.item_size',
                'IP.contentHash',
                'IP.crdate',
                'IP.parsetime',
                'IP.sys_language_uid',
                'IP.item_crdate',
                'IP.externalUrl',
                'IP.recordUid',
                'IP.freeIndexUid',
                'IP.freeIndexSetId'
            )
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'count_val'))
            ->from('index_phash', 'IP')
            ->from('index_section', 'ISEC')
            ->where(
                $queryBuilder->expr()->eq('IP.phash', $queryBuilder->quoteIdentifier('ISEC.phash')),
                $queryBuilder->expr()->eq(
                    'ISEC.page_id',
                    $queryBuilder->createNamedParameter($singleLine['row']['uid'], \PDO::PARAM_INT)
                )
            )
            ->groupBy(
                'IP.phash',
                'IP.phash_grouping',
                'IP.cHashParams',
                'IP.data_filename',
                'IP.data_page_id',
                'IP.data_page_reg1',
                'IP.data_page_type',
                'IP.data_page_mp',
                'IP.gr_list',
                'IP.item_type',
                'IP.item_title',
                'IP.item_description',
                'IP.item_mtime',
                'IP.tstamp',
                'IP.item_size',
                'IP.contentHash',
                'IP.crdate',
                'IP.parsetime',
                'IP.sys_language_uid',
                'IP.item_crdate',
                'ISEC.phash',
                'ISEC.phash_t3',
                'ISEC.rl0',
                'ISEC.rl1',
                'ISEC.rl2',
                'ISEC.page_id',
                'ISEC.uniqid',
                'IP.externalUrl',
                'IP.recordUid',
                'IP.freeIndexUid',
                'IP.freeIndexSetId'
            )
            ->orderBy('IP.item_type')
            ->addOrderBy('IP.tstamp')
            ->setMaxResults(11)
            ->execute();

            $lines = [];
            // Collecting phash values (to remove local indexing for)
            // Traverse the result set of phash rows selected:
            while ($row = $result->fetch()) {
                $row['icon'] = $this->makeItemTypeIcon($row['item_type']);
                $this->allPhashListed[] = $row['phash'];

                // Adds a display row:
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('index_rel');
                $wordCountResult = $queryBuilder->count('index_words.baseword')
                    ->from('index_rel')
                    ->from('index_words')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'index_rel.phash',
                            $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq('index_words.wid', $queryBuilder->quoteIdentifier('index_rel.wid'))
                    )
                    ->groupBy('index_words.baseword')
                    ->execute();

                $row['wordCount'] = $wordCountResult->rowCount();
                $wordCountResult->closeCursor();

                if ($mode === 'content') {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('index_fulltext');
                    $row['fulltextData'] = $queryBuilder->select('*')
                        ->from('index_fulltext')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'phash',
                                $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();

                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('index_rel');
                    $wordRecords = $queryBuilder->select('index_words.baseword')
                        ->from('index_rel')
                        ->from('index_words')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'index_rel.phash',
                                $queryBuilder->createNamedParameter($row['phash'], \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'index_words.wid',
                                $queryBuilder->quoteIdentifier('index_rel.wid')
                            )
                        )
                        ->groupBy('index_words.baseword')
                        ->orderBy('index_words.baseword')
                        ->execute()
                        ->fetchAll();

                    if (is_array($wordRecords)) {
                        $row['allWords'] = array_column($wordRecords, 'baseword');
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
        $list = GeneralUtility::makeInstance(FrontendBackendUserAuthentication::class)
            ->extGetTreeList($id, $depth, $begin, $perms_clause);

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

        foreach ($phashRows as $phash) {
            $phash = (int)$phash;
            if ($phash > 0) {
                $idList = [];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('index_section');
                $res = $queryBuilder
                    ->select('page_id')
                    ->from('index_section')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'phash',
                            $queryBuilder->createNamedParameter($phash, \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
                while ($row = $res->fetch()) {
                    $idList[] = (int)$row['page_id'];
                }

                if (!empty($idList)) {
                    /** @var FrontendInterface $pageCache */
                    $pageCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
                    foreach ($idList as $pageId) {
                        $pageCache->flushByTag('pageId_' . $pageId);
                    }
                }

                // Removing old registrations for all tables.
                $tableArr = [
                    'index_phash',
                    'index_rel',
                    'index_section',
                    'index_grlist',
                    'index_fulltext',
                    'index_debug'
                ];
                foreach ($tableArr as $table) {
                    GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getConnectionForTable($table)
                        ->delete($table, ['phash' => (int)$phash]);
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
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('index_words');
            $queryBuilder
                ->update('index_words')
                ->set('is_stopword', (int)$state)
                ->where(
                    $queryBuilder->expr()->eq(
                        'wid',
                        $queryBuilder->createNamedParameter($wid, \PDO::PARAM_INT)
                    )
                )
                ->execute();
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
