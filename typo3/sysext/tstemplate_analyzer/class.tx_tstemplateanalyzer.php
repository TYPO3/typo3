<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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

require_once(PATH_t3lib."class.t3lib_extobjbase.php");

class tx_tstemplateanalyzer extends t3lib_extobjbase {
	function modMenu()	{
		global $LANG;

		return Array (
			"ts_analyzer_checkSetup" => "",
			"ts_analyzer_checkConst" => "",
			"ts_analyzer_checkLinenum" => "",
			"ts_analyzer_checkComments" => "",
			"ts_analyzer_checkCrop" => "",
			"ts_analyzer_checkSyntax" => "",
			"ts_analyzer_checkSyntaxBlockmode" => "",
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
			$theOutput.=$this->pObj->doc->section("Current template:",'<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon("sys_template",$tplRow).'" width=18 height=16 align=top><b>'.$this->pObj->linkWrapTemplateTitle($tplRow["title"]).'</b>'.htmlspecialchars(trim($tplRow["sitetitle"])?' - ('.$tplRow["sitetitle"].')':''));
		}
		if ($manyTemplatesMenu)	{
			$theOutput.=$this->pObj->doc->section("",$manyTemplatesMenu);
		}

		//	debug($tmpl->hierarchyInfo);

		$tmpl->clearList_const_temp = array_flip($tmpl->clearList_const);
		$tmpl->clearList_setup_temp = array_flip($tmpl->clearList_setup);

		$pointer = count($tmpl->hierarchyInfo);
		$tmpl->hierarchyInfoArr = $tmpl->ext_process_hierarchyInfo(array(), $pointer);
		$tmpl->procesIncludes();

		$hierarArr = array();
		$head= '<tr>';
		$head.= '<td class="bgColor2"><b>Title&nbsp;&nbsp;</b></td>';
		$head.= '<td class="bgColor2"><b>Rootlevel&nbsp;&nbsp;</b></td>';
		$head.= '<td class="bgColor2"><b>C. Setup&nbsp;&nbsp;</b></td>';
		$head.= '<td class="bgColor2"><b>C. Const&nbsp;&nbsp;</b></td>';
		$head.= '<td class="bgColor2"><b>PID/RL&nbsp;&nbsp;</b></td>';
		$head.= '<td class="bgColor2"><b>NL&nbsp;&nbsp;</b></td>';
		$head.= '</tr>';
		$hierar = implode(array_reverse($tmpl->ext_getTemplateHierarchyArr($tmpl->hierarchyInfoArr, "",array(),1)),"");
		$hierar= '<table border=0 cellpadding=0 cellspacing=0>'.$head.$hierar.'</table>';

		$theOutput.=$this->pObj->doc->spacer(5);
		$theOutput.=$this->pObj->doc->section("Template hierarchy:",$hierar,0,1);


			// Output constants
		$theOutput.=$this->pObj->doc->spacer(25);
		$theOutput.=$this->pObj->doc->divider(0);
		$theOutput.=$this->pObj->doc->section("",
			'<label for="checkTs_analyzer_checkLinenum">Linenumbers</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkLinenum]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"],'','','id="checkTs_analyzer_checkLinenum"').
			'&nbsp;&nbsp;&nbsp;<label for="checkTs_analyzer_checkSyntax">Syntax HL</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkSyntax]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"],'','','id="checkTs_analyzer_checkSyntax"').
			(!$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"] ?
				'&nbsp;&nbsp;&nbsp;<label for="checkTs_analyzer_checkComments">Comments</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkComments]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"],'','','id="checkTs_analyzer_checkComments"').
				'&nbsp;&nbsp;&nbsp;<label for="checkTs_analyzer_checkCrop">Crop lines</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkCrop]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"],'','','id="checkTs_analyzer_checkCrop"')
				:
				'&nbsp;&nbsp;&nbsp;<label for="checkTs_analyzer_checkSyntaxBlockmode">Block mode</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkSyntaxBlockmode]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"],'','','id="checkTs_analyzer_checkSyntaxBlockmode"')
			)
		);
		$theOutput.=$this->pObj->doc->divider(2);
		//$theOutput.=$this->pObj->doc->section("Constants:",t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkConst]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkConst"]).fw("Enable"));
		$theOutput.=$this->pObj->doc->section("Constants:","",0,1);
		$theOutput.=$this->pObj->doc->sectionEnd();
		if (1==1 || $this->pObj->MOD_SETTINGS["ts_analyzer_checkConst"]) 	{
			$theOutput.='
				<table border=0 cellpadding=1 cellspacing=0>
			';
			$tmpl->ext_lineNumberOffset=-2;	// Don't know why -2 and not 0... :-) But works.
			$tmpl->ext_lineNumberOffset_mode="const";
			$tmpl->ext_lineNumberOffset+=count(explode(chr(10),t3lib_TSparser::checkIncludeLines("".$GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_constants"])))+1;

			reset($tmpl->constants);
			reset($tmpl->clearList_const);
			while(list($key,$val)=each($tmpl->constants))	{
				$cVal = current($tmpl->clearList_const);
				if ($cVal==t3lib_div::_GET('template') || t3lib_div::_GET('template')=="all")	{
					$theOutput.='
						<tr>
							<td><img src=clear.gif width=3 height=1></td><td class="bgColor2"><b>'.$tmpl->templateTitles[$cVal].'</b></td></tr>
						<tr>
							<td><img src=clear.gif width=3 height=1></td>
							<td class="bgColor2"><table border=0 cellpadding=0 cellspacing=0 class="bgColor4" width="100%"><tr><td nowrap>'.$tmpl->ext_outputTS(array($val),$this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"]).'</td></tr></table>
							</td>
						</tr>
					';
					if (t3lib_div::_GET('template')!="all") 	break;
				}
				$tmpl->ext_lineNumberOffset+=count(explode(chr(10),$val))+1;
				next($tmpl->clearList_const);
			}
			$theOutput.='
				</table>
			';
		}

			// Output setup
		$theOutput.=$this->pObj->doc->spacer(15);
		//$theOutput.=$this->pObj->doc->section("SETUP:",t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_analyzer_checkSetup]",$this->pObj->MOD_SETTINGS["ts_analyzer_checkSetup"]).fw("Enable"));
		$theOutput.=$this->pObj->doc->section("SETUP:","",0,1);
		$theOutput.=$this->pObj->doc->sectionEnd();
		if (1==1 || $this->pObj->MOD_SETTINGS["ts_analyzer_checkSetup"]) 	{
			$theOutput.='
				<table border=0 cellpadding=1 cellspacing=0>
			';
			$tmpl->ext_lineNumberOffset=0;
			$tmpl->ext_lineNumberOffset_mode="setup";
			$tmpl->ext_lineNumberOffset+=count(explode(chr(10),t3lib_TSparser::checkIncludeLines("".$GLOBALS["TYPO3_CONF_VARS"]["FE"]["defaultTypoScript_setup"])))+1;

			reset($tmpl->config);
			reset($tmpl->clearList_setup);
			while(list($key,$val)=each($tmpl->config))	{
				if (current($tmpl->clearList_setup)==t3lib_div::_GET('template') || t3lib_div::_GET('template')=="all")	{
					$theOutput.='
						<tr>
							<td><img src=clear.gif width=3 height=1></td><td class="bgColor2"><b>'.$tmpl->templateTitles[current($tmpl->clearList_setup)].'</b></td></tr>
						<tr>
							<td><img src=clear.gif width=3 height=1></td>
							<td class="bgColor2"><table border=0 cellpadding=0 cellspacing=0 class="bgColor4" width="100%"><tr><td nowrap>'.$tmpl->ext_outputTS(array($val),$this->pObj->MOD_SETTINGS["ts_analyzer_checkLinenum"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkComments"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkCrop"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntax"],$this->pObj->MOD_SETTINGS["ts_analyzer_checkSyntaxBlockmode"]).'</td></tr></table>
							</td>
						</tr>
					';
					if (t3lib_div::_GET('template')!="all") 	break;
				}
				$tmpl->ext_lineNumberOffset+=count(explode(chr(10),$val))+1;
				next($tmpl->clearList_setup);
			}
			$theOutput.='
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
