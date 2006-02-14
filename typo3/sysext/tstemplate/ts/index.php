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
 * Module: TypoScript Tools
 *
 *
 * 	$TYPO3_CONF_VARS["MODS"]["web_ts"]["onlineResourceDir"]  = Directory of default resources. Eg. "fileadmin/res/" or so.
 *
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */



unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
require_once (PATH_t3lib."class.t3lib_page.php");
require_once (PATH_t3lib."class.t3lib_tstemplate.php");
require_once (PATH_t3lib."class.t3lib_tsparser_ext.php");

require_once (PATH_t3lib."class.t3lib_scbase.php");

$BE_USER->modAccess($MCONF,1);


// ***************************
// Script Classes
// ***************************
class SC_mod_web_ts_index extends t3lib_SCbase {
	var $perms_clause;
	var $e;
	var $sObj;
	var $edit;
	var $textExtensions = 'html,htm,txt,css,tmpl,inc,js';

	var $modMenu_type = "";
	var $modMenu_dontValidateList = "ts_browser_toplevel_setup,ts_browser_toplevel_const,ts_browser_TLKeys_setup,ts_browser_TLKeys_const";
	var $modMenu_setDefaultList = "ts_browser_linkObjects,ts_browser_fixedLgd";

	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		$this->id = intval(t3lib_div::_GP("id"));
		$this->e = t3lib_div::_GP("e");
		$this->sObj = t3lib_div::_GP("sObj");
		$this->edit = t3lib_div::_GP("edit");

		$this->perms_clause = $BE_USER->getPagePermsClause(1);

