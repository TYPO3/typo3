<?php
/***************************************************************
*  Copyright notice
* 
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004-2005 Stanislas Rolland (stanislas.rolland@fructifor.ca)
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
 * Displays image selector for the RTE
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Stanislas Rolland <stanislas.rolland@fructifor.ca>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   92: class tx_rtehtmlarea_localFolderTree extends t3lib_folderTree 
 *   99:     function wrapTitle($title,$v)	
 *  113:     function printTree($treeArr="")	
 *  137:     function PM_ATagWrap($icon,$cmd,$bMark="")	
 *  151:     function ext_getRelFolder($path)	
 *  161:     function ext_isLinkable($v)	
 *
 *
 *  192: class tx_rtehtmlarea_select_image 
 *  206:     function preinit()	
 *  254:     function rteImageStorageDir()	
 *  264:     function magicProcess()	
 *  333:     function init()	
 *  478:     function main()	
 *  576:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  602:     function expandFolder($expandFolder=0,$plainFlag=0)	
 *  684:     function uploadForm($path)	
 *  708:     function barheader($str)	
 *  720:     function printCurrentUrl($str)	
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
define('MY_PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once (PATH_t3lib.'class.t3lib_foldertree.php');
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');
$LANG->includeLLFile('EXT:rtehtmlarea/locallang_rtehtmlarea_select_image.php');

/**
 * Local Folder Tree
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_image_localFolderTree extends t3lib_folderTree {
	var $ext_IconMode=1;

	/**
	 * Wrapping the title in a link, if applicable.
	 * 
	 * @param	string		Title, ready for output.
	 * @param	array		The "record"
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			$aOnClick = 'return jumpToUrl(\'?expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Returns true if the input "record" contains a folder which can be linked.
	 * 
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		True is returned if the path is found in the web-part of the the server and is NOT a recycler or temp folder
	 */
	function ext_isLinkable($v)	{
		$webpath=t3lib_BEfunc::getPathType_web_nonweb($v['path']);
		if ($GLOBALS['SOBE']->act=='magic') return 1;	//$webpath='web';	// The web/non-web path does not matter if the mode is 'magic'

		if (strstr($v['path'],'_recycler_') || strstr($v['path'],'_temp_') || $webpath!='web')	{
			return 0;
		} 
		return 1;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($bMark)	{
			$anchor = '#'.$bMark;
			$name=' name="'.$bMark.'"';
		}
		$aOnClick = 'return jumpToUrl(\'?PM='.$cmd.'\',\''.$anchor.'\');';
		return '<a href="#"'.$name.' onclick="'.htmlspecialchars($aOnClick).'">'.$icon.'</a>';
	}

	/**
	 * Print tree.
	 * 
	 * @param	mixed		Input tree array. If not array, then $this->tree is used.
	 * @return	string		HTML output of the tree.
	 */
	function printTree($treeArr='')	{
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);	

		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;
		
			// Traverse rows for the tree and print them into table rows:
		foreach($treeArr as $k => $v) {
			$c++;
			$bgColor=' class="'.(($c+1)%2 ? 'bgColor' : 'bgColor-10').'"';
			$out.='<tr'.$bgColor.'><td nowrap="nowrap">'.$v['HTML'].$this->wrapTitle(t3lib_div::fixed_lgd($v['row']['title'],$titleLen),$v['row']).'</td></tr>';
		}
		
		$out='<table border="0" cellpadding="0" cellspacing="0">'.$out.'</table>';
		return $out;
	}
}


