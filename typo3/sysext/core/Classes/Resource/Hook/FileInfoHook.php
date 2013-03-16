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
/**
 * Utility class to render TCEforms information about a sys_file record
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class FileInfoHook {

	/**
	 * User function for sys_file (element)
	 *
	 * @param array $PA the array with additional configuration options.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj the TCEforms parent object
	 * @return string The HTML code for the TCEform field
	 */
	public function renderFileInfo(array $PA, \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj) {
		$fileRecord = $PA['row'];
		if ($fileRecord['uid'] > 0) {
			$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($fileRecord['uid']);
			$processedFile = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => 150, 'height' => 150));
			$previewImage = $processedFile->getPublicUrl(TRUE);
			$content = '';
			if ($previewImage) {
				$content .= '<img src="' . htmlspecialchars($previewImage) . '" alt="" class="t3-tceforms-sysfile-imagepreview" />';
			}
			$content .= '<strong>' . htmlspecialchars($fileObject->getName()) . '</strong> (' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize())) . ')<br />';
			$content .= \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($PA['table'], 'type', $fileObject->getType()) . ' (' . $fileObject->getMimeType() . ')<br />';
			$content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaDataLocation', TRUE) . ': ' . htmlspecialchars($fileObject->getStorage()->getName()) . ' - ' . htmlspecialchars($fileObject->getIdentifier()) . '<br />';
			$content .= '<br />';
		} else {
			$content = '<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaErrorInvalidRecord', TRUE) . '</h2>';
		}
		return $content;
	}

}


?>