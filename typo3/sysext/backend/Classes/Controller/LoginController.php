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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Exception;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for rendering the login form
 */
class LoginController
{
    /**
     * The URL to redirect to after login.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * Set to the redirect URL of the form (may be redirect_url or "index.php?M=main")
     *
     * @var string
     */
    protected $redirectToURL;

    /**
     * the active login provider identifier
     *
     * @var string
     */
    protected $loginProviderIdentifier = null;

    /**
     * List of registered and sorted login providers
     *
     * @var array
     */
    protected $loginProviders = [];

    /**
     * Login-refresh bool; The backend will call this script
     * with this value set when the login is close to being expired
     * and the form needs to be redrawn.
     *
     * @var bool
     */
    protected $loginRefresh;

    /**
     * Value of forms submit button for login.
     *
     * @var string
     */
    protected $submitValue;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Initialize the login box. Will also react on a &L=OUT flag and exit.
     */
    public function __construct()
    {
        $this->validateAndSortLoginProviders();

        // We need a PHP session session for most login levels
        session_start();
        $this->redirectUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect_url'));
        $this->loginProviderIdentifier = $this->detectLoginProvider();

        $this->loginRefresh = (bool)GeneralUtility::_GP('loginRefresh');
        // Value of "Login" button. If set, the login button was pressed.
        $this->submitValue = GeneralUtility::_GP('commandLI');

        // Try to get the preferred browser language
        /** @var Locales $locales */
        $locales = GeneralUtility::makeInstance(Locales::class);
        $preferredBrowserLanguage = $locales
            ->getPreferredClientLanguage(GeneralUtility::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));

        // If we found a $preferredBrowserLanguage and it is not the default language and no be_user is logged in
        // initialize $this->getLanguageService() again with $preferredBrowserLanguage
        if ($preferredBrowserLanguage !== 'default' && empty($this->getBackendUserAuthentication()->user['uid'])) {
            $this->getLanguageService()->init($preferredBrowserLanguage);
            GeneralUtility::makeInstance(PageRenderer::class)->setLanguage($preferredBrowserLanguage);
        }

        $this->getLanguageService()->includeLLFile('EXT:lang/Resources/Private/Language/locallang_login.xlf');

        // Setting the redirect URL to "index.php?M=main" if no alternative input is given
        $this->redirectToURL = $this->redirectUrl ?: BackendUtility::getModuleUrl('main');

        // If "L" is "OUT", then any logged in is logged out. If redirect_url is given, we redirect to it
        if (GeneralUtility::_GP('L') === 'OUT' && is_object($this->getBackendUserAuthentication())) {
            $this->getBackendUserAuthentication()->logoff();
            HttpUtility::redirect($this->redirectUrl);
        }

