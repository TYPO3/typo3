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
 * that have the prefix RTEmagicC_* to the default storage (usually fileadmin/_migrated_/RTE/)
 * and also updates the according fields (e.g. tt_content:123:bodytext) with the new string, and updates
 * the softreference index
 *
 * @author Benjamin Mack <benni@typo3.org>
 * @license http://www.gnu.org/copyleft/gpl.html
 */
class RteMagicImagesUpdateWizard extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * title of the update wizard
	 * @var string
	 */
	protected $title = 'Migrate all RTE magic images from uploads/RTEmagicC_* to fileadmin/_migrated_/RTE/';

	/**
	 * the default storage
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * the old location of the file name, e.g. "uploads/RTEmagicC_"
	 * @var string
	 */
	protected $oldPrefix = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Log\Logger
	 */
	protected $logger;

	public function __construct() {
		/** @var $logManager \TYPO3\CMS\Core\Log\LogManager */
		$logManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager');
		$this->logger = $logManager->getLogger(__CLASS__);

		// set it to uploads/RTEmagicC_*
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
		$description = 'This update wizard goes through all magic images, located in ' . \TYPO3\CMS\Core\Utility\PathUtility::dirname($this->oldPrefix) . '., and moves the files to fileadmin/_migrated_/RTE/.<br />It also moves the files from uploads/ to the fileadmin/_migrated/ path.';

		// wizard is only available if oldPrefix set
		if ($this->oldPrefix) {
			$oldRecords = $this->findMagicImagesInOldLocation();
			if (count($oldRecords) > 0) {
				$description .= '<br />There are currently <strong>' . count($oldRecords) . '</strong> magic images in the old directory.<br />';
				return TRUE;
			}
		}

		// disable the update wizard if there are no old RTE magic images
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
		$targetDirectory = '/_migrated_/RTE/';
		$fullTargetDirectory = PATH_site . $fileadminDirectory . $targetDirectory;

		// create the directory, if necessary
		if (!is_dir($fullTargetDirectory)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($fullTargetDirectory);
		}


		$oldRecords = $this->findMagicImagesInOldLocation();
		foreach ($oldRecords as $refRecord) {

			// is usually uploads/RTE_magicC_123423324.png.png
			$sourceFileName     = $refRecord['ref_string'];
			// absolute path/filename
			$fullSourceFileName = PATH_site . $refRecord['ref_string'];
			$targetFileName     = $targetDirectory . \TYPO3\CMS\Core\Utility\PathUtility::basename($refRecord['ref_string']);
			// full directory
			$fullTargetFileName = $fullTargetDirectory . \TYPO3\CMS\Core\Utility\PathUtility::basename($refRecord['ref_string']);

			// if the source file does not exist, we should just continue, but leave a message in the docs;
			// ideally, the user would be informed after the update as well.
			if (!file_exists(PATH_site . $sourceFileName)) {
				$this->logger->notice('File ' . $sourceFileName . ' does not exist. Reference was not migrated.', array());

				$format = 'File \'%s\' does not exist. Referencing field: %s.%d.%s. The reference was not migrated.';
				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\TYPO3\CMS\Core\Messaging\FlashMessage',
					sprintf($format, $sourceFileName, $refRecord['tablename'], $row['recuid'], $row['field']),
					'', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
				);
				/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
				$customMessages .= '<br />' . $message->render();

				continue;
			}

			rename($fullSourceFileName, $fullTargetFileName);

			// get the File object
			$file = $this->storage->getFile($targetFileName);
			if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
				// and now update the referencing field
				$targetFieldName = $refRecord['field'];
				$targetRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, ' . $targetFieldName, $refRecord['table'], 'uid=' . intval($refRecord['recuid']));
				if ($targetRecord) {
					// replace the old filename with the new one, and update the according record
					$targetRecord[$targetFieldName] = str_replace($sourceFileName, $targetFileName, $targetRecord[$targetFieldName]);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						$refRecord['table'],
						'uid=' . intval($refRecord['recuid']),
						array($targetFieldName => $targetRecord[$targetFieldName])
					);
					$queries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);

					// finally, update the sys_refindex table as well
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'sys_refindex',
						'hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($refRecord['hash'], 'sys_refindex'),
						array(
							'ref_table'  => 'sys_file',
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


?>