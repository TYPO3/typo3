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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Displays 'SwitchUser' link with sprite icon to change current backend user to target (non-admin) backendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @internal
 */
class SwitchUserViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render link with sprite icon to change current backend user to target
	 *
	 * @param BackendUser $backendUser Target backendUser to switch active session to
	 * @return string
	 */
	public function render(BackendUser $backendUser) {
		if ($backendUser->getUid() == $GLOBALS['BE_USER']->user['uid'] || !$backendUser->isActive()) {
			return '<span class="btn disabled">' . IconUtility::getSpriteIcon('empty-empty') . '</span>';
		}
		$title = LocalizationUtility::translate('switchBackMode', 'beuser');
		return '<a class="btn" href="' .
			htmlspecialchars(GeneralUtility::linkThisScript(array('SwitchUser' => $backendUser->getUid()))) .
			'" target="_top" title="' . htmlspecialchars($title) . '">' .
			IconUtility::getSpriteIcon(('actions-system-backend-user-switch')) . '</a>';
	}

}
