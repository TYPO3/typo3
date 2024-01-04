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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderResolver;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\PageRendererBackendSetupTrait;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller responsible for rendering the TYPO3 Backend login form.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class LoginController
{
    use PageRendererBackendSetupTrait;

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
     * @todo: Only set for getCurrentRequest(). Should vanish.
     */
    protected ServerRequestInterface $request;

    public function __construct(
        protected readonly Typo3Information $typo3Information,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly PageRenderer $pageRenderer,
        protected readonly UriBuilder $uriBuilder,
        protected readonly Features $features,
        protected readonly Context $context,
        protected readonly LoginProviderResolver $loginProviderResolver,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendEntryPointResolver $backendEntryPointResolver,
        protected readonly FormProtectionFactory $formProtectionFactory,
        protected readonly Locales $locales,
    ) {}

    /**
     * Injects the request and response objects for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->init($request);
        $response = $this->createLoginLogoutForm($request);
        return $this->appendLoginProviderCookie($request, $request->getAttribute('normalizedParams'), $response);
    }

    /**
     * Calls the main function but with loginRefresh enabled at any time
     */
    public function refreshAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->init($request);
        $this->loginRefresh = true;
        $response = $this->createLoginLogoutForm($request);
        return $this->appendLoginProviderCookie($request, $request->getAttribute('normalizedParams'), $response);
    }

    /**
     * Returns a new request-token value, which is signed by a new nonce value (the nonce is sent
     * as cookie automatically in `RequestTokenMiddleware` since it is created via the `NoncePool`).
     */
    public function requestTokenAction(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse([
            'headerName' => RequestToken::HEADER_NAME,
            'requestToken' => $this->provideRequestTokenJwt(),
        ]);
    }

    /**
     * @todo: Ugly. This can be used by login providers, they receive an instance of $this.
     *        Unused in core, though. It should vanish when login providers receive love.
     */
    public function getLoginProviderIdentifier(): string
    {
        return $this->loginProviderIdentifier;
    }

    /**
     * @todo: Ugly. This can be used by login providers, they receive an instance of $this.
     */
    public function getCurrentRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * If a login provider was chosen in the previous request, which is not the default provider,
     * it is stored in a Cookie and appended to the HTTP Response.
     */
    protected function appendLoginProviderCookie(ServerRequestInterface $request, NormalizedParams $normalizedParams, ResponseInterface $response): ResponseInterface
    {
        if ($this->loginProviderIdentifier === $this->loginProviderResolver->getPrimaryLoginProviderIdentifier()) {
            return $response;
        }
        // Use the secure option when the current request is served by a secure connection
        $cookie = new Cookie(
            'be_lastLoginProvider',
            $this->loginProviderIdentifier,
            $GLOBALS['EXEC_TIME'] + 7776000, // 90 days
            $this->backendEntryPointResolver->getPathFromRequest($request),
            '',
            $normalizedParams->isHttps(),
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
        return $response->withAddedHeader('Set-Cookie', $cookie->__toString());
    }

    /**
     * Initialize the login box. Will also react on a &L=OUT flag and exit.
     */
    protected function init(ServerRequestInterface $request): void
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Try to get the preferred browser language
        $httpAcceptLanguage = $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $preferredBrowserLanguage = $this->locales->getPreferredClientLanguage($httpAcceptLanguage);

        // If we found a $preferredBrowserLanguage, which is not the default language, while no user is logged in,
        // initialize $this->getLanguageService() and set the language to the backend user object, so labels in fluid
        // views are translated
        if (empty($backendUser->user['uid'])) {
            $languageService->init($this->locales->createLocale($preferredBrowserLanguage));
            $backendUser->user['lang'] = $preferredBrowserLanguage;
        }

        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $request, $languageService);
        $this->pageRenderer->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));

        $this->redirectUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['redirect_url'] ?? $queryParams['redirect_url'] ?? '');
        $this->loginProviderIdentifier = $this->loginProviderResolver->resolveLoginProviderIdentifierFromRequest($request, 'be_lastLoginProvider');

        $this->loginRefresh = (bool)($parsedBody['loginRefresh'] ?? $queryParams['loginRefresh'] ?? false);
        // Value of "Login" button. If set, the login button was pressed.
        $this->submitValue = $parsedBody['commandLI'] ?? $queryParams['commandLI'] ?? null;

        // Setting the redirect URL to "index.php?M=main" if no alternative input is given
        if ($this->redirectUrl) {
            $this->redirectToURL = $this->redirectUrl;
        } else {
            // (consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different)
            $this->redirectToURL = (string)$this->uriBuilder->buildUriWithRedirect('main', [], RouteRedirect::createFromRequest($request));
        }

        // If "L" is "OUT", then any logged in is logged out. If redirect_url is given, we redirect to it
        if (($parsedBody['L'] ?? $queryParams['L'] ?? null) === 'OUT' && is_object($backendUser)) {
            $backendUser->logoff();
            $this->redirectToUrl();
        }

        // @todo: This should be ViewInterface. But this breaks LoginProviderInterface AND ModifyPageLayoutOnLoginProviderSelectionEvent
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        // StandaloneView should NOT receive a request at all, override the default StandaloneView constructor here.
        $this->view->setRequest();
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $this->view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $this->view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $this->provideCustomLoginStyling();
        $this->view->assign('referrerCheckEnabled', $this->features->isFeatureEnabled('security.backend.enforceReferrer'));
        $this->view->assign('loginUrl', (string)$request->getUri());
        $this->view->assign('loginProviderIdentifier', $this->loginProviderIdentifier);
    }

    protected function provideCustomLoginStyling(): void
    {
        $languageService = $this->getLanguageService();
        $authenticationStyleInformation = GeneralUtility::makeInstance(AuthenticationStyleInformation::class);
        if (($backgroundImageStyles = $authenticationStyleInformation->getBackgroundImageStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginBackgroundImage', $backgroundImageStyles, useNonce: true);
        }
        if (($footerNote = $authenticationStyleInformation->getFooterNote()) !== '') {
            $this->view->assign('loginFootnote', $footerNote);
        }
        if (($highlightColorStyles = $authenticationStyleInformation->getHighlightColorStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginHighlightColor', $highlightColorStyles, useNonce: true);
        }
        if (($logo = $authenticationStyleInformation->getLogo()) !== '') {
            $logoAlt = $authenticationStyleInformation->getLogoAlt() ?: $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
        } else {
            $logo = $authenticationStyleInformation->getDefaultLogo();
            $logoAlt = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
            $this->pageRenderer->addCssInlineBlock('loginLogo', $authenticationStyleInformation->getDefaultLogoStyles(), useNonce: true);
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
     */
    protected function createLoginLogoutForm(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUserAuthentication();

        // Checking, if we should make a redirect.
        // Might set JavaScript in the header to close window.
        $this->checkRedirect($request);

        // Show login form
        if (empty($backendUser->user['uid'])) {
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
            'backendUser' => $backendUser->user,
            'hasLoginError' => $this->isLoginInProgress($request),
            'action' => $action,
            'formActionUrl' => $formActionUrl,
            'requestTokenName' => RequestToken::PARAM_NAME,
            'requestTokenValue' => $this->provideRequestTokenJwt(),
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
        $this->renderHtmlViaLoginProvider();

        $this->pageRenderer->setBodyContent('<body>' . $this->view->render());
        return $this->pageRenderer->renderResponse();
    }

    protected function renderHtmlViaLoginProvider(): void
    {
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
    }

    /**
     * Checking, if we should perform some sort of redirection OR closing of windows.
     * Do a redirect if a user is logged in.
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws RouteNotFoundException
     */
    protected function checkRedirect(ServerRequestInterface $request): void
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (empty($backendUser->user['uid'])) {
            return;
        }

        // If no cookie has been set previously, we tell people that this is a problem.
        // This assumes that a cookie-setting script (like this one) has been hit at
        // least once prior to this instance.
        if (!isset($request->getCookieParams()[BackendUserAuthentication::getCookieName()])) {
            if ($this->submitValue === 'setCookie') {
                // we tried it a second time but still no cookie
                throw new \RuntimeException('Login-error: Yeah, that\'s a classic. No cookies, no TYPO3. ' .
                    'Please accept cookies from TYPO3 - otherwise you\'ll not be able to use the system.', 1294586846);
            }
            // try it once again - that might be needed for auto login
            $this->redirectToURL = 'index.php?commandLI=setCookie';
        }
        $redirectToUrl = (string)($backendUser->getTSConfig()['auth.']['BE.']['redirectToURL'] ?? '');
        if (!empty($redirectToUrl)) {
            $this->redirectToURL = $redirectToUrl;
        } else {
            // (consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different)
            $this->redirectToURL = (string)$this->uriBuilder->buildUriWithRedirect('main', [], RouteRedirect::createFromRequest($request));
        }

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
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
     * Gets news as array from sys_news and converts them into a
     * format suitable for showing them at the login screen.
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
     */
    protected function isLoginInProgress(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $username = $parsedBody['username'] ?? $queryParams['username'] ?? null;
        return !empty($username) || !empty($this->submitValue);
    }

    /**
     * Wrapper method to redirect to configured redirect URL.
     *
     * @throws PropagateResponseException
     */
    protected function redirectToUrl(): void
    {
        throw new PropagateResponseException(new RedirectResponse($this->redirectToURL, 303), 1607271511);
    }

    protected function provideRequestTokenJwt(): string
    {
        $nonce = SecurityAspect::provideIn($this->context)->provideNonce();
        return RequestToken::create('core/user-auth/be')->toHashSignedJwt($nonce);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
