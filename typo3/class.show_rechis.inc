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
 * Class for the record history display script (show_rechis.php)
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
 *   77: class recordHistory 
 *   88:     function getChangesSinceRecord($element,$hisUid=0,$hisUid_Stop=0)	
 *  133:     function nextHisUid($element,$hisUid)	
 *  167:     function compareChangesWithCurrent($element,$changeRec,$discardFileLists=0)	
 *  203:     function readFieldTypes($table,$id)	
 *  226:     function cmp($changeStatus,$oldRecord)	
 *  245:     function revertToPreviousValues($element,$field)	
 *  298:     function saveState($element,$sumUp)	
 *  343:     function removeFilefields($table,$dataArray)	
 *  363:     function displayHistory($element)	
 *  487:     function renderEntry($entry,$table)	
 *  537:     function displaySysHistoryEntry($sh_uid)	
 *  573:     function listHeader()	
 *  613:     function linkPage($str,$inparams=array(),$anchor="")	
 *
 * TOTAL FUNCTIONS: 13
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * Class for the record history display script (show_rechis.php)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class recordHistory {
	var $listType = 0;
	var $maxSteps=20;
	
	
	/**
	 * @param	[type]		$element: ...
	 * @param	[type]		$hisUid: ...
	 * @param	[type]		$hisUid_Stop: ...
	 * @return	[type]		...
	 */
	function getChangesSinceRecord($element,$hisUid=0,$hisUid_Stop=0)	{
		global $TCA;

		$sumChangesArray=array();
		$sumChangesArray["changes"]=array();
		$elParts = explode(":",$element);
		if ($TCA[$elParts[0]])	{
			$addWhere = " AND sys_history.uid>=".$hisUid;
			if ($hisUid_Stop)	$addWhere.= " AND sys_history.uid<=".$hisUid_Stop;
			$query="SELECT sys_history.*,sys_log.userid FROM sys_history,sys_log WHERE 
				sys_history.sys_log_uid=sys_log.uid
				AND sys_history.tablename='".$elParts[0]."' 
				AND sys_history.recuid=".intval($elParts[1]).$addWhere." 
				ORDER BY uid DESC";

			$res = mysql(TYPO3_db,$query);
			$times=array();
			while ($newRow = mysql_fetch_assoc($res))	{
				$hisDat = unserialize($newRow["history_data"]);
				if (is_array($hisDat["newRecord"]) && is_array($hisDat["oldRecord"]))	{
//					if ($newRow["uid"]!=$hisUid)	{	// Overlay the old-record values... 
//debug($hisDat["oldRecord"]);
						$sumChangesArray["changes"]=array_merge($sumChangesArray["changes"],$hisDat["oldRecord"]);
	//				} else {	// ... unless the record is the one we're waiting for..
		//				$sumChangesArray["changes"]=array_merge($sumChangesArray["changes"],$hisDat["newRecord"]);
			//		}
					$sumChangesArray["counter"]++;
					$sumChangesArray["tstamp"][]=$newRow["tstamp"];
					$sumChangesArray["userList"][]=$newRow["userid"];
				} else {
					debug("ERROR!");
				}
				
			}
		}	
		return $sumChangesArray;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$element: ...
	 * @param	[type]		$hisUid: ...
	 * @return	[type]		...
	 */
	function nextHisUid($element,$hisUid)	{
		$elParts = explode(":",$element);
		// Prev:
			$query="SELECT uid FROM sys_history WHERE 
				tablename='".$elParts[0]."' 
				AND recuid=".intval($elParts[1])." 
				AND uid<".intval($hisUid)."
				ORDER BY uid DESC LIMIT 1";
			$res = mysql(TYPO3_db,$query);
			if ($row = mysql_fetch_assoc($res))	{
				$prevUid=$row["uid"];
			}
		// Next		
			$query="SELECT uid FROM sys_history WHERE 
				tablename='".$elParts[0]."' 
				AND recuid=".intval($elParts[1])." 
				AND uid>".intval($hisUid)."
				ORDER BY uid LIMIT 1";
			$res = mysql(TYPO3_db,$query);
			if ($row = mysql_fetch_assoc($res))	{
				$nextUid=$row["uid"];
			}

		return array($prevUid,$nextUid);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$element: ...
	 * @param	[type]		$changeRec: ...
	 * @param	[type]		$discardFileLists: ...
	 * @return	[type]		...
	 */
	function compareChangesWithCurrent($element,$changeRec,$discardFileLists=0)	{
		global $TCA;

		$sumChangesArray=array();
		$elParts = explode(":",$element);
		$newChangeRec=array();
		if ($TCA[$elParts[0]])	{
			$currentRecord = t3lib_BEfunc::getRecord($elParts[0],$elParts[1]);
							// list($currentRecord,$DB_fieldTypes) = $this->readFieldTypes($elParts[0],$elParts[1]);
						// || ($cRecTypes[$col]=="int" && $currentRecord[$col]==0 && !strcmp($val,"")
			if (is_array($currentRecord) && is_array($changeRec["changes"]))	{
				reset($changeRec["changes"]);
				while(list($fN,$fV)=each($changeRec["changes"]))	{
					if (strcmp($fV,$currentRecord[$fN]))	{
						$newChangeRec["oldRecord"][$fN]=$fV;
						$newChangeRec["newRecord"][$fN]=$currentRecord[$fN];
					}
				}
				$newChangeRec["tstamp"]=min($changeRec["tstamp"]);
				$newChangeRec["counter"]=$changeRec["counter"];
				$newChangeRec["userList"]=array_unique($changeRec["userList"]);
			} else {
				return false;	// No arrays, possibly no record
			}
		}	
		
		return $newChangeRec;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function readFieldTypes($table,$id)	{
		$res = mysql(TYPO3_db,"SELECT * FROM $table WHERE uid=".intval($id));
			// Fetch the types of the fields.
		if (mysql_num_rows($res))	{
			$currentRecord = mysql_fetch_assoc($res);
			$c=0;
			reset($currentRecord);
			$cRecTypes=array();
			while (list($col,$val)=each($currentRecord))	{
				$cRecTypes[$col]=mysql_field_type($res,$c);
				$c++;
			}
		}
		return array($currentRecord,$cRecTypes);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$changeStatus: ...
	 * @param	[type]		$oldRecord: ...
	 * @return	[type]		...
	 */
	function cmp($changeStatus,$oldRecord)	{
		reset($oldRecord);
		$changes=array();
		while(list($fN,$fV)=each($oldRecord))	{
			if (isset($changeStatus[$fN]) && strcmp($fV,$changeStatus[$fN]))	{
				$changes["oldRecord"][$fN]=$changeStatus[$fN];
				$changes["newRecord"][$fN]=$fV;
			}
		}
		return $changes;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$element: ...
	 * @param	[type]		$field: ...
	 * @return	[type]		...
	 */
	function revertToPreviousValues($element,$field)	{
		$sumUp = t3lib_div::GPvar("sumUp");

		$elParts = explode(":",$element);
		$redirect=intval(t3lib_div::GPvar("doReturn"));
		if ($sumUp==-1)	{	// Undo/Redo
			$query="SELECT uid FROM sys_history WHERE 
				sys_history.tablename='".$elParts[0]."' 
				AND sys_history.recuid=".intval($elParts[1])." 
				ORDER BY uid DESC LIMIT 1";
			$res = mysql(TYPO3_db,$query);
			if ($row = mysql_fetch_assoc($res))	{
				$sumUp=$row["uid"];
			}
			$redirect=1;
		}
		if ($sumUp!=-1)	{
			$changeRec=$this->compareChangesWithCurrent($element,$this->getChangesSinceRecord($element,$sumUp));

			$data =array();
			if (t3lib_BEfunc::getRecord($elParts[0],$elParts[1]))	{
				if ($field=="ALL_FIELDS")	{
					$data=$changeRec["oldRecord"];
				} elseif(substr($field,0,6)=="field:") {
					$data[substr($field,6)]=$changeRec["oldRecord"][substr($field,6)];
				}
				$data = $this->removeFilefields($elParts[0],$data);
				if (count($data))	{
					$inData=array();
					$inData[$elParts[0]][$elParts[1]]=$data;
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->debug=0;
					$tce->dontProcessTransformations=1;
					$tce->start($inData,array());
					$tce->process_datamap();
				}
			}
		}

		if ($redirect)	{
			Header ("Location: ".t3lib_div::locationHeaderUrl(t3lib_div::GPvar("returnUrl")));
			exit;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$element: ...
	 * @param	[type]		$sumUp: ...
	 * @return	[type]		...
	 */
	function saveState($element,$sumUp)	{
		$elParts = explode(":",$element);

		$changeRec = $this->getChangesSinceRecord($element,$sumUp);

		$query="SELECT history_data FROM sys_history WHERE 
			sys_history.tablename='".$elParts[0]."' 
			AND sys_history.recuid=".intval($elParts[1])." 
			ORDER BY uid DESC LIMIT 1";
		$res = mysql(TYPO3_db,$query);
		if ($row = mysql_fetch_assoc($res))	{
			$lastestData = unserialize($row["history_data"]);
//			debug($lastestData);
		}


		$historyRecords=array();
		$historyRecords["oldRecord"] = $changeRec["changes"];
		$historyRecords["newRecord"] = array();
		reset($historyRecords["oldRecord"]);
		while(list($kk)=each($historyRecords["oldRecord"]))	{
			$historyRecords["newRecord"][$kk]=$lastestData["newRecord"][$kk];
		}

		$updateID = $GLOBALS["BE_USER"]->writelog(3,1,0,1,"Saved state","");
		
		$fields_values=array();
		$fields_values["history_data"]=serialize($historyRecords);
		$fields_values["fieldlist"]=implode(",",array_keys($historyRecords["oldRecord"]));
		$fields_values["tstamp"]=time();
		$fields_values["tablename"]=$elParts[0];
		$fields_values["recuid"]=$elParts[1];
		$fields_values["sys_log_uid"]=$updateID;
		$fields_values["snapshot"]=1;
		$query = t3lib_BEfunc::DBcompileInsert("sys_history",$fields_values);
		$res = mysql(TYPO3_db,$query);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$dataArray: ...
	 * @return	[type]		...
	 */
	function removeFilefields($table,$dataArray)	{
		global $TCA;
		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			reset($TCA[$table]["columns"]);
			while(list($field,$config)=each($TCA[$table]["columns"]))	{
				if ($config["config"]["type"]=="group" && $config["config"]["internal_type"]=="file")	{
					unset($dataArray[$field]);
				}
			}
		}
		return $dataArray;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$element: ...
	 * @return	[type]		...
	 */
	function displayHistory($element)	{
		global $SOBE, $LANG, $TCA;
		
		$elParts = explode(":",$element);
		$table = $elParts[0];
		if ($TCA[$table])	{
			$mainQBody = "FROM sys_history,sys_log WHERE 
				sys_history.sys_log_uid=sys_log.uid
				AND sys_history.tablename='".$table."' 
				AND sys_history.recuid=".intval($elParts[1]);
			$query="SELECT count(*) ".$mainQBody; 
			$res = mysql(TYPO3_db,$query);
			list($Rcount)=mysql_fetch_row($res);

			$max = $this->maxSteps;	// How many steps back...
			$query="SELECT sys_history.*,sys_log.userid ".$mainQBody." 
				ORDER BY uid LIMIT ".t3lib_div::intInRange($Rcount-$max,0).",".$max;
			$res = mysql(TYPO3_db,$query);

			$changesArray=array();
			$changeLog=array();
			while ($newRow = mysql_fetch_assoc($res))	{
				$hisDat = unserialize($newRow["history_data"]);
				if (is_array($hisDat["newRecord"]) && is_array($hisDat["oldRecord"]))	{
						// If intermedia changes:
					$intermediaChanges = $this->cmp($changesArray,$hisDat["oldRecord"]);
					if (count($intermediaChanges) && !$newRow["snapshot"])	{
						$changeLog[]=$intermediaChanges;
					}

						// Add hisDat to the changeLog
					$hisDat["uid"]=$newRow["uid"];
					$hisDat["tstamp"]=$newRow["tstamp"];
					$hisDat["user"]=$newRow["userid"];
					$hisDat["snapshot"]=$newRow["snapshot"];
					$changeLog[]=$hisDat;

						// Update change array
						// this is used to detect if any intermedia changes has been made.
					$changesArray = array_merge($changesArray,$hisDat["newRecord"]);
				} else {
					debug("ERROR: [displayHistory]");
				}
			}
			
			$lines=array();
			$darkerBgColor = t3lib_div::modifyHTMLcolor($SOBE->doc->bgColor4,-30,-30,-30);
			$darkerBgColor_interM = "#cccccc";
			if (t3lib_div::GPvar("sumUp"))	{
				
				$changeLog=array();
				$changeLog[]=$this->compareChangesWithCurrent($element,$this->getChangesSinceRecord($element,t3lib_div::GPvar("sumUp")));
				$this->listType=2;
				$lines[]=$this->listHeader();
				$be_users = t3lib_BEfunc::getUserNames();
				
				list($prevHisUid,$nextHisUid) = $this->nextHisUid($element,t3lib_div::GPvar("sumUp"));
				
				$linkPack=	($prevHisUid ? $this->linkPage('<img src="gfx/pilup.gif" width="14" height="14" hspace=1 border="0" title="'.$LANG->getLL("prev").'">', array("sumUp"=>$prevHisUid)) : "").
				($nextHisUid ? $this->linkPage('<img src="gfx/pildown.gif" width="14" height="14" hspace=1 border="0" title="'.$LANG->getLL("next").'">', array("sumUp"=>$nextHisUid)) : "").
				'<BR>'.$this->linkPage('<img src="gfx/history2.gif" width="13" height="12" hspace=1 vspace=3 border="0" title="'.$LANG->getLL("historyList").'">', array("sumUp"=>""), "uid_".t3lib_div::GPvar("sumUp")).
				
				$this->linkPage('<img src="gfx/savesnapshot.gif" width="17" height="12" hspace=1 vspace=3 border="0" title="'.$LANG->getLL("saveState").'">', array("saveState"=>t3lib_div::GPvar("sumUp"),"sumUp"=>""), "latest");
				
				reset($changeLog);
				while(list(,$entry)=each($changeLog))	{
					if (!is_array($entry["userList"]))	$entry["userList"]=array();
					reset($entry["userList"]);
					while(list($uLk,$uV)=each($entry["userList"]))	{
						$entry["userList"][$uLk]=$be_users[$uV]["username"];
					}
					
					$lines[]='<tr bgColor="'.$darkerBgColor.'">
						<td valign=top>'.$linkPack.'</td>
						<td colspan=4><B>'.$LANG->getLL("time").':</B> '.t3lib_BEfunc::datetime($entry["tstamp"]).', '.t3lib_BEfunc::calcAge(time()-$entry["tstamp"],$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears")).' &nbsp; - &nbsp; <B>'.$LANG->getLL("changeCount").':</B> '.$entry["counter"].'<BR>
							<B>'.$LANG->getLL("users").':</B> '.implode(", ",$entry["userList"]).' 
						</td>
					</tr>';
					if (isset($entry["oldRecord"]) && isset($entry["newRecord"]))	{
						$lines = array_merge($lines,$this->renderEntry($entry,$table));
					} else {
						$lines[]='<tr bgColor="'.$SOBE->doc->bgColor4.'"><td colspan=5 align=center><BR><B>'.$LANG->getLL("similar").'</B><BR><BR></td></tr>';
					}
				}
			} else {			
					// Change Log:
				$this->listType=1;
				$be_users = t3lib_BEfunc::getUserNames();
				$lines[]=$this->listHeader();
				reset($changeLog);
				
				while(list($c,$entry)=each($changeLog))	{
					$lines[]='<tr><td colspan=3>&nbsp;</td></tr>';
					$lastAnchor = ($c+1==count($changeLog)?'<a name="latest"></a>':"");
					if ($entry["uid"])	{
						$theTime = $this->linkPage(t3lib_BEfunc::datetime($entry["tstamp"]),array("sh_uid"=>$entry["uid"],"element"=>""));
						$lines[]='<tr bgColor="'.($entry["snapshot"]?$SOBE->doc->bgColor2:$darkerBgColor).'">
							<td colspan=2>'.$lastAnchor.'<a name="uid_'.$entry["uid"].'"></a>'.($entry["snapshot"]?'<img src="gfx/napshot.gif" width="12" height="12" border="0" align=top>':'').'<B>'.$LANG->getLL("time").':</B> '.$theTime.', '.t3lib_BEfunc::calcAge(time()-$entry["tstamp"],$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears")).' &nbsp; - &nbsp; <B>'.$LANG->getLL("user").':</B> '.$be_users[$entry["user"]]["username"].'</td>
							<td>'.
							$this->linkPage('<img src="gfx/history.gif" width="13" height="12" hspace=2 border="0" title="'.$LANG->getLL("revertAllFields").'">', array("revert"=>"ALL_FIELDS","sumUp"=>$entry["uid"],"doReturn"=>1)).
							$this->linkPage('<img src="gfx/history_details.gif" width="12" height="12" hspace=2 border="0" title="'.$LANG->getLL("sumUpChanges").'">', array("sumUp"=>$entry["uid"])).'</td>
						</tr>';
					} else {
						$lines[]='<tr bgColor="'.$darkerBgColor_interM.'">
							<td colspan=3><strong>'.$LANG->getLL("externalChange").'</strong></td>
							</tr>';
					}
					$lines = array_merge($lines,$this->renderEntry($entry,$table));
				}
			}
			
			$theCode='<table border=0 cellpadding=2 cellspacing=2>'.implode("",$lines).'</table>';
			$theCode.='<BR><img src="gfx/icon_note.gif" width="18" height="16" border="0" align="top">'.$LANG->getLL("differenceMsg")."<BR><BR>";
			return $SOBE->doc->section($LANG->getLL("changes"),$theCode,0,1);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$entry: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function renderEntry($entry,$table)	{
		global $SOBE, $LANG, $TCA;
		$lines=array();
 		if (is_array($entry["newRecord"]))	{
			$fieldsToDisplay = array_keys($entry["newRecord"]);
			reset($fieldsToDisplay);
			$t3lib_diff_Obj = t3lib_div::makeInstance("t3lib_diff");
			while(list(,$fN)=each($fieldsToDisplay))	{
				$diffres = $t3lib_diff_Obj->makeDiffDisplay(
					t3lib_BEfunc::getProcessedValue($table,$fN,$entry["oldRecord"][$fN],0,1),
					t3lib_BEfunc::getProcessedValue($table,$fN,$entry["newRecord"][$fN],0,1)
				);

				switch($this->listType)	{
					case 1:
						$lines[]='<tr bgColor="'.$SOBE->doc->bgColor4.'">
							<td valign=top>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN,"<i>|</i>")).'</td>
							<td valign=top>'.nl2br($diffres).'</td>
							<td valign=top>&nbsp;</td>
						</tr>';
					break;
					case 2:
						$lines[]='<tr bgColor="'.$SOBE->doc->bgColor4.'">
							<td valign=top>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN,"<i>|</i>")).'</td>
							<td valign=top>'.t3lib_BEfunc::getProcessedValue($table,$fN,$entry["oldRecord"][$fN]).'</td>
							<td valign=top>'.$this->linkPage('<img src="gfx/history.gif" width="13" height="12" border="0" title="'.$LANG->getLL("revertField").'">', array("revert"=>"field:".$fN)).'</td>
							<td valign=top>'.t3lib_BEfunc::getProcessedValue($table,$fN,$entry["newRecord"][$fN]).'</td>
							<td valign=top>'.nl2br($diffres).'</td>
						</tr>';
					break;
					default:
						$lines[]='<tr bgColor="'.$SOBE->doc->bgColor4.'">
							<td valign=top>'.$LANG->sl(t3lib_BEfunc::getItemLabel($table,$fN,"<i>|</i>")).'</td>
							<td valign=top>'.t3lib_BEfunc::getProcessedValue($table,$fN,$entry["oldRecord"][$fN]).'</td>
							<td valign=top>'.t3lib_BEfunc::getProcessedValue($table,$fN,$entry["newRecord"][$fN]).'</td>
							<td valign=top>'.nl2br($diffres).'</td>
						</tr>';
					break;
				}
			}
		}
		return $lines;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$sh_uid: ...
	 * @return	[type]		...
	 */
	function displaySysHistoryEntry($sh_uid)	{
		global $SOBE, $LANG, $TCA;

 		$query="SELECT * FROM sys_history WHERE uid=".intval($sh_uid);
		$res = mysql(TYPO3_db,$query);
		$newRow = mysql_fetch_assoc($res);
		if (is_array($newRow))	{
			$this->listType=0;
			$lines=array();

			$recIdentString = $LANG->sL($TCA[$newRow["tablename"]]["ctrl"]["title"])."/".$newRow["recuid"];
			$recIdentString = $this->linkPage($recIdentString,array("sh_uid"=>"","element"=>$newRow["tablename"].":".$newRow["recuid"]),"uid_".$sh_uid);
			$lines[]='<tr bgColor="'.$SOBE->doc->bgColor5.'">
				<td colspan=4>
					<strong>'.$LANG->getLL("tableUid").':</strong> '.$recIdentString.'<br>
					<strong>'.$LANG->getLL("time").':</strong> '.t3lib_BEfunc::datetime($newRow["tstamp"]).', '.t3lib_BEfunc::calcAge(time()-$newRow["tstamp"],$GLOBALS["LANG"]->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears")).'<br>
					</td>
			</tr>';

			$lines[]=$this->listHeader();

			$historyData = unserialize($newRow["history_data"]);
			$lines = array_merge($lines,$this->renderEntry($historyData,$newRow["tablename"]));
			
			$theCode='<table border=0 cellpadding=2 cellspacing=2>'.implode("",$lines).'</table>';
			$theCode.='<BR><img src="gfx/icon_note.gif" width="18" height="16" border="0" align="top">'.$LANG->getLL("differenceMsg")."<BR><BR>";
			$content.=$SOBE->doc->section($LANG->getLL("changes"),$theCode,0,1);
		}
		return $content;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function listHeader()	{
		global $SOBE, $LANG, $TCA;

		switch($this->listType)	{
			case 1:
				$out='<tr bgColor="'.$SOBE->doc->bgColor5.'">
					<td valign=top nowrap><strong>'.$LANG->getLL("fieldName").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("difference").':</strong></td>
					<td valign=top>&nbsp;</td>
				</tr>';	
			break;
			case 2:
				$out='<tr bgColor="'.$SOBE->doc->bgColor5.'">
					<td valign=top nowrap><strong>'.$LANG->getLL("fieldName").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("oldValue").':</strong></td>
					<td valign=top nowrap><strong>'.$this->linkPage('<img src="gfx/history.gif" width="13" height="12" border="0" title="'.$LANG->getLL("revertAllFields").'">', array("revert"=>"ALL_FIELDS")).'</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("currentValue").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("difference").':</strong></td>
				</tr>';	
			break;
			default:
				$out='<tr bgColor="'.$SOBE->doc->bgColor5.'">
					<td valign=top nowrap><strong>'.$LANG->getLL("fieldName").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("oldValue").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("newValue").':</strong></td>
					<td valign=top nowrap><strong>'.$LANG->getLL("difference").':</strong></td>
				</tr>';	
			break;
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$str: ...
	 * @param	[type]		$inparams: ...
	 * @param	[type]		$anchor: ...
	 * @return	[type]		...
	 */
	function linkPage($str,$inparams=array(),$anchor="")	{
		$params["sh_uid"]=t3lib_div::GPvar("sh_uid");
		$params["sumUp"]=t3lib_div::GPvar("sumUp");
		$params["element"]=t3lib_div::GPvar("element");
		$params["returnUrl"]=t3lib_div::GPvar("returnUrl");
		$params = array_merge($params,$inparams);			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		$link = '<a href="show_rechis.php?'.t3lib_div::implodeArrayForUrl("",$params).($anchor?"#".$anchor:"").'">'.$str.'</a>';
		return $link;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.show_rechis.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.show_rechis.inc']);
}
?>