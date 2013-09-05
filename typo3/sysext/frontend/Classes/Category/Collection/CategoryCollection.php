<?php
namespace TYPO3\CMS\Frontend\Category\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Francois Suter <francois.suter@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Extend category collection for the frontend, to collect related records
 * while respected language, enable fields, etc.
 *
 * @author Francois Suter <francois.suter@typo3.org>
 */
class CategoryCollection extends \TYPO3\CMS\Core\Category\Collection\CategoryCollection {

	/**
	 * Creates a new collection objects and reconstitutes the
	 * given database record to the new object.
	 *
	 * Overrides the parent method to create a *frontend* category collection.
	 *
	 * @param array $collectionRecord Database record
	 * @param boolean $fillItems Populates the entries directly on load, might be bad for memory on large collections
	 * @return \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection
	 */
	static public function create(array $collectionRecord, $fillItems = FALSE) {
		/** @var $collection \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection */
		$collection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Frontend\\Category\\Collection\\CategoryCollection',
			$collectionRecord['table_name']
		);
		$collection->fromArray($collectionRecord);
		if ($fillItems) {
			$collection->loadContents();
		}
		return $collection;
	}

	/**
	 * Loads the collections with the given id from persistence
	 * For memory reasons, per default only f.e. title, database-table,
	 * identifier (what ever static data is defined) is loaded.
	 * Entries can be load on first access.
	 *
	 * Overrides the parent method because of the call to "self::create()" which otherwise calls up
	 * \TYPO3\CMS\Core\Category\Collection\CategoryCollection
	 *
	 * @param integer $id Id of database record to be loaded
	 * @param boolean $fillItems Populates the entries directly on load, might be bad for memory on large collections
	 * @param string $tableName the table name
	 * @return \TYPO3\CMS\Core\Collection\CollectionInterface
	 */
	static public function load($id, $fillItems = FALSE, $tableName = '') {
		$collectionRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			static::$storageTableName,
			'uid = ' . intval($id) . self::getFrontendObject()->sys_page->enableFields(static::$storageTableName)
		);
		$collectionRecord['table_name'] = $tableName;
		return self::create($collectionRecord, $fillItems);
	}

	/**
	 * Gets the collected records in this collection, by
	 * looking up the MM relations of this record to the
	 * table name defined in the local field 'table_name'.
	 *
	 * Overrides its parent method to implement usage of language,
	 * enable fields, etc. Also performs overlays.
	 *
	 * @return array
	 */
	protected function getCollectedRecords() {
		$relatedRecords = array();
		// Base category condition
		$where = '
			AND ' . self::$storageTableName . '.uid=' . intval($this->getIdentifier()) .
			' AND sys_category_record_mm.tablenames = "' . $this->getItemTableName() . '"
		';
		// Add enable fields for item table
		$where .= self::getFrontendObject()->sys_page->enableFields($this->getItemTableName());
		// If language handling is defined for item table, add language condition
		if (isset($GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField'])) {
			// Consider default or "all" language
			$languageField = $this->getItemTableName() . '.' . $GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField'];
			$languageCondition = $languageField . ' IN (0, -1)';
			// If not in default language, also consider items in current language with no original
			if ($this->getFrontendObject()->sys_language_uid > 0) {
				$languageCondition .= '
					OR (' . $languageField . ' = ' . intval($this->getFrontendObject()->sys_language_uid) . '
					AND ' . $this->getItemTableName() . '.' .
					$GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['transOrigPointerField'] . ' = 0)
				';
			}
			$where .= ' AND (' . $languageCondition . ')';
		}
		// Get the related records from the database
		$resource = $this->getDatabase()->exec_SELECT_mm_query(
			$this->getItemTableName() . '.*',
			self::$storageTableName,
			'sys_category_record_mm',
			$this->getItemTableName(),
			$where
		);

		if ($resource) {
			while ($record = $this->getDatabase()->sql_fetch_assoc($resource)) {
				// Overlay the record for workspaces
				$this->getFrontendObject()->sys_page->versionOL(
					$this->getItemTableName(),
					$record
				);
				// Overlay the record for translations
				if (is_array($record) && $this->getFrontendObject()->sys_language_contentOL) {
					if ($this->getItemTableName() === 'pages') {
						$record = $this->getFrontendObject()->sys_page->getPageOverlay($record);
					} else {
						$record = $this->getFrontendObject()->sys_page->getRecordOverlay(
							$this->getItemTableName(),
							$record,
							$this->getFrontendObject()->sys_language_content,
							$this->getFrontendObject()->sys_language_contentOL
						);
					}
				}
				// Record may have been unset during the overlay process
				if (is_array($record)) {
					$relatedRecords[] = $record;
				}
			}
			$this->getDatabase()->sql_free_result($resource);
		}
		return $relatedRecords;
	}

	/**
	 * Gets the TSFE object.
	 *
	 * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	static protected function getFrontendObject() {
		return $GLOBALS['TSFE'];
	}
}

?>