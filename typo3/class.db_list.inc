<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Include file extending t3lib_recordList
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   79: class recordList extends t3lib_recordList 
 *  124:     function start($id,$table,$pointer,$search="",$levels="",$showLimit=0)	
 *  165:     function setDispFields()	
 *  183:     function generateList()	
 *  227:     function thumbCode($row,$table,$field)	
 *  240:     function makeQuery($table, $id, $addWhere="",$fieldList="*")	
 *  269:     function setTotalItems()	
 *  288:     function makeSearchString($table)	
 *  319:     function linkWrapTable($table,$code)	
 *  337:     function linkWrapItems($table,$uid,$code,$row)	
 *  356:     function listURL($altId="",$table=-1,$exclList="")	
 *  375:     function requestUri()	
 *  385:     function getSearchBox($formFields=1)	
 *  432:     function makeFieldList($table,$dontCheckUser=0)	
 *  464:     function getTreeObject($id,$depth,$perms_clause)	
 *  482:     function showSysNotesForPage()	
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


 
 
 
 
 




/**
 * Child class for rendering of Web > List (not the final class. see class.db_list_extra)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 * @see localRecordList
 */
class recordList extends t3lib_recordList {
	var $thumbs = 0;						// Boolean. Thumbnails on records containing files (pictures)
	var $itemsLimitPerTable = 20;			// default Max items shown per table in "multi-table mode", may be overridden by tables.php
	var $itemsLimitSingleTable = 100;		// default Max items shown per table in "single-table mode", may be overridden by tables.php
	var $widthGif = '<img src="clear.gif" width=1 height=4 hspace=160>';
	var $script = "db_list.php";
	var $spaceSearchBoxFromLeft = 5;
	var $allFields=0;
	
		// internal
	var $JScode = "";	
	var $HTMLcode = "";
	var $table="";		// set to the tablename if single-table mode
	var $tableList="";	// Specify a list of tables which are the only ones allowed to be displayed.
	var $searchString="";
	var $searchLevels="";
	var $returnUrl="";
	var $showLimit=0;
	var $pidSelect="";
	var $iLimit=0;		// "LIMIT " in SQL...
	var $firstElementNumber=0;

	var $eCounter=0;		// Counting the elements no matter what...
	var $totalItems="";
	var $recPath_cache=array();
	var $csvLines=array();
	var $perms_clause="";
	var $csvOutput=0;
	var $setFields=array();
	var $calcPerms=0;
	var $currentTable = array();

	var $temp_countQ="";
	var $duplicateStack=array();
	

