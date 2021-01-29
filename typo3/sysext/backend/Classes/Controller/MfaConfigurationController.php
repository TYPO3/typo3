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
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller to configure MFA providers in the backend
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class MfaConfigurationController extends AbstractMfaController
{
    protected array $allowedActions = ['overview', 'setup', 'activate', 'deactivate', 'unlock', 'edit', 'save'];

    /**
     * Main entry point, checking prerequisite, initializing and setting
     * up the view and finally dispatching to the requested action.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = (string)($request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'overview');

        if (!$this->isActionAllowed($action)) {
            return new HtmlResponse('Action not allowed', 400);
        }

        $this->initializeAction($request);
        // All actions expect "overview" require a provider to deal with.
        // If non is found at this point, initiate a redirect to the overview.
        if ($this->mfaProvider === null && $action !== 'overview') {
            $this->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:providerNotFound'), '', FlashMessage::ERROR);
            return new RedirectResponse($this->getActionUri('overview'));
        }
        $this->initializeView($action);

        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Setup the overview with all available MFA providers
     *
     * @param ServerRequestInterface $request
     */
    public function overviewAction(ServerRequestInterface $request): void
    {
        $this->addOverviewButtons($request);
        $this->view->assignMultiple([
            'providers' => $this->allowedProviders,
            'defaultProvider' => $this->getDefaultProviderIdentifier(),
            'recommendedProvider' => $this->getRecommendedProviderIdentifier(),
            'setupRequired' => $this->mfaRequired && !$this->mfaProviderRegistry->hasActiveProviders($this->getBackendUser())
        ]);
    }

    /**
     * Render form to setup a provider by using provider specific content
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function setupAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->addFormButtons();
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        $providerResponse = $this->mfaProvider->handleRequest($request, $propertyManager, MfaViewType::SETUP);
        $this->view->assignMultiple([
            'provider' => $this->mfaProvider,
            'providerContent' => $providerResponse->getBody()
        ]);
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Handle activate request, receiving from the setup view
     * by forwarding the request to the appropriate provider.
     * Furthermore, add the provider as default provider in case
     * it is the recommended provider for this user, or no default
     * provider is yet defined the newly activated provider is allowed
     * to be a default provider and there are no other providers which
     * would suite as default provider.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function activateAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $isRecommendedProvider = $this->getRecommendedProviderIdentifier() === $this->mfaProvider->getIdentifier();
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $backendUser);
        if (!$this->mfaProvider->activate($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:activate.failure'), $this->getLocalizedProviderTitle()), '', FlashMessage::ERROR);
            return new RedirectResponse($this->getActionUri('setup', ['identifier' => $this->mfaProvider->getIdentifier()]));
        }
        if ($isRecommendedProvider
            || (
                $this->getDefaultProviderIdentifier() === ''
                && $this->mfaProvider->isDefaultProviderAllowed()
                && !$this->hasSuitableDefaultProviders([$this->mfaProvider->getIdentifier()])
            )
        ) {
            $this->setDefaultProvider();
        }
        // If this is the first activated provider, the user has logged in without being required
        // to pass the MFA challenge. Therefore no session entry exists. To prevent the challenge
        // from showing up after the activation we need to set the session data here.
        if (!(bool)($backendUser->getSessionData('mfa') ?? false)) {
            $backendUser->setSessionData('mfa', true);
        }
        $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:activate.success'), $this->getLocalizedProviderTitle()), '', FlashMessage::OK);
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Handle deactivate request by forwarding the request to the
     * appropriate provider. Also remove the provider as default
     * provider from user UC, if set.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function deactivateAction(ServerRequestInterface $request): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        if (!$this->mfaProvider->deactivate($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:deactivate.failure'), $this->getLocalizedProviderTitle()), '', FlashMessage::ERROR);
        } else {
            if ($this->isDefaultProvider()) {
                $this->removeDefaultProvider();
            }
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:deactivate.success'), $this->getLocalizedProviderTitle()), '', FlashMessage::OK);
        }
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Handle unlock request by forwarding the request to the appropriate provider
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function unlockAction(ServerRequestInterface $request): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        if (!$this->mfaProvider->unlock($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:unlock.failure'), $this->getLocalizedProviderTitle()), '', FlashMessage::ERROR);
        } else {
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:unlock.success'), $this->getLocalizedProviderTitle()), '', FlashMessage::OK);
        }
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Render form to edit a provider by using provider specific content
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->addFormButtons();
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        $providerResponse = $this->mfaProvider->handleRequest($request, $propertyManager, MfaViewType::EDIT);
        $this->view->assignMultiple([
            'provider' => $this->mfaProvider,
            'providerContent' => $providerResponse->getBody(),
            'isDefaultProvider' => $this->isDefaultProvider()
        ]);
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Handle save request, receiving from the edit view by
     * forwarding the request to the appropriate provider.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($this->mfaProvider, $this->getBackendUser());
        if (!$this->mfaProvider->update($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:save.failure'), $this->getLocalizedProviderTitle()), '', FlashMessage::ERROR);
        } else {
            if ((bool)($request->getParsedBody()['defaultProvider'] ?? false)) {
                $this->setDefaultProvider();
            } elseif ($this->isDefaultProvider()) {
                $this->removeDefaultProvider();
            }
            $this->addFlashMessage(sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:save.success'), $this->getLocalizedProviderTitle()), '', FlashMessage::OK);
        }
        return new RedirectResponse($this->getActionUri('edit', ['identifier' => $this->mfaProvider->getIdentifier()]));
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
            $this->mfaProvider = $this->mfaProviderRegistry->getProvider($identifier);
        }
    }

    /**
     * Initialize the standalone view and set the template name
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/Mfa']);
        $this->view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $this->view->setTemplate($templateName);
    }

    /**
     * Build a uri for the current controller based on the
     * given action, respecting additional parameters.
     *
     * @param string $action
     * @param array  $additionalParameters
     *
     * @return UriInterface
     */
    protected function getActionUri(string $action, array $additionalParameters = []): UriInterface
    {
        if (!$this->isActionAllowed($action)) {
            $action = 'overview';
        }
        return $this->uriBuilder->buildUriFromRoute('mfa', array_merge(['action' => $action], $additionalParameters));
    }

    /**
     * Check if there are more suitable default providers for the current user
     *
     * @param array $excludedProviders
     * @return bool
     */
    protected function hasSuitableDefaultProviders(array $excludedProviders = []): bool
    {
        foreach ($this->allowedProviders as $identifier => $provider) {
            if (!in_array($identifier, $excludedProviders, true)
                && $provider->isDefaultProviderAllowed()
                && $provider->isActive(MfaProviderPropertyManager::create($provider, $this->getBackendUser()))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the default provider
     *
     * @return string The identifier of the default provider
     */
    protected function getDefaultProviderIdentifier(): string
    {
        $defaultProviderIdentifier = (string)($this->getBackendUser()->uc['mfa']['defaultProvider'] ?? '');
        // The default provider value is only valid, if the corresponding provider exist and is allowed
        if ($this->isValidIdentifier($defaultProviderIdentifier)) {
            $defaultProvider = $this->mfaProviderRegistry->getProvider($defaultProviderIdentifier);
            $propertyManager = MfaProviderPropertyManager::create($defaultProvider, $this->getBackendUser());
            // Also check if the provider is activated for the user
            if ($defaultProvider->isActive($propertyManager)) {
                return $defaultProviderIdentifier;
            }
        }

        // If the stored provider is not valid, clean up the UC
        $this->removeDefaultProvider();
        return '';
    }

    /**
     * Get the recommended provider
     *
     * @return string The identifier of the recommended provider
     */
    protected function getRecommendedProviderIdentifier(): string
    {
        $recommendedProviderIdentifier = (string)($this->mfaTsConfig['recommendedProvider'] ?? '');
        // Check if valid and allowed to be default provider, which is obviously a prerequisite
        if (!$this->isValidIdentifier($recommendedProviderIdentifier)
            || !$this->mfaProviderRegistry->getProvider($recommendedProviderIdentifier)->isDefaultProviderAllowed()
        ) {
            // If the provider, defined in user TSconfig is not valid or is not set, check the globally defined
            $recommendedProviderIdentifier = (string)($GLOBALS['TYPO3_CONF_VARS']['BE']['recommendedMfaProvider'] ?? '');
            if (!$this->isValidIdentifier($recommendedProviderIdentifier)
                || !$this->mfaProviderRegistry->getProvider($recommendedProviderIdentifier)->isDefaultProviderAllowed()
            ) {
                // If also not valid or not set, return
                return '';
            }
        }

        $provider = $this->mfaProviderRegistry->getProvider($recommendedProviderIdentifier);
        $propertyManager = MfaProviderPropertyManager::create($provider, $this->getBackendUser());
        // If the defined recommended provider is valid, check if it is not yet activated
        return !$provider->isActive($propertyManager) ? $recommendedProviderIdentifier : '';
    }

    protected function isDefaultProvider(): bool
    {
        return $this->getDefaultProviderIdentifier() === $this->mfaProvider->getIdentifier();
    }

    protected function setDefaultProvider(): void
    {
        $this->getBackendUser()->uc['mfa']['defaultProvider'] = $this->mfaProvider->getIdentifier();
        $this->getBackendUser()->writeUC();
    }

    protected function removeDefaultProvider(): void
    {
        $this->getBackendUser()->uc['mfa']['defaultProvider'] = '';
        $this->getBackendUser()->writeUC();
    }

    protected function addFlashMessage(string $message, string $title = '', int $severity = FlashMessage::INFO): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function addOverviewButtons(ServerRequestInterface $request): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        if (($returnUrl = $this->getReturnUrl($request)) !== '') {
            $button = $buttonBar
                ->makeLinkButton()
                ->setHref($returnUrl)
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setShowLabelText(true);
            $buttonBar->addButton($button);
        }

        $reloadButton = $buttonBar
            ->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function addFormButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        $closeButton = $buttonBar
            ->makeLinkButton()
            ->setHref($this->uriBuilder->buildUriFromRoute('mfa', ['action' => 'overview']))
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setShowLabelText(true)
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton);

        $saveButton = $buttonBar
            ->makeInputButton()
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setName('save')
            ->setValue('1')
            ->setShowLabelText(true)
            ->setForm('mfaConfigurationController')
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    protected function getReturnUrl(ServerRequestInterface $request): string
    {
        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            $request->getQueryParams()['returnUrl'] ?? $request->getParsedBody()['returnUrl'] ?? ''
        );

        if ($returnUrl === '' && ExtensionManagementUtility::isLoaded('setup')) {
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('user_setup');
        }

        return $returnUrl;
    }
}
