<?php
namespace TYPO3\CMS\IndexedSearch\Example;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2001-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Index search crawler hook example
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Index search crawler hook example
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CrawlerHook {

	/**
	 * Function is called when an indexing session starts according to the time intervals set for the indexing configuration.
	 *
	 * @return 	string		Return a text string for the first, initiating queue entry for the crawler.
	 * @todo Define visibility
	 */
	public function initMessage() {
		return 'Start of Custom Example Indexing session!';
	}

	/**
	 * This will do two things:
	 * 1) Carry out actual indexing of content (one or more items)
	 * 2) Add one or more new entries into the crawlers queue so we are called again (another instance) for further indexing in the session (optional of course, if all indexing is done, we add no new entries)
	 *
	 * @param 	array		Indexing Configuration Record (the record which holds the information that lead to this indexing session...)
	 * @param 	array		Session data variable. Passed by reference. Changed content is saved and passed back upon next instance in the session.
	 * @param 	array		Params array from the queue entry.
	 * @param 	object		Grant Parent Object (from "crawler" extension)
	 * @param 	object		Parent Object (from "indexed_search" extension)
	 * @return 	void
	 * @todo Define visibility
	 */
	public function indexOperation($cfgRec, &$session_data, $params, &$pObj) {
		// Init session data array if not already:
		if (!is_array($session_data)) {
			$session_data = array(
				'step' => 0
			);
		}
		// Increase step counter (this is just an example of how the session data can be used - to track how many instances of indexing is left)
		$session_data['step']++;
		switch ((int) $session_data['step']) {
		case 1:
			// Indexing Example: Content accessed with GET parameters added to URL:
			// Load indexer if not yet [DON'T CHANGE]:
			$pObj->loadIndexerClass();
			// Get rootline from the Indexing Record (needed because the indexer relates all search results to a position in the page tree!) [DON'T CHANGE]:
			$rl = $pObj->getUidRootLineForClosestTemplate($cfgRec['pid']);
			// Set up language uid, if any:
			$sys_language_uid = 0;
			// Set up 2 example items to index:
			$exampleItems = array(
				array(
					'ID' => '123',
					'title' => 'Title of Example 1',
					'content' => 'Vestibulum leo turpis, fringilla sit amet, semper eget, vestibulum ut, arcu. Vestibulum mauris orci, vulputate quis, congue eget, nonummy'
				),
				array(
					'ID' => 'example2',
					'title' => 'Title of Example 2',
					'content' => 'Cras tortor turpis, vulputate non, accumsan a, pretium in, magna. Cras turpis turpis, pretium pulvinar, pretium vel, nonummy eu.'
				)
			);
			// For each item, index it (this is what you might like to do in batches of like 100 items if all your content spans thousands of items!)
			foreach ($exampleItems as $item) {
				// Prepare the GET variables array that must be added to the page URL in order to view result:
				parse_str('&itemID=' . rawurlencode($item['ID']), $GETparams);
				// Prepare indexer (make instance, initialize it, set special features for indexing parameterized content - probably none of this should be changed by you) [DON'T CHANGE]:
				$indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
				$indexerObj->backend_initIndexer($cfgRec['pid'], 0, $sys_language_uid, '', $rl, $GETparams, FALSE);
				$indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
				$indexerObj->forceIndexing = TRUE;
				// Indexing the content of the item (see \TYPO3\CMS\IndexedSearch\Indexer::backend_indexAsTYPO3Page() for options)
				$indexerObj->backend_indexAsTYPO3Page($item['title'], '', '', $item['content'], $GLOBALS['LANG']->charSet, $item['tstamp'], $item['create_date'], $item['ID']);
			}
			break;
		case 2:
			// Indexing Example: Content accessed directly in file system:
			// Load indexer if not yet [DON'T CHANGE]:
			$pObj->loadIndexerClass();
			// Get rootline from the Indexing Record (needed because the indexer relates all search results to a position in the page tree!) [DON'T CHANGE]:
			$rl = $pObj->getUidRootLineForClosestTemplate($cfgRec['pid']);
			// Set up language uid, if any:
			$sys_language_uid = 0;
			// Prepare indexer (make instance, initialize it, set special features for indexing parameterized content - probably none of this should be changed by you) [DON'T CHANGE]:
			$indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
			$indexerObj->backend_initIndexer($cfgRec['pid'], 0, $sys_language_uid, '', $rl);
			$indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
			$indexerObj->hash['phash'] = -1;
			// To avoid phash_t3 being written to file sections (otherwise they are removed when page is reindexed!!!)
			// Index document:
			$indexerObj->indexRegularDocument('fileadmin/templates/index.html', TRUE);
			break;
		case 3:
			// Indexing Example: Content accessed on External URLs:
			// Load indexer if not yet.
			$pObj->loadIndexerClass();
			// Index external URL:
			$indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
			$indexerObj->backend_initIndexer($cfgRec['pid'], 0, $sys_language_uid, '', $rl);
			$indexerObj->backend_setFreeIndexUid($cfgRec['uid'], $cfgRec['set_id']);
			$indexerObj->hash['phash'] = -1;
			// To avoid phash_t3 being written to file sections (otherwise they are removed when page is reindexed!!!)
			// Index external URL (HTML only):
			$indexerObj->indexExternalUrl('http://www.google.com/');
			break;
		}
		// Finally, set entry for next indexing instance (if all steps are not completed)
		if ($session_data['step'] <= 3) {
			$title = 'Step #' . $session_data['step'] . ' of 3';
			// Just information field. Never mind that the field is called "url" - this is what will be shown in the "crawler" log. Could be a URL - or whatever else tells what that indexing instance will do.
			$pObj->addQueueEntryForHook($cfgRec, $title);
		}
	}

}


?>