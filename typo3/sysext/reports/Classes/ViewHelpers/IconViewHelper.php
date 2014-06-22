<?php
namespace TYPO3\CMS\Reports\ViewHelpers;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Render the icon of a report
 *
 */
class IconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders the icon
	 *
	 * @param string $icon Icon to be used
	 * @param string $title Optional title
	 * @return string Content rendered image
	 */
	public function render($icon, $title = '') {
		if (!empty($icon)) {
			$absIconPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFilename($icon);
			if (file_exists($absIconPath)) {
				$icon = $GLOBALS['BACK_PATH'] . '../' . str_replace(PATH_site, '', $absIconPath);
			}
		} else {
			$icon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('reports') . 'ext_icon.png';
		}
		$content = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], $icon, 'width="16" height="16"') . ' title="' . htmlspecialchars($title) . '" alt="' . htmlspecialchars($title) . '" />';
		return $content;
	}

}
