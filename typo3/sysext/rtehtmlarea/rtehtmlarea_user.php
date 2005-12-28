<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2005 Stanislas Rolland (stanislas.rolland@fructifor.com)
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
 * User defined content for htmlArea RTE
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @co-author	Stanislas Rolland <stanislas.rolland@fructifor.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   77: class SC_rte_user 
 *   86:     function init()	
 *  171:     function main()	
 *  183:     function printContent()	
 *
 *              SECTION: Other functions
 *  209:     function calcWH($imgInfo,$maxW=380,$maxH=500)	
 *  231:     function main_user($openKeys)	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
define('MY_PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:rtehtmlarea/locallang_rtehtmlarea_user.php');

/**
 * Script Class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_user {
	var $content;
	var $modData;
	var $siteUrl;
	var $doc;	

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

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

		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form = '<form action="" name="process" method="POST">
		<input type="hidden" name="processContent" value="">
		<input type="hidden" name="returnUrl" value="'.htmlspecialchars(t3lib_div::getIndpEnv("REQUEST_URI")).'">
		';
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
			/*<![CDATA[*/
			var editor = parent.editor;
			var HTMLArea = parent.HTMLArea;
			function insertHTML(content,noHide) {
				editor.insertHTML(content);
				if(!noHide) HTMLArea.edHidePopup();
			}
			function wrapHTML(wrap1,wrap2,noHide) {
				if(editor.hasSelectedText()) {
					editor.surroundHTML(wrap1,wrap2);
				} else {
					alert('.$GLOBALS['LANG']->JScharCode($LANG->getLL("noTextSelection")).');
				}
				if(!noHide) HTMLArea.edHidePopup();
			}
			function processSelection(script) {
				document.process.action = script;
				document.process.processContent.value = editor.getSelectedHTML();
				document.process.submit();
			}
			/*]]>*/
		</script>
		';

		$this->modData = $BE_USER->getModuleData("rtehtmlarea_user.php","ses");
		if (t3lib_div::_GP("OC_key"))	{
			$parts = explode("|",t3lib_div::_GP("OC_key"));
			$this->modData["openKeys"][$parts[1]] = $parts[0]=="O" ? 1 : 0;
			$BE_USER->pushModuleData("rtehtmlarea_user.php",$this->modData);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->main_user($this->modData["openKeys"]);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}



	/********************************
	 *
	 * Other functions
	 *
	 *********************************/

	/**
	 * @param	[type]		$imgInfo: ...
	 * @param	[type]		$maxW: ...
	 * @param	[type]		$maxH: ...
	 * @return	[type]		...
	 */
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
	
	/**
	 * Rich Text Editor (RTE) user element selector
	 * 
	 * @param	[type]		$openKeys: ...
	 * @return	[type]		...
	 */
	function main_user($openKeys)	{
		global $SOBE,$LANG,$BACK_PATH;
			// Starting content:
		$content.=$this->doc->startPage("RTE user");
		
		$RTEtsConfigParts = explode(":",t3lib_div::_GP("RTEtsConfigParams"));
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
										//$onClickEvent="wrapHTML(unescape('" . str_replace("%20"," ",rawurlencode($wrap[0])) . "'),unescape('".str_replace("%20"," ",rawurlencode($wrap[1]))."'));";
										//$onClickEvent="alert(wrapHTML(" . $GLOBALS['LANG']->JScharCode(t3lib_div::htmlspecialchars_decode($wrap[0])) . ", " . $GLOBALS['LANG']->JScharCode(t3lib_div::htmlspecialchars_decode($wrap[1])) . "));";
										$onClickEvent='wrapHTML(' . $GLOBALS['LANG']->JScharCode($wrap[0]) . ',' . $GLOBALS['LANG']->JScharCode($wrap[1]) . ',false);';
									break;
									case "processor":
										$script = trim($v[$k2i."."]["submitToScript"]);
										if (substr($script,0,4)!="http") $script = $this->siteUrl.$script;
							//debug($script);
										if ($script)	{
											$onClickEvent="processSelection(unescape('".rawurlencode($script)."'));";
										}
									break;
									case "insert":
									default:
										//$onClickEvent="insertHTML(unescape('".str_replace("%20"," ",rawurlencode($v[$k2i."."]["content"]))."'));";
										$onClickEvent='insertHTML(' . $GLOBALS['LANG']->JScharCode($v[$k2i . '.']['content']) . ');';
									break;
								}
								$A=array('<a href="#" onClick="'.$onClickEvent.'return false;">','</a>');
		//						debug($v[$k2i."."]);
								
								$subcats[$k2i]='<tr>
									<td><img src="clear.gif" width="18" height="1"></td>
									<td class="bgColor4" valign=top>'.$A[0].$logo.$A[1].'</td>
									<td class="bgColor4" valign=top>'.$A[0].'<strong>'.$title.'</strong><BR>'.$description.$A[1].'</td>
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
				$lines[]='<tr><td colspan=3 class="bgColor5"><a href="'.t3lib_div::linkThisScript(array("OC_key"=>($openKeys[$openK]?"C|":"O|").$openK)).'" title="'.$LANG->getLL("expand",1).'"><img src="'.$BACK_PATH.'gfx/ol/'.($openKeys[$openK]?"minus":"plus").'bullet.gif" width="18" height="16" border="0" align=top title="'.$LANG->getLL("expand",1).'"><strong>'.$title.'</strong></a></td></tr>';
				$lines[]=$v;
			}
			
			$content.='<table border=0 cellpadding=1 cellspacing=1>'.implode("",$lines).'</table>';
		}
	
		$content.= $this->doc->endPage();
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/rtehtmlarea_user.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/rtehtmlarea_user.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_user');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
