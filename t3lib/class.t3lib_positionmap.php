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
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */

class t3lib_positionMap {
	var $checkNewPageCache=Array();
	var $R_URI="";
	var $elUid="";
	var $moveUid="";
	var $moveOrCopy="move";
	var $l_insertNewPageHere = "insertNewPageHere";
	var $l_insertNewRecordHere = "insertNewRecordHere";
	var $dontPrintPageInsertIcons=0;
	var $backPath="";
	var $cur_sys_language;

	function positionTree($id,$pageinfo,$perms_clause,$R_URI)	{
		global $LANG;
		$t3lib_pageTree = t3lib_div::makeInstance("localPageTree");
		$t3lib_pageTree->init(" AND ".$perms_clause);
		$t3lib_pageTree->addField("pid");
		$this->R_URI = $R_URI;
		$this->elUid = $id;
	
		$depth=2;
		$t3lib_pageTree->getTree($pageinfo["pid"], $depth);
		if (!$this->dontPrintPageInsertIcons)	$code.=$this->JSimgFunc();
		reset($t3lib_pageTree->tree);
	
		$saveBlankLineState=array();
		$saveLatestUid=array();
		$latestInvDepth=$depth;
	
		while(list($cc,$dat)=each($t3lib_pageTree->tree))	{
				// Make link + parameters.
			$latestInvDepth=$dat["invertedDepth"];
			$saveLatestUid[$latestInvDepth]=$dat;
			if (isset($t3lib_pageTree->tree[$cc-1]))	{
				$prev_dat = $t3lib_pageTree->tree[$cc-1];
					// If current page, subpage?
				if ($prev_dat["row"]["uid"]==$id)	{
					if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id) && !($prev_dat["invertedDepth"]>$t3lib_pageTree->tree[$cc]["invertedDepth"]))	{	// 1) It must be allowed to create a new page and 2) If there are subpages there is no need to render a subpage icon here - it'll be done over the subpages...
//						$params='&edit[pages]['.$id.']=new&returnNewPageId=1';
						$code.='<nobr>'.$this->insertQuadLines($dat["blankLineCode"]).'<img src=clear.gif width=18 height=8 align=top><a href="#" onClick="'.$this->onClickEvent($id).'" onmouseover="changeImg(\'mImgSubpage'.$cc.'\',0);" onmouseout="changeImg(\'mImgSubpage'.$cc.'\',1);"><img name="mImgSubpage'.$cc.'" src="gfx/newrecord_marker_d.gif" width="281" height="8" border="0" title="'.$this->insertlabel().'" align=top></a><nobr><BR>';
					}
				}
					
				if ($prev_dat["invertedDepth"]>$t3lib_pageTree->tree[$cc]["invertedDepth"])	{	// If going down
					$prevPid = $t3lib_pageTree->tree[$cc]["row"]["pid"];
				} elseif ($prev_dat["invertedDepth"]<$t3lib_pageTree->tree[$cc]["invertedDepth"])	{		// If going up
					// First of all the previous level should have an icon:
					if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($prev_dat["row"]["pid"]))	{
						$prevPid = (-$prev_dat["row"]["uid"]);
//						$params='&edit[pages]['.$prevPid.']=new&returnNewPageId=1';
						$code.='<nobr>'.$this->insertQuadLines($dat["blankLineCode"]).'<img src=clear.gif width=18 height=1 align=top><a href="#" onClick="'.$this->onClickEvent($prevPid).'" onmouseover="changeImg(\'mImgAfter'.$cc.'\',0);" onmouseout="changeImg(\'mImgAfter'.$cc.'\',1);"><img name="mImgAfter'.$cc.'" src="gfx/newrecord_marker_d.gif" width="281" height="8" border="0" title="'.$this->insertlabel().'" align=top></a><nobr><BR>';
					}
	
					// Then set the current prevPid
					$prevPid = -$prev_dat["row"]["pid"];	
				} else {
					$prevPid = -$prev_dat["row"]["uid"];	// In on the same level
				}
			} else {
				$prevPid = $dat["row"]["pid"];	// First in the tree
			}
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat["row"]["pid"]))	{
//				$params='&edit[pages]['.$prevPid.']=new&returnNewPageId=1';
				$code.='<nobr>'.$this->insertQuadLines($dat["blankLineCode"]).'<a href="#" onClick="'.$this->onClickEvent($prevPid).'" onmouseover="changeImg(\'mImg'.$cc.'\',0);" onmouseout="changeImg(\'mImg'.$cc.'\',1);"><img name="mImg'.$cc.'" src="gfx/newrecord_marker_d.gif" width="281" height="8" border="0" title="'.$this->insertlabel().'" align=top></a><nobr><BR>';
			}
	
				// The line with the icon and title:
			$t_code='<nobr>'.$dat["HTML"].$this->linkPageTitle($this->boldTitle(htmlspecialchars(t3lib_div::fixed_lgd($dat["row"]["title"],$BE_USER->uc["titleLen"])),$dat,$id),$dat["row"])."<nobr><BR>";
			$code.=$t_code;
		}
		
			// If the current page was the last in the tree:
		$prev_dat = end($t3lib_pageTree->tree);
		if ($prev_dat["row"]["uid"]==$id)	{
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($id))	{
//				$params='&edit[pages]['.$id.']=new&returnNewPageId=1';
				$code.='<nobr>'.$this->insertQuadLines($saveLatestUid[$latestInvDepth]["blankLineCode"],1).'<img src=clear.gif width=18 height=8 align=top><a href="#" onClick="'.$this->onClickEvent($id).'" onmouseover="changeImg(\'mImgSubpage'.$cc.'\',0);" onmouseout="changeImg(\'mImgSubpage'.$cc.'\',1);"><img name="mImgSubpage'.$cc.'" src="gfx/newrecord_marker_d.gif" width="281" height="8" border="0" title="'.$this->insertlabel().'" align=top></a><nobr><BR>';
			}
		}
	
		for ($a=$latestInvDepth;$a<=$depth;$a++)	{
			$dat = $saveLatestUid[$a];
			$prevPid = (-$dat["row"]["uid"]);
			if (!$this->dontPrintPageInsertIcons && $this->checkNewPageInPid($dat["row"]["pid"]))	{
				$code.='<nobr>'.$this->insertQuadLines($dat["blankLineCode"],1).'<a href="#" onClick="'.$this->onClickEvent($prevPid).'" onmouseover="changeImg(\'mImgEnd'.$a.'\',0);" onmouseout="changeImg(\'mImgEnd'.$a.'\',1);"><img name="mImgEnd'.$a.'" src="gfx/newrecord_marker_d.gif" width="281" height="8" border="0" title="'.$this->insertlabel().'" align=top></a><nobr><BR>';
			}
		}
	
		return $code;
	}
	function JSimgFunc($prefix="")	{
		$code.='
		<script language="javascript" type="text/javascript">
			var img_newrecord_marker=new Image(); 
			img_newrecord_marker.src = "gfx/newrecord'.$prefix.'_marker.gif";
	
			var img_newrecord_marker_d=new Image(); 
			img_newrecord_marker_d.src = "gfx/newrecord'.$prefix.'_marker_d.gif";
	
			function changeImg(name,d)	{
				if (document[name]) {
					if (d)	{
						document[name].src = img_newrecord_marker_d.src;
					} else {
						document[name].src = img_newrecord_marker.src;
					}
				}
			}
		</script>
		';
		return $code;
	}
	function boldTitle($t_code,$dat,$id)	{
		if ($dat["row"]["uid"]==$id)	{
			$t_code='<strong>'.$t_code.'</strong>';
		}
		return $t_code;
	}
	function onClickEvent($pid)	{
		$params='&edit[pages]['.$pid.']=new&returnNewPageId=1';
		return t3lib_BEfunc::editOnClick($params,"",$this->R_URI);
	}
	function insertlabel()	{
		global $LANG;
		return $LANG->getLL($this->l_insertNewPageHere);
	}
	function linkPageTitle($str,$rec)	{
		return $str;
	}
	function checkNewPageInPid($pid)	{
		global $BE_USER;
		if (!isset($this->checkNewPageCache[$pid]))	{
			$pidInfo = t3lib_BEfunc::getRecord("pages",$pid);
			$this->checkNewPageCache[$pid] = ($BE_USER->isAdmin() || $BE_USER->doesUserHaveAccess($pidInfo,8));
		}
		return $this->checkNewPageCache[$pid];
	}
	function insertQuadLines($codes,$allBlank=0)	{	
		$codeA = t3lib_div::trimExplode(",",$codes.",line",1);
		
		$lines=array();
		while(list(,$code)=each($codeA))	{
			if ($code=="blank" || $allBlank)	{
				$lines[]='<img src="clear.gif" width="18" height="8" align=top>';
			} else {
				$lines[]='<img src="gfx/ol/halfline.gif" width="18" height="8" align=top>';		
			}
		}
		return implode("",$lines);
	}
	






	
	function printContentElementColumns($pid,$moveUid,$colPosList,$showHidden,$R_URI)	{
		$this->R_URI = $R_URI;
		$this->moveUid = $moveUid;
		$colPosArray = t3lib_div::trimExplode(",",$colPosList,1);

		$lines=array();
		while(list($kk,$vv)=each($colPosArray))	{
			$query = "SELECT * FROM tt_content WHERE pid=".intval($pid).
				($showHidden ? "" : t3lib_BEfunc::BEenableFields("tt_content")).
				" AND colPos=".$vv.
				(strcmp($this->cur_sys_language,"") ? " AND sys_language_uid=".intval($this->cur_sys_language) : "").
				t3lib_BEfunc::deleteClause("tt_content").
				" ORDER BY sorting";
			$res = mysql(TYPO3_db,$query);

			$lines[$kk]=array();
			$lines[$kk][]=$this->insertPositionIcon("",$vv,$kk,$moveUid,$pid);
			while($row=mysql_fetch_assoc($res))		{
				$lines[$kk][]=$this->wrapRecordHeader($this->getRecordHeader($row),$row);
				$lines[$kk][]=$this->insertPositionIcon($row,$vv,$kk,$moveUid,$pid);
			}
		}
		return $this->printRecordMap($lines,$colPosArray);
	}
	function printRecordMap($lines,$colPosArray)	{
		$row1="";
		$row2="";
		reset($colPosArray);
		while(list($kk,$vv)=each($colPosArray))	{
			$row1.='<td align=center width="'.round(100/count($colPosArray)).'%"><strong>'.$this->wrapColumnHeader(t3lib_div::danish_strtoupper($GLOBALS["LANG"]->sL(t3lib_BEfunc::getLabelFromItemlist("tt_content","colPos",$vv))),$vv).'</strong></td>';
			$row2.='<td valign=top nowrap>'.implode('<BR>',$lines[$kk]).'</td>';
		}
		$table = '<table border=0 cellpadding=0 cellspacing=1>
			<tr bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'">'.$row1.'</tr>
			<tr>'.$row2.'</tr>
		</table>';
		return $this->JSimgFunc("2").$table;
	}
	function wrapColumnHeader($str,$vv)	{
		return $str;
	}
	function insertPositionIcon($row,$vv,$kk,$moveUid,$pid)	{
		$cc = hexdec(substr(md5($row["uid"]."-".$vv."-".$kk),0,4));
		return '<a href="#" onClick="'.$this->onClickInsertRecord($row,$vv,$moveUid,$pid,$this->cur_sys_language).'" onmouseover="changeImg(\'mImg'.$cc.'\',0);" onmouseout="changeImg(\'mImg'.$cc.'\',1);"><img name="mImg'.$cc.'" src="gfx/newrecord2_marker_d.gif" width="100" height="8" border="0" title="'.$GLOBALS["LANG"]->getLL($this->l_insertNewRecordHere).'" align=top></a>';
	}
	function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) {
		$table="tt_content";
		if (is_array($row))	{
			$location="tce_db.php?cmd[".$table."][".$moveUid."][".$this->moveOrCopy."]=-".$row["uid"].'&prErr=1&uPT=1&vC='.$GLOBALS["BE_USER"]->veriCode();
		} else {
			$location="tce_db.php?cmd[".$table."][".$moveUid."][".$this->moveOrCopy."]=".$pid."&data[".$table."][".$moveUid."][colPos]=".$vv.'&prErr=1&vC='.$GLOBALS["BE_USER"]->veriCode();
		}
//		$location.='&redirect='.rawurlencode($this->R_URI);		// returns to prev. page
		$location.='&uPT=1&redirect='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"));		// This redraws screen
		
		return 'document.location=\''.$location.'\';return false;';
	}
	function wrapRecordHeader($str,$row)	{
		return $str;
	}
	function getRecordHeader($row)	{
		$line = t3lib_iconWorks::getIconImage("tt_content",$row,$this->backPath,t3lib_BEfunc::titleAttrib(t3lib_BEfunc::getRecordIconAltText($row,"tt_content"),1).' align=top');
		$line.= t3lib_BEfunc::getRecordTitle("tt_content",$row,1);
		return $this->wrapRecordTitle($line,$row);
	}
	function wrapRecordTitle($str,$row)	{
		return '<a href="'.t3lib_div::linkThisScript(array("uid"=>intval($row["uid"]),"moveUid"=>"")).'">'.$str.'</a>';
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_positionmap.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_positionmap.php"]);
}
?>