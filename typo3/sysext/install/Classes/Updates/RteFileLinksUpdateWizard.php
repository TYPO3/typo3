<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter <francois@typo3.org>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Upgrade wizard that rewrites all file links to FAL references.
 *
 * The content string and the reference index (sys_refindex) are updated accordingly.
 */
class RteFileLinksUpdateWizard extends AbstractUpdate {

	/**
	 * Title of the update wizard
	 * @var string
	 */
	protected $title = 'Migrate all file links of RTE-enabled fields to FAL';

	/**
	 * @var string Path the to fileadmin directory
	 */
	protected $fileAdminDir;

	/**
	 * The default storage
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * @var \TYPO3\CMS\Core\Html\RteHtmlParser
	 */
	protected $rteHtmlParser;

	/**
	 * Count of converted links
	 * @var integer
	 */
	protected $convertedLinkCounter = 0;

	/**
	 * Is DBAL installed or not (if not, we can use transactions)
	 * @var boolean
	 */
	protected $isDbalInstalled = FALSE;

	/**
	 * Array to store file conversion errors
	 * @var array
	 */
	protected $errors = array();

	/**
	 * List of update queries
	 * @var array
	 */
	protected $queries = array();

	/**
	 * Initialize some objects
	 *
	 * @return void
	 */
	public function init() {
		$this->rteHtmlParser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\RteHtmlParser');
		/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
		$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storages = $storageRepository->findAll();
		$this->storage = $storages[0];
		$this->fileAdminDir = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
		// Check if DBAL is installed or not
		$this->isDbalInstalled = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal');
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param string $description The description for the update
	 * @return boolean TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'This update wizard goes through all file links in all rich-text fields and changes them to FAL references.';
		$description .= 'If the process times out, please run it again.';
		// Issue warning about sys_refindex needing to be up to date
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
		$message = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			'This script bases itself on the references contained in the general reference index (sys_refindex). It is strongly advised to update it before running this wizard.',
			'Updating the reference index',
			\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
		);
		$description .= $message->render();

		// Confirm activation only if some old-style links are found
		$oldRecords = $this->findOldLinks();
		if (count($oldRecords) > 0) {
			$description .= '<br />There are currently <strong>' . count($oldRecords) . '</strong> links to update.<br />';
			return TRUE;
		}

