<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_analyzer/locallang.xml');

class tx_tstemplateanalyzer extends t3lib_extobjbase {
	function init(&$pObj,$conf)	{
		parent::init($pObj,$conf);

		$this->pObj->modMenu_setDefaultList.= ',ts_analyzer_checkLinenum,ts_analyzer_checkSyntax';
	}

	function modMenu()	{
		return array (
			'ts_analyzer_checkSetup' => '1',
			'ts_analyzer_checkConst' => '1',
			'ts_analyzer_checkLinenum' => '1',
			'ts_analyzer_checkComments' => '1',
			'ts_analyzer_checkCrop' => '1',
			'ts_analyzer_checkSyntax' => '1',
		);
	}

	function initialize_editor($pageId,$template_uid=0)	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!

		$GLOBALS['tmpl'] = t3lib_div::makeInstance("t3lib_tsparser_ext");
			// Do not log time-performance information
		$GLOBALS['tmpl']->tt_track = 0;
		$GLOBALS['tmpl']->init();

			// Gets the rootLine
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
		$GLOBALS['rootLine'] = $sys_page->getRootLine($pageId);
			// This generates the constants/config + hierarchy info for the template.
		$GLOBALS['tmpl']->runThroughTemplates($GLOBALS['rootLine'], $template_uid);

			// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		$GLOBALS['tplRow'] = $GLOBALS['tmpl']->ext_getFirstTemplate($pageId,$template_uid);
		if (is_array($GLOBALS['tplRow'])) {
				// IF there was a template...
			return 1;
		}
	}

	function main()	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!

		// **************************
		// Checking for more than one template an if, set a menu...
		// **************************
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu)	{
			$template_uid = $this->pObj->MOD_SETTINGS["templatesOnPage"];
		}

		// **************************
		// Main
		// **************************

		// BUGBUG: Should we check if the uset may at all read and write template-records???
		$existTemplate = $this->initialize_editor($this->pObj->id,$template_uid);		// initialize
		if ($existTemplate)	{
			$theOutput.=$this->pObj->doc->divider(5);
			$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('currentTemplate', TRUE) ,
				t3lib_iconWorks::getSpriteIconForRecord('sys_template', $GLOBALS['tplRow']) . '<strong>' .
				$this->pObj->linkWrapTemplateTitle($GLOBALS['tplRow']["title"]) . '</strong>' .
				htmlspecialchars(trim($GLOBALS['tplRow']["sitetitle"]) ? ' - (' . $GLOBALS['tplRow']["sitetitle"] . ')' : ''));
		}
		if ($manyTemplatesMenu)	{
			$theOutput.=$this->pObj->doc->section("",$manyTemplatesMenu);
		}

		//	debug($GLOBALS['tmpl']->hierarchyInfo);

		$GLOBALS['tmpl']->clearList_const_temp = array_flip($GLOBALS['tmpl']->clearList_const);
		$GLOBALS['tmpl']->clearList_setup_temp = array_flip($GLOBALS['tmpl']->clearList_setup);

		$pointer = count($GLOBALS['tmpl']->hierarchyInfo);
		$GLOBALS['tmpl']->hierarchyInfoArr = $GLOBALS['tmpl']->ext_process_hierarchyInfo(array(), $pointer);
		$GLOBALS['tmpl']->processIncludes();

		$hierarArr = array();
		$head = '<tr class="t3-row-header">';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('title', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('rootlevel', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('clearSetup', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('clearConstants', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('pid', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('rootline', TRUE) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('nextLevel', TRUE) . '</td>';
		$head.= '</tr>';
		$hierar = implode(array_reverse($GLOBALS['tmpl']->ext_getTemplateHierarchyArr($GLOBALS['tmpl']->hierarchyInfoArr, "", array(), 1)), "");
		$hierar= '<table id="ts-analyzer" border="0" cellpadding="0" cellspacing="1">' . $head . $hierar . '</table>';

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('templateHierarchy', TRUE), $hierar, 0, 1);

		$completeLink = '<p><a href="index.php?id=' . $GLOBALS['SOBE']->id . '&amp;template=all">' . $GLOBALS['LANG']->getLL('viewCompleteTS', TRUE) . '</a></p>';
		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('completeTS', TRUE), $completeLink, 0, 1);


			// Output options
		$theOutput.=$this->pObj->doc->spacer(25);
		$theOutput.=$this->pObj->doc->divider(0);
		$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('displayOptions', TRUE), '', 1, 1);
		$addParams = t3lib_div::_GET('template') ? '&template=' . t3lib_div::_GET('template') : '';
		$theOutput .= '<div class="tst-analyzer-options">' .
			t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkLinenum]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"], '', $addParams, 'id="checkTs_analyzer_checkLinenum"') .
			'<label for="checkTs_analyzer_checkLinenum">' . $GLOBALS['LANG']->getLL('lineNumbers', TRUE) . '</label> ' .
			t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkSyntax]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"], '', $addParams, 'id="checkTs_analyzer_checkSyntax"') .
			'<label for="checkTs_analyzer_checkSyntax">' . $GLOBALS['LANG']->getLL('syntaxHighlight', TRUE) . '</label> ' .
			(!$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"] ?
				t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkComments]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"], '', $addParams, 'id="checkTs_analyzer_checkComments"') .
				'<label for="checkTs_analyzer_checkComments">' . $GLOBALS['LANG']->getLL('comments', TRUE) . '</label> ' .
				t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkCrop]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"], '', $addParams, 'id="checkTs_analyzer_checkCrop"') .
				'<label for="checkTs_analyzer_checkCrop">' . $GLOBALS['LANG']->getLL('cropLines', TRUE) . '</label> '
				:
				''
			) . '</div>';



				// Output Constants
			if (t3lib_div::_GET('template')) {
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('constants', TRUE), "", 0, 1);
				$theOutput .= $this->pObj->doc->sectionEnd();
				$theOutput .= '
					<table border="0" cellpadding="1" cellspacing="0">
				';
					// Don't know why -2 and not 0... :-) But works.
				$GLOBALS['tmpl']->ext_lineNumberOffset = -2;
				$GLOBALS['tmpl']->ext_lineNumberOffset_mode = "const";
				$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, t3lib_TSparser::checkIncludeLines("" . $GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_constants"]))) + 1;

				reset($GLOBALS['tmpl']->clearList_const);
				foreach ($GLOBALS['tmpl']->constants as $key => $val) {
					$cVal = current($GLOBALS['tmpl']->clearList_const);
					if ($cVal == t3lib_div::_GET('template') || t3lib_div::_GET('template') == 'all') {
						$theOutput .= '
							<tr>
								<td><img src="clear.gif" width="3" height="1" alt="" /></td><td class="bgColor2"><strong>' . htmlspecialchars($GLOBALS['tmpl']->templateTitles[$cVal]) . '</strong></td></tr>
							<tr>
								<td><img src="clear.gif" width="3" height="1" alt="" /></td>
								<td class="bgColor2"><table border="0" cellpadding="0" cellspacing="0" class="bgColor0" width="100%"><tr><td nowrap="nowrap">' .
								$GLOBALS['tmpl']->ext_outputTS(array($val), $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], 0) .
								'</td></tr></table>
								</td>
							</tr>
						';
						if (t3lib_div::_GET('template') != "all") {
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
			if (t3lib_div::_GET('template')) {
				$theOutput .= $this->pObj->doc->spacer(15);
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('setup', TRUE), "", 0, 1);
				$theOutput .= $this->pObj->doc->sectionEnd();
				$theOutput .= '
					<table border="0" cellpadding="1" cellspacing="0">
				';
				$GLOBALS['tmpl']->ext_lineNumberOffset = 0;
				$GLOBALS['tmpl']->ext_lineNumberOffset_mode = "setup";
				$GLOBALS['tmpl']->ext_lineNumberOffset += count(explode(LF, t3lib_TSparser::checkIncludeLines("" . $GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_setup"]))) + 1;

				reset($GLOBALS['tmpl']->clearList_setup);
				foreach ($GLOBALS['tmpl']->config as $key => $val)	{
					if (current($GLOBALS['tmpl']->clearList_setup) == t3lib_div::_GET('template') || t3lib_div::_GET('template') == 'all') {
						$theOutput .= '
							<tr>
								<td><img src="clear.gif" width="3" height="1" alt="" /></td><td class="bgColor2"><strong>' . htmlspecialchars($GLOBALS['tmpl']->templateTitles[current($GLOBALS['tmpl']->clearList_setup)]) . '</strong></td></tr>
							<tr>
								<td><img src="clear.gif" width="3" height="1" alt="" /></td>
								<td class="bgColor2"><table border="0" cellpadding="0" cellspacing="0" class="bgColor0" width="100%"><tr><td nowrap="nowrap">' .
									  $GLOBALS['tmpl']->ext_outputTS(
										array($val),
										$this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'],
										$this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'],
										$this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'],
										$this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'],
										0) .
										'</td></tr></table>
								</td>
							</tr>
						';
						if (t3lib_div::_GET('template') != "all") {
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

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_analyzer/class.tx_tstemplateanalyzer.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_analyzer/class.tx_tstemplateanalyzer.php"]);
}

?>