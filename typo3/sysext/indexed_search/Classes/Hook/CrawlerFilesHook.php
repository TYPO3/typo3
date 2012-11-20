<?php
namespace TYPO3\CMS\IndexedSearch\Hook;

/**
 * Crawler hook for indexed search. Works with the "crawler" extension
 * This hook is specifically used to index external files found on pages through the crawler extension.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see \TYPO3\CMS\IndexedSearch\Indexer::extractLinks()
 */
class CrawlerFilesHook {

	/**
	 * Call back function for execution of a log element
	 *
	 * @param 	array		Params from log element.
	 * @param 	object		Parent object (tx_crawler lib)
	 * @return 	array		Result array
	 * @todo Define visibility
	 */
	public function crawler_execute($params, &$pObj) {
		// Load indexer if not yet.
		$this->loadIndexerClass();
		if (is_array($params['conf'])) {
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

	/**
	 * Include indexer class.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function loadIndexerClass() {
		global $TYPO3_CONF_VARS;
		require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('indexed_search') . 'class.indexer.php';
	}

}


?>