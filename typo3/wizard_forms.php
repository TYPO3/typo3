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
 * Wizard to help make forms (fx. for tt_content elements) of type "form". 
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
 *   80: class SC_wizard_forms 
 *   89:     function init()	
 *  119:     function main()	
 *  135:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  158:     function changeFunc($cArr,$TABLE_c)	
 *  218:     function cleanT($tArr)	
 *  235:     function formatCells($fArr)	
 *  251:     function tableWizard($P)	
 *
 * TOTAL FUNCTIONS: 7
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
class SC_wizard_forms {
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
				function jumpToUrl(URL,formEl)	{	/
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
			$this->content.=$this->doc->section($LANG->getLL("forms_title"),$this->tableWizard($this->P),0,1);
		} else {
			$this->content.=$this->doc->section($LANG->getLL("forms_title"),$GLOBALS["TBE_TEMPLATE"]->rfw($LANG->getLL("table_noData")),0,1);
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
	
	
	
	
	
	
	
	
	
	/****************************
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
		if ($TABLE_c["row_remove"])	{	
			$kk = key($TABLE_c["row_remove"]);
			$cmd="row_remove";
		} elseif ($TABLE_c["row_add"])	{	
			$kk = key($TABLE_c["row_add"]);
			$cmd="row_add";
		} elseif ($TABLE_c["row_top"])	{	
			$kk = key($TABLE_c["row_top"]);
			$cmd="row_top";
		} elseif ($TABLE_c["row_bottom"])	{	
			$kk = key($TABLE_c["row_bottom"]);
			$cmd="row_bottom";
		} elseif ($TABLE_c["row_up"])	{	
			$kk = key($TABLE_c["row_up"]);
			$cmd="row_up";
		} elseif ($TABLE_c["row_down"])	{	
			$kk = key($TABLE_c["row_down"]);
			$cmd="row_down";
		}
	
		if ($cmd && t3lib_div::testInt($kk)) {
			if (substr($cmd,0,4)=="row_")	{
				switch($cmd)	{
					case "row_remove":
						unset($cArr[$kk]);
					break;
					case "row_add":
						$cArr[$kk+1]=array();
					break;
					case "row_top":
						$cArr[1]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case "row_bottom":
						$cArr[1000000]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case "row_up":
						$cArr[$kk-3]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
					case "row_down":
						$cArr[$kk+3]=$cArr[$kk];
						unset($cArr[$kk]);
					break;
				}
				ksort($cArr);
			}
		}
	
		return $cArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$tArr: ...
	 * @return	[type]		...
	 */
	function cleanT($tArr)	{
		for($a=count($tArr);$a>0;$a--)	{
			if (strcmp($tArr[$a-1],""))	{
				break;
			} else {
				unset($tArr[$a-1]);
			}
		}
		return $tArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$fArr: ...
	 * @return	[type]		...
	 */
	function formatCells($fArr)	{
		reset($fArr);
		$lines=array();
		while(list($l,$c)=each($fArr))	{
			$lines[]='<tr><td nowrap>'.htmlspecialchars($l.":").'&nbsp;</td><td>'.$c.'</td></tr>';
		}
		$lines[]='<tr><td nowrap><img src=clear.gif width=70 height=1></td><td></td></tr>';
		return '<table border=0 cellpadding=0 cellspacing=0>'.implode("",$lines).'</table>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$P: ...
	 * @return	[type]		...
	 */
	function tableWizard($P)	{
		global $LANG, $HTTP_POST_VARS;
		
		$TABLE_c = t3lib_div::GPvar("TABLE",1);
		$row=t3lib_BEfunc::getRecord($P["table"],$P["uid"]);
		if (!is_array($row))	{
			t3lib_BEfunc::typo3PrintError ("Wizard Error","No reference to record",0);
			exit;
		}
		
		$special=t3lib_div::GPvar("special");
	
		if (isset($TABLE_c["c"]))	{
			$TABLE_c["c"] = $this->changeFunc($TABLE_c["c"],$TABLE_c);
			$inLines=array();
			
			reset($TABLE_c["c"]);
			while(list($a,$vv)=each($TABLE_c["c"]))	{
				if ($vv["content"])	{
					$inLines[]=trim($vv["content"]);
				} else {
					$thisLine=array();
						// Label:
					$thisLine[0]=str_replace("|","",$vv["label"]);
	
						// Type:
					if ($vv["type"])	{
						$thisLine[1]=($vv["required"]?"*":"").str_replace(",","",($vv["fieldname"]?$vv["fieldname"]."=":"").$vv["type"]);
							// Default:
						$tArr=array("","","","","","");
						switch((string)$vv["type"])	{
							case "textarea":
								if (intval($vv["cols"]))	$tArr[0]=intval($vv["cols"]);
								if (intval($vv["rows"]))	$tArr[1]=intval($vv["rows"]);
								if (trim($vv["extra"]))		$tArr[2]=trim($vv["extra"]);
							break;
							case "input":
							case "password":
								if (intval($vv["size"]))	$tArr[0]=intval($vv["size"]);
								if (intval($vv["max"]))		$tArr[1]=intval($vv["max"]);
							break;
							case "file":
								if (intval($vv["size"]))	$tArr[0]=intval($vv["size"]);
							break;
							case "select":
								if (intval($vv["size"]))	$tArr[0]=intval($vv["size"]);
								if ($vv["autosize"])	$tArr[0]="auto";
								if ($vv["multiple"])	$tArr[1]="m";
							break;
						}
						$tArr = $this->cleanT($tArr);
						if (count($tArr))	$thisLine[1].=",".implode(",",$tArr);
						
						$thisLine[1]=str_replace("|","",$thisLine[1]);
			
			
			
							// Default:
						if ($vv["type"]=="select" || $vv["type"]=="radio")	{
							$thisLine[2]=str_replace(chr(10),", ",str_replace(",","",$vv["options"]));
						} elseif ($vv["type"]=="check")	{
							if ($vv["checked"])	$thisLine[2]=1;
						} elseif (strcmp(trim($vv["default"]),"")) {
							$thisLine[2]=$vv["default"];
						}
						if (isset($thisLine[2]))		$thisLine[2]=str_replace("|","",$thisLine[2]);
					}
						// Compile line:
					$inLines[]=ereg_replace("[\n\r]*","",implode(" | ",$thisLine));
				}
			}
			$bodyText = implode(chr(10),$inLines);
	//debug(array($bodyText));
	
	
	
			if ($HTTP_POST_VARS["savedok_x"] || $HTTP_POST_VARS["saveandclosedok_x"])	{
				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$data=array();
				$data[$P["table"]][$P["uid"]][$P["field"]]=$bodyText;
				if ($special=="formtype_mail")	{
					$data[$P["table"]][$P["uid"]]["subheader"]=$TABLE_c["recipient"];
				}
	
	//debug($data);
				$tce->start($data,array());
				$tce->process_datamap();
	
				$row=t3lib_BEfunc::getRecord($P["table"],$P["uid"]);
				if ($HTTP_POST_VARS["saveandclosedok_x"])	{
					header("Location: ".t3lib_div::locationHeaderUrl($P["returnUrl"]));
					exit;
				}
			}
		} else {
			$bodyText = $row["bodytext"];
		}
	//$bodyText = $row["bodytext"];
	
		
		$specParts=array();
		$hiddenFields=array();
		$tRows=array();
		$cells=array(
			'<strong>'.$LANG->getLL("forms_preview").':</strong>',
			'<strong>'.$LANG->getLL("forms_element").':</strong>',
			'<strong>'.$LANG->getLL("forms_config").':</strong>',
		);
		$tRows[]='<tr bgColor="'.$this->doc->bgColor2.'"><td>&nbsp;</td><td nowrap>'.implode('</td><td nowrap valign=top>',$cells).'</td></tr>';
	
		$tLines=explode(chr(10),$bodyText);
	//debug($tLines);
		reset($tLines);
		while(list($k,$v)=each($tLines))	{
			$cells=array();
			$confData=array();
	
			$val=trim($v);
			$parts = t3lib_div::trimExplode("|",$val);
	
			if (!trim($val) || strcspn($val,"#/")) {	// $val && 
					// label:
				$confData["label"] = trim($parts[0]);
					// field:
				$fParts = t3lib_div::trimExplode(",",$parts[1]);
				$fParts[0]=trim($fParts[0]);
				if (substr($fParts[0],0,1)=="*")	{
					$confData["required"]=1;
					$fParts[0] = substr($fParts[0],1);
				}
				$typeParts = t3lib_div::trimExplode("=",$fParts[0]);
				$confData["type"] = trim(strtolower(end($typeParts)));
				if (count($typeParts)==1)	{
					$confData["fieldname"] = substr(ereg_replace("[^a-zA-Z0-9_]","",str_replace(" ","_",trim($parts[0]))),0,30);
	/*				if (strtolower($confData["fieldname"])=="email")	{$confData["fieldname"]="email";}
						// Duplicate fieldnames resolved
					if (isset($fieldname_hashArray[md5($confData["fieldname"])]))	{
						$confData["fieldname"].="_".$cc;
					}
					$fieldname_hashArray[md5($confData["fieldname"])]=$confData["fieldname"];
	*/
						// Attachment names...
					if ($confData["type"]=="file")	{
						$confData["fieldname"]="attachment".$attachmentCounter;
						$attachmentCounter=intval($attachmentCounter)+1;
					}
				} else {
					$confData["fieldname"] = str_replace(" ","_",trim($typeParts[0]));
				}
	
				if ($special=="formtype_mail" && t3lib_div::inList("formtype_mail,subject,html_enabled",$confData["fieldname"]))	{
					$specParts[$confData["fieldname"]]=$parts[2];
				} else {
						// Render title/field preview
					$cells[]=$confData["type"]!="hidden" ? '<strong>'.htmlspecialchars($confData["label"]).'</strong>' : '';
					
					$temp_cells=array();
						// Field type
					$opt=array();
					$opt[]='<option value=""></option>';
					$types = explode(",","input,textarea,select,check,radio,password,file,hidden,submit");
					while(list(,$t)=each($types))	{
						$opt[]='<option value="'.$t.'"'.($confData["type"]==$t?" SELECTED":"").'>'.htmlspecialchars($LANG->getLL("forms_type_".$t)).'</option>';
					}
					$temp_cells[$LANG->getLL("forms_type")]='<select name="TABLE[c]['.(($k+1)*2).'][type]">'.implode("",$opt).'</select>';
						// Title field
					$temp_cells[$LANG->getLL("forms_label")]='<input type="text"'.$this->doc->formWidth(15).' name="TABLE[c]['.(($k+1)*2).'][label]" value="'.htmlspecialchars($confData["label"]).'">';
						// Required
					if (!t3lib_div::inList(",hidden,submit",$confData["type"]))		$temp_cells[$LANG->getLL("forms_required")]='<input type="checkbox" name="TABLE[c]['.(($k+1)*2).'][required]" value="1"'.($confData["required"]?" CHECKED":"").t3lib_BEfunc::titleAttrib($LANG->getLL("forms_required"),1).'>';
					
					$cells[]=$this->formatCells($temp_cells);
					$temp_cells=array();
					
						// Fieldname
					$temp_cells[$LANG->getLL("forms_fieldName")]='<input type="text"'.$this->doc->formWidth(10).' name="TABLE[c]['.(($k+1)*2).'][fieldname]" value="'.htmlspecialchars($confData["fieldname"]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_fieldName"),1).'>';
					switch((string)$confData["type"])	{
						case "textarea":
							$temp_cells[$LANG->getLL("forms_cols")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][cols]" value="'.htmlspecialchars($fParts[1]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_cols"),1).'>';
							$temp_cells[$LANG->getLL("forms_rows")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][rows]" value="'.htmlspecialchars($fParts[2]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_rows"),1).'>';
							$temp_cells[$LANG->getLL("forms_extra")]='<input type="checkbox" name="TABLE[c]['.(($k+1)*2).'][extra]" value="OFF"'.(strtoupper($fParts[3])=="OFF"?" CHECKED":"").t3lib_BEfunc::titleAttrib($LANG->getLL("forms_extra"),1).'>';
						break;
						case "input":
						case "password":
							$temp_cells[$LANG->getLL("forms_size")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][size]" value="'.htmlspecialchars($fParts[1]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_size"),1).'>';
							$temp_cells[$LANG->getLL("forms_max")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][max]" value="'.htmlspecialchars($fParts[2]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_max"),1).'>';
						break;
						case "file":
							$temp_cells[$LANG->getLL("forms_size")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][size]" value="'.htmlspecialchars($fParts[1]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_size"),1).'>';
						break;
						case "select":
							$temp_cells[$LANG->getLL("forms_size")]='<input type="text"'.$this->doc->formWidth(5).' name="TABLE[c]['.(($k+1)*2).'][size]" value="'.htmlspecialchars(intval($fParts[1])?$fParts[1]:"").'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_size"),1).'>';
							$temp_cells[$LANG->getLL("forms_autosize")]='<input type="checkbox" name="TABLE[c]['.(($k+1)*2).'][autosize]" value="1"'.(strtolower(trim($fParts[1]))=="auto"?" CHECKED":"").t3lib_BEfunc::titleAttrib($LANG->getLL("forms_autosize"),1).'>';
							$temp_cells[$LANG->getLL("forms_multiple")]='<input type="checkbox" name="TABLE[c]['.(($k+1)*2).'][multiple]" value="1"'.(strtolower(trim($fParts[2]))=="m"?" CHECKED":"").t3lib_BEfunc::titleAttrib($LANG->getLL("forms_multiple"),1).'>';
						break;
					}
		
						// Default data
					if ($confData["type"]=="select" || $confData["type"]=="radio")	{
						$temp_cells[$LANG->getLL("forms_options")]='<textarea '.$this->doc->formWidthText(15).' rows="4" name="TABLE[c]['.(($k+1)*2).'][options]"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_options"),1).'>'.t3lib_div::formatForTextarea(implode(chr(10),t3lib_div::trimExplode(",",$parts[2]))).'</textarea>';
					} elseif ($confData["type"]=="check")	{
						$temp_cells[$LANG->getLL("forms_checked")]='<input type="checkbox" name="TABLE[c]['.(($k+1)*2).'][default]" value="1"'.(trim($parts[2])?" CHECKED":"").t3lib_BEfunc::titleAttrib($LANG->getLL("forms_checked"),1).'>';
					} elseif ($confData["type"] && $confData["type"]!="file") {
						$temp_cells[$LANG->getLL("forms_default")]='<input type="text"'.$this->doc->formWidth(15).' name="TABLE[c]['.(($k+1)*2).'][default]" value="'.htmlspecialchars($parts[2]).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_default"),1).'>';
					}
		
					$cells[]=$confData["type"]?$this->formatCells($temp_cells):"";
		
						// CTRL panel:
					$ctrl="";
					$onClick="document.wizardForm.action+='#ANC_".(($k+1)*2-2)."';";
					$onClick=' onClick="'.$onClick.'"';
	
					$brTag=$inputStyle?"":"<BR>";
					if ($k!=0)	{
						$ctrl.='<input type="image" name="TABLE[row_up]['.(($k+1)*2).']" src="gfx/pil2up.gif" width="12" vspace=2 height="7" hspace=1 border="0"'.$onClick.t3lib_BEfunc::titleAttrib($LANG->getLL("table_up")).'>'.$brTag;
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
			
					$bgC = $confData["type"]?' bgColor="'.$this->doc->bgColor5.'"':'';
					$tRows[]='<tr'.$bgC.'><td><a name="ANC_'.(($k+1)*2).'"></a>'.$ctrl.'</td><td bgColor="'.$this->doc->bgColor4.'">'.implode('</td><td valign=top>',$cells).'</td></tr>';
				}
			} else {
				$hiddenFields[]='<input type="hidden" name="TABLE[c]['.(($k+1)*2).'][content]" value="'.htmlspecialchars(trim($v)).'">';
			}
		}
	
		if ($special=="formtype_mail")	{
	//debug($specParts);
				// SUbject
			$tRows[]='<tr><td colspan=4>&nbsp;</td></tr>';
				// SUbject
			$tRows[]='<tr><td colspan=2 bgColor="'.$this->doc->bgColor2.'">&nbsp;</td><td colspan=2 bgColor="'.$this->doc->bgColor2.'"><strong>'.htmlspecialchars($LANG->getLL("forms_special_eform")).':</strong></td></tr>';
				// SUbject
			$tRows[]='<tr bgColor="'.$this->doc->bgColor5.'">
				<td>&nbsp;</td>
				<td bgColor="'.$this->doc->bgColor4.'">&nbsp;</td>
				<td>'.$LANG->getLL("forms_eform_formtype_mail").':</td>
				<td><input type="hidden" name="TABLE[c]['.(1000*2).'][fieldname]" value="formtype_mail"><input type="hidden" name="TABLE[c]['.(1000*2).'][type]" value="submit"><input type="text"'.$this->doc->formWidth(15).' name="TABLE[c]['.(1000*2).'][default]" value="'.htmlspecialchars($specParts["formtype_mail"]).'"></td>
				</tr>';
			$tRows[]='<tr bgColor="'.$this->doc->bgColor5.'">
				<td>&nbsp;</td>
				<td bgColor="'.$this->doc->bgColor4.'">&nbsp;</td>
				<td>'.$LANG->getLL("forms_eform_html_enabled").':</td>
				<td><input type="hidden" name="TABLE[c]['.(1001*2).'][fieldname]" value="html_enabled"><input type="hidden" name="TABLE[c]['.(1001*2).'][type]" value="hidden"><input type="checkbox" name="TABLE[c]['.(1001*2).'][default]" value="1"'.($specParts["html_enabled"]?" CHECKED":"").'></td>
				</tr>';
			$tRows[]='<tr bgColor="'.$this->doc->bgColor5.'">
				<td>&nbsp;</td>
				<td bgColor="'.$this->doc->bgColor4.'">&nbsp;</td>
				<td>'.$LANG->getLL("forms_eform_subject").':</td>
				<td><input type="hidden" name="TABLE[c]['.(1002*2).'][fieldname]" value="subject"><input type="hidden" name="TABLE[c]['.(1002*2).'][type]" value="hidden"><input type="text"'.$this->doc->formWidth(15).' name="TABLE[c]['.(1002*2).'][default]" value="'.htmlspecialchars($specParts["subject"]).'"></td>
				</tr>';
			$tRows[]='<tr bgColor="'.$this->doc->bgColor5.'">
				<td>&nbsp;</td>
				<td bgColor="'.$this->doc->bgColor4.'">&nbsp;</td>
				<td>'.$LANG->getLL("forms_eform_recipient").':</td>
				<td><input type="text"'.$this->doc->formWidth(15).' name="TABLE[recipient]" value="'.htmlspecialchars($row["subheader"]).'"></td>
				</tr>';
		}
		
		
			// 
		$content = '<table border=0 cellpadding=1 cellspacing=1>'.implode("",$tRows).'</table>';
		
		$closeUrl = $P["returnUrl"];
	
		$content.= '<BR>';
		$content.= '<input type="image" border=0 name="savedok" src="gfx/savedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDoc"),1).' align=top>';
		$content.= '<input type="image" border=0 name="saveandclosedok" src="gfx/saveandclosedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc"),1).' align=top>';
		$content.= '<a href="#" onClick="jumpToUrl(unescape(\''.rawurlencode($closeUrl).'\')); return false;"><img border=0 src="gfx/closedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.closeDoc"),1).' align=top></a>';
		$content.= '<input type="image" name="_refresh" src="gfx/refresh_n.gif" width="14" height="14" hspace=10 border="0"'.t3lib_BEfunc::titleAttrib($LANG->getLL("forms_refresh")).'>';
		$content.= implode("",$hiddenFields);
		
		return $content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_forms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/wizard_forms.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_wizard_forms');
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();
?>