<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderResolver;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller responsible for rendering the TYPO3 Backend login form
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
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
    protected string $loginProviderIdentifier = '';

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
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected EventDispatcherInterface $eventDispatcher;
    protected Typo3Information $typo3Information;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected Features $features;
    protected Context $context;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected LoginProviderResolver $loginProviderResolver;

    protected ?ServerRequestInterface $currentRequest = null;

    public function __construct(
        Typo3Information $typo3Information,
        EventDispatcherInterface $eventDispatcher,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        Features $features,
        Context $context,
        ModuleTemplateFactory $moduleTemplateFactory,
        LoginProviderResolver $loginProviderResolver
    ) {
        $this->typo3Information = $typo3Information;
        $this->eventDispatcher = $eventDispatcher;
        $this->uriBuilder = $uriBuilder;
        $this->pageRenderer = $pageRenderer;
        $this->features = $features;
        $this->context = $context;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->loginProviderResolver = $loginProviderResolver;
    }

    /**
     * Injects the request and response objects for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the finished response with the content
     */
    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $response = new HtmlResponse($this->createLoginLogoutForm($request));
        return $this->appendLoginProviderCookie($request->getAttribute('normalizedParams'), $response);
    }

    /**
     * Calls the main function but with loginRefresh enabled at any time
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the finished response with the content
     */
    public function refreshAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->loginRefresh = true;
        $response = new HtmlResponse($this->createLoginLogoutForm($request));
        return $this->appendLoginProviderCookie($request->getAttribute('normalizedParams'), $response);
    }

    /**
     * If a login provider was chosen in the previous request, which is not the default provider, it is stored in a
     * Cookie and appended to the HTTP Response.
     *
     * @param NormalizedParams $normalizedParams
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function appendLoginProviderCookie(NormalizedParams $normalizedParams, ResponseInterface $response): ResponseInterface
    {
        if ($this->loginProviderIdentifier === $this->loginProviderResolver->getPrimaryLoginProviderIdentifier()) {
            return $response;
        }
        // Use the secure option when the current request is served by a secure connection
        $cookie = new Cookie(
            'be_lastLoginProvider',
            $this->loginProviderIdentifier,
            $GLOBALS['EXEC_TIME'] + 7776000, // 90 days
            $normalizedParams->getSitePath() . TYPO3_mainDir,
            '',
            $normalizedParams->isHttps(),
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
        return $response->withAddedHeader('Set-Cookie', $cookie->__toString());
    }

    /**
     * This can be called by single login providers, they receive an instance of $this
     *
     * @return string
     */
    public function getLoginProviderIdentifier()
    {
        return $this->loginProviderIdentifier;
    }

    /**
     * Initialize the login box. Will also react on a &L=OUT flag and exit.
     *
     * @param ServerRequestInterface $request the current request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->redirectUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['redirect_url'] ?? $queryParams['redirect_url'] ?? null);
        $this->loginProviderIdentifier = $this->loginProviderResolver->resolveLoginProviderIdentifierFromRequest($request, 'be_lastLoginProvider');

        $this->loginRefresh = (bool)($parsedBody['loginRefresh'] ?? $queryParams['loginRefresh'] ?? false);
        // Value of "Login" button. If set, the login button was pressed.
        $this->submitValue = $parsedBody['commandLI'] ?? $queryParams['commandLI'] ?? null;
        // Try to get the preferred browser language
        $httpAcceptLanguage = $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $preferredBrowserLanguage = GeneralUtility::makeInstance(Locales::class)->getPreferredClientLanguage($httpAcceptLanguage);

        // If we found a $preferredBrowserLanguage and it is not the default language and no be_user is logged in
        // initialize $this->getLanguageService() again with $preferredBrowserLanguage
        if ($preferredBrowserLanguage !== 'default' && empty($this->getBackendUserAuthentication()->user['uid'])) {
            $this->getLanguageService()->init($preferredBrowserLanguage);
            $this->pageRenderer->setLanguage($preferredBrowserLanguage);
        }

        $this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_login.xlf');

        // Setting the redirect URL to "index.php?M=main" if no alternative input is given
        if ($this->redirectUrl) {
            $this->redirectToURL = $this->redirectUrl;
        } else {
            // (consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different)
            $this->redirectToURL = (string)$this->uriBuilder->buildUriWithRedirect('main', [], RouteRedirect::createFromRequest($request));
        }

        // If "L" is "OUT", then any logged in is logged out. If redirect_url is given, we redirect to it
        if (($parsedBody['L'] ?? $queryParams['L'] ?? null) === 'OUT' && is_object($this->getBackendUserAuthentication())) {
            $this->getBackendUserAuthentication()->logoff();
            $this->redirectToUrl();
        }

        $this->view = $this->moduleTemplate->getView();
        $this->view->getRequest()->setControllerExtensionName('Backend');
        $this->provideCustomLoginStyling();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Login');
        $this->view->assign('referrerCheckEnabled', $this->features->isFeatureEnabled('security.backend.enforceReferrer'));
        $this->view->assign('loginUrl', (string)$request->getUri());
        $this->view->assign('loginProviderIdentifier', $this->loginProviderIdentifier);
    }

    protected function provideCustomLoginStyling(): void
    {
        $authenticationStyleInformation = GeneralUtility::makeInstance(AuthenticationStyleInformation::class);
        if (($backgroundImageStyles = $authenticationStyleInformation->getBackgroundImageStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginBackgroundImage', $backgroundImageStyles);
        }
        if (($footerNote = $authenticationStyleInformation->getFooterNote()) !== '') {
            $this->view->assign('loginFootnote', $footerNote);
        }
        if (($highlightColorStyles = $authenticationStyleInformation->getHighlightColorStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginHighlightColor', $highlightColorStyles);
        }
        if (($logo = $authenticationStyleInformation->getLogo()) !== '') {
            $logoAlt = $authenticationStyleInformation->getLogoAlt();
        } else {
            $logo = $authenticationStyleInformation->getDefaultLogo();
            $logoAlt = $this->getLanguageService()->getLL('typo3.altText');
            $this->pageRenderer->addCssInlineBlock('loginLogo', $authenticationStyleInformation->getDefaultLogoStyles());
        }
        $this->view->assignMultiple([
            'logo' => $logo,
            'logoAlt' => $logoAlt,
            'images' => $authenticationStyleInformation->getSupportingImages(),
            'copyright' => $this->typo3Information->getCopyrightNotice(),
        ]);
    }

    /**
     * Main function - creating the login/logout form
     *
     * @param ServerRequestInterface $request
     * @return string $content
     */
    protected function createLoginLogoutForm(ServerRequestInterface $request): string
    {
        // Checking, if we should make a redirect.
        // Might set JavaScript in the header to close window.
        $this->checkRedirect($request);

        // Show login form
        if (empty($this->getBackendUserAuthentication()->user['uid'])) {
            $action = 'login';
            $formActionUrl = $this->uriBuilder->buildUriWithRedirect(
                'login',
                [
                    'loginProvider' => $this->loginProviderIdentifier,
                ],
                RouteRedirect::createFromRequest($request)
            );
        } else {
            // Show logout form
            $action = 'logout';
            $formActionUrl = $this->uriBuilder->buildUriFromRoute('logout');
        }
        $this->view->assignMultiple([
            'backendUser' => $this->getBackendUserAuthentication()->user,
            'hasLoginError' => $this->isLoginInProgress($request),
            'action' => $action,
            'formActionUrl' => $formActionUrl,
            'forgetPasswordUrl' => $this->uriBuilder->buildUriWithRedirect(
                'password_forget',
                ['loginProvider' => $this->loginProviderIdentifier],
                RouteRedirect::createFromRequest($request)
            ),
            'redirectUrl' => $this->redirectUrl,
            'loginRefresh' => $this->loginRefresh,
            'loginProviders' => $this->loginProviderResolver->getLoginProviders(),
            'loginNewsItems' => $this->getSystemNews(),
        ]);

        // Initialize interface selectors:
        $this->makeInterfaceSelector($request);
        $this->renderHtmlViaLoginProvider($request);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->moduleTemplate->renderContent();
    }

    protected function renderHtmlViaLoginProvider(ServerRequestInterface $request): void
    {
        $this->currentRequest = $request;
        $loginProviderConfiguration = $this->loginProviderResolver->getLoginProviderConfigurationByIdentifier($this->loginProviderIdentifier);
        /** @var LoginProviderInterface $loginProvider */
        $loginProvider = GeneralUtility::makeInstance($loginProviderConfiguration['provider']);
        $this->eventDispatcher->dispatch(
            new ModifyPageLayoutOnLoginProviderSelectionEvent(
                $this,
                $this->view,
                $this->pageRenderer
            )
        );
        $loginProvider->render($this->view, $this->pageRenderer, $this);
        $this->currentRequest = null;
    }

    /**
     * Checking, if we should perform some sort of redirection OR closing of windows.
     *
     * Do a redirect if a user is logged in
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function checkRedirect(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (empty($backendUser->user['uid'])) {
            return;
        }

        /*
         * If no cookie has been set previously, we tell people that this is a problem.
         * This assumes that a cookie-setting script (like this one) has been hit at
         * least once prior to this instance.
         */
        if (!isset($_COOKIE[BackendUserAuthentication::getCookieName()])) {
            if ($this->submitValue === 'setCookie') {
                // we tried it a second time but still no cookie
                throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3. ' .
                    'Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
            }
            // try it once again - that might be needed for auto login
            $this->redirectToURL = 'index.php?commandLI=setCookie';
        }
        $redirectToUrl = (string)($backendUser->getTSConfig()['auth.']['BE.']['redirectToURL'] ?? '');
        if (empty($redirectToUrl)) {
            // Based on the interface we set the redirect script
            $parsedBody = $request->getParsedBody();
            $queryParams = $request->getQueryParams();
            $interface = $parsedBody['interface'] ?? $queryParams['interface'] ?? '';
            switch ($interface) {
                case 'frontend':
                    $this->redirectToURL = '../';
                    break;
                case 'backend':
                    // (consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different)
                    $this->redirectToURL = (string)$this->uriBuilder->buildUriWithRedirect('main', [], RouteRedirect::createFromRequest($request));
                    break;
            }
        } else {
            $this->redirectToURL = $redirectToUrl;
            $interface = '';
        }
        // store interface
        $backendUser->uc['interfaceSetup'] = $interface;
        $backendUser->writeUC();

        $formProtection = FormProtectionFactory::get();
        if (!$formProtection instanceof BackendFormProtection) {
            throw new \RuntimeException('The Form Protection retrieved does not match the expected one.', 1432080411);
        }
        if ($this->loginRefresh) {
            $formProtection->setSessionTokenFromRegistry();
            $formProtection->persistSessionToken();
        // triggering `TYPO3/CMS/Backend/LoginRefresh` module happens in `TYPO3/CMS/Backend/Login`
        } else {
            $formProtection->storeSessionTokenInRegistry();
            $this->redirectToUrl();
        }
    }

    /**
     * Making interface selector
     * @param ServerRequestInterface $request
     */
    protected function makeInterfaceSelector(ServerRequestInterface $request): void
    {
        // If interfaces are defined AND no input redirect URL in GET vars:
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] && ($this->isLoginInProgress($request) || !$this->redirectUrl)) {
            $parts = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces']);
            if (count($parts) > 1) {
                // Only if more than one interface is defined we will show the selector
                $interfaces = [
                    'backend' => [
                        'label' => $this->getLanguageService()->getLL('interface.backend'),
                        'jumpScript' => (string)$this->uriBuilder->buildUriFromRoute('main'),
                        'interface' => 'backend',
                    ],
                    'frontend' => [
                        'label' => $this->getLanguageService()->getLL('interface.frontend'),
                        'jumpScript' => '../',
                        'interface' => 'frontend',
                    ],
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
    protected function getSystemNews(): array
    {
        $systemNewsTable = 'sys_news';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($systemNewsTable);
        $systemNews = [];
        $systemNewsRecords = $queryBuilder
            ->select('uid', 'title', 'content', 'crdate')
            ->from($systemNewsTable)
            ->orderBy('crdate', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($systemNewsRecords as $systemNewsRecord) {
            $systemNews[] = [
                'uid' => $systemNewsRecord['uid'],
                'date' => $systemNewsRecord['crdate'] ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)$systemNewsRecord['crdate']) : '',
                'header' => $systemNewsRecord['title'],
                'content' => $systemNewsRecord['content'],
            ];
        }
        return $systemNews;
    }

    /**
     * Checks if login credentials are currently submitted
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    protected function isLoginInProgress(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $username = $parsedBody['username'] ?? $queryParams['username'] ?? null;
        return !empty($username) || !empty($this->submitValue);
    }

    /**
     * Wrapper method to redirect to configured redirect URL
     */
    protected function redirectToUrl(): void
    {
        throw new PropagateResponseException(new RedirectResponse($this->redirectToURL, 303), 1607271511);
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public function getCurrentRequest(): ?ServerRequestInterface
    {
        return $this->currentRequest;
    }
}
