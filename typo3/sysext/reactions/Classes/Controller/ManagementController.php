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

namespace TYPO3\CMS\Reactions\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\MultiRecordSelection\Action;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Reactions\Pagination\DemandedArrayPaginator;
use TYPO3\CMS\Reactions\ReactionRegistry;
use TYPO3\CMS\Reactions\Repository\ReactionDemand;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;

/**
 * The System > Reaction module: Rendering the listing of reactions.
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
        private readonly ReactionRegistry $reactionRegistry,
        private readonly ReactionRepository $reactionRepository
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = ReactionDemand::fromRequest($request);

        $this->registerDocHeaderButtons($view, $request->getAttribute('normalizedParams')->getRequestUri(), $demand);

        $reactionRecords = $this->reactionRepository->getReactionRecords($demand);
        $paginator = new DemandedArrayPaginator($reactionRecords, $demand->getPage(), $demand->getLimit(), $this->reactionRepository->countAll());
        $pagination = new SimplePagination($paginator);

        $requestUri = $request->getAttribute('normalizedParams')->getRequestUri();
        $languageService = $this->getLanguageService();

        return $view->assignMultiple([
            'demand' => $demand,
            'reactionTypes' => iterator_to_array($this->reactionRegistry->getAvailableReactionTypes()),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'reactionRecords' => $reactionRecords,
            'actions' => [
                new Action(
                    'edit',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_reaction',
                        'returnUrl' => $requestUri,
                    ],
                    'actions-open',
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit'
                ),
                new Action(
                    'delete',
                    [
                        'idField' => 'uid',
                        'tableName' => 'sys_reaction',
                        'title' => $languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf:labels.delete.title'),
                        'content' => $languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf:labels.delete.message'),
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

    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri, ReactionDemand $demand): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Create new
        $newRecordButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => ['sys_reaction' => ['new']],
                    'returnUrl' => (string)$this->uriBuilder->buildUriFromRoute('system_reactions'),
                ]
            ))
            ->setShowLabelText(true)
            ->setTitle($languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf:reaction_create'))
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
            ->setRouteIdentifier('system_reactions')
            ->setDisplayName($languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf:mlang_labels_tablabel'))
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
