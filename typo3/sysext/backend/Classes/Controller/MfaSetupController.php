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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\AuthenticationStyleInformation;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller to provide the standalone setup endpoint for multi-factor authentication
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MfaSetupController extends AbstractMfaController
{
    protected const ACTION_METHOD_MAP = [
        'setup' => 'GET',
        'activate' => 'POST',
        'cancel' => 'GET',
    ];

    protected AuthenticationStyleInformation $authenticationStyleInformation;
    protected PageRenderer $pageRenderer;
    protected LoggerInterface $logger;

    public function __construct(
        UriBuilder $uriBuilder,
        MfaProviderRegistry $mfaProviderRegistry,
        ModuleTemplateFactory $moduleTemplateFactory,
        AuthenticationStyleInformation $authenticationStyleInformation,
        PageRenderer $pageRenderer,
        LoggerInterface $logger
    ) {
        parent::__construct($uriBuilder, $mfaProviderRegistry, $moduleTemplateFactory);
        $this->authenticationStyleInformation = $authenticationStyleInformation;
        $this->pageRenderer = $pageRenderer;
        $this->logger = $logger;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $action = (string)($request->getQueryParams()['action'] ?? 'setup');

        $backendUser = $this->getBackendUser();
        if (($backendUser->getSessionData('mfa') ?? false)
            || $backendUser->getOriginalUserIdWhenInSwitchUserMode() !== null
            || !$backendUser->isMfaSetupRequired()
            || $this->mfaProviderRegistry->hasActiveProviders($backendUser)
        ) {
            // Since the current user either did already pass MFA, is in "switch-user" mode,
            // is not required to set up MFA or has already activated a provider, throw an
            // exception to prevent the endpoint from being called unintentionally by custom code.
            throw new \InvalidArgumentException('MFA setup is not necessary. Do not call this endpoint on your own.', 1632154036);
        }

        $actionMethod = self::ACTION_METHOD_MAP[$action] ?? null;
        if ($actionMethod !== null && $request->getMethod() === $actionMethod) {
            return $this->{$action . 'Action'}($request);
        }
        return new HtmlResponse('', 404);
    }

    /**
     * Render form to setup a provider by using provider specific content. Fall
     * back to provider selection view, in case no valid provider was yet selected.
     */
    public function setupAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate->setTitle('TYPO3 CMS Login: ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? ''));

        $identifier = (string)($request->getQueryParams()['identifier'] ?? '');
        if ($identifier === '' || !$this->isValidIdentifier($identifier)) {
            return new HtmlResponse($this->renderSelectionView($request));
        }
        $mfaProvider = $this->mfaProviderRegistry->getProvider($identifier);
        $this->log('Required MFA setup initiated', $mfaProvider);
        return new HtmlResponse($this->renderSetupView($request, $mfaProvider));
    }

    /**
     * Handle activate request, receiving from the setup view
     * by forwarding the request to the appropriate provider.
     */
    public function activateAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = (string)($request->getParsedBody()['identifier'] ?? '');
        if ($identifier === '' || !$this->isValidIdentifier($identifier)) {
            // Return to selection view in case no valid identifier is given
            return new RedirectResponse($this->uriBuilder->buildUriWithRedirect('setup_mfa', [], RouteRedirect::createFromRequest($request)));
        }
        $mfaProvider = $this->mfaProviderRegistry->getProvider($identifier);
        $backendUser = $this->getBackendUser();
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $backendUser);
        // Check whether activation operation was successful and the provider is now active.
        if (!$mfaProvider->activate($request, $propertyManager) || !$mfaProvider->isActive($propertyManager)) {
            $this->log('Required MFA setup failed', $mfaProvider);
            return new RedirectResponse(
                $this->uriBuilder->buildUriWithRedirect(
                    'setup_mfa',
                    [
                        'identifier' => $mfaProvider->getIdentifier(),
                        'hasErrors' => true,
                    ],
                    RouteRedirect::createFromRequest($request)
                )
            );
        }
        $this->log('Required MFA setup successful', $mfaProvider);
        // Set the activated provider as the default provider, store the "mfa" key in the session data,
        // add a flash message to the session and finally initiate a redirect to the login, on which
        // possible redirect parameters are evaluated again.
        $backendUser->uc['mfa']['defaultProvider'] = $mfaProvider->getIdentifier();
        $backendUser->writeUC();
        $backendUser->setAndSaveSessionData('mfa', true);
        $this->addSuccessMessage($mfaProvider->getTitle());
        return new RedirectResponse($this->uriBuilder->buildUriWithRedirect('login', [], RouteRedirect::createFromRequest($request)));
    }

    /**
     * Allow the user to cancel the multi-factor authentication setup process
     * by calling logoff on the user object, to destroy the session and other
     * already gathered information and finally initiate a redirect back to the login.
     */
    public function cancelAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->log('Required MFA setup canceled');
        $this->getBackendUser()->logoff();
        return new RedirectResponse($this->uriBuilder->buildUriWithRedirect('login', [], RouteRedirect::createFromRequest($request)));
    }

    /**
     * Allow the user - required to set up MFA - to select between all available providers
     */
    protected function renderSelectionView(ServerRequestInterface $request): string
    {
        $recommendedProvider = $this->getRecommendedProvider();
        $providers = array_filter($this->allowedProviders, static function ($provider) use ($recommendedProvider) {
            // Remove the recommended provider and providers, which can not be used as default, e.g. recovery codes
            return $provider->isDefaultProviderAllowed()
                && ($recommendedProvider === null || $provider->getIdentifier() !== $recommendedProvider->getIdentifier());
        });
        $view = $this->initializeView($request, 'Selection');
        $view->assignMultiple([
            'recommendedProvider' => $recommendedProvider,
            'providers' => $providers,
        ]);
        $this->moduleTemplate->setContent($view->render());
        return $this->moduleTemplate->renderContent();
    }

    /**
     * Render form to setup a provider by using provider specific content
     */
    protected function renderSetupView(
        ServerRequestInterface $request,
        MfaProviderManifestInterface $mfaProvider
    ): string {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $providerResponse = $mfaProvider->handleRequest($request, $propertyManager, MfaViewType::SETUP);
        $view = $this->initializeView($request, 'Setup');
        $view->assignMultiple([
            'provider' => $mfaProvider,
            'providerContent' => $providerResponse->getBody(),
            'hasErrors' => (bool)($request->getQueryParams()['hasErrors'] ?? false),
        ]);
        $this->moduleTemplate->setContent($view->render());
        return $this->moduleTemplate->renderContent();
    }

    /**
     * Initialize the standalone view by setting the paths and assigning view variables
     */
    protected function initializeView(ServerRequestInterface $request, string $templateName): StandaloneView
    {
        $view = $this->moduleTemplate->getView();
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/Mfa/Standalone']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setTemplate($templateName);
        $view->assignMultiple([
            'redirect' => $request->getQueryParams()['redirect'] ?? '',
            'redirectParams' => $request->getQueryParams()['redirectParams'] ?? '',
            'siteName' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
            'footerNote' => $this->authenticationStyleInformation->getFooterNote(),
        ]);
        $this->addCustomAuthenticationFormStyles();
        return $view;
    }

    protected function addCustomAuthenticationFormStyles(): void
    {
        if (($backgroundImageStyles = $this->authenticationStyleInformation->getBackgroundImageStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginBackgroundImage', $backgroundImageStyles);
        }
        if (($highlightColorStyles = $this->authenticationStyleInformation->getHighlightColorStyles()) !== '') {
            $this->pageRenderer->addCssInlineBlock('loginHighlightColor', $highlightColorStyles);
        }
    }

    /**
     * Extend base identifier check to further evaluate whether
     * the provider is allowed to be a default provider.
     */
    protected function isValidIdentifier(string $identifier): bool
    {
        return parent::isValidIdentifier($identifier)
            && $this->mfaProviderRegistry->getProvider($identifier)->isDefaultProviderAllowed();
    }

    /**
     * Add a flash message to inform the user about the successful activation of MFA and
     * store this in the session, so it will be shown in the backend after the redirect.
     */
    protected function addSuccessMessage(string $mfaProviderTitle): void
    {
        $lang = $this->getLanguageService();
        GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->enqueue(
            GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:standalone.setup.success.message'), $lang->sL($mfaProviderTitle)),
                $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:standalone.setup.success.title'),
                AbstractMessage::OK,
                true
            )
        );
    }

    /**
     * Log debug information for MFA setup events
     */
    protected function log(string $message, ?MfaProviderManifestInterface $mfaProvider = null): void
    {
        $user = $this->getBackendUser();
        $context = [
            'user' => [
                'uid' => $user->user[$user->userid_column],
                'username' => $user->user[$user->username_column],
            ],
        ];
        if ($mfaProvider !== null) {
            $context['provider'] = $mfaProvider->getIdentifier();
        }
        $this->logger->debug($message, $context);
    }
}
