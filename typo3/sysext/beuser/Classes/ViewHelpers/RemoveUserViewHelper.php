<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Krzysztof Adamczyk <k.adamczyk@macopedia.pl>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
