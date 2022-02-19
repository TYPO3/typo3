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
use TYPO3\CMS\Backend\Domain\Repository\TableManualRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Main "CSH help" module controller
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class HelpController
{
    protected const ALLOWED_ACTIONS = ['index', 'all', 'detail'];

    /**
     * Section identifiers
     */
    const FULL = 0;

    /**
     * Show only Table of contents
     */
    const TOC_ONLY = 1;

    /** @var ModuleTemplate */
    protected $moduleTemplate;

    /** @var ViewInterface */
    protected $view;

    protected Typo3Information $typo3Information;
    protected TableManualRepository $tableManualRepository;
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        Typo3Information $typo3Information,
        TableManualRepository $tableManualRepository,
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->typo3Information = $typo3Information;
        $this->tableManualRepository = $tableManualRepository;
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request, and renders correct action
     *
     * @param ServerRequestInterface $request the current request
     * @param bool $addBackButton
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request, bool $addBackButton = true): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'index';

        if ($action === 'detail') {
            $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
            if (!$table) {
                return new RedirectResponse((string)$this->uriBuilder->buildUriFromRoute('help_cshmanual', [
                    'action' => 'index',
                ]), 303);
            }
        }

        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return new HtmlResponse('Action not allowed', 400);
        }

        $this->initializeView($action);

        $result = $this->{$action . 'Action'}($request);
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        $this->registerDocHeaderButtons($request, $addBackButton);

        $this->moduleTemplate->setTitle($this->getShortcutTitle($request));
        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates/ContextSensitiveHelp']);
        $this->view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $this->view->getRequest()->setControllerExtensionName('Backend');
        $this->view->assign('copyright', $this->typo3Information->getCopyrightNotice());
    }

    public function handleDetailPopup(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handleRequest($request, false);
    }

    /**
     * Show table of contents
     */
    public function indexAction()
    {
        $this->view->assign('toc', $this->tableManualRepository->getSections(self::TOC_ONLY));
    }

    /**
     * Show the table of contents and all manuals
     */
    public function allAction()
    {
        $this->view->assign('all', $this->tableManualRepository->getSections(self::FULL));
    }

    /**
     * Show a single manual
     *
     * @param ServerRequestInterface $request
     */
    public function detailAction(ServerRequestInterface $request)
    {
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';

        $this->view->assignMultiple([
            'table' => $table,
            'key' => $table,
            'field' => $field,
            'manuals' => $this->getManuals($request),
        ]);
    }

    /**
     * Registers the Icons into the docheader
     */
    protected function registerDocHeaderButtons(ServerRequestInterface $request, bool $addBackButton = true)
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $action = $request->getQueryParams()['action'] ?? $request->getParsedBody()['action'] ?? 'index';
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('help_cshmanual')
            ->setDisplayName($this->getShortcutTitle($request))
            ->setArguments([
                'action' => $action,
                'table' => $request->getQueryParams()['table'] ?? '',
                'field' => $request->getQueryParams()['field'] ?? '',
            ]);
        $buttonBar->addButton($shortcutButton);

        if ($action !== 'index' && $addBackButton) {
            $backButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('help_cshmanual'));
            $buttonBar->addButton($backButton);
        }
    }

    protected function getManuals(ServerRequestInterface $request): array
    {
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'] ?? '';
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';

        return $field === '*'
            ? $this->tableManualRepository->getTableManual($table)
            : [$this->tableManualRepository->getSingleManual($table, $field)];
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getShortcutTitle(ServerRequestInterface $request): string
    {
        $title = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_mod_help_cshmanual.xlf:mlang_labels_tablabel');
        if (($manuals = $this->getManuals($request)) !== []) {
            $manualTitle = array_shift($manuals)['headerLine'] ?? '';
            if ($manualTitle !== '') {
                $title .= ': ' . $manualTitle;
            }
        }
        return $title;
    }

    /**
     * Returns the currently logged in BE user
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
