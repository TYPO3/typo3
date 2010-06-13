<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */

$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_analyzer/locallang.xml');

class tx_tstemplateanalyzer extends t3lib_extobjbase {
	function init(&$pObj,$conf)	{
		parent::init($pObj,$conf);

		$this->pObj->modMenu_setDefaultList.= ',ts_analyzer_checkLinenum,ts_analyzer_checkSyntax,ts_analyzer_checkSyntaxBlockmode';
	}

	function modMenu()	{
		global $LANG;

		return array (
			'ts_analyzer_checkSetup' => '1',
			'ts_analyzer_checkConst' => '1',
			'ts_analyzer_checkLinenum' => '1',
			'ts_analyzer_checkComments' => '1',
			'ts_analyzer_checkCrop' => '1',
			'ts_analyzer_checkSyntax' => '1',
			'ts_analyzer_checkSyntaxBlockmode' => '1',
		);
	}

	function initialize_editor($pageId,$template_uid=0)	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl,$tplRow,$theConstants,$rootLine;

		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

			// Gets the rootLine
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
		$rootLine = $sys_page->getRootLine($pageId);
		$tmpl->runThroughTemplates($rootLine,$template_uid);	// This generates the constants/config + hierarchy info for the template.

		$tplRow = $tmpl->ext_getFirstTemplate($pageId,$template_uid);	// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		if (is_array($tplRow))	{	// IF there was a template...
			return 1;
		}
	}
	function main()	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		global $tmpl,$tplRow,$theConstants,$rootLine;

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
			$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('currentTemplate', true) ,
				t3lib_iconWorks::getSpriteIconForRecord('sys_template', $tplRow) . '<strong>' .
				$this->pObj->linkWrapTemplateTitle($tplRow["title"]) . '</strong>' .
				htmlspecialchars(trim($tplRow["sitetitle"]) ? ' - (' . $tplRow["sitetitle"] . ')' : ''));
		}
		if ($manyTemplatesMenu)	{
			$theOutput.=$this->pObj->doc->section("",$manyTemplatesMenu);
		}

		//	debug($tmpl->hierarchyInfo);

		$tmpl->clearList_const_temp = array_flip($tmpl->clearList_const);
		$tmpl->clearList_setup_temp = array_flip($tmpl->clearList_setup);

		$pointer = count($tmpl->hierarchyInfo);
		$tmpl->hierarchyInfoArr = $tmpl->ext_process_hierarchyInfo(array(), $pointer);
		$tmpl->processIncludes();

		$hierarArr = array();
		$head = '<tr class="t3-row-header">';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('title', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('rootlevel', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('clearSetup', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('clearConstants', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('pid', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('rootline', true) . '</td>';
		$head.= '<td>' . $GLOBALS['LANG']->getLL('nextLevel', true) . '</td>';
		$head.= '</tr>';
		$hierar = implode(array_reverse($tmpl->ext_getTemplateHierarchyArr($tmpl->hierarchyInfoArr, "", array(), 1)), "");
		$hierar= '<table id="ts-analyzer" border="0" cellpadding="0" cellspacing="1">' . $head . $hierar . '</table>';

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('templateHierarchy', true), $hierar, 0, 1);

		$completeLink = '<p><a href="index.php?id=' . $GLOBALS['SOBE']->id . '&amp;template=all">' . $GLOBALS['LANG']->getLL('viewCompleteTS', TRUE) . '</a></p>';
		$theOutput .= $this->pObj->doc->spacer(5);
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('completeTS', TRUE), $completeLink, 0, 1);


			// Output options
		$theOutput.=$this->pObj->doc->spacer(25);
		$theOutput.=$this->pObj->doc->divider(0);
		$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('displayOptions', true), '', 1, 1);
		$addParams = t3lib_div::_GET('template') ? '&template=' . t3lib_div::_GET('template') : '';
		$theOutput .= '<div class="tst-analyzer-options">' .
			t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkLinenum]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"], '', $addParams, 'id="checkTs_analyzer_checkLinenum"') .
			'<label for="checkTs_analyzer_checkLinenum">' . $GLOBALS['LANG']->getLL('lineNumbers', true) . '</label> ' .
			t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkSyntax]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"], '', $addParams, 'id="checkTs_analyzer_checkSyntax"') .
			'<label for="checkTs_analyzer_checkSyntax">' . $GLOBALS['LANG']->getLL('syntaxHighlight', true) . '</label> ' .
			(!$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"] ?
				t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkComments]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"], '', $addParams, 'id="checkTs_analyzer_checkComments"') .
				'<label for="checkTs_analyzer_checkComments">' . $GLOBALS['LANG']->getLL('comments', true) . '</label> ' .
				t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkCrop]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"], '', $addParams, 'id="checkTs_analyzer_checkCrop"') .
				'<label for="checkTs_analyzer_checkCrop">' . $GLOBALS['LANG']->getLL('cropLines', true) . '</label> '
				:
				t3lib_BEfunc::getFuncCheck($this->pObj->id, "SET[ts_analyzer_checkSyntaxBlockmode]", $this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"], '', $addParams, 'id="checkTs_analyzer_checkSyntaxBlockmode"') .
				'<label for="checkTs_analyzer_checkSyntaxBlockmode">' . $GLOBALS['LANG']->getLL('blockMode', true) . '</label> '
			) . '</div>';



				// Output Constants
			if (t3lib_div::_GET('template')) {
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('constants', true), "", 0, 1);
				$theOutput .= $this->pObj->doc->sectionEnd();
				$theOutput .= '
					<table border=0 cellpadding=1 cellspacing=0>
				';
				$tmpl->ext_lineNumberOffset = -2;	// Don't know why -2 and not 0... :-) But works.
				$tmpl->ext_lineNumberOffset_mode = "const";
				$tmpl->ext_lineNumberOffset += count(explode(LF, t3lib_TSparser::checkIncludeLines("" . $GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_constants"]))) + 1;

				reset($tmpl->clearList_const);
				foreach ($tmpl->constants as $key => $val) {
					$cVal = current($tmpl->clearList_const);
					if ($cVal == t3lib_div::_GET('template') || t3lib_div::_GET('template') == "all")	{
						$theOutput .= '
							<tr>
								<td><img src="clear.gif" width="3" height="1" /></td><td class="bgColor2"><strong>' . $tmpl->templateTitles[$cVal] . '</strong></td></tr>
							<tr>
								<td><img src="clear.gif" width="3" height="1" /></td>
								<td class="bgColor2"><table border=0 cellpadding=0 cellspacing=0 class="bgColor0" width="100%"><tr><td nowrap>' .
								$tmpl->ext_outputTS(array($val), $this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"], $this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"], $this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"], $this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"], $this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"]) .
								'</td></tr></table>
								</td>
							</tr>
						';
						if (t3lib_div::_GET('template') != "all") {
							break;
						}
					}
					$tmpl->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
					next($tmpl->clearList_const);
				}
				$theOutput .= '
					</table>
				';
			}

			// Output setup
			if (t3lib_div::_GET('template')) {
				$theOutput .= $this->pObj->doc->spacer(15);
				$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('setup', true), "", 0, 1);
				$theOutput .= $this->pObj->doc->sectionEnd();
				$theOutput .= '
					<table border=0 cellpadding=1 cellspacing=0>
				';
				$tmpl->ext_lineNumberOffset = 0;
				$tmpl->ext_lineNumberOffset_mode = "setup";
				$tmpl->ext_lineNumberOffset += count(explode(LF, t3lib_TSparser::checkIncludeLines("" . $GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_setup"]))) + 1;

				reset($tmpl->clearList_setup);
				foreach ($tmpl->config as $key => $val)	{
					if (current($tmpl->clearList_setup) == t3lib_div::_GET('template') || t3lib_div::_GET('template') == "all")	{
						$theOutput .= '
							<tr>
								<td><img src="clear.gif" width="3" height="1" /></td><td class="bgColor2"><strong>' . $tmpl->templateTitles[current($tmpl->clearList_setup)] . '</strong></td></tr>
							<tr>
								<td><img src="clear.gif" width="3" height="1" /></td>
								<td class="bgColor2"><table border=0 cellpadding=0 cellspacing=0 class="bgColor0" width="100%"><tr><td nowrap>'.$tmpl->ext_outputTS(array($val),$this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"]).'</td></tr></table>
								</td>
							</tr>
						';
						if (t3lib_div::_GET('template') != "all") {
							break;
						}
					}
					$tmpl->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
					next($tmpl->clearList_setup);
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