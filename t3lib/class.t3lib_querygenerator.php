<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2001-2004 Christian Jul Jensen (christian@typo3.com)
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
 * Class for generating front end for building queries
 *
 * $Id$
 *
 * @author	Christian Jul Jensen <christian@typo3.com>
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   95: class t3lib_queryGenerator	
 *  176:     function makeFieldList()	
 *  203:     function init($name,$table,$fieldList="")	
 *  292:     function setAndCleanUpExternalLists($name,$list,$force="")	
 *  308:     function procesData($qC="")	
 *  416:     function cleanUpQueryConfig($queryConfig)	
 *  473:     function getFormElements($subLevel=0,$queryConfig="",$parent="")	
 *  560:     function printCodeArray($codeArr,$l=0)	
 *  583:     function formatQ($str)	
 *  596:     function mkOperatorSelect($name,$op,$draw,$submit)	
 *  618:     function mkTypeSelect($name,$fieldName,$prepend="FIELD_")	
 *  637:     function verifyType($fieldName)	
 *  654:     function verifyComparison($comparison,$neg)	
 *  673:     function mkFieldToInputSelect($name,$fieldName)	
 *  694:     function mkTableSelect($name,$cur)	
 *  716:     function mkCompSelect($name,$comparison,$neg)	
 *  734:     function getSubscript($arr) 
 *  749:     function initUserDef()	
 *  758:     function userDef()	
 *  767:     function userDefCleanUp($queryConfig)	
 *  778:     function getQuery ($queryConfig,$pad="") 
 *  808:     function getQuerySingle($conf,$first)	
 *  829:     function cleanInputVal($conf,$suffix="")	
 *  848:     function getUserDefQuery ($qcArr) 
 *  856:     function updateIcon()	
 *  865:     function getLabelCol()	
 *  877:     function makeSelectorTable($modSettings,$enableList="table,fields,query,group,order,limit")	
 *  981:     function getSelectQuery($qString="")	
 * 1001:     function JSbottom($formname="forms[0]")	
 * 1007:     function typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue)	
 * 1025:     function typo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off)	
 *
 * TOTAL FUNCTIONS: 30
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */ 











