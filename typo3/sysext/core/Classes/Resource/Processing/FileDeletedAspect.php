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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Class FileDeletedAspect
 *
 * We do not have AOP in TYPO3 for now, thus the acspect which
 * deals with deleted files is a slot which reacts on a signal
 * on file deletion.
 *
 * The aspect cleansup processed files and filereferences
 *
 * @package TYPO3\CMS\Core\Resource\Security
 */
class FileDeletedAspect {

	/**
	 * Remove all processed files that belong to the given File object
	 *
	 * @param FileInterface $fileObject
	 */
	public function cleanupProcessedFiles(FileInterface $fileObject) {

		// only delete processed files of File objects
		if (!$fileObject instanceof File) {
			return;
		}

		/** @var $processedFileRepository \TYPO3\CMS\Core\Resource\ProcessedFileRepository */
		$processedFileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');

		/** @var $processedFile \TYPO3\CMS\Core\Resource\ProcessedFile */
		foreach ($processedFileRepository->findAllByOriginalFile($fileObject) as $processedFile) {
			$processedFile->delete(TRUE);
		}
	}
}

?>