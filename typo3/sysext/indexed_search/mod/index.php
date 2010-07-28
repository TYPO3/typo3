<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: Indexing Engine Overview
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   83: class SC_mod_tools_isearch_index
 *   97:     function init()
 *  110:     function jumpToUrl(URL)
 *  133:     function menuConfig()
 *  156:     function main()
 *  193:     function printContent()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  216:     function getRecordsNumbers()
 *  234:     function tableHead($str)
 *  243:     function getPhashStat()
 *  278:     function getPhashT3pages()
 *  347:     function getPhashExternalDocs()
 *  416:     function formatFeGroup($fegroup_recs)
 *  432:     function formatCHash($arr)
 *  447:     function getNumberOfSections($phash)
 *  459:     function getNumberOfWords($phash)
 *  471:     function getGrlistRecord($phash)
 *  487:     function getNumberOfFulltext($phash)
 *  498:     function getPhashTypes()
 *  528:     function countUniqueTypes($item_type)
 *
 * TOTAL FUNCTIONS: 18
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$BE_USER->modAccess($MCONF,1);

t3lib_extMgm::isLoaded("indexed_search",1);
require_once(t3lib_extMgm::extPath('indexed_search').'class.indexer.php');



/**
 * Backend module providing boring statistics of the index-tables.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_indexedsearch
 */
