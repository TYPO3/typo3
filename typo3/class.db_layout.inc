<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Include file extending db_list.inc for use with the web_layout module
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

class recordList_layout extends recordList {

	var $nextThree = 3;
	var $pI_showUser=0;
	var $pI_showStat=1;
	var $pages_noEditColumns=0;
	var $option_showBigButtons=1;
	var $option_newWizard=1;
	var $ext_function=0;

	var $tt_contentConfig = Array (
		"showInfo" => 1,		// Boolean: Display info-marks or not
		"showCommands" => 1,	// Boolean: Display up/down arrows and edit icons for tt_content records 
		"showCommands_info" => 1,	// Boolean: Displays infoGif with the command buttons
		"single" => 1, 			// Boolean: If set, the content of column(s) $this->tt_contentConfig["showSingleCol"] is shown in the total width of the page
		"showSingleCol" => 0,	// The column(s) to show if single mode (under each other)
		"languageCols" => 0,
		"languageMode" => 0,
		"languageColsPointer" => 0,
		"showHidden" => 1,		// Displays hidden records as well
		"sys_language_uid" => 0,	// Which language
		"cols" => "1,0,2,3"		// The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
	);
	
			// Internal:
	var $tt_contentData = Array(
		"nextThree" => Array(),
		"prev" => Array(),
		"next" => Array()
	);


	var $stat_select_field="page_id";
	var $stat_codes=array();	// eg. 	"HITS_days:-1"
	
	var $activeTables = array();
	var $doEdit=1;
	var $agePrefixes = " min| hrs| days| yrs";
	
	var $spaceSearchBoxFromLeft = 0;
	function getTable($table,$id)	{
		// Adds the code of a single table
		global $TCA;
		t3lib_div::loadTCA($table);
		switch($table)	{
			case "sys_note":
				return $this->getTable_sys_note($table,$id);
			break;
			case "pages":
				return $this->getTable_pages($table,$id);
			break;
			case "tt_board":
				return $this->getTable_tt_board($table,$id);
			break;
			case "tt_address":
				return $this->getTable_tt_address($table,$id);
			break;
			case "tt_links":
				return $this->getTable_tt_links($table,$id);
			break;
			case "tt_guest":
				return $this->getTable_tt_guest($table,$id);
			break;
			case "tt_news":
				return $this->getTable_tt_news($table,$id);
			break;
			case "fe_users":
				return $this->getTable_fe_users($table,$id);
			break;
			case "tt_calender":
				return $this->getTable_tt_calender($table,$id);
			break;
			case "tt_products":
				return $this->getTable_tt_products($table,$id);
			break;
			case "tt_content":
				return $this->getTable_tt_content($table,$id);
			break;
		}
	}
	
	function getTable_sys_note($table,$id)	{
		global $TCA;
		
		if (!t3lib_extMgm::isLoaded("sys_note"))	return "";

		$perms_clause = $GLOBALS["BE_USER"]->getPagePermsClause(1);
		$tree = $this->getTreeObject($id,intval($GLOBALS["SOBE"]->MOD_SETTINGS["pages_levels"]),$perms_clause);

		$this->itemLabels =array();
		while(list($name,$val)=each($TCA[$table]["columns"]))	{
			$this->itemLabels[$name]=$GLOBALS["LANG"]->sL($val["label"]);
		}
		if (count($tree->ids))		{
			$delClause = t3lib_BEfunc::deleteClause($table);
			$query = "SELECT * FROM sys_note where pid in (".implode($tree->ids,",").") AND (!personal OR cruser='".$GLOBALS["BE_USER"]->user[uid]."')".$delClause;
			$result = mysql(TYPO3_db,$query);
			if (mysql_error()) {echo mysql_error(); debug($query);}
			$dbCount = mysql_num_rows($result);
			$out="";
			if ($dbCount)	{
				$this->fieldArray = explode(",","__cmds__,info,note");

					// Start table:
				$out.='<table border=0 cellpadding=1 cellspacing=2 width=480>';

					// header line is drawn
				$theData = Array();
				$theData["__cmds__"] ="";
				$theData["info"] = '<b>Info</b><BR><img src="clear.gif" height=1 width=220>';
				$theData["note"] = '<b>Note</b>';
				$out.=$this->addelement(1,'',$theData,' bgcolor="'.$this->headLineCol.'"',20);

					// half line is drawn
				$theData = Array();
				$theData["info"] = $this->widthGif;
				$out.=$this->addelement(0,'',$theData);
		
				$this->no_noWrap=1;
					// items
				$this->eCounter=$this->firstElementNumber;
				while ($row = mysql_fetch_assoc($result))	{
					list($flag,$code) = $this->fwd_rwd_nav();
					$out.=$code;
					if ($flag)	{
						$color = Array (
							0 => '',		// No category
							1 => ' bgcolor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"',		// Instructions
							2 => ' bgcolor="'.$GLOBALS["SOBE"]->doc->bgColor2.'"',		// Template
							3 => '',		// Notes
							4 => ' bgcolor="'.$GLOBALS["SOBE"]->doc->bgColor5.'"'		// To-do
						);
						$tdparams = $color[$row["category"]];
							// getting childs:

						$info = Array();;
						$theData = Array();
						$this->getProcessedValue($table,"subject,category,author,email,personal",$row,$info);
						$cont=implode($info,"<BR>");
						$head = "<B>Page:</b> ".t3lib_BEfunc::getRecordPath($row["pid"],$perms_clause,10)."<BR>";
						$theData["__cmds__"]= $this->getIcon($table,$row,$row["subject"]);
						$theData["info"] = $head.$cont;
						$theData["note"] = nl2br($row["message"]);
						$out.=$this->addelement(1,'',$theData,$tdparams,20);


							// half line is drawn
						$theData = Array();
						$theData["info"] = $this->widthGif;
						$out.=$this->addelement(0,'',$theData);
					}
					$this->eCounter++;
				}
				$out.='</table>';
			}
		}
		return $out;
	}
	function getTable_pages($table,$id)	{
		global $TCA;

		$delClause = t3lib_BEfunc::deleteClause($table)." AND ".$GLOBALS["BE_USER"]->getPagePermsClause(1);
		$out="";

		$query = "SELECT * FROM pages where uid='".$id."'".$delClause;
		$result = mysql(TYPO3_db,$query);
		echo mysql_error();
		$row=mysql_fetch_assoc($result);
		if (!$id)	$row=$GLOBALS["SOBE"]->pageinfo;
		if (is_array($row))	{
			$pKey = $GLOBALS["SOBE"]->MOD_SETTINGS["function"]=="tx_cms_webinfo_hits" ? "hits" : $GLOBALS["SOBE"]->MOD_SETTINGS["pages"];

			switch($pKey)	{
				case "hits":
					$this->fieldArray = explode(",","title,".implode(",",$this->stat_codes));
				break;
				case 1:
					$this->cleanTableNames();
					$tableNames=$this->allowedTableNames;
					$this->fieldArray = explode(",","title,uid,".implode(",",array_keys($tableNames)));
				break;
				case 2:
					$this->fieldArray = explode(",","title,uid,lastUpdated,newUntil,no_cache,cache_timeout,php_tree_stop,TSconfig,storage_pid,is_siteroot");
				break;
				default:
					$this->fieldArray = explode(",","title,uid,alias,starttime,endtime,fe_group,target,url,shortcut");
				break;
			}
			$depth=intval($GLOBALS["SOBE"]->MOD_SETTINGS["pages_levels"]);
			
				// half line is drawn
			$theData = Array();
			$theData["subject"] = $this->widthGif;
			$out.=$this->addelement(0,'',$theData);

				// Overriding a few things:
			$this->no_noWrap=0;
			$color=$GLOBALS["SOBE"]->doc->bgColor3;
			$this->oddColumnsTDParams=' bgcolor="'.t3lib_div::modifyHTMLColor($color,-20,-20,-20).'"';

				// items
			$this->eCounter=$this->firstElementNumber;

			list($flag,$code) = $this->fwd_rwd_nav();
			$out.=$code;
			$editUids=array();
			if ($flag)	{
					// getting childs:
				$theRows = Array();
				$theRows = $this->pages_getTree($theRows,$row["uid"],$delClause,"",$depth);
				if ($GLOBALS["BE_USER"]->doesUserHaveAccess($row,2))	$editUids[]=$row["uid"];
				$out.=$this->pages_drawItem($table,$row,count($theRows),$this->fieldArray);

				reset($theRows);
				while(list($n,$sRow)=each($theRows))	{
					if ($GLOBALS["BE_USER"]->doesUserHaveAccess($sRow,2))	$editUids[]=$sRow["uid"];
					$out.=$this->pages_drawItem($table,$sRow,0,$this->fieldArray);
				}
				$this->eCounter++;	
			}


				// header line is drawn
			$theData = Array();
			$editIdList=implode(",",$editUids);
			reset($this->fieldArray);
			while(list(,$field)=each($this->fieldArray))	{
				if ($editIdList && isset($TCA["pages"]["columns"][$field]) && $field!="uid" && !$this->pages_noEditColumns)	{
					$params="&edit[pages][".$editIdList."]=edit&columnsOnly=".$field."&disHelp=1";
					$eI= '<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,$this->backPath,"").'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib(sprintf($GLOBALS["LANG"]->getLL("editThisColumn"),ereg_replace(":$","",trim($GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel("pages",$field)))))).'></a>';
				} else $eI="";
				switch($field)	{
					case "title":
						$theData[$field] = '&nbsp;<b>'.$GLOBALS["LANG"]->sL($TCA[$table]["columns"][$field]["label"]).'</b>'.$eI;
					break;
					case "uid":
						$theData[$field] = '&nbsp;<b>ID:</b>';
					break;
					default:
						if (substr($field,0,6)=="table_")	{
							$f2 = substr($field,6);
							if ($TCA[$f2])	{
								$theData[$field] = '&nbsp;<img src="'.$this->backPath.t3lib_iconWorks::getIcon($f2).'" width=18 height=16'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL($TCA[$f2]["ctrl"]["title"]),1).'>';
							}
						} elseif (substr($field,0,5)=="HITS_")	{
							$fParts = explode(":",substr($field,5));
							switch($fParts[0])	{
								case "days":
									$timespan = mktime (0,0,0)+intval($fParts[1])*3600*24;
									$theData[$field]='&nbsp;'.date("d",$timespan);
								break;
								default:
									$theData[$field] = "";
								break;
							}
						} else {
							$theData[$field] = '&nbsp;&nbsp;<b>'.$GLOBALS["LANG"]->sL(stripslashes($TCA[$table]["columns"][$field]["label"])).'</b>'.$eI;
						}
					break;
				}
			}