/**
 * Script Class
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_select_image {
	var $content;
	var $siteUrl;
	var $act;
	var $modData;
	var $thisConfig;
	var $allowedItems;
	var $doc;
	var $imgPath;
	var $classesImageJSOptions;

	/**
	 * Pre-initialization - the point is to do some processing before the actual init() function; In between we might have some magic-image processing going on...
	 *
	 * @return	[type]		...
	 */
	function preinit()	{
		global $BE_USER;

		// Current site url:
		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
		
		// Determine nature of current url:
		$this->act=t3lib_div::_GP("act");
		
		$this->modData = $BE_USER->getModuleData("rtehtmlarea_select_image.php","ses");
		if ($this->act!="image")	{
			if (isset($this->act))	{
				$this->modData["act"]=$this->act;
				$BE_USER->pushModuleData("rtehtmlarea_select_image.php",$this->modData);
			} else {
				$this->act=$this->modData["act"];
			}
		}
		$expandPage = t3lib_div::_GP("expandFolder");
		if (isset($expandPage))	{
			$this->modData["expandFolder"]=$expandPage;
			$BE_USER->pushModuleData("rtehtmlarea_select_image.php",$this->modData);
		} else {
			t3lib_div::_GETset($this->modData["expandFolder"],'expandFolder');
		}
		
		if (!$this->act)	{
			$this->act="magic";
		}

		$RTEtsConfigParts = explode(":",t3lib_div::_GP("RTEtsConfigParams"));
//		if (count($RTEtsConfigParts)<2)	die("Error: The GET parameter 'RTEtsConfigParams' was missing. Close the window.");
		$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5])); 
		$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		$this->imgPath = $RTEtsConfigParts[6];

		$this->allowedItems = array_diff(explode(",","magic,plain,dragdrop,image"),t3lib_div::trimExplode(",",$this->thisConfig["blindImageOptions"],1));
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems))	$this->act = current($this->allowedItems);

		if($this->thisConfig['classesImage']) {
			$classesImageArray = t3lib_div::trimExplode(',',$this->thisConfig['classesImage'],1);
			$this->classesImageJSOptions = '<option value=""></option>';
			reset($classesImageArray);
			while(list(,$class)=each($classesImageArray)) {
				$this->classesImageJSOptions .= '<option value="' .$class . '">' . $class . '</option>';
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function rteImageStorageDir()	{
		$dir = $this->imgPath ? $this->imgPath : $GLOBALS["TYPO3_CONF_VARS"]["BE"]["RTE_imageStorageDir"];;
		return $dir;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function magicProcess()	{

		if ($this->act=="magic" && t3lib_div::_GP("insertMagicImage"))	{
			$filepath = t3lib_div::_GP("insertMagicImage");
			
			$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;
		
			$imgInfo = $imgObj->getImageDimensions($filepath);
			
			if (is_array($imgInfo) && count($imgInfo)==4 && $this->rteImageStorageDir())	{
				$fI=pathinfo($imgInfo[3]);
				$fileFunc = t3lib_div::makeInstance("t3lib_basicFileFunctions");
				$basename = $fileFunc->cleanFileName("RTEmagicP_".$fI["basename"]);
				$destPath =PATH_site.$this->rteImageStorageDir();
				if (@is_dir($destPath))	{
					$destName = $fileFunc->getUniqueName($basename,$destPath);
					@copy($imgInfo[3],$destName);
		
					$cHeight=t3lib_div::intInRange(t3lib_div::_GP("cHeight"),0,500);
					$cWidth=t3lib_div::intInRange(t3lib_div::_GP("cWidth"),0,500);
					if (!$cHeight)	$cHeight=200;
					if (!$cWidth)	$cWidth=300;
						// This thing allows images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width 
							$cHeight=1000;

		//			debug(array($cHeight,$cWidth));
		//exit;			
					$imgI = $imgObj->imageMagickConvert($filepath,"WEB",$cWidth."m",$cHeight."m");	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
			//		debug($imgI);
					if ($imgI[3])	{
						$fI=pathinfo($imgI[3]);
						$mainBase="RTEmagicC_".substr(basename($destName),10).".".$fI["extension"];
						$destName = $fileFunc->getUniqueName($mainBase,$destPath);
						@copy($imgI[3],$destName);
		
						$iurl = $this->siteUrl.substr($destName,strlen(PATH_site));
						echo'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Untitled</title>
</head>
<script language="javascript" type="text/javascript">
/*<![CDATA[*/
	var editor = parent.editor;
	var HTMLArea = parent.HTMLArea;
	function insertImage(file,width,height)	{
		var styleWidth, styleHeight;
		styleWidth = parseInt(width);
		if (isNaN(styleWidth) || styleWidth == 0) {
			styleWidth = "auto";
		} else {
			styleWidth += "px";
		}
		styleHeight = parseInt(height);
		if (isNaN(styleHeight) || styleHeight == 0) {
			styleHeight = "auto";
		} else {
			styleHeight += "px";
		}
		editor.renderPopup_insertImage(\'<img src="\'+file+\'" style="width: \'+styleWidth+\'; height: \'+styleHeight+\';" />\');
	}
/*]]>*/
</script>
<body>
<script language="javascript" type="text/javascript">
/*<![CDATA[*/
	insertImage(\''.$iurl.'\','.$imgI[0].','.$imgI[1].');
/*]]>*/
</script>
</body>
</html>';
					}
					
				}
			}
			exit;
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function init()	{
		global $LANG,$BACK_PATH;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='
		<script language="javascript" type="text/javascript">
		/*<![CDATA[*/
			var editor = parent.editor;
			var HTMLArea = parent.HTMLArea;
			function jumpToUrl(URL,anchor)	{	//
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode(t3lib_div::_GP('RTEtsConfigParams')).'";
		
				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.style.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.style.height : "";
		
				var theLocation = URL+add_act+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				document.location = theLocation;
				return false;
			}
			function insertImage(file,width,height)	{
				var styleWidth, styleHeight;
				styleWidth = parseInt(width);
				if (isNaN(styleWidth) || styleWidth == 0) {
					styleWidth = "auto";
				} else {
					styleWidth += "px";
				}
				styleHeight = parseInt(height);
				if (isNaN(styleHeight) || styleHeight == 0) {
					styleHeight = "auto";
				} else {
					styleHeight += "px";
				}
				editor.renderPopup_insertImage(\'<img src="\'+file+\'" style="width: \'+styleWidth+\'; height: \'+styleHeight+\';" />\');
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$this->siteUrl.TYPO3_mainDir.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");	
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function getCurrentImageRef() {
				if (editor._selectedImage) {
					return editor._selectedImage;
				} else {
					return null;
				}
			}
			function printCurrentImageOptions() {
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if(classesImage) var styleSelector=\'<select name="iClass" style="width:140px;">' . $this->classesImageJSOptions  . '</select>\';
		//		var alignSelector=\'<select name="iAlign" style="width:60px;"><option value=""></option><option value="left">Left</option><option value="right">Right</option></select>\';
				var floatSelector=\'<select name="iFloat"><option value="">' . $LANG->getLL('notSet') . '</option><option value="none">' . $LANG->getLL('nonFloating') . '</option><option value="left">' . $LANG->getLL('left') . '</option><option value="right">' . $LANG->getLL('right') . '</option></select>\';
				var bgColor=\' class="bgColor4"\';
				var sz="";
				sz+=\'<table border=0 cellpadding=1 cellspacing=1><form action="" name="imageData">\';
				if(classesImage) {
					sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("class").': \'+styleSelector+\'</td></tr>\';
				}
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("width").': <input type="text" name="iWidth" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' />&nbsp;&nbsp;'.$LANG->getLL("height").': <input type="text" name="iHeight" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' />&nbsp;&nbsp;'.$LANG->getLL("border").': <input type="checkbox" name="iBorder" value="1" /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("float").': \'+floatSelector+\'</td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("margin_lr").': <input type="text" name="iHspace" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).'>&nbsp;&nbsp;'.$LANG->getLL("margin_tb").': <input type="text" name="iVspace" value=""'.$GLOBALS["TBE_TEMPLATE"]->formWidth(4).' /></td></tr>\';
		//		sz+=\'<tr><td\'+bgColor+\'>Textwrapping: \'+alignSelector+\'&nbsp;&nbsp;Style: \'+styleSelector+\'</td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("title").': <input type="text" name="iTitle"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL("alt").': <input type="text" name="iAlt"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).' /></td></tr>\';
				sz+=\'<tr><td><input type="submit" value="'.$LANG->getLL("update").'" onClick="return setImageProperties();"></td></tr>\';
				sz+=\'</form></table>\';
				return sz;
			}
			function setImageProperties() {
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if (selectedImageRef)	{
					if(document.imageData.iWidth.value && document.imageData.iWidth.value != "auto") {
						selectedImageRef.style.width = document.imageData.iWidth.value + "px";
					} else {
						selectedImageRef.style.width = "auto";
					}
					selectedImageRef.removeAttribute("width");
					if(document.imageData.iHeight.value && document.imageData.iHeight.value != "auto") {
						selectedImageRef.style.height=document.imageData.iHeight.value + "px";
					} else {
						selectedImageRef.style.height = "auto";
					}
					selectedImageRef.removeAttribute("height");

					selectedImageRef.style.paddingTop = "0px";
					selectedImageRef.style.paddingBottom = "0px";
					selectedImageRef.style.paddingRight = "0px";
					selectedImageRef.style.paddingLeft = "0px";
					selectedImageRef.style.padding = "";  // this statement ignored by Mozilla 1.3.1
					if(document.imageData.iVspace.value != "" && !isNaN(parseInt(document.imageData.iVspace.value))) {
						selectedImageRef.style.paddingTop = parseInt(document.imageData.iVspace.value) + "px";
						selectedImageRef.style.paddingBottom = selectedImageRef.style.paddingTop;
					}
					if(document.imageData.iHspace.value != "" && !isNaN(parseInt(document.imageData.iHspace.value))) {
						selectedImageRef.style.paddingRight = parseInt(document.imageData.iHspace.value) + "px";
						selectedImageRef.style.paddingLeft = selectedImageRef.style.paddingRight;
					}
					selectedImageRef.removeAttribute("vspace");
					selectedImageRef.removeAttribute("hspace");

					selectedImageRef.title=document.imageData.iTitle.value;
					selectedImageRef.alt=document.imageData.iAlt.value;

					selectedImageRef.style.borderStyle = "none";
					selectedImageRef.style.borderWidth = "0px";
					selectedImageRef.style.border = "";  // this statement ignored by Mozilla 1.3.1
					if(document.imageData.iBorder.checked) {
						selectedImageRef.style.borderStyle = "solid";
						selectedImageRef.style.borderWidth = "thin";
					}
					selectedImageRef.removeAttribute("border");

					var iFloat = document.imageData.iFloat.options[document.imageData.iFloat.selectedIndex].value;
					if (iFloat || selectedImageRef.style.cssFloat || selectedImageRef.style.styleFloat)	{
						if(document.all) {
							selectedImageRef.style.styleFloat = iFloat;
						} else {
							selectedImageRef.style.cssFloat = iFloat;
						}
					}
		
		/*
					var iAlign = document.imageData.iAlign.options[document.imageData.iAlign.selectedIndex].value;
					if (iAlign || selectedImageRef.align)	{
						selectedImageRef.align=iAlign;
					}
					selectedImageRef.style.cssText="";
		*/
					if(classesImage) {
						var iClass = document.imageData.iClass.options[document.imageData.iClass.selectedIndex].value;
						if (iClass || (selectedImageRef.attributes["class"] && selectedImageRef.attributes["class"].value))	{
							selectedImageRef.className = iClass;
						}
					}
					HTMLArea.edHidePopup();
				}
				return false;
			}
			function insertImagePropertiesInForm()	{
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if (selectedImageRef)	{
					var styleWidth, styleHeight, paddingTop, paddingRight;
					styleWidth = selectedImageRef.style.width ? selectedImageRef.style.width : selectedImageRef.width;
					styleWidth = parseInt(styleWidth);
					if (isNaN(styleWidth) || styleWidth == 0) { styleWidth = "auto"; }
					document.imageData.iWidth.value = styleWidth;
					styleHeight = selectedImageRef.style.height ? selectedImageRef.style.height : selectedImageRef.height;
					styleHeight = parseInt(styleHeight);
					if (isNaN(styleHeight) || styleHeight == 0) { styleHeight = "auto"; }
					document.imageData.iHeight.value = styleHeight;

					paddingTop = selectedImageRef.style.paddingTop ? selectedImageRef.style.paddingTop : selectedImageRef.vspace;
					paddingTop = parseInt(paddingTop);
					if (isNaN(paddingTop) || paddingTop < 0) { paddingTop = ""; }
					document.imageData.iVspace.value = paddingTop;
					paddingRight = selectedImageRef.style.paddingRight ? selectedImageRef.style.paddingRight : selectedImageRef.hspace;
					paddingRight = parseInt(paddingRight);
					if (isNaN(paddingRight) || paddingRight < 0) { paddingRight = ""; }
					document.imageData.iHspace.value = paddingRight;

					document.imageData.iTitle.value = selectedImageRef.title;
					document.imageData.iAlt.value = selectedImageRef.alt;

					if((selectedImageRef.style.borderStyle && selectedImageRef.style.borderStyle != "none" && selectedImageRef.style.borderStyle != "none none none none") || selectedImageRef.border) {
						document.imageData.iBorder.checked = 1;
					}

					var fObj=document.imageData.iFloat;
					var value = (selectedImageRef.style.cssFloat ? selectedImageRef.style.cssFloat : selectedImageRef.style.styleFloat);
					var l=fObj.length;
					for (a=0;a<l;a++)	{
						if (fObj.options[a].value == value)	{
							fObj.selectedIndex = a;
						}
					}

		/*
						// Update align
					var fObj=document.imageData.iAlign;
					var value=selectedImageRef.align;
					var l=fObj.length;
					for (a=0;a<l;a++)	{
						if (fObj.options[a].value == value)	{
							fObj.selectedIndex = a;
						}
					}
		*/

					if(classesImage) {
						var fObj=document.imageData.iClass;
						var value=selectedImageRef.className;
						var l=fObj.length;
						for (a=0;a<l;a++)	{
							if (fObj.options[a].value == value)	{
								fObj.selectedIndex = a;
							}
						}
					}
					
				}
				return false;
			}
			
			function openDragDrop()	{
				var url = "rtehtmlarea_browse_links.php?mode=filedrag&bparams=|||"+escape("gif,jpg,jpeg,png");
				//var url = "' . $BACK_PATH . 'browse_links.php?mode=filedrag&bparams=|||"+escape("gif,jpg,jpeg,png");
				parent.opener.browserWin = window.open(url,"Typo3WinBrowser","height=350,width=600,status=0,menubar=0,resizable=1,scrollbars=1");
				HTMLArea.edHidePopup();
			}
		
			var selectedImageRef = getCurrentImageRef();	// Setting this to a reference to the image object.
		
			'.($this->act=="dragdrop"?"openDragDrop();":"").'
			
		//	alert(selectedImageRef.href);
		/*]]>*/
		</script>
		';
		
			// Starting content:
		$this->content="";
		$this->content.=$this->doc->startPage("RTE image insert");
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $LANG, $TYPO3_CONF_VARS, $FILEMOUNTS;
		
		$menu='<table border=0 cellpadding=2 cellspacing=1><tr>';
		$bgcolor=' class="bgColor4"';
		$bgcolorA=' class="bgColor5"';
		if ($this->act=="image" || t3lib_div::_GP("cWidth"))	{	// If $this->act is specifically set to "image" or if cWidth is passed around...
			$menu.='<td align=center nowrap="nowrap" width="25%"'.($this->act=="image"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=image\');return false;"><strong>'.$LANG->getLL("currentImage").'</strong></a></td>';
		}
			if (in_array("magic",$this->allowedItems))	$menu.='<td align=center nowrap="nowrap" width="25%"'.($this->act=="magic"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=magic\');return false;"><strong>'.$LANG->getLL("magicImage").'</strong></a></td>';
			if (in_array("plain",$this->allowedItems))	$menu.='<td align=center nowrap="nowrap" width="25%"'.($this->act=="plain"?$bgcolorA:$bgcolor).'><a href="#" onClick="jumpToUrl(\'?act=plain\');return false;"><strong>'.$LANG->getLL("plainImage").'</strong></a></td>';
			if (in_array("dragdrop",$this->allowedItems))	$menu.='<td align=center nowrap="nowrap" width="25%"'.$bgcolor.'><a href="#" onClick="openDragDrop();return false;"><strong>'.$LANG->getLL("dragDropImage").'</strong></a></td>';
		$menu.='</tr></table>';
		
		$this->content.='<img src=clear.gif width=1 height=2>';
		$this->content.=$menu;
		$this->content.='<img src=clear.gif width=1 height=10>';
		
		if ($this->act!="image")	{

				// Getting flag for showing/not showing thumbnails:
			$noThumbs = $GLOBALS["BE_USER"]->getTSConfigVal("options.noThumbsInRTEimageSelect");
		
			if (!$noThumbs)	{
					// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
				$_MOD_MENU = array('displayThumbs' => '');
				$_MCONF['name']='file_list';
				$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
				$addParams = '&act='.$this->act.'&expandFolder='.rawurlencode($this->modData["expandFolder"]);
				$thumbNailCheck = t3lib_BEfunc::getFuncCheck('','SET[displayThumbs]',$_MOD_SETTINGS['displayThumbs'],'rtehtmlarea_select_image.php',$addParams).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.php:displayThumbs',1);
			} else {
				$thumbNailCheck='';
			}

				// File-folders:	
			$foldertree = t3lib_div::makeInstance("tx_rtehtmlarea_image_localFolderTree");
			$tree=$foldertree->getBrowsableTree();
			list(,,$specUid) = explode("_",t3lib_div::_GP("PM"));
			$files = $this->expandFolder($foldertree->specUIDmap[$specUid],$this->act=="plain",$noThumbs?$noThumbs:!$_MOD_SETTINGS['displayThumbs']);
			
			$this->content.= '<table border=0 cellpadding=0 cellspacing=0>
			<tr>
				<td valign=top>'.$this->barheader($LANG->getLL("folderTree").':').$tree.'</td>
				<td>&nbsp;</td>
				<td valign=top>'.$files.'</td>
			</tr>
			</table>
			<BR>'.$thumbNailCheck;

/*

				// Target:
			if ($this->act!="mail")	{
				$ltarget='<table border=0 cellpadding=2 cellspacing=1><form name="ltargetform" id="ltargetform"><tr>';
				$ltarget.='<td width=90>Target:</td>';
				$ltarget.='<td><input type="text" name="ltarget" onChange="setTarget(this.value);" value="'.htmlspecialchars($curUrlArray["target"]).'"></td>';
				$ltarget.='<td><select name="ltarget_type" onChange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
				<option></option>
				<option value="_top">Top</option>
				<option value="_blank">New window</option>
				</select></td>';
				if (($curUrlInfo["act"]=="page" || $curUrlInfo["act"]=="file") && $curUrlArray["href"])	{
					$ltarget.='<td><input type="submit" value="Update" onClick="return link_current();"></td>';
				}
				$ltarget.='</tr></form></table>';
				
				$this->content.=$ltarget;
			}
*/

			// ***************************
			// Upload
			// ***************************
			$fileProcessor = t3lib_div::makeInstance("t3lib_basicFileFunctions");
			$fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS["BE"]["fileExtensions"]);
			$path=t3lib_div::_GP("expandFolder");

			if (!$path || $path=="/" || !@is_dir($path))	{
				$path = $fileProcessor->findTempFolder();	// The closest TEMP-path is found
				if ($path)	$path.="/";
			}
			if ($path && @is_dir($path))	{
				$this->content.=$this->uploadForm($path)."<BR>";
			}
		
			// ***************************
			// Help
			// ***************************
			
			if ($this->act=="magic")	{
				$this->content.='<img src="'.$this->doc->backPath.'gfx/icon_note.gif" width="18" height="16" align=top>'.$LANG->getLL("magicImage_msg").'<BR>';
			}
			if ($this->act=="plain")	{
				$resolutionLimit_x = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxWidth'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxWidth'] : 640;
				$resolutionLimit_y = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxHeight'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxHeight'] : 680;
				$this->content.='<img src="'.$this->doc->backPath.'gfx/icon_note.gif" width="18" height="16" align=top>' . sprintf($LANG->getLL('plainImage_msg'), $resolutionLimit_x, $resolutionLimit_y) . '<br />';
				
				//$this->content.='<img src="'.$this->doc->backPath.'gfx/icon_note.gif" width="18" height="16" align=top>'.$LANG->getLL("plainImage_msg").'<BR>';
			}
		} else {
			$this->content.='
			<script language="javascript" type="text/javascript">
		document.write(printCurrentImageOptions());
		insertImagePropertiesInForm();
			</script>
			';
		}
	}

	/**
	 * Print content of module
	 * 
	 * @return	void		
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/
	/**
	 * @param	[type]		$expandFolder: ...
	 * @param	[type]		$plainFlag: ...
	 * @return	[type]		...
	 */
	function expandFolder($expandFolder=0,$plainFlag=0,$noThumbs=0)	{
		global $LANG;

		$expandFolder = $expandFolder ? $expandFolder :t3lib_div::_GP("expandFolder");
		$out="";

		$resolutionLimit_x = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxWidth'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxWidth'] : 640;
		$resolutionLimit_y = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxHeight'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plainImageMaxHeight'] : 680;
		
		if ($expandFolder)	{
			$files = t3lib_div::getFilesInDir($expandFolder,($plainFlag?"jpg,jpeg,gif,png":$GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"]),1,1);	// $extensionList="",$prependPath=0,$order="")
			if (is_array($files))	{
				reset($files);
		
				$out.=$this->barheader(sprintf($LANG->getLL("images").' (%s):',count($files)));
			
				$titleLen=intval($GLOBALS["BE_USER"]->uc["titleLen"]);	
				$picon='<img src="'.$this->doc->backPath.'gfx/i/_icon_webfolders.gif" width="18" height="16" align=top>';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
				$out.='<span class="nobr">'.$picon.'</span><BR>';
				
				$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				$lines=array();
				while(list(,$filepath)=each($files))	{
					$fI=pathinfo($filepath);
					
					$iurl = $this->siteUrl.t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site)));
					$imgInfo = $imgObj->getImageDimensions($filepath);
					
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI["extension"]));
					$pDim = $imgInfo[0]."x".$imgInfo[1]." pixels";
					$size=" (".t3lib_div::formatSize(filesize($filepath))."bytes, ".$pDim.")";
					$icon = '<img src="'.$this->doc->backPath.'gfx/fileicons/'.$icon.'" width=18 height=16 border=0 title="'.$fI["basename"].$size.'" class="absmiddle">';
					if (!$plainFlag)	{
						$ATag = '<a href="#" onclick="return jumpToUrl(\'?insertMagicImage='.rawurlencode($filepath).'\');">';
					} else {
						$ATag = '<a href="#" onclick="return insertImage(\''.$iurl.'\','.$imgInfo[0].','.$imgInfo[1].');">';
					}
					$ATag_e="</a>";
					if ($plainFlag && ($imgInfo[0]>$resolutionLimit_x || $imgInfo[1]>$resolutionLimit_y))	{
						$ATag="";
						$ATag_e="";
						$ATag2="";
						$ATag2_e="";
					} else {
						$ATag2='<a href="#" onClick="launchView(\''.rawurlencode($filepath).'\'); return false;">';
						$ATag2_e="</a>";
					}
					
					$filenameAndIcon=$ATag.$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen)).$ATag_e;
					

					$lines[]='<tr class="bgColor4"><td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td><td nowrap="nowrap">'.$pDim.'&nbsp;</td></tr>';
					$lines[]='<tr><td colspan=2>'.(
						$noThumbs ? 
						"" :
						$ATag2.t3lib_BEfunc::getThumbNail($this->doc->backPath.'thumbs.php',$filepath,'hspace="5" vspace="5" border="1"').$ATag2_e).
						'</td></tr>';
					$lines[]='<tr><td colspan=2><img src="clear.gif" width=1 height=3></td></tr>';
				}
				$out.='<table border=0 cellpadding=0 cellspacing=1>'.implode("",$lines).'</table>';
			}
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$path: ...
	 * @return	[type]		...
	 */
	function uploadForm($path)	{
		global $LANG,$SOBE;
	//	debug($path);
		$count=1;
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($LANG->getLL("uploadImage").":");
		$code.='<table border=0 cellpadding=0 cellspacing=3><FORM action="'.$this->doc->backPath.'tce_file.php" method="post" name="editform" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'"><tr><td>';
		$code.="<strong>".$LANG->getLL("path").":</strong> ".$header."</td></tr><tr><td>";
		for ($a=1;$a<=$count;$a++)	{
			$code.='<input type="File" name="upload_'.$a.'"'.$this->doc->formWidth(35).' size="50">
				<input type="Hidden" name="file[upload]['.$a.'][target]" value="'.$path.'">
				<input type="Hidden" name="file[upload]['.$a.'][data]" value="'.$a.'"><BR>';
		}
		$code.='
			<input type="Hidden" name="redirect" value="'.t3lib_extMgm::extRelPath('rtehtmlarea').'rtehtmlarea_select_image.php?act='.$this->act.'&expandFolder='.rawurlencode($path).'&RTEtsConfigParams='.rawurlencode(t3lib_div::_GP("RTEtsConfigParams")).'">
			<input type="Submit" name="submit" value="'.$LANG->sL("LLL:EXT:lang/locallang_core.php:file_upload.php.submit").'">
			<div id="c-override">
				<input type="checkbox" name="overwriteExistingFiles" value="1" /> '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.php:overwriteExistingFiles',1).'
			</div>
			
		</td>
		</tr>
		</FORM>
		</table>';

		return $code;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function barheader($str)	{
		global $LANG,$SOBE;

		return '<table border=0 cellpadding=2 cellspacing=0 width=100% class="bgColor5"><tr><td><strong>'.$str.'</strong></td></tr></table>';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function printCurrentUrl($str)	{
		global $LANG,$SOBE;

		return '<table border=0 cellpadding=0 cellspacing=0 width=100% class="bgColor5"><tr><td><strong>Current Link:</strong> '.$str.'</td></tr></table>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/rtehtmlarea_select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/rtehtmlarea_select_image.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_select_image');
$SOBE->preinit();
$SOBE->magicProcess();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>