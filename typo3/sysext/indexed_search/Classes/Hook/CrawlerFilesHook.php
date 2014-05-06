<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * This hook is specifically used to index external files found on pages through the crawler extension.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see \TYPO3\CMS\IndexedSearch\Indexer::extractLinks()
 */
class CrawlerFilesHook {

	/**
	 * Call back function for execution of a log element
	 *
	 * @param array $params Params from log element.
	 * @param object $pObj Parent object (tx_crawler lib)
	 * @return null|array Result array
	 * @todo Define visibility
	 */
	public function crawler_execute($params, &$pObj) {
		if (!is_array($params['conf'])) {
			return;
		}
		// Initialize the indexer class:
		$indexerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
		$indexerObj->conf = $params['conf'];
		$indexerObj->init();
		// Index document:
		if ($params['alturl']) {
			$fI = pathinfo($params['document']);
			$ext = strtolower($fI['extension']);
			$indexerObj->indexRegularDocument($params['alturl'], TRUE, $params['document'], $ext);
		} else {
			$indexerObj->indexRegularDocument($params['document'], TRUE);
		}
		// Return OK:
		return array('content' => array());
	}
}