	/**
	 * @param	[type]		$id: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$pointer: ...
	 * @param	[type]		$search: ...
	 * @param	[type]		$levels: ...
	 * @param	[type]		$showLimit: ...
	 * @return	[type]		...
	 */
	function start($id,$table,$pointer,$search="",$levels="",$showLimit=0)	{
			// Starts the list-generation.
			// $id is the parent-id from which we generate the list
			// $table is the tablename - if single-table mode
		global $TCA;
		if ($TCA[$table])	{		// single-table mode
			$this->table=$table;	
		}
		$this->id=intval($id);	// sets the parent id

		$this->counter=0;	
		$this->JScode="";
		$this->HTMLcode="";
		$this->firstElementNumber=$pointer;
		$this->searchString=trim($search);
		$this->searchLevels=trim($levels);
		$this->showLimit=t3lib_div::intInRange($showLimit,0,10000);
		$this->csvOutput = t3lib_div::GPvar("csv") ? 1 : 0;
		$this->sortField = t3lib_div::GPvar("sortField");
		$this->sortRev = t3lib_div::GPvar("sortRev");
		
		if (!$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["thumbnails"])	{
			$this->thumbScript='gfx/notfound_thumb.gif';
		}
		
			// Set select levels:
		$sL=intval($this->searchLevels);
		$this->perms_clause = $GLOBALS["BE_USER"]->getPagePermsClause(1);
		if ($sL>0)	{
			$tree = $this->getTreeObject($id,$sL,$this->perms_clause);
			$this->pidSelect = "pid IN (".implode($tree->ids,",").")";
		} else {
			$this->pidSelect = "pid=".intval($id);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function setDispFields()	{
			// Display fields:
		$dispFields = $GLOBALS["BE_USER"]->getModuleData("db_list.php/displayFields");
		$dispFields_in = t3lib_div::GPvar("displayFields");
		if (is_array($dispFields_in))	{
			reset($dispFields_in);
			$tKey = key($dispFields_in);
			$dispFields[$tKey]=$dispFields_in[$tKey];
			$GLOBALS["BE_USER"]->pushModuleData("db_list.php/displayFields",$dispFields);
		}
		$this->setFields=$dispFields;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function generateList()	{
			// For each table we wish to show, this->getTable is called.
			// Finishes off with a stopper-gif
		global $TCA;
		reset($TCA);
		$tableCount = 0;
		while (list($key)=each($TCA))	{
			t3lib_div::loadTCA($key);
			$val=$TCA[$key];
			if ((!$this->table || $key==$this->table) && (!$this->tableList || t3lib_div::inList($this->tableList,$key)) && $GLOBALS["BE_USER"]->check("tables_select",$key))	{		// Checks that we see only permitted/requested tables:
				if ($this->table)	{		// iLimit is set depending on whether we're in single- or multi-table mode
					$this->iLimit=(isset($val["interface"]["maxSingleDBListItems"])?intval($val["interface"]["maxSingleDBListItems"]):$this->itemsLimitSingleTable);
				} else {
					$this->iLimit=(isset($val["interface"]["maxDBListItems"])?intval($val["interface"]["maxDBListItems"]):$this->itemsLimitPerTable);
				}
				if ($this->showLimit)	$this->iLimit = $this->showLimit;
//				$this->iLimit=3;
				$tableCount++;

				if ($this->allFields)	{
					$fields = $this->makeFieldList($key);
					$fields[]="_PATH_";
					$fields[]="_CONTROL_";
					if (is_array($this->setFields[$key]))	{
						$fields = array_intersect($fields,$this->setFields[$key]);
					} else {
						$fields = array();
					}
				} else {
					$fields = array();
				}
				$this->HTMLcode.=$this->getTable($key, $this->id,implode(",",$fields));
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$row: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function thumbCode($row,$table,$field)	{
		return t3lib_BEfunc::thumbCode($row,$table,$field,$this->backPath,$this->thumbScript);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @param	[type]		$addWhere: ...
	 * @param	[type]		$fieldList: ...
	 * @return	[type]		...
	 */
	function makeQuery($table, $id, $addWhere="",$fieldList="*")	{
			// returns the SQL-query to select the records from a table $table with pid = $id
			// Depends on the global var $perms_clause to exist!!
		global $TCA,$perms_clause;
		$orderBy = ($TCA[$table]["ctrl"]["sortby"]) ? "ORDER BY ".$TCA[$table]["ctrl"]["sortby"] : $TCA[$table]["ctrl"]["default_sortby"];
		if ($this->sortField)	{
			if (in_array($this->sortField,$this->makeFieldList($table,1)))	{
				$orderBy = "ORDER BY ".$this->sortField;
				if ($this->sortRev)	$orderBy.=" DESC";
			}
		}
		
		$limit = ($this->iLimit) ? "LIMIT ".($this->firstElementNumber ? ($this->firstElementNumber)."," : "" ).($this->iLimit+1) : "";
		$pC = ($table=="pages" && $perms_clause)?" AND ".$perms_clause:"";
		$search = $this->makeSearchString($table);

		$query = sprintf("SELECT ".$fieldList." FROM %s WHERE ".$this->pidSelect." %s %s %s %s %s %s",
				$table, $pC, t3lib_BEfunc::deleteClause($table), $addWhere, $search, $orderBy, $limit);
		$this->temp_countQ = sprintf("SELECT count(*) FROM %s WHERE ".$this->pidSelect." %s %s %s %s",
				$table, $pC, t3lib_BEfunc::deleteClause($table), $addWhere, $search);
//		debug($query,1);
		return $query;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function setTotalItems()	{
		$result = mysql(TYPO3_db,$this->temp_countQ);
		if (mysql_error())	{
			echo "<HR>
			<strong>MySQL error:</strong> ".mysql_error()."<BR>
			<strong>Query:</strong> ".$this->temp_countQ."<BR>
			<HR>
			";
		}
		list($rCount) = mysql_fetch_row($result);
		$this->totalItems = $rCount;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function makeSearchString($table)	{
		global $TCA;
		if ($TCA[$table] && $this->searchString)	{
			t3lib_div::loadTCA($table);

			$sfields=array();
			$sfields[]="uid";

			$columns = $TCA[$table]["columns"];
			reset($columns);
			while(list($fieldName,$info)=each($columns))	{
				$type = $info["config"]["type"];
				if ($type=="text" || ($type=="input" && !ereg("date|time|int",$info["config"]["eval"])))	{
					$sfields[]=$fieldName;
				}
			}
			$like=" LIKE '%".addslashes($this->searchString)."%'";
			if (count($sfields))	{
				$queryPart = " AND (".implode($like." OR ",$sfields).$like.")";
				return $queryPart;
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$code: ...
	 * @return	[type]		...
	 */
	function linkWrapTable($table,$code)	{
			// Returns the title (based on $code) of a table ($table) with the proper link around
		if ($this->table!=$table)	{
			return '<A HREF="'.$this->listURL("",$table).'">'.$code.'</a>';
		} else {
			return '<A HREF="'.$this->listURL("","","sortField,sortRev,table").'">'.$code.'</a>';
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$code: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function linkWrapItems($table,$uid,$code,$row)	{
			// Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
		if (!strcmp($code,"")) {$code="<i>[".$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.no_title")."]</i> - ".t3lib_BEfunc::getRecordTitle($table,$row);}
		$code=t3lib_div::fixed_lgd("&nbsp;".$code,$this->fixedL);
		if ($table=="pages")	{
			return '<A HREF="'.$this->listURL($uid).'">'.$code.'</a>';
		} else {
			return $code;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$altId: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$exclList: ...
	 * @return	[type]		...
	 */
	function listURL($altId="",$table=-1,$exclList="")	{
		return $this->script.
			'?id='.(strcmp($altId,"")?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table:$table).
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->returnUrl?"&returnUrl=".rawurlencode($this->returnUrl):"").
			($this->searchString?"&search_field=".rawurlencode($this->searchString):"").
			($this->searchLevels?"&search_levels=".rawurlencode($this->searchLevels):"").
			($this->showLimit?"&showLimit=".rawurlencode($this->showLimit):"").
			((!$exclList || !t3lib_div::inList($exclList,"sortField")) && $this->sortField?"&sortField=".rawurlencode($this->sortField):"").
			((!$exclList || !t3lib_div::inList($exclList,"sortRev")) && $this->sortRev?"&sortRev=".rawurlencode($this->sortRev):"")
			;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function requestUri()	{
		return $this->listURL();
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$formFields: ...
	 * @return	[type]		...
	 */
	function getSearchBox($formFields=1)	{
		if ($GLOBALS["CLIENT"]["BROWSER"]=="net")	{
//			$content.= '<img src=clear.gif width=1 height=100><BR>';
		}
		$formElements=array("","");
		if ($formFields)	{
			$formElements=array('<form action="'.$this->listURL().'" method="POST">','</form>');
		}
			// Make level selector:
		$opt=array();
		$parts = explode("|",$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.enterSearchLevels"));
		while(list($kv,$label)=each($parts))	{
			$opt[] = '<option value="'.$kv.'"'.($kv==intval($this->searchLevels)?" selected":"").'>'.htmlspecialchars($label).'</option>';
		}
		$lMenu = '<select name="search_levels">'.implode("",$opt).'</select>';
		
			// Table with the search box:
		$content.= '
		<table border=0 cellpadding=1 cellspacing=0>
		'.$formElements[0].'
			<tr>
				<td><img src=clear.gif width='.$this->spaceSearchBoxFromLeft.' height=1></td>
				<td bgcolor="#9BA1A8">
					<table border=0 cellpadding=0 cellspacing=0 bgcolor="'.$GLOBALS["TBE_TEMPLATE"]->bgColor4.'">
					<tr>
						<td nowrap>&nbsp;'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.enterSearchString").'&nbsp;&nbsp;<input type="Text" name="search_field" value="'.htmlspecialchars($this->searchString).'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(10).'></td>
						<td>'.$lMenu.'</td>
						<td><input type="Submit" name="search" value="'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.search").'"></td>
					</tr>
					<tr>
						<td nowrap colspan=3>&nbsp;'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.showRecords").':&nbsp;&nbsp;<input type="text" name="showLimit" value="'.($this->showLimit?$this->showLimit:"").'"'.$GLOBALS["SOBE"]->doc->formWidth(4).'></td>
					</tr>
					</table>			
				</td>
			</tr>'.$formElements[1].'
		</table>
		';
		return $content;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$dontCheckUser: ...
	 * @return	[type]		...
	 */
	function makeFieldList($table,$dontCheckUser=0)	{
		global $TCA,$BE_USER;
		$fieldListArr = array();
		if (is_array($TCA[$table]))	{
			t3lib_div::loadTCA($table);
			reset($TCA[$table]["columns"]);
			while(list($fN,$fieldValue)=each($TCA[$table]["columns"]))	{
				if ($dontCheckUser || 
					((!$fieldValue["exclude"] || $BE_USER->check("non_exclude_fields",$table.":".$fN)) && $fieldValue["config"]["type"]!="passthrough"))	{
					$fieldListArr[]=$fN;
				}
			}
			if ($dontCheckUser || $BE_USER->isAdmin())	{
				$fieldListArr[]="uid";
				$fieldListArr[]="pid";
				if ($TCA[$table]["ctrl"]["tstamp"])	$fieldListArr[]=$TCA[$table]["ctrl"]["tstamp"];
				if ($TCA[$table]["ctrl"]["crdate"])	$fieldListArr[]=$TCA[$table]["ctrl"]["crdate"];
				if ($TCA[$table]["ctrl"]["cruser_id"])	$fieldListArr[]=$TCA[$table]["ctrl"]["cruser_id"];
				if ($TCA[$table]["ctrl"]["sortby"])	$fieldListArr[]=$TCA[$table]["ctrl"]["sortby"];
			}
		}
		return $fieldListArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @param	[type]		$depth: ...
	 * @param	[type]		$perms_clause: ...
	 * @return	[type]		...
	 */
	function getTreeObject($id,$depth,$perms_clause)	{
		$tree = t3lib_div::makeInstance("t3lib_pageTree");
		$tree->init("AND ".$perms_clause);
		$tree->makeHTML=0;
		$tree->fieldArray = Array("uid","php_tree_stop");
		if ($depth)	{
			$tree->getTree($id, $depth, "");
		}
		$tree->ids[]=$id;
//		debug($tree->ids);
		return $tree;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function showSysNotesForPage()	{
		global $TCA;

		if (!t3lib_extMgm::isLoaded("sys_note"))	return "";

		$delClause = t3lib_BEfunc::deleteClause("sys_note");
		$query = "SELECT * FROM sys_note where pid in (".$this->id.") AND (!personal OR cruser='".$GLOBALS["BE_USER"]->user["uid"]."')".$delClause;
		$result = mysql(TYPO3_db,$query);
		if (mysql_error()) {echo mysql_error(); debug($query);}
		$dbCount = mysql_num_rows($result);
		if ($dbCount)	{
			$cat = array();
			t3lib_div::loadTCA("sys_note");
			if ($TCA["sys_note"] && $TCA["sys_note"]["columns"]["category"] && is_array($TCA["sys_note"]["columns"]["category"]["config"]["items"]))	{
				reset($TCA["sys_note"]["columns"]["category"]["config"]["items"]);
				while(list(,$el)=each($TCA["sys_note"]["columns"]["category"]["config"]["items"]))	{
					$cat[$el[1]]=$GLOBALS["LANG"]->sL($el[0]);
				}
			}
			while($row=mysql_fetch_assoc($result))	{
				$icon= t3lib_iconWorks::getIcon("sys_note",$row);
				$fields=array();
				$subject=$row["subject"];

				$fields["Author:"]=$row["author"].($row["email"] && $row["author"] ? ", ":"").$row["email"];
				$fields["Category:"]=$cat[$row["category"]];
				$fields["Note:"]=nl2br($row[message]);
				$bgcol=' bgcolor="'.$GLOBALS["SOBE"]->doc->bgColor2.'"';
				$bgcol2=' bgcolor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"';
				
				$out.='
				<table border=0 cellpadding=1 cellspacing=1 width=1>
					<tr><td colspan=2'.$bgcol.'><img src="'.$icon.'" width=18 height=16 align=top><strong>'.$subject.'</strong></td></tr>
					<tr><td'.$bgcol2.'>'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.category").'</td><td'.$bgcol2.'>'.$fields["Category:"].'</td></tr>
					<tr><td'.$bgcol2.'>'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.author").'</td><td'.$bgcol2.'>'.$fields["Author:"].'</td></tr>
					<tr><td'.$bgcol2.'>'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.note").'</td><td'.$bgcol2.'>'.$fields["Note:"].'</td></tr>
					<tr><td colspan=2><img src=clear.gif width=380 height=1></td></tr>
				</table>
				';
//				debug($fields);
			}
		}
		return $out ? "".$out : "";
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.db_list.inc']);
}
?>