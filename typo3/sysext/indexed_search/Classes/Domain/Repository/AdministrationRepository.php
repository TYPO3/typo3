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

namespace TYPO3\CMS\IndexedSearch\Domain\Repository;

use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\FileContentParser;

/**
 * Administration repository
 * @internal This class is a specific repository implementation and is not considered part of the Public TYPO3 API.
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
            ->executeQuery();
        $numberOfRows = $queryBuilder
            ->count('uniqid')
            ->executeQuery()
            ->fetchOne();
        $allRows = [];
        while ($row = $result->fetchAssociative()) {
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
            ->executeQuery()
            ->fetchOne();
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
            ->executeQuery()
            ->fetchOne();
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
                'static_page_arguments',
                'data_filename',
                'data_page_id',
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
            ->executeQuery();

        while ($row = $res->fetchAssociative()) {
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
                    ->executeQuery();
                while ($row2 = $res2->fetchAssociative()) {
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
                ->executeQuery()
                ->fetchOne();
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
            'txt' => 4,
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
            ->executeQuery();

        while ($row = $res->fetchAssociative()) {
            $itemType = $row['item_type'];
            $counts[] = [
                'count' => $row['count'],
                'name' => $revTypes[$itemType] ?? '',
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
            ->executeQuery()
            ->fetchAllAssociative();

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
            ->executeQuery()
            ->fetchOne();
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
                'static_page_arguments',
                'data_filename',
                'data_page_id',
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
            ->executeQuery();

        while ($row = $res->fetchAssociative()) {
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
                    ->executeQuery();
                while ($row2 = $res2->fetchAssociative()) {
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
     * @return array|null
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
            ->orderBy('c', 'desc')
            ->setMaxResults((int)$max);

        if (!empty($additionalWhere)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($additionalWhere));
        }

        $result = $queryBuilder->executeQuery();
        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->resetQueryPart('orderBy');
        $count = (int)$countQueryBuilder
            ->count('uid')
            ->executeQuery()
            ->fetchOne();
        $result->free();

        // exist several statistics for this page?
        if ($count === 0) {
            // Limit access to pages of the current site
            $queryBuilder->where(
                $queryBuilder->expr()->in(
                    'pageid',
                    $queryBuilder->createNamedParameter(
                        $this->extGetTreeList((int)$pageUid),
                        Connection::PARAM_INT_ARRAY
                    )
                ),
                QueryHelper::stripLogicalOperatorPrefix($additionalWhere)
            );
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Add additional information to the result row
     *
     * @param array $row
     */
    protected function addAdditionalInformation(array &$row)
    {
        $grListRec = $this->getGrlistRecord($row['phash']);
        $row['static_page_arguments'] = $row['static_page_arguments'] ? json_decode($row['static_page_arguments'], true) : null;

        $row['numberOfWords'] = $this->getNumberOfWords($row['phash']);
        $row['numberOfSections'] = $this->getNumberOfSections($row['phash']);
        $row['numberOfFulltext'] = $this->getNumberOfFulltextRecords($row['phash']);
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
    public function getTree($pageId, $depth, $mode)
    {
        $allLines = [];
        $pageRecord = BackendUtility::getRecord('pages', (int)$pageId);
        if (!$pageRecord) {
            return $allLines;
        }
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(Permission::PAGE_SHOW);
        $tree->init('AND ' . $perms_clause);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $HTML = '<span title="' . htmlspecialchars($pageRecord['title']) . '">' . $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
        $tree->tree[] = [
            'row' => $pageRecord,
            'HTML' => $HTML,
        ];

        if ($depth > 0) {
            $tree->getTree((int)$pageId, $depth);
        }

        foreach ($tree->tree as $singleLine) {
            $rows = $this->getPhashRowsForPageId($singleLine['row']['uid']);
            $lines = [];
            // Collecting phash values (to remove local indexing for)
            // Traverse the result set of phash rows selected:
            foreach ($rows as $row) {
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
                    // @todo Executing and not use the assigned result looks weired, at least with the
                    //       circumstance that the same QueryBuilder is reused as count query and executed
                    //       directly afterwards - must be rechecked and either solved or proper commented
                    //       why this mystery is needed here as this is not obvious and against general
                    //       recommendation to not reuse the QueryBuilder.
                    ->executeQuery();

                $row['wordCount'] = $queryBuilder
                    ->count('index_rel.wid')
                    ->executeQuery()
                    ->fetchOne();
                $wordCountResult->free();

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
                        ->executeQuery()
                        ->fetchAssociative();

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
                        ->executeQuery()
                        ->fetchAllAssociative();

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

    protected function getPhashRowsForPageId(int $pageId): array
    {
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
            'IP.static_page_arguments',
            'IP.data_filename',
            'IP.data_page_id',
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
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->groupBy(
                'IP.phash',
                'IP.phash_grouping',
                'IP.static_page_arguments',
                'IP.data_filename',
                'IP.data_page_id',
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
            ->executeQuery();

        $usedResults = [];
        // Collecting phash values (to remove local indexing for)
        // Traverse the result set of phash rows selected
        while ($row = $result->fetchAssociative()) {
            $usedResults[] = $row;
        }
        return $usedResults;
    }

    /**
     * Generates a list of Page-uid's from $id.
     * The only pages excluded from the list are deleted pages.
     *
     * @param int $id page id
     * @return array Returns an array with all page IDs
     */
    protected function extGetTreeList(int $id): array
    {
        $pageIds = $this->getPageTreeIds($id, 100, 0);
        $pageIds[] = $id;
        return $pageIds;
    }

    /**
     * Generates a list of Page-uid's from $id. List does not include $id itself
     * The only pages excluded from the list are deleted pages.
     *
     * @param int $id Start page id
     * @param int $depth Depth to traverse down the page tree.
     * @param int $begin Determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
     * @return array Returns the list of pages
     */
    protected function getPageTreeIds(int $id, int $depth, int $begin): array
    {
        if (!$id || $depth <= 0) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder
            ->select('uid', 'title')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->executeQuery();

        $pageIds = [];
        while ($row = $result->fetchAssociative()) {
            if ($begin <= 0) {
                $pageIds[] = (int)$row['uid'];
            }
            if ($depth > 1) {
                $pageIds = array_merge($pageIds, $this->getPageTreeIds((int)$row['uid'], $depth - 1, $begin - 1));
            }
        }
        return $pageIds;
    }

    /**
     * Remove indexed phash row
     *
     * @param string $phashList
     * @param int $pageId
     * @param int $depth
     */
    public function removeIndexedPhashRow($phashList, $pageId, $depth = 4)
    {
        if ($phashList === 'ALL') {
            if ($depth === 0) {
                $phashRows = $this->getPhashRowsForPageId((int)$pageId);
                $phashRows = array_column($phashRows, 'phash');
            } else {
                $this->getTree($pageId, $depth, '');
                $phashRows = $this->allPhashListed;
                $this->allPhashListed = [];
            }
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
                    ->executeQuery();
                while ($row = $res->fetchAssociative()) {
                    $idList[] = (int)$row['page_id'];
                }

                if (!empty($idList)) {
                    $pageCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('pages');
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
                    'index_debug',
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
                ->executeStatement();
        }
    }

    /**
     * Save keywords
     *
     * @param array $words keywords
     * @param int $pageId page id
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
