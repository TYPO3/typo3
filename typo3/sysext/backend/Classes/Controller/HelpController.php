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
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * Main "CSH help" module controller.
 *
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
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $action = $request->getQueryParams()['action'] ?? 'index';
        if ($action === 'detail') {
            $table = $request->getQueryParams()['table'] ?? '';
            if (!$table) {
                return new RedirectResponse((string)$this->uriBuilder->buildUriFromRoute('help_cshmanual', [
                    'action' => 'index',
                ]), 303);
            }
        }
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return new HtmlResponse('Action not allowed', 400);
        }
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle($this->getShortcutTitle($request));
        return $this->{$action . 'Action'}($moduleTemplate, $request);
    }

    /**
     * Show table of contents
     */
    protected function indexAction(ModuleTemplate $moduleTemplate, ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $view->assign('toc', $this->tableManualRepository->getSections(self::TOC_ONLY));
        $moduleTemplate->setContent($view->render('ContextSensitiveHelp/Index'));
        $this->addShortcutButton($moduleTemplate, $request);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Show the table of contents and all manuals
     */
    protected function allAction(ModuleTemplate $moduleTemplate, ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $view->assign('all', $this->tableManualRepository->getSections(self::FULL));
        $moduleTemplate->setContent($view->render('ContextSensitiveHelp/All'));
        $this->addShortcutButton($moduleTemplate, $request);
        $this->addBackButton($moduleTemplate);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Show a single manual
     */
    protected function detailAction(ModuleTemplate $moduleTemplate, ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView();
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';
        $view->assignMultiple([
            'table' => $table,
            'key' => $table,
            'field' => $field,
            'manuals' => $this->getManuals($request),
        ]);
        $moduleTemplate->setContent($view->render('ContextSensitiveHelp/Detail'));
        $this->addShortcutButton($moduleTemplate, $request);
        $this->addBackButton($moduleTemplate);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    protected function addShortcutButton(ModuleTemplate $moduleTemplate, ServerRequestInterface $request): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $action = $request->getQueryParams()['action'] ?? 'index';
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('help_cshmanual')
            ->setDisplayName($this->getShortcutTitle($request))
            ->setArguments([
                'action' => $action,
                'table' => $request->getQueryParams()['table'] ?? '',
                'field' => $request->getQueryParams()['field'] ?? '',
            ]);
        $buttonBar->addButton($shortcutButton);
    }

    protected function addBackButton(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:back'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('help_cshmanual'));
        $buttonBar->addButton($backButton);
    }

    protected function getManuals(ServerRequestInterface $request): array
    {
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'] ?? '';
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';
        return $field === '*'
            ? $this->tableManualRepository->getTableManual($table)
            : [$this->tableManualRepository->getSingleManual($table, $field)];
    }

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

    protected function initializeView(): BackendTemplateView
    {
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:backend/Resources/Private/Templates']);
        $view->setPartialRootPaths(['EXT:backend/Resources/Private/Partials']);
        $view->setLayoutRootPaths(['EXT:backend/Resources/Private/Layouts']);
        $view->assign('copyright', $this->typo3Information->getCopyrightNotice());
        return $view;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
