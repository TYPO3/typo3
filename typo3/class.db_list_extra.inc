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
 * Include file extending recordList which extended t3lib_recordList
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 class localRecordList extends recordList {
 	var $noControlPanels = 0;
	var $showClipboard=0;
	var $clipObj;
	var $CBnames=array();
	var $pageRow=array();
	var $alternateBgColors=0;
	var $allowedNewTables=array();
	var $newWizards=0;
	var $dontShowClipControlPanels=0;
	
	function writeTop($row,$path)	{
		global $LANG;
		
			// Makes the code for the pageicon in the top
		$this->pageRow=$row;
		$this->counter++;
		$iconfile = t3lib_iconWorks::getIcon("pages",$row);
		$alttext = t3lib_BEfunc::getRecordIconAltText($row,"pages");
		$titleCol="test";
		$this->fieldArray = Array($titleCol,"up");
		
		$out="<table border=0 cellpadding=0 cellspacing=0>";

		$theData = Array();
		$theData[$titleCol] = $this->widthGif;

			// Edit		
		$localCalcPerms = $GLOBALS["BE_USER"]->calcPerms($row);
		
		$theData["up"]=array();
		$theCtrlPanel =array();
		
		$theCtrlPanel[]='<a HREF="#" onClick="'.t3lib_BEfunc::viewOnClick($this->id,"",t3lib_BEfunc::BEgetRootLine($this->id)).'"><img src="'.$this->backPath.'gfx/zoom.gif" width="12" height="12" vspace=1 border="0" align=top'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.showPage")).'></a>';

//		debug($localCalcPerms);
		if ($localCalcPerms&2)	{
			if ($this->id)	{
				$params="&edit[pages][".$row["uid"]."]=edit";
				$theCtrlPanel[]='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 vspace=2 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("editPage")).'></a>';
			}
			if (!$GLOBALS["SOBE"]->modTSconfig["properties"]["noCreateRecordsLink"]) 	{
				$theCtrlPanel[]='<a href="#" onClick="return jumpExt(\'db_new.php?id='.$this->id.'\');"><img src="gfx/new_el.gif" width="11" height="12" border="0" align=top'.t3lib_BEfunc::titleAttrib($LANG->getLL("newRecordGeneral"),1).'></a>';
			}

			if ($this->id)	{
				if ($row["hidden"])	{
					$params="&data[pages][".$row["uid"]."][hidden]=0";
					$theCtrlPanel[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_unhide.gif" width=11 height=10 vspace=2 hspace=2 border=0'.t3lib_BEfunc::titleAttrib($LANG->getLL("unHidePage")).' align="top"></a>';
				} else {
					$params="&data[pages][".$row["uid"]."][hidden]=1";
					$theCtrlPanel[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_hide.gif" width=11 height=10 vspace=2 hspace=2 border=0'.t3lib_BEfunc::titleAttrib($LANG->getLL("hidePage")).' align="top"></a>';
				}
			}
			if ($this->id)	{
				$theCtrlPanel[]='<A HREF="#" onClick="return jumpExt(\'move_el.php?table=pages&uid='.$row["uid"].'\');"><img src="'.$this->backPath.'gfx/move_'.($table=="tt_content"?"record":"page").'.gif" width=11 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("move_page")).'></a>';
			}
		}
		if (($localCalcPerms&8) || ($localCalcPerms&16))	{
			$elFromTable = $this->clipObj->elFromTable("");
			if (count($elFromTable))	{
				$theCtrlPanel[]='<a href="'.$this->clipObj->pasteUrl("",$this->id).'" onClick="return '.$this->clipObj->confirmMsg("pages",$this->pageRow,"into",$elFromTable).'"><img src="gfx/clip_pasteafter.gif" width="12" height="12" vspace=2 hspace=2 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("clip_paste")).' align="top"></a>';
			}
		}
		if (count($theCtrlPanel))	{
			$theData["up"][]='<table border=0 cellpadding=0 cellspacing=0 bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"><tr><td>'.implode('</td><td>',$theCtrlPanel).'</td></tr></table>';
		}
		


		$theData["up"][]='<A href="'.$this->listURL().'&clear_cache=1"><img src="'.$this->backPath.'gfx/clear_cache.gif" width=14 hspace=2 height=14 vspace=1 border=0'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.clear_cache")).' align="top"></a>';

		if ($this->table)	{
			$theData["up"][]='<A href="'.$this->listURL().'&csv=1"><img src="'.$this->backPath.'gfx/csv.gif" width=27 hspace=2 height=14 vspace=1 border=0'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.csv")).' align="top"></a>';
		}


		$theData["up"][]='<A href="'.$this->listURL().'"><img src="'.$this->backPath.'gfx/refresh_n.gif" width=14 hspace=2 height=14 vspace=1 border=0'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.reload")).' align="top"></a>';
		if ($row["uid"])	{
			$theIcon = '<img src="'.$this->backPath.$iconfile.'" width=18 height=16 border=0'.t3lib_BEfunc::titleAttrib($alttext).'">';
			$theIcon = $GLOBALS["SOBE"]->doc->wrapClickMenuOnIcon($theIcon,"pages",$this->id);

			$theData[$titleCol].="<BR>".t3lib_div::fixed_lgd_pre($path,$this->fixedL);
			$theData["up"][]='<A href="'.$this->listURL($row["pid"]).'"><img src="'.$this->backPath.'gfx/i/pages_up.gif" width=18 height=16 border=0'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.upOneLevel")).' align="top"></a>';
		} else {	
				// root:0
			$theIcon='<img src="'.$this->backPath.'gfx/i/_icon_website.gif" width=18 height=16 border=0>';
			$theData[$titleCol].="<BR>".t3lib_div::fixed_lgd_pre($GLOBALS["TYPO3_CONF_VARS"]["SYS"]["sitename"],$this->fixedL);
		}

		if ($this->returnUrl)	{
			$theData["up"][]='<A href="'.t3lib_div::linkThisUrl($this->returnUrl,array("id"=>$this->id)).'" class="typo3-goBack"><img src="'.$this->backPath.'gfx/goback.gif" width=14 hspace=2 height=14 vspace=1 border=0'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.goBack")).' align="top"></a>';
		}
		
		$theData["up"]='<table border=0 cellpadding=0 cellspacing=0><tr><td>'.implode("</td><td>",$theData["up"]).'</td></tr></table>';
		$out.=$this->addelement(1,'',$theData,'',$this->leftMargin,$theIcon);
//debug($theData);
		$out.="</table>";
		$this->HTMLcode.=$out;
	}
	function linkClipboardHeaderIcon($string,$table,$cmd,$warning="")	{
		$onClickEvent = 'document.dblistForm.cmd.value=\''.$cmd.'\';document.dblistForm.cmd_table.value=\''.$table.'\';document.dblistForm.submit();';
		if ($warning)	$onClickEvent = 'if (confirm('.$GLOBALS['LANG']->JScharCode($warning).')){'.$onClickEvent.'}';
		return '<a href="#" onClick="'.$onClickEvent.'return false;">'.$string.'</a>';
	}
	function getTable($table,$id,$rowlist)	{
			// Adds the code of a single table
		global $TCA;
		t3lib_div::loadTCA($table);
	//	echo $this->makeQuery($table, $id)."<BR>";

			// Init
		$titleCol = $TCA[$table]["ctrl"]["label"];
		$thumbsCol = $TCA[$table]["ctrl"]["thumbnail"];

		if (!$this->table && !$rowlist)	{
//			$this->addElement_tdParams[$titleCol]=' width="'.($GLOBALS["SOBE"]->MOD_SETTINGS["bigControlPanel"]?"230":"350").'"';
		}

			// Cleaning rowlist for duplicates and place the $titleCol as the first column always!
		$this->fieldArray=array();
		$this->fieldArray[] = $titleCol;
		if (!t3lib_div::inList($rowlist,"_CONTROL_"))	{
			$this->fieldArray[] = "_CONTROL_";
		}
		if ($this->showClipboard)	{
			$this->fieldArray[] = "_CLIPBOARD_";
		}
		if ($this->searchLevels)	{
			$this->fieldArray[]="_PATH_";
		}
		$this->fieldArray=array_unique(array_merge($this->fieldArray,t3lib_div::trimExplode(",",$rowlist,1)));
		if ($this->noControlPanels)	{
			$tempArray = array_flip($this->fieldArray);
			unset($tempArray["_CONTROL_"]);
			unset($tempArray["_CLIPBOARD_"]);
			$this->fieldArray = array_keys($tempArray);
		}
		
			// Select fields:
		$selectFields = $this->fieldArray;
		$selectFields[] = "uid";
		$selectFields[] = "pid";
		if ($thumbsCol)	$selectFields[] = $thumbsCol;	// adding column for thumbnails
		if ($table=="pages")	{
			if (t3lib_extMgm::isLoaded("cms"))	{
				$selectFields[] = "module";
				$selectFields[] = "extendToSubpages";
			}
			$selectFields[] = "doktype";
		}
		if (is_array($TCA[$table]["ctrl"]["enablecolumns"]))	{
			$selectFields = array_merge($selectFields,$TCA[$table]["ctrl"]["enablecolumns"]);
		}
		if ($TCA[$table]["ctrl"]["type"])	{
			$selectFields[] = $TCA[$table]["ctrl"]["type"];
		}
		if ($TCA[$table]["ctrl"]["typeicon_column"])	{
			$selectFields[] = $TCA[$table]["ctrl"]["typeicon_column"];
		}
		if ($TCA[$table]["ctrl"]["label_alt"])	{
			$selectFields = array_merge($selectFields,t3lib_div::trimExplode(",",$TCA[$table]["ctrl"]["label_alt"],1));
		}
		$selectFields = array_unique($selectFields);		// Unique list!
		$selectFields = array_intersect($selectFields,$this->makeFieldList($table,1));		// Making sure that the fields in the field-list ARE in the field-list from TCA!
		$selFieldList = implode(",",$selectFields);		// implode it into a list of fields for the SQL-statement.

			// Query:		
		$query = $this->makeQuery($table, $id,"",$selFieldList);
		$this->setTotalItems();
		$dbCount=0;
		
		if ($this->totalItems)	{
#$pt=t3lib_div::milliseconds();		
			$result = mysql(TYPO3_db,$query);
			if (mysql_error()) {echo mysql_error(); debug($query);}
			$dbCount = mysql_num_rows($result);
#debug(array(t3lib_div::milliseconds()-$pt,$table));
		}
		$out="";
		if ($dbCount)	{
				// Start table:
			$out.='<table border=0 cellpadding=0 cellspacing=0>';

				// half line is drawn
			$theData = Array();
			if (!$this->table && !$rowlist)	{
//				$theData[$titleCol] = $this->widthGif;	// 
				$theData[$titleCol] = '<img src=clear.gif width="'.($GLOBALS["SOBE"]->MOD_SETTINGS["bigControlPanel"]?"230":"350").'" height=1>';
				if (in_array("_CONTROL_",$this->fieldArray))	$theData["_CONTROL_"]="";
				if (in_array("_CLIPBOARD_",$this->fieldArray))	$theData["_CLIPBOARD_"]="";
			}
			$out.=$this->addelement(0,'',$theData,'',$this->leftMargin);

				// header line is drawn
			$theData = Array();
			if ($this->disableSingleTableView)	{
				$theData[$titleCol] = '<b>'.$GLOBALS["LANG"]->sL($TCA[$table]["ctrl"]["title"]).'</b> ('.$this->totalItems.')';
			} else $theData[$titleCol] = '<b>'.$this->linkWrapTable($table,$GLOBALS["LANG"]->sL($TCA[$table]["ctrl"]["title"]).'</b> ('.$this->totalItems.') <img src="'.$this->backPath.'gfx/'.($this->table?"minus":"plus").'bullet_list.gif" width="18" hspace=10 vspace=2 height="12" border="0" align="absmiddle"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL((!$this->table?"expandView":"contractView"))).'>');
			$theUpIcon = ($table=="pages"&&$this->id&&isset($this->pageRow["pid"])) ? '<A href="'.$this->listURL($this->pageRow["pid"]).'"><img src="gfx/i/pages_up.gif" width="18" height="16" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.upOneLevel")).' align="top"></a>':'';

			$out.=$this->addelement(1,$theUpIcon,$theData,' bgcolor="'.$this->headLineCol.'"','');

				// Fixing a order table for sortby tables
			$this->currentTable=array();
			$currentIdList=array();
			$doSort = ($TCA[$table]["ctrl"]["sortby"] && !$this->sortField);
#			if ($this->table || $doSort)	{
				$prevUid=0;
				$prevPrevUid=0;
				while ($row = mysql_fetch_assoc($result))	{
					$currentIdList[] = $row["uid"];
					if ($doSort)	{
						if ($prevUid)	{
							$this->currentTable["prev"][$row["uid"]]=$prevPrevUid;
							$this->currentTable["next"][$prevUid]="-".$row["uid"];
							$this->currentTable["prevUid"][$row["uid"]]=$prevUid;
						}
						$prevPrevUid = isset($this->currentTable["prev"][$row["uid"]]) ? -$prevUid : $row["pid"];
						$prevUid=$row["uid"];
					}
				}
				mysql_data_seek($result,0);
#			}
//			debug($this->currentTable);

				// CSV initiated
			if ($this->csvOutput) $this->initCSV();

				// items
			$iOut="";
			$this->CBnames=array();
			$this->duplicateStack=array();
			$this->eCounter=$this->firstElementNumber;
			$cc=0;
			while ($row = mysql_fetch_assoc($result))	{
				list($flag,$code) = $this->fwd_rwd_nav($table);
				$iOut.=$code;
				if ($flag)	{
					$cc++;
					$row_bgColor=
						$this->alternateBgColors ? 
						(($cc%2)?'' :' bgColor="'.t3lib_div::modifyHTMLColor($GLOBALS["SOBE"]->doc->bgColor4,+10,+10,+10).'"') :
						'';

						// Initialization
					$iconfile = t3lib_iconWorks::getIcon($table,$row);
					$alttext = t3lib_BEfunc::getRecordIconAltText($row,$table);
//					$recTitle = strip_tags($row[$titleCol]);		// commented out 300700
//					$recTitle = htmlspecialchars($row[$titleCol]);
					$recTitle = t3lib_BEfunc::getRecordTitle($table,$row,1);
					
					$this->counter++;

						// The icon with link	
					$theIcon = '<img src="'.$this->backPath.$iconfile.'" width=18 height=16 border=0'.t3lib_BEfunc::titleAttrib($alttext).'>';
					$theIcon = $GLOBALS["SOBE"]->doc->wrapClickMenuOnIcon($theIcon,$table,$row["uid"]);

						// 	Preparing and getting the data-array
					$theData = Array();
					reset($this->fieldArray);
					while(list(,$fCol)=each($this->fieldArray))	{
						if ($fCol==$titleCol)	{
							$theData[$fCol] = $this->linkWrapItems($table,$row["uid"],$recTitle,$row)."&nbsp;";
						} elseif ($fCol=="pid") {
							$theData[$fCol]=$row[$fCol];
						} elseif ($fCol=="_PATH_") {
							$theData[$fCol]="&nbsp;".$this->recPath($row["pid"]);
						} elseif ($fCol=="_CONTROL_") {
							$theData[$fCol]=$this->makeControl($table,$row);
						} elseif ($fCol=="_CLIPBOARD_") {
							$theData[$fCol]=$this->makeClip($table,$row);
						} else {
							$theData[$fCol]="&nbsp;".t3lib_BEfunc::getProcessedValueExtra($table,$fCol,$row[$fCol],100);
						}
					}

					if ($this->csvOutput) $this->addToCSV($row);
					$iOut.=$this->addelement(1,$theIcon,$theData,$row_bgColor);
					
						// Thumbsnails?
					if ($this->thumbs && trim($row[$thumbsCol]))	{
						$iOut.=$this->addelement(4,"", Array($titleCol=>$this->thumbCode($row,$table,$thumbsCol)),$row_bgColor);
					}
				}
				$this->eCounter++;	
			}

				// field header line is drawn:
			$theData = Array();
			reset($this->fieldArray);
			while(list(,$fCol)=each($this->fieldArray))	{
				$permsEdit = $this->calcPerms&($table=="pages"?2:16);
				if ($fCol=="_PATH_")	{
					$theData[$fCol]="&nbsp;<i>[".$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels._PATH_")."]</i>&nbsp;";
				} elseif ($fCol=="_CLIPBOARD_") {
					$cells=array();
					$elFromTable = $this->clipObj->elFromTable($table);
					if (count($elFromTable))	{
						$cells[]='<a href="'.$this->clipObj->pasteUrl($table,$this->id).'" onClick="return '.$this->clipObj->confirmMsg("pages",$this->pageRow,"into",$elFromTable).'"><img src="gfx/clip_pasteafter.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_paste")).'></a><img src=clear.gif width=2 height=1>';
					}
					if ($this->clipObj->current!="normal")	{
						$cells[]=$this->linkClipboardHeaderIcon('<img src="gfx/clip_copy.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_selectMarked")).'>',$table,"setCB");

						$editIdList = implode(",",$currentIdList);
#debug(array($editIdList,$table));
						$editIdList = "'+editList('".$table."','".$editIdList."')+'";
						$params="&edit[".$table."][".$editIdList."]=edit&disHelp=1";
						$cells[]='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="gfx/edit2.gif" width="11" height="12" hspace=2 border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_editMarked")).'></a>';

						$cells[]=$this->linkClipboardHeaderIcon('<img src="gfx/garbage.gif" width="11" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_deleteMarked")).'>',$table,"delete",sprintf($GLOBALS["LANG"]->getLL("clip_deleteMarkedWarning"),$GLOBALS["LANG"]->sL($TCA[$table]["ctrl"]["title"])));
						$cells[]='<img src=clear.gif width=6 height=1>';
						$cells[]='<a href="#" onClick="checkOffCB(\''.implode(",",$this->CBnames).'\'); return false;"><img src="gfx/clip_select.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_markRecords")).'></a>';
					} else {
						$cells[]="&nbsp;";
					}
					$theData[$fCol]=implode("",$cells);
				} elseif ($fCol=="_CONTROL_") {
					if (!$TCA[$table]["ctrl"]["readOnly"])	{
						if ($this->calcPerms&($table=="pages"?8:16) && $this->showNewRecLink($table))	{
							if ($table=="tt_content" && $this->newWizards)	{
								$theData[$fCol]='<A HREF="#" onClick="return jumpExt(\'db_new_content_el.php?id='.$this->id.'\');"><img src="'.$this->backPath.'gfx/new_'.($table=="pages"?"page":"el").'.gif" width='.($table=="pages"?13:11).' height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("new")).'></a>';
							} elseif ($table=="pages" && $this->newWizards)	{
								$theData[$fCol]='<A HREF="db_new.php?id='.$this->id.'&pagesOnly=1returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'"><img src="'.$this->backPath.'gfx/new_'.($table=="pages"?"page":"el").'.gif" width='.($table=="pages"?13:11).' height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("new")).'></a>';
							} else {
								$params="&edit[".$table."][".$this->id."]=new";
								$theData[$fCol]='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/new_'.($table=="pages"?"page":"el").'.gif" width='.($table=="pages"?13:11).' height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("new")).'></a>';
							}
						}
						if ($permsEdit && $this->table && is_array($currentIdList))	{
							$editIdList = implode(",",$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params="&edit[".$table."][".$editIdList."]=edit&columnsOnly=".implode(",",$this->fieldArray)."&disHelp=1";
							$theData[$fCol].='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 vspace=2 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("editShownColumns")).'></a>';
						}
					}
				} else {
					$theData[$fCol]="";
					$theData[$fCol].='&nbsp;';
					if ($this->table && is_array($currentIdList))	{
						if ($this->clipNumPane()) $theData[$fCol].='<A HREF="'.$this->listURL("",-1).'&duplicateField='.$fCol.'"><img src="gfx/select_duplicates.gif" width="11" height="11" vspace=2 border="0" align=top'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_duplicates")).'></a>';
						if (!$TCA[$table]["ctrl"]["readOnly"] && $permsEdit && $TCA[$table]["columns"][$fCol])	{
							$editIdList = implode(",",$currentIdList);
							if ($this->clipNumPane()) $editIdList = "'+editList('".$table."','".$editIdList."')+'";
							$params="&edit[".$table."][".$editIdList."]=edit&columnsOnly=".$fCol."&disHelp=1";
							$theData[$fCol].='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/edit2.gif" width=11 height=12 vspace=2 border=0 align="top"'.t3lib_BEfunc::titleAttrib(sprintf($GLOBALS["LANG"]->getLL("editThisColumn"),ereg_replace(":$","",trim($GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel($table,$fCol)))))).'></a>';
						}
					} else {
//						$theData[$fCol].='&nbsp;';
					}
					$theData[$fCol].=$this->addSortLink($GLOBALS["LANG"]->sL(t3lib_BEfunc::getItemLabel($table,$fCol,"&nbsp;<i>[|]</i>&nbsp;")),$fCol,$table);;
				}
			}
			$out.=$this->addelement(1,'',$theData,' bgcolor="'.$this->subHeadLineCol.'"','');
				// finish
			$out.=$iOut;
			$out.="</table>";	


			
				// Output csv if...
			if ($this->csvOutput)	$this->outputCSV($table);	// This ends the page with exit.
		}
		return $out;
	}
	function clipNumPane()	{
		return in_Array("_CLIPBOARD_",$this->fieldArray) && $this->clipObj->current!="normal";
	}
	function addSortLink($code,$field,$table)	{
		if ($field=="_CONTROL_")	return $code;
		if ($field=="_CLIPBOARD_")	return $code;
		if ($this->disableSingleTableView)	return $code;
		if ($field=="_PATH_")	$field=pid;
		return '<A HREF="'.$this->listURL("",-1,"sortField,sortRev,table").'&table='.$table.'&sortField='.$field.'&sortRev='.($this->sortRev || ($this->sortField!=$field)?0:1).'">'.$code.
		($this->sortField==$field?'<img src="gfx/red'.($this->sortRev?"up":"down").'.gif" hspace=2 width="7" height="4" border="0">':'').
		'</a>';
	}
	function makeControl($table,$row)	{
		global $TCA, $LANG;
		if ($this->dontShowClipControlPanels)	return "";

		t3lib_div::loadTCA($table);
		$cells=array();

		if ($table=="pages")	{
			$localCalcPerms = $GLOBALS["BE_USER"]->calcPerms(t3lib_BEfunc::getRecord("pages",$row["uid"]));
		}
		$permsEdit = ($table=="pages" && ($localCalcPerms&2)) || ($table!="pages" && ($this->calcPerms&16));
		
		
			// Show (only pages and tt_content elements)
		if ($table=="pages" || $table=="tt_content")	{
			$params="&edit[".$table."][".$row["uid"]."]=edit";
			$cells[]='<a href="#" onClick="'.t3lib_BEfunc::viewOnClick($table=="tt_content"?$this->id."#".$row["uid"]:$row["uid"]).'"><img src="'.$this->backPath.'gfx/zoom.gif" width=12 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.showPage")).'></a>';
		}
		
			// Edit: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
		if ($permsEdit)	{
			$params="&edit[".$table."][".$row["uid"]."]=edit";
			$cells[]='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/edit2'.(!$TCA[$table]["ctrl"]["readOnly"]?"":"_d").'.gif" width=11 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("edit")).'></a>';
		}
		
		if (($table=="tt_content" && $permsEdit) || ($table=="pages"))	{
			$cells[]='<A HREF="#" onClick="return jumpExt(\'move_el.php?table='.$table.'&uid='.$row["uid"].'\');"><img src="'.$this->backPath.'gfx/move_'.($table=="tt_content"?"record":"page").'.gif" width=11 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("move_".($table=="tt_content"?"record":"page"))).'></a>';
		}
		
		if ($GLOBALS["SOBE"]->MOD_SETTINGS["bigControlPanel"] || $this->table)	{
				// Info: (All records)
			$cells[]='<A HREF="#" onClick="top.launchView(\''.$table.'\', \''.$row["uid"].'\'); return false;"><img src="'.$this->backPath.'gfx/zoom2.gif" width=12 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("showInfo")).'></a>';
	
			if (!$TCA[$table]["ctrl"]["readOnly"])	{
					// Revert
				$cells[]='<A HREF="#" onClick="return jumpExt(\'show_rechis.php?element='.rawurlencode($table.":".$row["uid"]).'\',\'#latest\');"><img src="'.$this->backPath.'gfx/history2.gif"  width="13" height="12" border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("history")).' align="top"></a>';

					// Perms
				if ($table=="pages" && $GLOBALS["BE_USER"]->check("modules","web_perm"))	{
					$cells[]='<A HREF="mod/web/perm/index.php?id='.$row["uid"].'&return_id='.$row["uid"].'&edit=1"><img src="'.$this->backPath.'gfx/perm.gif" width="7" hspace=2 height="12" border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("permissions")).' align="top"></a>';
				}
	
					// New :
				if ($TCA[$table]["ctrl"]["sortby"] || $TCA[$table]["ctrl"]["useColumnsForDefaultValues"])	{	// ONLY if the records in the table are sorted by a "sortby"-row
					if (
						($table!="pages" && ($this->calcPerms&16)) || 	// For NON-pages, must have permission to edit content on this parent page
						($table=="pages" && ($this->calcPerms&8))		// For pages, must have permission to create new pages here.
						)	{	
						if ($this->showNewRecLink($table))	{
							$params="&edit[".$table."][".(-$row["uid"])."]=new";
							$cells[]='<A HREF="#" onClick="'.t3lib_BEfunc::editOnClick($params,"",-1).'"><img src="'.$this->backPath.'gfx/new_'.($table=="pages"?"page":"el").'.gif" width='.($table=="pages"?13:11).' height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("new".($table=="pages"?"Page":"Record"))).'></a>';
						}
					} 
				}

					// Up/Down
				if ($permsEdit && $TCA[$table]["ctrl"]["sortby"]  && !$this->sortField && !$this->searchLevels)	{	//
					if (isset($this->currentTable["prev"][$row["uid"]]))	{	// Up
						$params="&cmd[".$table."][".$row["uid"]."][move]=".$this->currentTable["prev"][$row["uid"]];
						$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_up.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("moveUp")).' align="top"></a>';
					} else {
						$cells[]='<img src="clear.gif" width=11 height=10 align="top">';
					}
					if ($this->currentTable["next"][$row["uid"]])	{	// Down
						$params="&cmd[".$table."][".$row["uid"]."][move]=".$this->currentTable["next"][$row["uid"]];
						$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_down.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("moveDown")).' align="top"></a>';
					} else {
						$cells[]='<img src="clear.gif" width=11 height=10 align="top">';
					}
				}
		
					// Hide
				$hiddenField = $TCA[$table]["ctrl"]["enablecolumns"]["disabled"];
				if ($permsEdit && $hiddenField && $TCA[$table]["columns"][$hiddenField] && (!$TCA[$table]["columns"][$hiddenField]["exclude"] || $GLOBALS["BE_USER"]->check("non_exclude_fields",$table.":".$hiddenField)))	{
					if ($row[$hiddenField])	{
						$params="&data[".$table."][".$row["uid"]."][".$hiddenField."]=0";
						$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_unhide.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("unHide".($table=="pages"?"Page":""))).' align="top"></a>';
					} else {
						$params="&data[".$table."][".$row["uid"]."][".$hiddenField."]=1";
						$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_hide.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("hide".($table=="pages"?"Page":""))).' align="top"></a>';
					}
				}
		
					// Delete
				if (
					($table=="pages" && ($localCalcPerms&4)) || ($table!="pages" && ($this->calcPerms&16))
					)	{
					$params="&cmd[".$table."][".$row["uid"]."][delete]=1";
					$cells[]='<A HREF="#" onClick="if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL("deleteWarning")).')) {jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');} return false;"><img src="'.$this->backPath.'gfx/garbage.gif" width=11 height=12 border=0 align="top"'.t3lib_BEfunc::titleAttrib($LANG->getLL("delete")).'></a>';
				}
		
					// Levels
				if ($permsEdit && $table=="pages" && !$this->searchLevels)	{
					if ($this->calcPerms&8)	{
						$params="&cmd[".$table."][".$row["uid"]."][move]=".-$this->id;
						$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_left.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("prevLevel")).' align="top"></a>';
					}
						// Down
					if ($this->currentTable["prevUid"][$row["uid"]])	{
						$localCalcPerms = $GLOBALS["BE_USER"]->calcPerms(t3lib_BEfunc::getRecord("pages",$this->currentTable["prevUid"][$row["uid"]]));
						if ($localCalcPerms&8)	{
							$params="&cmd[".$table."][".$row["uid"]."][move]=".$this->currentTable["prevUid"][$row["uid"]];
							$cells[]='<A HREF="#" onClick="return jumpToUrl(\''.$GLOBALS["SOBE"]->doc->issueCommand($params,-1).'\');"><img src="'.$this->backPath.'gfx/button_right.gif" width=11 height=10 border=0'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("nextLevel")).' align="top"></a>';
						} else {
							$cells[]='<img src="clear.gif" width=11 height=10 align="top">';
						}
					} else {
						$cells[]='<img src="clear.gif" width=11 height=10 align="top">';
					}
				}
			}
		}
		
		if ($lockInfo=t3lib_BEfunc::isRecordLocked($table,$row["uid"]))	{
//			debug($lockInfo);
			$cells[]='<a href="#" onClick="alert('.$GLOBALS['LANG']->JScharCode($lockInfo["msg"]).');return false;"><img src="gfx/recordlock_warning3.gif" width="17" height="12" border="0"'.t3lib_BEfunc::titleAttrib($lockInfo["msg"]).'></a>';
		}
		
		return '<table border=0 cellpadding=1 cellspacing=0 bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"><tr><td>'.implode("</td><td>",$cells).'</td></tr></table>';
	}
	function makeClip($table,$row)	{
		global $TCA;
		if ($this->dontShowClipControlPanels)	return "";
		$cells=array();

		if ($this->clipObj->current=="normal")	{
			$isSel = (string)$this->clipObj->isSelected($table,$row["uid"]);
			$cells[]='<a href="#" onClick="return jumpSelf(\''.$this->clipObj->selUrlDB($table,$row["uid"],1,($isSel=="copy"),array("returnUrl"=>"")).'\');"><img src="gfx/clip_copy'.($isSel=="copy"?"_h":"").'.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:cm.copy")).'></a>';
			$cells[]='<a href="#" onClick="return jumpSelf(\''.$this->clipObj->selUrlDB($table,$row["uid"],0,($isSel=="cut"),array("returnUrl"=>"")).'\');"><img src="gfx/clip_cut'.($isSel=="cut"?"_h":"").'.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:cm.cut")).'></a>';
		} else {
			$n=$table."|".$row["uid"];
			$this->CBnames[]=$n;

			$checked = ($this->clipObj->isSelected($table,$row["uid"])?" checked":"");

			$duplField = t3lib_div::GPvar("duplicateField");
			if ($duplField && isset($row[$duplField]))	{
				$checked="";
				if (in_array($row[$duplField], $this->duplicateStack))	{
					$checked=" checked";
				}
				$this->duplicateStack[] = $row[$duplField];
			}
			
			$cells[]='<input type="hidden" name="CBH['.$n.']" value="0"><input type="checkbox" name="CBC['.$n.']" value="1" style="width:12;height:12;margin:0 0 0 0;"'.$checked.'>';
			$cells[]='<img src=clear.gif width=10 height=1>';
		}
		
		$elFromTable = $this->clipObj->elFromTable($table);
		if (count($elFromTable) && $TCA[$table]["ctrl"]["sortby"])	{
			$cells[]='<a href="'.$this->clipObj->pasteUrl($table,-$row["uid"]).'" onClick="return '.$this->clipObj->confirmMsg($table,$row,"after",$elFromTable).'"><img src="gfx/clip_pasteafter.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_pasteAfter")).'></a>';
		}
		$elFromTable = $this->clipObj->elFromTable("");
		if ($table=="pages" && $elFromTable)	{
			$cells[]='<a href="'.$this->clipObj->pasteUrl("",$row["uid"]).'" onClick="return '.$this->clipObj->confirmMsg($table,$row,"into",$elFromTable).'"><img src="gfx/clip_pasteinto.gif" width="12" height="12" border="0"'.t3lib_BEfunc::titleAttrib($GLOBALS["LANG"]->getLL("clip_pasteInto")).'></a>';
		}

		if ($GLOBALS["CLIENT"]["BROWSER"]=="net" && $GLOBALS["CLIENT"]["VERSION"]<5)	{
			$w100 = '';
			$w40 = '';
		} else {
			$w100 = ' width="100%"';
			$w40 = ' width="40%"';
		}
		return '<table border=0 cellpadding=1 cellspacing=0 bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'"'.$w100.'><tr><td'.$w40.'>&nbsp;</td><td align=center>'.implode("</td><td>",$cells).'</td><td'.$w40.'>&nbsp;</td></tr></table>';
	}
	function fieldSelectBox($table,$formFields=1)	{
		global $TCA;
		t3lib_div::loadTCA($table);
		$formElements=array("","");
		if ($formFields)	{
			$formElements=array('<form action="'.$this->listURL().'" method="POST">','</form>');
		}

		$setFields=is_array($this->setFields[$table]) ? $this->setFields[$table] : array();
			// Make level selector:
		$fields = $this->makeFieldList($table);
		$fields[]="_PATH_";
		$fields[]="_CONTROL_";
		$fields[]="_CLIPBOARD_";
		$opt=array();
		reset($fields);
		$opt[] = '<option value=""></option>';
		while(list(,$fN)=each($fields))	{
			$fL = is_array($TCA[$table]["columns"][$fN]) ? ereg_replace(":$","",$GLOBALS["LANG"]->sL($TCA[$table]["columns"][$fN]["label"])) : "[".$fN."]";
			$opt[] = '<option value="'.$fN.'"'.(in_array($fN,$setFields)?" selected":"").'>'.htmlspecialchars($fL).'</option>';
		}
		$lMenu = '<select size='.t3lib_div::intInRange(count($fields)+1,3,20).' multiple name="displayFields['.$table.'][]">'.implode("",$opt).'</select>';
		
			// Table with the search box:
		$content.= '
		<table border=0 cellpadding=1 cellspacing=0>
		'.$formElements[0].'
			<tr>
				<td><img src=clear.gif width='.$this->spaceSearchBoxFromLeft.' height=1></td>
				<td bgcolor="#9BA1A8">
					<table border=0 cellpadding=0 cellspacing=0 bgcolor="'.$GLOBALS["TBE_TEMPLATE"]->bgColor4.'">
					<tr>
						<td>'.$lMenu.'</td>
						<td><input type="Submit" name="search" value="'.$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.setFields").'"></td>
					</tr>
					</table>			
				</td>
			</tr>'.$formElements[1].'
		</table>
		';
		return $content;
	}
	function initCSV()	{
		$this->csvLines=array();
		
		reset($this->fieldArray);
		$csvRow=array();
		while(list(,$fN)=each($this->fieldArray))	{
			$csvRow[]=$fN;
		}
		$this->setCsvRow($csvRow);
		$this->csvLines[]="";
	}
	function addToCSV($row)	{
		reset($this->fieldArray);
		$csvRow=array();
		while(list(,$fN)=each($this->fieldArray))	{
			if ($fN=="_PATH_")	{
				$csvRow[]=$this->recPath($row["pid"]);
			} else {
				$csvRow[]=$row[$fN];
			}
		}
		$this->setCsvRow($csvRow);
	}
	function setCsvRow($csvRow)	{
		$this->csvLines[] = t3lib_div::csvValues($csvRow);
	}
	function outputCSV($prefix)	{
		$filename=$prefix."_".date("dmy-Hi").".csv";
		$mimeType = "application/octet-stream";
		Header("Content-Type: ".$mimeType);
		Header("Content-Disposition: attachment; filename=".$filename);
		echo implode(chr(13).chr(10),$this->csvLines);
		exit;
	}
	function recPath($pid)	{
		if (!isset($this->recPath_cache[$pid]))	{
			$this->recPath_cache[$pid] = t3lib_BEfunc::getRecordPath ($pid,$this->perms_clause,20);
		}
		return $this->recPath_cache[$pid];
	}
	function showNewRecLink($table)	{
		return !count($this->allowedNewTables) || in_array($table,$this->allowedNewTables);
	}
	function makeReturnUrl()	{
		return '&returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"));
	}
}

 

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/class.db_list_extra.inc"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/class.db_list_extra.inc"]);
}

?>