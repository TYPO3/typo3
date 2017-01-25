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

use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
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
     * The constructor
     */
    public function __construct()
    {
        // To make sure the backend charset is available:
        if (!is_object($GLOBALS['LANG'])) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(\TYPO3\CMS\Lang\LanguageService::class);
            $GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
        }
    }

    /**
     * Initialization of crawler hook.
     * This function is asked for each instance of the crawler and we must check if something is timed to happen and if so put entry(s) in the crawlers log to start processing.
     * In reality we select indexing configurations and evaluate if any of them needs to run.
     *
     * @param object $pObj Parent object (tx_crawler lib)
     * @return void
     */
    public function crawler_init(&$pObj)
    {
        // Select all indexing configuration which are waiting to be activated:
        $indexingConfigurations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'index_config', 'hidden=0
				AND (starttime=0 OR starttime<=' . $GLOBALS['EXEC_TIME'] . ')
				AND timer_next_indexing<' . $GLOBALS['EXEC_TIME'] . '
				AND set_id=0
				' . BackendUtility::deleteClause('index_config'));
        // For each configuration, check if it should be executed and if so, start:
        foreach ($indexingConfigurations as $cfgRec) {
            // Generate a unique set-ID:
            $setId = GeneralUtility::md5int(microtime());
            // Get next time:
            $nextTime = $this->generateNextIndexingTime($cfgRec);
            // Start process by updating index-config record:
            $field_array = [
                'set_id' => $setId,
                'timer_next_indexing' => $nextTime,
                'session_data' => ''
            ];
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config', 'uid=' . (int)$cfgRec['uid'], $field_array);
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
                        $hookObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]);
                        if (is_object($hookObj)) {
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
            // Load the indexing configuration record:
            $cfgRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'index_config', 'uid=' . (int)$params['indexConfigUid']);
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
                            $hookObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['crawler'][$cfgRec['type']]);
                            if (is_object($hookObj)) {
                                $this->pObj = $pObj;
                                // For addQueueEntryForHook()
                                $hookObj->indexOperation($cfgRec, $session_data, $params, $this);
                            }
                        }
                }
                // Save process data which might be modified:
                $field_array = [
                    'session_data' => serialize($session_data)
                ];
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config', 'uid=' . (int)$cfgRec['uid'], $field_array);
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
     * @return void
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
            $numberOfRecords = $cfgRec['recordsbatch'] ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cfgRec['recordsbatch'], 1) : 100;
            // Get root line:
            $rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
            // Select
            $recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $cfgRec['table2index'], 'pid = ' . $pid . '
							AND uid > ' . (int)$session_data['uid'] . BackendUtility::deleteClause($cfgRec['table2index']) . BackendUtility::BEenableFields($cfgRec['table2index']), '', 'uid', $numberOfRecords);
            // Traverse:
            if (!empty($recs)) {
                foreach ($recs as $r) {
                    // Index single record:
                    $this->indexSingleRecord($r, $cfgRec, $rl);
                    // Update the UID we last processed:
                    $session_data['uid'] = $r['uid'];
                }
                // Finally, set entry for next indexing of batch of records:
                $nparams = [
                    'indexConfigUid' => $cfgRec['uid'],
                    'url' => 'Records from UID#' . ($r['uid'] + 1) . '-?',
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
     * @return void
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
                $files = GeneralUtility::removePrefixPathFromList($files, PATH_site);
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
     * @return void
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
     * @return void
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
                $urlList = $pObj->urlListFromUrlArray($vv, $pageRow, $GLOBALS['EXEC_TIME'], 30, 1, 0, $duplicateTrack, $downloadUrls, ['tx_indexedsearch_reindex']);
            }
        }
        // Add subpages to log now:
        if ($params['depth'] < $cfgRec['depth']) {
            // Subpages selected
            $recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title', 'pages', 'pid = ' . $pageUid . BackendUtility::deleteClause('pages'));
            // Traverse subpages and add to queue:
            if (!empty($recs)) {
                foreach ($recs as $r) {
                    $this->instanceCounter++;
                    $url = 'pages:' . $r['uid'] . ': ' . $r['title'];
                    $session_data['urlLog'][] = $url;
                    // Parameters:
                    $nparams = [
                        'indexConfigUid' => $cfgRec['uid'],
                        'url' => $r['uid'],
                        'procInstructions' => ['[Index Cfg UID#' . $cfgRec['uid'] . ']'],
                        'depth' => $params['depth'] + 1
                    ];
                    $pObj->addQueueEntry_callBack($cfgRec['set_id'], $nparams, $this->callBack, $cfgRec['pid'], $GLOBALS['EXEC_TIME'] + $this->instanceCounter * $this->secondsPerExternalUrl);
                }
            }
        }
    }

    /**
     * Look up all old index configurations which are finished and needs to be reset and done
     *
     * @return void
     */
    public function cleanUpOldRunningConfigurations()
    {
        // Lookup running index configurations:
        $runningIndexingConfigurations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,set_id', 'index_config', 'set_id<>0' . BackendUtility::deleteClause('index_config'));
        // For each running configuration, look up how many log entries there are which are scheduled for execution and if none, clear the "set_id" (means; Processing was DONE)
        foreach ($runningIndexingConfigurations as $cfgRec) {
            // Look for ended processes:
            $queued_items = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_crawler_queue', 'set_id=' . (int)$cfgRec['set_id'] . ' AND exec_time=0');
            if (!$queued_items) {
                // Lookup old phash rows:
                $oldPhashRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('phash', 'index_phash', 'freeIndexUid=' . (int)$cfgRec['uid'] . ' AND freeIndexSetId<>' . (int)$cfgRec['set_id']);
                foreach ($oldPhashRows as $pHashRow) {
                    // Removing old registrations for all tables (code copied from \TYPO3\CMS\IndexedSearch\Domain\Repository\IndexedPagesController\AdministrationRepository)
                    $tableArr = ['index_phash', 'index_rel', 'index_section', 'index_grlist', 'index_fulltext', 'index_debug'];
                    foreach ($tableArr as $table) {
                        $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash=' . (int)$pHashRow['phash']);
                    }
                }
                // End process by updating index-config record:
                $field_array = [
                    'set_id' => 0,
                    'session_data' => ''
                ];
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config', 'uid=' . (int)$cfgRec['uid'], $field_array);
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
     * @return string Returls the URL if OK, otherwise FALSE
     */
    public function checkUrl($url, $urlLog, $baseUrl)
    {
        $url = preg_replace('/\\/\\/$/', '/', $url);
        list($url) = explode('#', $url);
        if (!strstr($url, '../')) {
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
     * @return void
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
        $indexerObj->backend_initIndexer($cfgRec['pid'], 0, $sys_language_uid, '', $rl, $GETparams, (bool)$cfgRec['chashcalc']);
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
        $indexerObj->backend_indexAsTYPO3Page(strip_tags(str_replace('<', ' <', $theTitle)), '', '', strip_tags(str_replace('<', ' <', $theContent)), $GLOBALS['LANG']->charSet, $r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['tstamp']], $r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['crdate']], $r['uid']);
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
        $tmpl = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);
        $tmpl->tt_track = 0;
        // Do not log time-performance information
        $tmpl->init();
        // Gets the rootLine
        $sys_page = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $rootLine = $sys_page->getRootLine($id);
        // This generates the constants/config + hierarchy info for the template.
        $tmpl->runThroughTemplates($rootLine, 0);
        // Root line uids
        $rootline_uids = [];
        foreach ($tmpl->rootLine as $rlkey => $rldat) {
            $rootline_uids[$rlkey] = $rldat['uid'];
        }
        return $rootline_uids;
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
        $lastSureOffset = $aMidNight + \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cfgRec['timer_offset'], 0, 86400);
        $frequencySeconds = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($cfgRec['timer_frequency'], 1);
        // Now, find out how many blocks of the length of frequency there is until the next time:
        $frequencyBlocksUntilNextTime = ceil(($currentTime - $lastSureOffset) / $frequencySeconds);
        // Set next time to the offset + the frequencyblocks multiplied with the frequency length in seconds.
        return $lastSureOffset + $frequencyBlocksUntilNextTime * $frequencySeconds;
    }

    /**
     * Checks if $url has any of the URls in the $url_deny "list" in it and if so, returns TRUE.
     *
     * @param string $url URL to test
     * @param string $url_deny String where URLs are separated by line-breaks; If any of these strings is the first part of $url, the function returns TRUE (to indicate denial of decend)
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
     * @return void
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
     * @return void
     */
    public function deleteFromIndex($id)
    {
        // Lookup old phash rows:
        $oldPhashRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('phash', 'index_section', 'page_id=' . (int)$id);
        if (!empty($oldPhashRows)) {
            $pHashesToDelete = [];
            foreach ($oldPhashRows as $pHashRow) {
                $pHashesToDelete[] = $pHashRow['phash'];
            }
            $where_clause = 'phash IN (' . implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($pHashesToDelete)) . ')';
            $tables = [
                'index_debug',
                'index_fulltext',
                'index_grlist',
                'index_phash',
                'index_rel',
                'index_section',
            ];
            foreach ($tables as $table) {
                $GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where_clause);
            }
        }
    }

    /*************************
     *
     * Hook functions for TCEmain (indexing of records)
     *
     *************************/
    /**
     * TCEmain hook function for on-the-fly indexing of database records
     *
     * @param string $command TCEmain command
     * @param string $table Table name
     * @param string $id Record ID. If new record its a string pointing to index inside \TYPO3\CMS\Core\DataHandling\DataHandler::substNEWwithIDs
     * @param mixed $value Target value (ignored)
     * @param FormEngine $pObj tcemain calling object
     * @return void
     */
    public function processCmdmap_preProcess($command, $table, $id, $value, $pObj)
    {
        // Clean up the index
        if ($command === 'delete' && $table === 'pages') {
            $this->deleteFromIndex($id);
        }
    }

    /**
     * TCEmain hook function for on-the-fly indexing of database records
     *
     * @param string $status Status "new" or "update
     * @param string $table Table name
     * @param string $id Record ID. If new record its a string pointing to index inside \TYPO3\CMS\Core\DataHandling\DataHandler::substNEWwithIDs
     * @param array $fieldArray Field array of updated fields in the operation
     * @param FormEngine $pObj tcemain calling object
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $pObj)
    {
        // Check if any fields are actually updated:
        if (!empty($fieldArray)) {
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
                // Select all (not running) indexing configurations of type "record" (1) and which points to this table and is located on the same page as the record or pointing to the right source PID
                $indexingConfigurations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'index_config', 'hidden=0
						AND (starttime=0 OR starttime<=' . $GLOBALS['EXEC_TIME'] . ')
						AND set_id=0
						AND type=1
						AND table2index=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'index_config') . '
						AND (
								(alternative_source_pid=0 AND pid=' . (int)$currentRecord['pid'] . ')
								OR (alternative_source_pid=' . (int)$currentRecord['pid'] . ')
							)
						AND records_indexonchange=1
						' . BackendUtility::deleteClause('index_config'));
                foreach ($indexingConfigurations as $cfgRec) {
                    $this->indexSingleRecord($currentRecord, $cfgRec);
                }
            }
        }
    }
}
