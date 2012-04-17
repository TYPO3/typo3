<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Benjamin Mack <benni@typo3.org>
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
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_file_Utility_FileInfo {
	/**
	 * User function for sys_file (element)
	 *
	 * @param array $PA the array with additional configuration options.
	 * @param t3lib_TCEforms $tceformsObj the TCEforms parent object
	 * @return string The HTML code for the TCEform field
	 */
	public function renderFileInfo(array $PA, t3lib_TCEforms $tceformsObj) {
		$fileRecord = $PA['row'];

		if ($fileRecord['uid'] > 0) {
			$fileObject = t3lib_file_Factory::getInstance()->getFileObject($fileRecord['uid']);
			$processedFile = $fileObject->process(t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => 150, 'height' => 150));
			$previewImage = $processedFile->getPublicUrl(TRUE);

			$content = '';

			if ($previewImage) {
				$content .= '<img src="' . htmlspecialchars($previewImage) . '" style="float: left; margin-right: 10px; margin-bottom: 10px;" />';
			}

			$content .= '<strong>' . htmlspecialchars($fileObject->getName()) . '</strong> (' . htmlspecialchars(t3lib_div::formatSize($fileObject->getSize())) . ')<br />';
			$content .= t3lib_BEfunc::getProcessedValue($PA['table'], 'type', $fileObject->getType()) . ' (' . $fileObject->getMimeType() . ')<br />';
			$content .= 'Location: ' . htmlspecialchars($fileObject->getStorage()->getName()) . ' - ' . htmlspecialchars($fileObject->getIdentifier()) . '<br />';
			$content .= '<br />';
		} else {
			$content = '<h2>The File Info ... is great! But only with valid records.</h2>';
		}

		return $content;
	}
}
?>