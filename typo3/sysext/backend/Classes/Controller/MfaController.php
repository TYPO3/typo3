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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface;
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
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Controller to provide a multi-factor authentication endpoint
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MfaController extends AbstractMfaController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected AuthenticationStyleInformation $authenticationStyleInformation;
    protected PageRenderer $pageRenderer;

    public function __construct(
        UriBuilder $uriBuilder,
        MfaProviderRegistry $mfaProviderRegistry,
        ModuleTemplateFactory $moduleTemplateFactory,
        AuthenticationStyleInformation $authenticationStyleInformation,
        PageRenderer $pageRenderer
    ) {
        parent::__construct($uriBuilder, $mfaProviderRegistry, $moduleTemplateFactory);
        $this->authenticationStyleInformation = $authenticationStyleInformation;
        $this->pageRenderer = $pageRenderer;
    }

    /**
     * Main entry point, checking prerequisite, initializing and setting
     * up the view and finally dispatching to the requested action.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $action = (string)($request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'auth');

        switch ($action) {
            case 'auth':
            case 'verify':
                $mfaProvider = $this->getMfaProviderFromRequest($request);
                // All actions except "cancel" require a provider to deal with.
                // If non is found at this point, throw an exception since this should never happen.
                if ($mfaProvider === null) {
                    throw new \InvalidArgumentException('No active MFA provider was found!', 1611879242);
                }
                return $this->{$action . 'Action'}($request, $mfaProvider);
            case 'cancel':
                return $this->cancelAction($request);
            default:
                throw new \InvalidArgumentException('Action not allowed', 1611879244);
        }
    }

    /**
     * Setup the authentication view for the provider by using provider specific content
     */
    public function authAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $view = $this->moduleTemplate->getView();
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/Mfa']);
        $view->setTemplate('Auth');
        $view->assign('formUrl', $this->uriBuilder->buildUriWithRedirect(
            'auth_mfa',
            [
                'action' => 'verify',
            ],
            RouteRedirect::createFromRequest($request)
        ));
        $view->assign('redirectRoute', $request->getQueryParams()['redirect'] ?? '');
        $view->assign('redirectParams', $request->getQueryParams()['redirectParams'] ?? '');
        $view->assign('hasAuthError', (bool)($request->getQueryParams()['failure'] ?? false));
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $providerResponse = $mfaProvider->handleRequest($request, $propertyManager, MfaViewType::AUTH);
        $view->assignMultiple([
            'provider' => $mfaProvider,
            'alternativeProviders' => $this->getAlternativeProviders($mfaProvider),
            'isLocked' => $mfaProvider->isLocked($propertyManager),
            'providerContent' => $providerResponse->getBody(),
            'footerNote' => $this->authenticationStyleInformation->getFooterNote(),
        ]);
        $this->moduleTemplate->setTitle('TYPO3 CMS Login: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $this->addCustomAuthenticationFormStyles();
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Handle verification request, receiving from the auth view
     * by forwarding the request to the appropriate provider.
     */
    public function verifyAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());

        // Check if the provider can process the request and is not temporarily blocked
        if (!$mfaProvider->canProcess($request) || $mfaProvider->isLocked($propertyManager)) {
            // If this fails, cancel the authentication
            return $this->cancelAction($request);
        }
        // Call the provider to verify the request
        if (!$mfaProvider->verify($request, $propertyManager)) {
            $this->log('Multi-factor authentication failed');
            // If failed, initiate a redirect back to the auth view
            return new RedirectResponse($this->uriBuilder->buildUriWithRedirect(
                'auth_mfa',
                [
                    'identifier' => $mfaProvider->getIdentifier(),
                    'failure' => true,
                ],
                RouteRedirect::createFromRequest($request)
            ));
        }
        $this->log('Multi-factor authentication successful');
        // If verified, store this information in the session
        // and initiate a redirect back to the login view.
        $this->getBackendUser()->setAndSaveSessionData('mfa', true);
        return new RedirectResponse(
            $this->uriBuilder->buildUriWithRedirect('login', [], RouteRedirect::createFromRequest($request))
        );
    }

    /**
     * Allow the user to cancel the multi-factor authentication by
     * calling logoff on the user object, to destroy the session and
     * other already gathered information and finally initiate a
     * redirect back to the login.
     */
    public function cancelAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->log('Multi-factor authentication canceled');
        $this->getBackendUser()->logoff();
        return new RedirectResponse($this->uriBuilder->buildUriWithRedirect('login', [], RouteRedirect::createFromRequest($request)));
    }

    /**
     * Fetch alternative (activated and allowed) providers for the user to chose from
     *
     * @return ProviderInterface[]
     */
    protected function getAlternativeProviders(MfaProviderManifestInterface $mfaProvider): array
    {
        return array_filter($this->allowedProviders, function ($provider) use ($mfaProvider) {
            return $provider !== $mfaProvider
                && $provider->isActive(MfaProviderPropertyManager::create($provider, $this->getBackendUser()));
        });
    }

    /**
     * Log debug information for MFA events
     */
    protected function log(string $message, array $additionalData = [], ?MfaProviderManifestInterface $mfaProvider = null): void
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
            $context['isProviderLocked'] = $mfaProvider->isLocked(
                MfaProviderPropertyManager::create($mfaProvider, $user)
            );
        }
        $this->logger->debug($message, array_replace_recursive($context, $additionalData));
    }

    protected function getMfaProviderFromRequest(ServerRequestInterface $request): ?MfaProviderManifestInterface
    {
        $identifier = (string)($request->getQueryParams()['identifier'] ?? $request->getParsedBody()['identifier'] ?? '');
        // Check if given identifier is valid
        if ($this->isValidIdentifier($identifier)) {
            $provider = $this->mfaProviderRegistry->getProvider($identifier);
            // Only add provider if it was activated by the current user
            if ($provider->isActive(MfaProviderPropertyManager::create($provider, $this->getBackendUser()))) {
                return $provider;
            }
        }
        return null;
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
}
