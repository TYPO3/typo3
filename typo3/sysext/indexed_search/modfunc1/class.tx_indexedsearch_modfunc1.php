<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2001-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Module extension (addition to function menu) 'Indexed search' for the 'indexed_search' extension.
 *
 * @author    Kasper Skårhøj <kasperYYYY@typo3.com>
 */



require_once(PATH_t3lib."class.t3lib_pagetree.php");
require_once(PATH_t3lib."class.t3lib_extobjbase.php");
require_once(t3lib_extMgm::extPath("indexed_search")."class.indexer.php");

class tx_indexedsearch_modfunc1 extends t3lib_extobjbase {
	var $allPhashListed=array();
	
    function modMenu()    {
        global $LANG;
        
		return array (
			"depth" => array(
				0 => $LANG->sL("LLL:EXT:lang/locallang_core.php:labels.depth_0"),
				1 => $LANG->sL("LLL:EXT:lang/locallang_core.php:labels.depth_1"),
				2 => $LANG->sL("LLL:EXT:lang/locallang_core.php:labels.depth_2"),
				3 => $LANG->sL("LLL:EXT:lang/locallang_core.php:labels.depth_3"),
			)
		);
    }

    function main()    {
            // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
        
		if ($this->pObj->id<=0)	return;

		if (t3lib_div::_GP("deletePhash"))	{
			$indexer = t3lib_div::makeInstance("tx_indexedsearch_indexer");
			$indexer->removeIndexedPhashRow(t3lib_div::_GP("deletePhash"));
		}



		$h_func = t3lib_BEfunc::getFuncMenu($this->pObj->id,"SET[depth]",$this->pObj->MOD_SETTINGS["depth"],$this->pObj->MOD_MENU["depth"],"index.php");
		

        $theOutput.=$this->pObj->doc->spacer(5);
        $theOutput.=$this->pObj->doc->section($LANG->getLL("title"),$h_func,0,1);



			// Drawing tree:
		$tree = t3lib_div::makeInstance("t3lib_pageTree");
		$perms_clause = $GLOBALS["BE_USER"]->getPagePermsClause(1);
		$tree->init("AND ".$perms_clause);
		
		$HTML='<IMG src="'.$BACK_PATH.t3lib_iconWorks::getIcon("pages",$this->pObj->pageinfo).'" width="18" height="16" align="top">';
		$tree->tree[]=Array("row"=>$this->pObj->pageinfo,"HTML"=>$HTML);
		if ($this->pObj->MOD_SETTINGS["depth"])	{
			$tree->getTree($this->pObj->id,$this->pObj->MOD_SETTINGS["depth"],"");
		}
		
		// 
		$code='';
		
		reset($tree->tree);
		while(list(,$data)=each($tree->tree))	{
			$bgCol="";
			$tLen=20;
			$code.='<tr>
 				<td align="left" nowrap'.$bgCol.' valign=top>'.$data["HTML"].t3lib_div::fixed_lgd($data["row"]["title"],$tLen).'&nbsp;</td>
				';

			$code.='<td valign=top>'.$this->indexed_info($data["row"]).'</td>';
			$code.='</tr>';
		}



		$code='<table border=0 cellspacing=0 cellpadding=1>
			<tr>
				<td></td>
				<td valign=top><table border=0 cellpadding=0 cellspacing=1>'.$this->printPhashRowHeader().'</table></td>
			</tr>'.$code.'</table>';
		
        $theOutput.=$this->pObj->doc->section("",$code,0,1);
        
        return $theOutput;
    }
	
	
	function indexed_info($data)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'ISEC.*, IP.*, count(*) as count_val',
					'index_phash AS IP, index_section AS ISEC',
					'IP.phash = ISEC.phash AND ISEC.page_id = '.intval($data['uid']),
					'IP.phash', 
					'IP.crdate'
				);
		$lines = array();
		$phashAcc = array();
		$phashAcc[] = 0;
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$extraGrListRows = $this->getGrListEntriesForPhash($row["phash"],$row["gr_list"]);
			if (isset($lines[$row["phash_grouping"]]))	{
				$lines[$row["phash_grouping"]].= $this->printPhashRow($row,1,$extraGrListRows);
			} else {
				$lines[$row["phash_grouping"]] = $this->printPhashRow($row,0,$extraGrListRows);
			}
			$phashAcc[] = $row["phash"];
			$this->allPhashListed[] = $row["phash"];
		}
		$out = implode("",$lines);
		$out = '<table border=0 cellpadding=0 cellspacing=1>'.$out.'</table>';
		

			// Checking for phash-rows which are NOT joined with the section table:		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('IP.*', 'index_phash AS IP', 'IP.data_page_id = '.intval($data['uid']).' AND IP.phash NOT IN ('.implode(',',$phashAcc).')');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$out.= '<span class="typo3-red">Warning:</span> phash-row "'.$row["phash"].'" didn\'t have a representation in the index_section table!'.'<br>';
			$this->allPhashListed[] = $row["phash"];
		}
		
		return $out;
	}
	function printPhashRow($row,$grouping=0,$extraGrListRows)	{
#debug($row);
		$lines=array();
		if (!$grouping)	{
			$lines[]='<td valign=top>'.$this->makeItemTypeIcon($row["item_type"],$row["data_filename"]?$row["data_filename"]:$row["item_title"]).'</td>';
		} else {
			$lines[]='<td valign=top class="bgColor">&nbsp;</td>';
		}
		$lines[]='<td valign=top nowrap>'.t3lib_div::fixed_lgd($row["item_title"],20).$this->clearGif(120).'</td>';
		$lines[]='<td valign=top nowrap>'.$this->printRemoveIndexed($row["phash"]).'</td>';

		$lines[]='<td valign=top nowrap>'.$row["phash"].$this->clearGif(80).'</td>';
		$lines[]='<td valign=top nowrap>'.$row["contentHash"].$this->clearGif(80).'</td>';
		$lines[]='<td valign=top nowrap>'.$row["rl0"].'.'.$row["rl1"].'.'.$row["rl2"].$this->clearGif(50).'</td>';
		$lines[]='<td valign=top nowrap>'.($row["item_type"]?"":$row["data_page_id"].'.'.$row["data_page_type"].'.'.$row["sys_language_uid"].($row["data_page_mp"]?'.'.$row["data_page_mp"]:"")).$this->clearGif(50).'</td>';
		$lines[]='<td valign=top nowrap>'.t3lib_div::formatSize($row["item_size"]).$this->clearGif(40).'</td>';
		$lines[]='<td valign=top nowrap>'.$row["gr_list"].$this->printExtraGrListRows($extraGrListRows).$this->clearGif(45).'</td>';
		$arr = unserialize($row["cHashParams"]);
		unset($arr["cHash"]);
		unset($arr["encryptionKey"]);
		if ($row["item_type"]==2)	{	// pdf...
			$lines[]='<td valign=top nowrap>Page '.$arr["key"].'</td>';
		} elseif ($row["item_type"]==0) {
			$lines[]='<td valign=top nowrap'.(count($arr)?'':' class="bgColor"').'>'.htmlspecialchars(t3lib_div::implodeArrayForUrl("",$arr)).'</td>';
		} else {
			$lines[]='<td valign=top class="bgColor"></td>';
		}
		
		$out = '<tr'.($row["count_val"]!=1?' bgcolor="red"':' class="bgColor4"').'>'.implode("",$lines).'</tr>';
		return $out;
	}
	function printRemoveIndexed($phash,$alt="Clear phash-row")	{
		return '<a href="'.t3lib_div::linkThisScript(array("deletePhash"=>$phash)).'"><img src="'.$GLOBALS["BACK_PATH"].'gfx/garbage.gif" width="11" hspace=1 vspace=2 height="12" border="0" alt="'.$alt.'"></a>';
	}
	function printPhashRowHeader()	{
		$lines=array();
		$lines[]='<td>&nbsp;'.$this->clearGif(18).'</td>';
		$lines[]='<td nowrap><strong>Title</strong>'.$this->clearGif(120).'</td>';
#		$lines[]='<td>&nbsp;'.$this->clearGif(13).'</td>';
		$lines[]='<td bgcolor="red">'.$this->printRemoveIndexed(implode(",",$this->allPhashListed),"Clear ALL phash-rows below!").'</td>';

		$lines[]='<td nowrap><strong>pHash</strong>'.$this->clearGif(80).'</td>';
		$lines[]='<td nowrap><strong>cHash</strong>'.$this->clearGif(80).'</td>';
		$lines[]='<td nowrap><strong>rl-012</strong>'.$this->clearGif(50).'</td>';
		$lines[]='<td nowrap><strong>pid.t.l</strong>'.$this->clearGif(50).'</td>';
		$lines[]='<td nowrap><strong>Size</strong>'.$this->clearGif(40).'</td>';
		$lines[]='<td nowrap><strong>grlist</strong>'.$this->clearGif(45).'</td>';
		$lines[]='<td nowrap><strong>cHashParams</strong>'.$this->clearGif(50).'</td>';
		
		$out = '<tr class="bgColor5">'.implode("",$lines).'</tr>';
		return $out;
	}
	function printExtraGrListRows($extraGrListRows)	{
		if (count($extraGrListRows))	{
			reset($extraGrListRows);
			$lines=array();
			while(list(,$r)=each($extraGrListRows))	{
				$lines[]=$r["gr_list"];
			}
			return "<BR>".$GLOBALS["TBE_TEMPLATE"]->dfw(implode("<BR>",$lines));
		}
	}
	function clearGif($width)	{
		return '<br><img src=clear.gif width='.$width.' height=1>';
	}
	function getGrListEntriesForPhash($phash,$gr_list)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'index_grlist', 'phash='.intval($phash));
		$lines = array();
		$isRemoved = 0;
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			if (!$isRemoved && !strcmp($row["gr_list"],$gr_list))	{
				$isRemoved = 1;
			} else {
				$lines[] = $row;
			}
		}
		return $lines;
	}

	function makeItemTypeIcon($it,$alt="")	{
		switch($it)	{
			case 1:
				$icon="html.gif";
			break;
			case 2:
				$icon="pdf.gif";
			break;
			case 3:
				$icon="doc.gif";
			break;
			case 4:
				$icon="txt.gif";
			break;
			default:
				$icon="pages.gif";
			break;
		}
		$fullPath = t3lib_extMgm::extPath("indexed_search").'pi/res/'.$icon;
		$info = @getimagesize($fullPath);
		$iconPath = $GLOBALS["BACK_PATH"].t3lib_extMgm::extRelPath("indexed_search").'pi/res/'.$icon;
		return is_array($info) ? '<img src="'.$iconPath.'" '.$info[3].' title="'.htmlspecialchars($alt).'">' : '';
	}
	
	
	
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/modfunc1/class.tx_indexedsearch_modfunc1.php"])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/indexed_search/modfunc1/class.tx_indexedsearch_modfunc1.php"]);
}

?>