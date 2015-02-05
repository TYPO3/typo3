<?php
namespace TYPO3\CMS\SysNote\ViewHelpers;

/*
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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * ViewHelper to create a link to delete a note
 *
 * @internal
 */
class DeleteLinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Create link to delete a note
	 *
	 * @param int $id uid of the note
	 * @return string link
	 */
	public function render($id) {
		$redirectUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
		$parameters = 'cmd[sys_note][' . $id . '][delete]=1';
		$url = BackendUtility::getModuleUrl('tce_db', array(), $GLOBALS['BACK_PATH']) . '&' . $parameters . '&redirect=' . ($redirectUrl == '' ? '\' + T3_THIS_LOCATION + \'' : rawurlencode($redirectUrl)) . BackendUtility::getUrlToken('tceAction');
		return htmlspecialchars($url);
	}

}