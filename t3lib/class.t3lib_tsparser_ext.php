<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * TSParser extension class to t3lib_TStemplate
 *
 * $Id$
 * Contains functions for the TS module in TYPO3 backend
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  103: class t3lib_tsparser_ext extends t3lib_TStemplate	
 *  191:     function flattenSetup($setupArray, $prefix, $resourceFlag)	
 *  218:     function substituteConstants($all)	
 *  247:     function substituteCMarkers($all)	
 *  269:     function generateConfig_constants()	
 *  318:     function ext_getSetup($theSetup,$theKey)	
 *  349:     function ext_getObjTree($arr, $depth_in, $depthData, $parentType="",$parentValue="")	
 *  437:     function makeHtmlspecialchars($theValue)
 *  450:     function ext_getSearchKeys($arr, $depth_in, $searchString, $keyArray)		
 *  490:     function ext_getRootlineNumber($pid)	
 *  508:     function ext_getTemplateHierarchyArr($arr,$depthData, $keyArray,$first=0)	
 *  567:     function ext_process_hierarchyInfo($depthDataArr,&$pointer)	
 *  598:     function ext_outputTS($config, $lineNumbers=0, $comments=0, $crop=0, $syntaxHL=0, $syntaxHLBlockmode=0)	
 *  625:     function ext_fixed_lgd($string,$chars)	
 *  641:     function ext_lnBreakPointWrap($ln,$str)	
 *  654:     function ext_formatTS($input, $ln, $comments=1, $crop=0)	
 *  693:     function ext_getFirstTemplate($id,$template_uid=0)		
 *  713:     function ext_getAllTemplates($id)		
 *  734:     function ext_compareFlatSetups($default)	
 *  800:     function ext_categorizeEditableConstants($editConstArray)	
 *  823:     function ext_getCategoryLabelArray()	
 *  840:     function ext_getTypeData($type)	
 *  881:     function ext_getTSCE_config($category)	
 *  920:     function ext_getKeyImage($key)	
 *  930:     function ext_getTSCE_config_image($imgConf)	
 *  954:     function ext_resourceDims()	
 *  984:     function ext_readDirResources($path)	
 *  999:     function readDirectory($path,$type="file")	
 * 1024:     function ext_fNandV($params)	
 * 1042:     function ext_printFields($theConstants,$category)	
 *
 *              SECTION: Processing input values
 * 1299:     function ext_regObjectPositions($constants)	
 * 1314:     function ext_regObjects($pre)	
 * 1359:     function ext_putValueInConf($key, $var)	
 * 1382:     function ext_removeValueInConf($key)	
 * 1398:     function ext_depthKeys($arr,$settings)	
 * 1433:     function ext_procesInput($http_post_vars,$http_post_files,$theConstants,$tplRow)	
 * 1560:     function upload_copy_file($typeDat,&$tplRow,$theRealFileName,$tmp_name)	
 * 1601:     function ext_prevPageWithTemplate($id,$perms_clause)	
 * 1617:     function ext_setStar($val)	
 * 1629:     function ext_detectAndFixExtensionPrefix($value)	
 *
 * TOTAL FUNCTIONS: 39
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
require_once(PATH_t3lib."class.t3lib_tstemplate.php");







