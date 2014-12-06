<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
/**
 * Displays 'Delete user' link with sprite icon to remove user
 *
 * @internal
 */
class RemoveUserViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render link with sprite icon to remove user
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser Target backendUser to switch active session to
	 * @return string
	 */
	public function render(BackendUser $backendUser) {
		if ($backendUser->getUid() == $GLOBALS['BE_USER']->user['uid']) {
			return '<span class="btn disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</span>';
		}

		$redirectUrl = GeneralUtility::getIndpEnv('REQUEST_URI');
		$parameters = 'cmd[be_users][' . $backendUser->getUid() . '][delete]=1';
		$url = $GLOBALS['BACK_PATH'] . 'tce_db.php?&' . $parameters . '&redirect=' .
			($redirectUrl == '' ? '\' + T3_THIS_LOCATION + \'' : rawurlencode($redirectUrl)) . '&vC=' .
			rawurlencode($GLOBALS['BE_USER']->veriCode()) . BackendUtility::getUrlToken('tceAction') . '&prErr=1&uPT=1';
		return '<a class="btn" href="' . $url . '"  onclick="return confirm(' .
			GeneralUtility::quoteJSvalue(LocalizationUtility::translate('confirm', 'beuser', array($backendUser->getUserName()))) .
			')">' . IconUtility::getSpriteIcon(('actions-edit-delete')) . '</a>';
	}

}
