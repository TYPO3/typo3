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
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Controller to provide a multi-factor authentication endpoint
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MfaController extends AbstractMfaController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $allowedActions = ['auth', 'verify', 'cancel'];

    /**
     * Main entry point, checking prerequisite, initializing and setting
     * up the view and finally dispatching to the requested action.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = (string)($request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'auth');

        if (!$this->isActionAllowed($action)) {
            throw new \InvalidArgumentException('Action not allowed', 1611879243);
        }

        $this->initializeAction($request);
        // All actions expect "cancel" require a provider to deal with.
        // If non is found at this point, throw an exception since this should never happen.
        if ($this->mfaProvider === null && $action !== 'cancel') {
            throw new \InvalidArgumentException('No active MFA provider was found!', 1611879242);
        }

        $this->view = $this->moduleTemplate->getView();
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/Mfa']);
        $this->view->setTemplate('Auth');
        $this->view->assign('hasAuthError', (bool)($request->getQueryParams()['failure'] ?? false));

        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $this->moduleTemplate->setTitle('TYPO3 CMS Login: ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Setup the authentication view for the provider by using provider specific content
     *
     * @param ServerRequestInterface $request
     */
    public function authAction(ServerRequestInterface $request): void
    {
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        $providerResponse = $this->mfaProvider->handleRequest($request, $propertyManager, MfaViewType::AUTH);
        $this->view->assignMultiple([
            'provider' => $this->mfaProvider,
            'alternativeProviders' => $this->getAlternativeProviders(),
            'isLocked' => $this->mfaProvider->isLocked($propertyManager),
            'providerContent' => $providerResponse->getBody()
        ]);
    }

    /**
     * Handle verification request, receiving from the auth view
     * by forwarding the request to the appropriate provider.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    public function verifyAction(ServerRequestInterface $request): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());

        // Check if the provider can process the request and is not temporarily blocked
        if (!$this->mfaProvider->canProcess($request) || $this->mfaProvider->isLocked($propertyManager)) {
            // If this fails, cancel the authentication
            return $this->cancelAction($request);
        }
        // Call the provider to verify the request
        if (!$this->mfaProvider->verify($request, $propertyManager)) {
            $this->log('Multi-factor authentication failed');
            // If failed, initiate a redirect back to the auth view
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute(
                'auth_mfa',
                [
                    'identifier' => $this->mfaProvider->getIdentifier(),
                    'failure' => true
                ]
            ));
        }
        $this->log('Multi-factor authentication successfull');
        // If verified, store this information in the session
        // and initiate a redirect back to the login view.
        $this->getBackendUser()->setAndSaveSessionData('mfa', true);
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('login'));
    }

    /**
     * Allow the user to cancel the multi-factor authentication by
     * calling logoff on the user object, to destroy the session and
     * other already gathered information and finally initiate a
     * redirect back to the login.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    public function cancelAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->log('Multi-factor authentication canceled');
        $this->getBackendUser()->logoff();
        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('login'));
    }

    /**
     * Initialize the action by fetching the requested provider by its identifier
     *
     * @param ServerRequestInterface $request
     */
    protected function initializeAction(ServerRequestInterface $request): void
    {
        $identifier = (string)($request->getQueryParams()['identifier'] ?? $request->getParsedBody()['identifier'] ?? '');
        // Check if given identifier is valid
        if ($this->isValidIdentifier($identifier)) {
            $provider = $this->mfaProviderRegistry->getProvider($identifier);
            // Only add provider if it was activated by the current user
            if ($provider->isActive(MfaProviderPropertyManager::create($provider, $this->getBackendUser()))) {
                $this->mfaProvider = $provider;
            }
        }
    }

    /**
     * Fetch alternative (activated and allowed) providers for the user to chose from
     *
     * @return ProviderInterface[]
     */
    protected function getAlternativeProviders(): array
    {
        return array_filter($this->allowedProviders, function ($provider) {
            return $provider !== $this->mfaProvider
                && $provider->isActive(MfaProviderPropertyManager::create($provider, $this->getBackendUser()));
        });
    }

    /**
     * Log debug information for MFA events
     *
     * @param string $message
     * @param array $additionalData
     */
    protected function log(string $message, array $additionalData = []): void
    {
        $user = $this->getBackendUser();
        $context = [
            'user' => [
                'uid' => $user->user[$user->userid_column],
                'username' => $user->user[$user->username_column]
            ]
        ];
        if ($this->mfaProvider !== null) {
            $context['provider'] = $this->mfaProvider->getIdentifier();
            $context['isProviderLocked'] = $this->mfaProvider->isLocked(
                MfaProviderPropertyManager::create($this->mfaProvider, $user)
            );
        }
        $this->logger->debug($message, array_replace_recursive($context, $additionalData));
    }
}
