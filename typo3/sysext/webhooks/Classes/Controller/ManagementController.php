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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Webhooks\Pagination\DemandedArrayPaginator;
use TYPO3\CMS\Webhooks\Repository\WebhookDemand;
use TYPO3\CMS\Webhooks\Repository\WebhookRepository;
use TYPO3\CMS\Webhooks\WebhookTypesRegistry;

/**
 * The System > Webhooks module: Rendering the listing of webhooks.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
class ManagementController
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly WebhookTypesRegistry $webhookTypesRegistry,
        private readonly WebhookRepository $webhookRepository
    ) {
    }

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = WebhookDemand::fromRequest($request);
        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();

        $this->registerDocHeaderButtons($view, $requestUri, $demand);

        $webhookRecords = $this->webhookRepository->getWebhookRecords($demand);
        $paginator = new DemandedArrayPaginator($webhookRecords, $demand->getPage(), $demand->getLimit(), $this->webhookRepository->countAll());
        $pagination = new SimplePagination($paginator);

        return $view->assignMultiple([
            'demand' => $demand,
            'webhookTypes' => $this->webhookTypesRegistry->getAvailableWebhookTypes(),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'webhookRecords' => $webhookRecords,
            'editActionConfiguration' => GeneralUtility::jsonEncodeForHtmlAttribute([
                'idField' => 'uid',
                'tableName' => 'sys_webhook',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]),
            'deleteActionConfiguration' => GeneralUtility::jsonEncodeForHtmlAttribute([
                'idField' => 'uid',
                'tableName' => 'sys_webhook',
                'title' => $languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/locallang_module_webhooks.xlf:labels.delete.title'),
                'content' => $languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/locallang_module_webhooks.xlf:labels.delete.message'),
                'ok' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                'cancel' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                'returnUrl' => $requestUri,
            ]),
        ])->renderResponse('Management/Overview');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri, WebhookDemand $demand): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Create new
        $newRecordButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_webhook' => ['new']],
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('webhooks_management'),
                ]
            ))
            ->setShowLabelText(true)
            ->setTitle($languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/locallang_module_webhooks.xlf:webhook_create'))
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($requestUri)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('webhooks_management')
            ->setDisplayName($languageService->sL('LLL:EXT:webhooks/Resources/Private/Language/locallang_module_webhooks.xlf:mlang_labels_tablabel'))
            ->setArguments(array_filter([
                'demand' => $demand->getParameters(),
                'orderField' => $demand->getOrderField(),
                'orderDirection' => $demand->getOrderDirection(),
            ]));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