class SC_mod_tools_isearch_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();
	var $doc;

	var $include_once=array();
	var $content;

	/**
	 * Initialization
	 *
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		$this->MCONF = $GLOBALS["MCONF"];

		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance("noDoc");
		$this->doc->form='<form action="" method="POST">';
		$this->doc->backPath = $BACK_PATH;
				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';
		$this->doc->tableLayout = Array (
			"defRow" => Array (
				"0" => Array('<td valign="top" nowrap>','</td>'),
				"defCol" => Array('<TD><img src="'.$this->doc->backPath.'clear.gif" width=10 height=1></td><td valign="top" nowrap>','</td>')
			)
		);

		$indexer = t3lib_div::makeInstance('tx_indexedsearch_indexer');
		$indexer->initializeExternalParsers();
		#debug(array_keys($indexer->external_parsers));
		#debug($indexer->internal_log);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function menuConfig()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"function" => array(
				"stat" => "General statistics",
				"typo3pages" => "List: TYPO3 Pages",
				"externalDocs" => "List: External documents",
			)
		);
			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP("SET"), $this->MCONF["name"], "ses");
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->doc->startPage("Indexing Engine Statistics");

		$menu=t3lib_BEfunc::getFuncMenu(0,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"]);

		$this->content.=$this->doc->header("Indexing Engine Statistics");
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$menu);

		switch($this->MOD_SETTINGS["function"])	{
			case "stat":
				$this->content.=$this->doc->section('Records',$this->doc->table($this->getRecordsNumbers()),0,1);
				$this->content.=$this->doc->spacer(15);
		//		$this->content.=$this->doc->section('index_phash STATISTICS',$this->doc->table($this->getPhashStat()),1);
		//		$this->content.=$this->doc->spacer(15);
				$this->content.=$this->doc->section('index_phash TYPES',$this->doc->table($this->getPhashTypes()),1);
				$this->content.=$this->doc->spacer(15);
			break;
			case "externalDocs":
				$this->content.=$this->doc->section('External documents',$this->doc->table($this->getPhashExternalDocs()),0,1);
				$this->content.=$this->doc->spacer(15);
			break;
			case "typo3pages":
				$this->content.=$this->doc->section('TYPO3 Pages',$this->doc->table($this->getPhashT3pages()),0,1);
				$this->content.=$this->doc->spacer(15);
			break;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}










	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * @return	[type]		...
	 */
	function getRecordsNumbers()	{
		$tables=explode(",","index_phash,index_words,index_rel,index_grlist,index_section,index_fulltext");
		$recList=array();
		reset($tables);
		while(list(,$t)=each($tables))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', $t, '');
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
			$recList[] = array($this->tableHead($t), $row[0]);
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function tableHead($str)	{
		return "<strong>".$str.":&nbsp;&nbsp;&nbsp;</strong>";
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getPhashStat()	{
		$recList = array();

			// TYPO3 pages, unique
		$items = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),phash', 'index_phash', 'data_page_id!=0', 'phash_grouping,pcount,phash');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
			$items[] = $row;
		}
		$recList[] = array($this->tableHead("TYPO3 pages"), count($items));

			// TYPO3 pages:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_phash', 'data_page_id!=0');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$recList[] = array($this->tableHead("TYPO3 pages, raw"), $row[0]);

			// External files, unique
		$items = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),phash', 'index_phash', 'data_filename!=\'\'', 'phash_grouping');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$recList[] = array($this->tableHead("External files"), $row[0]);

			// External files
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_phash', 'data_filename!=\'\'');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$recList[] = array($this->tableHead("External files, raw"), $row[0]);

		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getPhashT3pages()	{
		$recList[]=array(
			$this->tableHead("id/type"),
			$this->tableHead("Title"),
			$this->tableHead("Size"),
			$this->tableHead("Words"),
			$this->tableHead("mtime"),
			$this->tableHead("Indexed"),
			$this->tableHead("Updated"),
			$this->tableHead("Parsetime"),
			$this->tableHead("#sec/gr/full"),
			$this->tableHead("#sub"),
			$this->tableHead("Lang"),
			$this->tableHead("cHash"),
			$this->tableHead("phash")
		);

			// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) AS pcount,index_phash.*', 'index_phash', 'data_page_id!=0', 'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId', 'data_page_id');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

			$cHash = count(unserialize($row["cHashParams"])) ? $this->formatCHash(unserialize($row["cHashParams"])) : "";
			$grListRec = $this->getGrlistRecord($row["phash"]);
			$recList[] = array(
				$row["data_page_id"].($row["data_page_type"]?"/".$row["data_page_type"]:""),
				htmlentities(t3lib_div::fixed_lgd_cs($row["item_title"],30)),
				t3lib_div::formatSize($row["item_size"]),
				$this->getNumberOfWords($row["phash"]),
				t3lib_BEfunc::datetime($row["item_mtime"]),
				t3lib_BEfunc::datetime($row["crdate"]),
				($row["tstamp"]!=$row["crdate"] ? t3lib_BEfunc::datetime($row["tstamp"]) : ""),
				$row["parsetime"],
				$this->getNumberOfSections($row["phash"])."/".$grListRec[0]["pcount"]."/".$this->getNumberOfFulltext($row["phash"]),
				$row["pcount"]."/".$this->formatFeGroup($grListRec),
				$row["sys_language_uid"],
				$cHash,
				$row["phash"]
			);

			if ($row["pcount"]>1)	{
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_phash.*', 'index_phash', 'phash_grouping='.intval($row['phash_grouping']).' AND phash!='.intval($row['phash']));
				while($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2))	{
					$grListRec = $this->getGrlistRecord($row2["phash"]);
					$recList[] = array(
						"",
						"",
						t3lib_div::formatSize($row2["item_size"]),
						$this->getNumberOfWords($row2["phash"]),
						t3lib_BEfunc::datetime($row2["item_mtime"]),
						t3lib_BEfunc::datetime($row2["crdate"]),
						($row2["tstamp"]!=$row2["crdate"] ? t3lib_BEfunc::datetime($row2["tstamp"]) : ""),
						$row2["parsetime"],
						$this->getNumberOfSections($row2["phash"])."/".$grListRec[0]["pcount"]."/".$this->getNumberOfFulltext($row2["phash"]),
						"-/".$this->formatFeGroup($grListRec),
						"",
						"",
						$row2["phash"]
					);
				}
			}
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getPhashExternalDocs()	{
		$recList[]=array(
			$this->tableHead("Filename"),
			$this->tableHead("Size"),
			$this->tableHead("Words"),
			$this->tableHead("mtime"),
			$this->tableHead("Indexed"),
			$this->tableHead("Updated"),
			$this->tableHead("Parsetime"),
			$this->tableHead("#sec/gr/full"),
			$this->tableHead("#sub"),
			$this->tableHead("cHash"),
			$this->tableHead("phash"),
			$this->tableHead("Path")
		);

			// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) AS pcount,index_phash.*', 'index_phash', 'item_type!=\'0\'', 'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId', 'item_type');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

			$cHash = count(unserialize($row["cHashParams"])) ? $this->formatCHash(unserialize($row["cHashParams"])) : "";
			$grListRec = $this->getGrlistRecord($row["phash"]);
			$recList[]=array(
				htmlentities(t3lib_div::fixed_lgd_cs($row["item_title"],30)),
				t3lib_div::formatSize($row["item_size"]),
				$this->getNumberOfWords($row["phash"]),
				t3lib_BEfunc::datetime($row["item_mtime"]),
				t3lib_BEfunc::datetime($row["crdate"]),
				($row["tstamp"]!=$row["crdate"] ? t3lib_BEfunc::datetime($row["tstamp"]) : ""),
				$row["parsetime"],
				$this->getNumberOfSections($row["phash"])."/".$grListRec[0]["pcount"]."/".$this->getNumberOfFulltext($row["phash"]),
				$row["pcount"],
				$cHash,
				$row["phash"],
				htmlentities(t3lib_div::fixed_lgd_cs($row["data_filename"],100))
			);

			if ($row["pcount"]>1)	{
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_phash.*', 'index_phash', 'phash_grouping='.intval($row['phash_grouping']).' AND phash!='.intval($row['phash']));
				while($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2))	{
					$cHash = count(unserialize($row2["cHashParams"])) ? $this->formatCHash(unserialize($row2["cHashParams"])) : "";
					$grListRec = $this->getGrlistRecord($row2["phash"]);
					$recList[]=array(
						"",
						"",
						$this->getNumberOfWords($row2["phash"]),
						"",
						t3lib_BEfunc::datetime($row2["crdate"]),
						($row2["tstamp"]!=$row2["crdate"] ? t3lib_BEfunc::datetime($row2["tstamp"]) : ""),
						$row2["parsetime"],
						$this->getNumberOfSections($row2["phash"])."/".$grListRec[0]["pcount"]."/".$this->getNumberOfFulltext($row2["phash"]),
						"",
						$cHash,
						$row2["phash"],
						""
					);
				}
			}
	//		debug($row);
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$fegroup_recs: ...
	 * @return	[type]		...
	 */
	function formatFeGroup($fegroup_recs)	{
		reset($fegroup_recs);
		$str = array();
		while(list(,$row)=each($fegroup_recs))	{
			$str[] = $row["gr_list"]=="0,-1" ? "NL" : $row["gr_list"];
		}
		arsort($str);
		return implode("|",$str);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function formatCHash($arr)	{
		reset($arr);
		$list=array();
		while(list($k,$v)=each($arr))	{
			$list[] = htmlspecialchars($k) . '=' . htmlspecialchars($v);
		}
		return implode("<BR>",$list);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function getNumberOfSections($phash)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_section', 'phash='.intval($phash));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function getNumberOfWords($phash)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_rel', 'phash='.intval($phash));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function getGrlistRecord($phash)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_grlist.*', 'index_grlist', 'phash='.intval($phash));
		$allRows = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$row["pcount"] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			$allRows[] = $row;
		}
		return $allRows;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$phash: ...
	 * @return	[type]		...
	 */
	function getNumberOfFulltext($phash)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_fulltext', 'phash='.intval($phash));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getPhashTypes()	{
		$recList=array();

		// Types:
		$Itypes = array(
			"html" => 1,
			"htm" => 1,
			"pdf" => 2,
			"doc" => 3,
			"txt" => 4
		);

		$revTypes=array_flip($Itypes);
		$revTypes[0]="TYPO3 page";

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),item_type', 'index_phash', '', 'item_type', 'item_type');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
			$iT = $row[1];
			$recList[] = array($this->tableHead($revTypes[$iT]." ($iT)"), $this->countUniqueTypes($iT)."/".$row[0]);
		}

		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$item_type: ...
	 * @return	[type]		...
	 */
	function countUniqueTypes($item_type)	{
			// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_phash', 'item_type='.$GLOBALS['TYPO3_DB']->fullQuoteStr($item_type, 'index_phash'), 'phash_grouping');
		$items = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res))	{
			$items[] = $row;
		}
		return count($items);
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/mod/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/mod/index.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_mod_tools_isearch_index");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>