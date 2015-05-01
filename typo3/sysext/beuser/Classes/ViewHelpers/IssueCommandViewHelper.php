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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Issue command ViewHelper, see TYPO3 Core Engine method issueCommand
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @internal
 */
class IssueCommandViewHelper extends AbstractViewHelper {

	/**
	 * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
	 *
	 * @param string $parameters Is a set of GET params to send to tce_db.php. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
	 * @param string $redirectUrl Redirect URL if any other that \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') is wished
	 *
	 * @return string URL to tce_db.php + parameters
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::issueCommand()
	 */
	public function render($parameters, $redirectUrl = '') {
		/** @var BackendUserAuthentication $beUser */
		$beUser = $GLOBALS['BE_USER'];
		$urlParameters = [
			'vC' => $beUser->veriCode(),
			'prErr' => 1,
			'uPT' => 1,
			'redirect' => $redirectUrl ?: GeneralUtility::getIndpEnv('REQUEST_URI')
		];
		return htmlspecialchars(BackendUtility::getModuleUrl('tce_db', $urlParameters) . $parameters . BackendUtility::getUrlToken('tceAction'));
	}

}
