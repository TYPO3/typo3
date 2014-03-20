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
 * A copy is found in the text file GPL.txt and important notices to the license
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
 * while respecting language, enable fields, etc.
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
			$collectionRecord['table_name'],
			$collectionRecord['field_name']
		);
		$collection->fromArray($collectionRecord);
		if ($fillItems) {
			$collection->loadContents();
		}
		return $collection;
	}

	/**
	 * Loads the collection with the given id from persistence
	 * For memory reasons, only data for the collection itself is loaded by default.
	 * Entries can be loaded on first access or straightaway using the $fillItems flag.
	 *
	 * Overrides the parent method because of the call to "self::create()" which otherwise calls up
	 * \TYPO3\CMS\Core\Category\Collection\CategoryCollection
	 *
	 * @param integer $id Id of database record to be loaded
	 * @param boolean $fillItems Populates the entries directly on load, might be bad for memory on large collections
	 * @param string $tableName the table name
	 * @param string $fieldName Name of the categories relation field
	 * @return \TYPO3\CMS\Core\Collection\CollectionInterface
	 */
	static public function load($id, $fillItems = FALSE, $tableName = '', $fieldName = '') {
		$collectionRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			static::$storageTableName,
			'uid = ' . (int)$id . self::getFrontendObject()->sys_page->enableFields(static::$storageTableName)
		);
		$collectionRecord['table_name'] = $tableName;
		$collectionRecord['field_name'] = $fieldName;
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
		// Assemble where clause
		$where = 'AND ' . self::$storageTableName . '.uid = ' . (int)$this->getIdentifier();
		// Add condition on tablenames fields
		$where .= ' AND sys_category_record_mm.tablenames = ' . $this->getDatabaseConnection()->fullQuoteStr(
			$this->getItemTableName(),
			'sys_category_record_mm'
		);
		// Add condition on fieldname field
		$where .= ' AND sys_category_record_mm.fieldname = ' . $this->getDatabaseConnection()->fullQuoteStr(
			$this->getRelationFieldName(),
			'sys_category_record_mm'
		);
		// Add enable fields for item table
		$where .= self::getFrontendObject()->sys_page->enableFields($this->getItemTableName());
		// If language handling is defined for item table, add language condition
		if (isset($GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField'])) {
			// Consider default or "all" language
			$languageField = $this->getItemTableName() . '.' . $GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField'];
			$languageCondition = $languageField . ' IN (0, -1)';
			// If not in default language, also consider items in current language with no original
			if ($this->getFrontendObject()->sys_language_content > 0) {
				$languageCondition .= '
					OR (' . $languageField . ' = ' . (int)$this->getFrontendObject()->sys_language_content . '
					AND ' . $this->getItemTableName() . '.' .
					$GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['transOrigPointerField'] . ' = 0)
				';
			}
			$where .= ' AND (' . $languageCondition . ')';
		}
		// Get the related records from the database
		$resource = $this->getDatabaseConnection()->exec_SELECT_mm_query(
			$this->getItemTableName() . '.*',
			self::$storageTableName,
			'sys_category_record_mm',
			$this->getItemTableName(),
			$where
		);

		if ($resource) {
			while ($record = $this->getDatabaseConnection()->sql_fetch_assoc($resource)) {
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
			$this->getDatabaseConnection()->sql_free_result($resource);
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
