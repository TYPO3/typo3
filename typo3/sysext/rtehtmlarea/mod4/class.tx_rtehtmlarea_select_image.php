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
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
require_once(PATH_typo3.'class.browse_links.php');

/**
 * Local Folder Tree
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
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
		$title = htmlspecialchars($title);

		if ($this->ext_isLinkable($v))	{
			$aOnClick = 'return jumpToUrl(\'?editorNo='.$GLOBALS['SOBE']->browser->editorNo.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		TRUE is returned if the path is found in the web-part of the the server and is NOT a recycler or temp folder
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
			$out.='<tr'.$bgColor.'><td nowrap="nowrap">'.$v['HTML'].$this->wrapTitle(t3lib_div::fixed_lgd_cs($v['row']['title'],$titleLen),$v['row']).'</td></tr>';
		}

		$out='<table border="0" cellpadding="0" cellspacing="0">'.$out.'</table>';
		return $out;
	}
}


/**
 * Script Class
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_rte
 */
class tx_rtehtmlarea_select_image extends browse_links {
	var $extKey = 'rtehtmlarea';
	var $content;

	public $allowedItems;
	public $allowedFileTypes = array();
	protected $defaultClass;
	protected $plainMaxWidth;
	protected $plainMaxHeight;
	protected $magicMaxWidth;
	protected $magicMaxHeight;
	protected $imgPath;
	protected $RTEImageStorageDir;

	public $editorNo;
	public $sys_language_content;
	public $thisConfig;
	public $buttonConfig;

	protected $imgObj;  // Instance object of t3lib_stdGraphic

