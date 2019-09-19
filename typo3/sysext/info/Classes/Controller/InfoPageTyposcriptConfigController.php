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
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Page TSconfig viewer in Web -> Info
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class InfoPageTyposcriptConfigController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using InfoPageTyposcriptConfigController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using InfoPageTyposcriptConfigController::$function_key is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using InfoPageTyposcriptConfigController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using InfoPageTyposcriptConfigController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
        'extObj' => 'Using InfoPageTyposcriptConfigController::$extObj is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'modMenu' => 'Using InfoPageTyposcriptConfigController::modMenu() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'extObjContent' => 'Using InfoPageTyposcriptConfigController::extObjContent() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var StandaloneView
     */
    protected $view;

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
        $languageService = $this->getLanguageService();
        $languageService->includeLLFile('EXT:info/Resources/Private/Language/InfoPageTsConfig.xlf');
        $this->view = $this->getFluidTemplateObject();
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $languageService->includeLLFile($this->localLangFile);
        }
        $this->id = (int)GeneralUtility::_GP('id');
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
    }

    /**
     * Main, called from parent object
     *
     * @return string HTML output
     */
    public function main()
    {
        $pageId = (int)GeneralUtility::_GP('id');
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        if ($pageId === 0) {
            $this->view->assign('pageZero', 1);
            $pagesUsingTSConfig = $this->getOverviewOfPagesUsingTSConfig();
            if (count($pagesUsingTSConfig) > 0) {
                $this->view->assign('overviewOfPagesUsingTSConfig', $pagesUsingTSConfig);
            }
        } else {
            if ($this->pObj->MOD_SETTINGS['tsconf_parts'] == 99) {
                $TSparts = BackendUtility::getRawPagesTSconfig($this->id);
                $lines = [];
                $pUids = [];

                foreach ($TSparts as $k => $v) {
                    if ($k !== 'uid_0') {
                        $line = [];
                        if ($k === 'defaultPageTSconfig') {
                            $line['defaultPageTSconfig'] = 1;
                        } else {
                            $editIdList = substr($k, 4);
                            $pUids[] = $editIdList;
                            $row = BackendUtility::getRecordWSOL('pages', $editIdList);

                            $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL);
                            $urlParameters = [
                                'edit' => [
                                    'pages' => [
                                        $editIdList => 'edit',
                                    ]
                                ],
                                'columnsOnly' => 'TSconfig',
                                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                            ];
                            $line['editIcon'] = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                            $line['editTitle'] = 'editTSconfig';
                            $line['title'] = BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $row['uid'])
                                . ' ' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $row));
                        }
                        $tsparser = GeneralUtility::makeInstance(TypoScriptParser::class);
                        $tsparser->lineNumberOffset = 0;
                        $line['content'] = $tsparser->doSyntaxHighlight(trim($v) . LF);
                        $lines[] = $line;
                    }
                }

                if (!empty($pUids)) {
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                implode(',', $pUids) => 'edit',
                            ]
                        ],
                        'columnsOnly' => 'TSconfig',
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $editIcon = htmlspecialchars($url);
                    $editTitle = 'editTSconfig_all';
                } else {
                    $editIcon = '';
                    $editTitle = '';
                }

                $this->view->assign('tsconfParts99', 1);
                $this->view->assign('csh', BackendUtility::cshItem('_MOD_web_info', 'tsconfig_edit', null, '|'));
                $this->view->assign('lines', $lines);
                $this->view->assign('editIcon', $editIcon);
                $this->view->assign('editTitle', $editTitle);
            } else {
                $this->view->assign('tsconfParts99', 0);
                // Defined global here!
                $tmpl = GeneralUtility::makeInstance(ExtendedTemplateService::class);
                $tmpl->ext_expandAllNotes = 1;
                $tmpl->ext_noPMicons = 1;

                $pageTsConfig = BackendUtility::getPagesTSconfig($this->id);
                switch ($this->pObj->MOD_SETTINGS['tsconf_parts']) {
                    case '1':
                        $pageTsConfig = $pageTsConfig['mod.'] ?? [];
                        break;
                    case '1a':
                        $pageTsConfig = $pageTsConfig['mod.']['web_layout.'] ?? [];
                        break;
                    case '1b':
                        $pageTsConfig = $pageTsConfig['mod.']['web_view.'] ?? [];
                        break;
                    case '1c':
                        $pageTsConfig = $pageTsConfig['mod.']['web_modules.'] ?? [];
                        break;
                    case '1d':
                        $pageTsConfig = $pageTsConfig['mod.']['web_list.'] ?? [];
                        break;
                    case '1e':
                        $pageTsConfig = $pageTsConfig['mod.']['web_info.'] ?? [];
                        break;
                    case '1f':
                        $pageTsConfig = $pageTsConfig['mod.']['web_func.'] ?? [];
                        break;
                    case '1g':
                        $pageTsConfig = $pageTsConfig['mod.']['web_ts.'] ?? [];
                        break;
                    case '2':
                        $pageTsConfig = $pageTsConfig['RTE.'] ?? [];
                        break;
                    case '5':
                        $pageTsConfig = $pageTsConfig['TCEFORM.'] ?? [];
                        break;
                    case '6':
                        $pageTsConfig = $pageTsConfig['TCEMAIN.'] ?? [];
                        break;
                    case '3':
                        $pageTsConfig = $pageTsConfig['TSFE.'] ?? [];
                        break;
                    case '7':
                        $pageTsConfig = $pageTsConfig['TCAdefaults.'] ?? [];
                        break;
                    case '4':
                        $pageTsConfig = $pageTsConfig['user.'] ?? [];
                        break;
                    default:
                        // Entire array
                }

                $this->view->assign('csh', BackendUtility::cshItem('_MOD_web_info', 'tsconfig_hierarchy', null, '|'));
                $this->view->assign('tree', $tmpl->ext_getObjTree($pageTsConfig, '', '', '', '', $this->pObj->MOD_SETTINGS['tsconf_alphaSort']));
            }
            $this->view->assign('alphaSort', BackendUtility::getFuncCheck($this->id, 'SET[tsconf_alphaSort]', $this->pObj->MOD_SETTINGS['tsconf_alphaSort'], '', '', 'id="checkTsconf_alphaSort"'));
            $this->view->assign('dropdownMenu', BackendUtility::getDropdownMenu($this->id, 'SET[tsconf_parts]', $this->pObj->MOD_SETTINGS['tsconf_parts'], $this->pObj->MOD_MENU['tsconf_parts']));
        }
        return $this->view->render();
    }

    /**
     * Function menu initialization
     *
     * @return array Menu array
     */
    protected function modMenu()
    {
        $lang = $this->getLanguageService();
        $modMenuAdd = [
            'tsconf_parts' => [
                0 => $lang->getLL('tsconf_parts_0'),
                1 => $lang->getLL('tsconf_parts_1'),
                '1a' => $lang->getLL('tsconf_parts_1a'),
                '1b' => $lang->getLL('tsconf_parts_1b'),
                '1c' => $lang->getLL('tsconf_parts_1c'),
                '1d' => $lang->getLL('tsconf_parts_1d'),
                '1e' => $lang->getLL('tsconf_parts_1e'),
                '1f' => $lang->getLL('tsconf_parts_1f'),
                '1g' => $lang->getLL('tsconf_parts_1g'),
                2 => 'RTE.',
                7 => 'TCAdefaults.',
                5 => 'TCEFORM.',
                6 => 'TCEMAIN.',
                3 => 'TSFE.',
                4 => 'user.',
                99 => $lang->getLL('tsconf_configFields')
            ],
            'tsconf_alphaSort' => '1'
        ];
        if (!$this->getBackendUser()->isAdmin()) {
            unset($modMenuAdd['tsconf_parts'][99]);
        }
        return $modMenuAdd;
    }

    /**
     * Renders table rows of all pages containing TSConfig together with its rootline
     *
     * @return array
     */
    protected function getOverviewOfPagesUsingTSConfig()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $res = $queryBuilder
            ->select('uid', 'TSconfig')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->neq(
                    'TSconfig',
                    $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                )
            )
            ->execute();

        $pageArray = [];

        while ($row = $res->fetch()) {
            $this->setInPageArray($pageArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1'), $row);
        }
        return $this->getList($pageArray);
    }

    /**
     * Set page in array
     * This function is called recursively and builds a multi-dimensional array that reflects the page
     * hierarchy.
     *
     * @param array $hierarchicArray The hierarchic array (passed by reference)
     * @param array $rootlineArray The rootline array
     * @param array $row The row from the database containing the uid and TSConfig fields
     */
    protected function setInPageArray(&$hierarchicArray, $rootlineArray, $row)
    {
        ksort($rootlineArray);
        reset($rootlineArray);
        if (!$rootlineArray[0]['uid']) {
            array_shift($rootlineArray);
        }
        $currentElement = current($rootlineArray);
        $hierarchicArray[$currentElement['uid']] = htmlspecialchars($currentElement['title']);
        array_shift($rootlineArray);
        if (!empty($rootlineArray)) {
            if (!isset($hierarchicArray[$currentElement['uid'] . '.'])) {
                $hierarchicArray[$currentElement['uid'] . '.'] = [];
            }
            $this->setInPageArray($hierarchicArray[$currentElement['uid'] . '.'], $rootlineArray, $row);
        } else {
            $hierarchicArray[$currentElement['uid'] . '_'] = $this->extractLinesFromTSConfig($row);
        }
    }

    /**
     * Extract the lines of TSConfig from a given pages row
     *
     * @param array $row The row from the database containing the uid and TSConfig fields
     * @return array
     */
    protected function extractLinesFromTSConfig(array $row)
    {
        $out = [];
        $includeLines = 0;
        $out['uid'] = $row['uid'];
        $lines = GeneralUtility::trimExplode("\r\n", $row['TSconfig']);
        foreach ($lines as $line) {
            if (strpos($line, '<INCLUDE_TYPOSCRIPT:') !== false) {
                $includeLines++;
            }
        }
        $out['includeLines'] = $includeLines;
        $out['writtenLines'] = (count($lines) - $includeLines);
        return $out;
    }

    /**
     * Get the list of pages to show.
     * This function is called recursively
     *
     * @param array $pageArray The Page Array
     * @param array $lines Lines that have been processed up to this point
     * @param int $pageDepth The level of the current $pageArray being processed
     * @return array
     */
    protected function getList($pageArray, $lines = [], $pageDepth = 0)
    {
        if (!is_array($pageArray)) {
            return $lines;
        }

        foreach ($pageArray as $identifier => $_) {
            if (!MathUtility::canBeInterpretedAsInteger($identifier)) {
                continue;
            }
            $line = [];
            $line['padding'] = ($pageDepth * 20) + 10;
            if (isset($pageArray[$identifier . '_'])) {
                $line['link'] = GeneralUtility::linkThisScript(['id' => $identifier]);
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['title'] = 'ID: ' . $identifier;
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($pageArray[$identifier], 30);
                $line['includedFiles'] = ($pageArray[$identifier . '_']['includeLines'] === 0 ? '' : $pageArray[$identifier . '_']['includeLines']);
                $line['lines'] = ($pageArray[$identifier . '_']['writtenLines'] === 0 ? '' : $pageArray[$identifier . '_']['writtenLines']);
            } else {
                $line['link'] = '';
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['title'] = '';
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($pageArray[$identifier], 30);
                $line['includedFiles'] = '';
                $line['lines'] = '';
            }
            $lines[] = $line;
            $lines = $this->getList($pageArray[$identifier . '.'], $lines, $pageDepth + 1);
        }
        return $lines;
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Templates')]);

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:info/Resources/Private/Templates/PageTsConfig.html'));

        $view->getRequest()->setControllerExtensionName('info');
        return $view;
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