				// Start table:
			$this->oddColumnsTDParams="";
			$out='<table border=0 cellpadding=0 cellspacing=0>'.$this->addelement(1,'',$theData,' bgcolor="'.$this->headLineCol.'"',20).$out."</table>";
		}
		$this->oddColumnsTDParams="";
		return $out;
	}
	function getTable_tt_board($table,$id)	{
		global $TCA;

		$delClause = t3lib_BEfunc::deleteClause($table);
		$query = $this->makeQuery($table, $id, "AND parent=0");
		$this->setTotalItems();
		$dbCount=0;

		if ($this->totalItems)	{
			$result = mysql(TYPO3_db,$query);
			if (mysql_error()) {echo mysql_error(); debug($query);}
			$dbCount = mysql_num_rows($result);
		}
		$out="";
		if ($dbCount)	{
			if ($GLOBALS["SOBE"]->MOD_SETTINGS["tt_board"]=="expand")	{
				$this->fieldArray = explode(",","subject,author,date,age");
			} else {
				$this->fieldArray = explode(",","subject,author,date,age,replys");
			}

				// Start table:
			$out.='<table border=0 cellpadding=0 cellspacing=0>';

				// header line is drawn
			$theData = Array();
			$theData["subject"] = '<b>'.$GLOBALS["LANG"]->getLL("tt_board_subject").'</b>';	
			$theData["author"] = '<b>'.$GLOBALS["LANG"]->getLL("tt_board_author").'</b>';
			$theData["date"] = '<b>'.$GLOBALS["LANG"]->getLL("tt_board_date").'</b>';
			$theData["age"] = '<b>'.$GLOBALS["LANG"]->getLL("tt_board_age").'</b>';
			if ($GLOBALS["SOBE"]->MOD_SETTINGS["tt_board"]!="expand") {
				$theData["replys"] = '<b>'.$GLOBALS["LANG"]->getLL("tt_board_RE").'</b>';
			}
			$out.=$this->addelement(1,'',$theData,' bgcolor="'.$this->headLineCol.'"',20);

				// half line is drawn
			$theData = Array();
			$theData["subject"] = $this->widthGif;
			$out.=$this->addelement(0,'',$theData);

				// items
			$this->eCounter=$this->firstElementNumber;
			while ($row = mysql_fetch_assoc($result))	{
				list($flag,$code) = $this->fwd_rwd_nav();
				$out.=$code;
				if ($flag)	{
						// getting childs:
					$theRows = Array();
					$theRows = $this->tt_board_getTree ($theRows,$row["uid"],$id,$delClause,"");
					$out.=$this->tt_board_drawItem($table,$row,count($theRows));

					if ($GLOBALS["SOBE"]->MOD_SETTINGS["tt_board"]=="expand")	{
						reset($theRows);
						while(list($n,$sRow)=each($theRows))	{
							$out.=$this->tt_board_drawItem($table,$sRow,0);
						}
					}
					// IKKE-bundne records!!
				}
				$this->eCounter++;	
			}
				// finish
			$out.="</table>";	
		}

		return $out;
	}
	function getTable_tt_address($table,$id)	{
		global $TCA;

		switch($GLOBALS["SOBE"]->MOD_SETTINGS["tt_address"])	{
			case 1:
				$icon = 0;
				$fList = "name,address,zip,city,country";
			break;
			case 2:
				$icon = 1;
				$fList = "name;title;email;company,image";
			break;
			default:
				$icon = 0;
				$fList = "name,email,www,phone,fax,mobile";
			break;
		}
		$out = $this->makeOrdinaryList($table,$id, $fList, $icon);
		return $out;
	}
	function getTable_tt_links($table,$id)	{
		global $TCA;

		switch($GLOBALS["SOBE"]->MOD_SETTINGS["tt_links"])	{
			case 1:
				$fList = "title,hidden,url";
			break;
			case 2:
				$fList = "title;url,note2";
			break;
			default:
				$fList = "title;url,note";
			break;
		}

		$out = $this->makeOrdinaryList($table,$id, $fList,1);
		return $out;
	}
	function getTable_tt_guest($table,$id)	{
		global $TCA;

		$fList = "title;cr_name;cr_email,note";
		$out = $this->makeOrdinaryList($table,$id, $fList, 1);
		return $out;
	}
	function getTable_tt_news($table,$id)	{
		global $TCA;

		$this->addElement_tdParams=array(
			"title"=>" nowrap",
			"datetime"=>" nowrap",
			"starttime"=>" nowrap",
			"author"=>" nowrap"
		);
		$fList = "title,author,author_email,datetime,starttime,category,image";
		$out = $this->makeOrdinaryList($table,$id, $fList, 1);
		$this->addElement_tdParams=array();
		return $out;
	}
	function getTable_fe_users($table,$id)	{
		global $TCA;

		$this->addElement_tdParams=array(
			"username"=>" nowrap",
			"password"=>" nowrap",
			"usergroup"=>" nowrap",
			"name"=>" nowrap",
			"address"=>" nowrap",
			"zip"=>" nowrap",
			"city"=>" nowrap",
			"email"=>" nowrap",
			"telephone"=>" nowrap"
		);
		$fList = "username,password,usergroup,name,email,telephone,address,zip,city";
		$out = $this->makeOrdinaryList($table,$id, $fList, 1);
		$this->addElement_tdParams=array();
		return $out;
	}
	function getTable_tt_calender($table,$id)	{
		global $TCA;

		$type=$GLOBALS["SOBE"]->MOD_SETTINGS["tt_calender"];
		switch($type)	{
			case "date":
					// Date default
				$fList = "date,title";
				$icon=0;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=0");
				return $out;
			break;
			case "date_ext":
					// Date extended
				$fList = "title;date;time;datetext;link,note";
				$icon=1;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=0");
				return $out;
			break;
			case "todo":
					// Todo default
				$fList = "title,complete,priority,date";
				$icon=0;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=1");
				return $out;
			break;
			case "todo_ext":
					// Todo extended
				$fList = "title;complete;priority;date;workgroup;responsible;category,note";
				$icon=1;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=1");
				return $out;
			break;
			default:
					// Overview, both todo and calender
				$fList = "title,date,time,week";
				$icon=1;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=0");
				$out.= $this->makeOrdinaryList($table,$id, $fList, $icon, " AND type=1");
				return $out;
			break;
		}
	}
	function getTable_tt_products($table,$id)	{
		global $TCA;

		$type=$GLOBALS["SOBE"]->MOD_SETTINGS["tt_products"];
		switch($type)	{
			case "ext":
				$fList = "title;itemnumber;price;price2;inStock;category,image,note";
				$icon=1;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon);
				return $out;
			break;
			default:
				$fList = "title,itemnumber,price,category,image";
				$icon=1;
				$out = $this->makeOrdinaryList($table,$id, $fList, $icon);
				return $out;
			break;
		}
	}
	function getTable_tt_content($table,$id)	{
		global $TCA;

		$RTE = $GLOBALS["BE_USER"]->isRTE();
		
		$lMarg=1;
		$showHidden = $this->tt_contentConfig["showHidden"]?"":t3lib_BEfunc::BEenableFields("tt_content");

		$pageTitleParamForAltDoc='&recTitle='.rawurlencode(t3lib_BEfunc::getRecordTitle("pages",t3lib_BEfunc::getRecord("pages",$id),1));

		
		reset($TCA[$table]["columns"]["CType"]["config"]["items"]);
		$this->CType_labels =array();
		while(list(,$val)=each($TCA[$table]["columns"]["CType"]["config"]["items"]))	{
			$this->CType_labels[$val[1]]=$GLOBALS["LANG"]->sL($val[0]);
		}
		reset($TCA[$table]["columns"]);
		$this->itemLabels =array();
		while(list($name,$val)=each($TCA[$table]["columns"]))	{
			$this->itemLabels[$name]=$GLOBALS["LANG"]->sL($val["label"]);
		}



		if (!$this->tt_contentConfig["single"])	{
			$langList = $this->tt_contentConfig["sys_language_uid"];
			if ($this->tt_contentConfig["languageMode"])	{
				if ($this->tt_contentConfig["languageColsPointer"])	{
					$langList="0,".$this->tt_contentConfig["languageColsPointer"];
				} else {
					$langList=implode(",",array_keys($this->tt_contentConfig["languageCols"]));
				}
				$languageColumn=array();
			}
			$langListArr = explode(",",$langList);
			reset($langListArr);
			while(list(,$lP)=each($langListArr))	{	// If NOT languageMode, then we'll only be through this once.
				$showLanguage = " AND sys_language_uid=".$lP;
				$delClause = t3lib_BEfunc::deleteClause($table);
				$cList = explode(",",$this->tt_contentConfig["cols"]);
				$content=array();
				$head=array();
				while(list(,$key)=each($cList))	{
					$query = $this->makeQuery($table, $id, "AND colPos=".intval($key).$showHidden.$showLanguage);
					$result = mysql(TYPO3_db,$query);
					if (mysql_error()) {echo mysql_error(); debug($query);}


					if ($this->option_showBigButtons && !intval($key) && !mysql_num_rows($result))	{
						$onClick="document.location='db_new_content_el.php?id=".$id."&colPos=".intval($key)."&sys_language_uid=".$lP."&uid_pid=".$id."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';";
						$theNewButton=$GLOBALS["SOBE"]->doc->t3Button($onClick,$GLOBALS["LANG"]->getLL("newPageContent"));
						$content[$key].='<img src=clear.gif width=1 height=5><BR>'.$theNewButton;
					}

					$rowArr = $this->getResult($result);
					reset($rowArr);
					while (list(,$row)=each($rowArr))	{
						$editUidList.=$row["uid"].",";
						$content[$key].=$this->tt_content_drawHeader($row,$this->tt_contentConfig["showInfo"]?15:5);
						
						$isRTE=$RTE && $this->isRTEforField("tt_content",$row,"bodytext");
						$content[$key].=$this->linkEditContent($this->tt_content_drawItem($row),$row,$isRTE);
					}
					$newP = $this->newContentElementOnClick($id,$key,$lP);
					$head[$key].=$this->tt_content_drawColHeader(t3lib_BEfunc::getProcessedValue($table,"colPos",$key), ($this->doEdit&&count($rowArr)?"&edit[tt_content][".$editUidList."]=edit".$pageTitleParamForAltDoc:""), $newP);
					$editUidList="";
				}
				$out = '<tr>';
					reset($cList);
					while(list($k,$key)=each($cList))	{
						if (!$k)	{
							$out.= '<td><img src="clear.gif" width='.$lMarg.' height=1></td>';
						} else {
							$out.= '<td><img src="clear.gif" width=4 height=1></td><td bgColor="black"><img src="clear.gif" width=1 height=1></td><td><img src="clear.gif" width=4 height=1></td>';
						}
						$out.= '<td valign=top>'.$head[$key].$content[$key].'</td>';
						if ($this->tt_contentConfig["languageMode"])	{
							$languageColumn[$key][$lP]=$head[$key].$content[$key]."<BR><BR>";
						}
					}
				$out.= '</tr>';
				$out = '<table border=0 cellpadding=0 cellspacing=0 width=480>'.$out.'</table>';
			} 
			
				// If language mode, then make another presentation
			if ($this->tt_contentConfig["languageMode"])	{
				$languageSelector = $this->languageSelector($id);
			
				$out="";
				$midSep = '<td><img src="clear.gif" width=4 height=1></td><td bgColor="black"><img src="clear.gif" width=1 height=1></td><td><img src="clear.gif" width=4 height=1></td>';

				$cCont=array();
				$sCont=array();
				reset($langListArr);
				while(list(,$lP)=each($langListArr))	{
					$cCont[$lP]='<td valign=top align=center bgColor="'.$GLOBALS["SOBE"]->doc->bgColor6.'"><strong>'.$this->tt_contentConfig["languageCols"][$lP].'</strong></td>';
					if ($lP)	{
						list($lpRecord) = t3lib_BEfunc::getRecordsByField("pages_language_overlay","pid",$id,"AND sys_language_uid=".$lP);
						$params="&edit[pages_language_overlay][".$lpRecord["uid"]."]=edit&overrideVals[pages_language_overlay][sys_language_uid]=".$lP;
						$lPLabel = '<nobr>'.t3lib_iconWorks::getIconImage("pages_language_overlay",$lpRecord,$this->backPath,"align=absmiddle").'<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("edit").'" align="absmiddle"></a>'.htmlspecialchars(t3lib_div::fixed_lgd($lpRecord["title"],20)).'</nobr>';
					} else {
						$lPLabel="";
					}
					$sCont[$lP]='<td>'.$lPLabel.'</td>';
				}
				$out.='<tr>'.implode($midSep,$cCont).'</tr>';
				$out.='<tr>'.implode($midSep,$sCont).'</tr>';

				reset($languageColumn);
				while(list($cKey,$cCont)=each($languageColumn))	{
					$out.='<tr><td valign=top>'.implode('</td>'.$midSep.'<td valign=top>',$cCont).'</td></tr>';
				}
				$out = $languageSelector.'<table border=0 cellpadding=0 cellspacing=0 width=480>'.$out.'</table>';
			}
		} else {
			$showLanguage = " AND sys_language_uid=".$this->tt_contentConfig["sys_language_uid"];
			$delClause = t3lib_BEfunc::deleteClause($table);
			$cList = explode(",",$this->tt_contentConfig["showSingleCol"]);
			$content=array();

			$out="";
					$out.='<tr>
						<td><img src="clear.gif" width='.$lMarg.' height=1></td>
						<td valign=top><img src="clear.gif" width=150 height=1></td>
						<td><img src="clear.gif" width=10 height=1></td>
						<td valign=top><img src="clear.gif" width=300 height=1></td>
						</tr>
					';
			while(list($counter,$key)=each($cList))	{
					
				$query = $this->makeQuery($table, $id, "AND colPos=".intval($key).$showHidden.$showLanguage);
				$result = mysql(TYPO3_db,$query);
				if (mysql_error()) {echo mysql_error(); debug($query);}
				$c=0;
				$rowArr = $this->getResult($result);
				$rowOut="";

				if ($this->option_showBigButtons && !intval($key) && !mysql_num_rows($result))	{
					$onClick="document.location='db_new_content_el.php?id=".$id."&colPos=".intval($key)."&sys_language_uid=".$lP."&uid_pid=".$id."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';";
					$theNewButton=$GLOBALS["SOBE"]->doc->t3Button($onClick,$GLOBALS["LANG"]->getLL("newPageContent"));
					$theNewButton='<img src=clear.gif width=1 height=5><BR>'.$theNewButton;
				} else $theNewButton="";

				reset($rowArr);
				while (list(,$row)=each($rowArr))	{
					$editUidList.=$row["uid"].",";
					$c++;

					$isRTE=$RTE && $this->isRTEforField("tt_content",$row,"bodytext");
					$rowOut.='<tr>
						<td></td>
						<td valign=top>'.$this->tt_content_drawHeader($row).'</td>
						<td></td>
						<td valign=top>'.$this->linkEditContent($this->tt_content_drawItem($row),$row,$isRTE).'</td></tr>
					';
					if ($c!=mysql_num_rows($result))	{
						$rowOut.='<tr><td></td><td colspan=3>
							<img src="gfx/stiblet_medium2.gif" width=468 height=1>
							</td></tr>
						';
					}
				}
				if ($counter)	{
					$out.='<tr><td></td><td colspan=3><BR><BR><BR><BR>
						</td></tr>
					';
				}

				$newP = $this->newContentElementOnClick($id,$key,$this->tt_contentConfig["sys_language_uid"]);
				$out.='<tr>
					<td></td>
					<td valign=top colspan=3>'.
						$this->tt_content_drawColHeader(t3lib_BEfunc::getProcessedValue($table,"colPos",$key), ($this->doEdit&&count($rowArr)?"&edit[tt_content][".$editUidList."]=edit".$pageTitleParamForAltDoc:""), $newP).
						$theNewButton.
						'<BR></td></tr>
				';
				$out.=$rowOut;
			}
			$out = '<table border=0 cellpadding=0 cellspacing=0 width=400>'.$out.'</table>';
		}
		
			// edit page title:
		if ($this->option_showBigButtons)	{
			$bArray=array();
			$bArray[0]=$GLOBALS["SOBE"]->doc->t3Button(t3lib_BEfunc::editOnClick("&edit[pages][".$id."]=edit",$this->backPath,""),$GLOBALS["LANG"]->getLL("editPageTitle"));
			$bArray[1]=$GLOBALS["SOBE"]->doc->t3Button("document.location='move_el.php?table=pages&uid=".$id."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';",$GLOBALS["LANG"]->getLL("move_page"));
			$bArray[2]=$GLOBALS["SOBE"]->doc->t3Button("document.location='db_new.php?id=".$id."&pagesOnly=1&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';",$GLOBALS["LANG"]->getLL("newPage2"));
			if ($this->ext_function==1) $bArray[3]=$GLOBALS["SOBE"]->doc->t3Button("document.location='db_new_content_el.php?id=".$id."&sys_language_uid=".$GLOBALS["SOBE"]->current_sys_language."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';",$GLOBALS["LANG"]->getLL("newPageContent2"));
			$out='<table border=0 cellpadding=4 cellspacing=0><tr><td>'.implode("</td><td>",$bArray).'</td></tr></table><img src=clear.gif width=1 height=5><BR>'.$out;
		}
		
		return $out;
	}
	function newContentElementOnClick($id,$colPos,$sys_language)	{
		if ($this->option_newWizard)	{
			$onClick="document.location='db_new_content_el.php?id=".$id."&colPos=".$colPos."&sys_language_uid=".$sys_language."&uid_pid=".$id."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';";
		} else {
			$onClick=t3lib_BEfunc::editOnClick("&edit[tt_content][".$id."]=new&defVals[tt_content][colPos]=".$colPos."&defVals[tt_content][sys_language_uid]=".$sys_language);
		}
		return $onClick;
	}
	
	function linkEditContent($str,$row,$isRTE=0)	{
		$addButton="";
		$onClick = "";

		if ($this->doEdit)	{
			if ($isRTE)	{
				$params = array();
				$params["table"] = "tt_content";
				$params["uid"] = $row["uid"];
				$params["pid"] = $row["pid"];
				$params["field"] = "bodytext";
				$params["returnUrl"] = t3lib_div::linkThisScript();
				$onClick="document.location='wizard_rte.php?".t3lib_div::implodeArrayForUrl("",array("P"=>$params))."';return false;";
				$addButton=$this->option_showBigButtons?$GLOBALS["SOBE"]->doc->t3Button($onClick,$GLOBALS["LANG"]->getLL("editInRTE")):"";
			}// else {
				$onClick=t3lib_BEfunc::editOnClick("&edit[tt_content][".$row["uid"]."]=edit");
			//}
		}
		return $onClick ? '<a href="#" onClick="'.$onClick.'"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("edit"),1).'>'.$str.'</a>'.$addButton : $str;
	}
	function languageSelector($id)	{
		if ($GLOBALS["BE_USER"]->check("tables_select","pages_language_overlay"))	{
				// All:
			$query = $GLOBALS["SOBE"]->languageQuery(0);
			$res = mysql(TYPO3_db,$query);
			$langSelItems=array();
			$langSelItems[0]='<option value="0"></option>';
			while($row=mysql_fetch_assoc($res))	{
				$langSelItems[$row["uid"]]='<option value="'.$row["uid"].'">'.$row["title"].'</option>';
			}
				// Local:
			$query = $GLOBALS["SOBE"]->languageQuery($id);
			$res = mysql(TYPO3_db,$query);
			while($row=mysql_fetch_assoc($res))	{
				unset($langSelItems[$row["uid"]]);
			}
			if (count($langSelItems)>1)		{
				return $GLOBALS["LANG"]->getLL("new_language").': <select name="createNewLanguage" onChange="document.location=\'alt_doc.php?&edit[pages_language_overlay]['.$id.']=new&overrideVals[pages_language_overlay][sys_language_uid]=\'+this.options[this.selectedIndex].value+\'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'\'">'.implode("",$langSelItems).'</select><BR><BR>';
			}
		}
	}
	function makeOrdinaryList($table, $id, $fList, $icon=0, $addWhere="")	{
		global $TCA;
		$out="";
		$delClause = t3lib_BEfunc::deleteClause($table);
		$query = $this->makeQuery($table, $id, $addWhere);
		$this->setTotalItems();
		$dbCount=0;
		if ($this->totalItems)	{
			$result = mysql(TYPO3_db,$query);
			$titleCol = $TCA[$table][ctrl][label];
			if (mysql_error()) {echo mysql_error(); debug($query);}
			$dbCount = mysql_num_rows($result);
		}
		$out="";
		if ($dbCount)	{
			$this->fieldArray = explode(",","__cmds__,".$fList);

				// Start table:
			$out.='<table border=0 cellpadding=1 cellspacing=2 width=480>';

				// header line is drawn
			$theData=array();
			$theData = $this->headerFields($this->fieldArray,$table,$theData);
			$out.=$this->addelement(1,'',$theData,' bgcolor="'.$this->headLineCol.'"',15);
			
				// items
			$this->eCounter=$this->firstElementNumber;
			while ($row = mysql_fetch_assoc($result))	{
				list($flag,$code) = $this->fwd_rwd_nav();
				$out.=$code;
				if ($flag)	{
						$params="&edit[".$table."][".$row["uid"]."]=edit";
						$Nrow=array();
						if ($icon)	{
							$recTitle = htmlspecialchars($row[$titleCol]);
							$Nrow["__cmds__"]= $this->getIcon($table,$row,$recTitle);
						}
						if ($this->doEdit)	{
							$Nrow["__cmds__"].='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("edit").'" align="top"></a>';
						} else {
							$Nrow["__cmds__"].=$this->noEditIcon();
						}

						$Nrow = $this->dataFields($this->fieldArray,$table,$row,$Nrow);
						$color=$GLOBALS["SOBE"]->doc->bgColor4;
						$tdparams = $this->eCounter%2 ? ' bgcolor="'.$color.'"' : ' bgcolor="'.t3lib_div::modifyHTMLColor($color,-20,-20,-20).'"';
						$out.=$this->addelement(1,'',$Nrow,$tdparams);
					// IKKE-bundne records!!
				}
				$this->eCounter++;	
			}
				// finish
			$out.="</table>";	
		}
		return $out;
	}
	function dataFields($fieldArr,$table,$row,$out=array())	{
		global $TCA;
		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			reset($fieldArr);
			$thumbsCol = $TCA[$table]["ctrl"]["thumbnail"];
			while(list(,$fieldName)=each($fieldArr))	{
				if ($TCA[$table]["columns"][$fieldName])	{
					if ($fieldName==$thumbsCol)	{
						$out[$fieldName] = $this->thumbCode($row,$table,$fieldName);
					} else {
						$out[$fieldName] = nl2br(trim(t3lib_div::fixed_lgd(t3lib_BEfunc::getProcessedValue($table,$fieldName,$row[$fieldName]),250)));
					}
				} else {
					$theFields = explode(";",$fieldName);
					while(list(,$fName2)=each($theFields))	{
						if ($TCA[$table]["columns"][$fName2])	{
							 $out[$fieldName].= "<b>".$GLOBALS["LANG"]->sL($TCA[$table]["columns"][$fName2]["label"])."</b>&nbsp;&nbsp;".t3lib_div::fixed_lgd(t3lib_BEfunc::getProcessedValue($table,$fName2,$row[$fName2]),25)."<BR>";
						}
					}
				}
				if (!$out[$fieldName])	$out[$fieldName]="&nbsp;";
				if ($this->isDisabled($table,$row))	{
					$out[$fieldName] = $GLOBALS["TBE_TEMPLATE"]->dfw($out[$fieldName]);
				} else {
					$out[$fieldName] = $out[$fieldName];
				}
			}
		}
		return $out;
	}
	function isDisabled($table,$row)	{
		global $TCA;
		if (
			($TCA[$table]["ctrl"]["enablecolumns"]["disabled"] && $row[$TCA[$table]["ctrl"]["enablecolumns"]["disabled"]]) ||
			($TCA[$table]["ctrl"]["enablecolumns"]["starttime"] && $row[$TCA[$table]["ctrl"]["enablecolumns"]["starttime"]]>time() ) ||
			($TCA[$table]["ctrl"]["enablecolumns"]["endtime"] && $row[$TCA[$table]["ctrl"]["enablecolumns"]["endtime"]] && $row[$TCA[$table]["ctrl"]["enablecolumns"]["endtime"]]<time())
		)	return true;
	}
	function headerFields($fieldArr,$table,$out=array())	{
		global $TCA;
		t3lib_div::loadTCA($table);
		reset($fieldArr);
		while(list(,$fieldName)=each($fieldArr))	{
			$ll = $GLOBALS["LANG"]->sL($TCA[$table]["columns"][$fieldName]["label"]);
			$out[$fieldName] = '<B>'.($ll?$ll:"&nbsp;").'</b>';
		}
		return $out;
	}
	function getResult($result)	{
		$editUidList="";
		$recs=Array();
		$nextTree = $this->nextThree;
		$c=0;
		$output=Array();
		while ($row = mysql_fetch_assoc($result))	{
			$output[]=$row;
			$recs[$c]=$row["uid"];
			
			for($a=0;$a<$nextTree;$a++)	{
				if(isset($recs[$c-$a]))	{
					$this->tt_contentData["nextThree"][$recs[$c-$a]].=$row["uid"].",";
					
				}
			}
			if (isset($recs[$c-1]))	{
				if (isset($recs[$c-2]))	{
					$this->tt_contentData["prev"][$row["uid"]]=-$recs[$c-2];
				} else {
					$this->tt_contentData["prev"][$row["uid"]]=$row["pid"];
				}
				$this->tt_contentData["next"][$recs[$c-1]]=-$row["uid"];
			}
			$c++;
		}
		return $output;
	}
	function tt_board_getTree ($theRows,$parent,$pid,$qWhere,$treeIcons) {
		$query = "SELECT * FROM tt_board WHERE pid=".$pid." and parent=".$parent.$qWhere." ORDER BY crdate";
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$c=0;
		$rc = mysql_num_rows($res);
		while($row = mysql_fetch_assoc($res))	{
			$c++;
			$row["treeIcons"] = $treeIcons.($rc==$c?'<img src="'.$this->backPath.'t3lib/gfx/ol/joinbottom.gif" width=18 height=16 align="top">':'<img src="'.$this->backPath.'t3lib/gfx/ol/join.gif" width=18 height=16 align="top">');
			$theRows[]=$row;
				
				// get the branch
			$theRows = $this->tt_board_getTree($theRows,$row["uid"],$row["pid"],$qWhere,$treeIcons.($rc==$c?'<img src="'.$this->backPath.'t3lib/gfx/ol/blank.gif" width=18 height=16 align="top">' : '<img src="'.$this->backPath.'t3lib/gfx/ol/line.gif" width=18 height=16 align="top">'));
		}
		return $theRows;	
	}
	function tt_board_drawItem($table,$row,$re)	{
			// Initialization
		$recTitle = htmlspecialchars($row["subject"]);
		$theIcon=$this->getIcon($table,$row,$recTitle);
		
			// 	Preparing and getting the data-array
		$theData = Array();
		$theData["subject"] = t3lib_div::fixed_lgd($recTitle,25).'&nbsp; &nbsp;';
		$theData["author"] = t3lib_div::fixed_lgd($row["author"],15).'&nbsp; &nbsp;';
		$theData["date"] = t3lib_div::fixed_lgd(t3lib_BEfunc::datetime($row["crdate"]),20).'&nbsp; &nbsp;';
		$theData["age"] = t3lib_BEfunc::calcAge(time()-$row["crdate"], $this->agePrefixes).'&nbsp; &nbsp;';
		if ($re)	{
			$theData["replys"] = $re;
		}


		$theData["subject"]=$row["treeIcons"].$theIcon.$theData["subject"];
		return $this->addelement(1,"",$theData);
	}
	function tt_content_drawHeader($row,$space=0)	{
		global $TCA;
		$table="tt_content";
		t3lib_div::loadTCA($table);
		$recTitle = $this->CType_labels[$row["CType"]];

		if ($lockInfo=t3lib_BEfunc::isRecordLocked("tt_content",$row["uid"]))	{
			$lockIcon='<a href="#" onClick="alert('.$GLOBALS['LANG']->JScharCode($lockInfo["msg"]).');return false;"><img src="gfx/recordlock_warning3.gif" width="17" height="12" vspace=2 border="0" align=top'.t3lib_BEfunc::titleAttrib($lockInfo["msg"]).'></a>';
		} else $lockIcon="";

		$header = $this->getIcon("tt_content",$row,$recTitle).$lockIcon.'&nbsp;<b>'.$recTitle.'</b>';

		$out = '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'">'.$header.'</td></tr>';
		if ($this->tt_contentConfig["showInfo"])	{
			$info = Array();;

			$this->getProcessedValue($table,"hidden,starttime,endtime,fe_group,spaceBefore,spaceAfter,section_frame,sectionIndex,linkToTop",$row,$info);
			
//			t3lib_div::print_array($info);

			if ($this->tt_contentConfig["showCommands"])	{
				$out.= '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'">';
					// Info:
/*				if ($this->tt_contentConfig["showCommands_info"] && count($info))	{
					$out.=trim($this->infoGif($info));
				}
	*/			if ($this->doEdit)	{
						// Edit:				
					$params="&edit[tt_content][".$this->tt_contentData["nextThree"][$row["uid"]]."]=edit";
					$out.='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 hspace=2 border=0 title="'.($this->nextThree>1?sprintf($GLOBALS["LANG"]->getLL("nextThree"),$this->nextThree):$GLOBALS["LANG"]->getLL("edit")).'" align="top"></a>';
		
						// New:

					if ($this->option_newWizard)	{
						$onClick="document.location='db_new_content_el.php?id=".$row["pid"].'&sys_language_uid='.$row["sys_language_uid"]."&colPos=".$row["colPos"]."&uid_pid=".(-$row["uid"])."&returnUrl=".rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))."';";
					} else {
						$params="&edit[tt_content][".(-$row["uid"])."]=new";
						$onClick = t3lib_BEfunc::editOnClick($params);
					}
					$out.='<A HREF="#" onClick="'.$onClick.'"><img src="'.$this->backPath.'gfx/new_record.gif" width=16 height=12 hspace=1 border=0 title="'.$GLOBALS["LANG"]->getLL("newAfter").'" align="top"></a>';
		
						// Up
					if ($this->tt_contentData["prev"][$row["uid"]])	{
						$params="&cmd[tt_content][".$row["uid"]."][move]=".$this->tt_contentData["prev"][$row["uid"]];
						$out.='<A HREF="'.$GLOBALS["SOBE"]->doc->issueCommand($params).'"><img src="'.$this->backPath.'gfx/button_up.gif" width=11 height=10 vspace=1 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("moveUp").'" align="top"></a>';
					} else {
						$out.='<img src="clear.gif" width=11 height=10 vspace=1 hspace=2 align="top">';
					}
						// Down
					if ($this->tt_contentData["next"][$row["uid"]])	{
						$params="&cmd[tt_content][".$row["uid"]."][move]=".$this->tt_contentData["next"][$row["uid"]];
						$out.='<A HREF="'.$GLOBALS["SOBE"]->doc->issueCommand($params).'"><img src="'.$this->backPath.'gfx/button_down.gif" width=11 height=10 vspace=1 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("moveDown").'" align="top"></a>';
					} else {
						$out.='<img src="clear.gif" width=11 height=10 vspace=1 hspace=2 align="top">';
					}
						// Hide
					$hiddenField = $TCA[$table]["ctrl"]["enablecolumns"]["disabled"];
					if ($hiddenField && $TCA[$table]["columns"][$hiddenField] && (!$TCA[$table]["columns"][$hiddenField]["exclude"] || $GLOBALS["BE_USER"]->check("non_exclude_fields",$table.":".$hiddenField)))	{
						if ($row[$hiddenField])	{
							$params="&data[tt_content][".$row["uid"]."][".$hiddenField."]=0";
							$out.='<A HREF="'.$GLOBALS["SOBE"]->doc->issueCommand($params).'"><img src="'.$this->backPath.'gfx/button_unhide.gif" width=11 height=10 vspace=1 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("unHide").'" align="top"></a>';
						} else {
							$params="&data[tt_content][".$row["uid"]."][".$hiddenField."]=1";
							$out.='<A HREF="'.$GLOBALS["SOBE"]->doc->issueCommand($params).'"><img src="'.$this->backPath.'gfx/button_hide.gif" width=11 height=10 vspace=1 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("hide").'" align="top"></a>';
						}
					}

						// Delete
					$params="&cmd[tt_content][".$row["uid"]."][delete]=1";
					$out.='<A HREF="'.$GLOBALS["SOBE"]->doc->issueCommand($params).'" onClick="return confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS["LANG"]->getLL("deleteWarning")).');"><img src="'.$this->backPath.'gfx/garbage.gif" width=11 height=12 vspace=0 hspace=1 border=0 title="'.$GLOBALS["LANG"]->getLL("deleteItem").'" align="top"></a>';
				}
				$out.= '</td></tr>';
			}
			if (count($info))	{
				$out.= '<tr><td bgColor="'.t3lib_div::modifyHTMLColor($GLOBALS["SOBE"]->doc->bgColor4,-20,-20,-20).'">'.implode($info,'<BR>').'</td></tr>';
			}
		}
		return '<table border=0 cellpadding=0 cellspacing=0 width="100%">'.($space?'<tr><td><img src="clear.gif" height='.$space.'></td></tr>':'').$out.'</table>';
	}
	function tt_content_drawColHeader($colName,$editParams,$newParams)	{
		global $TCA;
		$table="tt_content";

		$out = '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor2.'" nowrap><img src="clear.gif" width=1 height=2><BR><b><div align="center">'.strtoupper($colName).'</div></b><img src="clear.gif" width=1 height=2></td></tr>';

		if ($this->tt_contentConfig["showCommands"])	{
			$out.= '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'">';
				// Edit:				
			if ($editParams)	{
				$params=$editParams;
				$out.='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("editColumn").'" align="top"></a>';
			}
			if ($newParams)	{
				$params=$newParams;
				$out.='<A HREF="#" onClick="'.$params.'"><img src="'.$this->backPath.'gfx/new_record.gif" width=16 height=12 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("newInColumn").'" align="top"></a>';
			}

			$out.= '</td></tr>';
		}
		return '<table border=0 cellpadding=0 cellspacing=0 width="100%">'.($space?'<tr><td><img src="clear.gif" height='.$space.'></td></tr>':'').$out.'</table>';
	}
	function pages_getTree ($theRows,$pid,$qWhere,$treeIcons,$depth) {
		$depth--;
		if ($depth>=0)	{
			$query = "SELECT * FROM pages WHERE pid=".$pid.$qWhere." ORDER BY sorting";
			$res = mysql(TYPO3_db,$query);
			echo mysql_error();
			$c=0;
			$rc = mysql_num_rows($res);
			while($row = mysql_fetch_assoc($res))	{
				$c++;
				$row["treeIcons"] = $treeIcons.($rc==$c?'<img src="'.$this->backPath.'t3lib/gfx/ol/joinbottom.gif" width=18 height=16 align="top">':'<img src="'.$this->backPath.'t3lib/gfx/ol/join.gif" width=18 height=16 align="top">');
				$theRows[]=$row;
				
				// get the branch
				$spaceOutIcons = ($rc==$c?'<img src="'.$this->backPath.'t3lib/gfx/ol/blank.gif" width=18 height=16 align="top">':'<img src="'.$this->backPath.'t3lib/gfx/ol/line.gif" width=18 height=16 align="top">');
				$theRows = $this->pages_getTree($theRows,$row["uid"],$qWhere,$treeIcons.$spaceOutIcons,$row["php_tree_stop"]?0:$depth);
			}
		} else {
			$query = "SELECT count(*) FROM pages WHERE pid=".$pid.$qWhere;
			$res = mysql(TYPO3_db,$query);
			$row = mysql_fetch_row($res);
			if ($row[0])	{
				$this->plusPages[$pid]=$row[0];
			}			
		}
		return $theRows;	
	}
	function pages_drawItem($table,$row,$re,$fieldArr)	{
		global $TCA;
			// Initialization
		$recTitle = htmlspecialchars($row["title"]);
		$theIcon=$this->getIcon($table,$row,$recTitle);
		
			// 	Preparing and getting the data-array
		$theData = Array();
		while(list(,$field)=each($fieldArr))	{
			switch($field)	{
				case "title":
					$red = $this->plusPages[$row["uid"]] ? '<font color="red"><b>+&nbsp;</b></font>' : '';
					$pTitle = t3lib_BEfunc::getProcessedValue($table,$field,$row[$field],20);
					if ($red)	{
						$pTitle = '<a href="'.$this->script.'?id='.$row["uid"].'">'.$pTitle.'</a>';
					}
					$theData[$field]=$row["treeIcons"].$theIcon.$red.$pTitle.'&nbsp;&nbsp;';
				break;
				case "php_tree_stop":
				case "TSconfig":
					$theData[$field] = $row[$field]?'&nbsp;<b>x</b>':'&nbsp;';
				break;
				case "uid":
					if ($GLOBALS["BE_USER"]->doesUserHaveAccess($row,2))	{
						$params="&edit[pages][".$row["uid"]."]=edit";
						$eI= '<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,$this->backPath,"").'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 hspace=2 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("editThisPage")).'></a>';
					} else $eI="";
					$theData[$field] = '<div align="right">'.$row["uid"].$eI.'</div>';
				break;
				default:
					if (substr($field,0,6)=="table_")	{
						$f2 = substr($field,6);
						if ($TCA[$f2])	{
							$c = $this->numberOfRecords($f2,$row["uid"]);
							$theData[$field] = '&nbsp;&nbsp;'.($c?$c:'');
						}
					} elseif (substr($field,0,5)=="HITS_")	{
						if (t3lib_extMgm::isLoaded("sys_stat"))	{
							$fParts = explode(":",substr($field,5));
							switch($fParts[0])	{
								case "days":
									$timespan = mktime (0,0,0)+intval($fParts[1])*3600*24;
										// Page hits
									$query='SELECT count(*) FROM sys_stat 
										WHERE '.$this->stat_select_field.'='.$row["uid"].' 
										AND tstamp>='.$timespan.' AND tstamp<'.($timespan+3600*24);
									$res=mysql(TYPO3_db,$query);
									list($number) = mysql_fetch_row($res);
									if ($number)	{
											// Sessions
										$query.=' AND surecookie!="" GROUP BY surecookie';
										$res=mysql(TYPO3_db,$query);
										$scnumber = mysql_num_rows($res);
										
										$number.= "/".$scnumber;
									} else {
										$number="";
									}
								break;
							}
							$theData[$field]= '&nbsp;'.$number;
						} else {
							$theData[$field]= '&nbsp;';
						}
					} else {
						$theData[$field]= '&nbsp;&nbsp;'.t3lib_BEfunc::getProcessedValue($table,$field,$row[$field]);
					}
				break;			
			}
		}
		return $this->addelement(1,"",$theData);
	}
	function numberOfRecords($table,$pid)	{
		global $TCA;
		if ($TCA[$table])	{
			$delClause = t3lib_BEfunc::deleteClause($table);
			$query = "SELECT count(*) FROM ".$table." where pid='".$pid."'".$delClause;
			$result = mysql(TYPO3_db,$query);
			$c=0;
			if ($row=mysql_fetch_row($result))	{
				$c=$row[0];
			}
			return $c;
		}
	}
	function getProcessedValue($table,$fieldList,$row,&$info)	{
		$fieldArr = explode(",",$fieldList);
		while(list(,$field)=each($fieldArr))	{
			if ($row[$field])	{
				$info[]= $this->itemLabels[$field]." ".htmlspecialchars(t3lib_BEfunc::getProcessedValue($table,$field,$row[$field]));
			}
		}
	}
	function tt_content_drawItem($row)	{
		global $TCA;
		$out="";
		$table="tt_content";
		if ($row["header"] && $row["header_layout"]!=100)	{
			$infoArr = Array();
			$this->getProcessedValue($table,"header_position,header_layout,header_link",$row,$infoArr);

			$out.=  ($row["date"]?$this->itemLabels["date"]." ".t3lib_BEfunc::date($row["date"])."<BR>":""). 
					$this->infoGif($infoArr).
					"<b>".$this->renderText($row["header"])."</b><BR>";
		}
		$infoArr=Array();
		switch($row["CType"])	{
			case "header":
				if ($row["subheader"])	{
					$this->getProcessedValue($table,"layout",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->renderText($row["subheader"])."<BR>";
				}
			break;
			case "text":
			case "textpic":
			case "image":
				if ($row["CType"]=="text" || $row["CType"]=="textpic")	{
					if ($row["bodytext"])	{
						$this->getProcessedValue($table,"text_align,text_face,text_size,text_color,text_properties",$row,$infoArr);
						$out.=	$this->infoGif($infoArr).
								$this->renderText($row["bodytext"])."<BR>";
					}
				}
				if ($row["CType"]=="textpic" || $row["CType"]=="image")	{	
					if ($row["image"])	{
						$infoArr=Array();
						$this->getProcessedValue($table,"imageorient,imagecols,image_noRows,imageborder,imageheight,image_link,image_zoom,image_compression,image_effects,image_frames",$row,$infoArr);
						$out.=	$this->infoGif($infoArr).
								$this->thumbCode($row,"tt_content","image")."<BR>";
								
						if ($row["imagecaption"])	{
							$infoArr=Array();
							$this->getProcessedValue($table,"imagecaption_position",$row,$infoArr);
							$out.=	$this->infoGif($infoArr).
									$this->renderText($row["imagecaption"])."<BR>";
						}
					}
				}
			break;
			case "bullets":
				if ($row["bodytext"])	{
					$this->getProcessedValue($table,"layout,text_align,text_face,text_size,text_color,text_properties",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->renderText($row["bodytext"])."<BR>";
				}
			break;
			case "table":
				if ($row["bodytext"])	{
					$this->getProcessedValue($table,"table_bgColor,table_border,table_cellspacing,cols,layout,text_align,text_face,text_size,text_color,text_properties",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->renderText($row["bodytext"])."<BR>";
				}
			break;
			case "uploads":
				if ($row["media"])	{
					$this->getProcessedValue($table,"media,select_key,layout,filelink_size,table_bgColor,table_border,table_cellspacing",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->renderText($row["bodytext"])."<BR>";
				}
			break;
			case "multimedia":
				if ($row["multimedia"])	{
					$out.=	$this->renderText($row["multimedia"])."<BR>";
					$out.=	$this->renderText($row["parameters"])."<BR>";
				}
			break;
			case "mailform":
				if ($row["bodytext"])	{
					$this->getProcessedValue($table,"pages,subheader",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->renderText($row["bodytext"])."<BR>";
				}
			break;
			case "splash":
				if ($row["bodytext"])	{
					$out.=	$this->renderText($row["bodytext"])."<BR>";
				}
				if ($row["image"])	{
					$infoArr=Array();
					$this->getProcessedValue($table,"imagewidth",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$this->thumbCode($row,"tt_content","image")."<br>";
				}
			break;
			case "menu":
				if ($row["pages"])	{
					$this->getProcessedValue($table,"menu_type",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$row["pages"]."<BR>";
				}
			break;
			case "shortcut":
				if ($row["records"])	{
					$this->getProcessedValue($table,"layout",$row,$infoArr);
					$out.=	$this->infoGif($infoArr).
							$row["shortcut"]."<BR>";
				}
			break;
			case "list":
				$this->getProcessedValue($table,"layout",$row,$infoArr);
				$out.=	$this->infoGif($infoArr).
						$GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel($table,"list_type"))." ".
						$GLOBALS["LANG"]->sL(t3lib_BEfunc::getLabelFromItemlist($table,"list_type",$row["list_type"]))."<BR>";

				$out.=	$GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel($table,"select_key"))." ".$row["select_key"]."<BR>";

				$infoArr=Array();
				$this->getProcessedValue($table,"recursive",$row,$infoArr);
				$out.=	$this->infoGif($infoArr).
						$GLOBALS["LANG"]->sL(t3lib_BEfunc::getLabelFromItemlist($table,"pages",$row["pages"]))."<BR>";
			break;
			case "script":
				$out.=	$GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel($table,"select_key"))." ".$row["select_key"]."<BR>";
				$out.=	"<BR>".$this->renderText($row["bodytext"])."<BR>";
				$out.=	"<BR>".$this->renderText($row["imagecaption"])."<BR>";
			break;
			default:
				if ($row["bodytext"])	{
					$out.=$this->renderText($row["bodytext"])."<BR>";
				}
			break;
		}
		if ($this->isDisabled($table,$row))	{
			return $GLOBALS["TBE_TEMPLATE"]->dfw($out);
		} else {
			return $out;
		}
	}
	function getPageInfoBox($rec,$edit=0)	{
		global $LANG;
		
		if ($edit)	{
			$params="&edit[pages][".$rec["uid"]."]=edit";
			$editIcon='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 vspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL("edit").'" align="top"></a>';
		} else {
			$editIcon=$this->noEditIcon("noEditPage");
		}
								
		$out = t3lib_iconWorks::getIconImage("pages",$rec,$this->backPath,"align=top ".t3lib_BEfunc::titleAttribForPages($rec)).$editIcon."&nbsp;".htmlspecialchars($rec["title"]);
		

		$lines=array();

		if ($this->pI_showUser)	{
			// User:
			$users= t3lib_BEfunc::getUserNames("username,usergroup,usergroup_cached_list,uid,realName");
			$groupArray = explode(",",$GLOBALS["BE_USER"]->user["usergroup_cached_list"]);
			$users=t3lib_BEfunc::blindUserNames($users,$groupArray);
			$lines[]=array($LANG->getLL("pI_crUser").":",htmlspecialchars($users[$rec["cruser_id"]]["username"])." (".$users[$rec["cruser_id"]]["realName"].")");
		}

			// Created:		
		$lines[]=array($LANG->getLL("pI_crDate").":",t3lib_BEfunc::datetime($rec["crdate"])." (".t3lib_BEfunc::calcAge(time()-$rec["crdate"],$this->agePrefixes).")");
			// Last change:		
		$lines[]=array($LANG->getLL("pI_lastChange").":",t3lib_BEfunc::datetime($rec["tstamp"])." (".t3lib_BEfunc::calcAge(time()-$rec["tstamp"],$this->agePrefixes).")");
			// Last change of content:		
		if ($rec["SYS_LASTCHANGED"])	{
			$lines[]=array($LANG->getLL("pI_lastChangeContent").":",t3lib_BEfunc::datetime($rec["SYS_LASTCHANGED"])." (".t3lib_BEfunc::calcAge(time()-$rec["SYS_LASTCHANGED"],$this->agePrefixes).")");
		}
	
		$lines[]="";
		
			// Fields
		$dfields = explode(",","alias,target,hidden,starttime,endtime,fe_group,no_cache,cache_timeout,newUntil,lastUpdated,subtitle,keywords,description,abstract,author,author_email");
		while(list(,$fV)=each($dfields))	{
			if ($rec[$fV])	{
				$lines[]=array($GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel("pages",$fV)),t3lib_BEfunc::getProcessedValue("pages",$fV,$rec[$fV]));
			}
		}
		
			// Hits:
		if ($this->pI_showStat && t3lib_extMgm::isLoaded("sys_stat"))	{
			$query="SELECT count(*) FROM sys_stat WHERE page_id=".$rec["uid"];
			$res=mysql(TYPO3_db,$query);
			$rrow=mysql_fetch_row($res);
			if ($rrow[0])	{
					// Get min/max
				$query="SELECT min(tstamp) AS min,max(tstamp) AS max FROM sys_stat WHERE page_id=".$rec["uid"];
				$res=mysql(TYPO3_db,$query);
				$rrow2=mysql_fetch_row($res);
	
				$lines[]="";
				$lines[]=array($LANG->getLL("pI_hitsPeriod").":",t3lib_BEfunc::date($rrow2[0])." - ".t3lib_BEfunc::date($rrow2[1])." (".t3lib_BEfunc::calcAge($rrow2[1]-$rrow2[0],$this->agePrefixes).")");
				$lines[]=array($LANG->getLL("pI_hitsTotal").":",$rrow[0]);
	
	
				// Last 10 days
				$nextMidNight = mktime (0,0,0)+1*3600*24;
				$query='SELECT count(*), FLOOR(('.$nextMidNight.'-tstamp)/(24*3600)) AS day FROM sys_stat WHERE page_id='.$rec["uid"].' AND tstamp>'.($nextMidNight-10*24*3600).' GROUP BY day';
				$res=mysql(TYPO3_db,$query);
				$days=array();
				while($rrow=mysql_fetch_row($res))	{
					$days[$rrow[1]]=$rrow[0];
				}
				
				$headerH=array();
				$contentH=array();
				for($a=9;$a>=0;$a--)	{
					$headerH[]='<td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'" nowrap>&nbsp;'.date("d",$nextMidNight-($a+1)*24*3600).'&nbsp;</td>';
					$contentH[]='<td align=center>'.($days[$a]?intval($days[$a]):"-").'</td>';
				}
				$hitTable='<table border=0 cellpadding=0 cellspacing=1><tr>'.implode("",$headerH).'</tr><tr>'.implode("",$contentH).'</tr></table>';
				$lines[]=array($LANG->getLL("pI_hits10days").":",$hitTable,1);
	
	
				// Last 24 hours
				$nextHour = mktime (date("H"),0,0)+3600;
				$hours=16;
				
				$query='SELECT count(*), FLOOR(('.$nextHour.'-tstamp)/3600) AS hours FROM sys_stat WHERE page_id='.$rec["uid"].' AND tstamp>'.($nextHour-$hours*3600).' GROUP BY hours';
				$res=mysql(TYPO3_db,$query);
				$days=array();
				while($rrow=mysql_fetch_row($res))	{
					$days[$rrow[1]]=$rrow[0];
				}
				
				$headerH=array();
				$contentH=array();
				for($a=($hours-1);$a>=0;$a--)	{
					$headerH[]='<td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'" nowrap>&nbsp;'.intval(date("H",$nextHour-($a+1)*3600)).'&nbsp;</td>';
					$contentH[]='<td align=center>'.($days[$a]?intval($days[$a]):"-").'</td>';
				}
				$hitTable='<table border=0 cellpadding=0 cellspacing=1><tr>'.implode("",$headerH).'</tr><tr>'.implode("",$contentH).'</tr></table>';
				$lines[]=array($LANG->getLL("pI_hits24hours").":",$hitTable,1);
			}
		}		
		
		// Print:
		reset($lines);
		$out.='<table border=0 cellpadding=0 cellspacing=1>';
		while(list(,$fV)=each($lines))	{
			if (is_array($fV))	{
				if (!$fV[2])	$fV[1]=htmlspecialchars($fV[1]);
				$out.='<tr>
					<td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'" nowrap><strong>'.htmlspecialchars($fV[0]).'&nbsp;&nbsp;</strong></td>
					<td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'">'.$fV[1].'</td>
				</tr>';
			} else {
				$out.='<tr><td colspan=2><img src=clear.gif width=1 height=3></td></tr>';
			}
		}
		$out.='</table>';
		return $out;
	}
	function infoGif($infoArr)	{
		if (count($infoArr) && $this->tt_contentConfig["showInfo"])	{
			$out='<img src="'.$this->backPath.'gfx/zoom2.gif" width=12 height=12 title="'.implode($infoArr,$GLOBALS["CLIENT"]["BROWSER"]=="net"?", ":chr(10)).'" align="top" border=0> ';	
			return $out;
		}
	}
	function renderText($input)	{
		$input = strip_tags($input);	// added 150402 - don't know it this is a good idea, but it should help better recognize the content ...
		$input = t3lib_div::fixed_lgd($input,1500);
//		return $this->wordWrapper(trim(nl2br(htmlspecialchars($input))));
		return nl2br(htmlspecialchars(trim($this->wordWrapper($input))));
	}
	function getIcon($table,$row,$recTitle)	{
		global $TCA;
			// Initialization
		$iconfile = t3lib_iconWorks::getIcon($table,$row);
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
		$this->counter++;

			// The icon with link	
		$theIcon = '<img src="'.$this->backPath.$iconfile.'" width=18 height=16 border=0 title="'.$alttext.'" align="top">';
		$theIcon = $GLOBALS["SOBE"]->doc->wrapClickMenuOnIcon($theIcon,$table,$row["uid"]);

		return $theIcon;
	}
	function getTableMenu($id)	{
		global $TCA;
		$theTables=explode(",","tt_content,fe_users,tt_address,tt_links,tt_board,tt_guest,tt_calender,tt_products,tt_news");

		$this->activeTables=array();
		$out = '<table border=0 cellpadding=0 cellspacing=0><tr>';
		while(list(,$tName)=each($theTables))	{
			if ($GLOBALS["BE_USER"]->check("tables_select",$tName) && (t3lib_extMgm::isLoaded($tName)||t3lib_div::inList("fe_users,tt_content",$tName)))	{
				$query = "SELECT count(*) FROM ".$tName." WHERE pid=".$id.t3lib_BEfunc::deleteClause($tName);
				$result = mysql(TYPO3_db,$query);
				list($c)=mysql_fetch_row($result);
				if ($c || t3lib_div::inList("tt_content",$tName))	{
					$out.='<td><a href="#'.$tName.'"><img src="'.t3lib_iconWorks::getIcon($tName,Array()).'" width=18 height=16 hspace=2 border=0 title="'.$GLOBALS["LANG"]->sL($TCA[$tName]["ctrl"]["title"]).'"></td>';
					$this->activeTables[$tName]='<img src="'.t3lib_iconWorks::getIcon($tName,Array()).'" width=18 height=16 border=0 title="'.$GLOBALS["LANG"]->sL($TCA[$tName]["ctrl"]["title"]).": ".$c.' '.$GLOBALS["LANG"]->getLL("records").'" align=absmiddle>&nbsp;'.$GLOBALS["LANG"]->sL($TCA[$tName]["ctrl"]["title"]);
				}
			}
		}
		$out.='</tr></table>';
		return $out;
	}
	function wordWrapper($content,$max=50,$char=' -')	{	// max was 20 formerly...
//		$array = explode(" ",$content);			// Old
		$array = split(" |".chr(10),$content);
		while(list(,$val)=each($array))	{
//			if (strlen($val)>$max && !strstr($val,chr(10)))	{	// Old
			if (strlen($val)>$max)	{
				$content=str_replace($val,substr(chunk_split($val,$max,$char),0,-1),$content);
			}
		}
		return $content;
	}
	function getLabelFromItemlist($arr,$key)	{
		reset($arr);
		while(list($k,$v)=each($arr))	{
			if (!strcmp($v[1],$key))	return $v[0];
		}
	}
	function noEditIcon($label="noEditItems")	{
		return '<img src="'.$this->backPath.'gfx/edit2_d.gif" width=11 height=12 hspace=2 border=0 title="'.$GLOBALS["LANG"]->getLL($label).'" align="top">';
	}
	function cleanTableNames()	{
		global $TCA;
		
		$tableNames=array_flip(array_keys($TCA));
		unset($tableNames["pages"]);
		unset($tableNames["static_template"]);
		unset($tableNames["sys_filemounts"]);
		unset($tableNames["sys_action"]);
		unset($tableNames["sys_workflows"]);
		unset($tableNames["be_users"]);
		unset($tableNames["be_groups"]);
	
		$this->allowedTableNames=array();
		if (is_array($tableNames))	{
			reset($tableNames);
			while(list($k,$v)=each($tableNames))	{
				if ($GLOBALS["BE_USER"]->check("tables_select",$k))			$this->allowedTableNames["table_".$k]=$k;
			}
		}
	}
	function getSpecConfForField($table,$row,$field)	{
		$types_fieldConfig=t3lib_BEfunc::getTCAtypes($table,$row);
		if (is_array($types_fieldConfig))	{
			reset($types_fieldConfig);
			while(list(,$vconf)=each($types_fieldConfig))	{
				if ($vconf["field"]==$field)	return $vconf["spec"];
			}
		}
	}
	function isRTEforField($table,$row,$field){
		$specConf = $this->getSpecConfForField($table,$row,$field);
		$p=t3lib_BEfunc::getSpecConfParametersFromArray($specConf["rte_transform"]["parameters"]);
		if (isset($specConf["richtext"]) && (!$p["flag"] || !$row[$p["flag"]]))	{
			list($tscPID,$thePidValue)=t3lib_BEfunc::getTSCpid($table,$row["uid"],$row["pid"]);
			if ($thePidValue>=0)	{	// If the pid-value is not negative (that is, a pid could NOT be fetched)
				$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($tscPID));
				$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);
				$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$table,$field,$RTEtypeVal);
				if (!$thisConfig["disabled"])	{
					return 1;
				}
			}
		}
	}
}




if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/class.db_layout.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/class.db_layout.inc"]);
}

?>