		// No update needed, disable the wizard
		return FALSE;
	}

	/**
	 * Performs the database update.
	 *
	 * @param array $dbQueries queries done in this update
	 * @param mixed $customMessages custom messages
	 * @return boolean TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$this->init();

		// Make sure we have a storage
		if (!$this->storage) {
			$customMessages = 'No file resource storage found';
			return FALSE;
		}

		// Get the references and migrate them
		$records = $this->findOldLinks();
		foreach ($records as $singleRecord) {
			$this->migrateRecord($singleRecord);
		}
		$dbQueries = $this->queries;

		if (count($this->errors) > 0) {
			$customMessages .= implode(PHP_EOL, $this->errors);
			if ($this->convertedLinkCounter == 0) {
				// no links converted only missing files: UPDATE was not successful
				return FALSE;
			}
		}

		if ($this->convertedLinkCounter > 0) {
			$customMessages = $this->convertedLinkCounter . ' links converted.' . PHP_EOL . $customMessages;
		} else {
			$customMessages .= 'No file links found';
		}
		return TRUE;
	}

	/**
	 *  Processes each record and updates the database
	 *
	 * @param array $reference Reference to a record from sys_refindex
	 * @return void
	 */
	protected function migrateRecord(array $reference) {
		// Get the current record based on the sys_refindex information
		$record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid, ' . $reference['field'],
			$reference['tablename'],
			'uid = ' . $reference['recuid']
		);
		if ($record !== NULL) {
			$this->convertFileLinks($reference, $record);
		} else {
			// Original record could not be found (happens if sys_refindex is not up to date), issue error
			$this->errors[] = 'Original record not found for reference to element ' . $reference['recuid'] . ' of table ' . $reference['tablename'] . ' in field ' . $reference['field'] . '. Not migrated.';
		}
	}

	/**
	 * The actual transformation of the links
	 * pretty similar to TS_links_rte in RteHtmlParser
	 *
	 * @param array $reference sys_refindex information
	 * @param array $record Original record pointed to by the sys_refindex reference
	 * @return void
	 */
	protected function convertFileLinks(array $reference, array $record) {
		// First of all, try to get the referenced file. Continue only if found.
		try {
			$fileObject = $this->fetchReferencedFile($reference['ref_string'], $reference);
		} catch (\InvalidArgumentException $exception) {
			$fileObject = NULL;
			$this->errors[] = $reference['ref_string'] . ' could not be replaced. File does not exist.';
		}
		if ($fileObject instanceof \TYPO3\CMS\Core\Resource\AbstractFile) {
			// Next, match the reference path in the content to be sure it's present inside a <link> tag
			$content = $record[$reference['field']];
			$regularExpression = '$<(link ' . str_replace(' ', '%20', $reference['ref_string']) . ').*>$';
			$matches = array();
			$result = preg_match($regularExpression, $content, $matches);
			if ($result) {
				// Replace the file path with the file reference
				$modifiedContent = str_replace(
					$matches[1],
					'link file:' . $fileObject->getUid(),
					$record[$reference['field']]
				);
				// Save the changes and stop looping
				$this->saveChanges($modifiedContent, $reference, $fileObject);
				$this->convertedLinkCounter++;
			} else {
				$this->errors[] = $reference['ref_string'] . ' not found in referenced element (uid: ' . $reference['recuid'] . ' of table ' . $reference['tablename'] . ' in field ' . $reference['field'] . '). Reference index was probably out of date.';
			}
		}
	}

	/**
	 * Tries to fetch the file object corresponding to the given path.
	 *
	 * @param string $path Path to a file (starting with "fileadmin/")
	 * @param array $reference Corresponding sys_refindex entry
	 * @return null|\TYPO3\CMS\Core\Resource\FileInterface
	 */
	protected function fetchReferencedFile($path, array $reference) {
		$fileObject = NULL;
		if (@file_exists(PATH_site . '/' . $path)) {
			try {
				$fileObject = $this->storage->getFile(
					'/' . str_replace(
						$this->fileAdminDir,
						'',
						$path
					)
				);
			} catch (\TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException $notFoundException) {
				// This should really not happen, since we are testing existence of the file just before
				$this->errors[] = $path . ' not found (referenced in element ' . $reference['recuid'] . ' of table ' . $reference['tablename'] . ' in field ' . $reference['field'] . ')';
			}
		} else {
			// Nothing to be done if file not found
			$this->errors[] = $path . ' not found (referenced in element ' . $reference['recuid'] . ' of table ' . $reference['tablename'] . ' in field ' . $reference['field'] . ')';
		}
		return $fileObject;
	}

	/**
	 * Saves the modified content to the database and updates the sys_refindex accordingly.
	 *
	 * @param string $modifiedText Original content with the file links replaced
	 * @param array $reference sys_refindex record
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
	 * @return void
	 */
	protected function saveChanges($modifiedText, array $reference, $file) {

		// If DBAL is not installed, we can start a transaction before saving
		// This ensures that a possible time out doesn't break the database integrity
		// by occurring between the two needed DB writes.
		if (!$this->isDbalInstalled) {
			$GLOBALS['TYPO3_DB']->sql_query('START TRANSACTION');
		}

		// Save the changed field
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$reference['tablename'],
			'uid = ' . $reference['recuid'],
			array(
				$reference['field'] => $modifiedText
			)
		);
		$this->queries[] = htmlspecialchars(str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery));

		// Finally, update the sys_refindex table as well
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'sys_refindex',
			'hash = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($reference['hash'], 'sys_refindex'),
			array(
				'ref_table'  => 'sys_file',
				'ref_uid'    => $file->getUid(),
				'ref_string' => ''
			)
		);
		$this->queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

		// Confirm the transaction
		if (!$this->isDbalInstalled) {
			$GLOBALS['TYPO3_DB']->sql_query('COMMIT');
		}
	}

	/**
	 * Use sys_refindex to find all links to "old" files in typolink tags.
	 *
	 * This will find any RTE-enabled field.
	 *
	 * @return array Entries from sys_refindex
	 */
	protected function findOldLinks() {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'hash, tablename, recuid, field, ref_table, ref_uid, ref_string',
			'sys_refindex',
			'softref_key = \'typolink_tag\' AND ref_table = \'_FILE\' '
		);
		return $records;
	}

}
