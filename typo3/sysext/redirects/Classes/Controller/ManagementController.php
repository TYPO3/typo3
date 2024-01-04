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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;

/**
 * Lists all redirects in the TYPO3 Backend as a module.
 *
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
class ManagementController
{
    public function __construct(
        protected UriBuilder $uriBuilder,
        protected IconFactory $iconFactory,
        protected RedirectRepository $redirectRepository,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Injects the request object for the current request, and renders the overview of all redirects
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = Demand::fromRequest($request);

        $view->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:mlang_tabs_tab')
        );
        $this->registerDocHeaderButtons($view, $request->getAttribute('normalizedParams')->getRequestUri());

        $event = $this->eventDispatcher->dispatch(
            new ModifyRedirectManagementControllerViewDataEvent(
                $demand,
                $this->redirectRepository->findRedirectsByDemand($demand),
                $this->redirectRepository->findHostsOfRedirects(),
                $this->redirectRepository->findStatusCodesOfRedirects(),
                $this->redirectRepository->findCreationTypes(),
                GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('redirects.hitCount'),
                $view,
                $request,
            )
        );
        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();
        $view = $event->getView();
        $view->assignMultiple([
            'redirects' => $event->getRedirects(),
            'hosts' => $event->getHosts(),
            'statusCodes' => $event->getStatusCodes(),
            'creationTypes' => $event->getCreationTypes(),
            'demand' => $event->getDemand(),
            'showHitCounter' => $event->getShowHitCounter(),
            'pagination' => $this->preparePagination($event->getDemand()),
            'actions' => [
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
                        'title' => $languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_reactions.xlf:labels.delete.title'),
                        'content' => $languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_reactions.xlf:labels.delete.message'),
                        'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                        'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                        'returnUrl' => $requestUri,
                    ],
                    'actions-edit-delete',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
                ),
            ],
        ]);
        return $view->renderResponse('Management/Overview');
    }

    /**
     * Prepares information for the pagination of the module
     */
    protected function preparePagination(Demand $demand): array
    {
        $count = $this->redirectRepository->countRedirectsByByDemand($demand);
        $numberOfPages = ceil($count / $demand->getLimit());
        $endRecord = $demand->getOffset() + $demand->getLimit();
        if ($endRecord > $count) {
            $endRecord = $count;
        }

        $pagination = [
            'current' => $demand->getPage(),
            'numberOfPages' => $numberOfPages,
            'hasLessPages' => $demand->getPage() > 1,
            'hasMorePages' => $demand->getPage() < $numberOfPages,
            'startRecord' => $demand->getOffset() + 1,
            'endRecord' => $endRecord,
        ];
        if ($pagination['current'] < $pagination['numberOfPages']) {
            $pagination['nextPage'] = $pagination['current'] + 1;
        }
        if ($pagination['current'] > 1) {
            $pagination['previousPage'] = $pagination['current'] - 1;
        }
        return $pagination;
    }

    /**
     * Create document header buttons
     */
    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Create new
        $newRecordButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_redirect' => ['new'],
                    ],
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('site_redirects'),
                ]
            ))
            ->setTitle($languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:redirect_add_text'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL));
        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($requestUri)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('site_redirects')
            ->setDisplayName($languageService->sL('LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:mlang_labels_tablabel'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
