<?php
namespace TYPO3\CMS\Taskcenter\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Icon ViewHelper
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class IconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * @param string $icon
	 * @param string $title
	 */
	public function render($icon, $title) {
		$taskIcon = '';
		if (strpos($icon, '<img ') === FALSE) {
			$absoluteIconPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFilename($icon);
			// If the file indeed exists, assemble relative path to it
			if (file_exists($absoluteIconPath)) {
				$taskIcon = $GLOBALS['BACK_PATH'] . '../' . str_replace(PATH_site, '', $absoluteIconPath);
				$taskIcon = '<img src="' . $taskIcon . '" title="' . $title . '" alt="' . $title . '" />';
			}
			if (@is_file($taskIcon)) {
				$taskIcon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], $taskIcon, 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />';
			}
		} else {
			$taskIcon = $icon;
		}
		return $taskIcon;
	}

}

?>