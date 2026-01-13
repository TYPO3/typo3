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

namespace TYPO3\CMS\Redirects\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Redirects\Service\ModulePaginationService;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;

/**
 * Lists all redirects in the TYPO3 Backend as a module.
 *
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
#[AsController]
class ManagementController
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
        protected RedirectRepository $redirectRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        private EventDispatcherInterface $eventDispatcher,
        protected ComponentFactory $componentFactory,
        protected ModulePaginationService $modulePaginationService,
    ) {}

    /**
     * Injects the request object for the current request, and renders the overview of all redirects
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = Demand::fromRequest($request);
        $redirectType = $demand->getRedirectType();

        $view->setTitle(
            $this->getLanguageService()->translate('title', 'redirects.modules.redirects')
        );
        $view->makeDocHeaderModuleMenu();
        $this->registerDocHeaderButtons($view);

        if (!$this->canListRedirects()) {
            return $view->renderResponse('Management/Overview');
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRedirectManagementControllerViewDataEvent(
                $demand,
                $this->redirectRepository->findRedirectsByDemand($demand),
                $this->redirectRepository->findHostsOfRedirects($redirectType),
                $this->redirectRepository->findStatusCodesOfRedirects($redirectType),
                $this->redirectRepository->findCreationTypes($redirectType),
                GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('redirects.hitCount'),
                $view,
                $request,
                $this->redirectRepository->findIntegrityStatusCodes($redirectType),
            )
        );
        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $pagination = $this->modulePaginationService->preparePagination($demand);
        $languageService = $this->getLanguageService();
        $view = $event->getView();
        $hasEditPermissions = $this->canEditRedirects();
        $view->assignMultiple([
            'redirects' => $event->getRedirects(),
            'hosts' => $event->getHosts(),
            'statusCodes' => $event->getStatusCodes(),
            'creationTypes' => $event->getCreationTypes(),
            'integrityStatusCodes' => $event->getIntegrityStatusCodes(),
            'defaultIntegrityStatus' => RedirectConflict::NO_CONFLICT,
            'demand' => $event->getDemand(),
            'showHitCounter' => $event->getShowHitCounter(),
            'pagination' => $pagination,
            'canEditRedirects' => $hasEditPermissions,
            'canListRedirects' => true,
            'returnUrl' => $this->uriBuilder->buildUriFromRoute('redirects', [
                'page' => $pagination['current'],
                'demand' =>  $demand->getParameters(),
                'orderField' => $demand->getOrderField(),
                'orderDirection' => $demand->getOrderDirection(),
            ]),
            'actions' => $hasEditPermissions ? [
                new Action(
                    'edit',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_redirect',
                        'returnUrl' => $requestUri,
                    ],
                    'actions-open',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit'
                ),
                new Action(
                    'delete',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_redirect',
                        'title' => $languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:labels.delete.title'),
                        'content' => $languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:labels.delete.message'),
                        'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                        'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                        'returnUrl' => $requestUri,
                    ],
                    'actions-edit-delete',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
                ),
            ] : [],
        ]);
        return $view->renderResponse('Management/Overview');
    }

    protected function canListRedirects(): bool
    {
        return $this->getBackendUser()->check('tables_select', 'sys_redirect');
    }

    protected function canEditRedirects(): bool
    {
        return $this->getBackendUser()->check('tables_modify', 'sys_redirect');
    }

    /**
     * Create document header buttons
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();

        // Create new
        if ($this->canEditRedirects()) {
            $newRecordButton = $this->componentFactory->createLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => ['sys_redirect' => ['new']],
                        'module' => 'redirects',
                        'defVals' => [
                            'sys_redirect' => [
                                'redirect_type' => Demand::DEFAULT_REDIRECT_TYPE,
                            ],
                        ],
                        'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('redirects'),
                    ]
                ))
                ->setTitle($languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:redirect_add_text'))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
            $view->getDocHeaderComponent()->getButtonBar()->addButton($newRecordButton);
        }

        // Shortcut
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'redirects',
            displayName: $languageService->translate('short_description', 'redirects.modules.redirects')
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
