<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: Database integrity check
 *
 * This module lets you check if all pages and the records relate properly to each other
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
require_once (PATH_t3lib."class.t3lib_admin.php");
require_once (PATH_t3lib."class.t3lib_loaddbgroup.php");
require_once (PATH_t3lib."class.t3lib_querygenerator.php");
require_once (PATH_t3lib."class.t3lib_xml.php");
require_once (PATH_t3lib."class.t3lib_fullsearch.php");

$BE_USER->modAccess($MCONF,1);



// **************************
// Setting english (ONLY!) LOCAL_LANG
// **************************
$LOCAL_LANG = Array (
	"default" => Array (
		"tables" => "Tables:",
		"fixLostRecord" => "Click to move this lost record to rootlevel (pid=0)",

		"doktype" => "Document types:",
		"pages" => "Pages:",
		"total_pages" => "Total number of pages:",
		"deleted_pages" => "Marked-deleted pages:",
		"hidden_pages" => "Hidden pages:",
		"relations" => "Relations:",
		"relations_description" => "This will analyse the content of the tables and check if there are 'empty' relations between records or if files are missing from their expected position.",

		"files_many_ref" => "Files referenced from more than one record:",
		"files_no_ref" => "Files with no references at all (delete them!):",
		"files_no_file" => "Missing files:",
		"select_db" => "Select fields:",
		"group_db" => "Group fields:",

		"tree" => "The Page Tree:",
		"tree_description" => "This shows all pages in the system in one large tree. Beware that this will probably result in a very long document which will also take some time for the server to compute!",
		"records" => "Records Statistics:",
		"records_description" => "This shows some statistics for the records in the database. This runs through the entire page-tree and therefore it will also load the server heavily!",
		"search" => "Search Whole Database",
		"search_description" => "This searches through all database tables and records for a text string.",
		"filesearch" => "Search all filenames for pattern",
		"filesearch_description" => "Will search recursively for filenames in the PATH_site (subdirs to the website path) matching a certain regex pattern.",
		"title" => "Database integrity check"
	)
);





