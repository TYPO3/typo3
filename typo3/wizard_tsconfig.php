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
 * Wizard for inserting TSconfig in form fields. (page,user or TS)
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");
include ("sysext/lang/locallang_wizards.php");
require_once (PATH_t3lib."class.t3lib_parsehtml.php");
require_once (PATH_t3lib."class.t3lib_tstemplate.php");
require_once (PATH_t3lib."class.t3lib_tsparser_ext.php");



	
// ***************************
// Script Classes
// ***************************
class ext_TSparser extends t3lib_tsparser_ext {
	function makeHtmlspecialchars($P)	{
		return $P["_LINK"];
	}
}
class SC_wizard_tsconfig {
	var $content;
	var $P;
	var $mode;
	var $show;
	var $doc;	
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		t3lib_extMgm::isLoaded("tsconfig_help",1);

		$this->P = t3lib_div::GPvar("P",1);

		if (!is_array($this->P["fieldChangeFunc"]))	$this->P["fieldChangeFunc"]=array();
		unset($this->P["fieldChangeFunc"]["alert"]);
		reset($this->P["fieldChangeFunc"]);
		$update="";
		while(list($k,$v)=each($this->P["fieldChangeFunc"]))	{
			$update.= "
			window.opener.".$v;
		}
		
		
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="" name="editform">';
		$this->doc->JScode='
		<style>
			A:link {text-decoration: bold; color: '.$this->doc->hoverColor.';}
			A:visited {text-decoration: bold; color: '.$this->doc->hoverColor.';}
			A:active {text-decoration: bold; color: '.$this->doc->hoverColor.';}
			A:hover {color: '.$this->doc->bgColor2.'}
		</style>
		<script language="javascript" type="text/javascript">
			function checkReference()	{
				if (window.opener && window.opener.document && window.opener.document.'.$this->P["formName"].' && window.opener.document.'.$this->P["formName"].'["'.$this->P["itemName"].'"] )	{
					return window.opener.document.'.$this->P["formName"].'["'.$this->P["itemName"].'"];
				} else {
					close();
				}
			}
			function setValue(input)	{
				var field = checkReference();
				if (field)	{
					field.value=input+"\n"+field.value;
					'.$update.'
					window.opener.focus();
				}
				close();
			}
			function getValue()	{
				var field = checkReference();
				if (field)	return field.value;
			}
			function mixerField(cmd,objString)	{
				var temp;
				switch(cmd)	{
					case "Indent":
						temp = str_replace("\n","\n  ","\n"+document.editform.mixer.value);
						document.editform.mixer.value = temp.substr(1);
					break;
					case "Outdent":
						temp = str_replace("\n  ","\n","\n"+document.editform.mixer.value);
						document.editform.mixer.value = temp.substr(1);
					break;
					case "Transfer":
						setValue(document.editform.mixer.value);
					break;
					case "Wrap":
						document.editform.mixer.value=objString+" {\n"+document.editform.mixer.value+"\n}";
					break;
				}
			}
			function str_replace(match,replace,string)	{
				var input = ""+string;
				var matchStr = ""+match;
				if (!matchStr)	{return string;}
				var output = "";
				var pointer=0;
				var pos = input.indexOf(matchStr);
				while (pos!=-1)	{
					output+=""+input.substr(pointer, pos-pointer)+replace;
					pointer=pos+matchStr.length;
					pos = input.indexOf(match,pos+1);
				}
				output+=""+input.substr(pointer);
				return output;
			}
			function jump(show,objString)	{
				document.location = "'.t3lib_div::linkThisScript(array("show"=>"","objString"=>"")).'&show="+show+"&objString="+objString;
			}
		</script>
		';
		
		
		$this->content.=$this->doc->startPage($LANG->getLL("tsprop"));
		$this->mode = t3lib_div::GPvar("mode");
		$this->show = t3lib_div::GPvar("show");
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content.=$this->doc->section($LANG->getLL("tsprop"),$this->browseTSprop($this->mode,$this->show),0,1);
		if ($this->mode=="tsref")	{
			$this->content.=$this->doc->section($LANG->getLL("tsprop_TSref"),'
			<a href="'.$BACK_PATH.t3lib_extMgm::extRelPath("tsconfig_help").'man/TSref.html">'.$LANG->getLL("tsprop_localHTML").'</a><BR>
			<a href="http://www.typo3.com/doclink.php?doc=tsref" target="_blank">'.$LANG->getLL("tsprop_pdf").'</a>
			',0,1);
		}
		if ($this->mode=="page" || $this->mode=="beuser")	{
			$this->content.=$this->doc->section($LANG->getLL("tsprop_adminguide"),'
			<a href="'.$BACK_PATH.t3lib_extMgm::extRelPath("tsconfig_help").'man/adminguide.html">'.$LANG->getLL("tsprop_localHTML").'</a><BR>
			<a href="http://www.typo3.com/doclink.php?doc=adminguide" target="_blank">'.$LANG->getLL("tsprop_pdf").'</a>
			',0,1);
		}
	}
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************
	function revertFromSpecialChars($str)	{
		$str = str_replace("&gt;",">",$str);
		$str = str_replace("&lt;","<",$str);
		return $str;
	}
	function getObjTree($whichman="")	{
		$hash = md5("WIZARD_TSCONFIG-objTree");
	
		$query="SELECT uid,obj_string,title FROM static_tsconfig_help".($whichman?" WHERE guide='".addslashes($whichman)."'":"");
		$res = mysql(TYPO3_db,$query);
		$objTree=array();
		while($rec=mysql_fetch_assoc($res))	{
			$rec["obj_string"] = $this->revertFromSpecialChars($rec["obj_string"]);
			$p = explode(";",$rec["obj_string"]);
			while(list(,$v)=each($p))	{
				$p2 = t3lib_div::trimExplode(":",$v,1);
				$subp=t3lib_div::trimExplode("/",$p2[1],1);
				while(list(,$v2)=each($subp))	{
					$this->setObj($objTree,explode(".",$p2[0].".".$v2),array($rec,$v2));
				}
			}
		}
		return $objTree;
	}
	function setObj(&$objTree,$strArr,$params)	{
		$key = current($strArr);
		reset($strArr);
		if (count($strArr)>1)	{
			array_shift($strArr);
			if (!isset($objTree[$key."."]))	$objTree[$key."."]=array();
			$this->setObj($objTree[$key."."],$strArr,$params);
		} else {
			$objTree[$key]=$params;
			$objTree[$key]["_LINK"]=$this->doLink($params);
		}
	}
	function doLink($params)	{
		$title = htmlspecialchars(trim($params[0]["title"])?trim($params[0]["title"]):"[GO]");
		$str = $this->linkToObj($title,$params[0]["uid"],$params[1]);
		return $str;
	}
	function browseTSprop($mode,$show)	{
			$whichman="adminguide";
			
			$objTree = $this->getObjTree("");
			$out="";
			if ($show)	{
				$query="SELECT * FROM static_tsconfig_help WHERE uid='".intval($show)."'";
				$res = mysql(TYPO3_db,$query);
				$rec=mysql_fetch_assoc($res);
				$table = unserialize($rec["appdata"]);
				$obj_string = strtr(t3lib_div::GPvar("objString"),"()","[]");
				
				$out.='<a href="'.t3lib_div::linkThisScript(array("show"=>"")).'" class="typo3-goBack"><img src="gfx/goback.gif" width="14" height="14" hspace=2 border="0" align=top>';
				$out.= '<font size=2 color="black"><strong>'.$obj_string.'</strong></font></a><BR>';
				if ($rec["title"])	$out.= '<strong>'.$rec["title"].': </strong>';
				if ($rec["description"])	$out.= nl2br(trim($rec["description"])).'<BR>';
				
				$out.= '<BR>'.$this->printTable($table, $obj_string, $objTree[$mode."."]);
				$out.="<HR>";
		
				if (!t3lib_div::GPvar("onlyProperty"))	{
					$links=array();
					$links[]='<a href="#" onClick="mixerField(\'Indent\');return false;">Indent +2</a>';
					$links[]='<a href="#" onClick="mixerField(\'Outdent\');return false;">Outdent -2</a>';
					$links[]='<a href="#" onClick="mixerField(\'Wrap\',unescape(\''.rawurlencode($obj_string).'\'));return false;">Wrap</a>';
					$links[]='<a href="#" onClick="mixerField(\'Transfer\');return false;">Transfer & Close</a>';
					$out.='<textarea rows="5" name="mixer" wrap="off"'.$this->doc->formWidthText(48,"","off").'></textarea>';
					$out.='<BR><strong>'.implode("&nbsp; | &nbsp;",$links).'</strong>';
					$out.="<HR>";
				}
			} 
		
			$tmpl = t3lib_div::makeInstance("ext_TSparser");
			$tmpl->tt_track = 0;	// Do not log time-performance information
			$tmpl->fixedLgd=0;
			$tmpl->linkObjects=0;
			$tmpl->bType="";
			$tmpl->ext_expandAllNotes=1;
			$tmpl->ext_noPMicons=1;
			$tmpl->ext_noSpecialCharsOnLabels=1;
		
			if (is_array($objTree[$mode."."]))	{
				$out.='<table border=0 cellpadding=0 cellspacing=0><tr><td nowrap>'.$tmpl->ext_getObjTree($this->removePointerObjects($objTree[$mode."."]),"","").'</td></tr></table>';
			}
		return $out;
	}
	function removePointerObjects($objArray)	{
		reset($objArray);
		while(list($k)=each($objArray))	{
			if (substr(trim($k),0,2)=="->" && trim($k)!="->.")	{
				$objArray["->."][substr(trim($k),2)]=$objArray[$k];
				unset($objArray[$k]);
			}
		}
	//	debug($objArray);
		return $objArray;
	}
	function linkToObj($str,$uid,$objString="")	{
		return '<a href="#" onClick="jump(\''.rawurlencode($uid).'\',\''.rawurlencode($objString).'\');return false;">'.$str.'</a>';
	}
	function printTable($table,$objString,$objTree)	{
		if (is_array($table["rows"]))	{
			$lines=array();
	
				$lines[]='<tr>
						<td><img src=clear.gif width=175 height=1></td>
						<td><img src=clear.gif width=100 height=1></td>
						<td><img src=clear.gif width=400 height=1></td>
						<td><img src=clear.gif width=70 height=1></td>
					</tr>';
				$lines[]='<tr bgcolor="'.$this->doc->bgColor5.'">
						<td><strong>Property:</strong></td>
						<td><strong>Data type:</strong></td>
						<td><strong>Description:</strong></td>
						<td><strong>Default:</strong></td>
					</tr>';
	
					
			reset($table["rows"]);
			while(list(,$row)=each($table["rows"]))	{
					// Linking:
				$lP=t3lib_div::trimExplode(chr(10),$row["property"],1);
				$lP2=array();
				while(list($k,$lStr)=each($lP))	{
					$lP2[$k] = $this->linkProperty($lStr,$lStr,$objString,$row["datatype"]);
				}
				$linkedProperties=implode("<HR>",$lP2);
				
					// Data type:
#				$dataType = $this->revertFromSpecialChars($row["datatype"]);			// 260902: Removed because it make HTML-tags back to HTML tags - while they should stay non-tags (eg imgs)
				$dataType = $row["datatype"];
					
					// Generally "->[something]"
				$reg=array();
				ereg("->[[:alnum:]_]*",$dataType,$reg);
				if ($reg[0] && is_array($objTree[$reg[0]]))	{
	//				debug($objTree[$reg[0]]);
					$dataType = str_replace($reg[0],'<a href="'.t3lib_div::linkThisScript(array("show"=>$objTree[$reg[0]][0]["uid"],"objString"=>$objString.".".$lP[0])).'">'.$reg[0].'</a>',$dataType);
				}
					// stdWrap
				if (!strstr($dataType,"->stdWrap") && strstr(strip_tags($dataType),"stdWrap"))	{
						// Potential problem can be that "stdWrap" is substituted inside another A-tag. So maybe we should even check if there is already a <A>-tag present and if so, not make a substitution?
					$dataType = str_replace("stdWrap",'<a href="'.t3lib_div::linkThisScript(array("show"=>$objTree["->stdWrap"][0]["uid"],"objString"=>$objString.".".$lP[0])).'">stdWrap</a>',$dataType);
				}
	
			
				$lines[]='<tr bgColor="'.$GLOBALS["TBE_TEMPLATE"]->bgColor4.'">
					<td valign=top bgColor="'.t3lib_div::modifyHtmlColor($GLOBALS["TBE_TEMPLATE"]->bgColor4,-20,-20,-20).'"><strong>'.$linkedProperties.'</strong></td>
					<td valign=top>'.nl2br($dataType."&nbsp;").'</td>
					<td valign=top>'.nl2br($row["description"]).'</td>
					<td valign=top>'.nl2br($row["default"]).'</td>
					</tr>';
			}
			return '<table border=0 cellpadding=0 cellspacing=1 width=500>'.implode("",$lines).'</table>';
		}
	}
	function linkProperty($str,$propertyVal,$prefix,$datatype)	{
		$out="";
		if (strstr($datatype,"boolean"))	{
			$propertyVal.="=1";	// add preset "=1" to boolean values.
		} else {
			$propertyVal.="=";	// add preset "="
		}
	
		if(!t3lib_div::GPvar("onlyProperty"))	{
			$out.= '<a href="#" onClick="document.editform.mixer.value=unescape(\'  '.rawurlencode($propertyVal).'\')+\'\n\'+document.editform.mixer.value; return false;"><img src="gfx/plusbullet2.gif" width="18" height="16" border="0" title="Add to list..." align=top></a>';
			$propertyVal = $prefix.".".$propertyVal;
		}
		$out.= '<a href="#" onClick="setValue(unescape(\''.rawurlencode($propertyVal).'\')); return false;">'.$str.'</a>';
		return $out;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_tsconfig.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/wizard_tsconfig.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_wizard_tsconfig");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>