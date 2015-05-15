<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Script Class for rendering the login form
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class LoginController {
	/**
	 * The URL to redirect to after login.
	 *
	 * @var string
	 */
	public $redirect_url;

	/**
	 * Defines which interface to load (from interface selector)
	 *
	 * @var string
	 */
	public $GPinterface;

	/**
	 * preset username
	 *
	 * @var string
	 */
	public $u;

	/**
	 * preset password
	 *
	 * @var string
	 */
	public $p;

	/**
	 * OpenID URL submitted by form
	 *
	 * @var string
	 */
	protected $openIdUrl;

	/**
	 * If "L" is "OUT", then any logged in used is logged out. If redirect_url is given, we redirect to it
	 *
	 * @var string
	 */
	public $L;

	/**
	 * Login-refresh boolean; The backend will call this script
	 * with this value set when the login is close to being expired
	 * and the form needs to be redrawn.
	 *
	 * @var bool
	 */
	public $loginRefresh;

	/**
	 * Value of forms submit button for login.
	 *
	 * @var string
	 */
	public $commandLI;

	/**
	 * Set to the redirect URL of the form (may be redirect_url or "backend.php")
	 *
	 * @var string
	 */
	public $redirectToURL;

	/**
	 * Content accumulation
	 *
	 * @var string
	 */
	public $content;

	/**
	 * A selector box for selecting value for "interface" may be rendered into this variable
	 *
	 * @var string
	 */
	public $interfaceSelector;

	/**
	 * A selector box for selecting value for "interface" may be rendered into this variable
	 * this will have an onchange action which will redirect the user to the selected interface right away
	 *
	 * @var string
	 */
	public $interfaceSelector_jump;

	/**
	 * A hidden field, if the interface is not set.
	 *
	 * @var string
	 */
	public $interfaceSelector_hidden;

	/**
	 * Additional hidden fields to be placed at the login form
	 *
	 * @var string
	 */
	public $addFields_hidden = '';

	/**
	 * Sets the level of security
	 *
	 * 'normal' = clear-text
	 * password/username from form in $formfield_uident.
	 *
	 * @var string
	 */
	public $loginSecurityLevel = 'normal';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize the login box. Will also react on a &L=OUT flag and exit.
	 *
	 * @return void
	 */
	public function init() {
		// We need a PHP session session for most login levels
		session_start();
		$this->redirect_url = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect_url'));
		$this->GPinterface = GeneralUtility::_GP('interface');
		// Grabbing preset username and password, for security reasons this feature only works if SSL is used
		if (GeneralUtility::getIndpEnv('TYPO3_SSL')) {
			$this->u = GeneralUtility::_GP('u');
			$this->p = GeneralUtility::_GP('p');
			$this->openIdUrl = GeneralUtility::_GP('openid_url');
		}
		// If "L" is "OUT", then any logged in is logged out. If redirect_url is given, we redirect to it
		$this->L = GeneralUtility::_GP('L');
		// Login
		$this->loginRefresh = GeneralUtility::_GP('loginRefresh');
		// Value of "Login" button. If set, the login button was pressed.
		$this->commandLI = GeneralUtility::_GP('commandLI');
		// Sets the level of security from conf vars
		$this->loginSecurityLevel = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']) ?: 'normal';
		// Try to get the preferred browser language
		$preferredBrowserLanguage = $this->getLanguageService()->csConvObj->getPreferredClientLanguage(GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
		// If we found a $preferredBrowserLanguage and it is not the default language and no be_user is logged in
		// initialize $this->getLanguageService() again with $preferredBrowserLanguage
		if ($preferredBrowserLanguage !== 'default' && empty($this->getBackendUserAuthentication()->user['uid'])) {
			$this->getLanguageService()->init($preferredBrowserLanguage);
		}
		// Setting the redirect URL to "backend.php" if no alternative input is given
		$this->redirectToURL = $this->redirect_url ?: 'backend.php';
		// Do a logout if the command is set
		if ($this->L == 'OUT' && is_object($this->getBackendUserAuthentication())) {
			$this->getBackendUserAuthentication()->logoff();
			HttpUtility::redirect($this->redirect_url);
		}
	}

	/**
	 * Main function - creating the login/logout form
	 *
	 * @return void
	 */
	public function main() {
		// Initialize template object:
		$view = $this->getFluidTemplateObject('EXT:backend/Resources/Private/Templates/Login.html');

		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $this->getDocumentTemplate()->getPageRenderer();
		$pageRenderer->loadJquery();

		// support placeholders for IE9 and lower
		$clientInfo = GeneralUtility::clientInfo();
		if ($clientInfo['BROWSER'] == 'msie' && $clientInfo['VERSION'] <= 9) {
			$pageRenderer->addJsLibrary('placeholders', 'sysext/core/Resources/Public/JavaScript/Contrib/placeholders.jquery.min.js');
		}

		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Login');
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook'] as $function) {
				$params = array();
				$javaScriptCode = GeneralUtility::callUserFunction($function, $params, $this);
				if ($javaScriptCode) {
					$this->getDocumentTemplate()->JScode .= $javaScriptCode;
					break;
				}
			}
		}

		// Checking, if we should make a redirect.
		// Might set JavaScript in the header to close window.
		$this->checkRedirect();

		// Extension Configuration
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['backend']);

		// Background Image
		if (!empty($extConf['loginBackgroundImage'])) {
			$backgroundImage = $this->getUriForFileName($extConf['loginBackgroundImage']);
			$this->getDocumentTemplate()->inDocStylesArray[] = '
				@media (min-width: 768px){
					.typo3-login-carousel-control.right,
					.typo3-login-carousel-control.left,
					.panel-login { border: 0; }
					.typo3-login { background-image: url("' . $backgroundImage . '"); }
				}
			';
		}

		// Add additional css to use the highlight color in the login screen
		if (!empty($extConf['loginHighlightColor'])) {
			$this->getDocumentTemplate()->inDocStylesArray[] = '
				.btn-login.disabled, .btn-login[disabled], fieldset[disabled] .btn-login,
				.btn-login.disabled:hover, .btn-login[disabled]:hover, fieldset[disabled] .btn-login:hover,
				.btn-login.disabled:focus, .btn-login[disabled]:focus, fieldset[disabled] .btn-login:focus,
				.btn-login.disabled.focus, .btn-login[disabled].focus, fieldset[disabled] .btn-login.focus,
				.btn-login.disabled:active, .btn-login[disabled]:active, fieldset[disabled] .btn-login:active,
				.btn-login.disabled.active, .btn-login[disabled].active, fieldset[disabled] .btn-login.active,
				.btn-login:hover, .btn-login:focus, .btn-login:active,
				.btn-login { background-color: ' . $extConf['loginHighlightColor'] . '; }
				.panel-login .panel-body { border-color: ' . $extConf['loginHighlightColor'] . '; }
			';
		}

		// Logo
		if (!empty($extConf['loginLogo'])) {
			$logo = $extConf['loginLogo'];
		} elseif (!empty($GLOBALS['TBE_STYLES']['logo_login'])) {
			// Fallback to old TBE_STYLES login logo
			$logo = $GLOBALS['TBE_STYLES']['logo_login'];
			GeneralUtility::deprecationLog('$GLOBALS["TBE_STYLES"]["logo_login"] is deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8, please head to the backend extension configuration instead.');
		} else {
			// Use TYPO3 logo depending on highlight color
			if (!empty($extConf['loginHighlightColor'])) {
				$logo = 'EXT:backend/Resources/Public/Images/typo3_black.svg';
			} else {
				$logo = 'EXT:backend/Resources/Public/Images/typo3_orange.svg';
			}
			$this->getDocumentTemplate()->inDocStylesArray[] = '
				.typo3-login-logo .typo3-login-image { max-width: 150px; }
			';
		}
		$logo = $this->getUriForFileName($logo);

		// Start form
		$formType = empty($this->getBackendUserAuthentication()->user['uid']) ? 'loginForm' : 'logoutForm';
		$view->assignMultiple(array(
			'formTag' => $this->startForm(),
			'labelPrefixPath' => 'LLL:EXT:lang/locallang_login.xlf:',
			'backendUser' => $this->getBackendUserAuthentication()->user,
			'hasLoginError' => $this->isLoginInProgress(),
			'presetUsername' => $this->u,
			'presetPassword' => $this->p,
			'presetOpenId' => $this->openIdUrl,
			'formType' => $formType,
			'logo' => $logo,
			'images' => array(
				'capslock' => $this->getUriForFileName('EXT:backend/Resources/Public/Images/icon_capslock.svg'),
				'typo3' => $this->getUriForFileName('EXT:backend/Resources/Public/Images/typo3_orange.svg'),
			),
			'isOpenIdLoaded' => ExtensionManagementUtility::isLoaded('openid'),
			'copyright' => BackendUtility::TYPO3_copyRightNotice(),
			'loginNewsItems' => $this->getSystemNews()
		));

		// Initialize interface selectors:
		$this->makeInterfaceSelectorBox();
		$view->assignMultiple(array(
			'interfaceSelector' => $this->interfaceSelector,
			'interfaceSelectorJump' => $this->interfaceSelector_jump
		));

		// Starting page:
		$this->content .= $this->getDocumentTemplate()->startPage('TYPO3 CMS Login: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], FALSE);
		// Add Content:
		$this->content .= $view->render();
		$this->content .= $this->getDocumentTemplate()->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
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
	 * Checking, if we should perform some sort of redirection OR closing of windows.
	 *
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 */
	public function checkRedirect() {
		/*
		 * Do redirect:
		 *
		 * If a user is logged in AND
		 *   a) if either the login is just done (isLoginInProgress) or
		 *   b) a loginRefresh is done or c) the interface-selector is NOT enabled
		 *      (If it is on the other hand, it should not just load an interface,
		 *      because people has to choose then...)
		 */
		if (!empty($this->getBackendUserAuthentication()->user['uid'])
			&& ($this->isLoginInProgress() || $this->loginRefresh || !$this->interfaceSelector)) {
			/*
			 * If no cookie has been set previously we tell people that this is a problem.
			 * This assumes that a cookie-setting script (like this one) has been hit at
			 * least once prior to this instance.
			 */
			if (!$_COOKIE[BackendUserAuthentication::getCookieName()]) {
				if ($this->commandLI == 'setCookie') {
					/*
					 * we tried it a second time but still no cookie
					 * 26/4 2005: This does not work anymore, because the saving of challenge values
					 * in $_SESSION means the system will act as if the password was wrong.
					 */
					throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3. ' .
						'Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
				} else {
					// try it once again - that might be needed for auto login
					$this->redirectToURL = 'index.php?commandLI=setCookie';
				}
			}
			$redirectToUrl = (string)$this->getBackendUserAuthentication()->getTSConfigVal('auth.BE.redirectToURL');
			if (!empty($redirectToUrl)) {
				$this->redirectToURL = $redirectToUrl;
				$this->GPinterface = '';
			}
			// store interface
			$this->getBackendUserAuthentication()->uc['interfaceSetup'] = $this->GPinterface;
			$this->getBackendUserAuthentication()->writeUC();
			// Based on specific setting of interface we set the redirect script:
			switch ($this->GPinterface) {
				case 'backend':
					$this->redirectToURL = 'backend.php';
					break;
				case 'frontend':
					$this->redirectToURL = '../';
					break;
			}
			/** @var $formProtection \TYPO3\CMS\Core\FormProtection\BackendFormProtection */
			$formProtection = FormProtectionFactory::get();
			// If there is a redirect URL AND if loginRefresh is not set...
			if (!$this->loginRefresh) {
				$formProtection->storeSessionTokenInRegistry();
				HttpUtility::redirect($this->redirectToURL);
			} else {
				$formProtection->setSessionTokenFromRegistry();
				$formProtection->persistSessionToken();
				$this->getDocumentTemplate()->JScode .= $this->getDocumentTemplate()->wrapScriptTags('
					if (parent.opener && parent.opener.TYPO3 && parent.opener.TYPO3.LoginRefresh) {
						parent.opener.TYPO3.LoginRefresh.startTask();
						parent.close();
					}
				');
			}
		} elseif (empty($this->getBackendUserAuthentication()->user['uid']) && $this->isLoginInProgress()) {
			// Wrong password, wait for 5 seconds
			sleep(5);
		}
	}

	/**
	 * Making interface selector:
	 *
	 * @return void
	 */
	public function makeInterfaceSelectorBox() {
		// Reset variables:
		$this->interfaceSelector = '';
		$this->interfaceSelector_hidden = '';
		$this->interfaceSelector_jump = '';
		// If interfaces are defined AND no input redirect URL in GET vars:
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] && ($this->isLoginInProgress() || !$this->redirect_url)) {
			$parts = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']);
			// Only if more than one interface is defined will we show the selector:
			if (count($parts) > 1) {
				// Initialize:
				$labels = array(
					'backend' => $this->getLanguageService()->getLL('interface.backend'),
					'frontend' => $this->getLanguageService()->getLL('interface.frontend')
				);
				$jumpScript = array(
					'backend' => 'backend.php',
					'frontend' => '../'
				);
				// Traverse the interface keys:
				foreach ($parts as $valueStr) {
					$this->interfaceSelector .= '
							<option value="' . htmlspecialchars($valueStr) . '"' . (GeneralUtility::_GP('interface') == htmlspecialchars($valueStr) ? ' selected="selected"' : '') . '>' . htmlspecialchars($labels[$valueStr]) . '</option>';
					$this->interfaceSelector_jump .= '
							<option value="' . htmlspecialchars($jumpScript[$valueStr]) . '">' . htmlspecialchars($labels[$valueStr]) . '</option>';
				}
				$this->interfaceSelector = '
						<select id="t3-interfaceselector" name="interface" class="form-control input-login t3js-login-interface-field" tabindex="3">' . $this->interfaceSelector . '
						</select>';
				$this->interfaceSelector_jump = '
						<select id="t3-interfaceselector" name="interface" class="form-control input-login t3js-login-interface-field" tabindex="3" onchange="window.location.href=this.options[this.selectedIndex].value;">' . $this->interfaceSelector_jump . '
						</select>';
			} elseif (!$this->redirect_url) {
				// If there is only ONE interface value set and no redirect_url is present:
				$this->interfaceSelector_hidden = '<input type="hidden" name="interface" value="' . trim($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']) . '" />';
			}
		}
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
		$systemNewsRecords = $this->getDatabaseConnection()->exec_SELECTgetRows('title, content, crdate', $systemNewsTable, '1=1' . BackendUtility::BEenableFields($systemNewsTable) . BackendUtility::deleteClause($systemNewsTable), '', 'crdate DESC');
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
	 */
	public function startForm() {
		$form = '<form action="index.php" id="typo3-login-form" method="post" name="loginform">';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook'] as $function) {
				$params = array();
				$formCode = GeneralUtility::callUserFunction($function, $params, $this);
				if ($formCode) {
					$form = $formCode;
					break;
				}
			}
		}
		return $form . '<input type="hidden" name="login_status" value="login" />' .
			'<input type="hidden" id="t3-field-userident" class="t3js-login-userident-field" name="userident" value="" />' .
			'<input type="hidden" name="redirect_url" value="' . htmlspecialchars($this->redirectToURL) . '" />' .
			'<input type="hidden" name="loginRefresh" value="' . htmlspecialchars($this->loginRefresh) . '" />' .
			$this->interfaceSelector_hidden . $this->addFields_hidden;
	}

	/**
	 * Returns the uri of a relative reference, resolves the "EXT:" prefix
	 * (way of referring to files inside extensions) and checks that the file is inside
	 * the PATH_site of the TYPO3 installation
	 *
	 * @param string $filename The input filename/filepath to evaluate
	 * @return string Returns the filename of $filename if valid, otherwise blank string.
	 * @internal
	 */
	private function getUriForFileName($filename) {
		$urlPrefix = '';
		if (strpos($filename, '://')) {
			$urlPrefix = '';
		} elseif (strpos($filename, 'EXT:') === 0) {
			$absoluteFilename = GeneralUtility::getFileAbsFileName($filename);
			$filename = '';
			if ($absoluteFilename !== '') {
				$filename = PathUtility::getAbsoluteWebPath($absoluteFilename);
			}
		} elseif (strpos($filename, '/') !== 0) {
			$urlPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
		}
		return $urlPrefix . $filename;
	}

	/**
	 * Checks if login credentials are currently submitted
	 *
	 * @return bool
	 */
	protected function isLoginInProgress() {
		$username = GeneralUtility::_GP('username');
		return !empty($username) || !empty($this->commandLI);
	}

	/**
	 * Get the ObjectManager
	 *
	 * @return ObjectManager
	 */
	protected function getObjectManager() {
		return GeneralUtility::makeInstance(ObjectManager::class);
	}

	/**
	 * returns a new standalone view, shorthand function
	 *
	 * @param string $templatePathAndFileName optional the path to set the template path and filename
	 *
	 * @return StandaloneView
	 */
	protected function getFluidTemplateObject($templatePathAndFileName = NULL) {
		$this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang.xlf');
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_login.xlf');

		$view = GeneralUtility::makeInstance(StandaloneView::class);
		if ($templatePathAndFileName) {
			$view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
		}
		$view->getRequest()->setControllerExtensionName('backend');
		return $view;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}
}
