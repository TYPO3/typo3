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
 * Wizard to help make tables (eg. for tt_content elements) of type "table". 
 * Each line is a table row, each cell divided by a |
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
 *   78: class SC_wizard_table 
 *   87:     function init()	
 *  117:     function main()	
 *  133:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  156:     function changeFunc($cArr,$TABLE_c)	
 *  266:     function tableWizard($P)	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 

$BACK_PATH='';
require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_wizards.php');











/**
 * Script Class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_wizard_table {
	var $include_once=array();
	var $content;
	var $P;
	var $doc;	
	
	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->P = t3lib_div::GPvar('P',1);
		
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
			<script language="javascript" type="text/javascript">
				function jumpToUrl(URL,formEl)	{	//
					document.location = URL;
				}
			</script>
		';
		
		list($rUri) = explode("#",t3lib_div::getIndpEnv("REQUEST_URI"));
		$this->doc->form ='<form action="'.$rUri.'" method="POST" name="wizardForm">';
		
		$this->content.=$this->doc->startPage("Table");

		if ($HTTP_POST_VARS["savedok_x"] || $HTTP_POST_VARS["saveandclosedok_x"])	{
			$this->include_once[]=PATH_t3lib."class.t3lib_tcemain.php";
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		if ($this->P["table"] && $this->P["field"] && $this->P["uid"])	{
			$this->content.=$this->doc->section($LANG->getLL("table_title"),$this->tableWizard($this->P),0,1);
		} else {
			$this->content.=$this->doc->section($LANG->getLL("table_title"),$GLOBALS["TBE_TEMPLATE"]->rfw($LANG->getLL("table_noData")),0,1);
		}
		$this->content.=$this->doc->endPage();
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}
	








	/***************************
	 *
	 * OTHER FUNCTIONS:	
	 *
	 ***************************/
	
	/**
	 * @param	[type]		$cArr: ...
	 * @param	[type]		$TABLE_c: ...
	 * @return	[type]		...
	 */
	function changeFunc($cArr,$TABLE_c)	{
		if ($TABLE_c['col_remove'])	{	
			$kk = key($TABLE_c['col_remove']);
			$cmd='col_remove';
		} elseif ($TABLE_c['col_add'])	{	
			$kk = key($TABLE_c['col_add']);
			$cmd='col_add';
		} elseif ($TABLE_c['col_start'])	{	
			$kk = key($TABLE_c['col_start']);
			$cmd='col_start';
		} elseif ($TABLE_c['col_end'])	{	
			$kk = key($TABLE_c['col_end']);
			$cmd='col_end';
		} elseif ($TABLE_c['col_left'])	{	
			$kk = key($TABLE_c['col_left']);
			$cmd='col_left';
		} elseif ($TABLE_c['col_right'])	{	
			$kk = key($TABLE_c['col_right']);
			$cmd='col_right';
		} elseif ($TABLE_c['row_remove'])	{	
			$kk = key($TABLE_c['row_remove']);
			$cmd='row_remove';
		} elseif ($TABLE_c['row_add'])	{	
			$kk = key($TABLE_c['row_add']);
			$cmd='row_add';
		} elseif ($TABLE_c['row_top'])	{	
			$kk = key($TABLE_c['row_top']);
			$cmd='row_top';
		} elseif ($TABLE_c['row_bottom'])	{
			$kk = key($TABLE_c['row_bottom']);
			$cmd='row_bottom';
		} elseif ($TABLE_c['row_up'])	{	
			$kk = key($TABLE_c['row_up']);
			$cmd='row_up';
		} elseif ($TABLE_c['row_down'])	{	
			$kk = key($TABLE_c['row_down']);
			$cmd='row_down';
		}
	
		if ($cmd && t3lib_div::testInt($kk)) {
	//			debug($cmd);
	//			debug($cArr);
			if (substr($cmd,0,4)=='row_')	{
				switch($cmd)	{
					case 'row_remove':
						unset($cArr[$kk]);
					break;
					case 'row_add':
						$cArr[$kk+1]=array();
					break;
					case 'row_top':
						$cArr[1]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case 'row_bottom':
						$cArr[10000000]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case 'row_up':
						$cArr[$kk-3]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case 'row_down':
						$cArr[$kk+3]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
				}
				ksort($cArr);
			}
			if (substr($cmd,0,4)=='col_')	{
				reset($cArr);
				while(list($cAK)=each($cArr))	{
					switch($cmd)	{
						case 'col_remove':
							unset($cArr[$cAK][$kk]);
						break;
						case 'col_add':
							$cArr[$cAK][$kk+1]='';
						break;
						case 'col_start':
							$cArr[$cAK][1]=$cArr[$cAK][$kk];
							unset($cArr[$cAK][$kk]);
						break;
						case 'col_end':
							$cArr[$cAK][1000000]=$cArr[$cAK][$kk];
							unset($cArr[$cAK][$kk]);
						break;
						case 'col_left':
							$cArr[$cAK][$kk-3]=$cArr[$cAK][$kk];
							unset($cArr[$cAK][$kk]);
						break;
						case 'col_right':
							$cArr[$cAK][$kk+3]=$cArr[$cAK][$kk];
							unset($cArr[$cAK][$kk]);
						break;
					}
					ksort($cArr[$cAK]);
				}
			}
	//			debug($cArr);
		}
		return $cArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$P: ...
	 * @return	[type]		...
	 */
	function tableWizard($P)	{
		global $LANG, $HTTP_POST_VARS;
		
		$TABLE_c = t3lib_div::GPvar('TABLE',1);
		$row=t3lib_BEfunc::getRecord($P['table'],$P['uid']);
		if (!is_array($row))	{
			t3lib_BEfunc::typo3PrintError ('Wizard Error','No reference to record',0);
			exit;
		}
	
		$inputStyle=isset($TABLE_c['textFields']) ? $TABLE_c['textFields'] : 1;
		$cols=$row['cols'];
	
		if (isset($TABLE_c['c']))	{
			$TABLE_c['c'] = $this->changeFunc($TABLE_c['c'],$TABLE_c);
			$inLines=array();
			
			reset($TABLE_c["c"]);
			while(list($a)=each($TABLE_c["c"]))	{
	//		for($a=0;$a<intval($TABLE_c["lines"]);$a++)	{
				$thisLine=array();
	//			for($b=0;$b<$cols;$b++)	{
				reset($TABLE_c["c"][$a]);
				while(list($b)=each($TABLE_c["c"][$a]))	{
					$thisLine[]=str_replace("|","",str_replace(chr(10),"<BR>",str_replace(chr(13),"",$TABLE_c["c"][$a][$b])));
				}
				$inLines[]=implode("|",$thisLine);
			}
			$bodyText = implode(chr(10),$inLines);
	//		debug(array($bodyText));
	
	
	
			if ($HTTP_POST_VARS["savedok_x"] || $HTTP_POST_VARS["saveandclosedok_x"])	{
				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$data=array();
				$data[$P["table"]][$P["uid"]][$P["field"]]=$bodyText;
				$tce->start($data,array());
				$tce->process_datamap();
				if ($HTTP_POST_VARS["saveandclosedok_x"])	{
					header("Location: ".t3lib_div::locationHeaderUrl($P["returnUrl"]));
					exit;
				}
			}
		} else {
			$bodyText = $row["bodytext"];
		}
			
		$tLines=explode(chr(10),$bodyText);
	
			// Columns:
		if (!$cols && trim($tLines[0]))	{	// auto...
			$cols = count(explode("|",$tLines[0]));
		}
		$cols=$cols?$cols:4;
	
		reset($tLines);
		$tRows=array();
	
		while(list($k,$v)=each($tLines))	{
			$cells=array();
			$vParts = explode("|",$v);
			
			for ($a=0;$a<$cols;$a++)	{
				$content=$vParts[$a];
				if ($inputStyle)	{
					$cells[]='<input type="text"'.$this->doc->formWidth(20).' name="TABLE[c]['.(($k+1)*2).']['.(($a+1)*2).']" value="'.htmlspecialchars($content).'">';
				} else {
					$content=str_replace("<BR>",chr(10),$content);
					$cells[]='<textarea '.$this->doc->formWidth(20).' rows="5" name="TABLE[c]['.(($k+1)*2).']['.(($a+1)*2).']">'.t3lib_div::formatForTextarea($content).'</textarea>';
				}
			}
			$onClick="document.wizardForm.action+='#ANC_".(($k+1)*2-2)."';";
			$onClick=' onClick="'.$onClick.'"';
			$ctrl="";
			$brTag=$inputStyle?"":"<BR>";
			if ($k!=0)	{
				$ctrl.='<input type="image" name="TABLE[row_up]['.(($k+1)*2).']" src="gfx/pil2up.gif" width="12" vspace=2 height="7" border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_up")).'>'.$brTag;
			} else {
				$ctrl.='<input type="image" name="TABLE[row_bottom]['.(($k+1)*2).']" src="gfx/turn_up.gif" width="11" vspace=2 height="9" hspace=1 border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_bottom")).'>'.$brTag;
			}
			$ctrl.='<input type="image" name="TABLE[row_remove]['.(($k+1)*2).']" src="gfx/garbage.gif" width="11" height="12" border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_removeRow")).'>'.$brTag;
	
			if (($k+1)!=count($tLines))	{
				$ctrl.='<input type="image" name="TABLE[row_down]['.(($k+1)*2).']" src="gfx/pil2down.gif" width="12" vspace=2 height="7" hspace=1 border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_down")).'>'.$brTag;
			} else {
				$ctrl.='<input type="image" name="TABLE[row_top]['.(($k+1)*2).']" src="gfx/turn_down.gif" width="11" vspace=2 height="9" hspace=1 border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_top")).'>'.$brTag;
			}
			$ctrl.='<input type="image" name="TABLE[row_add]['.(($k+1)*2).']" src="gfx/add.gif" width="12" height="12" border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_addRow")).'>'.$brTag;
	
			$tRows[]='<tr bgColor="'.$this->doc->bgColor4.'"><td bgColor="'.$this->doc->bgColor5.'"><a name="ANC_'.(($k+1)*2).'"></a>'.$ctrl.'</td><td>'.implode('</td><td>',$cells).'</td></tr>';
		}
	
			// REMOVE
		$cells=array();
		$cells[]='';
		for ($a=0;$a<$cols;$a++)	{
			$content=$vParts[$a];
			$content=str_replace("<BR>",chr(10),$content);
			$ctrl="";
			if ($a!=0)	{
				$ctrl.='<input type="image" name="TABLE[col_left]['.(($a+1)*2).']" src="gfx/pil2left.gif" width="7" hspace=2 height="12" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_left")).'>';
			} else {
				$ctrl.='<input type="image" name="TABLE[col_end]['.(($a+1)*2).']" src="gfx/turn_left.gif" width="9" hspace=2 vspace=1 height="11" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_end")).'>';
			}
			$ctrl.='<input type="image" name="TABLE[col_remove]['.(($a+1)*2).']" src="gfx/garbage.gif" width="11" height="12" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_removeColumn")).'>';
			if (($a+1)!=$cols)	{
				$ctrl.='<input type="image" name="TABLE[col_right]['.(($a+1)*2).']" src="gfx/pil2right.gif" width="7" hspace=2 height="12" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_right")).'>';
			} else {
				$ctrl.='<input type="image" name="TABLE[col_start]['.(($a+1)*2).']" src="gfx/turn_right.gif" width="9" hspace=2 vspace=1 height="11" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_start")).'>';
			}
			$ctrl.='<input type="image" name="TABLE[col_add]['.(($a+1)*2).']" src="gfx/add.gif" width="12" hspace=5 height="12" border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("table_addColumn")).'>';
			$cells[]=$ctrl;
		}
		$tRows[]='<tr bgColor="'.$this->doc->bgColor5.'"><td align=center>'.implode('</td><td align=center>',$cells).'</td></tr>';
	
			// 
		$content = '<table border=0 cellpadding=0 cellspacing=1>'.implode("",$tRows).'</table>';
		
		$closeUrl = $P["returnUrl"];
		
		$content.= '<BR>';
		$content.= '<input type="image" border=0 name="savedok" src="gfx/savedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDoc"),1).' align=top>';
		$content.= '<input type="image" border=0 name="saveandclosedok" src="gfx/saveandclosedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc"),1).' align=top>';
		$content.= '<a href="#" onClick="jumpToUrl(unescape(\''.rawurlencode($closeUrl).'\')); return false;"><img border=0 src="gfx/closedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.closeDoc"),1).' align=top></a>';
		$content.= '<input type="image" name="_refresh" src="gfx/refresh_n.gif" width="14" height="14" hspace=10 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_refresh")).'>';
	
		$content.= '<BR><input type="hidden" name="TABLE[textFields]" value="0"><input type="checkbox" name="TABLE[textFields]" value="1"'.($inputStyle?" CHECKED":"").'> '.$LANG->getLL("table_smallFields");
		
		return $content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_table.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_table.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_table');
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();
?>