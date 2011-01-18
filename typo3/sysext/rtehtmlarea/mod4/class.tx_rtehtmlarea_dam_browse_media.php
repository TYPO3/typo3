<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasper@typo3.com)
*  (c) 2004-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * @author	Kasper Skårhøj <kasper@typo3.com>
 *
 * $Id$  *
 */
require_once(t3lib_extMgm::extPath('dam').'class.tx_dam_browse_media.php');

/**
 * Script Class
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_dam_browse_media extends tx_dam_browse_media {
	var $extKey = 'rtehtmlarea';
	var $content;
	var $act;
	var $allowedItems;
	var $removedProperties = array();
	var $defaultClass;
	var $plainMaxWidth;
	var $plainMaxHeight;
	var $lockPlainWidth = 'false';
	var $lockPlainHeight = 'false';
	var $magicMaxWidth;
	var $magicMaxHeight;
	var $imgPath;
	var $imgTitleDAMColumn = '';
	var $classesImageJSOptions;
	var $editorNo;
	var $sys_language_content;
	var $thisConfig;
	var $buttonConfig = array();

	/**
	 * Check if this object should be rendered.
	 *
	 * @param	string		$type Type: "file", ...
	 * @param	object		$pObj Parent object.
	 * @return	boolean
	 * @see SC_browse_links::main()
	 */
	function isValid($type, $pObj) {
		$isValid = false;

		$pArr = explode('|', t3lib_div::_GP('bparams'));

		if ($type=='rte' && $pObj->button == 'image') {
			$isValid = true;
		}

		return $isValid;
	}

	/**
	 * Rendering
	 * Called in SC_browse_links::main() when isValid() returns true;
	 *
	 * @param	string		$type Type: "file", ...
	 * @param	object		$pObj Parent object.
	 * @return	string		Rendered content
	 * @see SC_browse_links::main()
	 */
	function render($type, $pObj) {
		global $LANG, $BE_USER, $BACK_PATH;

		$this->pObj = $pObj;

			// init class browse_links
		$this->init();

		switch((string)$this->mode)	{
			case 'rte':
				$content = $this->main_rte();
			break;
//			case 'wizard':
//				$content = $this->main_rte(1);
//			break;
			default:
				$content = '';
			break;
		}

		return $content;
	}

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
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');

			// Find RTE parameters
		$this->bparams = t3lib_div::_GP('bparams');
		$this->editorNo = t3lib_div::_GP('editorNo');
		$this->sys_language_content = t3lib_div::_GP('sys_language_content');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		if (!$this->editorNo) {
			$pArr = explode('|', $this->bparams);
			$pRteArr = explode(':', $pArr[1]);
			$this->editorNo = $pRteArr[0];
			$this->sys_language_content = $pRteArr[1];
			$this->RTEtsConfigParams = $pArr[2];
		}

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

			// init fileProcessor
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);


		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		$this->imgPath = $RTEtsConfigParts[6];

		if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.'])) {
			$this->buttonConfig = $this->thisConfig['buttons.']['image.'];
			t3lib_div::loadTCA('tx_dam');
			if (is_array($this->buttonConfig['title.']) && is_array($TCA['tx_dam']['columns'][$this->buttonConfig['title.']['useDAMColumn']])) {
				$this->imgTitleDAMColumn = $this->buttonConfig['title.']['useDAMColumn'];
			}
			if (is_array($this->buttonConfig['properties.'])) {
				if ($this->buttonConfig['properties.']['removeItems']) {
					$this->removedProperties = t3lib_div::trimExplode(',',$this->buttonConfig['properties.']['removeItems'],1);
				}
				if (is_array($this->buttonConfig['properties.']['class.']) && trim($this->buttonConfig['properties.']['class.']['default'])) {
					$this->defaultClass = trim($this->buttonConfig['properties.']['class.']['default']);
				}
			}
		}

		if (is_array($this->thisConfig['proc.']) && $this->thisConfig['proc.']['plainImageMode']) {
			$plainImageMode = $this->thisConfig['proc.']['plainImageMode'];
			$this->lockPlainWidth = ($plainImageMode == 'lockDimensions')?'true':'false';
			$this->lockPlainHeight = ($this->lockPlainWidth || $plainImageMode == 'lockRatio' || ($plainImageMode == 'lockRatioWhenSmaller'))?'true':'false';
		}

		if (!$this->imgTitleDAMColumn) {
			$this->imgTitleDAMColumn = 'caption';
		}

		$this->allowedItems = explode(',','magic,plain,image,upload');
		$clientInfo = t3lib_div::clientInfo();
		if ($clientInfo['BROWSER'] !== 'opera') {
			$this->allowedItems[] = 'dragdrop';
		}
		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->buttonConfig['options.']['removeItems'],1));
		} else {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->thisConfig['blindImageOptions'],1));
		}

		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems))	{
			$this->act = current($this->allowedItems);
		}

		if ($this->act == 'magic') {
			if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['magic.'])) {
				if ($this->buttonConfig['options.']['magic.']['maxWidth']) $this->magicMaxWidth = $this->buttonConfig['options.']['magic.']['maxWidth'];
				if ($this->buttonConfig['options.']['magic.']['maxHeight']) $this->magicMaxHeight = $this->buttonConfig['options.']['magic.']['maxHeight'];
			}
				// These defaults allow images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width
			if (!$this->magicMaxWidth) $this->magicMaxWidth = 300;
			if (!$this->magicMaxHeight) $this->magicMaxHeight = 1000;
		} elseif ($this->act == 'plain') {
			if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['plain.'])) {
				if ($this->buttonConfig['options.']['plain.']['maxWidth']) $this->plainMaxWidth = $this->buttonConfig['options.']['plain.']['maxWidth'];
				if ($this->buttonConfig['options.']['plain.']['maxHeight']) $this->plainMaxHeight = $this->buttonConfig['options.']['plain.']['maxHeight'];
			}
			if (!$this->plainMaxWidth) $this->plainMaxWidth = 640;
			if (!$this->plainMaxHeight) $this->plainMaxHeight = 680;
		}

		if ($this->thisConfig['classesImage']) {
			$classesImageArray = t3lib_div::trimExplode(',',$this->thisConfig['classesImage'],1);
			$this->classesImageJSOptions = '<option value=""></option>';
			foreach ($classesImageArray as $class) {
				$this->classesImageJSOptions .= '<option value="' .$class . '">' . $class . '</option>';
			}
		}

			// init the DAM object
		$this->initDAM();

		$this->getModSettings();

		$this->processParams();

			// Insert the image if we are done
		$this->imageInsert();

			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
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
	function imageInsert()	{
		global $TCA,$TYPO3_CONF_VARS;

		if (t3lib_div::_GP('insertImage')) {
			$filepath = t3lib_div::_GP('insertImage');

			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;
			$imgInfo = $imgObj->getImageDimensions($filepath);
			$imgMetaData = tx_dam::meta_getDataForFile($filepath,'uid,pid,alt_text,hpixels,vpixels,'.$this->imgTitleDAMColumn.','.$TCA['tx_dam']['ctrl']['languageField']);
			$imgMetaData = $this->getRecordOverlay('tx_dam',$imgMetaData,$this->sys_language_content);

			switch ($this->act) {
				case 'magic':
					if (is_array($imgInfo) && count($imgInfo)==4 && $this->rteImageStorageDir() && is_array($imgMetaData))	{
						$fI=pathinfo($imgInfo[3]);
						$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
						$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fI['basename']);
						$destPath =PATH_site.$this->rteImageStorageDir();
						if (@is_dir($destPath))	{
							$destName = $fileFunc->getUniqueName($basename,$destPath);
							@copy($imgInfo[3],$destName);
							t3lib_div::fixPermissions($destName);
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
								t3lib_div::fixPermissions($destName);
								$iurl = $this->siteUrl.substr($destName,strlen(PATH_site));
								$this->imageInsertJS($iurl,$imgI[0],$imgI[1],$imgMetaData['alt_text'],$imgMetaData[$this->imgTitleDAMColumn],substr($imgInfo[3],strlen(PATH_site)));
							}
						}
					}
					exit;
					break;
				case 'plain':
					if (is_array($imgInfo) && count($imgInfo)==4 && is_array($imgMetaData))	{
						$iurl = $this->siteUrl.substr($imgInfo[3],strlen(PATH_site));
						$this->imageInsertJS($iurl,$imgMetaData['hpixels'],$imgMetaData['vpixels'],$imgMetaData['alt_text'],$imgMetaData[$this->imgTitleDAMColumn],substr($imgInfo[3],strlen(PATH_site)));
					}
					exit;
					break;
			}
		}
	}

	function imageInsertJS($url,$width,$height,$altText,$titleText,$origFile) {
		global $TYPO3_CONF_VARS;

		echo'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Untitled</title>
</head>
<script type="text/javascript">
/*<![CDATA[*/
	var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Image");
	function insertImage(file,width,height,alt,title)	{
		plugin.insertImage(\'<img src="\'+file+\'"'  . ($this->defaultClass?(' class="'.$this->defaultClass.'"'):'') . ' alt="\'+alt+\'" title="\'+title+\'" width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'" />\');
	}
/*]]>*/
</script>
<body>
<script type="text/javascript">
/*<![CDATA[*/
	insertImage('.t3lib_div::quoteJSvalue($url,1).','.$width.','.$height.','.t3lib_div::quoteJSvalue($altText,1).','.t3lib_div::quoteJSvalue($titleText,1).');
/*]]>*/
</script>
</body>
</html>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getJSCode()	{
		global $LANG,$BACK_PATH,$TYPO3_CONF_VARS;

		$JScode='
			var plugin = window.parent.RTEarea["' . $editorNo . '"].editor.getPlugin("TYPO3Image");
			var HTMLArea = window.parent.HTMLArea;
			var Ext = window.parent.Ext;
			if (Ext.isWebKit) {
				plugin.dialog.mon(Ext.get(plugin.dialog.getComponent("content-iframe").getEl().dom.contentWindow.document.documentElement), "dragend", plugin.onDrop, plugin, {single: true});
			}
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{
				return jumpToUrl(\''.$this->thisScript.'?act='.$this->act.'&mode='.$this->mode.'&bparams='.$this->bparams.'&insertImage='.'\'+fp);
			}
			function jumpToUrl(URL,anchor)	{
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo='.$this->editorNo.'" : "";
				var add_sys_language_content = URL.indexOf("sys_language_content=")==-1 ? "&sys_language_content='.$this->sys_language_content.'" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode(t3lib_div::_GP('RTEtsConfigParams')).'";

				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.style.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.style.height : "";

				var theLocation = URL+add_act+add_editorNo+add_sys_language_content+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$this->siteUrl.TYPO3_mainDir.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function getCurrentImageRef() {
				if (plugin.image) {
					return plugin.image;
				} else {
					return null;
				}
			}
			function printCurrentImageOptions() {
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if (classesImage) var styleSelector=\'<select id="iClass" name="iClass" style="width:140px;">' . $this->classesImageJSOptions  . '</select>\';
				var floatSelector=\'<select id="iFloat" name="iFloat"><option value="">' . $LANG->getLL('notSet') . '</option><option value="none">' . $LANG->getLL('nonFloating') . '</option><option value="left">' . $LANG->getLL('left') . '</option><option value="right">' . $LANG->getLL('right') . '</option></select>\';
				var bgColor=\' class="bgColor4"\';
				var sz="";
				sz+=\'<table border=0 cellpadding=1 cellspacing=1><form action="" name="imageData">\';
				'.(in_array('class', $this->removedProperties)?'':'
				if(classesImage) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iClass">'.$LANG->getLL('class').': </label></td><td>\'+styleSelector+\'</td></tr>\';
				}')
				.(in_array('width', $this->removedProperties)?'':'
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && '. $this->lockPlainWidth .')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iWidth">'.$LANG->getLL('width').': </label></td><td><input type="text" id="iWidth" name="iWidth" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				}')
				.(in_array('height', $this->removedProperties)?'':'
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && '. $this->lockPlainHeight .')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iHeight">'.$LANG->getLL('height').': </label></td><td><input type="text" id="iHeight" name="iHeight" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				}')
				.(in_array('border', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iBorder">'.$LANG->getLL('border').': </label></td><td><input type="checkbox" id="iBorder" name="iBorder" value="1" /></td></tr>\';')
				.(in_array('float', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iFloat">'.$LANG->getLL('float').': </label></td><td>\'+floatSelector+\'</td></tr>\';')
				.(in_array('paddingTop', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingTop">'.$LANG->getLL('padding_top').': </label></td><td><input type="text" id="iPaddingTop" name="iPaddingTop" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).'></td></tr>\';')
				.(in_array('paddingRight', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingRight">'.$LANG->getLL('padding_right').': </label></td><td><input type="text" id="iPaddingRight" name="iPaddingRight" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('paddingBottom', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingBottom">'.$LANG->getLL('padding_bottom').': </label></td><td><input type="text" id="iPaddingBottom" name="iPaddingBottom" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('paddingLeft', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingLeft">'.$LANG->getLL('padding_left').': </label></td><td><input type="text" id="iPaddingLeft" name="iPaddingLeft" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('title', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iTitle">'.$LANG->getLL('title').': </label></td><td><input type="text" id="iTitle" name="iTitle"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';')
				.(in_array('alt', $this->removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iAlt">'.$LANG->getLL('alt').': </label></td><td><input type="text" id="iAlt" name="iAlt"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';')
				.((!$TYPO3_CONF_VARS['EXTCONF'][$this->extKey]['enableClickEnlarge'] || in_array('clickenlarge', $this->removedProperties))?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iClickEnlarge">'.$LANG->sL('LLL:EXT:cms/locallang_ttc.php:image_zoom',1).' </label></td><td><input type="checkbox" name="iClickEnlarge" id="iClickEnlarge" value="0" /></td></tr>\';').'
				sz+=\'<tr><td><input type="submit" value="'.$LANG->getLL('update').'" onClick="return setImageProperties();"></td></tr>\';
				sz+=\'</form></table>\';
				return sz;
			}
			function setImageProperties() {
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if (selectedImageRef)	{
					if (document.imageData.iWidth) {
						if (document.imageData.iWidth.value && parseInt(document.imageData.iWidth.value)) {
							selectedImageRef.style.width = "";
							selectedImageRef.width = parseInt(document.imageData.iWidth.value);
						}
					}
					if (document.imageData.iHeight) {
						if (document.imageData.iHeight.value && parseInt(document.imageData.iHeight.value)) {
							selectedImageRef.style.height = "";
							selectedImageRef.height = parseInt(document.imageData.iHeight.value);
						}
					}
					if (document.imageData.iPaddingTop) {
						if (document.imageData.iPaddingTop.value != "" && !isNaN(parseInt(document.imageData.iPaddingTop.value))) {
							selectedImageRef.style.paddingTop = parseInt(document.imageData.iPaddingTop.value) + "px";
						} else {
							selectedImageRef.style.paddingTop = "";
						}
					}
					if (document.imageData.iPaddingRight) {
						if (document.imageData.iPaddingRight.value != "" && !isNaN(parseInt(document.imageData.iPaddingRight.value))) {
							selectedImageRef.style.paddingRight = parseInt(document.imageData.iPaddingRight.value) + "px";
						} else {
							selectedImageRef.style.paddingRight = "";
						}
					}
					if (document.imageData.iPaddingBottom) {
						if (document.imageData.iPaddingBottom.value != "" && !isNaN(parseInt(document.imageData.iPaddingBottom.value))) {
							selectedImageRef.style.paddingBottom = parseInt(document.imageData.iPaddingBottom.value) + "px";
						} else {
							selectedImageRef.style.paddingBottom = "";
						}
					}
					if (document.imageData.iPaddingLeft) {
						if (document.imageData.iPaddingLeft.value != "" && !isNaN(parseInt(document.imageData.iPaddingLeft.value))) {
							selectedImageRef.style.paddingLeft = parseInt(document.imageData.iPaddingLeft.value) + "px";
						} else {
							selectedImageRef.style.paddingLeft = "";
						}
					}
					if (document.imageData.iTitle) {
						selectedImageRef.title=document.imageData.iTitle.value;
					}
					if (document.imageData.iAlt) {
						selectedImageRef.alt=document.imageData.iAlt.value;
					}

					if (document.imageData.iBorder) {
						selectedImageRef.style.borderStyle = "";
						selectedImageRef.style.borderWidth = "";
						selectedImageRef.style.border = "";  // this statement ignored by Mozilla 1.3.1
						selectedImageRef.style.borderTopStyle = "";
						selectedImageRef.style.borderRightStyle = "";
						selectedImageRef.style.borderBottomStyle = "";
						selectedImageRef.style.borderLeftStyle = "";
						selectedImageRef.style.borderTopWidth = "";
						selectedImageRef.style.borderRightWidth = "";
						selectedImageRef.style.borderBottomWidth = "";
						selectedImageRef.style.borderLeftWidth = "";
						if(document.imageData.iBorder.checked) {
							selectedImageRef.style.borderStyle = "solid";
							selectedImageRef.style.borderWidth = "thin";
						}
						selectedImageRef.removeAttribute("border");
					}
					if (document.imageData.iFloat) {
						var iFloat = document.imageData.iFloat.options[document.imageData.iFloat.selectedIndex].value;
						if (document.all) {
							selectedImageRef.style.styleFloat = iFloat ? iFloat : "";
						} else {
							selectedImageRef.style.cssFloat = iFloat ? iFloat : "";
						}
					}
					if (classesImage && document.imageData.iClass) {
						var iClass = document.imageData.iClass.options[document.imageData.iClass.selectedIndex].value;
						if (iClass || (selectedImageRef.attributes["class"] && selectedImageRef.attributes["class"].value)) {
							selectedImageRef.className = iClass;
						} else {
							selectedImageRef.className = "";
						}
					}

					if (document.imageData.iClickEnlarge) {
						if (document.imageData.iClickEnlarge.checked) {
							selectedImageRef.setAttribute("clickenlarge","1");
						} else {
							selectedImageRef.removeAttribute("clickenlarge");
						}
					}
					plugin.close();
				}
				return false;
			}
			function insertImagePropertiesInForm()	{
				var classesImage = ' . ($this->thisConfig['classesImage']?'true':'false') . ';
				if (selectedImageRef)	{
					var styleWidth, styleHeight, padding;
					if (document.imageData.iWidth) {
						styleWidth = selectedImageRef.style.width ? selectedImageRef.style.width : selectedImageRef.width;
						styleWidth = parseInt(styleWidth);
						if (!(isNaN(styleWidth) || styleWidth == 0)) {
							document.imageData.iWidth.value = styleWidth;
						}
					}
					if (document.imageData.iHeight) {
						styleHeight = selectedImageRef.style.height ? selectedImageRef.style.height : selectedImageRef.height;
						styleHeight = parseInt(styleHeight);
						if (!(isNaN(styleHeight) || styleHeight == 0)) {
							document.imageData.iHeight.value = styleHeight;
						}
					}
					if (document.imageData.iPaddingTop) {
						var padding = selectedImageRef.style.paddingTop ? selectedImageRef.style.paddingTop : selectedImageRef.vspace;
						var padding = parseInt(padding);
						if (isNaN(padding) || padding <= 0) { padding = ""; }
						document.imageData.iPaddingTop.value = padding;
					}
					if (document.imageData.iPaddingRight) {
						padding = selectedImageRef.style.paddingRight ? selectedImageRef.style.paddingRight : selectedImageRef.hspace;
						var padding = parseInt(padding);
						if (isNaN(padding) || padding <= 0) { padding = ""; }
						document.imageData.iPaddingRight.value = padding;
					}
					if (document.imageData.iPaddingBottom) {
						var padding = selectedImageRef.style.paddingBottom ? selectedImageRef.style.paddingBottom : selectedImageRef.vspace;
						var padding = parseInt(padding);
						if (isNaN(padding) || padding <= 0) { padding = ""; }
						document.imageData.iPaddingBottom.value = padding;
					}
					if (document.imageData.iPaddingLeft) {
						var padding = selectedImageRef.style.paddingLeft ? selectedImageRef.style.paddingLeft : selectedImageRef.hspace;
						var padding = parseInt(padding);
						if (isNaN(padding) || padding <= 0) { padding = ""; }
						document.imageData.iPaddingLeft.value = padding;
					}
					if (document.imageData.iTitle) {
						document.imageData.iTitle.value = selectedImageRef.title;
					}
					if (document.imageData.iAlt) {
						document.imageData.iAlt.value = selectedImageRef.alt;
					}
					if (document.imageData.iBorder) {
						if((selectedImageRef.style.borderStyle && selectedImageRef.style.borderStyle != "none" && selectedImageRef.style.borderStyle != "none none none none") || selectedImageRef.border) {
							document.imageData.iBorder.checked = 1;
						}
					}
					if (document.imageData.iFloat) {
						var fObj=document.imageData.iFloat;
						var value = (selectedImageRef.style.cssFloat ? selectedImageRef.style.cssFloat : selectedImageRef.style.styleFloat);
						var l=fObj.length;
						for (var a=0;a<l;a++)	{
							if (fObj.options[a].value == value) {
								fObj.selectedIndex = a;
							}
						}
					}

					if (classesImage && document.imageData.iClass) {
						var fObj=document.imageData.iClass;
						var value=selectedImageRef.className;
						var l=fObj.length;
						for (var a=0;a < l; a++)	{
							if (fObj.options[a].value == value)	{
								fObj.selectedIndex = a;
							}
						}
					}
					if (document.imageData.iClickEnlarge) {
						if (selectedImageRef.getAttribute("clickenlarge") == "1") {
							document.imageData.iClickEnlarge.checked = 1;
						} else {
							document.imageData.iClickEnlarge.checked = 0;
						}
					}
					return false;
				}
			}

			var selectedImageRef = getCurrentImageRef();';	// Setting this to a reference to the image object.

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScodeArray['rtehtmlarea'] = $JScode;
	}

	function reinitParams() {
		if ($this->editorNo) {
			$pArr = explode('|', $this->bparams);
			$pArr[1] = implode(':', array($this->editorNo, $this->sys_language_content));
			$pArr[2] = $this->RTEtsConfigParams;
			if ($this->act == 'dragdrop' || $this->act == 'plain') {
				$pArr[3] = 'jpg,jpeg,gif,png';
			}
			$this->bparams = implode('|', $pArr);
		}
		parent::reinitParams();
	}

	/**
	 * Return true or false whether thumbs should be displayed or not
	 *
	 * @return	boolean
	 */
	function displayThumbs() {
		global $BE_USER;
		return parent::displayThumbs() && !$BE_USER->getTSConfigVal('options.noThumbsInRTEimageSelect') && ($this->act != 'dragdrop');
	}

	/**
	 * Create HTML checkbox to enable/disable thumbnail display
	 *
	 * @return	string HTML code
	 */
	function addDisplayOptions() {
		global $BE_USER;

			// Getting flag for showing/not showing thumbnails:
		$noThumbs = $BE_USER->getTSConfigVal('options.noThumbsInEB') || ($this->mode == 'rte' && $BE_USER->getTSConfigVal('options.noThumbsInRTEimageSelect')) || ($this->act == 'dragdrop');
		if ($noThumbs)	{
			$thumbNailCheckbox = '';
		} else {

			$thumbNailCheckbox = t3lib_BEfunc::getFuncCheck('', 'SET[displayThumbs]',$this->displayThumbs(), $this->thisScript, t3lib_div::implodeArrayForUrl('',$this->addParams));
			$description = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:displayThumbs',1);
			$id = 'l'.uniqid('tx_dam_scbase');
			$idAttr = ' id="'.$id.'"';
			$thumbNailCheckbox = str_replace('<input', '<input'.$idAttr, $thumbNailCheckbox);
			$thumbNailCheckbox .= ' <label for="'.$id.'">'.$description.'</label>';
			$this->damSC->addOption('html', 'thumbnailCheckbox', $thumbNailCheckbox);
		}
		$this->damSC->addOption('funcCheck', 'extendedInfo', $GLOBALS['LANG']->getLL('displayExtendedInfo',1));
	}

	/**
	 * Render list of files.
	 *
	 * @param	array		List of files. See t3lib_div::getFilesInDir
	 * @param	string		$mode EB mode: "db", "file", ...
	 * @return	string		HTML output
	 */
	function renderFileList($files, $mode='file') {
		global $LANG, $BACK_PATH, $BE_USER, $TYPO3_CONF_VARS;

		$out = '';

			// Listing the files:
		if (is_array($files) AND count($files))	{

			$displayThumbs = $this->displayThumbs();
			$displayImage = ($this->act === 'dragdrop');

				// Traverse the file list:
			$lines=array();
			foreach($files as $fI)	{

				if (!$fI['__exists']) {
					continue;
				}

					// Create file icon:
				$titleAttrib = tx_dam_guiFunc::icon_getTitleAttribute($fI);
				$iconFile = tx_dam::icon_getFileType($fI);
				$iconTag = tx_dam_guiFunc::icon_getFileTypeImgTag($fI);
				$iconAndFilename = $iconTag.htmlspecialchars(t3lib_div::fixed_lgd_cs($fI['file_title'], $BE_USER->uc['titleLen']));


					// Create links for adding the file:
				if (strstr($fI['file_name_absolute'], ',') || strstr($fI['file_name_absolute'], '|'))	{	// In case an invalid character is in the filepath, display error message:
					$eMsg = $LANG->JScharCode(sprintf($LANG->getLL('invalidChar'), ', |'));
					$ATag_insert = '<a href="#" onclick="alert('.$eMsg.');return false;">';

					// If filename is OK, just add it:
				} else {

						// JS: insertElement(table, uid, type, filename, fpath, filetype, imagefile ,action, close)
					$onClick_params = implode (', ', array(
						"'".$fI['_ref_table']."'",
						"'".$fI['_ref_id']."'",
						"'".$mode."'",
						$this->quoteJSvalue($fI['file_name']),
						$this->quoteJSvalue($fI['_ref_file_path']),
						"'".$fI['file_type']."'",
						"'".$iconFile."'")
						);
					$onClick = 'return insertElement('.$onClick_params.');';
					$ATag_add = '<a href="#" onclick="'.htmlspecialchars($onClick).'"'.$titleAttrib.'>';
					$onClick = 'return insertElement('.$onClick_params.', \'\', 1);';
					$ATag_insert = '<a href="#" onclick="'.htmlspecialchars($onClick).'"'.$titleAttrib.'>';
				}

					// Create link to showing details about the file in a window:
				if ($fI['__exists']) {
					$Ahref = $BACK_PATH.'show_item.php?table='.rawurlencode($fI['file_name_absolute']).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
					$ATag_info = '<a href="'.htmlspecialchars($Ahref).'">';
					$info = $ATag_info.'<img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/zoom2.gif', 'width="12" height="12"').' title="'.$LANG->getLL('info',1).'" alt="" /> '.$LANG->getLL('info',1).'</a>';

				} else {
					$info = '&nbsp;';
				}

					// Thumbnail/size generation:
				$clickThumb = '';
				if (t3lib_div::inList($TYPO3_CONF_VARS['GFX']['imagefile_ext'], $fI['file_type']) AND $displayThumbs AND is_file($fI['file_name_absolute']))	{
					$clickThumb = t3lib_BEfunc::getThumbNail($BACK_PATH.'thumbs.php', $fI['file_path_absolute'].$fI['file_name'], '');
					$clickThumb = '<div style="width:56px; overflow:auto; padding: 5px; background-color:#fff; border:solid 1px #ccc;">'.$ATag_insert.$clickThumb.'</a>'.'</div>';
				} elseif ($displayThumbs) {
					$clickThumb = '<div style="width:68px"></div>';
				}

					// Drag & drop image
				if ($displayImage AND t3lib_div::inList($TYPO3_CONF_VARS['GFX']['imagefile_ext'], $fI['file_type']) AND is_file($fI['file_name_absolute']))	{
					if (t3lib_div::_GP('noLimit'))	{
						$maxW=10000;
						$maxH=10000;
					} else {
						$maxW=380;
						$maxH=500;
					}
					$IW = $fI['hpixels'];
					$IH = $fI['vpixels'];
					if ($IW>$maxW)	{
						$IH=ceil($IH/$IW*$maxW);
						$IW=$maxW;
					}
					if ($IH>$maxH)	{
						$IW=ceil($IW/$IH*$maxH);
						$IH=$maxH;
					}
					$clickThumb = '<img src="'.$this->siteUrl.substr($fI['file_name_absolute'],strlen(PATH_site)).'" width="'.$IW.'" height="'.$IH.'"' . ($this->defaultClass?(' class="'.$this->defaultClass.'"'):''). ' alt="'.$fI['alt_text'].'" title="'.$fI[$this->imgTitleDAMColumn].'" />';
					$clickThumb = '<div style="width:380px; overflow:auto; padding: 5px; background-color:#fff; border:solid 1px #ccc;">'.$clickThumb.'</div>';
				}

					// Show element:
				$lines[] = '
					<tr class="bgColor4">
						<td valign="top" nowrap="nowrap" style="min-width:20em">'.($displayImage?'':$ATag_insert).$iconAndFilename.'</a>'.'&nbsp;</td>
						<td valign="top" width="1%">'.($displayImage?'':$ATag_add).'<img'.t3lib_iconWorks::skinImg($BACK_PATH, 'gfx/plusbullet2.gif', 'width="18" height="16"').' title="'.$LANG->getLL('addToList',1).'" alt="" /></a></td>
						<td valign="top" nowrap="nowrap" width="1%">'.$info.'</td>
					</tr>';


				$infoText = '';
				if ($this->getModSettings('extendedInfo')) {
					$infoText = tx_dam_guiFunc::meta_compileInfoData ($fI, 'file_name, file_size:filesize, _dimensions, caption:truncate:50, instructions', 'table');
					$infoText = str_replace('<table>', '<table border="0" cellpadding="0" cellspacing="1">', $infoText);
					$infoText = str_replace('<strong>', '<strong style="font-weight:normal;">', $infoText);
					$infoText = str_replace('</td><td>', '</td><td class="bgColor-10">', $infoText);
				}


				if (($displayThumbs || $displayImage) AND $infoText) {
					$lines[] = '
						<tr class="bgColor">
							<td valign="top" colspan="3">
							<table border="0" cellpadding="0" cellspacing="0"><tr>
								<td valign="top">'.$clickThumb.'</td>
								<td valign="top" style="padding-left:1em">'.$infoText.'</td></tr>
							</table>
							<div style="height:0.5em;"></div>
							</td>
						</tr>';
				} elseif ($clickThumb OR $infoText) {
					$lines[] = '
						<tr class="bgColor">
							<td valign="top" colspan="3" style="padding-left:22px">
							'.$clickThumb.$infoText.'
							<div style="height:0.5em;"></div>
							</td>
						</tr>';
				}

				$lines[] = '
						<tr>
							<td colspan="3"><div style="height:0.5em;"></div></td>
						</tr>';
			}

				// Wrap all the rows in table tags:
			$out .= '



		<!--
			File listing
		-->
				<table cellpadding="0" cellspacing="0" id="typo3-fileList">
					'.implode('',$lines).'
				</table>';
		}

			// Return accumulated content for filelisting:
		return $out;
	}

	/**
	 * Makes a DAM db query and collects data to be used in EB display
	 *
	 * @param	string		$allowedFileTypes Comma list of allowed file types
	 * @param	string		$disallowedFileTypes Comma list of disallowed file types
	 * @param	string		$mode EB mode: "db", "file", ...
	 * @return	array		Array of file elements
	 */
	function getFileListArr($allowedFileTypes, $disallowedFileTypes, $mode) {
		global $TYPO3_CONF_VARS, $TYPO3_DB;

		$filearray = array();

			// Use the current selection to create a query and count selected records
		$this->damSC->selection->addSelectionToQuery();
		$this->damSC->selection->qg->query['FROM']['tx_dam'] = tx_dam_db::getMetaInfoFieldList(true, array('hpixels','vpixels',$this->imgTitleDAMColumn,'alt_text'));
		#$this->damSC->selection->qg->addSelectFields(...
		if ($allowedFileTypes) {
			$extList = '"'.implode ('","', explode(',',$allowedFileTypes)).'"';
			$this->damSC->selection->qg->addWhere('AND tx_dam.file_type IN ('.$extList.')', 'WHERE', 'tx_dam.file_type');
		}
		if ($disallowedFileTypes) {
			$extList = '"'.implode ('","', explode(',',$disallowedFileTypes)).'"';
			$this->damSC->selection->qg->addWhere('AND NOT tx_dam.file_type IN ('.$extList.')', 'WHERE', 'NOT tx_dam.file_type');
		}
		if ($this->act == 'plain') {
			$this->damSC->selection->qg->addWhere('AND tx_dam.hpixels <= '.intval($this->plainMaxWidth), 'WHERE', 'tx_dam.hpixels');
			$this->damSC->selection->qg->addWhere('AND tx_dam.vpixels <= '.intval($this->plainMaxHeight), 'WHERE', 'tx_dam.vpixels');
		}

		$this->damSC->selection->execSelectionQuery(TRUE);

			// any records found?
		if($this->damSC->selection->pointer->countTotal) {

				// limit query for browsing
			$this->damSC->selection->addLimitToQuery();
			$this->damSC->selection->execSelectionQuery();

			if($this->damSC->selection->res) {
				while($row = $TYPO3_DB->sql_fetch_assoc($this->damSC->selection->res)) {

					$row['file_title'] = $row['title'] ? $row['title'] : $row['file_name'];
					$row['file_path_absolute'] = tx_dam::path_makeAbsolute($row['file_path']);
					$row['file_name_absolute'] = $row['file_path_absolute'].$row['file_name'];
					$row['__exists'] = @is_file($row['file_name_absolute']);

					if ($mode=='db') {
						$row['_ref_table'] = 'tx_dam';
						$row['_ref_id'] = $row['uid'];
						$row['_ref_file_path'] = '';
					} else {
						$row['_ref_table'] = '';
						$row['_ref_id'] = t3lib_div::shortMD5($row['file_name_absolute']);
						$row['_ref_file_path'] = $row['file_name_absolute'];
					}

					$filearray[] = $row;
					if (count($filearray) >= $this->damSC->selection->pointer->itemsPerPage) {
						break;
					}
				}
			}
		}
		return $filearray;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main_rte()	{
		global $LANG, $TYPO3_CONF_VARS, $FILEMOUNTS, $BE_USER;

		$path = tx_dam::path_makeAbsolute($this->damSC->path);
		if (!$path OR !@is_dir($path))	{
				// The closest TEMP-path is found
			$path = $this->fileProcessor->findTempFolder().'/';
		}
			 // Remove upload tab if filemount is readonly
		if ($this->isReadOnlyFolder($path)) {
			$this->allowedItems = array_diff($this->allowedItems, array('upload'));
		}
		$this->damSC->path = tx_dam::path_makeRelative($path); // mabe not needed

			// Starting content:
		$this->content = $this->doc->startPage($LANG->getLL('Insert Image',1));

		$this->reinitParams();

			// Making menu in top:
		$menuDef = array();
		if (in_array('image',$this->allowedItems) && ($this->act=='image' || t3lib_div::_GP('cWidth'))) {
			$menuDef['page']['isActive'] = $this->act=='image';
			$menuDef['page']['label'] = $LANG->getLL('currentImage',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onClick="jumpToUrl(\''.htmlspecialchars($this->thisScript.'?act=image&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('magic',$this->allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='magic';
			$menuDef['file']['label'] = $LANG->getLL('magicImage',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onClick="jumpToUrl(\''.htmlspecialchars($this->thisScript.'?act=magic&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('plain',$this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='plain';
			$menuDef['url']['label'] = $LANG->getLL('plainImage',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onClick="jumpToUrl(\''.htmlspecialchars($this->thisScript.'?act=plain&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('dragdrop',$this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='dragdrop';
			$menuDef['mail']['label'] = $LANG->getLL('dragDropImage',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onClick="jumpToUrl(\''.htmlspecialchars($this->thisScript.'?act=dragdrop&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('upload', $this->allowedItems)) {
			$menuDef['upload']['isActive'] = ($this->act=='upload');
			$menuDef['upload']['label'] = $LANG->getLL('tx_dam_file_upload.title',1);
			$menuDef['upload']['url'] = '#';
			$menuDef['upload']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars($this->thisScript.'?act=upload&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		$this->content .= $this->doc->getTabMenuRaw($menuDef);

		$pArr = explode('|', $this->bparams);
		switch($this->act)	{
			case 'image':
				$JScode = '
				document.write(printCurrentImageOptions());
				insertImagePropertiesInForm();';
				$this->content.= '<br />'.$this->doc->wrapScriptTags($JScode);
				break;
			case 'upload':
				$this->content.= $this->dam_upload($this->allowedFileTypes, $this->disallowedFileTypes);
				$this->content.= $this->damSC->getOptions();
				$this->content.='<br /><br />';
				if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB'))	{
					$this->content.= $this->createFolder($path);
					$this->content.= '<br />';
				}
				break;
			case 'dragdrop':
			case 'plain':
				//$this->allowedFileTypes = t3lib_div::trimExplode(',', $pArr[3], true);
			case 'magic':
				$this->addDisplayOptions();
				$this->content.= $this->dam_select($this->allowedFileTypes, $this->disallowedFileTypes);
				$this->content.= $this->damSC->getOptions();

				if ($this->act=='magic')	{
					$this->content .= $this->getMsgBox($LANG->getLL('magicImage_msg'));
				}
				if ($this->act=='plain')	{
					$this->content .= $this->getMsgBox(sprintf($LANG->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight));
				}
				break;
			default:
				break;
		}
			// Ending page, returning content:
		$this->content .= $this->doc->endPage();
		$this->getJSCode();
		$this->content = $this->damSC->doc->insertStylesAndJS($this->content);
		return $this->content;
	}

	/**
	 * Import from t3lib_page in order to create backend version
	 * Creates language-overlay for records in general (where translation is found in records from the same table)
	 *
	 * @param	string		Table name
	 * @param	array		Record to overlay. Must containt uid, pid and $table]['ctrl']['languageField']
	 * @param	integer		Pointer to the sys_language uid for content on the site.
	 * @param	string		Overlay mode. If "hideNonTranslated" then records without translation will not be returned un-translated but unset (and return value is false)
	 * @return	mixed		Returns the input record, possibly overlaid with a translation. But if $OLmode is "hideNonTranslated" then it will return false if no translation is found.
	 */
	function getRecordOverlay($table,$row,$sys_language_content,$OLmode='')	{
		global $TCA, $TYPO3_DB;
		if ($row['uid']>0 && $row['pid']>0)	{
			if ($TCA[$table] && $TCA[$table]['ctrl']['languageField'] && $TCA[$table]['ctrl']['transOrigPointerField'])	{
				if (!$TCA[$table]['ctrl']['transOrigPointerTable'])	{
						// Will try to overlay a record only if the sys_language_content value is larger that zero.
					if ($sys_language_content>0)	{
							// Must be default language or [All], otherwise no overlaying:
						if ($row[$TCA[$table]['ctrl']['languageField']]<=0)	{
								// Select overlay record:
							$res = $TYPO3_DB->exec_SELECTquery(
								'*',
								$table,
								'pid='.intval($row['pid']).
									' AND '.$TCA[$table]['ctrl']['languageField'].'='.intval($sys_language_content).
									' AND '.$TCA[$table]['ctrl']['transOrigPointerField'].'='.intval($row['uid']).
									t3lib_BEfunc::BEenableFields($table).
									t3lib_BEfunc::deleteClause($table),
								'',
								'',
								'1'
								);
							$olrow = $TYPO3_DB->sql_fetch_assoc($res);
							//$this->versionOL($table,$olrow);

								// Merge record content by traversing all fields:
							if (is_array($olrow))	{
								foreach($row as $fN => $fV)	{
									if ($fN!='uid' && $fN!='pid' && isset($olrow[$fN]))	{
										if ($TCA[$table]['l10n_mode'][$fN]!='exclude' && ($TCA[$table]['l10n_mode'][$fN]!='mergeIfNotBlank' || strcmp(trim($olrow[$fN]),'')))	{
											$row[$fN] = $olrow[$fN];
										}
									}
								}
							} elseif ($OLmode==='hideNonTranslated' && $row[$TCA[$table]['ctrl']['languageField']]==0)	{	// Unset, if non-translated records should be hidden. ONLY done if the source record really is default language and not [All] in which case it is allowed.
								unset($row);
							}

							// Otherwise, check if sys_language_content is different from the value of the record - that means a japanese site might try to display french content.
						} elseif ($sys_language_content!=$row[$TCA[$table]['ctrl']['languageField']])	{
							unset($row);
						}
					} else {
							// When default language is displayed, we never want to return a record carrying another language!:
						if ($row[$TCA[$table]['ctrl']['languageField']]>0)	{
							unset($row);
						}
					}
				}
			}
		}

		return $row;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_dam_browse_media.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_dam_browse_media.php']);
}

?>