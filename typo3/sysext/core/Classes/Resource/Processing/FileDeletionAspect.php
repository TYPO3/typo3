<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Frans Saris <franssaris@gmail.com>
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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileDeletionAspect
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with deleted files is a slot which reacts on a signal
 * on file deletion.
 *
 * The aspect cleans up database records, processed files and filereferences
 */
class FileDeletionAspect {

	/**
	 * Return a file index repository
	 *
	 * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
	 */
	protected function getFileIndexRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\FileIndexRepository');
	}

	/**
	 * Return a metadata repository
	 *
	 * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
	 */
	protected function getMetaDataRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
	}

	/**
	 * Return a processed file repository
	 *
	 * @return \TYPO3\CMS\Core\Resource\ProcessedFileRepository
	 */
	protected function getProcessedFileRepository() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
	}

	/**
	 * Wrapper method for getting DatabaseConnection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Cleanup database record for a deleted file
	 *
	 * @param FileInterface $fileObject
	 * @return void
	 */
	public function removeFromRepository(FileInterface $fileObject) {
		// remove file from repository
		if ($fileObject instanceof File) {
			$this->cleanupProcessedFiles($fileObject);
			$this->cleanupCategoryReferences($fileObject);
			$this->getFileIndexRepository()->remove($fileObject->getUid());
			$this->getMetaDataRepository()->removeByFileUid($fileObject->getUid());

			// remove all references
			$this->getDatabaseConnection()->exec_DELETEquery(
				'sys_file_reference',
				'uid_local=' . (int)$fileObject->getUid() . ' AND table_local = \'sys_file\''
			);

		} elseif ($fileObject instanceof ProcessedFile) {
			$this->getDatabaseConnection()->exec_DELETEquery('sys_file_processedfile', 'uid=' . (int)$fileObject->getUid());
		}
	}

	/**
	 * Remove all category references of the deleted file.
	 *
	 * @param File $fileObject
	 * @return void
	 */
	protected function cleanupCategoryReferences(File $fileObject) {

		// Retrieve the file metadata uid which is different from the file uid.
		$metadataProperties = $fileObject->_getMetaData();

		$metaDataUid = isset($metadataProperties['_ORIG_uid']) ? $metadataProperties['_ORIG_uid'] : $metadataProperties['uid'];
		$this->getDatabaseConnection()->exec_DELETEquery(
			'sys_category_record_mm',
			'uid_foreign=' . (int)$metaDataUid . ' AND tablenames = \'sys_file_metadata\''
		);
	}

	/**
	 * Remove all processed files that belong to the given File object
	 *
	 * @param FileInterface $fileObject
	 * @return void
	 */
	protected function cleanupProcessedFiles(FileInterface $fileObject) {

		// only delete processed files of File objects
		if (!$fileObject instanceof File) {
			return;
		}

		/** @var $processedFile \TYPO3\CMS\Core\Resource\ProcessedFile */
		foreach ($this->getProcessedFileRepository()->findAllByOriginalFile($fileObject) as $processedFile) {
			if ($processedFile->exists()) {
				$processedFile->delete(TRUE);
			}
			$this->removeFromRepository($processedFile);
		}
	}
}
