<?php
namespace TYPO3\CMS\Info\Controller;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for displaying translation status of pages in the tree in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class TranslationStatusController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using TranslationStatusController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using TranslationStatusController::function_key$ is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using TranslationStatusController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using TranslationStatusController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
        'extObj' => 'Using TranslationStatusController::$extObj is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'getContentElementCount' => 'Using TranslationStatusController::getContentElementCount() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getLangStatus' => 'Using TranslationStatusController::getLangStatus() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'renderL10nTable' => 'Using TranslationStatusController::renderL10nTable() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu' => 'Using TranslationStatusController::modMenu() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjContent' => 'Using TranslationStatusController::extObjContent() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

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

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extObj;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $localLangFile = '';

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extClassConf;

    /**
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $function_key = '';

    /**
     * Init, called from parent object
     *
     * @param InfoModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->id = (int)GeneralUtility::_GP('id');
        $this->initializeSiteLanguages();
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main, called from parent object
     *
     * @return string Output HTML for the module.
     */
    public function main()
    {
        $theOutput = '<h1>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_title')) . '</h1>';
        if ($this->id) {
            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Info/TranslationStatus');

            // Depth selector:
            $theOutput .= '<div class="form-inline form-inline-spaced">';
            $h_func = BackendUtility::getDropdownMenu($this->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);
            $h_func .= BackendUtility::getDropdownMenu($this->id, 'SET[lang]', $this->pObj->MOD_SETTINGS['lang'], $this->pObj->MOD_MENU['lang']);
            $theOutput .= $h_func;
            // Add CSH:
            $theOutput .= BackendUtility::cshItem('_MOD_web_info', 'lang', null, '<div class="form-group"><span class="btn btn-default btn-sm">|</span></div><br />');
            $theOutput .= '</div>';
            // Showing the tree:
            // Initialize starting point of page tree:
            $treeStartingPoint = (int)$this->id;
            $treeStartingRecord = BackendUtility::getRecordWSOL('pages', $treeStartingPoint);
            $depth = $this->pObj->MOD_SETTINGS['depth'];
            // Initialize tree object:
            $tree = GeneralUtility::makeInstance(PageTreeView::class);
            $tree->init('AND ' . $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
            $tree->addField('l18n_cfg');
            // Creating top icon; the current page
            $HTML = $this->iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render();
            $tree->tree[] = [
                'row' => $treeStartingRecord,
                'HTML' => $HTML
            ];
            // Create the tree from starting point:
            if ($depth) {
                $tree->getTree($treeStartingPoint, $depth, '');
            }
            // Render information table:
            $theOutput .= $this->renderL10nTable($tree);
        }
        return $theOutput;
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
                999 => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi')
            ]
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
     * @param array $tree The Page tree data
     * @return string HTML for the localization information table.
     */
    protected function renderL10nTable(&$tree)
    {
        $lang = $this->getLanguageService();
        // Title length:
        $titleLen = $this->getBackendUser()->uc['titleLen'];
        // Put together the TREE:
        $output = '';
        $newOL_js = [];
        $langRecUids = [];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);

        foreach ($tree->tree as $data) {
            $tCells = [];
            $langRecUids[0][] = $data['row']['uid'];
            $pageTitle = ($showPageId ? '[' . (int)$data['row']['uid'] . '] ' : '') . GeneralUtility::fixed_lgd_cs($data['row']['title'], $titleLen);
            // Page icons / titles etc.
            $tCells[] = '<td' . ($data['row']['_CSSCLASS'] ? ' class="' . $data['row']['_CSSCLASS'] . '"' : '') . '>' .
                ($data['depthData'] ?: '') .
                BackendUtility::wrapClickMenuOnIcon($data['HTML'], 'pages', $data['row']['uid']) .
                '<a href="#" onclick="' . htmlspecialchars(
                    'top.loadEditId(' . (int)$data['row']['uid'] . ',"&SET[language]=0"); return false;'
                ) . '" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPage') . '">' .
                htmlspecialchars($pageTitle) .
                '</a>' .
                ((string)$data['row']['nav_title'] !== '' ? ' [Nav: <em>' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($data['row']['nav_title'], $titleLen)) . '</em>]' : '') .
                '</td>';
            // DEFAULT language:
            // "View page" link is created:
            $viewPageLink = '<a href="#" onclick="' . htmlspecialchars(
                BackendUtility::viewOnClick(
                    $data['row']['uid'],
                    '',
                    null,
                    '',
                    '',
                    '&L=###LANG_UID###'
                )
            ) . '" class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
            $status = GeneralUtility::hideIfDefaultLanguage($data['row']['l18n_cfg']) ? 'danger' : 'success';
            // Create links:
            $editUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'pages' => [
                        $data['row']['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
            $info = '<a href="#" onclick="' . htmlspecialchars(
                BackendUtility::viewOnClick(
                    $data['row']['uid'],
                    '',
                    null,
                    '',
                    '',
                    ''
                )
            ) . '" class="btn btn-default" title="' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render() . '</a>';
            $info .= '<a href="' . htmlspecialchars($editUrl)
                . '" class="btn btn-default" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editDefaultLanguagePage'
                ) . '">' . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
            $info .= '&nbsp;';
            $info .= GeneralUtility::hideIfDefaultLanguage($data['row']['l18n_cfg']) ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.1')) . '">D</span>' : '&nbsp;';
            $info .= GeneralUtility::hideIfNotTranslated($data['row']['l18n_cfg']) ? '<span title="' . htmlspecialchars($lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg.I.2')) . '">N</span>' : '&nbsp;';
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
                    if (is_array($row)) {
                        $langRecUids[$languageId][] = $row['uid'];
                        $status = $row['_HIDDEN'] ? (GeneralUtility::hideIfNotTranslated($data['row']['l18n_cfg']) || GeneralUtility::hideIfDefaultLanguage($data['row']['l18n_cfg']) ? 'danger' : '') : 'success';
                        $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL)->render();
                        $info = $icon . ($showPageId ? ' [' . (int)$row['uid'] . ']' : '') . ' ' . htmlspecialchars(
                            GeneralUtility::fixed_lgd_cs($row['title'], $titleLen)
                        ) . ((string)$row['nav_title'] !== '' ? ' [Nav: <em>' . htmlspecialchars(
                            GeneralUtility::fixed_lgd_cs($row['nav_title'], $titleLen)
                        ) . '</em>]' : '') . ($row['_COUNT'] > 1 ? '<div>' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_badThingThereAre'
                        ) . '</div>' : '');
                        $tCells[] = '<td class="' . $status . ' col-border-left">' .
                            '<a href="#" onclick="' . htmlspecialchars(
                                'top.loadEditId(' . (int)$data['row']['uid'] . ',"&SET[language]=' . $languageId . '"); return false;'
                            ) . '" title="' . $lang->sL(
                                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageLang'
                            ) . '">' . $info . '</a></td>';
                        // Edit whole record:
                        // Create links:
                        $editUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit'
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ]);
                        $info = str_replace('###LANG_UID###', $languageId, $viewPageLink);
                        $info .= '<a href="' . htmlspecialchars($editUrl)
                            . '" class="btn btn-default" title="' . $lang->sL(
                                'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editLanguageOverlayRecord'
                            ) . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                        $tCells[] = '<td class="' . $status . '"><div class="btn-group">' . $info . '</div></td>';
                        $tCells[] = '<td class="' . $status . '" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_CEcount'
                        ) . '" align="center">' . $this->getContentElementCount($data['row']['uid'], $languageId) . '</td>';
                    } else {
                        $status = GeneralUtility::hideIfNotTranslated($data['row']['l18n_cfg']) || GeneralUtility::hideIfDefaultLanguage($data['row']['l18n_cfg']) ? 'danger' : '';
                        $info = '<div class="btn-group"><label class="btn btn-default btn-checkbox">';
                        $info .= '<input type="checkbox" data-lang="' . $languageId . '" data-uid="' . (int)$data['row']['uid'] . '" name="newOL[' . $languageId . '][' . $data['row']['uid'] . ']" value="1" />';
                        $info .= '<span class="t3-icon fa"></span></label></div>';
                        $newOL_js[$languageId] .=
                            ' +(document.webinfoForm['
                            . GeneralUtility::quoteJSvalue('newOL[' . $languageId . '][' . $data['row']['uid'] . ']')
                            . '].checked ? '
                            . GeneralUtility::quoteJSvalue('&edit[pages][' . $data['row']['uid'] . ']=new')
                            . ' : \'\')'
                        ;
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
        $tCells = [];
        $tCells[] = '<td>' . $lang->sL('LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_page') . '</td>';
        if (is_array($langRecUids[0])) {
            $editUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    'pages' => [
                        implode(',', $langRecUids[0]) => 'edit'
                    ]
                ],
                'columnsOnly' => 'title,nav_title,l18n_cfg,hidden',
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
            $editIco = '<a href="' . htmlspecialchars($editUrl)
                . '" class="btn btn-default" title="' . $lang->sL(
                    'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editPageProperties'
                ) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $editIco = '';
        }
        $tCells[] = '<td class="col-border-left" colspan="2">' . $lang->sL(
            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_default'
        ) . '&nbsp;' . $editIco . '</td>';
        foreach ($this->siteLanguages as $siteLanguage) {
            $languageId = $siteLanguage->getLanguageId();
            if ($languageId === 0) {
                continue;
            }
            if ($this->pObj->MOD_SETTINGS['lang'] == 0 || (int)$this->pObj->MOD_SETTINGS['lang'] === $languageId) {
                // Title:
                $tCells[] = '<td class="col-border-left">' . htmlspecialchars($siteLanguage->getTitle()) . '</td>';
                // Edit language overlay records:
                if (is_array($langRecUids[$languageId])) {
                    $editUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                        'edit' => [
                            'pages' => [
                                implode(',', $langRecUids[$languageId]) => 'edit'
                            ]
                        ],
                        'columnsOnly' => 'title,nav_title,hidden',
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ]);
                    $editButton = '<a href="' . htmlspecialchars($editUrl)
                        . '" class="btn btn-default" title="' . $lang->sL(
                            'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_editLangOverlays'
                        ) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $editButton = '';
                }
                // Create new overlay records:
                $createLink = (string)$uriBuilder->buildUriFromRoute('tce_db', [
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]);
                $newButton = '<a href="' .
                             htmlspecialchars($createLink) .
                             '" data-edit-url="' .
                             htmlspecialchars($createLink) .
                             '" class="btn btn-default disabled t3js-language-new-' .
                             $languageId .
                             '" title="' .
                             $lang->sL(
                                 'LLL:EXT:info/Resources/Private/Language/locallang_webinfo.xlf:lang_getlangsta_createNewTranslationHeaders'
                             ) .
                             '">' .
                             $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() .
                             '</a>';

                $tCells[] = '<td class="btn-group">' . $editButton . $newButton . '</td>';
                $tCells[] = '<td>&nbsp;</td>';
            }
        }

        $output =
            '<div class="table-fit">' .
                '<table class="table table-striped table-hover" id="langTable">' .
                    '<thead>' .
                        '<tr>' .
                            implode('', $tCells) .
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
     * Selects all system languages (from sys_language)
     *
     * @return array System language records in an array.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getSystemLanguages()
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if (!$this->getBackendUser()->isAdmin() && $this->getBackendUser()->groupData['allowed_languages'] !== '') {
            $allowed_languages = array_flip(explode(',', $this->getBackendUser()->groupData['allowed_languages']));
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language')
            ->select('*')
            ->from('sys_language')
            ->orderBy('sorting');
        $res = $queryBuilder->execute();
        $outputArray = [];
        if (is_array($allowed_languages) && !empty($allowed_languages)) {
            while ($output = $res->fetch()) {
                if (isset($allowed_languages[$output['uid']])) {
                    $outputArray[] = $output;
                }
            }
        } else {
            $outputArray = $res->fetchAll();
        }
        return $outputArray;
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
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class))
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
            ->execute();

        $row = $result->fetch();
        BackendUtility::workspaceOL('pages', $row);
        if (is_array($row)) {
            $row['_COUNT'] = $queryBuilder->count('uid')->execute()->fetchColumn(0);
            $row['_HIDDEN'] = $row['hidden'] || (int)$row['endtime'] > 0 && (int)$row['endtime'] < $GLOBALS['EXEC_TIME'] || $GLOBALS['EXEC_TIME'] < (int)$row['starttime'];
        }
        $result->closeCursor();
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
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
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
            ->execute()
            ->fetchColumn(0);
        return $count ?: '-';
    }

    /**
     * Since the AbstractFunctionModule cannot access the current request yet, we'll do it "old school"
     * to fetch the Site based on the current ID.
     */
    protected function initializeSiteLanguages()
    {
        /** @var SiteInterface $currentSite */
        $currentSite = $GLOBALS['TYPO3_REQUEST']->getAttribute('site');
        $this->siteLanguages = $currentSite->getAvailableLanguages($this->getBackendUser(), false, (int)$this->id);
    }

    /**
     * Called from InfoModuleController until deprecation removal in TYPO3 v10.0
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function checkExtObj()
    {
        if (is_array($this->extClassConf) && $this->extClassConf['name']) {
            $this->extObj = GeneralUtility::makeInstance($this->extClassConf['name']);
            $this->extObj->init($this->pObj, $this->extClassConf);
            // Re-write:
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), 'web_info');
        }
    }

    /**
     * Calls the main function inside ANOTHER sub-submodule which might exist.
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function extObjContent()
    {
        if (is_object($this->extObj)) {
            return $this->extObj->main();
        }
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
