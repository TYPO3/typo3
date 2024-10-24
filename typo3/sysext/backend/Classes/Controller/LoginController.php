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
use TYPO3\CMS\Backend\Attribute\AsController;
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
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Controller responsible for rendering the TYPO3 Backend login form.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 * @todo: The central template rendering magic needs an overhaul: Currently, LoginProviderInterface has to
 *        be implemented, which retrieves a "prepared" view with tons of variable used by the default Layout "Login.html".
 *        Single LoginProviderInterface then set their template path ("Login/UserPassLoginForm" in UsernamePasswordLoginProvider),
 *        which sets Login.html as layout in its template to then get its sections "loginFormFields" and "ResetPassword"
 *        rendered. This strategy is a major mess and needs to be turned around somehow.
 *        Note there is also this BE "relogin" and "login refresh" foo with lots of attached JS magic that
 *        should either be streamlined to actually work, or (preferred) be thrown away.
 */
#[AsController]
readonly class LoginController
{
    use PageRendererBackendSetupTrait;

    public function __construct(
        protected Typo3Information $typo3Information,
        protected EventDispatcherInterface $eventDispatcher,
        protected PageRenderer $pageRenderer,
        protected UriBuilder $uriBuilder,
        protected Features $features,
        protected Context $context,
        protected LoginProviderResolver $loginProviderResolver,
        protected ExtensionConfiguration $extensionConfiguration,
        protected BackendEntryPointResolver $backendEntryPointResolver,
        protected FormProtectionFactory $formProtectionFactory,
        protected Locales $locales,
        protected ConnectionPool $connectionPool,
        protected AuthenticationStyleInformation $authenticationStyleInformation,
        protected ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * Injects the request and response objects for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->createLoginLogout($request, (bool)($request->getParsedBody()['loginRefresh'] ?? $request->getQueryParams()['loginRefresh'] ?? false));
    }

    /**
     * Calls the main function but with loginRefresh enabled at any time
     */
    public function refreshAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->createLoginLogout($request, true);
    }

    /**
     * @param bool $loginRefresh The backend triggers this with this value set when the login is
     *                           close to being expired and the form needs to be redrawn.
     * @throws PropagateResponseException
     * @throws RouteNotFoundException
     */
    protected function createLoginLogout(ServerRequestInterface $request, bool $loginRefresh): ResponseInterface
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (!empty($backendUser->user['uid'])) {
            // If BE user is logged in, redirect to backend. Also handles "refresh" foo.
            $this->checkRedirect($request, $backendUser, $loginRefresh);
        }

        $languageService = $this->getLanguageService();
        if (empty($backendUser->user['uid'])) {
            // If no user is logged in, initialize LanguageService with preferred browser language and set the
            // language to the backend user object, so labels in fluid views are translated.
            $httpAcceptLanguage = $request->getServerParams()['HTTP_ACCEPT_LANGUAGE'] ?? '';
            $preferredBrowserLanguage = $this->locales->getPreferredClientLanguage($httpAcceptLanguage);
            $languageService->init($this->locales->createLocale($preferredBrowserLanguage));
            $backendUser->user['lang'] = $preferredBrowserLanguage;
        }

        if (($backgroundImageStyles = $this->authenticationStyleInformation->getBackgroundImageStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginBackgroundImage', $backgroundImageStyles, useNonce: true);
        }
        if (($highlightColorStyles = $this->authenticationStyleInformation->getHighlightColorStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginHighlightColor', $highlightColorStyles, useNonce: true);
        }
        $loginProviderIdentifier = $this->loginProviderResolver->resolveLoginProviderIdentifierFromRequest($request, 'be_lastLoginProvider');
        if (empty($backendUser->user['uid'])) {
            // Show login form
            $action = 'login';
            $formActionUrl = $this->uriBuilder->buildUriWithRedirect('login', ['loginProvider' => $loginProviderIdentifier], RouteRedirect::createFromRequest($request));
        } else {
            // Show logout form
            $action = 'logout';
            $formActionUrl = $this->uriBuilder->buildUriFromRoute('logout');
        }
        $forgotPasswordUrl = $this->uriBuilder->buildUriWithRedirect('password_forget', ['loginProvider' => $loginProviderIdentifier], RouteRedirect::createFromRequest($request));
        $viewVariables = [
            'copyright' => $this->typo3Information->getCopyrightNotice(),
            'loginFootnote' => $this->authenticationStyleInformation->getFooterNote(),
            'referrerCheckEnabled' => $this->features->isFeatureEnabled('security.backend.enforceReferrer'),
            'loginUrl' => (string)$request->getUri(),
            'loginProviderIdentifier' => $loginProviderIdentifier,
            'backendUser' => $backendUser->user,
            'hasLoginError' => $this->isLoginInProgress($request),
            'action' => $action,
            'formActionUrl' => $formActionUrl,
            'requestTokenName' => RequestToken::PARAM_NAME,
            'requestTokenValue' => $this->provideRequestTokenJwt(),
            'forgetPasswordUrl' => $forgotPasswordUrl,
            'redirectUrl' => GeneralUtility::sanitizeLocalUrl($request->getParsedBody()['redirect_url'] ?? $request->getQueryParams()['redirect_url'] ?? ''),
            'loginRefresh' => $loginRefresh,
            'loginProviders' => $this->loginProviderResolver->getLoginProviders(),
            'loginNewsItems' => $this->getSystemNews(),
        ];

        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $request, $languageService);
        $this->pageRenderer->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));

        $loginProviderConfiguration = $this->loginProviderResolver->getLoginProviderConfigurationByIdentifier($loginProviderIdentifier);
        $loginProvider = GeneralUtility::makeInstance($loginProviderConfiguration['provider']);
        if (!$loginProvider instanceof LoginProviderInterface) {
            throw new \RuntimeException($loginProviderConfiguration['provider'] . ' must implement LoginProviderInterface', 1724772171);
        }
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:backend/Resources/Private/Templates'],
            partialRootPaths: ['EXT:backend/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:backend/Resources/Private/Layouts'],
            request: $request,
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($viewVariables);
        $this->eventDispatcher->dispatch(new ModifyPageLayoutOnLoginProviderSelectionEvent($view, $request));
        $templateFile = $loginProvider->modifyView($request, $view);
        $content = $view->render($templateFile);
        $this->pageRenderer->setBodyContent('<body>' . $content);
        $response = $this->pageRenderer->renderResponse();
        return $this->appendLoginProviderCookie($request, $response);
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
     * @throws PropagateResponseException
     */
    protected function checkRedirect(ServerRequestInterface $request, BackendUserAuthentication $backendUser, bool $loginRefresh): void
    {
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        if (!$formProtection instanceof BackendFormProtection) {
            throw new \RuntimeException('The Form Protection retrieved does not match the expected one.', 1432080411);
        }
        if ($loginRefresh) {
            // Triggering `TYPO3/CMS/Backend/LoginRefresh` module happens in JS `TYPO3/CMS/Backend/Login`
            $formProtection->setSessionTokenFromRegistry();
            $formProtection->persistSessionToken();
        } else {
            $formProtection->storeSessionTokenInRegistry();
            // @todo: Consolidate RouteDispatcher::evaluateReferrer() when changing 'main' to something different
            $redirectToURL = (string)($backendUser->getTSConfig()['auth.']['BE.']['redirectToURL'] ?? '')
                ?: (string)$this->uriBuilder->buildUriWithRedirect('main', [], RouteRedirect::createFromRequest($request));
            throw new PropagateResponseException(new RedirectResponse($redirectToURL, 303), 1724705833);
        }
    }

    /**
     * If a login provider was chosen in the previous request, which is not the default provider,
     * it is stored in a Cookie and appended to the HTTP Response.
     */
    protected function appendLoginProviderCookie(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $loginProviderIdentifier = $this->loginProviderResolver->resolveLoginProviderIdentifierFromRequest($request, 'be_lastLoginProvider');
        if ($loginProviderIdentifier === $this->loginProviderResolver->getPrimaryLoginProviderIdentifier()) {
            return $response;
        }
        $cookie = new Cookie(
            'be_lastLoginProvider',
            $loginProviderIdentifier,
            $GLOBALS['EXEC_TIME'] + 7776000, // 90 days
            $this->backendEntryPointResolver->getPathFromRequest($request),
            '',
            // Use the secure option when the current request is served by a secure connection
            $normalizedParams->isHttps(),
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
        return $response->withAddedHeader('Set-Cookie', $cookie->__toString());
    }

    /**
     * Gets news as array from sys_news and converts them into a
     * format suitable for showing them at the login screen.
     */
    protected function getSystemNews(): array
    {
        $systemNews = [];
        $queryResult = $this->connectionPool
            ->getQueryBuilderForTable('sys_news')
            ->select('uid', 'title', 'content', 'crdate')
            ->from('sys_news')
            ->orderBy('crdate', 'DESC')
            ->executeQuery();
        while ($row = $queryResult->fetchAssociative()) {
            $systemNews[] = [
                'uid' => $row['uid'],
                'date' => $row['crdate'] ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)$row['crdate']) : '',
                'header' => $row['title'],
                'content' => $row['content'],
            ];
        }
        return $systemNews;
    }

    /**
     * Checks if login credentials have been submitted
     */
    protected function isLoginInProgress(ServerRequestInterface $request): bool
    {
        // @todo: Restrict to POST?!
        // Value of forms submit button for login. If set, the login button was pressed.
        $submitValue = $request->getParsedBody()['commandLI'] ?? $request->getQueryParams()['commandLI'] ?? '';
        $username = $request->getParsedBody()['username'] ?? $request->getQueryParams()['username'] ?? null;
        return !empty($username) || !empty($submitValue);
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
