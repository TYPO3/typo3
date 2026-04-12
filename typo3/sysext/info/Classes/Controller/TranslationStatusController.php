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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\LanguageAwareSchemaCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Info\Controller\Event\ModifyInfoModuleContentEvent;

/**
 * Status -> Localization overview
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
readonly class TranslationStatusController
{
    public function __construct(
        private IconFactory $iconFactory,
        private UriBuilder $uriBuilder,
        private ModuleProvider $moduleProvider,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private EventDispatcherInterface $eventDispatcher,
        private TcaSchemaFactory $tcaSchemaFactory,
        private ComponentFactory $componentFactory,
        private ConnectionPool $connectionPool,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $module = $request->getAttribute('module');
        $moduleData = $request->getAttribute('moduleData');
        $currentSite = $request->getAttribute('site');
        $pageId = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);

        $pageinfo = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: [];
        $hasAccess = false;
        if (($pageId && $pageinfo !== []) || ($backendUser->isAdmin() && $pageId === 0)) {
            $hasAccess = true;
        }
        if ($pageId === 0 && $backendUser->isAdmin()) {
            $pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
        }

        $siteLanguages = $currentSite->getAvailableLanguages($backendUser, false, $pageId);
        $allowedModuleOptions = $this->getModuleOptions($siteLanguages);
        if ($moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $selectedDepth = (int)$moduleData->get('depth');
        $selectedLanguage = (int)$moduleData->get('lang');

        $mainContent = '';
        if ($pageId > 0) {
            $tree = $this->getTree($pageId, $selectedDepth);
            $mainContent = $this->renderL10nTable($tree, $request, $siteLanguages, $selectedLanguage);
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('hasAccess', $hasAccess);
        if ($hasAccess) {
            $view->setTitle($languageService->sL($module->getTitle()), $pageId !== 0 && isset($pageinfo['title']) ? $pageinfo['title'] : '');
            $view->getDocHeaderComponent()->setPageBreadcrumb($pageinfo);
            $view->makeDocHeaderModuleMenu(['id' => $pageId]);
            $view->getDocHeaderComponent()->setShortcutContext($module->getIdentifier(), sprintf('%s [%d]', $languageService->sL($module->getTitle()), $pageId), ['id' => $pageId]);
            $previewUriBuilder = PreviewUriBuilder::create($pageinfo);
            if ($previewUriBuilder->isPreviewable()) {
                $previewDataAttributes = $previewUriBuilder
                    ->withRootLine(BackendUtility::BEgetRootLine($pageinfo['uid']))
                    ->buildDispatcherDataAttributes();
                $viewButton = $this->componentFactory->createLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setDisabled(!$previewDataAttributes)
                    ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL))
                    ->setShowLabelText(true);
                $view->addButtonToButtonBar($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }
        $event = $this->eventDispatcher->dispatch(new ModifyInfoModuleContentEvent($hasAccess, $request, $module, $view));
        if ($hasAccess) {
            $view->assignMultiple([
                'pageUid' => $pageId,
                'depthDropdownOptions' => $allowedModuleOptions['depth'],
                'depthDropdownCurrentValue' => $selectedDepth,
                'langDropdownOptions' => $allowedModuleOptions['lang'],
                'langDropdownCurrentValue' => $selectedLanguage,
                'content' => $mainContent,
                'headerContent' => $event->getHeaderContent(),
                'footerContent' => $event->getFooterContent(),
            ]);
        }
        return $view->renderResponse('TranslationStatus');
    }

    private function getModuleOptions(array $siteLanguages): array
    {
        $languageService = $this->getLanguageService();
        $menuArray = [
            'depth' => [
                0 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
            'lang' => [],
        ];
        foreach ($siteLanguages as $language) {
            if ($language->getLanguageId() === 0) {
                $menuArray['lang'][0] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages');
            } else {
                $menuArray['lang'][$language->getLanguageId()] = $language->getTitle();
            }
        }
        return $menuArray;
    }

    private function getTree(int $pageId, int $selectedDepth): PageTreeView
    {
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        $tree->tree[] = ['row' => BackendUtility::getRecordWSOL('pages', $pageId)];
        // Create the tree from starting point
        if ($selectedDepth) {
            $tree->getTree($pageId, $selectedDepth);
        }
        return $tree;
    }

    /**
     * Rendering the localization information table.
     *
     * @param PageTreeView $tree The Page tree data
     * @return string HTML for the localization information table.
     */
    private function renderL10nTable(PageTreeView $tree, ServerRequestInterface $request, array $siteLanguages, int $selectedLanguage): string
    {
        $lang = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        // Put together the TREE:
        $output = '';
        $langRecUids = [];

        $userTsConfig = $backendUser->getTSConfig();
        $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);

        $pageModule = 'web_layout';
        $pageModuleAccess = $this->moduleProvider->accessGranted($pageModule, $backendUser);

        foreach ($tree->tree as $data) {
            $tCells = [];
            $langRecUids[0][] = $data['row']['uid'];
            $pageTitle = ($showPageId ? '[' . (int)$data['row']['uid'] . '] ' : '') . $data['row']['title'];
            // Page icons / titles etc.
            if ($pageModuleAccess) {
                $pageModuleLink = (string)$this->uriBuilder->buildUriFromRoute($pageModule, ['id' => $data['row']['uid'], 'languages' => [0], 'viewMode' => PageViewMode::LayoutView->value]);
                $pageModuleLink = '<a href="' . htmlspecialchars($pageModuleLink) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPage') . '">' . htmlspecialchars($pageTitle) . '</a>';
            } else {
                $pageModuleLink = htmlspecialchars($pageTitle);
            }
            $icon = '<span title="' . BackendUtility::getRecordIconAltText($data['row']) . '">'
                . $this->iconFactory->getIconForRecord('pages', $data['row'], IconSize::SMALL)->setTitle(BackendUtility::getRecordIconAltText($data['row'], 'pages', false))->render()
                . '</span>';

            if ($backendUser->checkRecordEditAccess('pages', $data['row'])->isAllowed) {
                $icon = BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $data['row']['uid']);
            }

            $tCells[] = '<td class="col-title col-responsive">'
                . '<div class="treeline-container">'
                . (!empty($data['depthData']) ? $data['depthData'] : '')
                . ($data['HTML'] ?? '')
                . $icon
                . '<span class="treeline-label">'
                . $pageModuleLink
                . ((string)$data['row']['nav_title'] !== '' ? ' <span>[Nav: <em>' . htmlspecialchars($data['row']['nav_title']) . '</em>]</span>' : '')
                . '</span>'
                . '</div>'
                . '</td>';
            $previewUriBuilder = PreviewUriBuilder::create($data['row']);
            // DEFAULT language:
            $pageTranslationVisibility = new PageTranslationVisibility((int)($data['row']['l18n_cfg'] ?? 0));
            $status = $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage() ? 'danger' : 'success';
            // Create links:
            $editUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'pages' => [
                        $data['row']['uid'] => 'edit',
                    ],
                ],
                'module' => 'web_info_translations',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]);
            $info = '<button ' . ($previewUriBuilder->serializeDispatcherAttributes() ?? 'disabled="true"')
                . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                $this->iconFactory->getIcon('actions-view-page', IconSize::SMALL)->render() . '</button>';
            if ($backendUser->check('tables_modify', 'pages')) {
                $info .= '<a href="' . htmlspecialchars($editUrl)
                    . '" class="btn btn-default" title="' . $lang->sL(
                        'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageProperties'
                    ) . '">' . $this->iconFactory->getIcon('actions-page-open', IconSize::SMALL)->render() . '</a>';
            }
            $info .= '&nbsp;';
            $info .= $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage() ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.1')) . '">D</span>' : '&nbsp;';
            $info .= $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists() ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.2')) . '">N</span>' : '&nbsp;';
            // Put into cell:
            $tCells[] = '<td class="' . $status . ' col-border-left col-nowrap"><div class="btn-group btn-group-sm">' . $info . '</div></td>';
            $tCells[] = '<td class="' . $status . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount') . '" align="center">'
                . ($this->getContentElementCount((int)$data['row']['uid'], 0) ?: '-')
                . '</td>';
            // Traverse system languages:
            foreach ($siteLanguages as $siteLanguage) {
                $languageId = $siteLanguage->getLanguageId();
                if ($languageId === 0) {
                    continue;
                }
                if ($selectedLanguage === 0 || $selectedLanguage === $languageId) {
                    $row = $this->getLangStatus((int)$data['row']['uid'], $languageId);
                    if ($pageTranslationVisibility->shouldBeHiddenInDefaultLanguage() || $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                        $status = 'danger';
                    } else {
                        $status = '';
                    }
                    if (is_array($row)) {
                        $langRecUids[$languageId][] = $row['uid'];
                        if (!$row['_HIDDEN']) {
                            $status = 'success';
                        }
                        if ($row['_COUNT'] > 1) {
                            $status = 'warning';
                        }
                        $info = ($showPageId ? ' [' . (int)$row['uid'] . '] ' : '')
                            . htmlspecialchars($row['title'])
                            . ((string)$row['nav_title'] !== '' ? ' [Nav: <em>' . htmlspecialchars($row['nav_title']) . '</em>]' : '')
                            . ($row['_COUNT'] > 1 ? '<div>' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_badThingThereAre') . '</div>' : '');

                        if ($pageModuleAccess) {
                            $pageModuleLink = (string)$this->uriBuilder->buildUriFromRoute($pageModule, ['id' => $data['row']['uid'], 'language' => [$languageId], 'viewMode' => PageViewMode::LanguageComparisonView->value]);
                            $pageModuleLink = '<a href="' . htmlspecialchars($pageModuleLink) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editTranslatedPage') . '">' . $info . '</a>';
                        } else {
                            $pageModuleLink = $info;
                        }
                        $icon = '<span title="' . BackendUtility::getRecordIconAltText($row) . '">'
                            . $this->iconFactory->getIconForRecord('pages', $row, IconSize::SMALL)->setTitle(BackendUtility::getRecordIconAltText($row, 'pages', false))->render()
                            . '</span>';
                        $tCells[] = '<td class="col-responsive col-border-left ' . $status . '">' .
                            BackendUtility::wrapClickMenuOnIcon($icon, 'pages', (int)$row['uid']) .
                            $pageModuleLink .
                            '</td>';
                        // Edit whole record:
                        // Create links:
                        $editUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit',
                                ],
                            ],
                            'module' => 'web_info_translations',
                            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                        ]);
                        // ViewPageLink
                        $info = '<button ' . ($previewUriBuilder
                                ->withLanguage($languageId)
                                ->serializeDispatcherAttributes() ?? 'disabled="true"')
                            . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewTranslatedPage') . '">' .
                            $this->iconFactory->getIcon('actions-view', IconSize::SMALL)->render() . '</button>';
                        $info .= '<a href="' . htmlspecialchars($editUrl)
                            . '" class="btn btn-default" title="' . $lang->sL(
                                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editTranslatedPageProperties'
                            ) . '">' . $this->iconFactory->getIcon('actions-open', IconSize::SMALL)->render() . '</a>';
                        $tCells[] = '<td class="' . $status . '"><div class="btn-group btn-group-sm">' . $info . '</div></td>';
                        $tCells[] = '<td class="' . $status . '" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount'
                        ) . '" align="center">' . ($this->getContentElementCount((int)$data['row']['uid'], $languageId) ?: '-') . '</td>';
                    } else {
                        $idName = sprintf('new-overlay-%d-%d', $languageId, $data['row']['uid']);
                        $info = '<div class="form-check form-check-type-icon-toggle">'
                            . '<input type="checkbox" data-lang="' . $languageId . '" data-uid="' . (int)$data['row']['uid'] . '" name="newOL[' . $languageId . '][' . $data['row']['uid'] . ']" id="' . htmlspecialchars($idName) . '" class="form-check-input" value="1" />'
                            . '<label class="form-check-label" for="' . $idName . '">'
                            . '<span class="form-check-label-icon">'
                            . '<span class="form-check-label-icon-checked">' . $this->iconFactory->getIcon('actions-check', IconSize::SMALL)->render() . '</span>'
                            . '<span class="form-check-label-icon-unchecked">' . $this->iconFactory->getIcon('empty-empty', IconSize::SMALL)->render() . '</span>'
                            . '</span>'
                            . '</label>'
                            . '</div>';
                        $tCells[] = '<td class="' . $status . ' col-border-left">&nbsp;</td>';
                        $tCells[] = '<td class="' . $status . '">&nbsp;</td>';
                        $tCells[] = '<td class="' . $status . '">' . $info . '</td>';
                    }
                }
            }
            $output .= '<tr>' . implode('', $tCells) . '</tr>';
        }
        // Put together HEADER:
        $headerCells = [];
        $headerCells[] = '<th>' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_page') . '</th>';
        if ($backendUser->check('tables_modify', 'pages')) {
            $editUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'pages' => [
                        implode(',', $langRecUids[0]) => 'edit',
                    ],
                ],
                'columnsOnly' => [
                    'pages' => ['title', 'nav_title', 'l18n_cfg', 'hidden'],
                ],
                'module' => 'web_info_translations',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]);
            $editIco = '<a href="' . htmlspecialchars($editUrl)
                . '" class="btn btn-default btn-sm" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editAllPageProperties'
                ) . '">' . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '</a>';
        } else {
            $editIco = '';
        }
        if (isset($siteLanguages[0])) {
            $defaultLanguageLabel = $siteLanguages[0]->getTitle();
        } else {
            $defaultLanguageLabel = $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_default');
        }
        $headerCells[] = '<th class="col-border-left" colspan="2">' . htmlspecialchars($defaultLanguageLabel) . '&nbsp;' . $editIco . '</th>';
        foreach ($siteLanguages as $siteLanguage) {
            $languageId = $siteLanguage->getLanguageId();
            if ($languageId === 0) {
                continue;
            }
            if ($selectedLanguage === 0 || $selectedLanguage === $languageId) {
                // Title:
                $headerCells[] = '<th class="col-border-left">' . htmlspecialchars($siteLanguage->getTitle()) . '</th>';
                // Edit language overlay records:
                if (is_array($langRecUids[$languageId] ?? null)) {
                    $editUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                        'edit' => [
                            'pages' => [
                                implode(',', $langRecUids[$languageId]) => 'edit',
                            ],
                        ],
                        'columnsOnly' => [
                            'pages' =>  ['title', 'nav_title', 'hidden'],
                        ],
                        'module' => 'web_info_translations',
                        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                    ]);
                    $editButton = '<a href="' . htmlspecialchars($editUrl)
                        . '" class="btn btn-default" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editAllTranslationedPageProperties'
                        ) . '">' . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '</a>';
                } else {
                    $editButton = '';
                }
                // Create new overlay records:
                $createLink = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                    'redirect' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ]);
                $newButton = '<a href="' . htmlspecialchars($createLink) . '" data-edit-url="' . htmlspecialchars($createLink) . '" class="btn btn-default btn-sm disabled t3js-language-new" data-lang="' . $languageId . '" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_getlangsta_createNewTranslationHeaders'
                ) . '">' . $this->iconFactory->getIcon('actions-document-new', IconSize::SMALL, null, IconState::STATE_DISABLED)->render() . '</a>';

                $headerCells[] = '<th>' . $editButton . '</th>';
                $headerCells[] = '<th>' . $newButton . '</th>';
            }
        }

        $output =
            '<div class="table-fit">' .
                '<table class="table table-striped table-hover" id="langTable">' .
                    '<thead>' .
                        '<tr>' .
                            implode('', $headerCells) .
                        '</tr>' .
                    '</thead>' .
                    '<tbody>' .
                        $output .
                    '</tbody>' .
                '</table>' .
            '</div>';
        return $output;
    }

    /**
     * Get an alternative language record for a specific page / language
     *
     * @param int $pageId Page ID to look up for.
     * @param int $langId Language UID to select for.
     * @return array|bool translated pages record
     */
    private function getLangStatus(int $pageId, int $langId): bool|array
    {
        $schema = $this->tcaSchemaFactory->get('pages');
        /** @var LanguageAwareSchemaCapability $languageCapability */
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $languageCapability->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $languageCapability->getLanguageField()->getName(),
                    $queryBuilder->createNamedParameter($langId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $row = $result->fetchAssociative();
        BackendUtility::workspaceOL('pages', $row);
        if (is_array($row)) {
            $row['_COUNT'] = $queryBuilder->count('uid')->executeQuery()->fetchOne();
            $row['_HIDDEN'] = $row['hidden'] || (int)$row['endtime'] > 0 && (int)$row['endtime'] < $GLOBALS['EXEC_TIME'] || $GLOBALS['EXEC_TIME'] < (int)$row['starttime'];
        }
        $result->free();
        return $row;
    }

    /**
     * Counting content elements for a single language on a page.
     *
     * @param int $pageId Page id to select for.
     * @param int $sysLang Sys language uid
     * @return int Number of content elements from the PID where the language is set to a certain value.
     */
    private function getContentElementCount(int $pageId, int $sysLang): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));
        return (int)$queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLang, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