        $this->view = $this->getFluidTemplateObject();
    }

    /**
     * Injects the request and response objects for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response the current response
     * @return ResponseInterface the finished response with the content
     */
    public function formAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $content = $this->main();
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Main function - creating the login/logout form
     *
     * @throws Exception
     * @return string The content to output
     */
    public function main()
    {
        /** @var $pageRenderer PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Login');

        // Checking, if we should make a redirect.
        // Might set JavaScript in the header to close window.
        $this->checkRedirect();

        // Extension Configuration
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['backend'], ['allowed_classes' => false]);

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
				.btn-login:active:hover, .btn-login:active:focus,
				.btn-login { background-color: ' . $extConf['loginHighlightColor'] . '; }
				.panel-login .panel-body { border-color: ' . $extConf['loginHighlightColor'] . '; }
			';
        }

        // Logo
        if (!empty($extConf['loginLogo'])) {
            $logo = $extConf['loginLogo'];
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
        $formType = empty($this->getBackendUserAuthentication()->user['uid']) ? 'LoginForm' : 'LogoutForm';
        $this->view->assignMultiple([
            'backendUser' => $this->getBackendUserAuthentication()->user,
            'hasLoginError' => $this->isLoginInProgress(),
            'formType' => $formType,
            'logo' => $logo,
            'images' => [
                'capslock' => $this->getUriForFileName('EXT:backend/Resources/Public/Images/icon_capslock.svg'),
                'typo3' => $this->getUriForFileName('EXT:backend/Resources/Public/Images/typo3_orange.svg'),
            ],
            'copyright' => BackendUtility::TYPO3_copyRightNotice(),
            'redirectUrl' => $this->redirectUrl,
            'loginNewsItems' => $this->getSystemNews(),
            'loginProviderIdentifier' => $this->loginProviderIdentifier,
            'loginProviders' => $this->loginProviders
        ]);

        // Initialize interface selectors:
        $this->makeInterfaceSelectorBox();

        /** @var LoginProviderInterface $loginProvider */
        $loginProvider = GeneralUtility::makeInstance($this->loginProviders[$this->loginProviderIdentifier]['provider']);
        $loginProvider->render($this->view, $pageRenderer, $this);

        $content = $this->getDocumentTemplate()->startPage('TYPO3 CMS Login: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $content .= $this->view->render();
        $content .= $this->getDocumentTemplate()->endPage();

        return $content;
    }

    /**
     * Checking, if we should perform some sort of redirection OR closing of windows.
     *
     * Do redirect:
     *
     * If a user is logged in AND
     *   a) if either the login is just done (isLoginInProgress) or
     *   b) a loginRefresh is done
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    protected function checkRedirect()
    {
        if (
            empty($this->getBackendUserAuthentication()->user['uid'])
            && ($this->isLoginInProgress() || !$this->loginRefresh)
        ) {
            return;
        }

        /*
         * If no cookie has been set previously, we tell people that this is a problem.
         * This assumes that a cookie-setting script (like this one) has been hit at
         * least once prior to this instance.
         */
        if (!$_COOKIE[BackendUserAuthentication::getCookieName()]) {
            if ($this->submitValue === 'setCookie') {
                /*
                 * we tried it a second time but still no cookie
                 * 26/4 2005: This does not work anymore, because the saving of challenge values
                 * in $_SESSION means the system will act as if the password was wrong.
                 */
                throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3. ' .
                    'Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
            }
            // try it once again - that might be needed for auto login
            $this->redirectToURL = 'index.php?commandLI=setCookie';
        }
        $redirectToUrl = (string)$this->getBackendUserAuthentication()->getTSConfigVal('auth.BE.redirectToURL');
        if (empty($redirectToUrl)) {
            // Based on the interface we set the redirect script
            switch (GeneralUtility::_GP('interface')) {
                case 'frontend':
                    $interface = 'frontend';
                    $this->redirectToURL = '../';
                    break;
                case 'backend':
                    $interface = 'backend';
                    $this->redirectToURL = BackendUtility::getModuleUrl('main');
                    break;
                default:
                    $interface = '';
            }
        } else {
            $this->redirectToURL = $redirectToUrl;
            $interface = '';
        }
        // store interface
        $this->getBackendUserAuthentication()->uc['interfaceSetup'] = $interface;
        $this->getBackendUserAuthentication()->writeUC();

        $formProtection = FormProtectionFactory::get();
        if (!$formProtection instanceof BackendFormProtection) {
            throw new \RuntimeException('The Form Protection retrieved does not match the expected one.', 1432080411);
        }
        if ($this->loginRefresh) {
            $formProtection->setSessionTokenFromRegistry();
            $formProtection->persistSessionToken();
            $this->getDocumentTemplate()->JScode .= GeneralUtility::wrapJS('
				if (parent.opener && parent.opener.TYPO3 && parent.opener.TYPO3.LoginRefresh) {
					parent.opener.TYPO3.LoginRefresh.startTask();
					parent.close();
				}
			');
        } else {
            $formProtection->storeSessionTokenInRegistry();
            HttpUtility::redirect($this->redirectToURL);
        }
    }

    /**
     * Making interface selector:
     */
    public function makeInterfaceSelectorBox()
    {
        // If interfaces are defined AND no input redirect URL in GET vars:
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] && ($this->isLoginInProgress() || !$this->redirectUrl)) {
            $parts = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']);
            if (count($parts) > 1) {
                // Only if more than one interface is defined we will show the selector
                $interfaces = [
                    'backend' => [
                        'label' => $this->getLanguageService()->getLL('interface.backend'),
                        'jumpScript' => BackendUtility::getModuleUrl('main'),
                        'interface' => 'backend'
                    ],
                    'frontend' => [
                        'label' => $this->getLanguageService()->getLL('interface.frontend'),
                        'jumpScript' => '../',
                        'interface' => 'frontend'
                    ]
                ];

                $this->view->assign('showInterfaceSelector', true);
                $this->view->assign('interfaces', $interfaces);
            } elseif (!$this->redirectUrl) {
                // If there is only ONE interface value set and no redirect_url is present
                $this->view->assign('showInterfaceSelector', false);
                $this->view->assign('interface', $parts[0]);
            }
        }
    }

    /**
     * Gets news from sys_news and converts them into a format suitable for
     * showing them at the login screen.
     *
     * @return array An array of login news.
     */
    protected function getSystemNews()
    {
        $systemNewsTable = 'sys_news';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($systemNewsTable);
        $systemNews = [];
        $systemNewsRecords = $queryBuilder
            ->select('title', 'content', 'crdate')
            ->from($systemNewsTable)
            ->orderBy('crdate', 'DESC')
            ->execute()
            ->fetchAll();
        foreach ($systemNewsRecords as $systemNewsRecord) {
            $systemNews[] = [
                'date' => date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)$systemNewsRecord['crdate']),
                'header' => $systemNewsRecord['title'],
                'content' => $systemNewsRecord['content']
            ];
        }
        return $systemNews;
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
    private function getUriForFileName($filename)
    {
        if (strpos($filename, '://')) {
            return $filename;
        }
        $urlPrefix = '';
        if (strpos($filename, 'EXT:') === 0) {
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
    protected function isLoginInProgress()
    {
        $username = GeneralUtility::_GP('username');
        return !empty($username) || !empty($this->submitValue);
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    /**
     * Validates the registered login providers
     *
     * @throws \RuntimeException
     */
    protected function validateAndSortLoginProviders()
    {
        if (
            !isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'])
            || empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'])
        ) {
            throw new \RuntimeException('No login providers are registered in $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'backend\'][\'loginProviders\'].', 1433417281);
        }
        $providers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['loginProviders'];
        foreach ($providers as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException('Missing configuration for login provider "' . $identifier . '".', 1433416043);
            }
            if (!is_string($configuration['provider']) || empty($configuration['provider']) || !class_exists($configuration['provider']) || !is_subclass_of($configuration['provider'], LoginProviderInterface::class)) {
                throw new \RuntimeException('The login provider "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . LoginProviderInterface::class . '".', 1460977275);
            }
            if (empty($configuration['label'])) {
                throw new \RuntimeException('Missing label definition for login provider "' . $identifier . '".', 1433416044);
            }
            if (empty($configuration['icon-class'])) {
                throw new \RuntimeException('Missing icon definition for login provider "' . $identifier . '".', 1433416045);
            }
            if (!isset($configuration['sorting'])) {
                throw new \RuntimeException('Missing sorting definition for login provider "' . $identifier . '".', 1433416046);
            }
        }
        // sort providers
        uasort($providers, function ($a, $b) {
            return $b['sorting'] - $a['sorting'];
        });
        $this->loginProviders = $providers;
    }

    /**
     * Detect the login provider, get from request or choose the
     * first one as default
     *
     * @return string
     */
    protected function detectLoginProvider()
    {
        $loginProvider = GeneralUtility::_GP('loginProvider');
        if ((empty($loginProvider) || !isset($this->loginProviders[$loginProvider])) && !empty($_COOKIE['be_lastLoginProvider'])) {
            $loginProvider = $_COOKIE['be_lastLoginProvider'];
        }
        if (empty($loginProvider) || !isset($this->loginProviders[$loginProvider])) {
            reset($this->loginProviders);
            $loginProvider = key($this->loginProviders);
        }
        // Use the secure option when the current request is served by a secure connection:
        $cookieSecure = (bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieSecure'] && GeneralUtility::getIndpEnv('TYPO3_SSL');
        setcookie('be_lastLoginProvider', $loginProvider, $GLOBALS['EXEC_TIME'] + 7776000, null, null, $cookieSecure, true); // 90 days
        return $loginProvider;
    }

    /**
     * @return string
     */
    public function getLoginProviderIdentifier()
    {
        return $this->loginProviderIdentifier;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
