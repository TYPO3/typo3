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

namespace TYPO3\CMS\Webhooks\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Webhooks\Pagination\DemandedArrayPaginator;
use TYPO3\CMS\Webhooks\Repository\WebhookDemand;
use TYPO3\CMS\Webhooks\Repository\WebhookRepository;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * The System > Webhooks module: Rendering the listing of webhooks.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class ManagementController
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly WebhookTypesRegistry $webhookTypesRegistry,
        private readonly WebhookRepository $webhookRepository,
        private readonly ComponentFactory $componentFactory,
    ) {}

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = WebhookDemand::fromRequest($request);
        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();

        $this->registerDocHeaderButtons($view, $requestUri, $demand);
        $view->makeDocHeaderModuleMenu();

        $webhookRecords = $this->webhookRepository->getWebhookRecords($demand);
        $paginator = new DemandedArrayPaginator($webhookRecords, $demand->getPage(), $demand->getLimit(), $this->webhookRepository->countAll($demand));
        $pagination = new SimplePagination($paginator);

        return $view->assignMultiple([
            'demand' => $demand,
            'webhookTypes' => $this->webhookTypesRegistry->getAvailableWebhookTypes(),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'actions' => [
                new Action(
                    'edit',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_webhook',
                        'returnUrl' => $requestUri,
                    ],
                    'actions-open',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit'
                ),
                new Action(
                    'delete',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_webhook',
                        'title' => $languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:labels.delete.title'),
                        'content' => $languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:labels.delete.message'),
                        'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                        'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                        'returnUrl' => $requestUri,
                    ],
                    'actions-edit-delete',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
                ),
            ],
        ])->renderResponse('Management/Overview');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri, WebhookDemand $demand): void
    {
        $languageService = $this->getLanguageService();

        // Create new
        $newRecordButton = $this->componentFactory->createLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_webhook' => ['new']],
                    'module' => 'integrations_webhooks',
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('integrations_webhooks'),
                ]
            ))
            ->setShowLabelText(true)
            ->setTitle($languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:webhook_create'))
            ->setIcon($this->iconFactory->getIcon('actions-add', IconSize::SMALL));
        $view->addButtonToButtonBar($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Reload
        $view->addButtonToButtonBar($this->componentFactory->createReloadButton($requestUri), ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $shortcutButton = $this->componentFactory->createShortcutButton()
            ->setRouteIdentifier('integrations_webhooks')
            ->setDisplayName($languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/Modules/webhooks.xlf:title'))
            ->setArguments(array_filter([
                'demand' => $demand->getParameters(),
                'orderField' => $demand->getOrderField(),
                'orderDirection' => $demand->getOrderDirection(),
            ]));
        $view->addButtonToButtonBar($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
