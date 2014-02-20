<?php
namespace TYPO3\CMS\Core\Resource\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Benjamin Mack <benni@typo3.org>
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

use \TYPO3\CMS\Backend\Utility\BackendUtility;
use \TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Utility class to render TCEforms information about a sys_file record
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class FileInfoHook {

	/**
	 * User function for sys_file (element)
	 *
	 * @param array $propertyArray the array with additional configuration options.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj the TCEforms parent object
	 * @return string The HTML code for the TCEform field
	 */
	public function renderFileInfo(array $propertyArray, \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj) {
		$fileRecord = $propertyArray['row'];
		$fileObject = NULL;
		if ($fileRecord['uid'] > 0) {
			$fileObject = ResourceFactory::getInstance()->getFileObject((int)$fileRecord['uid']);

		}
		return $this->renderFileInformationContent($fileObject);
	}

	/**
	 * User function for sys_file_meta (element)
	 *
	 * @param array $propertyArray the array with additional configuration options.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj the TCEforms parent object
	 * @return string The HTML code for the TCEform field
	 */
	public function renderFileMetadataInfo(array $propertyArray, \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj) {
		$fileMetadataRecord = $propertyArray['row'];
		$fileObject = NULL;
		if ($fileMetadataRecord['file'] > 0) {
			$fileObject = ResourceFactory::getInstance()->getFileObject((int)$fileMetadataRecord['file']);
		}

		return $this->renderFileInformationContent($fileObject);
	}


	/**
	 * Renders a HTML Block with file information
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $file
	 * @return string
	 */
	protected function renderFileInformationContent(\TYPO3\CMS\Core\Resource\File $file = NULL) {
		if ($file !== NULL) {
			$processedFile = $file->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => 150, 'height' => 150));
			$previewImage = $processedFile->getPublicUrl(TRUE);
			$content = '';
			if ($file->isMissing()) {
				$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($file);
				$content .= $flashMessage->render();
			}
			if ($previewImage) {
				$content .= '<img src="' . htmlspecialchars($previewImage) . '" alt="" class="t3-tceforms-sysfile-imagepreview" />';
			}
			$content .= '<strong>' . htmlspecialchars($file->getName()) . '</strong>';
			$content .= '(' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($file->getSize())) . 'bytes)<br />';
			$content .= BackendUtility::getProcessedValue('sys_file', 'type', $file->getType()) . ' (' . $file->getMimeType() . ')<br />';
			$content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaDataLocation', TRUE) . ': ';
			$content .= htmlspecialchars($file->getStorage()->getName()) . ' - ' . htmlspecialchars($file->getIdentifier()) . '<br />';
			$content .= '<br />';
		} else {
			$content = '<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaErrorInvalidRecord', TRUE) . '</h2>';
		}

		return $content;
	}
}
