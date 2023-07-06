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

namespace TYPO3\CMS\Info\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent;

/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can add their third-level modules
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class InfoModuleController
{
    protected ModuleInterface $currentModule;
    protected ?ModuleTemplate $view;
    public array $pageinfo = [];

    /**
     * Value of the GET/POST var 'id' = the current page ID
     */
    protected int $id;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->id = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->currentModule = $request->getAttribute('module');
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $this->view->setTitle(
            $this->getLanguageService()->sL($this->currentModule->getTitle()),
            $this->id !== 0 && isset($this->pageinfo['title']) ? $this->pageinfo['title'] : ''
        );
        // The page will show only if there is a valid page and if this page
        // may be viewed by the user
        if ($this->pageinfo !== []) {
            $this->view->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        $accessContent = false;
        $backendUser = $this->getBackendUser();
        if (($this->id && $this->pageinfo !== []) || ($backendUser->isAdmin() && !$this->id)) {
            $accessContent = true;
            if (!$this->id && $backendUser->isAdmin()) {
                $this->pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            $this->view->assign('id', $this->id);
            $this->view->assign('formAction', (string)$this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier()));
            // Setting up the buttons and the module menu for the doc header
            $this->getButtons();
            $this->view->makeDocHeaderModuleMenu(['id' => $this->id]);
        }
        $event = $this->eventDispatcher->dispatch(
            new ModifyInfoModuleContentEvent($accessContent, $request, $this->currentModule, $this->view)
        );
        $this->view->assignMultiple([
            'accessContent' => $accessContent,
            'headerContent' => $event->getHeaderContent(),
            'footerContent' => $event->getFooterContent(),
        ]);
    }

    /**
     * Main action, should never be called, but works as a fallback if e.g. no submodule is accessible
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        return $this->view->renderResponse('Main');
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();

        if ($this->id) {
            // View
            $pagesTSconfig = BackendUtility::getPagesTSconfig($this->pageinfo['uid']);
            if (isset($pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'])) {
                $excludeDokTypes = GeneralUtility::intExplode(
                    ',',
                    (string)$pagesTSconfig['TCEMAIN.']['preview.']['disableButtonForDokType'],
                    true
                );
            } else {
                // exclude sysfolders and recycler by default
                $excludeDokTypes = [
                    PageRepository::DOKTYPE_RECYCLER,
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_SPACER,
                ];
            }
            if (!in_array((int)$this->pageinfo['doktype'], $excludeDokTypes, true)) {
                // View page
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageinfo['uid'])
                    ->withRootLine(BackendUtility::BEgetRootLine($this->pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setDisabled(!$previewDataAttributes)
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
                    ->setShowLabelText(true);
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT);
            }
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->currentModule->getIdentifier())
            ->setDisplayName($this->currentModule->getTitle())
            ->setArguments(['id' => $this->id]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
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
