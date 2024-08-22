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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller responsible for rendering the TYPO3 Backend login form.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class LoginController
{
    use PageRendererBackendSetupTrait;

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
        protected readonly ConnectionPool $connectionPool,
        protected readonly AuthenticationStyleInformation $authenticationStyleInformation,
    ) {}

    /**
     * Injects the request and response objects for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function formAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        return $this->createLoginLogout($request, (bool)($request->getParsedBody()['loginRefresh'] ?? $request->getQueryParams()['loginRefresh'] ?? false));
    }

    /**
     * Calls the main function but with loginRefresh enabled at any time
     */
    public function refreshAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
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
        if (($logo = $this->authenticationStyleInformation->getLogo()) !== '') {
            $logoAlt = $this->authenticationStyleInformation->getLogoAlt() ?: $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
        } else {
            $logo = $this->authenticationStyleInformation->getDefaultLogo();
            $logoAlt = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_login.xlf:typo3.altText');
            $this->pageRenderer->addCssInlineBlock('loginLogo', $this->authenticationStyleInformation->getDefaultLogoStyles(), useNonce: true);
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
            'logo' => $logo,
            'logoAlt' => $logoAlt,
            'images' => $this->authenticationStyleInformation->getSupportingImages(),
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

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setRequest();
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->assignMultiple($viewVariables);

        $this->setUpBasicPageRendererForBackend($this->pageRenderer, $this->extensionConfiguration, $request, $languageService);
        $this->pageRenderer->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));

        $this->eventDispatcher->dispatch(new ModifyPageLayoutOnLoginProviderSelectionEvent($this, $view, $this->pageRenderer));

        $loginProviderConfiguration = $this->loginProviderResolver->getLoginProviderConfigurationByIdentifier($loginProviderIdentifier);
        /** @var LoginProviderInterface $loginProvider */
        $loginProvider = GeneralUtility::makeInstance($loginProviderConfiguration['provider']);
        $loginProvider->render($view, $this->pageRenderer, $this);

        $this->pageRenderer->setBodyContent('<body>' . $view->render());
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
     * @todo: Ugly. This can be used by login providers, they receive an instance of $this.
     */
    public function getLoginProviderIdentifier(): string
    {
        return $this->loginProviderResolver->resolveLoginProviderIdentifierFromRequest($this->request, 'be_lastLoginProvider');
    }

    /**
     * @todo: Ugly. This can be used by login providers, they receive an instance of $this.
     */
    public function getCurrentRequest(): ServerRequestInterface
    {
        return $this->request;
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
