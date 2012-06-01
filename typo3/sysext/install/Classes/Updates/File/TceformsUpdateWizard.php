<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Ingmar Schlecht <ingmar@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Upgrade wizard which goes through all files referenced in the tt_content.image filed
 * and creates sys_file records as well as sys_file_reference records for the individual usages.
 *
 * @package     TYPO3
 * @author Ingmar Schlecht <ingmar@typo3.org>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Tx_Install_Updates_File_TceformsUpdateWizard extends Tx_Install_Updates_Base {
	/**
	 * @var string
	 */
	protected $title = 'Migrate file relations';

	/**
	 * @var t3lib_file_Storage
	 */
	protected $storage;

	/**
	 * Initialize the storage repository.
	 */
	public function init() {
		/** @var $storageRepository t3lib_file_Repository_StorageRepository */
		$storageRepository = t3lib_div::makeInstance('t3lib_file_Repository_StorageRepository');
		$storages = $storageRepository->findAll();
		$this->storage = $storages[0]; // take first found storage
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param	string		&$description: The description for the update
	 * @return	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
			// @todo Function below copied from sysext/install/updates/class.tx_coreupdates_imagelink.php, needs to be adopted

		$description = 'TODO add description of FAL migration';

			// make this wizard always available
		return TRUE;
	}

	/**
	 * Performs the database update.
	 *
	 * @param	array		&$dbQueries: queries done in this update
	 * @param	mixed		&$customMessages: custom messages
	 * @return	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(&$dbQueries, &$customMessages) {
		$this->init();

			// Function below copied from sysext/install/updates/class.tx_coreupdates_imagelink.php

		$tables = array(
			'tt_content' => array(
				'image' => array(
					'sourcePath' => 'uploads/pics/',
						// Relative to fileadmin
					'targetPath' => '_migrated/pics/',
					'titleTexts' => 'titleText',
					'captions' => 'imagecaption',
					'links' => 'image_link',
					'alternativeTexts' => 'altText',
				),
			),
			'pages' => array(
				'media' => array(
					'sourcePath' => 'uploads/media/',
						// Relative to fileadmin
					'targetPath' => '_migrated/media/',
				),
			),
			'pages_language_overlay' => array(
				'media' => array(
					'sourcePath' => 'uploads/media/',
						// Relative to fileadmin
					'targetPath' => '_migrated/media/',
				),
			),
		);
			// We write down the fields that were migrated. Like this: tt_content:media
			// so you can check whether a field was already migrated
		if (isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard'])) {
			$finishedFields = explode(',', $GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard']);
		} else {
			$finishedFields = array();
		}

		$result = TRUE;
		if ($this->versionNumber >= 6000000) {
			/**
			 * TODO
			 *
			 * - for each table:
			 *   - get records from table
			 *   - for each record:
			 *     - for each field:
			 *       - migrate field
			 */
			foreach ($tables as $table => $tableConfiguration) {
				$fieldsToMigrate = array_keys($tableConfiguration);
				$fieldsToGet = array();
					// find all additional fields we should get from the database
				foreach ($tableConfiguration as $field => $fieldConfiguration) {
					$fieldKey = $table . ':' . $field;
					if (array_search($fieldKey, $finishedFields) !== FALSE) {
							// this field was already migrated
						continue;
					} else {
						$finishedFields[] = $fieldKey;
					}

					$fieldsToGet[] = $field;
					if (isset($fieldConfiguration['titleTexts'])) {
						$fieldsToGet[] = $fieldConfiguration['titleTexts'];
					}
					if (isset($fieldConfiguration['alternativeTexts'])) {
						$fieldsToGet[] = $fieldConfiguration['alternativeTexts'];
					}
					if (isset($fieldConfiguration['captions'])) {
						$fieldsToGet[] = $fieldConfiguration['captions'];
					}
					if (isset($fieldConfiguration['links'])) {
						$fieldsToGet[] = $fieldConfiguration['links'];
					}

				}

				$records = $this->getRecordsFromTable($table, $fieldsToGet);

				foreach ($records as $record) {
					foreach ($fieldsToMigrate as $field) {
						$dbQueries = array_merge($this->migrateField($table, $record, $field, $tableConfiguration[$field]));
					}
				}
			}
		}

		$finishedFields = implode(',', $finishedFields);

		$this->markWizardAsDone($finishedFields);

		return $result;
	}

	protected function getRecordsFromTable($table, $relationFields) {
		$fields = implode(',', array_merge($relationFields, array('uid', 'pid')));

		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, '');

		return $records;
	}

	protected function migrateField($table, $row, $fieldname, $fieldConfiguration) {
		$fieldItems = t3lib_div::trimExplode(',', $row[$fieldname], TRUE);

		if (empty($fieldItems) || is_numeric($row[$fieldname])) {
			return array();
		}

		if (isset($fieldConfiguration['titleTexts'])) {
			$titleTextField = $fieldConfiguration['titleTexts'];
			$titleTextContents = explode(LF, $row[$titleTextField]);
		}
		if (isset($fieldConfiguration['alternativeTexts'])) {
			$alternativeTextField = $fieldConfiguration['alternativeTexts'];
			$alternativeTextContents = explode(LF, $row[$alternativeTextField]);
		}
		if (isset($fieldConfiguration['captions'])) {
			$captionField = $fieldConfiguration['captions'];
			$captionContents = explode(LF, $row[$captionField]);
		}
		if (isset($fieldConfiguration['links'])) {
			$linkField = $fieldConfiguration['links'];
			$linkContents = explode(LF, $row[$linkField]);
		}

		$fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';

		$queries = array();
		$i = 0;
		foreach ($fieldItems as $item) {

			if(!PATH_site) throw new Exception('PATH_site was undefined.');

				// copy file
			$sourcePath = PATH_site . $fieldConfiguration['sourcePath'] . $item;
			$targetPath = PATH_site . $fileadminDirectory . $fieldConfiguration['targetPath'] . $item;
			if(!is_dir(dirname($targetPath))) {
				t3lib_div::mkdir_deep(dirname($targetPath));
			}

			rename($sourcePath, $targetPath);

				// get the File object
			$file = $this->storage->getFile($fieldConfiguration['targetPath'] . $item);

			if($file instanceof t3lib_file_File) {
				$fields = array(
						// TODO add sorting/sorting_foreign
					'fieldname' => $fieldname,
					'table_local' => 'sys_file',
					'uid_foreign' => $row['uid'],
					'uid_local' => $file->getUid(),
					'tablenames' => $table,
					'crdate' => time(),
					'tstamp' => time(),
				);
				if (isset($titleTextField)) {
					$fields['title'] = trim($titleTextContents[$i]);
				}
				if (isset($alternativeTextField)) {
					$fields['alternative'] = trim($alternativeTextContents[$i]);
				}
				if (isset($captionField)) {
					$fields['description'] = trim($captionContents[$i]);
				}
				if (isset($linkField)) {
					$fields['link'] = trim($linkContents[$i]);
				}

				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file_reference', $fields);
				$queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

				++$i;
			}
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . $row['uid'], array($fieldname => ''));
		$queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

		return $queries;
		// TODO update original row
	}
}

?>