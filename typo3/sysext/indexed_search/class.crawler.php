<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   59: class tx_indexedsearch_crawler
 *   70:     function crawler_init(&$pObj)
 *  119:     function crawler_execute($params,&$pObj)
 *  180:     function checkUrl($url,$urlLog,$baseUrl)
 *  212:     function indexExtUrl($url, $pageId, $rl, $cfgUid)
 *  251:     function loadIndexerClass()
 *  263:     function getUidRootLineForClosestTemplate($id)
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class tx_indexedsearch_crawler {

		// Static:
	var $secondsPerExternalUrl = 3;		// Number of seconds to use as interval between queued indexing operations of URLs

		// Internal, dynamic:
	var $instanceCounter = 0;		// Counts up for each added URL

		// Internal, static:
	var $callBack = 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler';		// The object reference to this class.

	/**
	 * Initialization of crawler hook.
	 * This function is asked for each instance of the crawler and we must check if something is timed to happen and if so put entry(s) in the crawlers log to start processing.
	 * In reality we select indexing configurations and evaluate if any of them needs to run.
	 *
	 * @param	object		Parent object (tx_crawler lib)
	 * @return	void
	 */
	function crawler_init(&$pObj){

			// Select all indexing configuration which are waiting to be activated:
		$indexingConfigurations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,pid,first_run_time,frequency,last_run,type,externalUrl,filepath',
			'index_config',
			'hidden=0
				AND (starttime=0 OR starttime<='.time().')
				AND set_id=0
				'.t3lib_BEfunc::deleteClause('index_config')

		);

			// For each configuration, check if it should be executed and if so, start:
		foreach($indexingConfigurations as $cfgRec)	{

				// Generate a unique set-ID:
			$setId = t3lib_div::md5int(microtime());

				// Start process by updating index-config record:
			$field_array = array (
				'set_id' => $setId,
				'session_data' => '',
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config','uid='.intval($cfgRec['uid']), $field_array);

				// Based on configuration type:
			switch($cfgRec['type'])	{
				case 1:
						// Parameters:
					$params = array(
						'indexConfigUid' => $cfgRec['uid'],
						'url' => 'Records (start)',
						'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']')
					);
						//
					$pObj->addQueueEntry_callBack($setId,$params,$this->callBack,$cfgRec['pid']);
				break;
				case 3:	// External URL:

						// Parameters:
					$params = array(
						'indexConfigUid' => $cfgRec['uid'],		// General
						'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']'),	// General
						'url' => $cfgRec['externalUrl'],	// Partly general... (for URL and file types)
						'depth' => 0	// Specific for URL and file types
					);

					$pObj->addQueueEntry_callBack($setId,$params,$this->callBack,$cfgRec['pid']);
				break;
				case 2:

						// Parameters:
					$params = array(
						'indexConfigUid' => $cfgRec['uid'],		// General
						'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']'),	// General
						'url' => $cfgRec['filepath'],	// Partly general... (for URL and file types)
						'depth' => 0	// Specific for URL and file types
					);

					$pObj->addQueueEntry_callBack($setId,$params,$this->callBack,$cfgRec['pid']);
				break;
			}
		}

			// Finally, look up all old index configurations which are finished and needs to be reset and done.
		$this->cleanUpOldRunningConfigurations();
	}

	/**
	 * Call back function for execution of a log element
	 *
	 * @param	array		Params from log element
	 * @param	object		Parent object (tx_crawler lib)
	 * @return	array		Result array
	 */
	function crawler_execute($params,&$pObj)	{

			// Indexer configuration ID must exist:
		if ($params['indexConfigUid'])	{
			list($cfgRec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'index_config',
				'uid='.intval($params['indexConfigUid'])
			);

			if (is_array($cfgRec))	{

					// Unpack session data:
				$session_data = unserialize($cfgRec['session_data']);

					// Select which type:
				switch($cfgRec['type'])	{
					case 1:
						if ($cfgRec['table2index'] && isset($GLOBALS['TCA'][$cfgRec['table2index']]))	{

								// Init session data array if not already:
							if (!is_array($session_data))	{
								$session_data = array(
									'uid' => 0
								);
							}

								// Init:
							$pid = intval($cfgRec['alternative_source_pid']) ? intval($cfgRec['alternative_source_pid']) : $this->pObj->id;
							$fieldList = t3lib_div::trimExplode(',',$cfgRec['fieldlist'],1);

								// Get root line:
							$rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);

								// Load indexer if not yet.
							$this->loadIndexerClass();

								// Select
							$recs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
										'*',
										$cfgRec['table2index'],
										'pid = '.intval($pid).'
											AND uid > '.intval($session_data['uid']).
											t3lib_BEfunc::deleteClause($cfgRec['table2index']),
										'',
										'uid',
										'2'
									);

								// Traverse:
							if (count($recs))	{
								foreach($recs as $r)	{

										// (Re)-Indexing a row from a table:
									$indexerObj = &t3lib_div::makeInstance('tx_indexedsearch_indexer');
									parse_str(str_replace('###UID###',$r['uid'],$cfgRec['get_params']),$GETparams);
									$indexerObj->backend_initIndexer($cfgRec['pid'], 0, 0, '', $rl, $GETparams, $cfgRec['chashcalc'] ? TRUE : FALSE);
									$indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
									$indexerObj->forceIndexing = TRUE;

									$theContent = '';
									foreach($fieldList as $k => $v)	{
										if (!$k)	{
											$theTitle = $r[$v];
										} else {
											$theContent.= $r[$v].' ';
										}
									}

									$indexerObj->backend_indexAsTYPO3Page(
										$theTitle,
										'',
										'',
										$theContent,
										$GLOBALS['LANG']->charSet,
										$r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['tstamp']],
										$r[$GLOBALS['TCA'][$cfgRec['table2index']]['ctrl']['crdate']],
										$r['uid']
									);

									#debug($indexerObj->internal_log);

										// Update the UID we last processed:
									$session_data['uid'] = $r['uid'];
								}


									// Parameters:
								$nparams = array(
									'indexConfigUid' => $cfgRec['uid'],
									'url' => 'Records from UID#'.($r['uid']+1).'-?',
									'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']')
								);
									//
								$pObj->addQueueEntry_callBack($cfgRec['set_id'],$nparams,$this->callBack,$cfgRec['pid']);
							}
						}
					break;
					case 3:	// External URL:

							// Init session data array if not already:
						if (!is_array($session_data))	{
							$session_data = array(
								'urlLog' => array($params['url'])
							);
						}

							// Index the URL:
						$rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);
						$subUrls = $this->indexExtUrl($params['url'], $cfgRec['pid'], $rl, $cfgRec['uid'], $cfgRec['set_id']);

							// Add more elements to log now:
						if ($params['depth'] < $cfgRec['depth'])	{
							foreach($subUrls as $url)	{
								if ($url = $this->checkUrl($url,$session_data['urlLog'],$cfgRec['externalUrl']))	{
									$this->instanceCounter++;
									$session_data['urlLog'][] = $url;

										// Parameters:
									$nparams = array(
										'indexConfigUid' => $cfgRec['uid'],
										'url' => $url,
										'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']'),
										'depth' => $params['depth']+1
									);
									$pObj->addQueueEntry_callBack($cfgRec['set_id'],$nparams,$this->callBack,$cfgRec['pid'],time()+$this->instanceCounter*$this->secondsPerExternalUrl);
								}
							}
						}
					break;
					case 2:

							// Prepare path, making it absolute and checking:
						$readpath = $params['url'];
						if (!t3lib_div::isAbsPath($readPath))	{
							$readpath = t3lib_div::getFileAbsFileName($readpath);
						}

						if (t3lib_div::isAllowedAbsPath($readpath))	{
							if (@is_file($readpath))	{	// If file, index it!

									// Get root line:
								$rl = $this->getUidRootLineForClosestTemplate($cfgRec['pid']);

									// Load indexer if not yet.
								$this->loadIndexerClass();

									// (Re)-Indexing file on page.
								$indexerObj = &t3lib_div::makeInstance('tx_indexedsearch_indexer');
								$indexerObj->backend_initIndexer($cfgRec['pid'], 0, 0, '', $rl);
								$indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
								$indexerObj->hash['phash'] = -1;	// EXPERIMENT - but to avoid phash_t3 being written to file sections (otherwise they are removed when page is reindexed!!!)

									// Index document:
								$indexerObj->indexRegularDocument(substr($readpath,strlen(PATH_site)), TRUE);
							} elseif (@is_dir($readpath)) {	// If dir, read content and create new pending items for log:

									// Select files and directories in path:
								$extList = implode(',',t3lib_div::trimExplode(',',$cfgRec['extensions'],1));
								$fileArr = array();
								$files = t3lib_div::getAllFilesAndFoldersInPath($fileArr,$readpath,$extList,0,0);

								$directoryList = t3lib_div::get_dirs($readpath);
								if (is_array($directoryList) && $params['depth'] < $cfgRec['depth'])	{
									foreach ($directoryList as $subdir)	{
										if ((string)$subdir!='')	{
											$files[]= $readpath.$subdir.'/';
										}
									}
								}
								$files = t3lib_div::removePrefixPathFromList($files,PATH_site);

									// traverse the items and create log entries:
								foreach($files as $path)	{
									$this->instanceCounter++;
									if ($path!==$params['url'])	{
											// Parameters:
										$nparams = array(
											'indexConfigUid' => $cfgRec['uid'],
											'url' => $path,
											'procInstructions' => array('[Index Cfg UID#'.$cfgRec['uid'].']'),
											'depth' => $params['depth']+1
										);
										$pObj->addQueueEntry_callBack($cfgRec['set_id'],$nparams,$this->callBack,$cfgRec['pid'],time()+$this->instanceCounter*$this->secondsPerExternalUrl);
									}
								}
							}
						}
					break;
				}

					// Save process data which might be modified:
				$field_array = array (
					'session_data' => serialize($session_data)
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config','uid='.intval($cfgRec['uid']), $field_array);
			}
		}

		return array('log' => $params);
	}

	/**
	 * Look up all old index configurations which are finished and needs to be reset and done
	 *
	 * @return	void
	 */
	function cleanUpOldRunningConfigurations()	{

			// Lookup running index configurations:
		$runningIndexingConfigurations = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid,set_id',
			'index_config',
			'set_id!=0'.t3lib_BEfunc::deleteClause('index_config')
		);

			// For each running configuration, look up how many log entries there are which are scheduled for execution and if none, clear the "set_id" (means; Processing was DONE)
		foreach($runningIndexingConfigurations as $cfgRec)	{

				// Look for ended processes:
			list($queued_items) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'count(*) AS count',
				'tx_crawler_queue',
				'set_id='.intval($cfgRec['set_id']).' AND exec_time=0'
			);

			if (!$queued_items['count'])	{

					// Lookup old phash rows:
				$oldPhashRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'phash,freeIndexUid,freeIndexSetId,externalUrl',
					'index_phash',
					'freeIndexUid='.intval($cfgRec['uid']).' AND freeIndexSetId!='.$cfgRec['set_id']
				);

				foreach($oldPhashRows as $pHashRow)	{
						// Removing old registrations for all tables (code copied from class.tx_indexedsearch_modfunc1.php)
					$tableArr = explode(',','index_phash,index_rel,index_section,index_grlist,index_fulltext,index_debug');
					foreach($tableArr as $table)	{
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, 'phash='.intval($pHashRow['phash']));
					}
				}

					// End process by updating index-config record:
				$field_array = array (
					'set_id' => 0,
					'session_data' => '',
				);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('index_config','uid='.intval($cfgRec['uid']), $field_array);
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
	 * @param	string		URL
	 * @param	array		Array of already indexed URLs (input url is looked up here and must not exist already)
	 * @param	string		Base URL of the indexing process (input URL must be "inside" the base URL!)
	 * @return	string		Returls the URL if OK, otherwise false
	 */
	function checkUrl($url,$urlLog,$baseUrl)	{
		$url = ereg_replace('\/\/$','/',$url);
		list($url) = explode('#',$url);

		if (!strstr($url,'../'))	{
			if (t3lib_div::isFirstPartOfStr($url,$baseUrl))	{
				if (!in_array($url,$urlLog))	{
					return $url;
				}
			}
		}
	}

	/**
	 * Indexing External URL
	 *
	 * @param	string		URL, http://....
	 * @param	integer		Page id to relate indexing to.
	 * @param	array		Rootline array to relate indexing to
	 * @param	integer		Configuration UID
	 * @param	integer		Set ID
	 * @return	array		URLs found on this page
	 */
	function indexExtUrl($url, $pageId, $rl, $cfgUid, $setId)	{

			// Load indexer if not yet.
		$this->loadIndexerClass();

			// Index external URL:
		$indexerObj = &t3lib_div::makeInstance('tx_indexedsearch_indexer');
		$indexerObj->backend_initIndexer($pageId, 0, 0, '', $rl);
		$indexerObj->backend_setFreeIndexUid($cfgUid, $setId);

		$indexerObj->indexExternalUrl($url);
		$url_qParts = parse_url($url);

			// Get URLs on this page:
		$subUrls = array();
		$list = $indexerObj->extractHyperLinks($indexerObj->indexExternalUrl_content);

						// Traverse links:
		foreach($list as $count => $linkInfo)	{

				// Decode entities:
			$subUrl = t3lib_div::htmlspecialchars_decode($linkInfo['href']);

			$qParts = parse_url($subUrl);
			if (!$qParts['scheme'])	{
				$subUrl = $url_qParts['scheme'].'://'.$url_qParts['host'].'/'.t3lib_div::resolveBackPath($subUrl);
			}

			$subUrls[] = $subUrl;
		}

		return $subUrls;
	}

	/**
	 * Include indexer class.
	 *
	 * @return	void
	 */
	function loadIndexerClass()	{
		global $TYPO3_CONF_VARS;
		require_once(t3lib_extMgm::extPath('indexed_search').'class.indexer.php');
	}

	/**
	 * Get rootline for closest TypoScript template root.
	 * Algorithm same as used in Web > Template, Object browser
	 *
	 * @param	integer		The page id to traverse rootline back from
	 * @return	array		Array where the root lines uid values are found.
	 */
	function getUidRootLineForClosestTemplate($id)	{
		global $TYPO3_CONF_VARS;

		require_once (PATH_t3lib."class.t3lib_page.php");
		require_once (PATH_t3lib."class.t3lib_tstemplate.php");
		require_once (PATH_t3lib."class.t3lib_tsparser_ext.php");



		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

				// Gets the rootLine
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
		$rootLine = $sys_page->getRootLine($id);
		$tmpl->runThroughTemplates($rootLine,0);	// This generates the constants/config + hierarchy info for the template.

			// Root line uids
		$rootline_uids = array();
		foreach($tmpl->rootLine as $rlkey => $rldat)	{
			$rootline_uids[$rlkey] = $rldat['uid'];
		}

		return $rootline_uids;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.crawler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/indexed_search/class.crawler.php']);
}
?>