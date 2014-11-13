<?php
namespace TYPO3\CMS\Recycler\Domain\Model;

/**
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

use TYPO3\CMS\Recycler\Utility\RecyclerUtility;

/**
 * Model class for the 'recycler' extension.
 *
 * @author Julian Kleinhans <typo3@kj187.de>
 */
class Tables {

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $languageService;

	/**
	 * Database Connection
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->languageService = $GLOBALS['LANG'];
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Get tables for menu example
	 *
	 * @param string $format Return format (example: json) - currently unused
	 * @param bool $withAllOption FALSE: no, TRUE: return tables with a "all" option
	 * @param int $startUid UID from selected page
	 * @param int $depth How many levels recursive
	 * @return string The tables to be displayed
	 */
	public function getTables($format, $withAllOption = TRUE, $startUid, $depth = 0) {
		$deletedRecordsTotal = 0;
		$tables = array();
		foreach (array_keys($GLOBALS['TCA']) as $tableName) {
			$deletedField = RecyclerUtility::getDeletedField($tableName);
			if ($deletedField) {
				// Determine whether the table has deleted records:
				$deletedCount = $this->databaseConnection->exec_SELECTcountRows('uid', $tableName, $deletedField . '<>0');
				if ($deletedCount) {
					/* @var $deletedDataObject \TYPO3\CMS\Recycler\Domain\Model\DeletedRecords */
					$deletedDataObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Recycler\Domain\Model\DeletedRecords::class);
					$deletedData = $deletedDataObject->loadData($startUid, $tableName, $depth)->getDeletedRows();
					if (isset($deletedData[$tableName])) {
						if ($deletedRecordsInTable = count($deletedData[$tableName])) {
							$deletedRecordsTotal += $deletedRecordsInTable;
							$tables[] = array(
								$tableName,
								$deletedRecordsInTable,
								$tableName,
								RecyclerUtility::getUtf8String($this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
							);
						}
					}
				}
			}
		}
		$jsonArray = $tables;
		if ($withAllOption) {
			array_unshift($jsonArray, array(
				'',
				$deletedRecordsTotal,
				'',
				$this->languageService->sL('LLL:EXT:recycler/mod1/locallang.xlf:label_alltables')
			));
		}
		$output = json_encode($jsonArray);
		return $output;
	}
}