	/**
	 * Initialisation
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;

		$this->initVariables();
		$this->initConfiguration();
		$this->initHookObjects();

			// init fileProcessor
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

		$this->allowedItems = $this->getAllowedItems('magic,plain,image', $this->buttonConfig);
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems))	{
			$this->act = current($this->allowedItems);
		}

		$this->insertImage();

			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->bodyTagAdditions = $this->getBodyTagAdditions();
		$this->doc->backPath = $BACK_PATH;

			// Load the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/browse_links.js');

		$this->doc->getContextMenuCode();
	}

	/**
	 * Initialize class variables
	 *
	 * @return	void
	 */
	public function initVariables() {

			// Process bparams
		$this->bparams = t3lib_div::_GP('bparams');
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $pRteArr[0];
		$this->sys_language_content = $pRteArr[1];
		$this->RTEtsConfigParams = $pArr[2];
		if (!$this->editorNo) {
			$this->editorNo = t3lib_div::_GP('editorNo');
			$this->sys_language_content = t3lib_div::_GP('sys_language_content');
			$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		}
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$pArr[1] = implode(':', array($this->editorNo, $this->sys_language_content));
		$pArr[2] = $this->RTEtsConfigParams;
		if ($this->act == 'dragdrop' || $this->act == 'plain') {
			$this->allowedFileTypes = explode(',','jpg,jpeg,gif,png');
		}
		$pArr[3] = implode(',', $this->allowedFileTypes);
		$this->bparams = implode('|', $pArr);

			// Find "mode"
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode = 'rte';
		}
			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// Get "act"
		$this->act = t3lib_div::_GP('act');
		if (!$this->act) {
			$this->act = 'magic';
		}
	}

	/**
	 * Initialize hook objects implementing interface t3lib_browseLinksHook
	 *
	 * @return	void
	 */
	protected function initHookObjects() {
		global $TYPO3_CONF_VARS;
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']['browseLinksHook'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']['browseLinksHook'] as $classData) {
				$processObject = t3lib_div::getUserObj($classData);
				if(!($processObject instanceof t3lib_browseLinksHook)) {
					throw new UnexpectedValueException('$processObject must implement interface t3lib_browseLinksHook', 1195115652);
				}
				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}
	}

	/**
	 * Provide the additional parameters to be included in the template body tag
	 *
	 * @return	string		the body tag additions
	 */
	public function getBodyTagAdditions() {
		return 'onload="initEventListeners();"';
	}

	/**
	 * Get the path to the folder where RTE images are stored
	 *
	 * @return	string		the path to the folder where RTE images are stored
	 */
	protected function getRTEImageStorageDir()	{
		return ($this->imgPath ? $this->imgPath : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']);
	}

	/**
	 * Insert the image in the editing area
	 *
	 * @return	void
	 */
	protected function insertImage()	{
		if (t3lib_div::_GP('insertImage'))	{
			$filepath = t3lib_div::_GP('insertImage');
			$imgInfo = $this->getImageInfo($filepath);
			switch ($this->act) {
				case 'magic':
					$this->insertMagicImage($filepath, $imgInfo);
					exit;
					break;
				case 'plain':
					$this->insertPlainImage($imgInfo);
					exit;
					break;
				default:
						// Call hook
					foreach ($this->hookObjects as $hookObject) {
						if (method_exists($hookObject, "insertElement")) {
							$hookObject->insertElement($this->act);
						}
					}
					break;
			}
		}
	}

	/**
	 * Get the information on the image file identified its path
	 *
	 * @param	string		$filepath: the path to the image file
	 *
	 * @return	array		a 4-elements information array about the file
	 */
	public function getImageInfo($filepath) {
		$this->imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
		$this->imgObj->init();
		$this->imgObj->mayScaleUp = 0;
		$this->imgObj->tempPath = PATH_site.$this->imgObj->tempPath;
		return $this->imgObj->getImageDimensions($filepath);
	}

	/**
	 * Insert a magic image
	 *
	 * @param	string		$filepath: the path to the image file
	 * @param	array		$imgInfo: a 4-elements information array about the file
	 * @param	string		$altText: text for the alt attribute of the image
	 * @param	string		$titleText: text for the title attribute of the image
	 * @param	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return	void
	 */
	public function insertMagicImage($filepath, $imgInfo, $altText='', $titleText='', $additionalParams='') {
		if (is_array($imgInfo) && count($imgInfo) == 4) {
			if ($this->RTEImageStorageDir) {
				$fI = pathinfo($imgInfo[3]);
				$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fI['basename']);
				$destPath =PATH_site.$this->RTEImageStorageDir;
				if (@is_dir($destPath))	{
					$destName = $fileFunc->getUniqueName($basename,$destPath);
					@copy($imgInfo[3],$destName);
					t3lib_div::fixPermissions($destName);
					$cWidth = t3lib_utility_Math::forceIntegerInRange(t3lib_div::_GP('cWidth'), 0, $this->magicMaxWidth);
					$cHeight = t3lib_utility_Math::forceIntegerInRange(t3lib_div::_GP('cHeight'), 0, $this->magicMaxHeight);
					if (!$cWidth)	$cWidth = $this->magicMaxWidth;
					if (!$cHeight)	$cHeight = $this->magicMaxHeight;

					$imgI = $this->imgObj->imageMagickConvert($filepath,'WEB',$cWidth.'m',$cHeight.'m');	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
					if ($imgI[3])	{
						$fI=pathinfo($imgI[3]);
						$mainBase='RTEmagicC_'.substr(basename($destName),10).'.'.$fI['extension'];
						$destName = $fileFunc->getUniqueName($mainBase,$destPath);
						@copy($imgI[3],$destName);
						t3lib_div::fixPermissions($destName);
						$destName = dirname($destName).'/'.rawurlencode(basename($destName));
						$iurl = $this->siteURL.substr($destName,strlen(PATH_site));
						$this->imageInsertJS($iurl, $imgI[0], $imgI[1], $altText, $titleText, $additionalParams);
					} else {
						t3lib_div::sysLog('Attempt at creating a magic image failed due to error converting image: "' . $filepath . '".', $this->extKey . '/tx_rtehtmlarea_select_image', t3lib_div::SYSLOG_SEVERITY_ERROR);
					}
				} else {
					t3lib_div::sysLog('Attempt at creating a magic image failed due to incorrect destination path: "' . $destPath . '".', $this->extKey . '/tx_rtehtmlarea_select_image', t3lib_div::SYSLOG_SEVERITY_ERROR);
				}
			} else {
				t3lib_div::sysLog('Attempt at creating a magic image failed due to absent RTE_imageStorageDir', $this->extKey . '/tx_rtehtmlarea_select_image', t3lib_div::SYSLOG_SEVERITY_ERROR);
			}
		} else {
			t3lib_div::sysLog('Attempt at creating a magic image failed due to missing image file info.', $this->extKey . '/tx_rtehtmlarea_select_image', t3lib_div::SYSLOG_SEVERITY_ERROR);
		}
	}

	/**
	 * Insert a plain image
	 *
	 * @param	array		$imgInfo: a 4-elements information array about the file
	 * @param	string		$altText: text for the alt attribute of the image
	 * @param	string		$titleText: text for the title attribute of the image
	 * @param	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return	void
	 */
	public function insertPlainImage($imgInfo, $altText='', $titleText='', $additionalParams='') {
		if (is_array($imgInfo) && count($imgInfo)==4)	{
			$iurl = $this->siteURL.substr($imgInfo[3],strlen(PATH_site));
			$this->imageInsertJS($iurl, $imgInfo[0], $imgInfo[1], $altText, $titleText, $additionalParams);
		}
	}

	/**
	 * Echo the HTML page and JS that will insert the image
	 *
	 * @param	string		$url: the url of the image
	 * @param	integer		$width: the width of the image
	* @param	integer		$height: the height of the image
	 * @param	string		$altText: text for the alt attribute of the image
	 * @param	string		$titleText: text for the title attribute of the image
	 * @param	string		$additionalParams: text representing more html attributes to be added on the img tag
	 * @return	void
	 */
	protected function imageInsertJS($url, $width, $height, $altText='', $titleText='', $additionalParams='') {
		echo'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Untitled</title>
	<script type="text/javascript">
	/*<![CDATA[*/
		var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Image");
		function insertImage(file,width,height,alt,title,additionalParams)	{
			plugin.insertImage(\'<img src="\'+file+\'" width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'"\''  . ($this->defaultClass?('+\' class="'.$this->defaultClass.'"\''):'') .
				'+(alt?\' alt="\'+alt+\'"\':\'\')+(title?\' title="\'+title+\'"\':\'\')+(additionalParams?\' \'+additionalParams:\'\')+\' />\');
		}
	/*]]>*/
	</script>
</head>
<body>
<script type="text/javascript">
/*<![CDATA[*/
	insertImage('.t3lib_div::quoteJSvalue($url,1).','.$width.','.$height.','.t3lib_div::quoteJSvalue($altText,1).','.t3lib_div::quoteJSvalue($titleText,1).','.t3lib_div::quoteJSvalue($additionalParams, 1).');
/*]]>*/
</script>
</body>
</html>';
	}

	/**
	 * Generate JS code to be used on the image insert/modify dialogue
	 *
	 * @param	string		$act: the action to be performed
	 * @param	string		$editorNo: the number of the RTE instance on the page
	 * @param	string		$sys_language_content: the language of the content element
	 *
	 * @return	string		the generated JS code
	 */
	function getJSCode($act, $editorNo, $sys_language_content)	{
		global $LANG, $TYPO3_CONF_VARS;

		$removedProperties = array();
		if (is_array($this->buttonConfig['properties.'])) {
			if ($this->buttonConfig['properties.']['removeItems']) {
				$removedProperties = t3lib_div::trimExplode(',',$this->buttonConfig['properties.']['removeItems'],1);
			}
		}

		if ($this->thisConfig['classesImage']) {
			$classesImageArray = t3lib_div::trimExplode(',', $this->thisConfig['classesImage'], 1);
			$classesImageJSOptions = '<option value=""></option>';
			foreach ($classesImageArray as $class) {
				$classesImageJSOptions .= '<option value="' .$class . '">' . $class . '</option>';
			}
		}

		$lockPlainWidth = 'false';
		$lockPlainHeight = 'false';
		if (is_array($this->thisConfig['proc.']) && $this->thisConfig['proc.']['plainImageMode']) {
			$plainImageMode = $this->thisConfig['proc.']['plainImageMode'];
			$lockPlainWidth = ($plainImageMode == 'lockDimensions')?'true':'false';
			$lockPlainHeight = ($lockPlainWidth || $plainImageMode == 'lockRatio' || ($plainImageMode == 'lockRatioWhenSmaller'))?'true':'false';
		}

		$JScode='
			var plugin = window.parent.RTEarea["' . $editorNo . '"].editor.getPlugin("TYPO3Image");
			var HTMLArea = window.parent.HTMLArea;
			function initEventListeners() {
				if (Ext.isWebKit) {
					Ext.EventManager.addListener(window.document.body, "dragend", plugin.onDrop, plugin, { single: true });
				}
			}
			function jumpToUrl(URL,anchor)	{
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$act.'" : "";
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo='.$editorNo.'" : "";
				var add_sys_language_content = URL.indexOf("sys_language_content=")==-1 ? "&sys_language_content='.$sys_language_content.'" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams).'";

				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.style.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.style.height : "";

				var theLocation = URL+add_act+add_editorNo+add_sys_language_content+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function insertImage(file,width,height)	{
				plugin.insertImage(\'<img src="\'+file+\'"' . ($this->defaultClass?(' class="'.$this->defaultClass.'"'):'') . ' width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'" />\');
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.t3lib_div::getIndpEnv('TYPO3_SITE_URL').TYPO3_mainDir.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
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
				if (classesImage) var styleSelector=\'<select id="iClass" name="iClass" style="width:140px;">' . $classesImageJSOptions  . '</select>\';
				var floatSelector=\'<select id="iFloat" name="iFloat"><option value="">' . $LANG->getLL('notSet') . '</option><option value="none">' . $LANG->getLL('nonFloating') . '</option><option value="left">' . $LANG->getLL('left') . '</option><option value="right">' . $LANG->getLL('right') . '</option></select>\';
				if (plugin.getButton("Language")) {
					var languageSelector = \'<select id="iLang" name="iLang">\';
					plugin.getButton("Language").getStore().each(function (record) {
						languageSelector +=\'<option value="\' + record.get("value") + \'">\' + record.get("text") + \'</option>\';
					});
					languageSelector += \'</select>\';
				}
				var bgColor=\' class="bgColor4"\';
				var sz="";
				sz+=\'<table border="0" cellpadding="1" cellspacing="1"><form action="" name="imageData">\';
				'.(in_array('class', $removedProperties)?'':'
				if(classesImage) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iClass">'.$LANG->getLL('class').': </label></td><td>\'+styleSelector+\'</td></tr>\';
				}')
				.(in_array('width', $removedProperties)?'':'
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && '. $lockPlainWidth .')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iWidth">'.$LANG->getLL('width').': </label></td><td><input type="text" id="iWidth" name="iWidth" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				}')
				.(in_array('height', $removedProperties)?'':'
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && '. $lockPlainHeight .')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iHeight">'.$LANG->getLL('height').': </label></td><td><input type="text" id="iHeight" name="iHeight" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';
				}')
				.(in_array('border', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iBorder">'.$LANG->getLL('border').': </label></td><td><input type="checkbox" id="iBorder" name="iBorder" value="1" /></td></tr>\';')
				.(in_array('float', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iFloat">'.$LANG->getLL('float').': </label></td><td>\'+floatSelector+\'</td></tr>\';')
				.(in_array('paddingTop', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingTop">'.$LANG->getLL('padding_top').': </label></td><td><input type="text" id="iPaddingTop" name="iPaddingTop" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).'></td></tr>\';')
				.(in_array('paddingRight', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingRight">'.$LANG->getLL('padding_right').': </label></td><td><input type="text" id="iPaddingRight" name="iPaddingRight" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('paddingBottom', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingBottom">'.$LANG->getLL('padding_bottom').': </label></td><td><input type="text" id="iPaddingBottom" name="iPaddingBottom" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('paddingLeft', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingLeft">'.$LANG->getLL('padding_left').': </label></td><td><input type="text" id="iPaddingLeft" name="iPaddingLeft" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(4).' /></td></tr>\';')
				.(in_array('title', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iTitle">'.$LANG->getLL('title').': </label></td><td><input type="text" id="iTitle" name="iTitle"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';')
				.(in_array('alt', $removedProperties)?'':'
				sz+=\'<tr><td\'+bgColor+\'><label for="iAlt">'.$LANG->getLL('alt').': </label></td><td><input type="text" id="iAlt" name="iAlt"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /></td></tr>\';')
				.(in_array('lang', $removedProperties)?'':'
				if (plugin.getButton("Language")) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iLang">\' + plugin.editor.getPlugin("Language").localize(\'Language-Tooltip\') + \': </label></td><td>\' + languageSelector + \'</td></tr>\';
				}')
				.(in_array('clickenlarge', $removedProperties)?'':'
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
					if (document.imageData.iLang) {
						var iLang = document.imageData.iLang.options[document.imageData.iLang.selectedIndex].value;
						var languageObject = plugin.editor.getPlugin("Language");
						if (iLang || languageObject.getLanguageAttribute(selectedImageRef)) {
							languageObject.setLanguageAttributes(selectedImageRef, iLang);
						} else {
							languageObject.setLanguageAttributes(selectedImageRef, "none");
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
						for (var a=0;a < l; a++) {
							if (fObj.options[a].value == value) {
								fObj.selectedIndex = a;
							}
						}
					}
					if (document.imageData.iLang) {
						var fObj=document.imageData.iLang;
						var value=plugin.editor.getPlugin("Language").getLanguageAttribute(selectedImageRef);
						for (var i = 0, n = fObj.length; i < n; i++) {
							if (fObj.options[i].value == value) {
								fObj.selectedIndex = i;
								if (i) {
									fObj.options[0].text = plugin.editor.getPlugin("Language").localize("Remove language mark");
								}
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
		return $JScode;
	}

	/**
	 * Session data for this class can be set from outside with this method.
	 * Call after init()
	 *
	 * @param	array		Session data array
	 * @return	array		Session data and boolean which indicates that data needs to be stored in session because it's changed
	 */
	function processSessionData($data) {
		$store = FALSE;

		if ($this->act != 'image') {
			if (isset($this->act))	{
				$data['act'] = $this->act;
				$store = TRUE;
			} else {
				$this->act = $data['act'];
			}
		}

		if (isset($this->expandFolder))	{
			$data['expandFolder'] = $this->expandFolder;
			$store = TRUE;
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
		global $LANG;

			// Starting content:
		$this->content = $this->doc->startPage($LANG->getLL('Insert Image',1));

			// Making menu in top:
		$menuDef = array();
		if (in_array('image',$this->allowedItems) && ($this->act=='image' || t3lib_div::_GP('cWidth'))) {
			$menuDef['page']['isActive'] = $this->act=='image';
			$menuDef['page']['label'] = $LANG->getLL('currentImage',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onClick="jumpToUrl(\'?act=image&bparams='.$this->bparams.'\');return false;"';
		}
		if (in_array('magic',$this->allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='magic';
			$menuDef['file']['label'] = $LANG->getLL('magicImage',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onClick="jumpToUrl(\'?act=magic&bparams='.$this->bparams.'\');return false;"';
		}
		if (in_array('plain',$this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='plain';
			$menuDef['url']['label'] = $LANG->getLL('plainImage',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onClick="jumpToUrl(\'?act=plain&bparams='.$this->bparams.'\');return false;"';
		}
		if (in_array('dragdrop',$this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='dragdrop';
			$menuDef['mail']['label'] = $LANG->getLL('dragDropImage',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onClick="jumpToUrl(\'?act=dragdrop&bparams='.$this->bparams.'\');return false;"';
		}

			// Call hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}

		$this->content .= $this->doc->getTabMenuRaw($menuDef);

		switch($this->act)	{
			case 'image':
				$JScode = '
				document.write(printCurrentImageOptions());
				insertImagePropertiesInForm();';
				$this->content.= '<br />'.$this->doc->wrapScriptTags($JScode);
				break;
			case 'plain':
			case 'magic':
					// Adding upload form, if allowed
				$this->content .= $this->insertUploadForm($this->expandFolder);
					// Getting flag for showing/not showing thumbnails:
				$noThumbs = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInRTEimageSelect');
				if (!$noThumbs)	{
						// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
					$_MOD_MENU = array('displayThumbs' => '');
					$_MCONF['name']='file_list';
					$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
					$addParams = '&act='.$this->act.'&editorNo='.$this->editorNo.'&expandFolder='.rawurlencode($this->expandFolder);
					$thumbNailCheck = t3lib_BEfunc::getFuncCheck('','SET[displayThumbs]',$_MOD_SETTINGS['displayThumbs'],'select_image.php',$addParams,'id="checkDisplayThumbs"').' <label for="checkDisplayThumbs">'.$LANG->sL('LLL:EXT:lang/locallang_mod_file_list.php:displayThumbs',1).'</label>';
				} else {
					$thumbNailCheck='';
				}
					// Create folder tree:
				$foldertree = t3lib_div::makeInstance('tx_rtehtmlarea_image_folderTree');
				$tree = $foldertree->getBrowsableTree();
				list(,,$specUid) = explode('_',t3lib_div::_GP('PM'));
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid],$this->act=='plain',$noThumbs?$noThumbs:!$_MOD_SETTINGS['displayThumbs']);
				$this->content.= '<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="vertical-align: top;">'.$this->barheader($LANG->getLL('folderTree').':').$tree.'</td>
					<td>&nbsp;</td>
					<td style="vertical-align: top;">'.$files.'</td>
				</tr>
				</table>
				<br />'.$thumbNailCheck;
					// Add help message
				$helpMessage = $this->getHelpMessage($this->act);
				if ($helpMessage) {
					$this->content .= $this->getMsgBox($helpMessage);
				}
				break;
			case 'dragdrop':
				$foldertree = t3lib_div::makeInstance('TBE_FolderTree');
				$foldertree->thisScript=$this->thisScript;
				$foldertree->ext_noTempRecyclerDirs = TRUE;
				$tree = $foldertree->getBrowsableTree();
				list(,,$specUid) = explode('_',t3lib_div::_GP('PM'));
				$files = $this->TBE_dragNDrop($foldertree->specUIDmap[$specUid], implode(',', $this->allowedFileTypes));
				$this->content.= '<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="vertical-align: top;">'.$this->barheader($LANG->getLL('folderTree').':').$tree.'</td>
					<td>&nbsp;</td>
					<td style="vertical-align: top;">'.$files.'</td>
				</tr>
				</table>';
				break;
			default:
					// Call hook
				foreach ($this->hookObjects as $hookObject) {
					$this->content.= $hookObject->getTab($this->act);
				}
				break;
		}
		$this->content.= $this->doc->endPage();
		$this->doc->JScodeArray['rtehtmlarea'] = $this->getJSCode($this->act, $this->editorNo, $this->sys_language_content);
		$this->content = $this->doc->insertStylesAndJS($this->content);
		return $this->content;
	}

	/**
	 * Create upload/create folder forms, if a path is given
	 *
	 * @param	string		$folderPath: the selected path , if any
	 *
	 * @return	string		the html content for the upload form
	 */
	protected function insertUploadForm($folderPath) {
		$content = '';
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB') && !$this->readOnly && count($GLOBALS['FILEMOUNTS'])) {
			$path = $folderPath;
			if (!$path || !@is_dir($path))	{
					// The closest TEMP-path is found
				$path = $this->fileProcessor->findTempFolder().'/';
			}
			if ($path!='/' && @is_dir($path)) {
				$uploadForm=$this->uploadForm($path);
				$createFolder=$this->createFolder($path);
			} else {
				$createFolder='';
				$uploadForm='';
			}
			$content .= $uploadForm;
			if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
				$content.=$createFolder;
			}
		}
		return $content;
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
		global $LANG, $BACK_PATH;

		$expandFolder = $expandFolder ? $expandFolder :t3lib_div::_GP('expandFolder');
		$out='';

		if ($expandFolder && $this->checkFolder($expandFolder))	{
			$files = t3lib_div::getFilesInDir($expandFolder,($plainFlag?'jpg,jpeg,gif,png':$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),1,1);	// $extensionList="",$prependPath=0,$order="")
			if (is_array($files))	{
				$out.=$this->barheader(sprintf($LANG->getLL('images').' (%s):',count($files)));

				$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
				$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
				$out.='<span class="nobr">'.$picon.'</span><br />';

				$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				$lines=array();
				foreach ($files as $filepath) {
					$fI=pathinfo($filepath);

					$origFile = t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site)));
					$iurl = $this->siteURL.$origFile;
					$imgInfo = $imgObj->getImageDimensions($filepath);
						// File icon:
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
					$pDim = $imgInfo[0].'x'.$imgInfo[1].' '.$LANG->getLL('pixels',1);
					$size=' ('.t3lib_div::formatSize(filesize($filepath)).$LANG->getLL('bytes',1).', '.$pDim.')';
					$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$icon.'','width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';
					$ATag = '<a href="#" onclick="return jumpToUrl(\'?editorNo='.$this->editorNo.'&insertImage='.rawurlencode($filepath).'\');">';
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

					$filenameAndIcon=$ATag.$icon.htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).$ATag_e;


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
	 * For RTE: This displays all IMAGES (gif,png,jpg) (from extensionList) from folder. Thumbnails are shown for images.
	 * This listing is of images located in the web-accessible paths ONLY - the listing is for drag-n-drop use in the RTE
	 *
	 * @param	string		The folder path to expand
	 * @param	string		List of fileextensions to show
	 * @return	string		HTML output
	 */
	function TBE_dragNDrop($expandFolder=0,$extensionList='')	{
		global $BACK_PATH;

		$expandFolder = $expandFolder ? $expandFolder : t3lib_div::_GP('expandFolder');
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			if ($this->isWebFolder($expandFolder))	{

					// Read files from directory:
				$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')
				if (is_array($files))	{
					$out.=$this->barheader(sprintf($GLOBALS['LANG']->getLL('files').' (%s):',count($files)));

					$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
					$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
					$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
					$out.=$picon.'<br />';

						// Init row-array:
					$lines=array();

						// Add "drag-n-drop" message:
					$lines[]='
						<tr>
							<td colspan="2">'.$this->getMsgBox($GLOBALS['LANG']->getLL('findDragDrop')).'</td>
						</tr>';

						// Traverse files:
					foreach ($files as $filepath) {
						$fI = pathinfo($filepath);

							// URL of image:
						$iurl = $this->siteURL.t3lib_div::rawurlencodeFP(substr($filepath,strlen(PATH_site)));

							// Show only web-images
						if (t3lib_div::inList('gif,jpeg,jpg,png',strtolower($fI['extension'])))	{
							$imgInfo = @getimagesize($filepath);
							$pDim = $imgInfo[0].'x'.$imgInfo[1].' pixels';

							$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
							$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes'.($pDim?', '.$pDim:'').')';
							$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$ficon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';
							$filenameAndIcon=$icon.htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen));

							if (t3lib_div::_GP('noLimit'))	{
								$maxW=10000;
								$maxH=10000;
							} else {
								$maxW=380;
								$maxH=500;
							}
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

								// Make row:
							$lines[]='
								<tr class="bgColor4">
									<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
									<td nowrap="nowrap">'.
									($imgInfo[0]!=$IW ? '<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('noLimit'=>'1'))).'">'.
														'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/icon_warning2.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->getLL('clickToRedrawFullSize',1).'" alt="" />'.
														'</a>':'').
									$pDim.'&nbsp;</td>
								</tr>';
								// Remove hardcoded border="1"
								// Add default class for images
							$lines[]='
								<tr>
									<td colspan="2"><img src="'.$iurl.'" width="'.$IW.'" height="'.$IH.'" alt=""' . ($this->defaultClass?(' class="'.$this->defaultClass.'"'):''). ' style="cursor:move;" /></td>
								</tr>';
							$lines[]='
								<tr>
									<td colspan="2"><img src="clear.gif" width="1" height="3" alt="" /></td>
								</tr>';
						}
					}

						// Finally, wrap all rows in a table tag:
					$out.='


			<!--
				File listing / Drag-n-drop
			-->
						<table border="0" cellpadding="0" cellspacing="1" id="typo3-dragBox">
							'.implode('',$lines).'
						</table>';
				}
			} else {
					// Print this warning if the folder is NOT a web folder:
				$out.=$this->barheader($GLOBALS['LANG']->getLL('files'));
				$out.=$this->getMsgBox($GLOBALS['LANG']->getLL('noWebFolder'),'icon_warning2');
			}
		}
		return $out;
	}

	/**
	 * Initializes the configuration variables
	 *
	 * @return	void
	 */
	 public function initConfiguration() {
		$this->thisConfig = $this->getRTEConfig();
		$this->buttonConfig = $this->getButtonConfig();
		$this->imgPath = $this->getImgPath();
		$this->RTEImageStorageDir = $this->getRTEImageStorageDir();
		$this->defaultClass = $this->getDefaultClass();
		$this->setMaximumImageDimensions();
	 }

	/**
	 * Get the RTE configuration from Page TSConfig
	 *
	 * @return	array		RTE configuration array
	 */
	protected function getRTEConfig()	{
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		return t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
	}

	/**
	 * Get the path of the image to be inserted or modified
	 *
	 * @return	string		path to the image
	 */
	protected function getImgPath()	{
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		return $RTEtsConfigParts[6];
	}

	/**
	 * Get the configuration of the image button
	 *
	 * @return	array		the configuration array of the image button
	 */
	protected function getButtonConfig()	{
		return ((is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.'])) ? $this->thisConfig['buttons.']['image.'] : array());
	}

	/**
	 * Get the allowed items or tabs
	 *
	 * @param	string		$items: initial list of possible items
	 * @return	array		the allowed items
	 */
	public function getAllowedItems($items)	{
		$allowedItems = explode(',', $items);
		$clientInfo = t3lib_div::clientInfo();
		if ($clientInfo['BROWSER'] !== 'opera') {
			$allowedItems[] = 'dragdrop';
		}
			// Call hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$allowedItems = $hookObject->addAllowedItems($allowedItems);
		}
			// Remove tab "image" if there is no current image
		if ($this->act !== 'image') {
			$allowedItems = array_diff($allowedItems, array('image'));
		}
			// Remove options according to RTE configuration
		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$allowedItems = array_diff($allowedItems, t3lib_div::trimExplode(',', $this->buttonConfig['options.']['removeItems'], 1));
		} else {
			$allowedItems = array_diff($allowedItems, t3lib_div::trimExplode(',', $this->thisConfig['blindImageOptions'], 1));
		}
		return $allowedItems;
	}

	/**
	 * Get the default image class
	 *
	 * @return	string		the default class, if any
	 */
	protected function getDefaultClass() {
		$defaultClass = '';
		if (is_array($this->buttonConfig['properties.'])) {
			if (is_array($this->buttonConfig['properties.']['class.']) && trim($this->buttonConfig['properties.']['class.']['default'])) {
				$defaultClass = trim($this->buttonConfig['properties.']['class.']['default']);
			}
		}
		return $defaultClass;
	}

	/**
	 * Set variables for maximum image dimensions
	 *
	 * @return	void
	 */
	protected function setMaximumImageDimensions() {
		if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['plain.'])) {
			if ($this->buttonConfig['options.']['plain.']['maxWidth']) $this->plainMaxWidth = $this->buttonConfig['options.']['plain.']['maxWidth'];
			if ($this->buttonConfig['options.']['plain.']['maxHeight']) $this->plainMaxHeight = $this->buttonConfig['options.']['plain.']['maxHeight'];
		}
		if (!$this->plainMaxWidth) $this->plainMaxWidth = 640;
		if (!$this->plainMaxHeight) $this->plainMaxHeight = 680;
		if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['magic.'])) {
			if ($this->buttonConfig['options.']['magic.']['maxWidth']) $this->magicMaxWidth = $this->buttonConfig['options.']['magic.']['maxWidth'];
			if ($this->buttonConfig['options.']['magic.']['maxHeight']) $this->magicMaxHeight = $this->buttonConfig['options.']['magic.']['maxHeight'];
		}
			// These defaults allow images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width
		if (!$this->magicMaxWidth) $this->magicMaxWidth = 300;
		if (!$this->magicMaxHeight) $this->magicMaxHeight = 1000;
	}

	/**
	 * Get the help message to be displayed on a given tab
	 *
	 * @param	string	$act: the identifier of the tab
	 * @return	string	the text of the message
	 */
	public function getHelpMessage($act) {
		global $LANG;
		switch ($act)	{
			case 'plain':
				return sprintf($LANG->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight);
				break;
			case 'magic':
				return sprintf($LANG->getLL('magicImage_msg'));
				break;
			default:
				return '';
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']);
}

?>