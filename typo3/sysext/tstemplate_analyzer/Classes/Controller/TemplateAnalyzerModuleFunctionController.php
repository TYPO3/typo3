<?php
namespace TYPO3\CMS\TstemplateAnalyzer\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * TypoScript template analyzer
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TemplateAnalyzerModuleFunctionController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Init
	 *
	 * @param object $pObj
	 * @param array $conf
	 * @return void
	 * @todo Define visibility
	 */
	public function init(&$pObj, $conf) {
		parent::init($pObj, $conf);
		$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_analyzer/locallang.xlf');
		$this->pObj->modMenu_setDefaultList .= ',ts_analyzer_checkLinenum,ts_analyzer_checkSyntax';
	}

	/**
	 * Mod menu
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function modMenu() {
		return array(
			'ts_analyzer_checkSetup' => '1',
			'ts_analyzer_checkConst' => '1',
			'ts_analyzer_checkLinenum' => '1',
			'ts_analyzer_checkComments' => '1',
			'ts_analyzer_checkCrop' => '1',
			'ts_analyzer_checkSyntax' => '1'
		);
	}

	/**
	 * Initialize editor
	 *
	 * @param integer $pageId
	 * @param integer $template_uid
	 * @return integer
	 * @todo Define visibility
	 */
	public function initialize_editor($pageId, $template_uid = 0) {
		// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		$GLOBALS['tmpl'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
		// Do not log time-performance information
		$GLOBALS['tmpl']->tt_track = 0;
		$GLOBALS['tmpl']->init();
		// Gets the rootLine
		$sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$GLOBALS['rootLine'] = $sys_page->getRootLine($pageId);
		// This generates the constants/config + hierarchy info for the template.
		$GLOBALS['tmpl']->runThroughTemplates($GLOBALS['rootLine'], $template_uid);
		// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$GLOBALS['tplRow'] = $GLOBALS['tmpl']->ext_getFirstTemplate($pageId, $template_uid);
		if (is_array($GLOBALS['tplRow'])) {
			// IF there was a template...
			return 1;
		}
	}

	/**
	 * Main
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function main() {
		// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		// Checking for more than one template an if, set a menu...
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu) {
			$template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
		}
		// BUGBUG: Should we check if the uset may at all read and write template-records???
		$existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
		// initialize
		if ($existTemplate) {
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('currentTemplate', TRUE), \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('sys_template', $GLOBALS['tplRow']) . '<strong>' . $this->pObj->linkWrapTemplateTitle($GLOBALS['tplRow']['title']) . '</strong>' . htmlspecialchars((trim($GLOBALS['tplRow']['sitetitle']) ? ' (' . $GLOBALS['tplRow']['sitetitle'] . ')' : '')));
		}
		if ($manyTemplatesMenu) {
			$theOutput .= $this->pObj->doc->section('', $manyTemplatesMenu);
		}
		$GLOBALS['tmpl']->clearList_const_temp = array_flip($GLOBALS['tmpl']->clearList_const);
		$GLOBALS['tmpl']->clearList_setup_temp = array_flip($GLOBALS['tmpl']->clearList_setup);
		$pointer = count($GLOBALS['tmpl']->hierarchyInfo);
		$GLOBALS['tmpl']->hierarchyInfoArr = $GLOBALS['tmpl']->ext_process_hierarchyInfo(array(), $pointer);
		$GLOBALS['tmpl']->processIncludes();
		$hierarArr = array();
		$head = '<tr class="t3-row-header">';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('title', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('rootlevel', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('clearSetup', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('clearConstants', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('pid', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('rootline', TRUE) . '</td>';
		$head .= '<td>' . $GLOBALS['LANG']->getLL('nextLevel', TRUE) . '</td>';
		$head .= '</tr>';
		$hierar = implode(array_reverse($GLOBALS['tmpl']->ext_getTemplateHierarchyArr($GLOBALS['tmpl']->hierarchyInfoArr, '', array(), 1)), '');
		$hierar = '<table id="ts-analyzer" cellpadding="0" cellspacing="0">' . $head . $hierar . '</table>';
		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('templateHierarchy', TRUE), $hierar, 0, 1);
		$urlParameters = array(
			'id' => $GLOBALS['SOBE']->id,
			'template' => 'all'
		);
		$aHref = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_ts', $urlParameters);
		$completeLink = '<p><a href="' . htmlspecialchars($aHref) . '">' . $GLOBALS['LANG']->getLL('viewCompleteTS', TRUE) . '</a></p>';
		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('completeTS', TRUE), $completeLink, 0, 1);
		$theOutput .= $this->pObj->doc->spacer(15);
		// Output options
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('displayOptions', TRUE), '', FALSE, TRUE);
		$addParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') ? '&template=' . \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') : '';
		$theOutput .= '<div class="tst-analyzer-options">' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkLinenum]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], '', $addParams, 'id="checkTs_analyzer_checkLinenum"') . '<label for="checkTs_analyzer_checkLinenum">' . $GLOBALS['LANG']->getLL('lineNumbers', TRUE) . '</label> ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkSyntax]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], '', $addParams, 'id="checkTs_analyzer_checkSyntax"') . '<label for="checkTs_analyzer_checkSyntax">' . $GLOBALS['LANG']->getLL('syntaxHighlight', TRUE) . '</label> ' . (!$this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'] ? \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkComments]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], '', $addParams, 'id="checkTs_analyzer_checkComments"') . '<label for="checkTs_analyzer_checkComments">' . $GLOBALS['LANG']->getLL('comments', TRUE) . '</label> ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkCrop]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], '', $addParams, 'id="checkTs_analyzer_checkCrop"') . '<label for="checkTs_analyzer_checkCrop">' . $GLOBALS['LANG']->getLL('cropLines', TRUE) . '</label> ' : '') . '</div>';
		$theOutput .= $this->pObj->doc->spacer(25);
		// Output Constants
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template')) {
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('constants', TRUE), '', 0, 1);
			$theOutput .= $this->pObj->doc->sectionEnd();
			$theOutput .= '
				<table class="ts-typoscript" border="0" cellpadding="1" cellspacing="0">
			';
			// Don't know why -2 and not 0... :-) But works.
			$GLOBALS['tmpl']->ext_lineNumberOffset = -2;
			$GLOBALS['tmpl']->ext_lineNumberOffset_mode = 'const';
			$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines(('' . $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'])))) + 1;
			reset($GLOBALS['tmpl']->clearList_const);
			foreach ($GLOBALS['tmpl']->constants as $key => $val) {
				$cVal = current($GLOBALS['tmpl']->clearList_const);
				if ($cVal == \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') || \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') == 'all') {
					$theOutput .= '
						<tr>
							</td><td class="bgColor2"><strong>' . htmlspecialchars($GLOBALS['tmpl']->templateTitles[$cVal]) . '</strong></td></tr>
						<tr>
							<td class="bgColor2"><table border="0" cellpadding="0" cellspacing="0" class="bgColor0" width="100%"><tr><td nowrap="nowrap">' . $GLOBALS['tmpl']->ext_outputTS(array($val), $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], 0) . '</td></tr></table>
							</td>
						</tr>
					';
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') != 'all') {
						break;
					}
				}
				$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
				next($GLOBALS['tmpl']->clearList_const);
			}
			$theOutput .= '
				</table>
			';
		}
		// Output setup
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template')) {
			$theOutput .= $this->pObj->doc->spacer(15);
			$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('setup', TRUE), '', 0, 1);
			$theOutput .= $this->pObj->doc->sectionEnd();
			$theOutput .= '
				<table class="ts-typoscript" border="0" cellpadding="1" cellspacing="0">
			';
			$GLOBALS['tmpl']->ext_lineNumberOffset = 0;
			$GLOBALS['tmpl']->ext_lineNumberOffset_mode = 'setup';
			$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines(('' . $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup'])))) + 1;
			reset($GLOBALS['tmpl']->clearList_setup);
			foreach ($GLOBALS['tmpl']->config as $key => $val) {
				if (current($GLOBALS['tmpl']->clearList_setup) == \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') || \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') == 'all') {
					$theOutput .= '
						<tr>
							<td class="bgColor2"><strong>' . htmlspecialchars($GLOBALS['tmpl']->templateTitles[current($GLOBALS['tmpl']->clearList_setup)]) . '</strong></td></tr>
						<tr>
							<td class="bgColor2"><table border="0" cellpadding="0" cellspacing="0" class="bgColor0" width="100%"><tr><td nowrap="nowrap">' . $GLOBALS['tmpl']->ext_outputTS(array($val), $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], 0) . '</td></tr></table>
							</td>
						</tr>
					';
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('template') != 'all') {
						break;
					}
				}
				$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
				next($GLOBALS['tmpl']->clearList_setup);
			}
			$theOutput .= '
				</table>
			';
		}
		return $theOutput;
	}

}

?>