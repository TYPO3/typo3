<?php
namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Backend\Form\DataPreprocessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Provides services around item processing
 */
class ItemProcessingService {

	/**
	 * Executes an itemsProcFunc if defined in TCA and returns the combined result (predefined + processed items)
	 *
	 * @param string $table
	 * @param int $pageId
	 * @param string $field
	 * @param array $row
	 * @param array $tcaConfig The TCA configuration of $field
	 * @param array $selectedItems The items already defined in the TCA configuration
	 * @return array The processed items (including the predefined items)
	 */
	public function getProcessingItems($table, $pageId, $field, $row, $tcaConfig, $selectedItems) {
		$pageId = $table === 'pages' ? $row['uid'] : $row['pid'];

		$TSconfig = BackendUtility::getPagesTSconfig($pageId);
		$fieldTSconfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.'];

		$dataPreprocessor = GeneralUtility::makeInstance(DataPreprocessor::class);
		$items = $dataPreprocessor->procItems(
			$selectedItems,
			$fieldTSconfig['itemsProcFunc.'],
			$tcaConfig,
			$table,
			$row,
			$field
		);

		return $items;
	}

}
