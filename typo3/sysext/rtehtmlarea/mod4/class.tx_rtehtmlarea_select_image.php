<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */
require_once(PATH_typo3.'/class.browse_links.php');
require_once(PATH_t3lib.'class.t3lib_foldertree.php');
require_once(PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');

/**
 * Local Folder Tree
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_image_folderTree extends t3lib_folderTree {
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
			$aOnClick = 'return jumpToUrl(\'?editorNo='.$GLOBALS['SOBE']->browser->editorNo.'&expandFolder='.rawurlencode($v['path']).'\');';
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
		if ($GLOBALS['SOBE']->browser->act=='magic') return 1;	//$webpath='web';	// The web/non-web path does not matter if the mode is 'magic'

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
class tx_rtehtmlarea_select_image extends browse_links {
	var $extKey = 'rtehtmlarea';
	var $content;
	var $act;
	var $allowedItems;
	var $plainMaxWidth;
	var $plainMaxHeight;
	var $magicMaxWidth;
	var $magicMaxHeight;
	var $imgPath;
	var $classesImageJSOptions;
	var $editorNo;
	var $buttonConfig = array();
	
	/**
	 * Initialisation
	 *
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$TYPO3_CONF_VARS;

			// Main GPvars:
		$this->siteUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->act = t3lib_div::_GP('act');
		$this->editorNo = t3lib_div::_GP('editorNo');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		
			// Find "mode"
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode='rte';
		}

			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
		
		if (!$this->act)	{
			$this->act='magic';
		}
		
		$RTEtsConfigParts = explode(':',t3lib_div::_GP('RTEtsConfigParams'));
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		$this->imgPath = $RTEtsConfigParts[6];
		
		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.'])) {
			$this->buttonConfig = $this->thisConfig['buttons.']['image.'];
		}
		
		$this->allowedItems = explode(',','magic,plain,dragdrop,image');
		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->buttonConfig['options.']['removeItems'],1));
		} else {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->thisConfig['blindImageOptions'],1));
		}
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems))	{
			$this->act = current($this->allowedItems);
		}
		
		if ($this->act == 'plain') {
			if ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['plainImageMaxWidth']) $this->plainMaxWidth = $TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['plainImageMaxWidth'];
			if ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['plainImageMaxHeight']) $this->plainMaxHeight = $TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['plainImageMaxHeight'];
			if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['plain.'])) {
				if ($this->buttonConfig['options.']['plain.']['maxWidth']) $this->plainMaxWidth = $this->buttonConfig['options.']['plain.']['maxWidth'];
				if ($this->buttonConfig['options.']['plain.']['maxHeight']) $this->plainMaxHeight = $this->buttonConfig['options.']['plain.']['maxHeight'];
			}
			if (!$this->plainMaxWidth) $this->plainMaxWidth = 640;
			if (!$this->plainMaxHeight) $this->plainMaxHeight = 680;
		} elseif ($this->act == 'magic') {
			if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['magic.'])) {
				if ($this->buttonConfig['options.']['magic.']['maxWidth']) $this->magicMaxWidth = $this->buttonConfig['options.']['magic.']['maxWidth'];
				if ($this->buttonConfig['options.']['magic.']['maxHeight']) $this->magicMaxHeight = $this->buttonConfig['options.']['magic.']['maxHeight'];
			}
				// These defaults allow images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width
			if (!$this->magicMaxWidth) $this->magicMaxWidth = 300;
			if (!$this->magicMaxHeight) $this->magicMaxHeight = 1000;
		}
		
		if($this->thisConfig['classesImage']) {
			$classesImageArray = t3lib_div::trimExplode(',',$this->thisConfig['classesImage'],1);
			$this->classesImageJSOptions = '<option value=""></option>';
			reset($classesImageArray);
			while(list(,$class)=each($classesImageArray)) {
				$this->classesImageJSOptions .= '<option value="' .$class . '">' . $class . '</option>';
			}
		}
		
		$this->magicProcess();
		
			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		
		$this->getJSCode();
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function rteImageStorageDir()	{
		$dir = $this->imgPath ? $this->imgPath : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'];;
		return $dir;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function magicProcess()	{
		global $TYPO3_CONF_VARS;

		if ($this->act=='magic' && t3lib_div::_GP('insertMagicImage'))	{
			$filepath = t3lib_div::_GP('insertMagicImage');

			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;

			$imgInfo = $imgObj->getImageDimensions($filepath);

			if (is_array($imgInfo) && count($imgInfo)==4 && $this->rteImageStorageDir())	{
				$fI=pathinfo($imgInfo[3]);
				$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fI['basename']);
				$destPath =PATH_site.$this->rteImageStorageDir();
				if (@is_dir($destPath))	{
					$destName = $fileFunc->getUniqueName($basename,$destPath);
					@copy($imgInfo[3],$destName);
					
					$cWidth = t3lib_div::intInRange(t3lib_div::_GP('cWidth'),0,$this->magicMaxWidth);
					$cHeight = t3lib_div::intInRange(t3lib_div::_GP('cHeight'),0,$this->magicMaxHeight);
					if (!$cWidth)	$cWidth = $this->magicMaxWidth;
					if (!$cHeight)	$cHeight = $this->magicMaxHeight;
					
					$imgI = $imgObj->imageMagickConvert($filepath,'WEB',$cWidth.'m',$cHeight.'m');	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
					if ($imgI[3])	{
						$fI=pathinfo($imgI[3]);
						$mainBase='RTEmagicC_'.substr(basename($destName),10).'.'.$fI['extension'];
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
	var editor = window.opener.RTEarea[' . $this->editorNo . ']["editor"];
	var HTMLArea = window.opener.HTMLArea;
	function insertImage(file,width,height,origFile)	{
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
		editor.renderPopup_insertImage(\'<img src="\'+file+\'" style="width: \'+styleWidth+\'; height: \'+styleHeight+\';"'.(($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && !(is_array($this->buttonConfig['clickEnlarge.']) && $this->buttonConfig['clickEnlarge.']['disabled']))?' clickenlargesrc="\'+origFile+\'" clickenlarge="0"':'').' />\');
	}
/*]]>*/
</script>
<body>
<script type="text/javascript">
/*<![CDATA[*/
	insertImage(\''.$iurl.'\','.$imgI[0].','.$imgI[1].',\''.substr($imgInfo[3],strlen(PATH_site)).'\');
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
	function getJSCode()	{
		global $LANG,$BACK_PATH,$TYPO3_CONF_VARS;

		$JScode='
			var editor = window.opener.RTEarea[' . $this->editorNo . ']["editor"];
			var HTMLArea = window.opener.HTMLArea;
			function jumpToUrl(URL,anchor)	{	//
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo='.$this->editorNo.'" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode(t3lib_div::_GP('RTEtsConfigParams')).'";

				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.style.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.style.height : "";

				var theLocation = URL+add_act+add_editorNo+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function insertImage(file,width,height,origFile)	{
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
				editor.renderPopup_insertImage(\'<img src="\'+file+\'" style="width: \'+styleWidth+\'; height: \'+styleHeight+\';"'.(($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && !(is_array($this->buttonConfig['clickEnlarge.']) && $this->buttonConfig['clickEnlarge.']['disabled']))?' clickenlargesrc="\'+origFile+\'" clickenlarge="0"':'').' />\');
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
				var floatSelector=\'<select name="iFloat"><option value="">' . $LANG->getLL('notSet') . '</option><option value="none">' . $LANG->getLL('nonFloating') . '</option><option value="left">' . $LANG->getLL('left') . '</option><option value="right">' . $LANG->getLL('right') . '</option></select>\';
				var bgColor=\' class="bgColor4"\';
				var sz="";
				sz+=\'<table border=0 cellpadding=1 cellspacing=1><form action="" name="imageData">\';
				if(classesImage) {
					sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('class').': </td><td>\'+styleSelector+\'</td></tr>\';
				}
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('width').': </td><td><input type="text" name="iWidth" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('height').': </td><td><input type="text" name="iHeight" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('border').': </td><td><input type="checkbox" name="iBorder" value="1" /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('float').': </td><td>\'+floatSelector+\'</td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('margin_lr').': </td><td><input type="text" name="iHspace" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).'></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('margin_tb').': </td><td><input type="text" name="iVspace" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('title').': </td><td><input type="text" name="iTitle"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';
				sz+=\'<tr><td\'+bgColor+\'>'.$LANG->getLL('alt').': </td><td><input type="text" name="iAlt"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';
				'.(($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && !(is_array($this->buttonConfig['clickEnlarge.']) && $this->buttonConfig['clickEnlarge.']['disabled']))?'if (selectedImageRef && selectedImageRef.getAttribute("clickenlargesrc")) sz+=\'<tr><td\'+bgColor+\'><label for="iClickEnlarge">'.$LANG->sL('LLL:EXT:cms/locallang_ttc.php:image_zoom',1).' </label></td><td><input type="checkbox" name="iClickEnlarge" id="iClickEnlarge" value="1" /></td></tr>\';':'').'				sz+=\'<tr><td><input type="submit" value="'.$LANG->getLL('update').'" onClick="return setImageProperties();"></td></tr>\';
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

					if(classesImage) {
						var iClass = document.imageData.iClass.options[document.imageData.iClass.selectedIndex].value;
						if (iClass || (selectedImageRef.attributes["class"] && selectedImageRef.attributes["class"].value))	{
							selectedImageRef.className = iClass;
						}
					}
					
					'.(($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && !(is_array($this->buttonConfig['clickEnlarge.']) && $this->buttonConfig['clickEnlarge.']['disabled']))?'
					if (document.imageData.iClickEnlarge && document.imageData.iClickEnlarge.checked) selectedImageRef.setAttribute("clickenlarge","1");
						else selectedImageRef.setAttribute("clickenlarge","0");':'').'
					
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
					
					'.(($TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] && !(is_array($this->buttonConfig['clickEnlarge.']) && $this->buttonConfig['clickEnlarge.']['disabled']))?'if (selectedImageRef.getAttribute("clickenlargesrc")) {
						if (selectedImageRef.getAttribute("clickenlarge") == "1") document.imageData.iClickEnlarge.checked = 1;
							else document.imageData.iClickEnlarge.removeAttribute("checked");
					}':'').'
				}
				return false;
			}

			function openDragDrop()	{
				var url = "' . $BACK_PATH . t3lib_extMgm::extRelPath($this->extKey) . 'mod3/browse_links.php?mode=filedrag&editorNo='.$this->editorNo.'&bparams=|||"+escape("gif,jpg,jpeg,png");
				window.opener.browserWin = window.open(url,"Typo3WinBrowser","height=350,width=600,status=0,menubar=0,resizable=1,scrollbars=1");
				HTMLArea.edHidePopup();
			}

			var selectedImageRef = getCurrentImageRef();	// Setting this to a reference to the image object.

			'.($this->act=='dragdrop'?'openDragDrop();':'');

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScode = $this->doc->wrapScriptTags($JScode);
	}
	
	/**
	 * Session data for this class can be set from outside with this method.
	 * Call after init()
	 *
	 * @param	array		Session data array
	 * @return	array		Session data and boolean which indicates that data needs to be stored in session because it's changed
	 */
	function processSessionData($data) {
		$store = false;
		
		if ($this->act != 'image') {
			if (isset($this->act))	{
				$data['act'] = $this->act;
				$store = true;
			} else {
				$this->act = $data['act'];
			}
		}
		
		if (isset($this->expandFolder))	{
			$data['expandFolder'] = $this->expandFolder;
			$store = true;
		} else {
			$this->expandFolder = $data['expandFolder'];
		}
		
		return array($data, $store);
	}
	
	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main_rte()	{
		global $LANG, $TYPO3_CONF_VARS, $FILEMOUNTS, $BE_USER;
		
			// Starting content:
		$this->content = $this->doc->startPage($LANG->getLL('Insert Image',1));
		
			// Making menu in top:
		$menuDef = array();
		if (in_array('image',$this->allowedItems) && ($this->act=='image' || t3lib_div::_GP('cWidth'))) {
			$menuDef['page']['isActive'] = $this->act=='image';
			$menuDef['page']['label'] = $LANG->getLL('currentImage',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onClick="jumpToUrl(\'?act=image&editorNo='.$this->editorNo.'\');return false;"';
		}
		if (in_array('magic',$this->allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='magic';
			$menuDef['file']['label'] = $LANG->getLL('magicImage',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onClick="jumpToUrl(\'?act=magic&editorNo='.$this->editorNo.'\');return false;"';
		}
		if (in_array('plain',$this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='plain';
			$menuDef['url']['label'] = $LANG->getLL('plainImage',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onClick="jumpToUrl(\'?act=plain&editorNo='.$this->editorNo.'\');return false;"';
		}
		if (in_array('dragdrop',$this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='dragdrop';
			$menuDef['mail']['label'] = $LANG->getLL('dragDropImage',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onClick="openDragDrop();return false;"';
		}
		$this->content .= $this->doc->getTabMenuRaw($menuDef);
		
		if ($this->act!='image')	{
			
			// ***************************
			// Upload
			// ***************************
				// Create upload/create folder forms, if a path is given:
			if ($BE_USER->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
				$path=$this->expandFolder;
				if (!$path || !@is_dir($path))	{
					$path = $fileProcessor->findTempFolder().'/';	// The closest TEMP-path is found
				}
				if ($path!='/' && @is_dir($path))	{
					$uploadForm=$this->uploadForm($path);
					$createFolder=$this->createFolder($path);
				} else {
					$createFolder='';
					$uploadForm='';
				}
				$this->content .= $uploadForm;
				if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB')) {
					$this->content.=$createFolder;
				}
			}

				// Getting flag for showing/not showing thumbnails:
			$noThumbs = $BE_USER->getTSConfigVal('options.noThumbsInRTEimageSelect');

			if (!$noThumbs)	{
					// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
				$_MOD_MENU = array('displayThumbs' => '');
				$_MCONF['name']='file_list';
				$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
				$addParams = '&act='.$this->act.'&editorNo='.$this->editorNo.'&expandFolder='.rawurlencode($this->expandFolder);
				$thumbNailCheck = t3lib_BEfunc::getFuncCheck('','SET[displayThumbs]',$_MOD_SETTINGS['displayThumbs'],'select_image.php',$addParams).' '.$LANG->sL('LLL:EXT:lang/locallang_mod_file_list.php:displayThumbs',1);
			} else {
				$thumbNailCheck='';
			}

				// File-folders:
			$foldertree = t3lib_div::makeInstance('tx_rtehtmlarea_image_folderTree');
			$tree=$foldertree->getBrowsableTree();
			list(,,$specUid) = explode('_',t3lib_div::_GP('PM'));
			$files = $this->expandFolder($foldertree->specUIDmap[$specUid],$this->act=='plain',$noThumbs?$noThumbs:!$_MOD_SETTINGS['displayThumbs']);
			
			$this->content.= '<table border=0 cellpadding=0 cellspacing=0>
			<tr>
				<td valign=top>'.$this->barheader($LANG->getLL('folderTree').':').$tree.'</td>
				<td>&nbsp;</td>
				<td valign=top>'.$files.'</td>
			</tr>
			</table>
			<br />'.$thumbNailCheck;
			
			// ***************************
			// Help
			// ***************************
			if ($this->act=='magic')	{
				$this->content .= $this->getMsgBox($LANG->getLL('magicImage_msg'));
			}
			if ($this->act=='plain')	{
				$this->content .= $this->getMsgBox(sprintf($LANG->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight));
			}
		} else {
			$JScode = '
				document.write(printCurrentImageOptions());
				insertImagePropertiesInForm();';
			$this->content.= '<br />'.$this->doc->wrapScriptTags($JScode);
		}
		$this->content.= $this->doc->endPage();
		return $this->content;
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
		global $LANG, $BE_USER, $BACK_PATH;

		$expandFolder = $expandFolder ? $expandFolder :t3lib_div::_GP('expandFolder');
		$out='';

		if ($expandFolder && $this->checkFolder($expandFolder))	{
			$files = t3lib_div::getFilesInDir($expandFolder,($plainFlag?'jpg,jpeg,gif,png':$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),1,1);	// $extensionList="",$prependPath=0,$order="")
			if (is_array($files))	{
				reset($files);

				$out.=$this->barheader(sprintf($LANG->getLL('images').' (%s):',count($files)));

				$titleLen = intval($BE_USER->uc['titleLen']);
				$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd(basename($expandFolder),$titleLen));
				$out.='<span class="nobr">'.$picon.'</span><br />';

				$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;
				
				$lines=array();
				while(list(,$filepath)=each($files))	{
					$fI=pathinfo($filepath);
					
					$origFile = t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site)));
					$iurl = $this->siteUrl.$origFile;
					$imgInfo = $imgObj->getImageDimensions($filepath);
						// File icon:
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
					$pDim = $imgInfo[0].'x'.$imgInfo[1].' '.$LANG->getLL('pixels',1);
					$size=' ('.t3lib_div::formatSize(filesize($filepath)).$LANG->getLL('bytes',1).', '.$pDim.')';
					$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$icon.'','width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';
					if (!$plainFlag)	{
						$ATag = '<a href="#" onclick="return jumpToUrl(\'?editorNo='.$this->editorNo.'&insertMagicImage='.rawurlencode($filepath).'\');">';
					} else {
						$ATag = '<a href="#" onclick="return insertImage(\''.$iurl.'\','.$imgInfo[0].','.$imgInfo[1].',\''.$origFile.'\');">';
					}
					$ATag_e='</a>';
					if ($plainFlag && (($imgInfo[0] > $this->plainMaxWidth) || ($imgInfo[1] > $this->plainMaxHeight)))	{
						$ATag='';
						$ATag_e='';
						$ATag2='';
						$ATag2_e='';
					} else {
						$ATag2='<a href="#" onClick="launchView(\''.rawurlencode($filepath).'\'); return false;">';
						$ATag2_e='</a>';
					}

					$filenameAndIcon=$ATag.$icon.htmlspecialchars(t3lib_div::fixed_lgd(basename($filepath),$titleLen)).$ATag_e;


					$lines[]='<tr class="bgColor4"><td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td><td nowrap="nowrap">'.$pDim.'&nbsp;</td></tr>';
					$lines[]='<tr><td colspan="2">'.($noThumbs ? '' : $ATag2.t3lib_BEfunc::getThumbNail($this->doc->backPath.'thumbs.php',$filepath,'hspace="5" vspace="5" border="1"').$ATag2_e).
						'</td></tr>';
					$lines[]='<tr><td colspan="2"><img src="clear.gif" width="1" height="3"></td></tr>';
				}
				$out.='<table border="0" cellpadding="0" cellspacing="1">'.implode('',$lines).'</table>';
			}
		}
		return $out;
	}
	
	/**
	 * For TBE: Makes an upload form for uploading files to the filemount the user is browsing.
	 * The files are uploaded to the tce_file.php script in the core which will handle the upload.
	 *
	 * @param	string		Absolute filepath on server to which to upload.
	 * @return	string		HTML for an upload form.
	 */
	function uploadForm($path)	{
		global $BACK_PATH;
		$count=3;

			// Create header, showing upload path:
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($GLOBALS['LANG']->getLL('uploadImage').':');
		$code.='

			<!--
				Form, for uploading files:
			-->
			<form action="'.$BACK_PATH.'tce_file.php" method="post" name="editform" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'">
				<table border="0" cellpadding="0" cellspacing="3" id="typo3-uplFiles">
					<tr>
						<td><strong>'.$GLOBALS['LANG']->getLL('path',1).':</strong> '.htmlspecialchars($header).'</td>
					</tr>
					<tr>
						<td>';

			// Traverse the number of upload fields (default is 3):
		for ($a=1;$a<=$count;$a++)	{
			$code.='<input type="file" name="upload_'.$a.'"'.$this->doc->formWidth(35).' size="50" />
				<input type="hidden" name="file[upload]['.$a.'][target]" value="'.htmlspecialchars($path).'" />
				<input type="hidden" name="file[upload]['.$a.'][data]" value="'.$a.'" /><br />';
		}

			// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript.'?act='.$this->act.'&editorNo='.$this->editorNo.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
		$code.='<input type="hidden" name="redirect" value="'.htmlspecialchars($redirectValue).'" />'.
				'<input type="submit" name="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit',1).'" />';

		$code.='
			<div id="c-override">
				<input type="checkbox" name="overwriteExistingFiles" value="1" /> '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:overwriteExistingFiles',1).'
			</div>
		';


		$code.='</td>
					</tr>
				</table>
			</form>';

		return $code;
	}
	
		
	/**
	 * For TBE: Makes a form for creating new folders in the filemount the user is browsing.
	 * The folder creation request is sent to the tce_file.php script in the core which will handle the creation.
	 *
	 * @param	string		Absolute filepath on server in which to create the new folder.
	 * @return	string		HTML for the create folder form.
	 */
	function createFolder($path)	{
		global $BACK_PATH;
			// Create header, showing upload path:
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code=$this->barheader($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_newfolder.php.pagetitle').':');
		$code.='

			<!--
				Form, for creating new folders:
			-->
			<form action="'.$BACK_PATH.'tce_file.php" method="post" name="editform2">
				<table border="0" cellpadding="0" cellspacing="3" id="typo3-crFolder">
					<tr>
						<td><strong>'.$GLOBALS['LANG']->getLL('path',1).':</strong> '.htmlspecialchars($header).'</td>
					</tr>
					<tr>
						<td>';

			// Create the new-folder name field:
		$a=1;
		$code.='<input'.$this->doc->formWidth(20).' type="text" name="file[newfolder]['.$a.'][data]" />'.
				'<input type="hidden" name="file[newfolder]['.$a.'][target]" value="'.htmlspecialchars($path).'" />';

			// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript.'?act='.$this->act.'&editorNo='.$this->editorNo.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
		$code.='<input type="hidden" name="redirect" value="'.htmlspecialchars($redirectValue).'" />'.
				'<input type="submit" name="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_newfolder.php.submit',1).'" />';

		$code.='</td>
					</tr>
				</table>
			</form>';

		return $code;
	}


}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']);
}

?>