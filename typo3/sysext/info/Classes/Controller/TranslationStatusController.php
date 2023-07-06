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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying translation status of pages in the tree in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class TranslationStatusController extends InfoModuleController
{
    /**
     * @var SiteLanguage[]
     */
    protected array $siteLanguages = [];
    protected int $currentDepth = 0;
    protected int $currentLanguageId = 0;

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->initializeSiteLanguages($request);
        $backendUser = $this->getBackendUser();
        $moduleData = $request->getAttribute('moduleData');
        $allowedModuleOptions = $this->getAllowedModuleOptions();
        if ($moduleData->cleanUp($allowedModuleOptions)) {
            $backendUser->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $this->currentDepth = (int)$moduleData->get('depth');
        $this->currentLanguageId = (int)$moduleData->get('lang');

        if ($this->id) {
            $tree = $this->getTree();
            $content = $this->renderL10nTable($tree, $request);
            $this->view->assignMultiple([
                'pageUid' => $this->id,
                'depthDropdownOptions' => $allowedModuleOptions['depth'],
                'depthDropdownCurrentValue' => $this->currentDepth,
                'displayLangDropdown' => !empty($allowedModuleOptions['lang']),
                'langDropdownOptions' => $allowedModuleOptions['lang'],
                'langDropdownCurrentValue' => $this->currentLanguageId,
                'content' => $content,
            ]);
        }
        return $this->view->renderResponse('TranslationStatus');
    }

    protected function getTree(): PageTreeView
    {
        // Initialize starting point of page tree
        $treeStartingPoint = $this->id;
        $treeStartingRecord = BackendUtility::getRecordWSOL('pages', $treeStartingPoint);
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        $tree->addField('l18n_cfg');
        $tree->tree[] = [
            'row' => $treeStartingRecord,
            // Creating top icon; the current page
            'HTML' => $this->iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render(),
        ];
        // Create the tree from starting point
        if ($this->currentDepth) {
            $tree->getTree($treeStartingPoint, $this->currentDepth);
        }
        return $tree;
    }

    protected function getAllowedModuleOptions(): array
    {
        $lang = $this->getLanguageService();
        $menuArray = [
            'depth' => [
                0 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ],
        ];
        // Languages:
        $menuArray['lang'] = [];
        foreach ($this->siteLanguages as $language) {
            if ($language->getLanguageId() === 0) {
                $menuArray['lang'][0] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages');
            } else {
                $menuArray['lang'][$language->getLanguageId()] = $language->getTitle();
            }
        }
        return $menuArray;
    }

    /**
     * Rendering the localization information table.
     *
     * @param PageTreeView $tree The Page tree data
     * @return string HTML for the localization information table.
     */
    protected function renderL10nTable(PageTreeView $tree, ServerRequestInterface $request): string
    {
        $lang = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        // Title length:
        $titleLen = (int)$backendUser->uc['titleLen'];
        // Put together the TREE:
        $output = '';
        $langRecUids = [];

        $userTsConfig = $backendUser->getTSConfig();
        $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);

        // If another page module was specified, replace the default Page module with the new one
        $pageModule = trim($userTsConfig['options.']['overridePageModule'] ?? '');
        $pageModule = $this->moduleProvider->isModuleRegistered($pageModule) ? $pageModule : 'web_layout';
        $pageModuleAccess = $this->moduleProvider->accessGranted($pageModule, $backendUser);

        foreach ($tree->tree as $data) {
            $tCells = [];
            $langRecUids[0][] = $data['row']['uid'];
            $pageTitle = ($showPageId ? '[' . (int)$data['row']['uid'] . '] ' : '') . GeneralUtility::fixed_lgd_cs($data['row']['title'], $titleLen);
            // Page icons / titles etc.
            if ($pageModuleAccess) {
                $pageModuleLink = (string)$this->uriBuilder->buildUriFromRoute($pageModule, ['id' => $data['row']['uid'], 'SET' => ['language' => 0]]);
                $pageModuleLink = '<a href="' . htmlspecialchars($pageModuleLink) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPage') . '">' . htmlspecialchars($pageTitle) . '</a>';
            } else {
                $pageModuleLink = htmlspecialchars($pageTitle);
            }

            $tCells[] = '<td' . (!empty($data['row']['_CSSCLASS']) ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '') . '>' .
                (!empty($data['depthData']) ? $data['depthData'] : '') .
                BackendUtility::wrapClickMenuOnIcon($data['HTML'], 'pages', $data['row']['uid']) .
                $pageModuleLink .
                ((string)$data['row']['nav_title'] !== '' ? ' [Nav: <em>' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['nav_title'], $titleLen)) . '</em>]' : '') .
                '</td>';
            $previewUriBuilder = PreviewUriBuilder::create((int)$data['row']['uid']);
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
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]);
            $info = '<button ' . ($previewUriBuilder->serializeDispatcherAttributes() ?? 'disabled="true"')
                . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render() . '</button>';
            if ($backendUser->check('tables_modify', 'pages')) {
                $info .= '<a href="' . htmlspecialchars($editUrl)
                    . '" class="btn btn-default" title="' . $lang->sL(
                        'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editDefaultLanguagePage'
                    ) . '">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
            }
            $info .= '&nbsp;';
            $info .= $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage() ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.1')) . '">D</span>' : '&nbsp;';
            $info .= $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists() ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.2')) . '">N</span>' : '&nbsp;';
            // Put into cell:
            $tCells[] = '<td class="' . $status . ' col-border-left"><div class="btn-group">' . $info . '</div></td>';
            $tCells[] = '<td class="' . $status . '" title="' . $lang->sL(
                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount'
            ) . '" align="center">' . ($this->getContentElementCount((int)$data['row']['uid'], 0) ?: '-') . '</td>';
            // Traverse system languages:
            foreach ($this->siteLanguages as $siteLanguage) {
                $languageId = $siteLanguage->getLanguageId();
                if ($languageId === 0) {
                    continue;
                }
                if ($this->currentLanguageId === 0 || $this->currentLanguageId === $languageId) {
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
                        $info = ($showPageId ? ' [' . (int)$row['uid'] . ']' : '') . ' ' . htmlspecialchars(
                            GeneralUtility::fixed_lgd_cs($row['title'], $titleLen)
                        ) . ((string)$row['nav_title'] !== '' ? ' [Nav: <em>' . htmlspecialchars(
                            GeneralUtility::fixed_lgd_cs($row['nav_title'], $titleLen)
                        ) . '</em>]' : '') . ($row['_COUNT'] > 1 ? '<div>' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_badThingThereAre'
                        ) . '</div>' : '');

                        if ($pageModuleAccess) {
                            $pageModuleLink = (string)$this->uriBuilder->buildUriFromRoute($pageModule, ['id' => $data['row']['uid'], 'SET' => ['language' => $languageId]]);
                            $pageModuleLink = '<a href="' . htmlspecialchars($pageModuleLink) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageLang') . '">' . $info . '</a>';
                        } else {
                            $pageModuleLink = $info;
                        }
                        $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL);
                        $iconMarkup = '<span title="' . BackendUtility::getRecordIconAltText($row, 'pages') . '">' . $icon->render() . '</span>';
                        $tCells[] = '<td class="' . $status . ' col-border-left">' .
                            BackendUtility::wrapClickMenuOnIcon($iconMarkup, 'pages', (int)$row['uid']) .
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
                            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                        ]);
                        // ViewPageLink
                        $info = '<button ' . ($previewUriBuilder
                                ->withLanguage($languageId)
                                ->serializeDispatcherAttributes() ?? 'disabled="true"')
                            . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                            $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</button>';
                        $info .= '<a href="' . htmlspecialchars($editUrl)
                            . '" class="btn btn-default" title="' . $lang->sL(
                                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editLanguageOverlayRecord'
                            ) . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                        $tCells[] = '<td class="' . $status . '"><div class="btn-group">' . $info . '</div></td>';
                        $tCells[] = '<td class="' . $status . '" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount'
                        ) . '" align="center">' . ($this->getContentElementCount((int)$data['row']['uid'], $languageId) ?: '-') . '</td>';
                    } else {
                        $idName = sprintf('new-overlay-%d-%d', $languageId, $data['row']['uid']);
                        $info = '<div class="form-check form-check-type-icon-toggle">'
                            . '<input type="checkbox" data-lang="' . $languageId . '" data-uid="' . (int)$data['row']['uid'] . '" name="newOL[' . $languageId . '][' . $data['row']['uid'] . ']" id="' . htmlspecialchars($idName) . '" class="form-check-input" value="1" />'
                            . '<label class="form-check-label" for="' . $idName . '">'
                            . '<span class="form-check-label-icon">'
                            . '<span class="form-check-label-icon-checked">' . $this->iconFactory->getIcon('actions-check', Icon::SIZE_SMALL)->render() . '</span>'
                            . '<span class="form-check-label-icon-unchecked">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>'
                            . '</span>'
                            . '</label>'
                            . '</div>';
                        $tCells[] = '<td class="' . $status . ' col-border-left">&nbsp;</td>';
                        $tCells[] = '<td class="' . $status . '">&nbsp;</td>';
                        $tCells[] = '<td class="' . $status . '">' . $info . '</td>';
                    }
                }
            }
            $output .= '
				<tr>
					' . implode('
					', $tCells) . '
				</tr>';
        }
        // Put together HEADER:
        $headerCells = [];
        $headerCells[] = '<th>' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_page') . '</th>';
        if ($backendUser->check('tables_modify', 'pages') && is_array($langRecUids[0])) {
            $editUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'pages' => [
                        implode(',', $langRecUids[0]) => 'edit',
                    ],
                ],
                'columnsOnly' => 'title,nav_title,l18n_cfg,hidden',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
            ]);
            $editIco = '<a href="' . htmlspecialchars($editUrl)
                . '" class="btn btn-default" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageProperties'
                ) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $editIco = '';
        }
        if (isset($this->siteLanguages[0])) {
            $defaultLanguageLabel = $this->siteLanguages[0]->getTitle();
        } else {
            $defaultLanguageLabel = $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_default');
        }
        $headerCells[] = '<th class="col-border-left" colspan="2">' . htmlspecialchars($defaultLanguageLabel) . '&nbsp;' . $editIco . '</th>';
        foreach ($this->siteLanguages as $siteLanguage) {
            $languageId = $siteLanguage->getLanguageId();
            if ($languageId === 0) {
                continue;
            }
            if ($this->currentLanguageId === 0 || $this->currentLanguageId === $languageId) {
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
                        'columnsOnly' => 'title,nav_title,hidden',
                        'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                    ]);
                    $editButton = '<a href="' . htmlspecialchars($editUrl)
                        . '" class="btn btn-default" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editLangOverlays'
                        ) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $editButton = '';
                }
                // Create new overlay records:
                $createLink = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                    'redirect' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ]);
                $newButton = '<a href="' . htmlspecialchars($createLink) . '" data-edit-url="' . htmlspecialchars($createLink) . '" class="btn btn-default disabled t3js-language-new" data-lang="' . $languageId . '" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_getlangsta_createNewTranslationHeaders'
                ) . '">' . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . '</a>';

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
    protected function getLangStatus(int $pageId, int $langId): bool|array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
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
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
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
    protected function getContentElementCount(int $pageId, int $sysLang): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
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

    /**
     * Since the controller does not access the current request yet, we'll do it "old school"
     * to fetch the Site based on the current ID.
     */
    protected function initializeSiteLanguages(ServerRequestInterface $request): void
    {
        /** @var SiteInterface $currentSite */
        $currentSite = $request->getAttribute('site');
        $this->siteLanguages = $currentSite->getAvailableLanguages($this->getBackendUser(), false, $this->id);
    }
}
