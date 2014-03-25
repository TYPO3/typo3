<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Benjamin Mack <benni@typo3.org>
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
 * Upgrade wizard that moves all RTE magic images (usually in uploads/)
 * that have the prefix RTEmagicC_* to the default storage (usually fileadmin/_migrated/RTE/)
 * and also updates the according fields (e.g. tt_content:123:bodytext) with the new string, and updates
 * the softreference index
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class RteMagicImagesUpdateWizard extends AbstractUpdate {

	/**
	 * Title of the update wizard
	 * @var string
	 */
	protected $title = 'Migrate all RTE magic images from uploads/RTEmagicC_* to fileadmin/_migrated/RTE/';

	/**
	 * The default storage
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * The old location of the file name, e.g. "uploads/RTEmagicC_"
	 * @var string
	 */
	protected $oldPrefix = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	/**
	 * Constructor
	 */
	public function __construct() {
		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');
		$this->logger = $logManager->getLogger(__CLASS__);

		// Set it to uploads/RTEmagicC_*
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'])) {
			$this->oldPrefix = $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'] . 'RTEmagicC_';
		}
	}

	/**
	 * Initialize the storage repository.
	 *
	 * @return void
	 */
	public function init() {
		/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
		$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storages = $storageRepository->findAll();
		$this->storage = $storages[0];
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param string $description The description for the update
	 * @return boolean TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'This update wizard goes through all magic images, located in "' . htmlspecialchars($this->oldPrefix) . '", and moves the files to fileadmin/_migrated/RTE/.';
		$description .= '<br />It also moves the files from uploads/ to the fileadmin/_migrated/ path.';
		// Issue warning about sys_refindex needing to be up to date
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
		$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			'This script bases itself on the references contained in the general reference index (sys_refindex). It is strongly advised to update it before running this wizard.',
			'Updating the reference index',
			\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
		);
		$description .= $message->render();

		// Wizard is only available if oldPrefix set
		if ($this->oldPrefix) {
			$oldRecords = $this->findMagicImagesInOldLocation();
			if (count($oldRecords) > 0) {
				$description .= '<br />There are currently <strong>' . count($oldRecords) . '</strong> magic images in the old directory.<br />';
				return TRUE;
			}
		}

		// Disable the update wizard if there are no old RTE magic images
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

		if (!PATH_site) {
			throw new \Exception('PATH_site was undefined.');
		}

		$fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/');
		$targetDirectory = '/_migrated/RTE/';
		$fullTargetDirectory = PATH_site . $fileadminDirectory . $targetDirectory;

		// Create the directory, if necessary
		if (!is_dir($fullTargetDirectory)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($fullTargetDirectory);
		}


		$oldRecords = $this->findMagicImagesInOldLocation();
		foreach ($oldRecords as $refRecord) {

			// Is usually uploads/RTE_magicC_123423324.png.png
			$sourceFileName     = $refRecord['ref_string'];
			// Absolute path/filename
			$fullSourceFileName = PATH_site . $refRecord['ref_string'];
			$targetFileName     = $targetDirectory . \TYPO3\CMS\Core\Utility\PathUtility::basename($refRecord['ref_string']);
			// Full directory
			$fullTargetFileName = $fullTargetDirectory . \TYPO3\CMS\Core\Utility\PathUtility::basename($refRecord['ref_string']);

			// maybe the file has been moved previously
			if (!file_exists($fullTargetFileName)) {
				// If the source file does not exist, we should just continue, but leave a message in the docs;
				// ideally, the user would be informed after the update as well.
				if (!file_exists(PATH_site . $sourceFileName)) {
					$this->logger->notice('File ' . $sourceFileName . ' does not exist. Reference was not migrated.', array());

					$format = 'File \'%s\' does not exist. Referencing field: %s.%d.%s. The reference was not migrated.';
					$message = sprintf($format, $sourceFileName, $refRecord['tablename'], $refRecord['recuid'], $refRecord['field']);
					$customMessages .= PHP_EOL . $message;

					continue;
				}

				rename($fullSourceFileName, $fullTargetFileName);
			}

			// Get the File object
			$file = $this->storage->getFile($targetFileName);
			if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
				// And now update the referencing field
				$targetFieldName = $refRecord['field'];
				$targetRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					'uid, ' . $targetFieldName,
					$refRecord['tablename'],
					'uid=' . (int)$refRecord['recuid']
				);
				if ($targetRecord) {
					// Replace the old filename with the new one, and add data-* attributes used by the RTE
					$searchString = 'src="' . $sourceFileName . '"';
					$replacementString = 'src="' . $fileadminDirectory . $targetFileName . '"';
					$replacementString .= ' data-htmlarea-file-uid="' . $file->getUid() . '"';
					$replacementString .= ' data-htmlarea-file-table="sys_file"';
					$targetRecord[$targetFieldName] = str_replace(
						$searchString,
						$replacementString,
						$targetRecord[$targetFieldName]
					);
					// Update the record
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						$refRecord['tablename'],
						'uid=' . (int)$refRecord['recuid'],
						array($targetFieldName => $targetRecord[$targetFieldName])
					);
					$queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

					// Finally, update the sys_refindex table as well
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'sys_refindex',
						'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($refRecord['hash'], 'sys_refindex'),
						array(
							'ref_table'  => 'sys_file',
							'softref_key' => 'rtehtmlarea_images',
							'ref_uid'    => $file->getUid(),
							'ref_string' => $fileadminDirectory . $targetFileName
						)
					);
					$queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
				}

			}
		}

		return TRUE;
	}

	/**
	 * Go through the soft refindex and find all occurences where the old filename
	 * is still written in the ref_string
	 *
	 * @return array Entries from sys_refindex
	 */
	protected function findMagicImagesInOldLocation() {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'hash, tablename, recuid, field, ref_table, ref_uid, ref_string',
			'sys_refindex',
			'ref_string LIKE "' . $GLOBALS['TYPO3_DB']->escapeStrForLike($this->oldPrefix, 'sys_refindex') . '%"',
			'',
			'ref_string ASC'
		);
		return $records;
	}

}