/**
 * TSParser extension class to t3lib_TStemplate
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tsparser_ext extends t3lib_TStemplate	{

	var $edit_divider = "###MOD_TS:EDITABLE_CONSTANTS###";		// This string is used to indicate the point in a template from where the editable constants are listed. Any vars before this point (if it exists though) is regarded as default values.
	var $HTMLcolorList = "aqua,black,blue,fuchsia,gray,green,lime,maroon,navy,olive,purple,red,silver,teal,yellow,white";	

		// internal
	var $categories = array(
		"basic" => array(),		// Constants of superior importance for the template-layout. This is dimensions, imagefiles and enabling of various features. The most basic constants, which you would almost always want to configure.
		"menu" => array(),		// Menu setup. This includes fontfiles, sizes, background images. Depending on the menutype.
		"content" => array(),	// All constants related to the display of pagecontent elements
		"page" => array(),		// General configuration like metatags, link targets
		"advanced" => array(),	// Advanced functions, which are used very seldomly.
		"all" => array()		// All constants are put here also!
	);		// This will be filled with the available categories of the current template.
	var $subCategories = array(
	// Standard categories:
		"enable" => Array("Enable features", "a"),
		"dims" => Array("Dimensions, widths, heights, pixels", "b"),
		"file" => Array("Files", "c"),
		"typo"	=> Array("Typography", "d"),
		"color" => Array("Colors", "e"),
		"links" => Array("Links and targets", "f"),
		"language" => Array("Language specific constants", "g"),
		
	// subcategories based on the default content elements
		"cheader" => Array("Content: 'Header'", "ma"),
		"cheader_g" => Array("Content: 'Header', Graphical", "ma"),
		"ctext" => Array("Content: 'Text'", "mb"),
//		"ctextpic" => 
		"cimage" => Array("Content: 'Image'", "md"),
		"cbullets" => Array("Content: 'Bullet list'", "me"),
		"ctable" => Array("Content: 'Table'", "mf"),
		"cuploads" => Array("Content: 'Filelinks'", "mg"),
		"cmultimedia" => Array("Content: 'Multimedia'", "mh"),
		"cmailform" => Array("Content: 'Form'", "mi"),
		"csearch" => Array("Content: 'Search'", "mj"),
		"clogin" => Array("Content: 'Login'", "mk"),
		"csplash" => Array("Content: 'Textbox'", "ml"),
		"cmenu" => Array("Content: 'Menu/Sitemap'", "mm"),
		"cshortcut" => Array("Content: 'Insert records'", "mn"),
		"clist" => Array("Content: 'List of records'", "mo"),
		"cscript" => Array("Content: 'Script'", "mp"),
		"chtml" => Array("Content: 'HTML'", "mq")
	);
	var $resourceDimensions = array();
	var $dirResources = array();

//	var $matchAll = 0;		// If set, all conditions are matched!

	var $backend_info=1;
	
		// tsconstanteditor
	var $ext_inBrace=0;

		// tsbrowser
	var $tsbrowser_searchKeys = array();
	var $tsbrowser_depthKeys = array();
	var $constantMode="";
	var $regexMode="";
	var $fixedLgd="";
	var $resourceCheck=0;
	var $ext_lineNumberOffset=0;	
	var $ext_localGfxPrefix="";
	var $ext_localWebGfxPrefix="";
	var $ext_expandAllNotes=0;
	var $ext_noPMicons=0;
	var $ext_noSpecialCharsOnLabels=0;
	var $ext_listOfTemplatesArr=array();
	var $ext_lineNumberOffset_mode="";
	var $ext_dontCheckIssetValues=0;	// Dont change...
	var $ext_noCEUploadAndCopying=0;
	var $ext_printAll=0;
	var $ext_CEformName="forms[0]";
	var $ext_defaultOnlineResourceFlag=0;
	
		// ts analyzer
	var $templateTitles=array();
	
	
	/**
	 * This flattens a hierarchical setuparray to $this->flatSetup
	 * The original function fetched the resource-file if any ("file."). This functions doesn't.
	 * 
	 * @param	[type]		$setupArray: ...
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$resourceFlag: ...
	 * @return	[type]		...
	 */
	function flattenSetup($setupArray, $prefix, $resourceFlag)	{
		if (is_array($setupArray))	{
			$this->getFileName_backPath=PATH_site;		// Setting absolute prefixed path for relative resources.
			reset($setupArray);
			while(list($key,$val)=each($setupArray))	{
				if ($prefix || substr($key,0,16)!="TSConstantEditor")	{		// We don't want "TSConstantEditor" in the flattend setup.
					if (is_array($val))	{
						$this->flattenSetup($val,$prefix.$key, ($key=="file."));
					} elseif ($resourceFlag && $this->resourceCheck) {
						$this->flatSetup[$prefix.$key] = $this->getFileName($val);
						if ($this->removeFromGetFilePath && substr($this->flatSetup[$prefix.$key],0,strlen($this->removeFromGetFilePath))==$this->removeFromGetFilePath)	{
							$this->flatSetup[$prefix.$key] = substr($this->flatSetup[$prefix.$key],strlen($this->removeFromGetFilePath));
						}
					} else {
						$this->flatSetup[$prefix.$key] = $val;
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$all: ...
	 * @return	[type]		...
	 */
	function substituteConstants($all)	{
		$this->Cmarker=substr(md5(uniqid("")),0,6);
		reset($this->flatSetup);
		while (list($const,$val)=each($this->flatSetup))	{
			if (!is_array($val))	{
				switch($this->constantMode)	{
					case "const":
						$all = str_replace('{$'.$const.'}','##'.$this->Cmarker.'_B##{$'.$const.'}##'.$this->Cmarker.'_E##',$all);
					break;
					case "subst":
						$all = str_replace('{$'.$const.'}','##'.$this->Cmarker.'_B##'.$val.'##'.$this->Cmarker.'_E##',$all);
					break;
					case "untouched":
					break;
					default:
						$all = str_replace('{$'.$const.'}',$val,$all);
					break;
				}
			}
		}
		return $all;	
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$all: ...
	 * @return	[type]		...
	 */
	function substituteCMarkers($all)	{
		switch($this->constantMode)	{
			case "const":
				$all = str_replace('##'.$this->Cmarker.'_B##', '<font color="green"><B>', $all);
				$all = str_replace('##'.$this->Cmarker.'_E##', '</b></font>', $all);
			break;
			case "subst":
				$all = str_replace('##'.$this->Cmarker.'_B##', '<font color="green"><B>', $all);
				$all = str_replace('##'.$this->Cmarker.'_E##', '</b></font>', $all);
			break;
			default:
				$all = $all;
			break;
		}
		return $all;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function generateConfig_constants()	{
			// Parses the constants in $this->const with respect to the constant-editor in this module.
			// In particular comments in the code are registered and the edit_divider is taken into account.
			// These vars are also set lateron...
		$this->setup["resources"]= $this->resources;
		$this->setup["sitetitle"]= $this->sitetitle;

			// parse constants
		$constants = t3lib_div::makeInstance("t3lib_TSparser");
		$constants->regComments=1;		// Register comments!
		$constants->setup = $this->const;
		$constants->setup = $this->mergeConstantsFromPageTSconfig($constants->setup);

		$matchObj = t3lib_div::makeInstance("t3lib_matchCondition");
//		$matchObj->matchAlternative = array("[globalString =  page | title = *test*]");
		$matchObj->matchAll=1;		// Matches ALL conditions in TypoScript

		$c=0;
		$cc=count($this->constants);
		reset($this->constants);
		while (list(,$str)=each($this->constants))	{
			$c++;
			if ($c==$cc)	{
				if (strstr($str,$this->edit_divider))	{
					$parts = explode($this->edit_divider,$str,2);
					$str=$parts[1];
					$constants->parse($parts[0],$matchObj);
				}
				$this->flatSetup = Array();
				$this->flattenSetup($constants->setup,"","");
				$defaultConstants=$this->flatSetup;
			}
			$constants->parse($str,$matchObj);
		}

		$this->flatSetup = Array();
		$this->flattenSetup($constants->setup,"","");
		$this->setup["constants"] = $constants->setup;
		
		return $this->ext_compareFlatSetups($defaultConstants);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$theSetup: ...
	 * @param	[type]		$theKey: ...
	 * @return	[type]		...
	 */
	function ext_getSetup($theSetup,$theKey)	{
		$parts = explode(".",$theKey,2);
//		debug("-----------",1);
//		debug($theKey,1);
		if (strcmp($parts[0],"") && is_array($theSetup[$parts[0]."."]))	{
//			debug(trim($parts[1]),1);
			if (strcmp(trim($parts[1]),""))	{
//			debug(trim($parts[1]),1);
				return $this->ext_getSetup($theSetup[$parts[0]."."],trim($parts[1]));
			} else {
				return array($theSetup[$parts[0]."."], $theSetup[$parts[0]]);
			}
		} else {
			if (strcmp(trim($theKey),""))	{
				return array(array(),$theSetup[$theKey]);
			} else {
				return array($theSetup,"");
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$arr: ...
	 * @param	[type]		$depth_in: ...
	 * @param	[type]		$depthData: ...
	 * @param	[type]		$parentType: ...
	 * @param	[type]		$parentValue: ...
	 * @return	[type]		...
	 */
	function ext_getObjTree($arr, $depth_in, $depthData, $parentType="",$parentValue="")	{
		$HTML="";
		$a=0;

		reset($arr);
		$keyArr_num=array();
		$keyArr_alpha=array();
		while (list($key,)=each($arr))	{
			$key=ereg_replace("\.$","",$key);
			if (substr($key,-1)!=".")	{
				if (t3lib_div::testInt($key))	{
					$keyArr_num[$key]=$arr[$key];
				} else {
					$keyArr_alpha[$key]=$arr[$key];
				}
			}
		}
		ksort($keyArr_num);
		$keyArr=$keyArr_num+$keyArr_alpha;
		reset($keyArr);
		$c=count($keyArr);
		if ($depth_in)	{$depth_in = $depth_in.".";}
		
//		$validate_info= verify_TSobjects($keyArr,$parentType,$parentValue);
//		debug($validate_info);
		
		while (list($key,)=each($keyArr))	{
			$a++;
			$depth=$depth_in.$key;
			if ($this->bType!="const" || substr($depth,0,1)!="_")	{		// this excludes all constants starting with "_" from being shown.
				$goto = substr(md5($depth),0,6);
	//			debug($depth);
				$deeper = (is_array($arr[$key."."]) && ($this->tsbrowser_depthKeys[$depth] || $this->ext_expandAllNotes)) ? 1 : 0;
				$PM = "join";
				$LN = ($a==$c)?"blank":"line";
				$BTM = ($a==$c)?"bottom":"";
				$PM = is_array($arr[$key."."]) && !$this->ext_noPMicons ? ($deeper ? "minus":"plus") : "join";
	
				$HTML.=$depthData;
				$theIcon='<img src="'.$GLOBALS["BACK_PATH"].'t3lib/gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" border="0" alt="" />';
				if ($PM=="join")	{
					$HTML.=$theIcon;
				} else {
					$aHref='index.php?id='.$GLOBALS["SOBE"]->id.'&tsbr['.$depth.']='.($deeper?0:1).'#'.$goto;
					$HTML.='<a name="'.$goto.'" href="'.htmlspecialchars($aHref).'">'.$theIcon.'</a>';
				}
	
				$label = $key;
				if (t3lib_div::inList("types,resources,sitetitle",$depth) && $this->bType=="setup")	{		// Read only...
					$label='<font color="#666666">'.$label.'</font>';
				} else {
					if ($this->linkObjects)	{
						$aHref = 'index.php?id='.$GLOBALS["SOBE"]->id.'&sObj='.$depth;
						$label = '<a href="'.htmlspecialchars($aHref).'">'.$label.'</a>';
					}
				}

				$HTML.="[".$label."]";

				if (isset($arr[$key]))	{
					$theValue = $arr[$key];
					if ($this->fixedLgd)	{
						$imgBlocks = ceil(1+strlen($depthData)/77);
						$lgdChars = 68-ceil(strlen("[".$key."]")*0.8)-$imgBlocks*3;
						$theValue = $this->ext_fixed_lgd($theValue,$lgdChars);
					}
					if ($this->tsbrowser_searchKeys[$depth])	{
						$HTML.='=<b><font color="red">'.$this->makeHtmlspecialchars($theValue).'</font></b>';
					} else {
						$HTML.="=<b>".$this->makeHtmlspecialchars($theValue)."</b>";
					}
				}
				$HTML.="<br />";
					
				if ($deeper)	{
					$HTML.=$this->ext_getObjTree($arr[$key."."], $depth, $depthData.'<img src="'.$GLOBALS["BACK_PATH"].'t3lib/gfx/ol/'.$LN.'.gif" width="18" height="16" align="top" alt="" />', $validate_info[$key], $arr[$key]);
				}
			}
		}
		return $HTML;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$theValue: ...
	 * @return	[type]		...
	 */
	function makeHtmlspecialchars($theValue){
		return $this->ext_noSpecialCharsOnLabels ? $theValue : htmlspecialchars($theValue);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$arr: ...
	 * @param	[type]		$depth_in: ...
	 * @param	[type]		$searchString: ...
	 * @param	[type]		$keyArray: ...
	 * @return	[type]		...
	 */
	function ext_getSearchKeys($arr, $depth_in, $searchString, $keyArray)		{
		reset($arr);
		$keyArr=array();
		while (list($key,)=each($arr))	{
			$key=ereg_replace("\.$","",$key);
			if (substr($key,-1)!=".")	{
				$keyArr[$key]=1;
			}
		}
		reset($keyArr);
//		asort($keyArr);
		$c=count($keyArr);
		if ($depth_in)	{$depth_in = $depth_in.".";}
		while (list($key,)=each($keyArr))	{
			$depth=$depth_in.$key;
			$deeper = is_array($arr[$key."."]);
			
			if ($this->regexMode)	{
				if (ereg($searchString,$arr[$key]))	{	$this->tsbrowser_searchKeys[$depth]=1;	}
			} else {
				if (stristr($arr[$key],$searchString))	{	$this->tsbrowser_searchKeys[$depth]=1;	}
			}
			
			if ($deeper)	{
				$cS = count($this->tsbrowser_searchKeys);
				$keyArray = $this->ext_getSearchKeys($arr[$key."."], $depth, $searchString, $keyArray);
				if ($cS != count($this->tsbrowser_searchKeys))	{
					$keyArray[$depth]=1;
				}
			}
		}	
		return $keyArray;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function ext_getRootlineNumber($pid)	{
		if ($pid && is_array($GLOBALS["rootLine"]))	{
			reset($GLOBALS["rootLine"]);
			while(list($key,$val)=each($GLOBALS["rootLine"]))	{
				if ($val['uid']==$pid)	return $key;
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$arr: ...
	 * @param	[type]		$depthData: ...
	 * @param	[type]		$keyArray: ...
	 * @param	[type]		$first: ...
	 * @return	[type]		...
	 */
	function ext_getTemplateHierarchyArr($arr,$depthData, $keyArray,$first=0)	{
		reset($arr);
		$keyArr=array();
		while (list($key,)=each($arr))	{
			$key=ereg_replace("\.$","",$key);
			if (substr($key,-1)!=".")	{
				$keyArr[$key]=1;
			}
		}
		reset($keyArr);
		$a=0;
		$c=count($keyArr);
		while (list($key,)=each($keyArr))	{
			$HTML="";
			$a++;
			$deeper = is_array($arr[$key."."]);
			$row=$arr[$key];
			
			$PM = "join";
			$LN = ($a==$c)?"blank":"line";
			$BTM = ($a==$c)?"top":"";
			$PM = "join";

			$HTML.=$depthData;
			$icon = substr($row["templateID"],0,3)=="sys" ? t3lib_iconWorks::getIcon("sys_template",array("root"=>$row["root"])) : 
							(substr($row["templateID"],0,6)=="static" ? t3lib_iconWorks::getIcon("static_template",array()) : 'gfx/i/default.gif');
			$alttext= "[".$row['templateID']."]";
			$alttext.= $row['pid'] ? " - ".t3lib_BEfunc::getRecordPath($row['pid'],$GLOBALS["SOBE"]->perms_clause,20) : "";
			if (in_array($row['templateID'],$this->clearList_const) || in_array($row['templateID'],$this->clearList_setup))	{
				$A_B='<a href="index.php?id='.$GLOBALS["SOBE"]->id.'&template='.$row['templateID'].'">';
				$A_E="</A>";
			} else {
				$A_B="";
				$A_E="";
			}
			$HTML.=($first?'':'<IMG src="'.$GLOBALS["BACK_PATH"].'t3lib/gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" border=0>').'<IMG src="'.$GLOBALS["BACK_PATH"].$icon.'" width="18" height="16" align="top" title="'.$alttext.'">'.$A_B.t3lib_div::fixed_lgd($row['title'],$GLOBALS["BE_USER"]->uc["titleLen"]).$A_E."&nbsp;&nbsp;";
			$RL = $this->ext_getRootlineNumber($row['pid']);
			$keyArray[] = '<tr>
							<td nowrap>'.$HTML.'</td>
							<td align=center>'.($row["root"]?"<b>X</b>":"").'&nbsp;&nbsp;</td>
							<td align=center'.$row["bgcolor_setup"].'>'.fw(($row["clConf"]?"<b>X</b>":"")."&nbsp;&nbsp;").'</td>
							<td align=center'.$row["bgcolor_const"].'>'.fw(($row["clConst"]?"<b>X</b>":"")."&nbsp;&nbsp;").'</td>
							<td>'.($row["pid"]?"&nbsp;".$row["pid"].(strcmp($RL,"")?" (".$RL.")":"")."&nbsp;&nbsp;":"").'</td>
							<td>'.($row["next"]?"&nbsp;".$row["next"]."&nbsp;&nbsp;":"").'</td>
						</tr>';
			if ($deeper)	{
				$keyArray = $this->ext_getTemplateHierarchyArr($arr[$key."."], $depthData.($first?'':'<IMG src="'.$GLOBALS["BACK_PATH"].'t3lib/gfx/ol/'.$LN.'.gif" width="18" height="16" align="top">'), $keyArray);
			}
		}	
		return $keyArray;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$depthDataArr: ...
	 * @param	[type]		$pointer: ...
	 * @return	[type]		...
	 */
	function ext_process_hierarchyInfo($depthDataArr,&$pointer)	{
		$parent = $this->hierarchyInfo[$pointer-1]['templateParent'];
		while ($pointer>0 && $this->hierarchyInfo[$pointer-1]['templateParent']==$parent)	{
			$pointer--;
			$row = $this->hierarchyInfo[$pointer];

			$depthDataArr[$row['templateID']]=$row;
			$depthDataArr[$row['templateID']]["bgcolor_setup"] = isset($this->clearList_setup_temp[$row['templateID']])?' class="bgColor5"':'';
			$depthDataArr[$row['templateID']]["bgcolor_const"] = isset($this->clearList_const_temp[$row['templateID']])?' class="bgColor5"':'';
			unset($this->clearList_setup_temp[$row['templateID']]);
			unset($this->clearList_const_temp[$row['templateID']]);
			$this->templateTitles[$row['templateID']]=$row["title"];
			
			if ($row['templateID']==$this->hierarchyInfo[$pointer-1]['templateParent'])	{
				$depthDataArr[$row['templateID']."."] = $this->ext_process_hierarchyInfo(array(), $pointer);
			}
		}
		return $depthDataArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$config: ...
	 * @param	[type]		$lineNumbers: ...
	 * @param	[type]		$comments: ...
	 * @param	[type]		$crop: ...
	 * @param	[type]		$syntaxHL: ...
	 * @param	[type]		$syntaxHLBlockmode: ...
	 * @return	[type]		...
	 */
	function ext_outputTS($config, $lineNumbers=0, $comments=0, $crop=0, $syntaxHL=0, $syntaxHLBlockmode=0)	{
		$all="";
		reset($config);
		while (list(,$str)=each($config))	{
			$all.="\n[GLOBAL]\n".$str;
		}
		
		if ($syntaxHL)	{
			$all = ereg_replace("^[^".chr(10)."]*.","",$all);
			$all = chop($all);
			$tsparser = t3lib_div::makeInstance("t3lib_TSparser");
			$tsparser->lineNumberOffset=$this->ext_lineNumberOffset+1;
			return $tsparser->doSyntaxHighlight($all,$lineNumbers?array($this->ext_lineNumberOffset+1):'',$syntaxHLBlockmode);
		} else {
			return $this->ext_formatTS($all,$lineNumbers,$comments,$crop);
		}
	}
	
	/**
	 * Returns a new string of max. $chars lenght
	 * If the string is longer, it will be truncated and prepended with "..."
	 * $chars must be an integer of at least 4
	 * 
	 * @param	[type]		$string: ...
	 * @param	[type]		$chars: ...
	 * @return	[type]		...
	 */
	function ext_fixed_lgd($string,$chars)	{
		if ($chars >= 4)	{
			if(strlen($string)>$chars)  {
				return substr($string, 0, $chars-3)."...";
			}
		}
		return $string;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$ln: ...
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function ext_lnBreakPointWrap($ln,$str)	{
		return '<A href="#" onClick="return brPoint('.$ln.','.($this->ext_lineNumberOffset_mode=="setup"?1:0).');">'.$str.'</a>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$input: ...
	 * @param	[type]		$ln: ...
	 * @param	[type]		$comments: ...
	 * @param	[type]		$crop: ...
	 * @return	[type]		...
	 */
	function ext_formatTS($input, $ln, $comments=1, $crop=0)	{
		$input = ereg_replace("^[^".chr(10)."]*.","",$input);
		$input = chop($input);
		$cArr = explode(chr(10),$input);

		reset($cArr);
		$n = ceil(log10(count($cArr)+$this->ext_lineNumberOffset));
		$lineNum="";
		while(list($k,$v)=each($cArr))	{
			$lln=$k+$this->ext_lineNumberOffset+1;
			if ($ln)	$lineNum = $this->ext_lnBreakPointWrap($lln,str_replace(" ",'&nbsp;',sprintf("% ".$n."d",$lln))).":   ";
			$v=htmlspecialchars($v);
			if ($crop)	{$v=$this->ext_fixed_lgd($v,($ln?71:77));}
			$cArr[$k] = $lineNum.str_replace(" ",'&nbsp;',$v);
			
			$firstChar = substr(trim($v),0,1);
			if ($firstChar=="[")	{
				$cArr[$k] = '<font color="green"><b>'.$cArr[$k].'</b></font>';
			} elseif ($firstChar=="/" || $firstChar=="#")	{
				if ($comments)	{
					$cArr[$k] = '<span class="typo3-dimmed">'.$cArr[$k].'</span>';
				} else {
					unset($cArr[$k]);
				}
			}


		}
		$output = implode($cArr, "<BR>")."<BR>";
		return $output;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @param	[type]		$template_uid: ...
	 * @return	[type]		...
	 */
	function ext_getFirstTemplate($id,$template_uid=0)		{
			// Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
		if (intval($id))	{
			if ($template_uid)	{
				$addC = " AND uid=".$template_uid;
			}
			$query = "SELECT * FROM sys_template WHERE pid = ".intval($id).$addC." ".$this->whereClause." ORDER BY sorting LIMIT 1";
			$res = mysql(TYPO3_db, $query);
			echo mysql_error();
			$row = mysql_fetch_assoc($res);
			return $row;	// Returns the template row if found.
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function ext_getAllTemplates($id)		{
			// Query is taken from the runThroughTemplates($theRootLine) function in the parent class.
		if (intval($id))	{
			$query = "SELECT * FROM sys_template WHERE pid = ".intval($id)." ".$this->whereClause." ORDER BY sorting";
			$res = mysql(TYPO3_db, $query);
			echo mysql_error();
			$outRes=array();
			while($row = mysql_fetch_assoc($res))	{
				$outRes[] = $row;
			}
			return $outRes;	// Returns the template rows in an array.
		}
	}
	
	/**
	 * This function compares the flattened constants (default and all).
	 * Returns an array with the constants from the whole template which may be edited by the module.
	 * 
	 * @param	[type]		$default: ...
	 * @return	[type]		...
	 */
	function ext_compareFlatSetups($default)	{
		$editableComments=array();
		reset($this->flatSetup);
		while(list($const,$value)=each($this->flatSetup))	{
			if (substr($const,-2)!=".." && isset($this->flatSetup[$const.".."]))	{
				$comment = trim($this->flatSetup[$const.".."]);
				$c_arr = explode(chr(10),$comment);
				while(list($k,$v)=each($c_arr))	{
					$line=trim(ereg_replace("^[#\/]*","",$v));
					if ($line)	{
						$parts = explode(";", $line);
						while(list(,$par)=each($parts))		{
							if (strstr($par,"="))	{
								$keyValPair =explode("=",$par,2);
								switch(trim(strtolower($keyValPair[0])))	{
									case "type":
											// Type: 	
											/*
	int (range; low-high, list: item,item,item = selector), 
	boolean (check), 
	string (default), 
	wrap, 
	html-color ..., 
	file
											*/
										$editableComments[$const]["type"] = trim($keyValPair[1]);
									break;
									case "cat":
											// list of categories.
										$catSplit=explode("/",strtolower($keyValPair[1]));
										$editableComments[$const]["cat"] = trim($catSplit[0]);
										$catSplit[1]=trim($catSplit[1]);	// This is the subcategory. Must be a key in $this->subCategories[]. catSplit[2] represents the search-order within the subcat.
										if ($catSplit[1] && isset($this->subCategories[$catSplit[1]]))	{
											$editableComments[$const]["subcat_name"]=$catSplit[1];
											$editableComments[$const]["subcat"]=$this->subCategories[$catSplit[1]][1]."/".$catSplit[1]."/".trim($catSplit[2])."z";
										} else {
											$editableComments[$const]["subcat"]="x"."/".trim($catSplit[2])."z";
										}
									break;
									case "label":
											// label
										$editableComments[$const]["label"] = trim($keyValPair[1]);
									break;
								}
							}
						}
					}
				}
			}
			if (isset($editableComments[$const]))	{
				$editableComments[$const]["name"]=$const;
				$editableComments[$const]["value"]=trim($value);
				if (isset($default[$const]))	{
					$editableComments[$const]["default_value"]=trim($default[$const]);
				}
			}
		}
		return $editableComments;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$editConstArray: ...
	 * @return	[type]		...
	 */
	function ext_categorizeEditableConstants($editConstArray)	{
		// Runs through the available constants and fills the $this->categories array with pointers and priority-info
		reset($editConstArray);
		while(list($constName,$constData)=each($editConstArray))	{
			if (!$constData["type"])	{$constData["type"]="string";}
			$cats = explode(",",$constData["cat"]);
			reset($cats);
			while (list(,$theCat)=each($cats))	{		// if = only one category, while allows for many. We have agreed on only one category is the most basic way...
				$theCat=trim($theCat);
				if ($theCat)	{
					$this->categories[$theCat][$constName]=$constData["subcat"];
//					$this->categories["all"][$constName]=$constData["subcat"];
				}
			}
		}
//		debug($this->categories);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function ext_getCategoryLabelArray()	{
		// Returns array used for labels in the menu.
		$retArr = array();
		while(list($k,$v)=each($this->categories))	{
			if (count($v))	{
				$retArr[$k]=strtoupper($k)." (".count($v).")";
			}
		}
		return $retArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function ext_getTypeData($type)	{
		$retArr = array();
		$type=trim($type);
		if (!$type)	{
			$retArr["type"]="string";
		} else {
			$m=strcspn ($type," [");
			$retArr["type"]=strtolower(substr($type,0,$m));
			if (t3lib_div::inList("int,options,file,boolean,offset",$retArr["type"]))	{
				$p=trim(substr($type,$m));
				ereg("\[(.*)\]",$p,$reg);
				$p=trim($reg[1]);
				if ($p)	{
					$retArr["paramstr"]=$p;
					switch($retArr["type"])	{
						case "int":
							if (substr($retArr["paramstr"],0,1)=="-")	{
								$retArr["params"]=t3lib_div::intExplode("-",substr($retArr["paramstr"],1));
								$retArr["params"][0]=intval("-".$retArr["params"][0]);
							} else {
								$retArr["params"]=t3lib_div::intExplode("-",$retArr["paramstr"]);
							}
							$retArr["paramstr"]=$retArr["params"][0]." - ".$retArr["params"][1];
						break;
						case "options":
							$retArr["params"]=explode(",",$retArr["paramstr"]);
						break;
					}
				}
			}
		}
//		debug($retArr);
		return $retArr;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$category: ...
	 * @return	[type]		...
	 */
	function ext_getTSCE_config($category)	{
		$catConf=$this->setup["constants"]["TSConstantEditor."][$category."."];
		$out=array();
		if (is_array($catConf))	{
			reset($catConf);
			while(list($key,$val)=each($catConf))	{
				switch($key)	{
					case "image":
						$out["imagetag"] = $this->ext_getTSCE_config_image($catConf["image"]);
					break;
					case "description":
					case "bulletlist":
					case "header":
						$out[$key] = $val;
					break;
					default:
						if (t3lib_div::testInt($key))	{
							$constRefs = explode(",",$val);
							reset($constRefs);
							while(list(,$const)=each($constRefs))	{
								$const=trim($const);
								if ($const && $const<=20)	{
									$out["constants"][$const].=$this->ext_getKeyImage($key);
								}
							}
						}
					break;
				}
			}
		}
		$this->helpConfig=$out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function ext_getKeyImage($key)	{
		return '<img src="'.$this->ext_localWebGfxPrefix.'gfx/'.$key.'.gif" align="top" hspace=2>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$imgConf: ...
	 * @return	[type]		...
	 */
	function ext_getTSCE_config_image($imgConf)	{
		if (substr($imgConf,0,4)=="gfx/")	{
			$iFile=$this->ext_localGfxPrefix.$imgConf;
			$tFile=$this->ext_localWebGfxPrefix.$imgConf;
		} elseif (substr($imgConf,0,4)=='EXT:') {
			$iFile = t3lib_div::getFileAbsFileName($imgConf);
			if ($iFile)	{
				$f = substr($iFile,strlen(PATH_site));	
				$tFile=$GLOBALS["BACK_PATH"]."../".$f;
			}
		} else {
			$f = "uploads/tf/".$this->extractFromResources($this->setup["resources"],$imgConf);
			$iFile=PATH_site.$f;
			$tFile=$GLOBALS["BACK_PATH"]."../".$f;
		}
		$imageInfo=@getImagesize($iFile);
		return '<img src="'.$tFile.'" '.$imageInfo[3].'>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function ext_resourceDims()	{
		if ($this->setup["resources"])	{
			$rArr=explode(",",$this->setup["resources"]);
			while(list($c,$val)=each($rArr))	{
				$val=trim($val);
				$theFile = PATH_site."uploads/tf/".$val;
				if ($val && @is_file($theFile))	{
					$imgInfo = @getimagesize($theFile);
				}
				if (is_array($imgInfo))	{
					$this->resourceDimensions[$val]=" (".$imgInfo[0]."x".$imgInfo[1].")";
				}
			}
		}
		reset($this->dirResources);
		while(list($c,$val)=each($this->dirResources))	{
			$val=trim($val);
			$imgInfo = @getimagesize(PATH_site.$val);
			if (is_array($imgInfo))	{
				$this->resourceDimensions[$val]=" (".$imgInfo[0]."x".$imgInfo[1].")";
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function ext_readDirResources($path)	{
		$path=trim($path);
		if ($path && substr($path,0,10)=="fileadmin/")	{
			$path = ereg_replace("\/$","",$path);
			$this->readDirectory(PATH_site.$path);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$path: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function readDirectory($path,$type="file")	{
		if(@is_dir($path))	{
			$d = @dir($path);
			$tempArray=Array();
			if (is_object($d))	{
				while($entry=$d->read()) {
					if ($entry!="." && $entry!="..")	{
						$wholePath = $path."/".$entry;		// Because of odd PHP-error where  <BR>-tag is sometimes placed after a filename!!
						if (@file_exists($wholePath) && (!$type || filetype($wholePath)==$type))	{
							$fI = t3lib_div::split_fileref($wholePath);
							$this->dirResources[]=substr($wholePath,strlen(PATH_site));
						}
					}
				}
				$d->close();
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$params: ...
	 * @return	[type]		...
	 */
	function ext_fNandV($params)	{
		$fN='data['.$params["name"].']';
		$fV=$params["value"];
		if (ereg("^{[\$][a-zA-Z0-9\.]*}$",trim($fV),$reg))	{		// Values entered from the constantsedit cannot be constants!	230502; removed \{ and set {
			$fV="";
		}
		$fV=htmlspecialchars($fV);

		return array($fN,$fV,$params);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$theConstants: ...
	 * @param	[type]		$category: ...
	 * @return	[type]		...
	 */
	function ext_printFields($theConstants,$category)	{
			// This functions returns the HTML-code that creates the editor-layout of the module.
		reset($theConstants);
		$output="";
		$subcat="";
		if (is_array($this->categories[$category]))	{
		
			$help=$this->helpConfig;
			$this->rArr=explode(",",$this->setup["resources"].",".implode($this->dirResources,","));
		
			asort($this->categories[$category]);
			while(list($name,$type)=each($this->categories[$category]))	{
				$params = $theConstants[$name];
				if (is_array($params))	{
					if ($subcat!=$params["subcat_name"])	{
						$subcat=$params["subcat_name"];
						$subcat_name = $params["subcat_name"] ? $this->subCategories[$params["subcat_name"]][0] : "Others";
						$output.='<tr>';
						$output.='<td colspan=2 class="bgColor4"><div align="center"><b>'.$subcat_name.'</b></div></td>';
						$output.='</tr>';
					}

			//		if (substr($params["value"],0,2)!='{$')	{
						$label=$GLOBALS["LANG"]->sL($params["label"]);
						$label_parts = explode(":",$label,2);
						if (count($label_parts)==2)	{
							$head=trim($label_parts[0]);
							$body=trim($label_parts[1]);
						} else {
							$head=trim($label_parts[0]);
							$body="";
						}
						if (strlen($head)>35)	{
							if (!$body) {$body=$head;}
							$head=t3lib_div::fixed_lgd($head,35);
						}
						$typeDat=$this->ext_getTypeData($params["type"]);
						$checked="";
						$p_field="";
						$raname = substr(md5($params["name"]),0,10);
						$aname="'".$raname."'";
						if ($this->ext_dontCheckIssetValues || isset($this->objReg[$params["name"]]))	{
							$checked=" checked";
							list($fN,$fV,$params)=$this->ext_fNandV($params);
							
							switch($typeDat["type"])	{
								case "int":
								case "int+":
									$p_field='<input type="text" name="'.$fN.'" value="'.$fV.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(5).' onChange="uFormUrl('.$aname.')">';
									if ($typeDat["paramstr"])	{
										$p_field.=' Range: '.$typeDat["paramstr"];
									} elseif ($typeDat["type"]=="int+") {
										$p_field.=' Range: 0 - ';
									} else {
										$p_field.=' (Integer)';
									}
								break;
								case "color":
									$colorNames=explode(",",",".$this->HTMLcolorList);
									$p_field="";
									while(list(,$val)=each($colorNames))	{
										$sel="";
										if ($val==strtolower($params["value"]))	{$sel=" selected";}
										$p_field.='<option value="'.htmlspecialchars($val).'"'.$sel.'>'.$val.'</option>';
									}
									$p_field='<select name="C'.$fN.'" onChange="document.'.$this->ext_CEformName.'[\''.$fN.'\'].value=this.options[this.selectedIndex].value; uFormUrl('.$aname.');">'.$p_field.'</select>';

									$p_field.='<input type="text" name="'.$fN.'" value="'.$fV.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(7).' onChange="uFormUrl('.$aname.')">';
								break;
								case "wrap":
									$wArr = explode("|",$fV);
									$p_field='<input type="text" name="'.$fN.'" value="'.$wArr[0].'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(29).' onChange="uFormUrl('.$aname.')">';
									$p_field.=' | ';
									$p_field.='<input type="text" name="W'.$fN.'" value="'.$wArr[1].'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(15).' onChange="uFormUrl('.$aname.')">';
								break;
								case "offset":
									$wArr = explode(",",$fV);
									$labels = t3lib_div::trimExplode(",",$typeDat["paramstr"]);
									$p_field=($labels[0]?$labels[0]:"x").':<input type="text" name="'.$fN.'" value="'.$wArr[0].'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' onChange="uFormUrl('.$aname.')">';
									$p_field.=' , ';
									$p_field.=($labels[1]?$labels[1]:"y").':<input type="text" name="W'.$fN.'" value="'.$wArr[1].'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' onChange="uFormUrl('.$aname.')">';
									for ($aa=2;$aa<count($labels);$aa++)	{
										if ($labels[$aa])	{
											$p_field.=' , '.$labels[$aa].':<input type="text" name="W'.$aa.$fN.'" value="'.$wArr[$aa].'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' onChange="uFormUrl('.$aname.')">';
										} else {
											$p_field.='<input type="hidden" name="W'.$aa.$fN.'" value="'.$wArr[$aa].'">';
										}
									}
								break;
								case "options":
									if (is_array($typeDat["params"]))	{
										$p_field="";
										while(list(,$val)=each($typeDat["params"]))	{
											$vParts = explode("=",$val,2);
											$label = $vParts[0];
											$val = isset($vParts[1]) ? $vParts[1] : $vParts[0];
											
												// option tag:
											$sel="";
											if ($val==$params["value"])	{$sel=" selected";}
											$p_field.='<option value="'.htmlspecialchars($val).'"'.$sel.'>'.$GLOBALS["LANG"]->sL($label).'</option>';
										}
										$p_field='<select name="'.$fN.'" onChange="uFormUrl('.$aname.')">'.$p_field.'</select>';
									}
								break;
								case "boolean":
									$p_field='<input type="Hidden" name="'.$fN.'" value="0">';
									$sel=""; if ($fV)	{$sel=" checked";}
									$p_field.='<input type="Checkbox" name="'.$fN.'" value="'.($typeDat["paramstr"]?$typeDat["paramstr"]:1).'"'.$sel.' onClick="uFormUrl('.$aname.')">';
								break;
								case "comment":
									$p_field='<input type="Hidden" name="'.$fN.'" value="#">';
									$sel=""; if (!$fV)	{$sel=" checked";}
									$p_field.='<input type="Checkbox" name="'.$fN.'" value=""'.$sel.' onClick="uFormUrl('.$aname.')">';
								break;
								case "file":
									$p_field='<option value=""></option>';
//									debug($params["value"]);
									$theImage="";
//									if ($this->)	{
										$selectThisFile = $this->extractFromResources($this->setup["resources"],$params["value"]);
										if ($params["value"] && !$selectThisFile)	{
											if (in_array($params["value"],$this->dirResources))	{
												$selectThisFile=$params["value"];
											}
										}
//										debug($selectThisFile);
											// extensionlist
										$extList = $typeDat["paramstr"];
										$p_field='<option value="">('.$extList.')</option>';
										if ($extList=="IMAGE_EXT")	{
											$extList = $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"];
										}
										reset($this->rArr);
										$onlineResourceFlag=$this->ext_defaultOnlineResourceFlag;
										
										while(list($c,$val)=each($this->rArr))	{
											$val=trim($val);
											$fI=t3lib_div::split_fileref($val);
//											debug($fI);
											if ($val && (!$extList || t3lib_div::inList($extList,$fI["fileext"])))	{
												if ($onlineResourceFlag<=0 && substr($fI["path"],0,10)=="fileadmin/")	{
													if ($onlineResourceFlag<0)	{
														$p_field.='<option value=""></option>';
													}
													$p_field.='<option value="">__'.$fI["path"].'__:</option>';
													$onlineResourceFlag=1;
												}
												$dims=$this->resourceDimensions[$val];
												$sel="";
												
													// Check if $params["value"] is in the list of resources.
												if ($selectThisFile && $selectThisFile==$val)	{
													$sel=" selected";
													if ($onlineResourceFlag<=0)	{
														$theImage=t3lib_BEfunc::thumbCode(array("resources"=>$selectThisFile),"sys_template","resources",$GLOBALS["BACK_PATH"],"");
													} else {
														$theImage=t3lib_BEfunc::thumbCode(array("resources"=>$fI["file"]),"sys_template","resources",$GLOBALS["BACK_PATH"],"",$fI["path"]);
													}
												}
												
												if ($onlineResourceFlag<=0)	{
													$onlineResourceFlag--;
														// Value is set with a *
													$val = $this->ext_setStar($val);
													$p_field.='<option value="'.htmlspecialchars($val).'"'.$sel.'>'.$val.$dims.'</option>';
												} else {
													$p_field.='<option value="'.htmlspecialchars($val).'"'.$sel.'>'.$fI["file"].$dims.'</option>';
												}
											}
										}
										if (trim($params["value"]) && !$selectThisFile)	{
											$val = $params["value"];
											$p_field.='<option value=""></option>';
											$p_field.='<option value="'.htmlspecialchars($val).'" selected>'.$val.'</option>';
										}
	//								}
									$p_field='<select name="'.$fN.'" onChange="uFormUrl('.$aname.')">'.$p_field.'</select>';
									$p_field.=$theImage;

									if (!$this->ext_noCEUploadAndCopying)	{
											// Copy a resource
										$copyFile = $this->extractFromResources($this->setup["resources"],$params["value"]);
										if (!$copyFile)	{
											if ($params["value"])	{
												$copyFile=PATH_site.$this->ext_detectAndFixExtensionPrefix($params["value"]);
											}
										} else {
	//										$copyFile=PATH_site."uploads/tf/".$copyFile;
											$copyFile="";
										}
#debug($copyFile);
										if ($copyFile && @is_file($copyFile))	{
											$p_field.='<img src="clear.gif" width="20" height="1" alt="" /><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/clip_copy.gif','width="12" height="12"').' border="0" alt="" /><input type="Checkbox" name="_copyResource['.$params["name"].']" value="'.htmlspecialchars($copyFile).'" onClick="uFormUrl('.$aname.');if (this.checked) {alert(unescape(\''.rawurlencode(sprintf("This will make a copy of the current file, '%s'. Do you really want that?",$params["value"])).'\'));}">';
										}
											// Upload?
										$p_field.='<BR>';
										$p_field.='<input type="file" name="upload_'.$fN.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth().' onChange="uFormUrl('.$aname.')" size="50" />';
									}
								break;
								case 'small':
								default:
									$fwidth= $typeDat["type"]=="small" ? 10 : 46;
									$p_field='<input type="text" name="'.$fN.'" value="'.$fV.'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth($fwidth).' onChange="uFormUrl('.$aname.')">';
								break;
							}
						}
						if (!$this->ext_dontCheckIssetValues)	$p_field='<input type="Checkbox" name="check['.$params["name"].']" value="1"'.$checked.' onClick="uFormUrl('.$aname.')">'.$p_field;
						if ($typeDat["type"]=="color" && substr($params["value"],0,2)!='{$')	{
							$p_field='<table border=0 cellpadding=0 cellspacing=0><tr><td nowrap>'.$p_field.'</td><td>&nbsp;</td><td bgcolor="'.$params["value"].'"><img src="clear.gif" width=50 height=10></td></tr></table>';
						} else {
							$p_field='<span class="nobr">'.$p_field.'</span><br />';
						}
	
						$p_name = '<span class="typo3-dimmed">['.$params["name"].']</span><BR>';
						$p_dlabel='<span class="typo3-dimmed"><b>Default:</b> '.htmlspecialchars($params["default_value"]).'</span><BR>';			
						$p_label = '<b>'.htmlspecialchars($head).'</b>';
						$p_descrip = $body ? htmlspecialchars($body)."<BR>" : "";
						
						$output.='<tr>';
						$output.='<td valign=top nowrap><a name="'.$raname.'"></a>'.$help["constants"][$params["name"]].$p_label.'</td>';
						$output.='<td valign=top align="right">'.$p_name.'</td>';
						$output.='</tr>';
						$output.='<tr>';
						$output.='<td colspan=2>'.$p_descrip.$p_field.$p_dlabel.'<br></td>';
						$output.='</tr>';
			//		}
				} else {
					debug("Error. Constant did not exits. Should not happen.");
				}
			}
			$output='<table border=0 cellpadding=0 cellspacing=0>'.$output.'</table>';
		}
		return $output;
	}












	/***************************
	 *
	 * Processing input values
	 *
	 ***************************/

	/**
	 * @param	[type]		$constants: ...
	 * @return	[type]		...
	 */
	function ext_regObjectPositions($constants)	{
			// This runs through the lines of the constants-field of the active template and registers the constants-names and linepositions in an array, $this->objReg
		$this->raw = explode(chr(10),$constants);
		$this->rawP=0;

		$this->objReg=array();		// resetting the objReg if the divider is found!!
		$this->ext_regObjects("");
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$pre: ...
	 * @return	[type]		...
	 */
	function ext_regObjects($pre)	{
			// works with regObjectPositions. "expands" the names of the TypoScript objects
		while (isset($this->raw[$this->rawP]))	{
			$line = ltrim($this->raw[$this->rawP]);
			if (strstr($line,$this->edit_divider))	{
				$this->objReg=array();		// resetting the objReg if the divider is found!!
			}
			$this->rawP++;
			if ($line)	{
				if (substr($line,0,1)=="[")	{
//					return $line;
				} elseif (strcspn($line,"}#/")!=0)	{
					$varL = strcspn($line," {=<");
					$var=substr($line,0,$varL);
					$line = ltrim(substr($line,$varL));
					switch(substr($line,0,1))	{
						case "=":
							$this->objReg[$pre.$var]=$this->rawP-1;
						break;
						case "{":
							$this->ext_inBrace++;
							$this->ext_regObjects($pre.$var.".");
						break;
					}
					$this->lastComment="";
				} elseif (substr($line,0,1)=="}")	{
					$this->lastComment="";
					$this->ext_inBrace--;
					if ($this->ext_inBrace<0)	{
						$this->ext_inBrace=0;
					} else {
						break;
					}
				}
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$key: ...
	 * @param	[type]		$var: ...
	 * @return	[type]		...
	 */
	function ext_putValueInConf($key, $var)	{
			// Puts the value $var to the TypoScript value $key in the current lines of the templates. 
			// If the $key is not found in the template constants field, a new line is inserted in the bottom.
		$theValue = " ".trim($var);
		if (isset($this->objReg[$key]))	{
			$lineNum = $this->objReg[$key];
			$parts = explode("=",$this->raw[$lineNum],2);
			if (count($parts)==2)	{
				$parts[1]= $theValue;
			}
			$this->raw[$lineNum]=implode($parts,"=");
		} else {
			$this->raw[]=$key." =".$theValue;
		}
		$this->changed=1;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function ext_removeValueInConf($key)	{
			// Removes the value in the configuration
		if (isset($this->objReg[$key]))	{
			$lineNum = $this->objReg[$key];
			unset($this->raw[$lineNum]);
		}
		$this->changed=1;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$arr: ...
	 * @param	[type]		$settings: ...
	 * @return	[type]		...
	 */
	function ext_depthKeys($arr,$settings)	{
		reset($arr);
		$tsbrArray=array();
		while(list($theK,$theV)=each($arr))	{
			$theKeyParts = explode(".",$theK);
			$depth="";
			$c=count($theKeyParts);
			$a=0;
			while(list(,$p)=each($theKeyParts))	{
				$a++;
				$depth.=($depth?".":"").$p;
				$tsbrArray[$depth]= ($c==$a) ? $theV : 1;
			}
		}
			// Modify settings
		reset($tsbrArray);
		while(list($theK,$theV)=each($tsbrArray))	{
			if ($theV)	{
				$settings[$theK] = 1;
			} else {
				unset($settings[$theK]);
			}
		}
		return $settings;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$http_post_vars: ...
	 * @param	[type]		$http_post_files: ...
	 * @param	[type]		$theConstants: ...
	 * @param	[type]		$tplRow: ...
	 * @return	[type]		...
	 */
	function ext_procesInput($http_post_vars,$http_post_files,$theConstants,$tplRow)	{
		$data=$http_post_vars["data"];
		$check=$http_post_vars["check"];
		$copyResource=$http_post_vars["_copyResource"];
		$Wdata=$http_post_vars["Wdata"];
		$W2data=$http_post_vars["W2data"];
		$W3data=$http_post_vars["W3data"];
		$W4data=$http_post_vars["W4data"];
		$W5data=$http_post_vars["W5data"];

		if (is_array($data))	{
			reset($data);
			while(list($key,$var)=each($data))	{
				if (isset($theConstants[$key]))	{
					if ($this->ext_dontCheckIssetValues || isset($check[$key]))	{		// If checkbox is set, update the value
						list($var) = explode(chr(10),stripslashes($var));	// exploding with linebreak, just to make sure that no multiline input is given!
						$typeDat=$this->ext_getTypeData($theConstants[$key]["type"]);
						switch($typeDat["type"])	{
							case "int":
								if ($typeDat["paramstr"])	{
									$var=t3lib_div::intInRange($var,$typeDat["params"][0],$typeDat["params"][1]);
								} else {
									$var=intval($var);
								}
							break;
							case "int+":
								$var=t3lib_div::intInRange($var,0,10000);
							break;
							case "color":
								$col=array();
								if($var && !t3lib_div::inList($this->HTMLcolorList,strtolower($var)))	{
									$var = ereg_replace("[^A-Fa-f0-9]*","",$var);
									$col[]=HexDec(substr($var,0,2));
									$col[]=HexDec(substr($var,2,2));
									$col[]=HexDec(substr($var,4,2));
									$var="#".strtoupper(substr("0".DecHex($col[0]),-2).substr("0".DecHex($col[1]),-2).substr("0".DecHex($col[2]),-2));
								}
							break;
							case "comment":
								if ($val)	{
									$val="#";
								} else {
									$val="";
								}
							break;
							case "wrap":
								if (isset($Wdata[$key]))	{
									$var.="|".stripslashes($Wdata[$key]);
								}
							break;
							case "offset":
								if (isset($Wdata[$key]))	{
									$var=intval($var).",".intval($Wdata[$key]);
									if (isset($W2data[$key]))	{
										$var.=",".intval($W2data[$key]);
										if (isset($W3data[$key]))	{
											$var.=",".intval($W3data[$key]);
											if (isset($W4data[$key]))	{
												$var.=",".intval($W4data[$key]);
												if (isset($W5data[$key]))	{
													$var.=",".intval($W5data[$key]);
												}
											}
										}
									}
								}
							break;
							case "boolean":
								if ($var)	{
									$var = $typeDat["paramstr"] ? $typeDat["paramstr"] : 1;
								}
							break;
							case "file":
								if (!$this->ext_noCEUploadAndCopying)	{
									if ($http_post_files["upload_data"]["name"][$key] && $http_post_files["upload_data"]["tmp_name"][$key]!="none")	{
										$var = $this->upload_copy_file(
											$typeDat,
											$tplRow,
											trim($http_post_files["upload_data"]["name"][$key]),
											$http_post_files["upload_data"]["tmp_name"][$key]
										);
									}
									if ($copyResource[$key])	{
										$var = $this->upload_copy_file(
											$typeDat,
											$tplRow,
											basename($copyResource[$key]),
											$copyResource[$key]
										);
									}
								}
							break;
						}
						if ($this->ext_printAll || strcmp($theConstants[$key]["value"],$var))	{
							$this->ext_putValueInConf($key, $var);		// Put value in, if changed.
						}
						unset($check[$key]);		// Remove the entry because it has been "used"
					} else {
						$this->ext_removeValueInConf($key);
					}
				}
			}
		}
			// Remaining keys in $check indicates fields that are just clicked "on" to be edited. Therefore we get the default value and puts that in the template as a start...
		if (!$this->ext_dontCheckIssetValues && is_array($check))	{
			reset($check);
			while(list($key,$var)=each($check))	{
				if (isset($theConstants[$key]))	{
					$dValue = $theConstants[$key]["default_value"];
//					debug($dValue);
					$this->ext_putValueInConf($key, $dValue);
//					debug($key,1);
				}
			}
		}
//		debug($this->objReg);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$typeDat: ...
	 * @param	[type]		$tplRow: ...
	 * @param	[type]		$theRealFileName: ...
	 * @param	[type]		$tmp_name: ...
	 * @return	[type]		...
	 */
	function upload_copy_file($typeDat,&$tplRow,$theRealFileName,$tmp_name)	{

			// extensions
		$extList = $typeDat["paramstr"];
		if ($extList=="IMAGE_EXT")	{
			$extList = $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"];
		}
		$fI=t3lib_div::split_fileref($theRealFileName);
		if ($theRealFileName && (!$extList || t3lib_div::inList($extList,$fI["fileext"])))	{
			$tmp_upload_name = t3lib_div::upload_to_tempfile($tmp_name);	// If there is an uploaded file, move it for the sake of safe_mode.
		
				// Saving resource
			$alternativeFileName=array();
			$alternativeFileName[$tmp_upload_name] = $theRealFileName;
				// Making list of resources
			$resList = $tplRow["resources"];
			$resList = $tmp_upload_name.",".$resList;
			$resList=implode(t3lib_div::trimExplode(",",$resList,1),",");
				// Making data-array
			$recData=array();
			$recData["sys_template"][$tplRow["uid"]]["resources"] = $resList;
				// Saving
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values=0;
			$tce->alternativeFileName = $alternativeFileName;
			$tce->start($recData,Array());
			$tce->process_datamap();
			
			t3lib_div::unlink_tempfile($tmp_upload_name);
			
			$tmpRow = t3lib_BEfunc::getRecord("sys_template",$tplRow["uid"],"resources");
			$tplRow["resources"] = $tmpRow["resources"];

				// Setting the value
			$var = $this->ext_setStar($theRealFileName);
		}
		return $var;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @param	[type]		$perms_clause: ...
	 * @return	[type]		...
	 */
	function ext_prevPageWithTemplate($id,$perms_clause)	{
		$rootLine = t3lib_BEfunc::BEgetRootLine($id,$perms_clause?" AND ".$perms_clause:"");
		reset($rootLine);
		while(list(,$p)=each($rootLine))	{
			if ($this->ext_getFirstTemplate($p["uid"]))	{
				return $p;
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$val: ...
	 * @return	[type]		...
	 */
	function ext_setStar($val)	{
		$fParts = explode(".",strrev($val),2);
		$val=ereg_replace("_[0-9][0-9]$","",strrev($fParts[1]))."*.".strrev($fParts[0]);
		return $val;	
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$value: ...
	 * @return	[type]		...
	 */
	function ext_detectAndFixExtensionPrefix($value)	{
		if (substr($value,0,4)=="EXT:")	{
			$parts = explode("/",substr($value,4),2);
#debug($parts);
			$extPath = t3lib_extMgm::siteRelPath($parts[0]);
			$value = $extPath.$parts[1];
			return $value;
		} else {
			return $value;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsparser_ext.php']);
}
?>