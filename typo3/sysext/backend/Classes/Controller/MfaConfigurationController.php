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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderManifestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
    private array $providerActionsWhenInactive = ['setup', 'activate'];
    private array $providerActionsWhenActive = ['deactivate', 'unlock', 'edit', 'save'];

    protected IconFactory $iconFactory;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        MfaProviderRegistry $mfaProviderRegistry,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        parent::__construct($uriBuilder, $mfaProviderRegistry, $moduleTemplateFactory);
    }

    /**
     * Main entry point, checking prerequisite, initializing and setting
     * up the view and finally dispatching to the requested action.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $action = (string)($request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'overview');

        if (!$this->isActionAllowed($action)) {
            return new HtmlResponse('Action not allowed', 400);
        }

        $mfaProvider = null;
        $identifier = (string)($request->getQueryParams()['identifier'] ?? $request->getParsedBody()['identifier'] ?? '');
        // Check if given identifier is valid
        if ($this->isValidIdentifier($identifier)) {
            $mfaProvider = $this->mfaProviderRegistry->getProvider($identifier);
        }
        // All actions expect "overview" require a provider to deal with.
        // If non is found at this point, initiate a redirect to the overview.
        if ($mfaProvider === null && $action !== 'overview') {
            $this->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:providerNotFound'), '', FlashMessage::ERROR);
            return new RedirectResponse($this->getActionUri('overview'));
        }
        // If a valid provider is given, check if the requested action can be performed on this provider
        if ($mfaProvider !== null) {
            $isProviderActive = $mfaProvider->isActive(
                MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser())
            );
            // Some actions require the provider to be inactive
            if ($isProviderActive && in_array($action, $this->providerActionsWhenInactive, true)) {
                $this->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:providerActive'), '', FlashMessage::ERROR);
                return new RedirectResponse($this->getActionUri('overview'));
            }
            // Some actions require the provider to be active
            if (!$isProviderActive && in_array($action, $this->providerActionsWhenActive, true)) {
                $this->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:providerNotActive'), '', FlashMessage::ERROR);
                return new RedirectResponse($this->getActionUri('overview'));
            }
        }

        switch ($action) {
            case 'overview':
                return $this->overviewAction($request, $this->initializeView($action));
            case 'setup':
            case 'edit':
                return $this->{$action . 'Action'}($request, $mfaProvider, $this->initializeView($action));
            case 'activate':
            case 'deactivate':
            case 'unlock':
            case 'save':
                return $this->{$action . 'Action'}($request, $mfaProvider);
            default:
                return new HtmlResponse('Action not allowed', 400);
        }
    }

    /**
     * Setup the overview with all available MFA providers
     */
    public function overviewAction(ServerRequestInterface $request, StandaloneView $view): ResponseInterface
    {
        $this->addOverviewButtons($request);
        $view->assignMultiple([
            'providers' => $this->allowedProviders,
            'defaultProvider' => $this->getDefaultProviderIdentifier(),
            'recommendedProvider' => $this->getRecommendedProviderIdentifier(),
            'setupRequired' => $this->mfaRequired && !$this->mfaProviderRegistry->hasActiveProviders($this->getBackendUser()),
        ]);
        $this->moduleTemplate->setContent($view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Render form to setup a provider by using provider specific content
     */
    public function setupAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider, StandaloneView $view): ResponseInterface
    {
        $this->addFormButtons();
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $providerResponse = $mfaProvider->handleRequest($request, $propertyManager, MfaViewType::SETUP);
        $view->assignMultiple([
            'provider' => $mfaProvider,
            'providerContent' => $providerResponse->getBody(),
        ]);
        $this->moduleTemplate->setContent($view->render());
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
     */
    public function activateAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $isRecommendedProvider = $this->getRecommendedProviderIdentifier() === $mfaProvider->getIdentifier();
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $backendUser);
        $languageService = $this->getLanguageService();
        // Check whether activation operation was successful and the provider is now active.
        if (!$mfaProvider->activate($request, $propertyManager) || !$mfaProvider->isActive($propertyManager)) {
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:activate.failure'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::ERROR);
            return new RedirectResponse($this->getActionUri('setup', ['identifier' => $mfaProvider->getIdentifier()]));
        }
        if ($isRecommendedProvider
            || (
                $this->getDefaultProviderIdentifier() === ''
                && $mfaProvider->isDefaultProviderAllowed()
                && !$this->hasSuitableDefaultProviders([$mfaProvider->getIdentifier()])
            )
        ) {
            $this->setDefaultProvider($mfaProvider);
        }
        // If this is the first activated provider, the user has logged in without being required
        // to pass the MFA challenge. Therefore no session entry exists. To prevent the challenge
        // from showing up after the activation we need to set the session data here.
        if (!(bool)($backendUser->getSessionData('mfa') ?? false)) {
            $backendUser->setSessionData('mfa', true);
        }
        $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:activate.success'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::OK);
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Handle deactivate request by forwarding the request to the
     * appropriate provider. Also remove the provider as default
     * provider from user UC, if set.
     */
    public function deactivateAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $languageService = $this->getLanguageService();
        if (!$mfaProvider->deactivate($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:deactivate.failure'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::ERROR);
        } else {
            if ($this->isDefaultProvider($mfaProvider)) {
                $this->removeDefaultProvider();
            }
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:deactivate.success'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::OK);
        }
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Handle unlock request by forwarding the request to the appropriate provider
     */
    public function unlockAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $languageService = $this->getLanguageService();
        if (!$mfaProvider->unlock($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:unlock.failure'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::ERROR);
        } else {
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:unlock.success'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::OK);
        }
        return new RedirectResponse($this->getActionUri('overview'));
    }

    /**
     * Render form to edit a provider by using provider specific content
     */
    public function editAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider, StandaloneView $view): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        if ($mfaProvider->isLocked($propertyManager)) {
            // Do not show edit view for locked providers
            $this->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:providerIsLocked'), '', FlashMessage::ERROR);
            return new RedirectResponse($this->getActionUri('overview'));
        }
        $this->addFormButtons();
        $providerResponse = $mfaProvider->handleRequest($request, $propertyManager, MfaViewType::EDIT);
        $view->assignMultiple([
            'provider' => $mfaProvider,
            'providerContent' => $providerResponse->getBody(),
            'isDefaultProvider' => $this->isDefaultProvider($mfaProvider),
        ]);
        $this->moduleTemplate->setContent($view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Handle save request, receiving from the edit view by
     * forwarding the request to the appropriate provider.
     */
    public function saveAction(ServerRequestInterface $request, MfaProviderManifestInterface $mfaProvider): ResponseInterface
    {
        $propertyManager = MfaProviderPropertyManager::create($mfaProvider, $this->getBackendUser());
        $languageService = $this->getLanguageService();
        if (!$mfaProvider->update($request, $propertyManager)) {
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:save.failure'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::ERROR);
        } else {
            if ((bool)($request->getParsedBody()['defaultProvider'] ?? false)) {
                $this->setDefaultProvider($mfaProvider);
            } elseif ($this->isDefaultProvider($mfaProvider)) {
                $this->removeDefaultProvider();
            }
            $this->addFlashMessage(sprintf($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mfa.xlf:save.success'), $languageService->sL($mfaProvider->getTitle())), '', FlashMessage::OK);
        }
        if (!$mfaProvider->isActive($propertyManager)) {
            return new RedirectResponse($this->getActionUri('overview'));
        }
        return new RedirectResponse($this->getActionUri('edit', ['identifier' => $mfaProvider->getIdentifier()]));
    }

    /**
     * Initialize the standalone view and set the template name
     */
    protected function initializeView(string $templateName): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/Mfa']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->setTemplate($templateName);
        return $view;
    }

    /**
     * Build a uri for the current controller based on the
     * given action, respecting additional parameters.
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
     */
    protected function getRecommendedProviderIdentifier(): string
    {
        $recommendedProvider = $this->getRecommendedProvider();
        if ($recommendedProvider === null) {
            return '';
        }

        $propertyManager = MfaProviderPropertyManager::create($recommendedProvider, $this->getBackendUser());
        // If the defined recommended provider is valid, check if it is not yet activated
        return !$recommendedProvider->isActive($propertyManager) ? $recommendedProvider->getIdentifier() : '';
    }

    protected function isDefaultProvider(MfaProviderManifestInterface $mfaProvider): bool
    {
        return $this->getDefaultProviderIdentifier() === $mfaProvider->getIdentifier();
    }

    protected function setDefaultProvider(MfaProviderManifestInterface $mfaProvider): void
    {
        $this->getBackendUser()->uc['mfa']['defaultProvider'] = $mfaProvider->getIdentifier();
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
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setShowLabelText(true);
            $buttonBar->addButton($button);
        }

        $reloadButton = $buttonBar
            ->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function addFormButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $lang = $this->getLanguageService();

        $closeButton = $buttonBar
            ->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('mfa', ['action' => 'overview']))
            ->setClasses('t3js-editform-close')
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton);

        $saveButton = $buttonBar
            ->makeInputButton()
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
            ->setName('save')
            ->setValue('1')
            ->setShowLabelText(true)
            ->setForm('mfaConfigurationController')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
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