/**
 * Class for generating front end for building queries
 *
 * @author	Christian Jul Jensen <christian@typo3.com>
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_queryGenerator	{
	var $lang = array(
		"OR" => "or",
		"AND" => "and",
		"comparison" => array(
				 // Type = text	offset = 0	
			"0_" => "contains",
			"1_" => "does not contain",
			"2_" => "starts with",
			"3_" => "does not start with",
			"4_" => "ends with",
			"5_" => "does not end with",
			"6_" => "equals",
			"7_" => "does not equal",
				 // Type = date,number ,	offset = 32
			"32_" => "equals",
			"33_" => "does not equal",
			"34_" => "is greater than",
			"35_" => "is less than",
			"36_" => "is between",
			"37_" => "is not between",
			"38_" => "is in list",
			"39_" => "is not in list",
			"40_" => "binary AND equals",
			"41_" => "binary AND does not equal",
			"42_" => "binary OR equals",
			"43_" => "binary OR does not equal"
		)
	);
	
	var $compSQL = array(
			// Type = text	offset = 0	
		"0" => "#FIELD# LIKE '%#VALUE#%'",
		"1" => "#FIELD# NOT LIKE '%#VALUE#%'",
		"2" => "#FIELD# LIKE '#VALUE#%'",
		"3" => "#FIELD# NOT LIKE '#VALUE#%'",
		"4" => "#FIELD# LIKE '%#VALUE#'",
		"5" => "#FIELD# NOT LIKE '%#VALUE#'",
		"6" => "#FIELD# = '#VALUE#'",
		"7" => "#FIELD# != '#VALUE#'",
			// Type = date,number ,	offset = 32
		"32" => "#FIELD# = '#VALUE#'",
		"33" => "#FIELD# != '#VALUE#'",
		"34" => "#FIELD# > #VALUE#",
		"35" => "#FIELD# < #VALUE#",
		"36" => "#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#",
		"37" => "NOT (#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#)",
		"38" => "#FIELD# IN (#VALUE#)",
		"39" => "#FIELD# NOT IN (#VALUE#)",
		"40" => "(#FIELD# & #VALUE#)=#VALUE#",
		"41" => "(#FIELD# & #VALUE#)!=#VALUE#",
		"42" => "(#FIELD# | #VALUE#)=#VALUE#",
		"43" => "(#FIELD# | #VALUE#)!=#VALUE#"
	);
	
	var $comp_offsets = array(
		"text" => 0,
		"number" => 1,
		"date" => 1
	);
	var $noWrap=" nowrap";
	
	var $name;			// Form data name prefix
	var $table;			// table for the query
	var $fieldList;		// field list
	var $fields = array();	// Array of the fields possible
	var $extFieldLists = array();
	var $queryConfig=array(); // The query config
	var $enablePrefix=0;
	var $enableQueryParts = 0;
	var $extJSCODE="";







	/**
	 * @return	[type]		...
	 */
	function makeFieldList()	{
		global $TCA;
		$fieldListArr = array();
		if (is_array($TCA[$this->table]))	{
			t3lib_div::loadTCA($this->table);
			reset($TCA[$this->table]["columns"]);
			while(list($fN)=each($TCA[$this->table]["columns"]))	{
				$fieldListArr[]=$fN;
			}
			$fieldListArr[]="uid";
			$fieldListArr[]="pid";
			if ($TCA[$this->table]["ctrl"]["tstamp"])	$fieldListArr[]=$TCA[$this->table]["ctrl"]["tstamp"];
			if ($TCA[$this->table]["ctrl"]["crdate"])	$fieldListArr[]=$TCA[$this->table]["ctrl"]["crdate"];
			if ($TCA[$this->table]["ctrl"]["cruser_id"])	$fieldListArr[]=$TCA[$this->table]["ctrl"]["cruser_id"];
			if ($TCA[$this->table]["ctrl"]["sortby"])	$fieldListArr[]=$TCA[$this->table]["ctrl"]["sortby"];
		}
		return implode(",",$fieldListArr);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$fieldList: ...
	 * @return	[type]		...
	 */
	function init($name,$table,$fieldList="")	{
		global $TCA;

			// Analysing the fields in the table.
		if (is_array($TCA[$table]))	{
			t3lib_div::loadTCA($table);
			$this->name = $name;
			$this->table = $table;
			$this->fieldList = $fieldList ? $fieldList : $this->makeFieldList();

			$fieldArr = t3lib_div::trimExplode(",",$this->fieldList,1);
			reset($fieldArr);
			while(list(,$fN)=each($fieldArr))	{
				$fC = $TCA[$this->table]["columns"][$fN];
				if (is_array($fC) && $fC["label"])	{
					$this->fields[$fN]["label"] = ereg_replace(":$","",trim($GLOBALS["LANG"]->sL($fC["label"])));
					switch($fC["config"]["type"])	{
						case "input":
							if (eregi("int|year",$fC["config"]["eval"]))	{
								$this->fields[$fN]["type"]="number";
							} elseif (eregi("date|time",$fC["config"]["eval"]))	{
								$this->fields[$fN]["type"]="date";
							} else {
								$this->fields[$fN]["type"]="text";
							}
						break;
						case "check":
						case "select":
							$this->fields[$fN]["type"]="number";
						break;
						case "text":
						default:
							$this->fields[$fN]["type"]="text";
						break;
					}
				
				} else {
					$this->fields[$fN]["label"]="[FIELD: ".$fN."]";
					$this->fields[$fN]["type"]="number";
				}
			}
		}
		
		/*	// EXAMPLE:
		$this->queryConfig = array(
			array(
				"operator" => "AND",
				"type" => "FIELD_spaceBefore",
			),
			array(
				"operator" => "AND",
				"type" => "FIELD_records",
				"negate" => 1,
				"inputValue" => "foo foo"
			),
			array( 
				"type" => "newlevel",
				"nl" => array(
					array(
						"operator" => "AND",
						"type" => "FIELD_spaceBefore",
						"negate" => 1,
						"inputValue" => "foo foo"
					),
					array(
						"operator" => "AND",
						"type" => "FIELD_records",
						"negate" => 1,
						"inputValue" => "foo foo"
					)
				)
			),
			array(
				"operator" => "OR",
				"type" => "FIELD_maillist",
			)
		);
		*/
		$this->initUserDef();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$list: ...
	 * @param	[type]		$force: ...
	 * @return	[type]		...
	 */
	function setAndCleanUpExternalLists($name,$list,$force="")	{
		$fields = array_unique(t3lib_div::trimExplode(",",$list.",".$force,1));
		reset($fields);
		$reList=array();
		while(list(,$fN)=each($fields))	{
			if ($this->fields[$fN])		$reList[]=$fN;
		}
		$this->extFieldLists[$name]=implode(",",$reList);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$qC: ...
	 * @return	[type]		...
	 */
	function procesData($qC="")	{
		$this->queryConfig = $qC;
	
		$POST = t3lib_div::_POST();
/*	 	// adjust queryConfig to match requests from user
		if($GLOBALS["HTTP_POST_VARS"]["queryConfig"]) {
			$this->queryConfig = $GLOBALS["HTTP_POST_VARS"]["queryConfig"];
			t3lib_div::stripSlashesOnArray($this->queryConfig);
		}
*/
		// if delete...
		if($POST["qG_del"]) {
			//initialize array to work on, save special parameters
			$ssArr = $this->getSubscript($POST["qG_del"]);
			$workArr =& $this->queryConfig;
			for($i=0;$i<sizeof($ssArr)-1;$i++) {
				$workArr =& $workArr[$ssArr[$i]];
			}
			// delete the entry and move the other entries
			unset($workArr[$ssArr[$i]]);
			for($j=$ssArr[$i];$j<sizeof($workArr);$j++) {
				$workArr[$j] = $workArr[$j+1];
				unset($workArr[$j+1]);
			}
		}

		// if insert...
		if($POST["qG_ins"]) {
			//initialize array to work on, save special parameters
			$ssArr = $this->getSubscript($POST["qG_ins"]);
			$workArr =& $this->queryConfig;
			for($i=0;$i<sizeof($ssArr)-1;$i++) {
				$workArr =& $workArr[$ssArr[$i]];
			}
			// move all entries above position where new entry is to be inserted
			for($j=sizeof($workArr);$j>$ssArr[$i];$j--) {
				$workArr[$j] = $workArr[$j-1];
			}
			//clear new entry position
			unset($workArr[$ssArr[$i]+1]);
			$workArr[$ssArr[$i]+1]['type'] = "FIELD_";
		}

		// if move up...
		if($POST["qG_up"]) {
			//initialize array to work on
			$ssArr = $this->getSubscript($POST["qG_up"]);
			$workArr =& $this->queryConfig;
			for($i=0;$i<sizeof($ssArr)-1;$i++) {
				$workArr =& $workArr[$ssArr[$i]];
			}
			//swap entries
			$qG_tmp = $workArr[$ssArr[$i]];
			$workArr[$ssArr[$i]] = $workArr[$ssArr[$i]-1];
			$workArr[$ssArr[$i]-1] = $qG_tmp;
		}

		// if new level...
		if($POST["qG_nl"]) {
			//initialize array to work on
			$ssArr = $this->getSubscript($POST["qG_nl"]);
			$workArr =& $this->queryConfig;
			for($i=0;$i<sizeof($ssArr)-1;$i++) {
				$workArr =& $workArr[$ssArr[$i]];
			}
			// Do stuff:
			$tempEl = $workArr[$ssArr[$i]];
			if (is_array($tempEl))	{
				if ($tempEl["type"]!="newlevel")	{
					$workArr[$ssArr[$i]]=array(
						"type" => "newlevel",
						"operator" => $tempEl["operator"],
						"nl" => array($tempEl)
					);
				}
			}
		}

		// if collapse level...
		if($POST["qG_remnl"]) {
			//initialize array to work on
			$ssArr = $this->getSubscript($POST["qG_remnl"]);
			$workArr =& $this->queryConfig;
			for($i=0;$i<sizeof($ssArr)-1;$i++) {
				$workArr =& $workArr[$ssArr[$i]];
			}

			// Do stuff:
			$tempEl = $workArr[$ssArr[$i]];
			if (is_array($tempEl))	{
				if ($tempEl["type"]=="newlevel")	{
					$a1 = array_slice($workArr,0,$ssArr[$i]);
					$a2 = array_slice($workArr,$ssArr[$i]);
					array_shift($a2);
					$a3 = $tempEl["nl"];
					$a3[0]["operator"] = $tempEl["operator"];
					$workArr=array_merge($a1,$a3,$a2);
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$queryConfig: ...
	 * @return	[type]		...
	 */
	function cleanUpQueryConfig($queryConfig)	{
		//since we dont traverse the array using numeric keys in the upcoming whileloop make sure it's fresh and clean before displaying
		if (is_array($queryConfig))	{
			ksort($queryConfig);
		} else {
			//queryConfig should never be empty!
			if(!$queryConfig[0] || !$queryConfig[0]["type"]) $queryConfig[0] = array("type"=>"FIELD_");
		}
			// Traverse:
		reset($queryConfig);
		$c=0;
		$arrCount=0;
		while(list($key,$conf)=each($queryConfig))	{
			if(substr($conf["type"],0,6)=="FIELD_") {
				$fName = substr($conf["type"],6);
				$fType = $this->fields[$fName]["type"];
			} elseif($conf["type"]=="newlevel") {
				$fType = $conf["type"];
			} else {
				$fType = "ignore";
			}
//			debug($fType);
			switch($fType)	{
				case "newlevel":
					if(!$queryConfig[$key]["nl"]) $queryConfig[$key]["nl"][0]["type"] = "FIELD_";
					$queryConfig[$key]["nl"]=$this->cleanUpQueryConfig($queryConfig[$key]["nl"]);
				break;
				case "userdef":
					$queryConfig[$key]=$this->userDefCleanUp($queryConfig[$key]);
				break;
				case "ignore":
				default:
//					debug($queryConfig[$key]);
					$verifiedName=$this->verifyType($fName);
					$queryConfig[$key]["type"]="FIELD_".$this->verifyType($verifiedName);

					if($conf["comparison"] >> 5 != $this->comp_offsets[$fType]) $conf["comparison"] = $this->comp_offsets[$fType] << 5;
					$queryConfig[$key]["comparison"]=$this->verifyComparison($conf["comparison"],$conf["negate"]?1:0);

					$queryConfig[$key]["inputValue"]=$this->cleanInputVal($queryConfig[$key]);
					$queryConfig[$key]["inputValue1"]=$this->cleanInputVal($queryConfig[$key],1);

//					debug($queryConfig[$key]);
			break;
			}
		}
		return $queryConfig;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$subLevel: ...
	 * @param	[type]		$queryConfig: ...
	 * @param	[type]		$parent: ...
	 * @return	[type]		...
	 */
	function getFormElements($subLevel=0,$queryConfig="",$parent="")	{
		$codeArr=array();
		if (!is_array($queryConfig))	$queryConfig=$this->queryConfig;

		reset($queryConfig);
		$c=0;
		$arrCount=0;
		while(list($key,$conf)=each($queryConfig))	{
			$subscript = $parent."[$key]";
			$lineHTML = "";
			$lineHTML.=$this->mkOperatorSelect($this->name.$subscript,$conf["operator"],$c,($conf["type"]!="FIELD_"));
			if(substr($conf["type"],0,6)=="FIELD_") {
				$fName = substr($conf["type"],6);
				$fType = $this->fields[$fName]["type"];
				if($conf["comparison"] >> 5 != $this->comp_offsets[$fType]) $conf["comparison"] = $this->comp_offsets[$fType] << 5;

				//nasty nasty... 
				//make sure queryConfig contains _actual_ comparevalue. 
				//mkCompSelect don't care, but getQuery does.
				$queryConfig[$key]["comparison"] += (isset($conf["negate"])-($conf["comparison"]%2));

			} elseif($conf["type"]=="newlevel") {
				$fType = $conf["type"];
			} else {
				$fType = "ignore";
			}
//			debug($fType);
			switch($fType)	{
				case "ignore":
				break;
				case "newlevel":
					if(!$queryConfig[$key]["nl"]) $queryConfig[$key]["nl"][0]["type"] = "FIELD_";
					$lineHTML.='<input type="hidden" name="'.$this->name.$subscript.'[type]" value="newlevel">';
					$codeArr[$arrCount]["sub"] = $this->getFormElements($subLevel+1,$queryConfig[$key]["nl"],$subscript."[nl]");
				break;
				case "userdef":
					 $lineHTML.=$this->userDef($this->name.$subscript,$conf,$fName,$fType);
				break;
				default:
					$lineHTML.=$this->mkTypeSelect($this->name.$subscript.'[type]',$fName);
					$lineHTML.=$this->mkCompSelect($this->name.$subscript.'[comparison]',$conf["comparison"],$conf["negate"]?1:0);
					$lineHTML.='<input type="checkbox" '.($conf["negate"]?"checked":"").' name="'.$this->name.$subscript.'[negate]'.'" onClick="submit();">';

					if ($conf["comparison"]==37 || $conf["comparison"]==36)	{	// between:
						$lineHTML.='<input type="text" value="'.htmlspecialchars($conf["inputValue"]).'" name="'.$this->name.$subscript.'[inputValue]'.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(5).'>
						<input type="text" value="'.htmlspecialchars($conf["inputValue1"]).'" name="'.$this->name.$subscript.'[inputValue1]'.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(5).'>
						';	// onChange="submit();"
					} elseif ($fType=="date") {
						$lineHTML.='<input type="text" name="'.$this->name.$subscript.'[inputValue]_hr'.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(10).' onChange="typo3FormFieldGet(\''.$this->name.$subscript.'[inputValue]\', \'datetime\', \'\', 0,0);"><input type="hidden" value="'.htmlspecialchars($conf["inputValue"]).'" name="'.$this->name.$subscript.'[inputValue]'.'">';
						$this->extJSCODE.='typo3FormFieldSet("'.$this->name.$subscript.'[inputValue]", "datetime", "", 0,0);';
					} else {					
						$lineHTML.='<input type="text" value="'.htmlspecialchars($conf["inputValue"]).'" name="'.$this->name.$subscript.'[inputValue]'.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(10).'>';	// onChange="submit();"
					}
				break;
			}
			if($fType != "ignore") {
				$lineHTML .= $this->updateIcon();
				$lineHTML .= '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/garbage.gif" class="absmiddle" width="11" height="12" hspace=3 vspace=3 title="Remove condition" name="qG_del'.$subscript.'">';
				$lineHTML .= '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/add.gif" class="absmiddle" width="12" height="12" hspace=3 vspace=3 title="Add condition" name="qG_ins'.$subscript.'">';
				if($c!=0) $lineHTML.= '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/pil2up.gif" class="absmiddle" width="12" height="7" hspace=3 vspace=3 title="Move up" name="qG_up'.$subscript.'">';

				if($c!=0 && $fType!="newlevel") {
					$lineHTML.= '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/pil2right.gif" class="absmiddle" height="12" width="7" hspace=3 vspace=3 title="New level" name="qG_nl'.$subscript.'">';
				}
				if($fType=="newlevel") {
					$lineHTML.= '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/pil2left.gif" class="absmiddle" height="12" width="7" hspace=3 vspace=3 title="Collapse new level" name="qG_remnl'.$subscript.'">';
				}

				$codeArr[$arrCount]["html"] = $lineHTML;
				$codeArr[$arrCount]["query"] = $this->getQuerySingle($conf,$c>0?0:1);
				$arrCount++;
				$c++;
			}
		}
//		$codeArr[$arrCount] .='<input type="hidden" name="CMD" value="displayQuery">';
		$this->queryConfig = $queryConfig;
//modifyHTMLColor($color,$R,$G,$B)
		return $codeArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$codeArr: ...
	 * @param	[type]		$l: ...
	 * @return	[type]		...
	 */
	function printCodeArray($codeArr,$l=0)	{
		reset($codeArr);
		$line="";
		if ($l)		$indent='<td><img height="1" width="50"></td>';
		$lf=$l*30;
		$bgColor = t3lib_div::modifyHTMLColor($GLOBALS["TBE_TEMPLATE"]->bgColor2,$lf,$lf,$lf);
		while(list($k,$v)=each($codeArr))	{
			$line.= '<tr>'.$indent.'<td bgcolor="'.$bgColor.'"'.$this->noWrap.'>'.$v["html"].'</td></tr>';
			if ($this->enableQueryParts)	{$line.= '<tr>'.$indent.'<td>'.$this->formatQ($v["query"]).'</td></tr>';}
			if (is_array($v["sub"]))	{
				$line.= '<tr>'.$indent.'<td'.$this->noWrap.'>'.$this->printCodeArray($v["sub"],$l+1).'</td></tr>';
			}
		}
		$out='<table border=0 cellpadding=0 cellspacing=1>'.$line.'</table>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function formatQ($str)	{
		return '<font size=1 face=verdana color=maroon><i>'.$str.'</i></font>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$op: ...
	 * @param	[type]		$draw: ...
	 * @param	[type]		$submit: ...
	 * @return	[type]		...
	 */
	function mkOperatorSelect($name,$op,$draw,$submit)	{
		if ($draw)	{
			$out='<select name="'.$name.'[operator]"'.($submit?' onChange="submit();"':'').'>';	// 
			$out.='<option value="AND"'.(!$op||$op=="AND" ? ' selected':'').'>'.$this->lang["AND"].'</option>';
			$out.='<option value="OR"'.($op=="OR" ? ' selected':'').'>'.$this->lang["OR"].'</option>';
			$out.='</select>';
		} else {
			$out.='<input type="hidden" value="'.$op.'" name="'.$name.'[operator]">';
			$out.='<img src="clear.gif" height="1" width="47">';

		}
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$fieldName: ...
	 * @param	[type]		$prepend: ...
	 * @return	[type]		...
	 */
	function mkTypeSelect($name,$fieldName,$prepend="FIELD_")	{
		$out='<select name="'.$name.'" onChange="submit();">';
		$out.='<option value=""></option>';
		reset($this->fields);
		while(list($key,)=each($this->fields)) {
			if ($GLOBALS["BE_USER"]->check("non_exclude_fields",$this->table.":".$key))	{
				$out.='<option value="'.$prepend.$key.'"'.($key==$fieldName ? ' selected':'').'>'.$this->fields[$key]["label"].'</option>';	
			}
		}
		$out.='</select>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$fieldName: ...
	 * @return	[type]		...
	 */
	function verifyType($fieldName)	{
		reset($this->fields);
		$first = "";
		while(list($key,)=each($this->fields)) {
			if (!$first)	$first = $key;
			if ($key==$fieldName) return $key;
		}
		return $first;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$comparison: ...
	 * @param	[type]		$neg: ...
	 * @return	[type]		...
	 */
	function verifyComparison($comparison,$neg)	{
		$compOffSet = $comparison >> 5;
		$first=-1;
		for($i=32*$compOffSet+$neg;$i<32*($compOffSet+1);$i+=2) {
			if ($first==-1)	$first = $i;
			if (($i >> 1)==($comparison >> 1))	{
				return $i;
			}
		}
		return $first;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$fieldName: ...
	 * @return	[type]		...
	 */
	function mkFieldToInputSelect($name,$fieldName)	{
		$out='<input type="Text" value="'.htmlspecialchars($fieldName).'" name="'.$name.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth().'>'.$this->updateIcon();
		$out.='<a href="#" onClick="document.forms[0][\''.$name.'\'].value=\'\';return false;"><img src="'.$GLOBALS["BACK_PATH"].'gfx/garbage.gif" class="absmiddle" width="11" height="12" hspace=3 vspace=3 title="Clear list" border=0></a>';
		$out.='<BR><select name="_fieldListDummy" size=5 onChange="document.forms[0][\''.$name.'\'].value+=\',\'+this.value">';
		reset($this->fields);
		while(list($key,)=each($this->fields)) {
			if ($GLOBALS["BE_USER"]->check("non_exclude_fields",$this->table.":".$key))	{
				$out.='<option value="'.$prepend.$key.'"'.($key==$fieldName ? ' selected':'').'>'.$this->fields[$key]["label"].'</option>';	
			}
		}
		$out.='</select>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$cur: ...
	 * @return	[type]		...
	 */
	function mkTableSelect($name,$cur)	{
		global $TCA;
		$out='<select name="'.$name.'" onChange="submit();">';
		$out.='<option value=""></option>';
		reset($TCA);
		while(list($tN)=each($TCA)) {
			if ($GLOBALS["BE_USER"]->check("tables_select",$tN))	{
				$out.='<option value="'.$tN.'"'.($tN==$cur ? ' selected':'').'>'.$GLOBALS["LANG"]->sl($TCA[$tN]["ctrl"]["title"]).'</option>';	
			}
		}
		$out.='</select>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$name: ...
	 * @param	[type]		$comparison: ...
	 * @param	[type]		$neg: ...
	 * @return	[type]		...
	 */
	function mkCompSelect($name,$comparison,$neg)	{
		$compOffSet = $comparison >> 5;
		$out='<select name="'.$name.'" onChange="submit();">';
		for($i=32*$compOffSet+$neg;$i<32*($compOffSet+1);$i+=2) {
			if($this->lang["comparison"][$i."_"]) {
				$out.='<option value="'.$i.'"'.(($i >> 1)==($comparison >> 1) ? ' selected':'').'>'.$this->lang["comparison"][$i."_"].'</option>';
			}
		}
		$out.='</select>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function getSubscript($arr) {
		while(is_array($arr)) {
			reset($arr);
			list($key,)=each($arr);
			$retArr[] = $key;
			$arr = $arr[$key];
		}
		return $retArr;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function initUserDef()	{
	
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function userDef()	{
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$queryConfig: ...
	 * @return	[type]		...
	 */
	function userDefCleanUp($queryConfig)	{
		return $queryConfig;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$queryConfig: ...
	 * @param	[type]		$pad: ...
	 * @return	[type]		...
	 */
	function getQuery ($queryConfig,$pad="") {
		$qs = "";
		//since wo dont traverse the array using numeric keys in the upcoming whileloop make sure it's fresh and clean
		ksort($queryConfig);
		reset($queryConfig);
		$first=1;
		while(list($key,$conf) = each($queryConfig)) {
			switch($conf["type"]) {
				case "newlevel": 
					$qs.=chr(10).$pad.trim($conf["operator"])." (".$this->getQuery($queryConfig[$key]["nl"],$pad."   ").chr(10).$pad.")";
				break;
				case "userdef":
					$qs.=chr(10).$pad.getUserDefQuery($conf,$first);
				break;
				default:			
					$qs.=chr(10).$pad.$this->getQuerySingle($conf,$first);
				break;
			}
			$first=0;
		}
		return $qs;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$conf: ...
	 * @param	[type]		$first: ...
	 * @return	[type]		...
	 */
	function getQuerySingle($conf,$first)	{
		$prefix = $this->enablePrefix ? $this->table."." : "";
		if (!$first)	{$qs.= trim(($conf["operator"]?$conf["operator"]:"AND"))." ";}		// Is it OK to insert the AND operator if none is set? 
		$qsTmp = str_replace("#FIELD#",$prefix.trim(substr($conf["type"],6)),$this->compSQL[$conf["comparison"]]);
		$inputVal = $this->cleanInputVal($conf);
		$qsTmp = str_replace("#VALUE#", $GLOBALS['TYPO3_DB']->quoteStr($inputVal, $this->table),$qsTmp);
		if ($conf["comparison"]==37 || $conf["comparison"]==36)	{	// between:
			$inputVal = $this->cleanInputVal($conf,"1");
			$qsTmp = str_replace("#VALUE1#", $GLOBALS['TYPO3_DB']->quoteStr($inputVal, $this->table),$qsTmp);
		}
		$qs .= trim($qsTmp);
		return $qs;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$conf: ...
	 * @param	[type]		$suffix: ...
	 * @return	[type]		...
	 */
	function cleanInputVal($conf,$suffix="")	{
		if(($conf["comparison"] >> 5==0) || ($conf["comparison"]==32 || $conf["comparison"]==33))	{
			$inputVal = $conf["inputValue".$suffix];
		} else {
			if ($conf["comparison"]==39 || $conf["comparison"]==38)	{	// in list:
				$inputVal = implode(",",t3lib_div::intExplode(",",$conf["inputValue".$suffix]));
			} else {
				$inputVal = doubleval($conf["inputValue".$suffix]);
			}
		}
		return $inputVal;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$qcArr: ...
	 * @return	[type]		...
	 */
	function getUserDefQuery ($qcArr) {
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function updateIcon()	{
		return '<input type="image" border=0 src="'.$GLOBALS["BACK_PATH"].'gfx/refresh_n.gif" class="absmiddle" width="14" height="14" hspace=3 vspace=3 title="Update" name="just_update">';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getLabelCol()	{
		global $TCA;
		return $TCA[$this->table]["ctrl"]["label"];
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$modSettings: ...
	 * @param	[type]		$enableList: ...
	 * @return	[type]		...
	 */
	function makeSelectorTable($modSettings,$enableList="table,fields,query,group,order,limit")	{
		$enableArr=explode(",",$enableList);
			// Make output
		$TDparams = ' class="bgColor5" nowrap';
		
		if (in_array("table",$enableArr))	{
			$out='
			<tr>
				<td'.$TDparams.'><strong>Select a table:</strong></td>
				<td'.$TDparams.'>'.$this->mkTableSelect("SET[queryTable]",$this->table).'</td>
			</tr>';
		}
		if ($this->table)	{
	
				// Init fields:
			$this->setAndCleanUpExternalLists("queryFields",$modSettings["queryFields"],"uid,".$this->getLabelCol());
			$this->setAndCleanUpExternalLists("queryGroup",$modSettings["queryGroup"]);
			$this->setAndCleanUpExternalLists("queryOrder",$modSettings["queryOrder"].",".$modSettings["queryOrder2"]);

				// Limit:
			$this->extFieldLists["queryLimit"]=$modSettings["queryLimit"];
			if (!$this->extFieldLists["queryLimit"])	$this->extFieldLists["queryLimit"]=100;
			$parts = t3lib_div::intExplode(",",$this->extFieldLists["queryLimit"]);
			$this->extFieldLists["queryLimit"] = implode(",",array_slice($parts,0,2));
			
				// Insert Descending parts
			if ($this->extFieldLists["queryOrder"])	{
				$descParts = explode(",",$modSettings["queryOrderDesc"].",".$modSettings["queryOrder2Desc"]);
				$orderParts = explode(",",$this->extFieldLists["queryOrder"]);
				reset($orderParts);
				$reList=array();
				while(list($kk,$vv)=each($orderParts))	{
					$reList[]=$vv.($descParts[$kk]?" DESC":"");
				}
				$this->extFieldLists["queryOrder_SQL"] = implode(",",$reList);
			}		
	
				// Query Generator:
			$this->procesData($modSettings["queryConfig"] ? unserialize($modSettings["queryConfig"]) : "");
	//		debug($this->queryConfig);
			$this->queryConfig = $this->cleanUpQueryConfig($this->queryConfig);
	//		debug($this->queryConfig);
			$this->enableQueryParts = $modSettings["search_query_smallparts"];
	
			$codeArr=$this->getFormElements();
			$queryCode=$this->printCodeArray($codeArr);
		
			if (in_array("fields",$enableArr))	{
				$out.='
				<tr>
					<td'.$TDparams.'><strong>Select fields:</strong></td>
					<td'.$TDparams.'>'.$this->mkFieldToInputSelect("SET[queryFields]",$this->extFieldLists["queryFields"]).'</td>
				</tr>';
			}
			if (in_array("query",$enableArr))	{
				$out.='<tr>
					<td colspan=2'.$TDparams.'><strong>Make Query:</strong></td>
				</tr>
				<tr>
					<td colspan=2>'.$queryCode.'</td>
				</tr>
				';
			}
			if (in_array("group",$enableArr))	{
				$out.='<tr>
					<td'.$TDparams.'><strong>Group By:</strong></td>
					<td'.$TDparams.'>'.$this->mkTypeSelect("SET[queryGroup]",$this->extFieldLists["queryGroup"],"").'</td>
				</tr>';
			}
			if (in_array("order",$enableArr))	{
				$orderByArr = explode(",",$this->extFieldLists["queryOrder"]);
		//		debug($orderByArr);
				$orderBy="";
				$orderBy.=$this->mkTypeSelect("SET[queryOrder]",$orderByArr[0],"").
				"&nbsp;".t3lib_BEfunc::getFuncCheck($GLOBALS["SOBE"]->id,"SET[queryOrderDesc]",$modSettings["queryOrderDesc"])."&nbsp;Descending";
				if ($orderByArr[0])	{
					$orderBy.= "<BR>".$this->mkTypeSelect("SET[queryOrder2]",$orderByArr[1],"").
					"&nbsp;".t3lib_BEfunc::getFuncCheck($GLOBALS["SOBE"]->id,"SET[queryOrder2Desc]",$modSettings["queryOrder2Desc"])."&nbsp;Descending";
				}
				$out.='<tr>
					<td'.$TDparams.'><strong>Order By:</strong></td>
					<td'.$TDparams.'>'.$orderBy.'</td>
				</tr>';
			}	
			if (in_array("limit",$enableArr))	{
				$limit = '<input type="Text" value="'.htmlspecialchars($this->extFieldLists["queryLimit"]).'" name="SET[queryLimit]"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(10).'>'.$this->updateIcon();
				$out.='<tr>
					<td'.$TDparams.'><strong>Limit:</strong></td>
					<td'.$TDparams.'>'.$limit.'</td>
				</tr>
				';
			}
		}
		$out='<table border=0 cellpadding=3 cellspacing=1>'.$out.'</table>';
		$out.=$this->JSbottom();
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$qString: ...
	 * @return	[type]		...
	 */
	function getSelectQuery($qString="")	{
		if (!$qString)	$qString=$this->getQuery($this->queryConfig);

		$query = $GLOBALS['TYPO3_DB']->SELECTquery(
						$this->extFieldLists["queryFields"], 
						$this->table, 
						$qString.t3lib_BEfunc::deleteClause($this->table),
						trim($this->extFieldLists["queryGroup"]),
						$this->extFieldLists["queryOrder"] ? trim($this->extFieldLists["queryOrder_SQL"]) : '',
						$this->extFieldLists["queryLimit"]
					);
		return $query;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$formname: ...
	 * @return	[type]		...
	 */
	function JSbottom($formname="forms[0]")	{
		if ($this->extJSCODE)	{
			$out.='
			<script language="javascript" type="text/javascript" src="'.$GLOBALS["BACK_PATH"].'t3lib/jsfunc.evalfield.js"></script>
			<script language="javascript" type="text/javascript">
				var evalFunc = new evalFunc;
				function typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue)	{
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					var theValue = document.'.$formname.'[theField].value;
					if (checkbox && theValue==checkboxValue)	{
						document.'.$formname.'[theField+"_hr"].value="";
						if (document.'.$formname.'[theField+"_cb"])	document.'.$formname.'[theField+"_cb"].checked = "";
					} else {
						document.'.$formname.'[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
						if (document.'.$formname.'[theField+"_cb"])	document.'.$formname.'[theField+"_cb"].checked = "on";
					}
				}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theField, evallist, is_in, checkbox, checkboxValue, checkbox_off: ...
	 * @return	[type]		...
	 */
				function typo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off)	{
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					if (checkbox_off)	{
						document.'.$formname.'[theField].value=checkboxValue;
					}else{
						document.'.$formname.'[theField].value = evalFunc.evalObjValue(theFObj, document.'.$formname.'[theField+"_hr"].value);
					}
					typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
				}
			</script>
			<script language="javascript" type="text/javascript">'.$this->extJSCODE.'</script>';
			return $out;	
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_querygenerator.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_querygenerator.php']);
}
?>
