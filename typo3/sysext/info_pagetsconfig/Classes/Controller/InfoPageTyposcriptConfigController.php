<?php
namespace TYPO3\CMS\InfoPagetsconfig\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Page TSconfig viewer
 */
class InfoPageTyposcriptConfigController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:info_pagetsconfig/Resources/Private/Language/locallang.xlf');

        $this->view = $this->getFluidTemplateObject();
    }

    /**
     * Function menu initialization
     *
     * @return array Menu array
     */
    public function modMenu()
    {
        $lang = $this->getLanguageService();
        $modMenuAdd = array(
            'tsconf_parts' => array(
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
                5 => 'TCEFORM.',
                6 => 'TCEMAIN.',
                3 => 'TSFE.',
                4 => 'user.',
                99 => $lang->getLL('tsconf_configFields')
            ),
            'tsconf_alphaSort' => '1'
        );
        if (!$this->getBackendUser()->isAdmin()) {
            unset($modMenuAdd['tsconf_parts'][99]);
        }
        return $modMenuAdd;
    }

    /**
     * Main function of class
     *
     * @return string HTML output
     */
    public function main()
    {
        $pageId = (int)(GeneralUtility::_GP('id'));

        if ($pageId === 0) {
            $this->view->assign('pageZero', 1);
            $this->view->assign('overviewOfPagesUsingTSConfig', $this->getOverviewOfPagesUsingTSConfig());
        } else {
            if ($this->pObj->MOD_SETTINGS['tsconf_parts'] == 99) {
                $TSparts = BackendUtility::getPagesTSconfig($this->pObj->id, null, true);
                $lines = array();
                $pUids = array();

                foreach ($TSparts as $k => $v) {
                    if ($k != 'uid_0') {
                        $line = array();
                        if ($k == 'defaultPageTSconfig') {
                            $line['defaultPageTSconfig'] = 1;
                        } else {
                            $pUids[] = substr($k, 4);
                            $row = BackendUtility::getRecordWSOL('pages', substr($k, 4));

                            $icon = $this->iconFactory->getIconForRecord('pages', $row, Icon::SIZE_SMALL);
                            $editIdList = substr($k, 4);
                            $urlParameters = [
                                'edit' => [
                                    'pages' => [
                                        $editIdList => 'edit',
                                    ]
                                ],
                                'columnsOnly' => 'TSconfig',
                                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                            ];
                            $line['editIcon'] = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                            $line['editTitle'] = 'editTSconfig';
                            $line['title'] = BackendUtility::wrapClickMenuOnIcon($icon, 'pages', $row['uid'])
                                . ' ' . htmlspecialchars(BackendUtility::getRecordTitle('pages', $row));
                        }
                        $tsparser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
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
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
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
                $tmpl = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);

                // Do not log time-performance information
                $tmpl->tt_track = 0;
                $tmpl->fixedLgd = 0;
                $tmpl->linkObjects = 0;
                $tmpl->bType = '';
                $tmpl->ext_expandAllNotes = 1;
                $tmpl->ext_noPMicons = 1;

                $beUser = $this->getBackendUser();
                switch ($this->pObj->MOD_SETTINGS['tsconf_parts']) {
                    case '1':
                        $modTSconfig = BackendUtility::getModTSconfig($this->pObj->id, 'mod');
                        break;
                    case '1a':
                        $modTSconfig = $beUser->getTSConfig('mod.web_layout', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1b':
                        $modTSconfig = $beUser->getTSConfig('mod.web_view', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1c':
                        $modTSconfig = $beUser->getTSConfig('mod.web_modules', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1d':
                        $modTSconfig = $beUser->getTSConfig('mod.web_list', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1e':
                        $modTSconfig = $beUser->getTSConfig('mod.web_info', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1f':
                        $modTSconfig = $beUser->getTSConfig('mod.web_func', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '1g':
                        $modTSconfig = $beUser->getTSConfig('mod.web_ts', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '2':
                        $modTSconfig = $beUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '5':
                        $modTSconfig = $beUser->getTSConfig('TCEFORM', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '6':
                        $modTSconfig = $beUser->getTSConfig('TCEMAIN', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '3':
                        $modTSconfig = $beUser->getTSConfig('TSFE', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    case '4':
                        $modTSconfig = $beUser->getTSConfig('user', BackendUtility::getPagesTSconfig($this->pObj->id));
                        break;
                    default:
                        $modTSconfig['properties'] = BackendUtility::getPagesTSconfig($this->pObj->id);
                }

                $modTSconfig = $modTSconfig['properties'];
                if (!is_array($modTSconfig)) {
                    $modTSconfig = array();
                }

                $this->view->assign('csh', BackendUtility::cshItem('_MOD_web_info', 'tsconfig_hierarchy', null, '|'));
                $this->view->assign('tree', $tmpl->ext_getObjTree($modTSconfig, '', '', '', '', $this->pObj->MOD_SETTINGS['tsconf_alphaSort']));
            }
            $this->view->assign('alphaSort', BackendUtility::getFuncCheck($this->pObj->id, 'SET[tsconf_alphaSort]', $this->pObj->MOD_SETTINGS['tsconf_alphaSort'], '', '', 'id="checkTsconf_alphaSort"'));
            $this->view->assign('dropdownMenu', BackendUtility::getDropdownMenu($this->pObj->id, 'SET[tsconf_parts]', $this->pObj->MOD_SETTINGS['tsconf_parts'], $this->pObj->MOD_MENU['tsconf_parts']));
        }
        return $this->view->render();
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
            ->where($queryBuilder->expr()->neq('TSconfig', $queryBuilder->quote('')))
            ->groupBy('uid')
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
     * @return void
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
                $hierarchicArray[$currentElement['uid'] . '.'] = array();
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
        $out = array();
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
    protected function getList($pageArray, $lines = array(), $pageDepth = 0)
    {
        if (!is_array($pageArray)) {
            return $lines;
        }

        foreach ($pageArray as $identifier => $_) {
            if (!MathUtility::canBeInterpretedAsInteger($identifier)) {
                continue;
            }
            $line = array();
            $line['padding'] = ($pageDepth * 20);
            if (isset($pageArray[$identifier . '_'])) {
                $line['link'] = htmlspecialchars(GeneralUtility::linkThisScript(array('id' => $identifier)));
                $line['icon'] = $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render();
                $line['title'] = htmlspecialchars('ID: ' . $identifier);
                $line['pageTitle'] = GeneralUtility::fixed_lgd_cs($pageArray[$identifier], 30);
                $line['includedFiles'] = ($pageArray[$identifier . '_']['includeLines'] === 0 ? '' : $pageArray[($identifier . '_')]['includeLines']);
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * returns a new standalone view, shorthand function
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject()
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:info_pagetsconfig/Resources/Private/Layouts')));
        $view->setPartialRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:info_pagetsconfig/Resources/Private/Partials')));
        $view->setTemplateRootPaths(array(GeneralUtility::getFileAbsFileName('EXT:info_pagetsconfig/Resources/Private/Templates')));

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:info_pagetsconfig/Resources/Private/Templates/Main.html'));

        $view->getRequest()->setControllerExtensionName('info_pagetsconfig');
        return $view;
    }
}