		if (t3lib_div::_GP("clear_all_cache"))	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
	}
	function clearCache()	{
		if (t3lib_div::_GP("clear_all_cache"))	{
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values=0;
			$tce->start(Array(),Array());
			$tce->clear_cacheCmd("all");
		}
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($pageinfo) ? 1 : 0;

		if ($this->id && $access)	{
			$this->doc = t3lib_div::makeInstance("template");
			$this->doc->backPath = $BACK_PATH;


				// JavaScript
			$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
			function uFormUrl(aname)	{
				document.forms[0].action = "index.php?id='.$this->id.'#"+aname;
			}
			function brPoint(lnumber,t)	{
				window.location.href = "index.php?id='.$this->id.'&SET[function]=tx_tstemplateobjbrowser&SET[ts_browser_type]="+(t?"setup":"const")+"&breakPointLN="+lnumber;
				return false;
			}
		</script>
		';

			$this->doc->postCode='
		<script language="javascript" type="text/javascript">
			script_ended = 1;
			if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
		</script>
		';

			$this->doc->inDocStylesArray[] = '
				TABLE#typo3-objectBrowser A { text-decoration: none; }
			';


				// Setting up the context sensitive menu:
			$CMparts=$this->doc->getContextMenuCode();
			$this->doc->bodyTagAdditions = $CMparts[1];
			$this->doc->JScode.=$CMparts[0];
			$this->doc->postCode.= $CMparts[2];


			$headerSection = $this->doc->getHeader("pages",$pageinfo,$pageinfo["_thePath"]).'<br>'.
				$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").': '.
				'<span title="'.htmlspecialchars($pageinfo['_thePathFull']).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($pageinfo['_thePath'],-50)).'</span>';

				// Draw the header.
			$this->doc->form='<form action="index.php?id='.$this->id.'" method="post" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" name="editForm">';
			$this->content.=$this->doc->startPage("Template Tools");
			$this->content.=$this->doc->header("Template Tools");
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));


			$this->extObjContent();



			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon("id,e,sObj,template","function,ts_browser_type,constant_editor_cat",$this->MCONF["name"]));
			}
			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage("Template Tools");
			$this->content.=$this->doc->header("Template Tools");
			$this->content.=$this->doc->spacer(5);


				// Template pages:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'pages.uid, count(*) AS count, max(sys_template.root) AS root_max_val, min(sys_template.root) AS root_min_val',
						'pages,sys_template',
						'pages.uid=sys_template.pid'.
							t3lib_BEfunc::deleteClause('pages').
							t3lib_BEfunc::versioningPlaceholderClause('pages').
							t3lib_BEfunc::deleteClause('sys_template').
							t3lib_BEfunc::versioningPlaceholderClause('sys_template'),
						'pages.uid'
					);
			$templateArray = array();
			$pArray = array();
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$this->setInPageArray(
					$pArray,
					t3lib_BEfunc::BEgetRootLine ($row["uid"],"AND 1=1"),
					$row
				);
			}

			$lines = array();
			$lines[] = '<tr class="bgColor5">
				<td nowrap><strong>Page name</strong></td>
				<td nowrap><strong># Templates</strong></td>
				<td nowrap><strong>Is Root?</strong></td>
				<td nowrap><strong>Is Ext?</strong></td>
				</tr>';
			$lines = array_merge($lines,$this->renderList($pArray));

			$table = '<table border=0 cellpadding=0 cellspacing=1>'.implode("",$lines).'</table>';
			$this->content.= $this->doc->section("",'
			<BR>
			This is an overview of the pages in the database containing one or more template records. Click a page title to go to the page.
			<BR><BR>
			'.$table);
		//	debug($pArray);
			// ********************************************
			// RENDER LIST of pages with templates, END
			// ********************************************



			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon("id","",$this->MCONF["name"]));
			}

			$this->content.=$this->doc->spacer(10);
		}
	}
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	// ***************************
	// OTHER FUNCTIONS:
	// ***************************
	function getCountCacheTables($humanReadable)	{
		$out=array();

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'cache_pages', '');
		list($out["cache_pages"]) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'cache_pagesection', '');
		list($out["cache_pagesection"]) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'cache_hash', '');
		list($out["cache_hash"]) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);

		if ($humanReadable)	{
			$newOut=array();
			reset($out);
			while(list($k,$v)=each($out))	{
				$newOut[]=$k.":".$v;
			}
			$out=implode($newOut,", ");
		}
		return $out;
	}

	function linkWrapTemplateTitle($title,$onlyKey="")	{
		if ($onlyKey)	{
			$title = '<a href="index.php?id='.$GLOBALS["SOBE"]->id.'&e['.$onlyKey.']=1&SET[function]=tx_tstemplateinfo">'.htmlspecialchars($title).'</a>';
		} else {
			$title = '<a href="index.php?id='.$GLOBALS["SOBE"]->id.'&e[constants]=1&e[config]=1&SET[function]=tx_tstemplateinfo">'.htmlspecialchars($title).'</a>';
		}
		return $title;
	}
	function noTemplate($newStandardTemplate=0)	{
		global $SOBE;

		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

		$confirm = ' onClick="return confirm(\'Are you sure you want to do this?\');"';

			// No template
		$theOutput.=$this->doc->spacer(10);
		$theOutput.=$this->doc->section('<span class="typo3-red">No template</span>',"There was no template on this page!<BR>Create a template record first in order to edit constants!",0,0,0,1);
			// New standard?
		if ($newStandardTemplate)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'static_template', '', '', 'title');
			$opt = "";
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if (substr(trim($row["title"]),0,8)=="template")	{
					$opt.='<option value="'.$row["uid"].'">'.htmlspecialchars($row["title"]).'</option>';
				}
			}
			$selector = '<select name="createStandard"><option></option>'.$opt.'</select>';
				// Extension?
			$theOutput.=$this->doc->spacer(10);
			$theOutput.=$this->doc->section("Create new website",'If you want this page to be the root of a new website, optionally based on one of the standard templates, then press the button below:<BR>
			<BR>
			'.$selector.'<BR>
			<img src="'.$GLOBALS["BACK_PATH"].'gfx/icon_warning.gif" width=18 height=16 hspace=5 align=top><input type="Submit" name="newWebsite" value="Create template for a new site"'.$confirm.'>',0,1);
		}
			// Extension?
		$theOutput.=$this->doc->spacer(10);
		$theOutput.=$this->doc->section("Create extension template",'An extension template allows you to enter TypoScript values that will affect only this page and subpages.<BR><BR><img src="'.$GLOBALS["BACK_PATH"].'gfx/icon_warning.gif" width=18 height=16 hspace=5 align=top><input type="submit" name="createExtension" value="Click here to create an extension template."'.$confirm.'>',0,1);

			// Go to first appearing...
		$first = $tmpl->ext_prevPageWithTemplate($this->id,$this->perms_clause);
		if ($first)	{
			$theOutput.=$this->doc->spacer(10);
			$theOutput.=$this->doc->section("Go to closest page with template",sprintf("Closest template is on page '%s' (uid %s).<BR><BR>%s<strong>Click here to go.</strong>%s",$first["title"],$first["uid"],'<a href="index.php?id='.$first["uid"].'">','</a>'),0,1);
		}
		return $theOutput;
	}
	function templateMenu()	{
		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();
		$all = $tmpl->ext_getAllTemplates($this->id,$this->perms_clause);
		$menu='';
		if (count($all)>1)	{
			$this->MOD_MENU['templatesOnPage']=array();
			foreach($all as $d)	{
				$this->MOD_MENU['templatesOnPage'][$d['uid']] = $d['title'];
			}
		}

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
		$menu = t3lib_BEfunc::getFuncMenu($this->id,'SET[templatesOnPage]',$this->MOD_SETTINGS['templatesOnPage'],$this->MOD_MENU['templatesOnPage']);

		return $menu;
	}
	function createTemplate($id)	{
		if (t3lib_div::_GP("createExtension"))	{
			require_once (PATH_t3lib."class.t3lib_tcemain.php");
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values=0;
			$recData=array();
			$recData["sys_template"]["NEW"] = array(
				"pid" => $id,
				"title" => "+ext",
				"sorting" => time()
			);
			$tce->start($recData,Array());
			$tce->process_datamap();
		} elseif (t3lib_div::_GP("newWebsite"))	{
			require_once (PATH_t3lib."class.t3lib_tcemain.php");
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values=0;
			$recData=array();
			if (intval(t3lib_div::_GP("createStandard")))	{
				$staticT = intval(t3lib_div::_GP("createStandard"));
				$recData["sys_template"]["NEW"] = array(
					"pid" => $id,
					"title" => "NEW SITE, based on standard",
					"sorting" => 0,
					"root" => 1,
					"clear" => 3,
					"include_static" => $staticT.",57"	// 57 is cSet
				);
			} else {
				$recData["sys_template"]["NEW"] = array(
					"pid" => $id,
					"title" => "NEW SITE",
					"sorting" => 0,
					"root" => 1,
					"clear" => 3,
					"config" => '
# Default PAGE object:
page = PAGE
page.10 = TEXT
page.10.value = HELLO WORLD!
'
				);
			}
			$tce->start($recData,Array());
			$tce->process_datamap();
			$tce->clear_cacheCmd("all");
		}
	}
	// ********************************************
	// RENDER LIST of pages with templates, BEGIN
	// ********************************************
	function setInPageArray(&$pArray,$rlArr,$row)	{
		ksort($rlArr);
		reset($rlArr);
		if (!$rlArr[0]["uid"])		array_shift($rlArr);

		$cEl = current($rlArr);
		$pArray[$cEl["uid"]]=$cEl["title"];
		array_shift($rlArr);
		if (count($rlArr))	{
			if (!isset($pArray[$cEl["uid"]."."]))	$pArray[$cEl["uid"]."."]=array();
			$this->setInPageArray($pArray[$cEl["uid"]."."],$rlArr,$row);
		} else {
			$pArray[$cEl["uid"]."_"]=$row;
		}
	}
	function renderList($pArray,$lines=array(),$c=0)	{
		if (is_array($pArray))	{
			reset($pArray);
			while(list($k,$v)=each($pArray))	{
				if (t3lib_div::testInt($k))	{
					if (isset($pArray[$k."_"]))	{
						$lines[]='<tr class="bgColor4">
							<td nowrap><img src=clear.gif width=1 height=1 hspace='.($c*10).' align=top>'.
							'<a href="'.t3lib_div::linkThisScript(array("id"=>$k)).'">'.
							t3lib_iconWorks::getIconImage("pages",t3lib_BEfunc::getRecordWSOL("pages",$k),$GLOBALS["BACK_PATH"],' align="top" title="ID: '.$k.'"').
							t3lib_div::fixed_lgd_cs($pArray[$k],30).'</a></td>
							<td align=center>'.$pArray[$k."_"]["count"].'</td>
							<td align=center>'.($pArray[$k."_"]["root_max_val"]>0?"x":"&nbsp;").'</td>
							<td align=center>'.($pArray[$k."_"]["root_min_val"]==0?"x":"&nbsp;").'</td>
							</tr>';
					} else {
						$lines[]='<tr>
							<td nowrap colspan=3><img src=clear.gif width=1 height=1 hspace='.($c*10).' align=top>'.
							t3lib_iconWorks::getIconImage("pages",t3lib_BEfunc::getRecordWSOL("pages",$k),$GLOBALS["BACK_PATH"]," align=top").
							t3lib_div::fixed_lgd_cs($pArray[$k],30).'</td>
							</tr>';
					}
					$lines=$this->renderList($pArray[$k."."],$lines,$c+1);
				}
			}
		}
		return $lines;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate/ts/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate/ts/index.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_web_ts_index");
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkExtObj();	// Checking for first level external objects

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();
?>
