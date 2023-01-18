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

namespace TYPO3\CMS\Backend\Controller\PageTsConfig;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * PageTsConfig > Active Page TsConfig
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
class PageTsConfigActiveController
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();

        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');

        $pageUid = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        if ($pageUid <= 0) {
            // Redirect to records overview if on page 0 or invalid uid.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('pagetsconfig_records'));
        }
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];

        $alphaSort = (bool)$moduleData->get('alphaSort');
        $pageTsConfig = BackendUtility::getPagesTSconfig($pageUid);
        if ($alphaSort) {
            $pageTsConfig = ArrayUtility::sortByKeyRecursive($pageTsConfig);
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'alphaSort' => $alphaSort,
            'pageTsConfig' => $pageTsConfig,
        ]);
        return $view->renderResponse('PageTsConfig/Active');
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_active'),
            BackendUtility::getRecordTitle('pages', $pageInfo),
            $pageUid
        );
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($moduleIdentifier)
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $pageUid]);
        $buttonBar->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
