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
 * Displays 'SwitchUser' link with sprite icon to change current backend user to target (non-admin) backendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 */
class SwitchUserViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render link with sprite icon to change current backend user to target
	 *
	 * @param \TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser Target backendUser to switch active session to
	 * @param boolean $emulate Return to current session or logout after target session termination?
	 * @return string
	 */
	public function render(\TYPO3\CMS\Beuser\Domain\Model\BackendUser $backendUser, $emulate = FALSE) {
		if ($backendUser->getUid() == $GLOBALS['BE_USER']->user['uid']) {
			return '';
		}
		$title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(($emulate ? 'switchBackMode' : 'changeToMode'), 'beuser');
		return '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('SwitchUser' => $backendUser->getUid(), 'switchBackUser' => $emulate))) . '" target="_top" title="' . htmlspecialchars($title) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-system-backend-user-' . ($emulate ? 'emulate' : 'switch'))) . '</a>';
	}

}
