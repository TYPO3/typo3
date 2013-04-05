<?php
namespace TYPO3\CMS\Backend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Script Class for rendering the login form
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LoginController {

	const SIGNAL_RenderLoginForm = 'renderLoginForm';
	// Internal, GPvars:
	// GPvar: redirect_url; The URL to redirect to after login.
	/**
	 * @todo Define visibility
	 */
	public $redirect_url;

	// GPvar: Defines which interface to load (from interface selector)
	/**
	 * @todo Define visibility
	 */
	public $GPinterface;

	// GPvar: preset username
	/**
	 * @todo Define visibility
	 */
	public $u;

	// GPvar: preset password
	/**
	 * @todo Define visibility
	 */
	public $p;

	// GPvar: If "L" is "OUT", then any logged in used is logged out. If redirect_url is given, we redirect to it
	/**
	 * @todo Define visibility
	 */
	public $L;

	// Login-refresh boolean; The backend will call this script with this value set when the login is close to being expired and the form needs to be redrawn.
	/**
	 * @todo Define visibility
	 */
	public $loginRefresh;

	// Value of forms submit button for login.
	/**
	 * @todo Define visibility
	 */
	public $commandLI;

	// Internal, static:
	// Set to the redirect URL of the form (may be redirect_url or "backend.php")
	/**
	 * @todo Define visibility
	 */
	public $redirectToURL;

	// Internal, dynamic:
	// Content accumulation
	/**
	 * @todo Define visibility
	 */
	public $content;

	// A selector box for selecting value for "interface" may be rendered into this variable
	/**
	 * @todo Define visibility
	 */
	public $interfaceSelector;

	// A selector box for selecting value for "interface" may be rendered into this variable
	// this will have an onchange action which will redirect the user to the selected interface right away
	/**
	 * @todo Define visibility
	 */
	public $interfaceSelector_jump;

	// A hidden field, if the interface is not set.
	/**
	 * @todo Define visibility
	 */
	public $interfaceSelector_hidden;

	// Additional hidden fields to be placed at the login form
	/**
	 * @todo Define visibility
	 */
	public $addFields_hidden = '';

	// sets the level of security. *'normal' = clear-text. 'challenged' = hashed
	// password/username from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.
	/**
	 * @todo Define visibility
	 */
	public $loginSecurityLevel = 'superchallenged';

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * Initialize the login box. Will also react on a &L=OUT flag and exit.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// We need a PHP session session for most login levels
		session_start();
		$this->redirect_url = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('redirect_url'));
		$this->GPinterface = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('interface');
		// Grabbing preset username and password, for security reasons this feature only works if SSL is used
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			$this->u = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('u');
			$this->p = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('p');
		}
		// If "L" is "OUT", then any logged in is logged out. If redirect_url is given, we redirect to it
		$this->L = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('L');
		// Login
		$this->loginRefresh = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('loginRefresh');
		// Value of "Login" button. If set, the login button was pressed.
		$this->commandLI = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('commandLI');
		// Sets the level of security from conf vars
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) {
			$this->loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'];
		}
		// Try to get the preferred browser language
		$preferredBrowserLanguage = $GLOBALS['LANG']->csConvObj->getPreferredClientLanguage(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
		// If we found a $preferredBrowserLanguage and it is not the default language and no be_user is logged in
		// initialize $GLOBALS['LANG'] again with $preferredBrowserLanguage
		if ($preferredBrowserLanguage != 'default' && !$GLOBALS['BE_USER']->user['uid']) {
			$GLOBALS['LANG']->init($preferredBrowserLanguage);
		}
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_login.xlf');
		// Setting the redirect URL to "backend.php" if no alternative input is given
		$this->redirectToURL = $this->redirect_url ? $this->redirect_url : 'backend.php';
		// Do a logout if the command is set
		if ($this->L == 'OUT' && is_object($GLOBALS['BE_USER'])) {
			$GLOBALS['BE_USER']->logoff();
			if ($this->redirect_url) {
				\TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->redirect_url);
			}
			die;
		}
	}

	/**
	 * Main function - creating the login/logout form
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Initialize template object:
		$GLOBALS['TBE_TEMPLATE']->bodyTagAdditions = ' onload="startUp();"';
		$GLOBALS['TBE_TEMPLATE']->moduleTemplate = $GLOBALS['TBE_TEMPLATE']->getHtmlTemplate('templates/login.html');
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->loadExtJS();
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->loadPrototype();
		$GLOBALS['TBE_TEMPLATE']->getPageRenderer()->loadScriptaculous();
		// Set JavaScript for creating a MD5 hash of the password:
		$GLOBALS['TBE_TEMPLATE']->JScode .= $this->getJScode();
		// Checking, if we should make a redirect.
		// Might set JavaScript in the header to close window.
		$this->checkRedirect();
		// Initialize interface selectors:
		$this->makeInterfaceSelectorBox();
		// Creating form based on whether there is a login or not:
		if (!$GLOBALS['BE_USER']->user['uid']) {
			$GLOBALS['TBE_TEMPLATE']->form = $this->startForm();
			$loginForm = $this->makeLoginForm();
		} else {
			$GLOBALS['TBE_TEMPLATE']->form = '
				<form action="index.php" method="post" name="loginform">
				<input type="hidden" name="login_status" value="logout" />
				';
			$loginForm = $this->makeLogoutForm();
		}
		// Starting page:
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage('TYPO3 Login: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], FALSE);
		// Add login form:
		$this->content .= $this->wrapLoginForm($loginForm);
		$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/*****************************
	 *
	 * Various functions
	 *
	 ******************************/
	/**
	 * Creates the login form
	 * This is drawn when NO login exists.
	 *
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function makeLoginForm() {
		$content = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($GLOBALS['TBE_TEMPLATE']->moduleTemplate, '###LOGIN_FORM###');
		$markers = array(
			'VALUE_USERNAME' => htmlspecialchars($this->u),
			'VALUE_PASSWORD' => htmlspecialchars($this->p),
			'VALUE_SUBMIT' => $GLOBALS['LANG']->getLL('labels.submitLogin', TRUE)
		);
		// Show an error message if the login command was successful already, otherwise remove the subpart
		if (!$this->isLoginInProgress()) {
			$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($content, '###LOGIN_ERROR###', '');
		} else {
			$markers['ERROR_MESSAGE'] = $GLOBALS['LANG']->getLL('error.login', TRUE);
			$markers['ERROR_LOGIN_TITLE'] = $GLOBALS['LANG']->getLL('error.login.title', TRUE);
			$markers['ERROR_LOGIN_DESCRIPTION'] = $GLOBALS['LANG']->getLL('error.login.description', TRUE);
		}
		// Remove the interface selector markers if it's not available
		if (!($this->interfaceSelector && !$this->loginRefresh)) {
			$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($content, '###INTERFACE_SELECTOR###', '');
		} else {
			$markers['LABEL_INTERFACE'] = $GLOBALS['LANG']->getLL('labels.interface', TRUE);
			$markers['VALUE_INTERFACE'] = $this->interfaceSelector;
		}
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($content, $markers, '###|###');
	}

	/**
	 * Creates the logout form
	 * This is drawn if a user login already exists.
	 *
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function makeLogoutForm() {
		$content = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($GLOBALS['TBE_TEMPLATE']->moduleTemplate, '###LOGOUT_FORM###');
		$markers = array(
			'LABEL_USERNAME' => $GLOBALS['LANG']->getLL('labels.username', TRUE),
			'VALUE_USERNAME' => htmlspecialchars($GLOBALS['BE_USER']->user['username']),
			'VALUE_SUBMIT' => $GLOBALS['LANG']->getLL('labels.submitLogout', TRUE)
		);
		// Remove the interface selector markers if it's not available
		if (!$this->interfaceSelector_jump) {
			$content = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($content, '###INTERFACE_SELECTOR###', '');
		} else {
			$markers['LABEL_INTERFACE'] = $GLOBALS['LANG']->getLL('labels.interface', TRUE);
			$markers['VALUE_INTERFACE'] = $this->interfaceSelector_jump;
		}
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($content, $markers, '###|###');
	}

	/**
	 * Wrapping the login form table in another set of tables etc:
	 *
	 * @param string $content HTML content for the login form
	 * @return string The HTML for the page.
	 * @todo Define visibility
	 */
	public function wrapLoginForm($content) {
		$mainContent = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($GLOBALS['TBE_TEMPLATE']->moduleTemplate, '###PAGE###');
		if ($GLOBALS['TBE_STYLES']['logo_login']) {
			$logo = '<img src="' . htmlspecialchars(($GLOBALS['BACK_PATH'] . $GLOBALS['TBE_STYLES']['logo_login'])) . '" alt="" class="t3-login-logo" />';
		} else {
			$logo = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/typo3logo.gif', 'width="123" height="34"') . ' alt="" class="t3-login-logo" />';
		}
		/** @var $browserWarning \TYPO3\CMS\Core\Messaging\FlashMessage */
		$browserWarning = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('warning.incompatibleBrowser') . ' ' . $GLOBALS['LANG']->getLL('warning.incompatibleBrowserInternetExplorer'), $GLOBALS['LANG']->getLL('warning.incompatibleBrowserHeadline'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		$browserWarning = $browserWarning->render();
		$additionalCssClasses = array();
		if ($this->isLoginInProgress()) {
			$additionalCssClasses[] = 'error';
		}
		if ($this->loginRefresh) {
			$additionalCssClasses[] = 'refresh';
		}
		$markers = array(
			'LOGO' => $logo,
			'LOGINBOX_IMAGE' => $this->makeLoginBoxImage(),
			'FORM' => $content,
			'NEWS' => $this->makeLoginNews(),
			'COPYRIGHT' => \TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightShowVersion']),
			'CSS_CLASSES' => !empty($additionalCssClasses) ? 'class="' . implode(' ', $additionalCssClasses) . '"' : '',
			'CSS_OPENIDCLASS' => 't3-login-openid-' . (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('openid') ? 'enabled' : 'disabled'),
			// The labels will be replaced later on, thus the other parts above
			// can use these markers as well and it will be replaced
			'HEADLINE' => $GLOBALS['LANG']->getLL('headline', TRUE),
			'INFO_ABOUT' => $GLOBALS['LANG']->getLL('info.about', TRUE),
			'INFO_RELOAD' => $GLOBALS['LANG']->getLL('info.reset', TRUE),
			'INFO' => $GLOBALS['LANG']->getLL('info.cookies_and_js', TRUE),
			'WARNING_BROWSER_INCOMPATIBLE' => $browserWarning,
			'ERROR_JAVASCRIPT' => $GLOBALS['LANG']->getLL('error.javascript', TRUE),
			'ERROR_COOKIES' => $GLOBALS['LANG']->getLL('error.cookies', TRUE),
			'ERROR_COOKIES_IGNORE' => $GLOBALS['LANG']->getLL('error.cookies_ignore', TRUE),
			'ERROR_CAPSLOCK' => $GLOBALS['LANG']->getLL('error.capslock', TRUE),
			'ERROR_FURTHERHELP' => $GLOBALS['LANG']->getLL('error.furtherInformation', TRUE),
			'LABEL_DONATELINK' => $GLOBALS['LANG']->getLL('labels.donate', TRUE),
			'LABEL_USERNAME' => $GLOBALS['LANG']->getLL('labels.username', TRUE),
			'LABEL_OPENID' => $GLOBALS['LANG']->getLL('labels.openId', TRUE),
			'LABEL_PASSWORD' => $GLOBALS['LANG']->getLL('labels.password', TRUE),
			'LABEL_WHATISOPENID' => $GLOBALS['LANG']->getLL('labels.whatIsOpenId', TRUE),
			'LABEL_SWITCHOPENID' => $GLOBALS['LANG']->getLL('labels.switchToOpenId', TRUE),
			'LABEL_SWITCHDEFAULT' => $GLOBALS['LANG']->getLL('labels.switchToDefault', TRUE),
			'CLEAR' => $GLOBALS['LANG']->getLL('clear', TRUE),
			'LOGIN_PROCESS' => $GLOBALS['LANG']->getLL('login_process', TRUE),
			'SITELINK' => '<a href="/">###SITENAME###</a>',
			// Global variables will now be replaced (at last)
			'SITENAME' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])
		);
		$this->emitRenderLoginFormSignal($markers);
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($mainContent, $markers, '###|###');
	}

	/**
	 * Checking, if we should perform some sort of redirection OR closing of windows.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function checkRedirect() {
		// Do redirect:
		// If a user is logged in AND a) if either the login is just done (isLoginInProgress) or b) a loginRefresh is done or c) the interface-selector is NOT enabled (If it is on the other hand, it should not just load an interface, because people has to choose then...)
		if ($GLOBALS['BE_USER']->user['uid'] && ($this->isLoginInProgress() || $this->loginRefresh || !$this->interfaceSelector)) {
			// If no cookie has been set previously we tell people that this is a problem. This assumes that a cookie-setting script (like this one) has been hit at least once prior to this instance.
			if (!$_COOKIE[\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName()]) {
				if ($this->commandLI == 'setCookie') {
					// we tried it a second time but still no cookie
					// 26/4 2005: This does not work anymore, because the saving of challenge values in $_SESSION means the system will act as if the password was wrong.
					throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3.<br /><br />Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
				} else {
					// try it once again - that might be needed for auto login
					$this->redirectToURL = 'index.php?commandLI=setCookie';
				}
			}
			if ($redirectToURL = (string) $GLOBALS['BE_USER']->getTSConfigVal('auth.BE.redirectToURL')) {
				$this->redirectToURL = $redirectToURL;
				$this->GPinterface = '';
			}
			// store interface
			$GLOBALS['BE_USER']->uc['interfaceSetup'] = $this->GPinterface;
			$GLOBALS['BE_USER']->writeUC();
			// Based on specific setting of interface we set the redirect script:
			switch ($this->GPinterface) {
			case 'backend':

			case 'backend_old':
				$this->redirectToURL = 'backend.php';
				break;
			case 'frontend':
				$this->redirectToURL = '../';
				break;
			}
			/** @var $formProtection \TYPO3\CMS\Core\FormProtection\BackendFormProtection */
			$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
			// If there is a redirect URL AND if loginRefresh is not set...
			if (!$this->loginRefresh) {
				$formProtection->storeSessionTokenInRegistry();
				\TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->redirectToURL);
			} else {
				$formProtection->setSessionTokenFromRegistry();
				$formProtection->persistSessionToken();
				$GLOBALS['TBE_TEMPLATE']->JScode .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
					if (parent.opener && (parent.opener.busy || parent.opener.TYPO3.loginRefresh)) {
						if (parent.opener.TYPO3.loginRefresh) {
							parent.opener.TYPO3.loginRefresh.startTimer();
						} else {
							parent.opener.busy.loginRefreshed();
						}
						parent.close();
					}
				');
			}
		} elseif (!$GLOBALS['BE_USER']->user['uid'] && $this->isLoginInProgress()) {
			// Wrong password, wait for 5 seconds
			sleep(5);
		}
	}

	/**
	 * Making interface selector:
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function makeInterfaceSelectorBox() {
		// Reset variables:
		$this->interfaceSelector = '';
		$this->interfaceSelector_hidden = '';
		$this->interfaceSelector_jump = '';
		// If interfaces are defined AND no input redirect URL in GET vars:
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] && ($this->isLoginInProgress() || !$this->redirect_url)) {
			$parts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']);
			// Only if more than one interface is defined will we show the selector:
			if (count($parts) > 1) {
				// Initialize:
				$labels = array();
				$labels['backend'] = $GLOBALS['LANG']->getLL('interface.backend');
				$labels['backend_old'] = $GLOBALS['LANG']->getLL('interface.backend_old');
				$labels['frontend'] = $GLOBALS['LANG']->getLL('interface.frontend');
				$jumpScript = array();
				$jumpScript['backend'] = 'backend.php';
				$jumpScript['backend_old'] = 'backend.php';
				$jumpScript['frontend'] = '../';
				// Traverse the interface keys:
				foreach ($parts as $valueStr) {
					$this->interfaceSelector .= '
							<option value="' . htmlspecialchars($valueStr) . '"' . (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('interface') == htmlspecialchars($valueStr) ? ' selected="selected"' : '') . '>' . htmlspecialchars($labels[$valueStr]) . '</option>';
					$this->interfaceSelector_jump .= '
							<option value="' . htmlspecialchars($jumpScript[$valueStr]) . '">' . htmlspecialchars($labels[$valueStr]) . '</option>';
				}
				$this->interfaceSelector = '
						<select id="t3-interfaceselector" name="interface" class="c-interfaceselector" tabindex="3">' . $this->interfaceSelector . '
						</select>';
				$this->interfaceSelector_jump = '
						<select id="t3-interfaceselector" name="interface" class="c-interfaceselector" tabindex="3" onchange="window.location.href=this.options[this.selectedIndex].value;">' . $this->interfaceSelector_jump . '
						</select>';
			} elseif (!$this->redirect_url) {
				// If there is only ONE interface value set and no redirect_url is present:
				$this->interfaceSelector_hidden = '<input type="hidden" name="interface" value="' . trim($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']) . '" />';
			}
		}
	}

	/**
	 * COPYRIGHT notice
	 *
	 * Warning:
	 * DO NOT prevent this notice from being shown in ANY WAY.
	 * According to the GPL license an interactive application must show such a notice on start-up ('If the program is interactive, make it output a short notice... ' - see GPL.txt)
	 * Therefore preventing this notice from being properly shown is a violation of the license, regardless of whether you remove it or use a stylesheet to obstruct the display.
	 *
	 * @return string Text/Image (HTML) for copyright notice.
	 *
	 * @todo Define visibility
	 * @deprecated since TYPO3 6.0, will be removed in TYPO3 6.2
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice()
	 */
	public function makeCopyrightNotice() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return \TYPO3\CMS\Backend\Utility\BackendUtility::TYPO3_copyRightNotice();
	}

	/**
	 * Returns the login box image, whether the default or an image from the rotation folder.
	 *
	 * @return string HTML image tag.
	 * @todo Define visibility
	 */
	public function makeLoginBoxImage() {
		$loginboxImage = '';
		// Look for rotation image folder:
		if ($GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder']) {
			$absPath = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath(PATH_typo3 . $GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder']);
			// Get rotation folder:
			$dir = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($absPath);
			if ($dir && @is_dir($dir)) {
				// Get files for rotation into array:
				$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($dir, 'png,jpg,gif');
				// Pick random file:
				$randImg = array_rand($files, 1);
				// Get size of random file:
				$imgSize = @getimagesize(($dir . $files[$randImg]));
				$imgAuthor = is_array($GLOBALS['TBE_STYLES']['loginBoxImage_author']) && $GLOBALS['TBE_STYLES']['loginBoxImage_author'][$files[$randImg]] ? htmlspecialchars($GLOBALS['TBE_STYLES']['loginBoxImage_author'][$files[$randImg]]) : '';
				// Create image tag:
				if (is_array($imgSize)) {
					$loginboxImage = '<img src="' . htmlspecialchars(($GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder'] . $files[$randImg])) . '" ' . $imgSize[3] . ' id="loginbox-image" alt="' . $imgAuthor . '" title="' . $imgAuthor . '" />';
				}
			}
		} else {
			// If no rotation folder configured, print default image:
			// Development version
			if (strstr(TYPO3_version, '-dev')) {
				$loginImage = 'loginbox_image_dev.png';
				$imagecopy = 'You are running a development version of TYPO3 ' . TYPO3_branch;
			} else {
				$loginImage = 'loginbox_image.jpg';
				$imagecopy = 'Photo by J.C. Franca (www.digitalphoto.com.br)';
			}
			$loginboxImage = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], ('gfx/' . $loginImage), 'width="200" height="133"') . ' id="loginbox-image" alt="' . $imagecopy . '" title="' . $imagecopy . '" />';
		}
		// Return image tag:
		return $loginboxImage;
	}

	/**
	 * Make login news - renders the HTML content for a list of news shown under
	 * the login form. News data is added through sys_news records
	 *
	 * @return string HTML content
	 * @credits Idea by Jan-Hendrik Heuing
	 * @todo Define visibility
	 */
	public function makeLoginNews() {
		$newsContent = '';
		$systemNews = $this->getSystemNews();
		// Traverse news array IF there are records in it:
		if (is_array($systemNews) && count($systemNews) && !\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('loginRefresh')) {
			/** @var $htmlParser \TYPO3\CMS\Core\Html\RteHtmlParser */
			$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\RteHtmlParser');
			// Get the main news template, and replace the subpart after looped through
			$newsContent = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($GLOBALS['TBE_TEMPLATE']->moduleTemplate, '###LOGIN_NEWS###');
			$newsItemTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($newsContent, '###NEWS_ITEM###');
			$newsItem = '';
			$count = 1;
			foreach ($systemNews as $newsItemData) {
				$additionalClass = '';
				if ($count == 1) {
					$additionalClass = ' first-item';
				} elseif ($count == count($systemNews)) {
					$additionalClass = ' last-item';
				}
				$newsItemContent = $htmlParser->TS_transform_rte($htmlParser->TS_links_rte($newsItemData['content']));
				$newsItemMarker = array(
					'###HEADER###' => htmlspecialchars($newsItemData['header']),
					'###DATE###' => htmlspecialchars($newsItemData['date']),
					'###CONTENT###' => $newsItemContent,
					'###CLASS###' => $additionalClass
				);
				$count++;
				$newsItem .= \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($newsItemTemplate, $newsItemMarker);
			}
			$title = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['loginNewsTitle'] : $GLOBALS['LANG']->getLL('newsheadline');
			$newsContent = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarker($newsContent, '###NEWS_HEADLINE###', htmlspecialchars($title));
			$newsContent = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($newsContent, '###NEWS_ITEM###', $newsItem);
		}
		return $newsContent;
	}

	/**
	 * Gets news from sys_news and converts them into a format suitable for
	 * showing them at the login screen.
	 *
	 * @return array An array of login news.
	 */
	protected function getSystemNews() {
		$systemNewsTable = 'sys_news';
		$systemNews = array();
		$systemNewsRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('title, content, crdate', $systemNewsTable, '1=1' . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($systemNewsTable) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($systemNewsTable), '', 'crdate DESC');
		foreach ($systemNewsRecords as $systemNewsRecord) {
			$systemNews[] = array(
				'date' => date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $systemNewsRecord['crdate']),
				'header' => $systemNewsRecord['title'],
				'content' => $systemNewsRecord['content']
			);
		}
		return $systemNews;
	}

	/**
	 * Returns the form tag
	 *
	 * @return string Opening form tag string
	 * @todo Define visibility
	 */
	public function startForm() {
		$output = '';
		// The form defaults to 'no login'. This prevents plain
		// text logins to the Backend. The 'sv' extension changes the form to
		// use superchallenged method and rsaauth extension makes rsa authetication.
		$form = '<form action="index.php" method="post" name="loginform" ' . 'onsubmit="alert(\'No authentication methods available. Please, ' . 'contact your TYPO3 administrator.\');return false">';
		// Call hooks. If they do not return anything, we fail to login
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'] as $function) {
				$params = array();
				$formCode = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($function, $params, $this);
				if ($formCode) {
					$form = $formCode;
					break;
				}
			}
		}
		$output .= $form . '<input type="hidden" name="login_status" value="login" />' . '<input type="hidden" name="userident" value="" />' . '<input type="hidden" name="redirect_url" value="' . htmlspecialchars($this->redirectToURL) . '" />' . '<input type="hidden" name="loginRefresh" value="' . htmlspecialchars($this->loginRefresh) . '" />' . $this->interfaceSelector_hidden . $this->addFields_hidden;
		return $output;
	}

	/**
	 * Creates JavaScript for the login form
	 *
	 * @return string JavaScript code
	 * @todo Define visibility
	 */
	public function getJScode() {
		$JSCode = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'] as $function) {
				$params = array();
				$JSCode = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($function, $params, $this);
				if ($JSCode) {
					break;
				}
			}
		}
		$JSCode .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
			function startUp() {
					// If the login screen is shown in the login_frameset window for re-login, then try to get the username of the current/former login from opening windows main frame:
				try {
					if (parent.opener && parent.opener.TS && parent.opener.TS.username && document.loginform && document.loginform.username) {
						document.loginform.username.value = parent.opener.TS.username;
					}
				}
				catch(error) {
					//continue
				}

					// Wait a few millisecons before calling checkFocus(). This might be necessary because some browsers need some time to auto-fill in the form fields
				window.setTimeout("checkFocus()", 50);
			}

				// This moves focus to the right input field:
			function checkFocus() {
					// If for some reason there already is a username in the username form field, move focus to the password field:
				if (document.loginform.username && document.loginform.username.value == "") {
					document.loginform.username.focus();
				} else if (document.loginform.p_field && document.loginform.p_field.type!="hidden") {
					document.loginform.p_field.focus();
				}
			}

				// This function shows a warning, if user has capslock enabled
				// parameter showWarning: shows warning if TRUE and capslock active, otherwise only hides warning, if capslock gets inactive
			function checkCapslock(e, showWarning) {
				if (!isCapslock(e)) {
					document.getElementById(\'t3-capslock\').style.display = \'none\';
				} else if (showWarning) {
					document.getElementById(\'t3-capslock\').style.display = \'block\';
				}
			}

				// Checks weather capslock is enabled (returns TRUE if enabled, false otherwise)
				// thanks to http://24ways.org/2007/capturing-caps-lock

			function isCapslock(e) {
				var ev = e ? e : window.event;
				if (!ev) {
					return;
				}
				var targ = ev.target ? ev.target : ev.srcElement;
				// get key pressed
				var which = -1;
				if (ev.which) {
					which = ev.which;
				} else if (ev.keyCode) {
					which = ev.keyCode;
				}
				// get shift status
				var shift_status = false;
				if (ev.shiftKey) {
					shift_status = ev.shiftKey;
				} else if (ev.modifiers) {
					shift_status = !!(ev.modifiers & 4);
				}
				return (((which >= 65 && which <= 90) && !shift_status) ||
					((which >= 97 && which <= 122) && shift_status));
			}

				// prevent opening the login form in the backend frameset
			if (top.location.href != self.location.href) {
				top.location.href = self.location.href;
			}

			');
		return $JSCode;
	}

	/**
	 * Checks if login credentials are currently submitted
	 *
	 * @return boolean
	 */
	protected function isLoginInProgress() {
		$username = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('username');
		return !(empty($username) && empty($this->commandLI));
	}

	/**
	 * Emits the render login form signal
	 *
	 * @param array $markers Array with markers for the login form
	 * @return void
	 */
	protected function emitRenderLoginFormSignal(array &$markers) {
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Backend\\Controller\\LoginController', self::SIGNAL_RenderLoginForm, array($this, $markers));
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		if (!isset($this->signalSlotDispatcher)) {
			$this->signalSlotDispatcher = $this->getObjectManager()->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
		}
		return $this->signalSlotDispatcher;
	}

	/**
	 * Get the ObjectManager
	 *
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

}


?>