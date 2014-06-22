<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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
 * Displays 'Delete user' link with sprite icon to remove user
 */
class RemoveUserViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render link with sprite icon to remove user
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser Target backendUser to switch active session to
	 * @return string
	 */
	public function render(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser) {
		if ($backendUser->getUid() == $GLOBALS['BE_USER']->user['uid']) {
			return '';
		}

		$redirectUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
		$parameters = 'cmd[be_users][' . $backendUser->getUid() . '][delete]=1';
		$url = $GLOBALS['BACK_PATH'] . 'tce_db.php?&' . $parameters . '&redirect=' . ($redirectUrl == '' ? '\' + T3_THIS_LOCATION + \'' : rawurlencode($redirectUrl)) . '&vC=' . rawurlencode($GLOBALS['BE_USER']->veriCode()) . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '&prErr=1&uPT=1';
		return '<a href="' . $url . '"  onclick="return confirm(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue(\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('confirm', 'beuser', array($backendUser->getUserName()))) . ')">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-edit-delete')) . '</a>';
	}

}
