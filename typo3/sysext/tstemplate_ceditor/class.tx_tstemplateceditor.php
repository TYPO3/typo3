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
 * TypoScript Constant editor
 *
 * Module Include-file
 *
 * localconf-variables:
 * $TYPO3_CONF_VARS['MODS']['web_ts']['onlineResourceDir'] = 'fileadmin/fonts/';		// This is the path (must be in "fileadmin/" !!) where the web_ts/constant-editor submodule fetches online resources. Put fonts (ttf) and standard images here!
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$GLOBALS['LANG']->includeLLFile('EXT:tstemplate_ceditor/locallang.xml');

class tx_tstemplateceditor extends t3lib_extobjbase {
	function initialize_editor($pageId,$template_uid=0)	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl,$tplRow,$theConstants;

		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

		$tmpl->ext_localGfxPrefix=t3lib_extMgm::extPath("tstemplate_ceditor");
		$tmpl->ext_localWebGfxPrefix=$GLOBALS["BACK_PATH"].t3lib_extMgm::extRelPath("tstemplate_ceditor");

		$tplRow = $tmpl->ext_getFirstTemplate($pageId,$template_uid);	// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		if (is_array($tplRow))	{	// IF there was a template...
				// Gets the rootLine
			$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
			$rootLine = $sys_page->getRootLine($pageId);
			$tmpl->runThroughTemplates($rootLine,$template_uid);	// This generates the constants/config + hierarchy info for the template.
			$theConstants = $tmpl->generateConfig_constants();	// The editable constants are returned in an array.
			$tmpl->ext_categorizeEditableConstants($theConstants);	// The returned constants are sorted in categories, that goes into the $tmpl->categories array
			$tmpl->ext_regObjectPositions($tplRow["constants"]);		// This array will contain key=[expanded constantname], value=linenumber in template. (after edit_divider, if any)
			return 1;
		}
	}
	function displayExample($theOutput)	{
		global $SOBE,$tmpl;
		if ($tmpl->helpConfig["imagetag"] || $tmpl->helpConfig["description"] || $tmpl->helpConfig["header"])	{
	//		$theOutput.=$this->pObj->doc->divider(20);
			$theOutput.=$this->pObj->doc->spacer(30);
			$theOutput.=$this->pObj->doc->section($tmpl->helpConfig["header"],
				'<div align="center">'.$tmpl->helpConfig["imagetag"].'</div><BR>'.
				($tmpl->helpConfig["description"] ? implode(explode("//",$tmpl->helpConfig["description"]),"<BR>")."<BR>" : "").
				($tmpl->helpConfig["bulletlist"] ? "<ul><li>".implode(explode("//",$tmpl->helpConfig["bulletlist"]),"<li>")."</ul>" : "<BR>")
				);
		}
		return $theOutput;
	}

	function main()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		global $tmpl,$tplRow,$theConstants;


		// **************************
		// Create extension template
		// **************************
		$this->pObj->createTemplate($this->pObj->id);




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
			$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];

				// Update template ?
			if (t3lib_div::_POST('submit') || (t3lib_div::testInt(t3lib_div::_POST('submit_x')) && t3lib_div::testInt(t3lib_div::_POST('submit_y')))) {
				$tmpl->changed=0;
				$tmpl->ext_procesInput(t3lib_div::_POST(),$_FILES,$theConstants,$tplRow);
		//		debug($tmpl->changed);
		//		debug($tmpl->raw);
		//		$tmpl->changed=0;
				if ($tmpl->changed)	{
						// Set the data to be saved
					$recData=array();
					$recData["sys_template"][$saveId]["constants"] = implode($tmpl->raw,LF);
						// Create new  tce-object
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
						// Initialize
					$tce->start($recData,Array());
						// Saved the stuff
					$tce->process_datamap();
						// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd("all");

						// re-read the template ...
					$this->initialize_editor($this->pObj->id,$template_uid);
				}
			}

			// Output edit form
			$tmpl->ext_readDirResources($TYPO3_CONF_VARS["MODS"]["web_ts"]["onlineResourceDir"]);
			$tmpl->ext_resourceDims();

				// Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
			$this->pObj->MOD_MENU["constant_editor_cat"] = $tmpl->ext_getCategoryLabelArray();

			$this->pObj->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->pObj->MOD_MENU, t3lib_div::_GP("SET"), $this->pObj->MCONF["name"]);
				// Resetting the menu (stop)

			$theOutput.=$this->pObj->doc->spacer(5);
			$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('editConstants', true),t3lib_iconWorks::getSpriteIconForRecord('sys_template', $tplRow).'<strong>'.$this->pObj->linkWrapTemplateTitle($tplRow["title"],"constants").'</strong>'.htmlspecialchars(trim($tplRow["sitetitle"])?' - ('.$tplRow["sitetitle"].')':''),0,1);

			if ($manyTemplatesMenu)	{
				$theOutput.=$this->pObj->doc->section("",$manyTemplatesMenu);
				$theOutput.=$this->pObj->doc->divider(5);
			}

			$theOutput.=$this->pObj->doc->spacer(5);
			if (count($this->pObj->MOD_MENU["constant_editor_cat"]))	{
				$menu = $GLOBALS['LANG']->getLL('category', true)." ".t3lib_BEfunc::getFuncMenu($this->pObj->id,"SET[constant_editor_cat]",$this->pObj->MOD_SETTINGS["constant_editor_cat"],$this->pObj->MOD_MENU["constant_editor_cat"]);
				$theOutput.=$this->pObj->doc->section("",'<NOBR>'.$menu.'</NOBR>');
			} else {
				$theOutput.=$this->pObj->doc->section($GLOBALS['LANG']->getLL('noConstants', true),$GLOBALS['LANG']->getLL('noConstantsDescription', true),1,0,1);
			}


					// Category and constant editor config:
			$category = $this->pObj->MOD_SETTINGS["constant_editor_cat"];
		/*	$TSCE_tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
			$TSCE_tmpl->tt_track = 0;	// Do not log time-performance information
			$TSCE_tmpl->init();
			$TSCE_tmpl->constants=array($tplRow["constants"]);
			debug($tplRow);
			$TSCE_tmpl->generateConfig_constants();
			debug($TSCE_tmpl->setup);
			*/
			$tmpl->ext_getTSCE_config($category);

# NOT WORKING:
			if ($BE_USER_modOptions["properties"]["constantEditor."]["example"]=="top")	{
				$theOutput=$this->displayExample($theOutput);
			}

			$printFields = trim($tmpl->ext_printFields($theConstants,$category));
			if ($printFields)	{
				$theOutput.=$this->pObj->doc->spacer(20);
				$theOutput.=$this->pObj->doc->section("",$printFields);
			}

			if ($BE_USER_modOptions["properties"]["constantEditor."]["example"]!="top")	{
				$theOutput=$this->displayExample($theOutput);
			}
		} else {
			$theOutput.=$this->pObj->noTemplate(1);
		}
		return $theOutput;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_ceditor/class.tx_tstemplateceditor.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_ceditor/class.tx_tstemplateceditor.php"]);
}

?>