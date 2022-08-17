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
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $demand = ReactionDemand::fromRequest($request);

        $this->registerDocHeaderButtons($view, $request->getAttribute('normalizedParams')->getRequestUri());

        $reactionRecords = $this->reactionRepository->getReactionRecords($demand);
        $paginator = new DemandedArrayPaginator($reactionRecords, $demand->getPage(), $demand->getLimit(), $this->reactionRepository->countAll());
        $pagination = new SimplePagination($paginator);

        return $view->assignMultiple([
            'demand' => $demand,
            'reactionTypes' => $this->reactionRegistry->getAvailableReactionTypes(),
            'paginator' => $paginator,
            'pagination' => $pagination,
            'reactionRecords' => $reactionRecords,
        ])->renderResponse('Management/Overview');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, string $requestUri): void
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
            ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
        $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($requestUri)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        // @todo Demand should be respected for shortcuts
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_reactions')
            ->setDisplayName($languageService->sL('LLL:EXT:reactions/Resources/Private/Language/locallang_module_reactions.xlf:mlang_labels_tablabel'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
