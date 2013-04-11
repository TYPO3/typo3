<?php
namespace TYPO3\CMS\Version\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 TYPO3 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Hook for checking if the preview mode is activated
 * preview mode = show a page of a workspace without having to log in
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class PreviewHook implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * the GET parameter to be used
	 *
	 * @var string
	 */
	protected $previewKey = 'ADMCMD_prev';

	/**
	 * instance of the tslib_fe object
	 *
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $tsfeObj;

	/**
	 * preview configuration
	 *
	 * @var array
	 */
	protected $previewConfiguration = FALSE;

	/**
	 * hook to check if the preview is activated
	 * right now, this hook is called at the end of "$TSFE->connectToDB"
	 *
	 * @param $params (not needed right now)
	 * @param $pObj the instance of the tslib_fe object
	 * @return void
	 */
	public function checkForPreview($params, &$pObj) {
		$this->tsfeObj = $pObj;
		$this->previewConfiguration = $this->getPreviewConfiguration();
		if (is_array($this->previewConfiguration)) {
			// In case of a keyword-authenticated preview,
			// re-initialize the TSFE object:
			// because the GET variables are taken from the preview
			// configuration
			$GLOBALS['TSFE'] = ($this->tsfeObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController', $GLOBALS['TYPO3_CONF_VARS'], \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('no_cache'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cHash'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('jumpurl'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('MP'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RDCT')));
			// Configuration after initialization of TSFE object.
			// Basically this unsets the BE cookie if any and forces
			// the BE user set according to the preview configuration.
			// @previouslyknownas TSFE->ADMCMD_preview_postInit
			// Clear cookies:
			unset($_COOKIE['be_typo_user']);
		}
	}

	/**
	 * hook after the regular BE user has been initialized
	 * if there is no BE user login, but a preview configuration
	 * the BE user of the preview configuration gets initialized
	 *
	 * @param $params holding the BE_USER object
	 * @param $pObj the instance of the tslib_fe object
	 * @return void
	 */
	public function initializePreviewUser(&$params, &$pObj) {
		if ((is_null($params['BE_USER']) || $params['BE_USER'] === FALSE) && $this->previewConfiguration !== FALSE && $this->previewConfiguration['BEUSER_uid'] > 0) {
			// New backend user object
			$BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication');
			$BE_USER->userTS_dontGetCached = 1;
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($this->previewConfiguration['BEUSER_uid']);
			$BE_USER->unpack_uc('');
			if ($BE_USER->user['uid']) {
				$BE_USER->fetchGroupData();
				$pObj->beUserLogin = 1;
			} else {
				$BE_USER = NULL;
				$pObj->beUserLogin = 0;
				$_SESSION['TYPO3-TT-start'] = FALSE;
			}
			$params['BE_USER'] = $BE_USER;
		}
		// if there is a valid BE user, and the full workspace should be
		// previewed, the workspacePreview option shouldbe set
		$workspaceUid = $this->previewConfiguration['fullWorkspace'];
		if ($pObj->beUserLogin && is_object($params['BE_USER']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($workspaceUid)) {
			if ($workspaceUid == 0 || $workspaceUid >= -1 && $params['BE_USER']->checkWorkspace($workspaceUid)) {
				// Check Access to workspace. Live (0) is OK to preview for all.
				$pObj->workspacePreview = intval($workspaceUid);
			} else {
				// No preview, will default to "Live" at the moment
				$pObj->workspacePreview = -99;
			}
		}
	}

	/**
	 * Looking for a ADMCMD_prev code, looks it up if found and returns configuration data.
	 * Background: From the backend a request to the frontend to show a page, possibly with workspace preview can be "recorded" and associated with a keyword. When the frontend is requested with this keyword the associated request parameters are restored from the database AND the backend user is loaded - only for that request.
	 * The main point is that a special URL valid for a limited time, eg. http://localhost/typo3site/index.php?ADMCMD_prev=035d9bf938bd23cb657735f68a8cedbf will open up for a preview that doesn't require login. Thus it's useful for sending in an email to someone without backend account.
	 * This can also be used to generate previews of hidden pages, start/endtimes, usergroups and those other settings from the Admin Panel - just not implemented yet.
	 *
	 * @return 	array		Preview configuration array from sys_preview record.
	 */
	public function getPreviewConfiguration() {
		$inputCode = $this->getPreviewInputCode();
		// If inputcode is available, look up the settings
		if ($inputCode) {
			// "log out"
			if ($inputCode == 'LOGOUT') {
				setcookie($this->previewKey, '', 0, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
				if ($this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']) {
					$templateFile = PATH_site . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate'];
					if (@is_file($templateFile)) {
						$message = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_site . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']);
					} else {
						$message = '<strong>ERROR!</strong><br>Template File "' . $this->tsfeObj->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate'] . '" configured with $TYPO3_CONF_VARS["FE"]["workspacePreviewLogoutTemplate"] not found. Please contact webmaster about this problem.';
					}
				} else {
					$message = 'You logged out from Workspace preview mode. Click this link to <a href="%1$s">go back to the website</a>';
				}
				$returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('returnUrl'));
				die(sprintf($message, htmlspecialchars(preg_replace('/\\&?' . $this->previewKey . '=[[:alnum:]]+/', '', $returnUrl))));
			}
			// Look for keyword configuration record:
			$previewData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_preview', 'keyword=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($inputCode, 'sys_preview') . ' AND endtime>' . $GLOBALS['EXEC_TIME']);
			// Get: Backend login status, Frontend login status
			// - Make sure to remove fe/be cookies (temporarily);
			// BE already done in ADMCMD_preview_postInit()
			if (is_array($previewData)) {
				if (!count(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST())) {
					// Unserialize configuration:
					$previewConfig = unserialize($previewData['config']);
					// For full workspace preview we only ADD a get variable
					// to set the preview of the workspace - so all other Get
					// vars are accepted. Hope this is not a security problem.
					// Still posting is not allowed and even if a backend user
					// get initialized it shouldn't lead to situations where
					// users can use those credentials.
					if ($previewConfig['fullWorkspace']) {
						// Set the workspace preview value:
						\TYPO3\CMS\Core\Utility\GeneralUtility::_GETset($previewConfig['fullWorkspace'], 'ADMCMD_previewWS');
						// If ADMCMD_prev is set the $inputCode value cannot come
						// from a cookie and we set that cookie here. Next time it will
						// be found from the cookie if ADMCMD_prev is not set again...
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->previewKey)) {
							// Lifetime is 1 hour, does it matter much?
							// Requires the user to click the link from their email again if it expires.
							SetCookie($this->previewKey, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->previewKey), 0, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
						}
						return $previewConfig;
					} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?' . $this->previewKey . '=' . $inputCode === \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) {
						// Set GET variables
						$GET_VARS = '';
						parse_str($previewConfig['getVars'], $GET_VARS);
						\TYPO3\CMS\Core\Utility\GeneralUtility::_GETset($GET_VARS);
						// Return preview keyword configuration
						return $previewConfig;
					} else {
						// This check is to prevent people from setting additional
						// GET vars via realurl or other URL path based ways of passing parameters.
						throw new \Exception(htmlspecialchars('Request URL did not match "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?' . $this->previewKey . '=' . $inputCode . '"', 1294585190));
					}
				} else {
					throw new \Exception('POST requests are incompatible with keyword preview.', 1294585191);
				}
			} else {
				throw new \Exception('ADMCMD command could not be executed! (No keyword configuration found)', 1294585192);
			}
		}
		return FALSE;
	}

	/**
	 * returns the input code value from the admin command variable
	 *
	 * @return input code
	 */
	protected function getPreviewInputCode() {
		$inputCode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP($this->previewKey);
		// If no inputcode and a cookie is set, load input code from cookie:
		if (!$inputCode && $_COOKIE[$this->previewKey]) {
			$inputCode = $_COOKIE[$this->previewKey];
		}
		return $inputCode;
	}

	/**
	 * Set preview keyword, eg:
	 * $previewUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').'index.php?ADMCMD_prev='.$this->compilePreviewKeyword('id='.$pageId.'&L='.$language.'&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS='.$this->workspace, $GLOBALS['BE_USER']->user['uid'], 120);
	 *
	 * todo for sys_preview:
	 * - Add a comment which can be shown to previewer in frontend in some way (plus maybe ability to write back, take other action?)
	 * - Add possibility for the preview keyword to work in the backend as well: So it becomes a quick way to a certain action of sorts?
	 *
	 * @param 	string		Get variables to preview, eg. 'id=1150&L=0&ADMCMD_view=1&ADMCMD_editIcons=1&ADMCMD_previewWS=8'
	 * @param 	string		32 byte MD5 hash keyword for the URL: "?ADMCMD_prev=[keyword]
	 * @param 	integer		Time-To-Live for keyword
	 * @param 	integer		Which workspace to preview. Workspace UID, -1 or >0. If set, the getVars is ignored in the frontend, so that string can be empty
	 * @return 	string		Returns keyword to use in URL for ADMCMD_prev=
	 */
	public function compilePreviewKeyword($getVarsStr, $backendUserUid, $ttl = 172800, $fullWorkspace = NULL) {
		$fieldData = array(
			'keyword' => md5(uniqid(microtime())),
			'tstamp' => $GLOBALS['EXEC_TIME'],
			'endtime' => $GLOBALS['EXEC_TIME'] + $ttl,
			'config' => serialize(array(
				'fullWorkspace' => $fullWorkspace,
				'getVars' => $getVarsStr,
				'BEUSER_uid' => $backendUserUid
			))
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_preview', $fieldData);
		return $fieldData['keyword'];
	}

	/**
	 * easy function to just return the number of hours
	 * a preview link is valid, based on the TSconfig value "options.workspaces.previewLinkTTLHours"
	 * by default, it's 48hs
	 *
	 * @return integer	the hours as a number
	 */
	public function getPreviewLinkLifetime() {
		$ttlHours = intval($GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours'));
		return $ttlHours ? $ttlHours : 24 * 2;
	}

}


?>