// ***************************
// Script Classes
// ***************************
class SC_mod_tools_dbint_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $doc;

	var $content;
	var $menu;

	/**
	 *
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS["MCONF"];

		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->form='<form action="" method="POST">';
		$this->doc->backPath = $BACK_PATH;
				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				document.location = URL;
			}
		</script>
		';
		$this->doc->tableLayout = Array (
			"defRow" => Array (
				"0" => Array('<TD valign="top">','</td>'),
				"1" => Array('<TD valign="top">','</td>'),
				"defCol" => Array('<TD><img src="'.$this->doc->backPath.'clear.gif" width=15 height=1></td><td valign="top">','</td>')
			)
		);
	}

	/**
	 *
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"function" => array(
				0 => "[ MENU ]",
				"records" => "Record Statistics",
				"tree" => "Total Page Tree",
				"relations" => "Database Relations",
				"search" => "Full search",
				"filesearch" => "Find filename"
			),
			"search" => array(
				"raw" => "Raw search in all fields",
				"query" => "Advanced query"
			),

			"search_query_smallparts" => "",

			"queryConfig" => "",	// Current query
			"queryTable" => "",	// Current table
			"queryFields" => "",	// Current tableFields
			"queryLimit" => "",	// Current limit
			"queryOrder" => "",	// Current Order field
			"queryOrderDesc" => "",	// Current Order field descending flag
			"queryOrder2" => "",	// Current Order2 field
			"queryOrder2Desc" => "",	// Current Order2 field descending flag
			"queryGroup" => "",	// Current Group field

			"storeArray" => "",	// Used to store the available Query config memory banks
			"storeQueryConfigs" => "",	// Used to store the available Query configs in memory

			"search_query_makeQuery" => array(
				"all" => "Select records",
				"count" => "Count results",
				"explain" => "Explain query",
				"csv" => "CSV Export",
				"xml" => "XML Export"
			),


			"sword" => ""
		);
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP("SET"), $this->MCONF["name"], "ses");

		if (t3lib_div::_GP("queryConfig"))	{
			$qA = t3lib_div::_GP("queryConfig");
			$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, array("queryConfig"=>serialize($qA)), $this->MCONF["name"], "ses");
		}
	}

	/**
	 *
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// **************************
		// Content creation
		// **************************
		$this->content.=$this->doc->startPage($LANG->getLL("title"));
		$this->menu=t3lib_BEfunc::getFuncMenu(0,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"]);

		switch($this->MOD_SETTINGS["function"])	{
			case "search":
				$this->func_search();
			break;
			case "tree":
				$this->func_tree();
			break;
			case "records":
				$this->func_records();
			break;
			case "relations":
				$this->func_relations();
			break;
			case "filesearch":
				$this->func_filesearch();
			break;
			default:
				$this->func_default();
			break;
		}

		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).
						$this->doc->section('',$this->doc->makeShortcutIcon("","function,search,search_query_makeQuery",$this->MCONF["name"]));
		}
	}

	/**
	 *
	 */
	function printContent()	{

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 *
	 */
	function func_search()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$fullsearch = t3lib_div::makeInstance("t3lib_fullsearch");
		$this->content.=$this->doc->header($LANG->getLL("search"));
		$this->content.=$this->doc->spacer(5);

		$menu2=t3lib_BEfunc::getFuncMenu(0,"SET[search]",$this->MOD_SETTINGS["search"],$this->MOD_MENU["search"]);
		if ($this->MOD_SETTINGS["search"]=="query")	{
			$menu2.=t3lib_BEfunc::getFuncMenu(0,"SET[search_query_makeQuery]",$this->MOD_SETTINGS["search_query_makeQuery"],$this->MOD_MENU["search_query_makeQuery"]).
					"&nbsp;".t3lib_BEfunc::getFuncCheck($GLOBALS["SOBE"]->id,"SET[search_query_smallparts]",$this->MOD_SETTINGS["search_query_smallparts"])."&nbsp;Show SQL parts";
		}
		$this->content.=$this->doc->section('',$this->menu);//$this->doc->divider(5);
		$this->content.=$this->doc->section('',$menu2).$this->doc->spacer(10);

		switch($this->MOD_SETTINGS["search"])		{
			case "query":
				$this->content.=$fullsearch->queryMaker();
			break;
			case "raw":
			default:
				$this->content.=$this->doc->section('Search options:',$fullsearch->form(),0,1);
				$this->content.=$this->doc->section('Result:',$fullsearch->search(),0,1);
			break;
		}
	}

	/**
	 *
	 */
	function func_tree()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$startID=0;
		$admin = t3lib_div::makeInstance("t3lib_admin");
		$admin->genTree_makeHTML=1;
		$admin->backPath = $BACK_PATH;
		$admin->genTree(intval($startID),'<img src="'.$BACK_PATH.'clear.gif" width=1 height=1 align=top>');

		$this->content.=$this->doc->header($LANG->getLL("tree"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu).$this->doc->divider(5);
		$this->content.=$this->doc->sectionEnd();

		$this->content.=$admin->genTree_HTML;
		$this->content.=$admin->lostRecords($admin->genTree_idlist."0");
	}

	/**
	 *
	 */
	function func_records()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		global $PAGES_TYPES;

		$admin = t3lib_div::makeInstance("t3lib_admin");
		$admin->genTree_makeHTML=0;
		$admin->backPath = $BACK_PATH;
		$admin->genTree(0,'');

		$this->content.=$this->doc->header($LANG->getLL("records"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu);

			// Pages stat
		$codeArr=Array();
		$i++;
		$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/pages.gif','width="18" height="16"').' hspace=4 align="top">';
		$codeArr[$i][]=$LANG->getLL("total_pages");
		$codeArr[$i][]=count($admin->page_idArray);
		$i++;
		if (t3lib_extMgm::isLoaded("cms"))	{
			$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/hidden_page.gif','width="18" height="16"').' hspace=4 align="top">';
			$codeArr[$i][]=$LANG->getLL("hidden_pages");
			$codeArr[$i][]=$admin->recStat["hidden"];
			$i++;
		}
		$codeArr[$i][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/deleted_page.gif','width="18" height="16"').' hspace=4 align="top">';
		$codeArr[$i][]=$LANG->getLL("deleted_pages");
		$codeArr[$i][]=$admin->recStat["deleted"];

		$this->content.=$this->doc->section($LANG->getLL("pages"),$this->doc->table($codeArr),0,1);

			// Doktype
		$codeArr=Array();
		$doktype=$TCA["pages"]["columns"]["doktype"]["config"]["items"];
		if (is_array($doktype))	{
			reset($doktype);
			while(list($n,$setup)=each($doktype))	{
				if ($setup[1]!="--div--")	{
					$codeArr[$n][]='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/'.($PAGES_TYPES[$setup[1]]['icon'] ? $PAGES_TYPES[$setup[1]]['icon'] : $PAGES_TYPES["default"]['icon']),'width="18" height="16"').' hspace="4" align="top">';
					$codeArr[$n][]=$LANG->sL($setup[0]).' ('.$setup[1].')';
					$codeArr[$n][]=intval($admin->recStat[doktype][$setup[1]]);
				}
			}
			$this->content.=$this->doc->section($LANG->getLL("doktype"),$this->doc->table($codeArr),0,1);
		}

			// Tables and lost records
		$id_list="0,".implode($admin->page_idArray,",");
		$id_list = t3lib_div::rm_endcomma($id_list);
		$admin->lostRecords($id_list);
		if ($admin->fixLostRecord(t3lib_div::_GET('fixLostRecords_table'),t3lib_div::_GET('fixLostRecords_uid')))	{
			$admin = t3lib_div::makeInstance("admin_int");
			$admin->backPath = $BACK_PATH;
			$admin->genTree(0,'');
			$id_list="0,".implode($admin->page_idArray,",");
			$id_list = t3lib_div::rm_endcomma($id_list);
			$admin->lostRecords($id_list);
		}

		$codeArr=Array();
		$countArr=$admin->countRecords($id_list);
		if (is_array($TCA))	{
			reset($TCA);
			while(list($t)=each($TCA))	{
				$codeArr[$t][]=t3lib_iconWorks::getIconImage($t,array(),$BACK_PATH,'hspace="4" align="top"');
				$codeArr[$t][]=$LANG->sL($TCA[$t]["ctrl"]["title"]);
				$codeArr[$t][]=$t;

				if ($countArr["all"][$t])	{
					$theNumberOfRe = intval($countArr["non_deleted"][$t])."/".(intval($countArr["all"][$t])-intval($countArr["non_deleted"][$t]));
				} else {
					$theNumberOfRe ="";
				}
				$codeArr[$t][]=$theNumberOfRe;

				$lr="";
				if (is_array($admin->lRecords[$t]))	{
					reset($admin->lRecords[$t]);
					while(list(,$data)=each($admin->lRecords[$t]))	{
						if (!t3lib_div::inList($admin->lostPagesList,$data[pid]))	{
							$lr.='<NOBR><b><A HREF="index.php?SET[function]=records&fixLostRecords_table='.$t.'&fixLostRecords_uid='.$data[uid].'"><img src="'.$BACK_PATH.'gfx/required_h.gif" width=10 hspace=3 height=10 border=0 align="top" title="'.$LANG->getLL("fixLostRecord").'"></a>uid:'.$data[uid].', pid:'.$data[pid].', '.t3lib_div::fixed_lgd(strip_tags($data[title]),20).'</b></NOBR><BR>';
						} else {
							$lr.='<NOBR><img src="'.$BACK_PATH.'clear.gif" width=16 height=1 border=0><font color="Gray">uid:'.$data[uid].', pid:'.$data[pid].', '.t3lib_div::fixed_lgd(strip_tags($data[title]),20).'</font></NOBR><BR>';
						}
					}
				}
				$codeArr[$t][]=$lr;
			}
			$this->content.=$this->doc->section($LANG->getLL("tables"),$this->doc->table($codeArr),0,1);
		}
	}

	/**
	 *
	 */
	function func_relations()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->header($LANG->getLL("relations"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu);

		$admin = t3lib_div::makeInstance("t3lib_admin");
		$admin->genTree_makeHTML=0;
		$admin->backPath = $BACK_PATH;

		$fkey_arrays = $admin->getGroupFields("");
		$admin->selectNonEmptyRecordsWithFkeys($fkey_arrays);


		$fileTest=$admin->testFileRefs();

		$code="";
		if (is_array($fileTest["noReferences"]))	{
			while(list(,$val)=each($fileTest["noReferences"]))	{
				$code.="<NOBR>".$val[0]."/<b>".$val[1]."</b></NOBR><BR>";
			}
		}
		$this->content.=$this->doc->section($LANG->getLL("files_no_ref"),$code,1,1);

		$code="";
		if (is_array($fileTest[moreReferences]))	{
			while(list(,$val)=each($fileTest["moreReferences"]))	{
				$code.="<NOBR>".$val[0]."/<b>".$val[1]."</b>: ".$val[2]." references:</NOBR><BR>".$val[3]."<BR><BR>";
			}
		}
		$this->content.=$this->doc->section($LANG->getLL("files_many_ref"),$code,1,1);

		$code="";
		if (is_array($fileTest["noFile"]))	{
			ksort($fileTest["noFile"]);
			reset($fileTest["noFile"]);
			while(list(,$val)=each($fileTest["noFile"]))	{
				$code.="<NOBR>".$val[0]."/<b>".$val[1]."</b> is missing! </NOBR><BR>Referenced from: ".$val[2]."<BR><BR>";
			}
		}
		$this->content.=$this->doc->section($LANG->getLL("files_no_file"),$code,1,1);
		$this->content.=$this->doc->section($LANG->getLL("select_db"),$admin->testDBRefs($admin->checkSelectDBRefs),1,1);
		$this->content.=$this->doc->section($LANG->getLL("group_db"),$admin->testDBRefs($admin->checkGroupDBRefs),1,1);
	}

	/**
	 * Searching for files with a specific pattern
	 */
	function func_filesearch()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->header($LANG->getLL("relations"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu);


		$pattern = t3lib_div::_GP("pattern");
		$pcontent='Enter regex pattern: <input type="text" name="pattern" value="'.htmlspecialchars($pattern?$pattern:$GLOBALS["TYPO3_CONF_VARS"]["BE"]["fileDenyPattern"]).'"> <input type="submit" name="Search">';
		$this->content.=$this->doc->section('Pattern',$pcontent,0,1);

		if (strcmp($pattern,""))	{
			$dirs = t3lib_div::get_dirs(PATH_site);
	#		debug($dirs);
			$lines=array();
			$depth=10;

			foreach ($dirs as $key => $value) {
				$matching_files=array();
				$info="";
				if (!t3lib_div::inList("typo3,typo3conf,tslib,media,t3lib",$value))	{
					$info = $this->findFile(PATH_site.$value."/",$pattern,$matching_files,$depth);
				}
				if (is_array($info))	{
					$lines[]='<hr><b>'.$value.'/</b> being checked...';
					$lines[]='Dirs: '.$info[0];
					if ($info[2])	$lines[]='<span class="typo3-red">ERROR: Directories deeper than '.$depth.' levels</span>';
					$lines[]='Files: '.$info[1];
					$lines[]='Matching files:<br><nobr><span class="typo3-red">'.implode("<br>",$matching_files).'</span></nobr>';
				} else {
					$lines[]=$GLOBALS["TBE_TEMPLATE"]->dfw('<hr><b>'.$value.'/</b> not checked.');
				}
			}

			$this->content.=$this->doc->section('Searching for filenames:',implode("<BR>",$lines),0,1);
		}
	}

	/**
	 * Searching for filename pattern recursively in the specified dir.
	 */
	function findFile($basedir,$pattern,&$matching_files,$depth)	{
		$files_searched=0;
		$dirs_searched=0;
		$dirs_error=0;

			// Traverse files:
		$files = t3lib_div::getFilesInDir($basedir,"",1);
		if (is_array($files))	{
			$files_searched+=count($files);
			foreach ($files as $value) {
				if (eregi($pattern,basename($value)))	$matching_files[]=substr($value,strlen(PATH_site));
			}
		}


			// Traverse subdirs
		if ($depth>0)	{
			$dirs = t3lib_div::get_dirs($basedir);
			if (is_array($dirs))	{
				$dirs_searched+=count($dirs);

				foreach ($dirs as $value) {
					$inf= $this->findFile($basedir.$value."/",$pattern,$matching_files,$depth-1);
					$dirs_searched+=$inf[0];
					$files_searched+=$inf[1];
					$dirs_error=$inf[2];
				}
			}
		} else {
			$dirs = t3lib_div::get_dirs($basedir);
			if (is_array($dirs) && count($dirs))	{
				$dirs_error=1;	// Means error - there were further subdirs!
			}
		}

		return array($dirs_searched,$files_searched,$dirs_error);
	}

	/**
	 * Menu
	 */
	function func_default()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->header($LANG->getLL("title"));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->menu);
		$this->content.=$this->doc->section('<A HREF="index.php?SET[function]=records">'.$LANG->getLL("records").'</a>',$LANG->getLL("records_description"),1,1,0,1);
		$this->content.=$this->doc->section('<A HREF="index.php?SET[function]=tree">'.$LANG->getLL("tree").'</a>',$LANG->getLL("tree_description"),1,1,0,1);
		$this->content.=$this->doc->section('<A HREF="index.php?SET[function]=relations">'.$LANG->getLL("relations").'</a>',$LANG->getLL("relations_description"),1,1,0,1);
		$this->content.=$this->doc->section('<A HREF="index.php?SET[function]=search">'.$LANG->getLL("search").'</a>',$LANG->getLL("search_description"),1,1,0,1);
		$this->content.=$this->doc->section('<A HREF="index.php?SET[function]=filesearch">'.$LANG->getLL("filesearch").'</a>',$LANG->getLL("filesearch_description"),1,1,0,1);
		$this->content.=$this->doc->spacer(50);
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/lowlevel/dbint/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/lowlevel/dbint/index.php"]);
}









// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_tools_dbint_index");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>