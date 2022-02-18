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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;

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

    public function __construct(
        protected readonly Typo3Information $typo3Information,
        protected readonly TableManualRepository $tableManualRepository,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
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
        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($this->getShortcutTitle($request));
        return $this->{$action . 'Action'}($view, $request);
    }

    /**
     * Show table of contents
     */
    protected function indexAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $view->assignMultiple([
            'copyright' => $this->typo3Information->getCopyrightNotice(),
            'toc' => $this->tableManualRepository->getSections(self::TOC_ONLY),
        ]);
        $this->addShortcutButton($view, $request);
        return $view->renderResponse('ContextSensitiveHelp/Index');
    }

    /**
     * Show the table of contents and all manuals
     */
    protected function allAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $view->assignMultiple([
            'copyright' => $this->typo3Information->getCopyrightNotice(),
            'all' => $this->tableManualRepository->getSections(self::FULL),
        ]);
        $this->addShortcutButton($view, $request);
        $this->addBackButton($view);
        return $view->renderResponse('ContextSensitiveHelp/All');
    }

    /**
     * Show a single manual
     */
    protected function detailAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $table = $request->getQueryParams()['table'] ?? $request->getParsedBody()['table'];
        $field = $request->getQueryParams()['field'] ?? $request->getParsedBody()['field'] ?? '*';
        $view->assignMultiple([
            'copyright' => $this->typo3Information->getCopyrightNotice(),
            'table' => $table,
            'key' => $table,
            'field' => $field,
            'manuals' => $this->getManuals($request),
        ]);
        $this->addShortcutButton($view, $request);
        $this->addBackButton($view);
        return $view->renderResponse('ContextSensitiveHelp/Detail');
    }

    protected function addShortcutButton(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
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

    protected function addBackButton(ModuleTemplate $view): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
