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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:info_pagetsconfig/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Function menu initialization
     *
     * @return array Menu array
     */
    public function modMenu()
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
     * Main function of class
     *
     * @return string HTML output
     */
    public function main()
    {
        if ((int)(GeneralUtility::_GP('id')) === 0) {
            $lang = $this->getLanguageService();
            return '<div class="nowrap"><div class="table-fit"><table class="table table-striped table-hover" id="tsconfig-overview">' .
                '<thead>' .
                '<tr>' .
                '<th>' . $lang->getLL('pagetitle') . '</th>' .
                '<th>' . $lang->getLL('included_tsconfig_files') . '</th>' .
                '<th>' . $lang->getLL('written_tsconfig_lines') . '</th>' .
                '</tr>' .
                '</thead>' .
                '<tbody>' . implode('', $this->getOverviewOfPagesUsingTSConfig()) . '</tbody>' .
                '</table></div>';
        } else {
            $menu = '<div class="form-inline form-inline-spaced">';
            $menu .= BackendUtility::getDropdownMenu($this->pObj->id, 'SET[tsconf_parts]', $this->pObj->MOD_SETTINGS['tsconf_parts'], $this->pObj->MOD_MENU['tsconf_parts']);
            $menu .= '<div class="checkbox"><label for="checkTsconf_alphaSort">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[tsconf_alphaSort]', $this->pObj->MOD_SETTINGS['tsconf_alphaSort'], '', '', 'id="checkTsconf_alphaSort"') . ' ' . $this->getLanguageService()->getLL('sort_alphabetic', true) . '</label></div>';
            $menu .= '</div>';
            $theOutput = $this->pObj->doc->header($this->getLanguageService()->getLL('tsconf_title'));

            if ($this->pObj->MOD_SETTINGS['tsconf_parts'] == 99) {
                $TSparts = BackendUtility::getPagesTSconfig($this->pObj->id, null, true);
                $lines = [];
                $pUids = [];
                foreach ($TSparts as $k => $v) {
                    if ($k != 'uid_0') {
                        if ($k == 'defaultPageTSconfig') {
                            $pTitle = '<strong>' . $this->getLanguageService()->getLL('editTSconfig_default', true) . '</strong>';
                            $editIcon = '';
                        } else {
                            $pUids[] = substr($k, 4);
                            $row = BackendUtility::getRecordWSOL('pages', substr($k, 4));
                            $pTitle = $this->pObj->doc->getHeader('pages', $row, '', false);
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
                            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                            $editIcon = '<a href="' . htmlspecialchars($url) . '" title="' . $this->getLanguageService()->getLL('editTSconfig', true) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        $TScontent = nl2br(htmlspecialchars(trim($v) . LF));
                        $tsparser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
                        $tsparser->lineNumberOffset = 0;
                        $TScontent = $tsparser->doSyntaxHighlight(trim($v) . LF);
                        $lines[] = '
							<tr><td nowrap="nowrap" class="bgColor5">' . $pTitle . '</td></tr>
							<tr><td nowrap="nowrap" class="bgColor4">' . $TScontent . $editIcon . '</td></tr>
							<tr><td>&nbsp;</td></tr>
						';
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
                    $editIcon = '<a href="' . htmlspecialchars($url) . '" title="' . $this->getLanguageService()->getLL('editTSconfig_all', true) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '<strong>' . $this->getLanguageService()->getLL('editTSconfig_all', true) . '</strong>' . '</a>';
                } else {
                    $editIcon = '';
                }
                $theOutput .= '<div>';
                $theOutput .= BackendUtility::cshItem('_MOD_web_info', 'tsconfig_edit', null, '<span class="btn btn-default btn-sm">|</span>') . $menu . '
						<!-- Edit fields: -->
						<table border="0" cellpadding="0" cellspacing="1">' . implode('', $lines) . '</table><br />' . $editIcon;
                $theOutput .= '</div>';
            } else {
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
                    $modTSconfig = [];
                }

                $csh = BackendUtility::cshItem('_MOD_web_info', 'tsconfig_hierarchy', null, '<span class="btn btn-default btn-sm">|</span>');
                $tree = $tmpl->ext_getObjTree($modTSconfig, '', '', '', '', $this->pObj->MOD_SETTINGS['tsconf_alphaSort']);

                $theOutput .= '<div>';
                $theOutput .= $csh . $menu . '<div class="nowrap">' . $tree . '</div>';
                $theOutput .= '</div>';
            }
        }

        return $theOutput;
    }

    /**
     * Renders table rows of all pages containing TSConfig together with its rootline
     *
     * @return array
     */
    protected function getOverviewOfPagesUsingTSConfig()
    {
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            'uid, TSconfig',
            'pages',
            'TSconfig != \'\''
            . BackendUtility::deleteClause('pages')
            . BackendUtility::versioningPlaceholderClause('pages'), 'pages.uid');
        $pageArray = [];
        while ($row = $db->sql_fetch_assoc($res)) {
            $this->setInPageArray($pageArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1'), $row);
        }
        return $this->renderList($pageArray);
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
     * Render the list of pages to show.
     * This function is called recursively
     *
     * @param array $pageArray The Page Array
     * @param array $lines Lines that have been processed up to this point
     * @param int $pageDepth The level of the current $pageArray being processed
     * @return array
     */
    protected function renderList($pageArray, $lines = [], $pageDepth = 0)
    {
        $cellStyle = 'padding-left: ' . ($pageDepth * 20) . 'px';
        if (!is_array($pageArray)) {
            return $lines;
        }

        foreach ($pageArray as $identifier => $_) {
            if (!MathUtility::canBeInterpretedAsInteger($identifier)) {
                continue;
            }
            if (isset($pageArray[$identifier . '_'])) {
                $lines[] = '
				<tr>
					<td nowrap style="' . $cellStyle . '">
						<a href="'
                    . htmlspecialchars(GeneralUtility::linkThisScript(['id' => $identifier]))
                    . '" title="' . htmlspecialchars('ID: ' . $identifier) . '">'
                    . $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render()
                    . GeneralUtility::fixed_lgd_cs($pageArray[$identifier], 30) . '</a></td>
					<td>' . ($pageArray[$identifier . '_']['includeLines'] === 0 ? '' : $pageArray[($identifier . '_')]['includeLines']) . '</td>
					<td>' . ($pageArray[$identifier . '_']['writtenLines'] === 0 ? '' : $pageArray[$identifier . '_']['writtenLines']) . '</td>
					</tr>';
            } else {
                $lines[] = '<tr>
					<td nowrap style="' . $cellStyle . '">'
                    . $this->iconFactory->getIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $identifier), Icon::SIZE_SMALL)->render()
                    . GeneralUtility::fixed_lgd_cs($pageArray[$identifier], 30) . '</td>
					<td></td>
					<td></td>
					</tr>';
            }
            $lines = $this->renderList($pageArray[$identifier . '.'], $lines, $pageDepth + 1);
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
}
