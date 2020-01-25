<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 * @internal this is a TYPO3-internal hook implementation and not part of TYPO3's Core API.
 */
class CrawlerHook
{
    /**
     * Number of seconds to use as interval between queued indexing operations of URLs / files (types 2 & 3)
     *
     * @var int
     */
    public $secondsPerExternalUrl = 3;

    /**
     * Counts up for each added URL (type 3)
     *
     * @var int
     */
    public $instanceCounter = 0;

    /**
     * @var string
     */
    public $callBack = self::class;

    /**
     * @var object
     */
    private $pObj;

    /**
     * Initialization of crawler hook.
     * This function is asked for each instance of the crawler and we must check if something is timed to happen and if so put entry(s) in the crawlers log to start processing.
     * In reality we select indexing configurations and evaluate if any of them needs to run.
     *
     * @param object $pObj Parent object (tx_crawler lib)
     */
    public function crawler_init(&$pObj)
    {
        $this->pObj = $pObj;

        $message = null;
        // Select all indexing configuration which are waiting to be activated:
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('index_config');
        $queryBuilder = $connection->createQueryBuilder();

        $result = $queryBuilder->select('*')
            ->from('index_config')
            ->where(
                $queryBuilder->expr()->lt(
                    'timer_next_indexing',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq('set_id', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute();

        // For each configuration, check if it should be executed and if so, start:
        while ($cfgRec = $result->fetch()) {
            // Generate a unique set-ID:
            $setId = GeneralUtility::md5int(microtime());
            // Get next time:
            $nextTime = $this->generateNextIndexingTime($cfgRec);
            // Start process by updating index-config record:
            $connection->update(
                'index_config',
                [
                    'set_id' => $setId,
                    'timer_next_indexing' => $nextTime,
                    'session_data' => ''
                ],
                [
                    'uid' => (int)$cfgRec['uid']
                ]
            );
            // Based on configuration type:
            switch ($cfgRec['type']) {
                case 1:
                    // RECORDS:
                    // Parameters:
                    $params = [
                        'indexConfigUid' => $cfgRec['uid'],
                        'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                        'url' => 'Records (start)'
                    ];
                    //
                    $pObj->addQueueEntry_callBack($setId, $params, $this->callBack, $cfgRec['pid']);
                    break;
                case 2:
                    // FILES:
                    // Parameters:
                    $params = [
                        'indexConfigUid' => $cfgRec['uid'],
                        // General
                        'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                        // General
                        'url' => $cfgRec['filepath'],
                        // Partly general... (for URL and file types)
                        'depth' => 0
                    ];
                    $pObj->addQueueEntry_callBack($setId, $params, $this->callBack, $cfgRec['pid']);
                    break;
                case 3:
                    // External URL:
                    // Parameters:
                    $params = [
                        'indexConfigUid' => $cfgRec['uid'],
                        // General
                        'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                        // General
                        'url' => $cfgRec['externalUrl'],
                        // Partly general... (for URL and file types)
                        'depth' => 0
                    ];
                    $pObj->addQueueEntry_callBack($setId, $params, $this->callBack, $cfgRec['pid']);
                    break;
                case 4:
                    // Page tree
                    // Parameters:
                    $params = [
                        'indexConfigUid' => $cfgRec['uid'],
                        // General
                        'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                        // General
                        'url' => (int)$cfgRec['alternative_source_pid'],
                        // Partly general... (for URL and file types and page tree (root))
                        'depth' => 0
                    ];
                    $pObj->addQueueEntry_callBack($setId, $params, $this->callBack, $cfgRec['pid']);
                    break;
                case 5:
                    // Meta configuration, nothing to do:
                    // NOOP
                    break;
                default:
                    if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]) {
                        $hookObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]);
                        // Parameters:
                        $params = [
                            'indexConfigUid' => $cfgRec['uid'],
                            // General
                            'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . '/CUSTOM]'],
                            // General
                            'url' => $hookObj->initMessage($message)
                        ];
                        $pObj->addQueueEntry_callBack($setId, $params, $this->callBack, $cfgRec['pid']);
                    }
            }
        }
        // Finally, look up all old index configurations which are finished and needs to be reset and done.
        $this->cleanUpOldRunningConfigurations();
    }

    /**
     * Call back function for execution of a log element
     *
     * @param array $params Params from log element. Must contain $params['indexConfigUid']
     * @param object $pObj Parent object (tx_crawler lib)
     * @return array Result array
     */
    public function crawler_execute($params, &$pObj)
    {
        // Indexer configuration ID must exist:
        if ($params['indexConfigUid']) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('index_config');
            $queryBuilder->getRestrictions()->removeAll();
            // Load the indexing configuration record:
            $cfgRec = $queryBuilder
                ->select('*')
                ->from('index_config')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($params['indexConfigUid'], \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
            if (is_array($cfgRec)) {
                // Unpack session data:
                $session_data = unserialize($cfgRec['session_data']);
                // Select which type:
                switch ($cfgRec['type']) {
                    case 1:
                        // Records:
                        $this->crawler_execute_type1($cfgRec, $session_data, $params, $pObj);
                        break;
                    case 2:
                        // Files
                        $this->crawler_execute_type2($cfgRec, $session_data, $params, $pObj);
                        break;
                    case 3:
                        // External URL:
                        $this->crawler_execute_type3($cfgRec, $session_data, $params, $pObj);
                        break;
                    case 4:
                        // Page tree:
                        $this->crawler_execute_type4($cfgRec, $session_data, $params, $pObj);
                        break;
                    case 5:
                        // Meta
                        // NOOP (should never enter here!)
                        break;
                    default:
                        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]) {
                            $hookObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]);
                            $this->pObj = $pObj;
                            // For addQueueEntryForHook()
                            $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
                            $hookObj->indexOperation($cfgRec, $session_data, $params, $ref);
                        }
                }
                // Save process data which might be modified:
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('index_config')
                    ->update(
                        'index_config',
                        ['session_data' => serialize($session_data)],
                        ['uid' => (int)$cfgRec['uid']]
                    );
            }
        }
        return ['log' => $params];
    }

    /**
     * Indexing records from a table
     *
     * @param array $cfgRec Indexing Configuration Record
     * @param array $session_data Session data for the indexing session spread over multiple instances of the script. Passed by reference so changes hereto will be saved for the next call!
     * @param array $params Parameters from the log queue.
     * @param object $pObj Parent object (from "crawler" extension!)
     */
    public function crawler_execute_type1($cfgRec, &$session_data, $params, &$pObj)
    {
        if ($cfgRec['table2index'] && isset($GLOBALS['TCA'][$cfgRec['table2index']])) {
            // Init session data array if not already:
            if (!is_array($session_data)) {
                $session_data = [
                    'uid' => 0
                ];
            }
            // Init:
            $pid = (int)$cfgRec['alternative_source_pid'] ?: $cfgRec['pid'];
            $numberOfRecords = $cfgRec['recordsbatch']
                ? MathUtility::forceIntegerInRange($cfgRec['recordsbatch'], 1)
                : 100;

            // Get root line:
            $rootLine = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
            // Select
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($cfgRec['table2index']);

            $baseQueryBuilder = $queryBuilder->select('*')
                ->from($cfgRec['table2index'])
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        'uid',
                        $queryBuilder->createNamedParameter($session_data['uid'], \PDO::PARAM_INT)
                    )
                );
            $result = $baseQueryBuilder
                ->setMaxResults($numberOfRecords)
                ->orderBy('uid')
                ->execute();

            // Traverse:
            while ($row = $result->fetch()) {
                // Index single record:
                $this->indexSingleRecord($row, $cfgRec, $rootLine);
                // Update the UID we last processed:
                $session_data['uid'] = $row['uid'];
            }

            $rowCount = $baseQueryBuilder->count('uid')->execute()->fetchColumn(0);
            // Finally, set entry for next indexing of batch of records:
            if ($rowCount) {
                $nparams = [
                    'indexConfigUid' => $cfgRec['uid'],
                    'url' => 'Records from UID#' . ($session_data['uid'] + 1) . '-?',
                    'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']']
                ];
                $pObj->addQueueEntry_callBack($cfgRec['set_id'], $nparams, $this->callBack, $cfgRec['pid']);
            }
        }
    }

    /**
     * Indexing files from fileadmin
     *
     * @param array $cfgRec Indexing Configuration Record
     * @param array $session_data Session data for the indexing session spread over multiple instances of the script. Passed by reference so changes hereto will be saved for the next call!
     * @param array $params Parameters from the log queue.
     * @param object $pObj Parent object (from "crawler" extension!)
     */
    public function crawler_execute_type2($cfgRec, &$session_data, $params, &$pObj)
    {
        // Prepare path, making it absolute and checking:
        $readpath = $params['url'];
        if (!GeneralUtility::isAbsPath($readpath)) {
            $readpath = GeneralUtility::getFileAbsFileName($readpath);
        }
        if (GeneralUtility::isAllowedAbsPath($readpath)) {
            if (@is_file($readpath)) {
                // If file, index it!
                // Get root line (need to provide this when indexing external files)
                $rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
                // (Re)-Indexing file on page.
                $indexerObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
                $indexerObj->backend_initIndexer($cfgRec['pid'], 0, 0, '', $rl);
                $indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
                $indexerObj->hash['phash'] = -1;
                // EXPERIMENT - but to avoid phash_t3 being written to file sections (otherwise they are removed when page is reindexed!!!)
                // Index document:
                $indexerObj->indexRegularDocument(\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($readpath), true);
            } elseif (@is_dir($readpath)) {
                // If dir, read content and create new pending items for log:
                // Select files and directories in path:
                $extList = implode(',', GeneralUtility::trimExplode(',', $cfgRec['extensions'], true));
                $fileArr = [];
                $files = GeneralUtility::getAllFilesAndFoldersInPath($fileArr, $readpath, $extList, 0, 0);
                $directoryList = GeneralUtility::get_dirs($readpath);
                if (is_array($directoryList) && $params['depth'] < $cfgRec['depth']) {
                    foreach ($directoryList as $subdir) {
                        if ((string)$subdir != '') {
                            $files[] = $readpath . $subdir . '/';
                        }
                    }
                }
                $files = GeneralUtility::removePrefixPathFromList($files, Environment::getPublicPath() . '/');
                // traverse the items and create log entries:
                foreach ($files as $path) {
                    $this->instanceCounter++;
                    if ($path !== $params['url']) {
                        // Parameters:
                        $nparams = [
                            'indexConfigUid' => $cfgRec['uid'],
                            'url' => $path,
                            'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                            'depth' => $params['depth'] + 1
                        ];
                        $pObj->addQueueEntry_callBack($cfgRec['set_id'], $nparams, $this->callBack, $cfgRec['pid'], $GLOBALS['EXEC_TIME'] + $this->instanceCounter * $this->secondsPerExternalUrl);
                    }
                }
            }
        }
    }

    /**
     * Indexing External URLs
     *
     * @param array $cfgRec Indexing Configuration Record
     * @param array $session_data Session data for the indexing session spread over multiple instances of the script. Passed by reference so changes hereto will be saved for the next call!
     * @param array $params Parameters from the log queue.
     * @param object $pObj Parent object (from "crawler" extension!)
     */
    public function crawler_execute_type3($cfgRec, &$session_data, $params, &$pObj)
    {
        // Init session data array if not already:
        if (!is_array($session_data)) {
            $session_data = [
                'urlLog' => [$params['url']]
            ];
        }
        // Index the URL:
        $rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
        $subUrls = $this->indexExtUrl($params['url'], $cfgRec['pid'], $rl, $cfgRec['uid'], $cfgRec['set_id']);
        // Add more elements to log now:
        if ($params['depth'] < $cfgRec['depth']) {
            foreach ($subUrls as $url) {
                if ($url = $this->checkUrl($url, $session_data['urlLog'], $cfgRec['externalUrl'])) {
                    if (!$this->checkDeniedSuburls($url, $cfgRec['url_deny'])) {
                        $this->instanceCounter++;
                        $session_data['urlLog'][] = $url;
                        // Parameters:
                        $nparams = [
                            'indexConfigUid' => $cfgRec['uid'],
                            'url' => $url,
                            'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                            'depth' => $params['depth'] + 1
                        ];
                        $pObj->addQueueEntry_callBack($cfgRec['set_id'], $nparams, $this->callBack, $cfgRec['pid'], $GLOBALS['EXEC_TIME'] + $this->instanceCounter * $this->secondsPerExternalUrl);
                    }
                }
            }
        }
    }

    /**
     * Page tree indexing type
     *
     * @param array $cfgRec Indexing Configuration Record
     * @param array $session_data Session data for the indexing session spread over multiple instances of the script. Passed by reference so changes hereto will be saved for the next call!
     * @param array $params Parameters from the log queue.
     * @param object $pObj Parent object (from "crawler" extension!)
     */
    public function crawler_execute_type4($cfgRec, &$session_data, $params, &$pObj)
    {
        // Base page uid:
        $pageUid = (int)$params['url'];
        // Get array of URLs from page:
        $pageRow = BackendUtility::getRecord('pages', $pageUid);
        $res = $pObj->getUrlsForPageRow($pageRow);
        $duplicateTrack = [];
        // Registry for duplicates
        $downloadUrls = [];
        // Dummy.
        // Submit URLs:
        if (!empty($res)) {
            foreach ($res as $paramSetKey => $vv) {
                $pObj->urlListFromUrlArray($vv, $pageRow, $GLOBALS['EXEC_TIME'], 30, 1, 0, $duplicateTrack, $downloadUrls, ['tx_indexedsearch_reindex']);
            }
        }
        // Add subpages to log now:
        if ($params['depth'] < $cfgRec['depth']) {
            // Subpages selected
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $result = $queryBuilder->select('uid', 'title')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)
                    )
                )
                ->execute();
            // Traverse subpages and add to queue:
            while ($row = $result->fetch()) {
                $this->instanceCounter++;
                $url = 'pages:' . $row['uid'] . ': ' . $row['title'];
                $session_data['urlLog'][] = $url;
                // Parameters:
                $nparams = [
                    'indexConfigUid' => $cfgRec['uid'],
                    'url' => $row['uid'],
                    'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                    'depth' => $params['depth'] + 1
                ];
                $pObj->addQueueEntry_callBack(
                    $cfgRec['set_id'],
                    $nparams,
                    $this->callBack,
                    $cfgRec['pid'],
                    $GLOBALS['EXEC_TIME'] + $this->instanceCounter * $this->secondsPerExternalUrl
                );
            }
        }
    }

    /**
     * Look up all old index configurations which are finished and needs to be reset and done
     */
    public function cleanUpOldRunningConfigurations()
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        // List of tables that store information related to the phash value
        $tablesToClean = [
            'index_phash',
            'index_rel',
            'index_section',
            'index_grlist',
            'index_fulltext',
            'index_debug'
        ];

        $queryBuilder = $connectionPool->getQueryBuilderForTable('index_config');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // Lookup running index configurations:
        $runningIndexingConfigurations = $queryBuilder->select('*')
            ->from('index_config')
            ->where($queryBuilder->expr()->neq('set_id', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->execute()
            ->fetchAll();
        // For each running configuration, look up how many log entries there are which are scheduled
        // for execution and if none, clear the "set_id" (means; Processing was DONE)
        foreach ($runningIndexingConfigurations as $cfgRec) {
            // Look for ended processes:
            $queued_items = $connectionPool->getConnectionForTable('tx_crawler_queue')
                ->count(
                    '*',
                    'tx_crawler_queue',
                    [
                        'set_id' => (int)$cfgRec['set_id'],
                        'exec_time' => 0
                    ]
                );
            if (!$queued_items) {
                // Lookup old phash rows:
                $queryBuilder = $connectionPool->getQueryBuilderForTable('index_phash');
                $oldPhashRows = $queryBuilder
                    ->select('phash')
                    ->from('index_phash')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'freeIndexUid',
                            $queryBuilder->createNamedParameter($cfgRec['uid'], \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->neq(
                            'freeIndexSetId',
                            $queryBuilder->createNamedParameter($cfgRec['set_id'], \PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetchAll();

                // Removing old registrations for all tables
                foreach ($tablesToClean as $table) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
                    $queryBuilder->delete($table)
                        ->where(
                            $queryBuilder->expr()->in(
                                'phash',
                                $queryBuilder->createNamedParameter(
                                    array_column($oldPhashRows, 'phash'),
                                    Connection::PARAM_INT_ARRAY
                                )
                            )
                        )
                        ->execute();
                }

                // End process by updating index-config record:
                $connectionPool->getConnectionForTable('index_config')
                    ->update(
                        'index_config',
                        [
                            'set_id' => 0,
                            'session_data' => ''
                        ],
                        ['uid' => (int)$cfgRec['uid']]
                    );
            }
        }
    }

    /*****************************************
     *
     * Helper functions
     *
     *****************************************/
    /**
     * Check if an input URL are allowed to be indexed. Depends on whether it is already present in the url log.
     *
     * @param string $url URL string to check
     * @param array $urlLog Array of already indexed URLs (input url is looked up here and must not exist already)
     * @param string $baseUrl Base URL of the indexing process (input URL must be "inside" the base URL!)
     * @return string Returns the URL if OK, otherwise FALSE
     */
    public function checkUrl($url, $urlLog, $baseUrl)
    {
        $url = preg_replace('/\\/\\/$/', '/', $url);
        [$url] = explode('#', $url);
        if (strpos($url, '../') === false) {
            if (GeneralUtility::isFirstPartOfStr($url, $baseUrl)) {
                if (!in_array($url, $urlLog)) {
                    return $url;
                }
            }
        }
    }

    /**
     * Indexing External URL
     *
     * @param string $url URL, http://....
     * @param int $pageId Page id to relate indexing to.
     * @param array $rl Rootline array to relate indexing to
     * @param int $cfgUid Configuration UID
     * @param int $setId Set ID value
     * @return array URLs found on this page
     */
    public function indexExtUrl($url, $pageId, $rl, $cfgUid, $setId)
    {
        // Index external URL:
        $indexerObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
        $indexerObj->backend_initIndexer($pageId, 0, 0, '', $rl);
        $indexerObj->backend_setFreeIndexUid($cfgUid, $setId);
        $indexerObj->hash['phash'] = -1;
        // To avoid phash_t3 being written to file sections (otherwise they are removed when page is reindexed!!!)
        $indexerObj->indexExternalUrl($url);
        $url_qParts = parse_url($url);
        $baseAbsoluteHref = $url_qParts['scheme'] . '://' . $url_qParts['host'];
        $baseHref = $indexerObj->extractBaseHref($indexerObj->indexExternalUrl_content);
        if (!$baseHref) {
            // Extract base href from current URL
            $baseHref = $baseAbsoluteHref;
            $baseHref .= substr($url_qParts['path'], 0, strrpos($url_qParts['path'], '/'));
        }
        $baseHref = rtrim($baseHref, '/');
        // Get URLs on this page:
        $subUrls = [];
        $list = $indexerObj->extractHyperLinks($indexerObj->indexExternalUrl_content);
        // Traverse links:
        foreach ($list as $count => $linkInfo) {
            // Decode entities:
            $subUrl = htmlspecialchars_decode($linkInfo['href']);
            $qParts = parse_url($subUrl);
            if (!$qParts['scheme']) {
                $relativeUrl = GeneralUtility::resolveBackPath($subUrl);
                if ($relativeUrl[0] === '/') {
                    $subUrl = $baseAbsoluteHref . $relativeUrl;
                } else {
                    $subUrl = $baseHref . '/' . $relativeUrl;
                }
            }
            $subUrls[] = $subUrl;
        }
        return $subUrls;
    }

    /**
     * Indexing Single Record
     *
     * @param array $r Record to index
     * @param array $cfgRec Configuration Record
     * @param array $rl Rootline array to relate indexing to
     */
    public function indexSingleRecord($r, $cfgRec, $rl = null)
    {
        // Init:
        $rl = is_array($rl) ? $rl : $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
        $fieldList = GeneralUtility::trimExplode(',', $cfgRec['fieldlist'], true);
        $languageField = $GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['languageField'];
        $sys_language_uid = $languageField ? $r[$languageField] : 0;
        // (Re)-Indexing a row from a table:
        $indexerObj = GeneralUtility::makeInstance(\TYPO3\CMS\IndexedSearch\Indexer::class);
        parse_str(str_replace('###UID###', $r['uid'], $cfgRec['get_params']), $GETparams);
        $indexerObj->backend_initIndexer($cfgRec['pid'], 0, $sys_language_uid, '', $rl, $GETparams);
        $indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
        $indexerObj->forceIndexing = true;
        $theContent = '';
        foreach ($fieldList as $k => $v) {
            if (!$k) {
                $theTitle = $r[$v];
            } else {
                $theContent .= $r[$v] . ' ';
            }
        }
        // Indexing the record as a page (but with parameters set, see ->backend_setFreeIndexUid())
        $indexerObj->backend_indexAsTYPO3Page(strip_tags(str_replace('<', ' <', $theTitle)), '', '', strip_tags(str_replace('<', ' <', $theContent)), 'utf-8', $r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['tstamp']], $r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['crdate']], $r['uid']);
    }

    /**
     * Get rootline for closest TypoScript template root.
     * Algorithm same as used in Web > Template, Object browser
     *
     * @param int $id The page id to traverse rootline back from
     * @return array Array where the root lines uid values are found.
     */
    public function getUidRootLineForClosestTemplate($id)
    {
        $rootLineUids = [];
        try {
            // Gets the rootLine
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $id)->get();
            // This generates the constants/config + hierarchy info for the template.
            $tmpl = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);
            $tmpl->runThroughTemplates($rootLine);
            // Root line uids
            foreach ($tmpl->rootLine as $rlkey => $rldat) {
                $rootLineUids[$rlkey] = $rldat['uid'];
            }
        } catch (RootLineException $e) {
            // do nothing
        }
        return $rootLineUids;
    }

    /**
     * Generate the unix time stamp for next visit.
     *
     * @param array $cfgRec Index configuration record
     * @return int The next time stamp
     */
    public function generateNextIndexingTime($cfgRec)
    {
        $currentTime = $GLOBALS['EXEC_TIME'];
        // Now, find a midnight time to use for offset calculation. This has to differ depending on whether we have frequencies within a day or more than a day; Less than a day, we don't care which day to use for offset, more than a day we want to respect the currently entered day as offset regardless of when the script is run - thus the day-of-week used in case "Weekly" is selected will be respected
        if ($cfgRec['timer_frequency'] <= 24 * 3600) {
            $aMidNight = mktime(0, 0, 0) - 1 * 24 * 3600;
        } else {
            $lastTime = $cfgRec['timer_next_indexing'] ?: $GLOBALS['EXEC_TIME'];
            $aMidNight = mktime(0, 0, 0, date('m', $lastTime), date('d', $lastTime), date('y', $lastTime));
        }
        // Find last offset time plus frequency in seconds:
        $lastSureOffset = $aMidNight + MathUtility::forceIntegerInRange($cfgRec['timer_offset'], 0, 86400);
        $frequencySeconds = MathUtility::forceIntegerInRange($cfgRec['timer_frequency'], 1);
        // Now, find out how many blocks of the length of frequency there is until the next time:
        $frequencyBlocksUntilNextTime = ceil(($currentTime - $lastSureOffset) / $frequencySeconds);
        // Set next time to the offset + the frequencyblocks multiplied with the frequency length in seconds.
        return $lastSureOffset + $frequencyBlocksUntilNextTime * $frequencySeconds;
    }

    /**
     * Checks if $url has any of the URls in the $url_deny "list" in it and if so, returns TRUE.
     *
     * @param string $url URL to test
     * @param string $url_deny String where URLs are separated by line-breaks; If any of these strings is the first part of $url, the function returns TRUE (to indicate denial of descend)
     * @return bool TRUE if there is a matching URL (hence, do not index!)
     */
    public function checkDeniedSuburls($url, $url_deny)
    {
        if (trim($url_deny)) {
            $url_denyArray = GeneralUtility::trimExplode(LF, $url_deny, true);
            foreach ($url_denyArray as $testurl) {
                if (GeneralUtility::isFirstPartOfStr($url, $testurl)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Adding entry in queue for Hook
     *
     * @param array $cfgRec Configuration record
     * @param string $title Title/URL
     */
    public function addQueueEntryForHook($cfgRec, $title)
    {
        $nparams = [
            'indexConfigUid' => $cfgRec['uid'],
            // This must ALWAYS be the cfgRec uid!
            'url' => $title,
            'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']']
        ];
        $this->pObj->addQueueEntry_callBack($cfgRec['set_id'], $nparams, $this->callBack, $cfgRec['pid']);
    }

    /**
     * Deletes all data stored by indexed search for a given page
     *
     * @param int $id Uid of the page to delete all pHash
     */
    public function deleteFromIndex($id)
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        // Lookup old phash rows:

        $queryBuilder = $connectionPool->getQueryBuilderForTable('index_section');
        $oldPhashRows = $queryBuilder->select('phash')
            ->from('index_section')
            ->where(
                $queryBuilder->expr()->eq(
                    'page_id',
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        if (empty($oldPhashRows)) {
            return;
        }

        $tables = [
            'index_debug',
            'index_fulltext',
            'index_grlist',
            'index_phash',
            'index_rel',
            'index_section',
        ];
        foreach ($tables as $table) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            $queryBuilder->delete($table)
                ->where(
                    $queryBuilder->expr()->in(
                        'phash',
                        $queryBuilder->createNamedParameter(
                            array_column($oldPhashRows, 'phash'),
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->execute();
        }
    }

    /*************************
     *
     * Hook functions for DataHandler (indexing of records)
     *
     *************************/
    /**
     * DataHandler hook function for on-the-fly indexing of database records
     *
     * @param string $command DataHandler command
     * @param string $table Table name
     * @param string $id Record ID. If new record its a string pointing to index inside \TYPO3\CMS\Core\DataHandling\DataHandler::substNEWwithIDs
     * @param mixed $value Target value (ignored)
     * @param DataHandler $pObj DataHandler calling object
     */
    public function processCmdmap_preProcess($command, $table, $id, $value, $pObj)
    {
        // Clean up the index
        if ($command === 'delete' && $table === 'pages') {
            $this->deleteFromIndex($id);
        }
    }

    /**
     * DataHandler hook function for on-the-fly indexing of database records
     *
     * @param string $status Status "new" or "update
     * @param string $table Table name
     * @param string $id Record ID. If new record its a string pointing to index inside \TYPO3\CMS\Core\DataHandling\DataHandler::substNEWwithIDs
     * @param array $fieldArray Field array of updated fields in the operation
     * @param DataHandler $pObj DataHandler calling object
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj)
    {
        // Check if any fields are actually updated:
        if (empty($fieldArray)) {
            return;
        }
        // Translate new ids.
        if ($status === 'new') {
            $id = $pObj->substNEWwithIDs[$id];
        } elseif ($table === 'pages' && $status === 'update' && (array_key_exists('hidden', $fieldArray) && $fieldArray['hidden'] == 1 || array_key_exists('no_search', $fieldArray) && $fieldArray['no_search'] == 1)) {
            // If the page should be hidden or not indexed after update, delete index for this page
            $this->deleteFromIndex($id);
        }
        // Get full record and if exists, search for indexing configurations:
        $currentRecord = BackendUtility::getRecord($table, $id);
        if (is_array($currentRecord)) {
            // Select all (not running) indexing configurations of type "record" (1) and
            // which points to this table and is located on the same page as the record
            // or pointing to the right source PID
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('index_config');
            $result = $queryBuilder->select('*')
                ->from('index_config')
                ->where(
                    $queryBuilder->expr()->eq('set_id', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        'table2index',
                        $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq(
                                'alternative_source_pid',
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($currentRecord['pid'], \PDO::PARAM_INT)
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'alternative_source_pid',
                            $queryBuilder->createNamedParameter($currentRecord['pid'], \PDO::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        'records_indexonchange',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    )
                )
                ->execute();

            while ($cfgRec = $result->fetch()) {
                $this->indexSingleRecord($currentRecord, $cfgRec);
            }
        }
    }
}
