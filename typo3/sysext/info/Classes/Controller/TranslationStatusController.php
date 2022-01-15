<?php

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying translation status of pages in the tree in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class TranslationStatusController
{
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;

    /**
     * @var SiteLanguage[]
     */
    protected $siteLanguages;

    /**
     * @var InfoModuleController Contains a reference to the parent calling object
     */
    protected $pObj;

    /**
     * @var int Value of the GET/POST var 'id'
     */
    protected $id;

    public function __construct(IconFactory $iconFactory, UriBuilder $uriBuilder)
    {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init(InfoModuleController $pObj, ServerRequestInterface $request)
    {
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $this->initializeSiteLanguages($request);
        $this->pObj = $pObj;

        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main, called from parent object
     *
     * @return string Output HTML for the module.
     */
    public function main(ServerRequestInterface $request)
    {
        $theOutput = '<h1>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_title')) . '</h1>';
        if ($this->id) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Info/TranslationStatus');

            $moduleMenu = '';

            foreach (['depth' => false, 'lang' => true] as $name => $addCsh) {
                $menu = BackendUtility::getDropdownMenu($this->id, 'SET[' . $name . ']', $this->pObj->MOD_SETTINGS[$name], $this->pObj->MOD_MENU[$name]);
                if ($menu !== '') {
                    $moduleMenu .= '
                        <div class="col">
                           <label class="form-label">' .
                                htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:moduleFunctions.' . $name)) .
                           '</label>' .
                           $menu .
                        '</div>';
                    if ($addCsh) {
                        $moduleMenu .= BackendUtility::cshItem('_MOD_web_info', $name, '', '<div class="col"><span class="btn btn-default btn-sm">|</span></div>');
                    }
                }
            }

            if ($moduleMenu !== '') {
                $theOutput .= '<div class="row row-cols-auto mb-3 g-3 align-items-center">' . $moduleMenu . '</div>';
            }

            // Showing the tree
            $tree = $this->getTree();
            // Render information table
            $theOutput .= $this->renderL10nTable($tree, $request);
        }
        return $theOutput;
    }

    protected function getTree(): PageTreeView
    {
        // Initialize starting point of page tree
        $treeStartingPoint = $this->id;
        $treeStartingRecord = BackendUtility::getRecordWSOL('pages', $treeStartingPoint);
        $depth = (int)$this->pObj->MOD_SETTINGS['depth'];
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        $tree->addField('l18n_cfg');
        $tree->tree[] = [
            'row' => $treeStartingRecord,
            // Creating top icon; the current page
            'HTML' => $this->iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render(),
        ];
        // Create the tree from starting point
        if ($depth) {
            $tree->getTree($treeStartingPoint, $depth);
        }
        return $tree;
    }

    /**
     * Returns the menu array
     *
     * @return array
     */
    protected function modMenu()
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
                $menuArray['lang'][0] = '[All]';
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
     * @param ServerRequestInterface $request
     * @return string HTML for the localization information table.
     */
    protected function renderL10nTable(PageTreeView $tree, ServerRequestInterface $request)
    {
        $lang = $this->getLanguageService();
        // Title length:
        $titleLen = $this->getBackendUser()->uc['titleLen'];
        // Put together the TREE:
        $output = '';
        $langRecUids = [];

        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);

        // If another page module was specified, replace the default Page module with the new one
        $pageModule = trim($this->getBackendUser()->getTSConfig()['options.']['overridePageModule'] ?? '');
        $pageModule = BackendUtility::isModuleSetInTBE_MODULES($pageModule) ? $pageModule : 'web_layout';
        $canLinkToPageModule = $this->getBackendUser()->check('modules', $pageModule);

        foreach ($tree->tree as $data) {
            $tCells = [];
            $langRecUids[0][] = $data['row']['uid'];
            $pageTitle = ($showPageId ? '[' . (int)$data['row']['uid'] . '] ' : '') . GeneralUtility::fixed_lgd_cs($data['row']['title'], $titleLen);
            // Page icons / titles etc.
            if ($canLinkToPageModule) {
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
            $info = '<a href="#" ' . $previewUriBuilder->serializeDispatcherAttributes()
                . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render() . '</a>';
            if ($this->getBackendUser()->check('tables_modify', 'pages')) {
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
            ) . '" align="center">' . $this->getContentElementCount($data['row']['uid'], 0) . '</td>';
            // Traverse system languages:
            foreach ($this->siteLanguages as $siteLanguage) {
                $languageId = $siteLanguage->getLanguageId();
                if ($languageId === 0) {
                    continue;
                }
                if ($this->pObj->MOD_SETTINGS['lang'] == 0 || (int)$this->pObj->MOD_SETTINGS['lang'] === $languageId) {
                    $row = $this->getLangStatus($data['row']['uid'], $languageId);
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

                        if ($canLinkToPageModule) {
                            $pageModuleLink = (string)$this->uriBuilder->buildUriFromRoute($pageModule, ['id' => $data['row']['uid'], 'SET' => ['language' => $languageId]]);
                            $pageModuleLink = '<a href="' . htmlspecialchars($pageModuleLink) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageLang') . '">' . $info . '</a>';
                        } else {
                            $pageModuleLink = $info;
                        }
                        $tCells[] = '<td class="' . $status . ' col-border-left">' .
                            BackendUtility::wrapClickMenuOnIcon($tree->getIcon($row), 'pages', (int)$row['uid']) .
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
                        $info = '<a href="#" ' . $previewUriBuilder
                                ->withAdditionalQueryParameters('&L=' . $languageId)
                                ->serializeDispatcherAttributes()
                            . ' class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                            $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
                        $info .= '<a href="' . htmlspecialchars($editUrl)
                            . '" class="btn btn-default" title="' . $lang->sL(
                                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editLanguageOverlayRecord'
                            ) . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                        $tCells[] = '<td class="' . $status . '"><div class="btn-group">' . $info . '</div></td>';
                        $tCells[] = '<td class="' . $status . '" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount'
                        ) . '" align="center">' . $this->getContentElementCount($data['row']['uid'], $languageId) . '</td>';
                    } else {
                        $info = '<div class="btn-group"><label class="btn btn-default btn-checkbox">';
                        $info .= '<input type="checkbox" data-lang="' . $languageId . '" data-uid="' . (int)$data['row']['uid'] . '" name="newOL[' . $languageId . '][' . $data['row']['uid'] . ']" value="1" />';
                        $info .= '<span class="t3-icon fa"></span></label></div>';
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
        if ($this->getBackendUser()->check('tables_modify', 'pages') && is_array($langRecUids[0])) {
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
            if ($this->pObj->MOD_SETTINGS['lang'] == 0 || (int)$this->pObj->MOD_SETTINGS['lang'] === $languageId) {
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
                $newButton = '<a href="' . htmlspecialchars($createLink) . '" data-edit-url="' . htmlspecialchars($createLink) . '" class="btn btn-default disabled t3js-language-new-' . $languageId . '" title="' . $lang->sL(
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
     * @return array translated pages record
     */
    protected function getLangStatus($pageId, $langId)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($langId, \PDO::PARAM_INT)
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
    protected function getContentElementCount($pageId, $sysLang)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
        $count = $queryBuilder
            ->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->andWhere(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sysLang, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        return $count ?: '-';
    }

    /**
     * Since the controller does not access the current request yet, we'll do it "old school"
     * to fetch the Site based on the current ID.
     */
    protected function initializeSiteLanguages(ServerRequestInterface $request)
    {
        /** @var SiteInterface $currentSite */
        $currentSite = $request->getAttribute('site');
        $this->siteLanguages = $currentSite->getAvailableLanguages($this->getBackendUser(), false, (int)$this->id);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
