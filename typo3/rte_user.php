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
 * User defined content for the RTE
 *
 * Belongs to the "rte" extension
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

 

$BACK_PATH="";
require ("init.php");
require ("template.php");
include ("sysext/lang/locallang_rte_user.php");


// ***************************
// Script Classes
// ***************************
class SC_rte_user {
	var $content;
	var $modData;
	var $siteUrl;
	var $doc;	
	
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

/*		$PRE_CODED["clear_gifs"] = Array (
			"100" => "Clear-gif, 100x20",
			"100." => Array (
				"content" => '<img src=clear.gif width=100 height=20><BR>'
			),
			"110" => "Clear-gif, 200x50",
			"110." => Array (
				"content" => '<img src=clear.gif width=200 height=50><BR>'
			)
		);
	*/	
		
		
		// Current site url:
		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
		
		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form = '<form action="" name="process" method="POST">
		<input type="hidden" name="processContent" value="">
		<input type="hidden" name="returnUrl" value="'.htmlspecialchars(t3lib_div::getIndpEnv("REQUEST_URI")).'">
		';
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			var RTEobj = self.parent.parent;
		
			function getSelectedTextContent()	{
				var oSel = RTEobj.GLOBAL_SEL;
				var sType = oSel.type;
		//		alert(sType);
		//		RTEobj.debugObj(oSel);
				if (sType=="Text")	{
					return oSel.htmlText;
				}
				return "";
			}
			function insertHTML(content,noHide)	{
		//		alert(content);
				RTEobj.insertHTML(content);
				if (!noHide)	RTEobj.edHidePopup();
			}
			function wrapHTML(wrap1,wrap2,noHide)	{
				var contentToWrap = getSelectedTextContent();
				if (contentToWrap)	{
					contentToWrap = ""+wrap1+contentToWrap+wrap2;
					setSelectedTextContent(contentToWrap);
				} else {
					alert('.$GLOBALS['LANG']->JScharCode($LANG->getLL("noTextSelection")).');
				}
				if (!noHide)	RTEobj.edHidePopup();
			}
			function processSelection(script)	{
				document.process.action = script;
				document.process.processContent.value = getSelectedTextContent();
				document.process.submit();
			}
			function setSelectedTextContent(content)	{
				var oSel = RTEobj.GLOBAL_SEL;
				var sType = oSel.type;
				if (sType=="Text")	{
					oSel.pasteHTML(content);
				}
			}
		//	alert(RTEobj.getHTML());
		//	RTEobj.setHTML("Hej <b>Kasper</b>-dreng!",1);
		</script>
		';
		
		
		$this->modData = $BE_USER->getModuleData("rte_user.php","ses");
		if (t3lib_div::GPvar("OC_key"))	{
			$parts = explode("|",t3lib_div::GPvar("OC_key"));
			$this->modData["openKeys"][$parts[1]] = $parts[0]=="O" ? 1 : 0;
			$BE_USER->pushModuleData("rte_user.php",$this->modData);
		}
	}
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->main_user($this->modData["openKeys"]);
	}
	function printContent()	{
		echo $this->content;
	}
	
	// ***************************
	// OTHER FUNCTIONS:	
	// ***************************
	function calcWH($imgInfo,$maxW=380,$maxH=500)	{
		$IW = $imgInfo[0];
		$IH = $imgInfo[1];
		if ($IW>$maxW)	{
			$IH=ceil($IH/$IW*$maxW);
			$IW=$maxW;
		}
		if ($IH>$maxH)	{
			$IW=ceil($IW/$IH*$maxH);
			$IH=$maxH;
		}
		
		$imgInfo[3]='width="'.$IW.'" height="'.$IH.'"';
		return $imgInfo;
	}
	
	// ******************************************************************
	// Rich Text Editor (RTE) link selector (MAIN function)
	// ******************************************************************
	function main_user($openKeys)	{
		global $SOBE,$LANG;
			// Starting content:
		$content.=$this->doc->startPage("RTE user");
		
		$RTEtsConfigParts = explode(":",t3lib_div::GPvar("RTEtsConfigParams"));
		$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5])); 
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		
	//debug($RTEtsConfigParts);
	//debug($thisConfig);
		if (is_array($thisConfig["userElements."]))	{
	
			$categories=array();
			reset($thisConfig["userElements."]);
			while(list($k)=each($thisConfig["userElements."]))	{
				$ki=intval($k);
				$v = $thisConfig["userElements."][$ki."."];
				if (substr($k,-1)=="." && is_array($v))	{
					$subcats=array();
					$openK = $ki;
					if ($openKeys[$openK])	{
						
						$mArray = "";
						switch ((string)$v["load"])	{
							case "images_from_folder":
								$mArray=array();
								if ($v["path"] && @is_dir(PATH_site.$v["path"]))	{
									$files = t3lib_div::getFilesInDir(PATH_site.$v["path"],"gif,jpg,jpeg,png",0,"");
									if (is_array($files))	{
										reset($files);
										$c=0;
										while(list(,$filename)=each($files))	{
											$iInfo = @getimagesize(PATH_site.$v["path"].$filename);
											$iInfo = $this->calcWH($iInfo,50,100);
										
											$ks=(string)(100+$c);
											$mArray[$ks]=$filename;
											$mArray[$ks."."]=array(
												"content" => '<img src="'.$this->siteUrl.$v["path"].$filename.'">',
												"_icon" => '<img src="'.$this->siteUrl.$v["path"].$filename.'" '.$iInfo[3].' border=0>',
												"description" => $LANG->getLL("filesize").': '.str_replace("&nbsp;"," ",t3lib_div::formatSize(@filesize(PATH_site.$v["path"].$filename))).', '.$LANG->getLL("pixels").': '.$iInfo[0].'x'.$iInfo[1]
											);
											$c++;
										}
									}						
								}
							break;
		/*					case "clear_gifs":
								$mArray=$GLOBALS["PRE_CODED"]["clear_gifs"];
							break;*/
						}
						if (is_array($mArray))	{
							if ($v["merge"])	{
								$v=t3lib_div::array_merge_recursive_overrule($mArray,$v);
							} else {
								$v=$mArray;
							}
						}
		
		//				debug($v);
						reset($v);
						while(list($k2)=each($v))	{
							$k2i = intval($k2);
							if (substr($k2,-1)=="." && is_array($v[$k2i."."]))	{
								$title = trim($v[$k2i]);
								if (!$title)	{
									$title="[".$LANG->getLL("noTitle")."]";
								} else {
									$title=$LANG->sL($title,1);
								}
								$description=$LANG->sL($v[$k2i."."]["description"],1)."<BR>";
								if (!$v[$k2i."."]["dontInsertSiteUrl"])	$v[$k2i."."]["content"] = str_replace("###_URL###",$this->siteUrl,$v[$k2i."."]["content"]);
		
								$logo = $v[$k2i."."]["_icon"] ? $v[$k2i."."]["_icon"] : '';
								
								$onClickEvent='';
								switch((string)$v[$k2i."."]["mode"])	{
									case "wrap":
										$wrap = explode("|",$v[$k2i."."]["content"]);
										$onClickEvent="wrapHTML(unescape('".str_replace("%20"," ",rawurlencode($wrap[0]))."'),unescape('".str_replace("%20"," ",rawurlencode($wrap[1]))."'));";
									break;
									case "processor":
										$script = trim($v[$k2i."."]["submitToScript"]);
										if (substr($script,0,4)!="http")		$script = $this->siteUrl.$script;
							//debug($script);
										if ($script)	{
											$onClickEvent="processSelection(unescape('".rawurlencode($script)."'));";
										}
									break;
									case "insert":
									default:
										$onClickEvent="insertHTML(unescape('".str_replace("%20"," ",rawurlencode($v[$k2i."."]["content"]))."'));";
									break;
								}
								$A=array('<a href="#" onClick="'.$onClickEvent.'return false;">','</a>');
		//						debug($v[$k2i."."]);
								
								$subcats[$k2i]='<tr>
									<td><img src="clear.gif" width="18" height="1"></td>
									<td bgColor="'.$this->doc->bgColor4.'" valign=top>'.$A[0].$logo.$A[1].'</td>
									<td bgColor="'.$this->doc->bgColor4.'" valign=top>'.$A[0].'<strong>'.$title.'</strong><BR>'.$description.$A[1].'</td>
								</tr>';
							}
						}
						ksort($subcats);
					}
					$categories[$ki]=implode("",$subcats);
				}
			}
			ksort($categories);
			
			# Render menu of the items:
			$lines=array();
			reset($categories);
			while(list($k,$v)=each($categories))	{
				$title = trim($thisConfig["userElements."][$k]);
				$openK = $k;
				if (!$title)	{
					$title="[".$LANG->getLL("noTitle")."]";
				} else {
					$title=$LANG->sL($title,1);
				}
				$lines[]='<tr><td colspan=3 bgColor="'.$this->doc->bgColor5.'"><a href="'.t3lib_div::linkThisScript(array("OC_key"=>($openKeys[$openK]?"C|":"O|").$openK)).'"'.t3lib_BEfunc::titleAttrib($LANG->getLL("expand")).'><img src="gfx/ol/'.($openKeys[$openK]?"minus":"plus").'bullet.gif" width="18" height="16" border="0" align=top'.t3lib_BEfunc::titleAttrib($LANG->getLL("expand")).'><strong>'.$title.'</strong></a></td></tr>';
				$lines[]=$v;
			}
			
			$content.='<table border=0 cellpadding=1 cellspacing=1>'.implode("",$lines).'</table>';
		}
	
		$content.= $this->doc->endPage();
		return $content;
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_user.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/rte_user.php"]);
}












// Make instance:
$SOBE = t3lib_div::makeInstance("SC_rte_user");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>