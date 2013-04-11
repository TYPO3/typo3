<?php
namespace TYPO3\CMS\Rtehtmlarea;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasper@typo3.com)
 *  (c) 2004-2013 Stanislas Rolland <typo3(arobas)jbr.ca>
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
 * Script Class
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 */
class SelectImage extends \TYPO3\CMS\Recordlist\Browser\ElementBrowser {

	/**
	 * @todo Define visibility
	 */
	public $extKey = 'rtehtmlarea';

	/**
	 * @todo Define visibility
	 */
	public $content;

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

	protected $imgObj;

	/**
	 * Initialisation
	 *
	 * @return void
	 */
	public function init() {
		$this->initVariables();
		$this->initConfiguration();
		$this->initHookObjects();
		// init fileProcessor
		$this->fileProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\BasicFileUtility');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->allowedItems = $this->getAllowedItems('magic,plain,image');
		$this->insertImage();
		// Creating backend template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		// Apply the same styles as those of the base script
		$this->doc->bodyTagId = 'typo3-browse-links-php';
		$this->doc->bodyTagAdditions = $this->getBodyTagAdditions();
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		// Load the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/tree.js');
		$this->doc->loadJavascriptLib('js/browse_links.js');
		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		');
		$this->doc->getContextMenuCode();
	}

	/**
	 * Initialize class variables
	 *
	 * @return 	void
	 */
	public function initVariables() {
		// Get "act"
		$this->act = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('act');
		if (!$this->act) {
			$this->act = FALSE;
		}
		// Process bparams
		$this->bparams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('bparams');
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $pRteArr[0];
		$this->sys_language_content = $pRteArr[1];
		$this->RTEtsConfigParams = $pArr[2];
		if (!$this->editorNo) {
			$this->editorNo = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('editorNo');
			$this->sys_language_content = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('sys_language_content');
			$this->RTEtsConfigParams = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RTEtsConfigParams');
		}
		$this->expandPage = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandPage');
		$this->expandFolder = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('expandFolder');
		$pArr[1] = implode(':', array($this->editorNo, $this->sys_language_content));
		$pArr[2] = $this->RTEtsConfigParams;
		if ($this->act == 'dragdrop' || $this->act == 'plain') {
			$this->allowedFileTypes = explode(',', 'jpg,jpeg,gif,png');
		}
		$pArr[3] = implode(',', $this->allowedFileTypes);
		$this->bparams = implode('|', $pArr);
		// Find "mode"
		$this->mode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mode');
		if (!$this->mode) {
			$this->mode = 'rte';
		}
		// Site URL
		$this->siteURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		// Current site url
		// the script to link to
		$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
	}

	/**
	 * Initialize hook objects implementing the hook interface
	 *
	 * @return 	void
	 */
	protected function initHookObjects() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']['browseLinksHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php']['browseLinksHook'] as $classData) {
				$processObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				if (!$processObject instanceof \TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface) {
					throw new \UnexpectedValueException('$processObject must implement interface TYPO3\\CMS\\Core\\ElementBrowser\\ElementBrowserHookInterface', 1195115652);
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
	 * @return 	string		the body tag additions
	 */
	public function getBodyTagAdditions() {
		return 'onload="initEventListeners();"';
	}

	/**
	 * Get the path to the folder where RTE images are stored
	 *
	 * @return 	string		the path to the folder where RTE images are stored
	 */
	protected function getRTEImageStorageDir() {
		return $this->imgPath ? $this->imgPath : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'];
	}

	/**
	 * Insert the image in the editing area
	 *
	 * @return 	void
	 */
	protected function insertImage() {
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('insertImage')) {
			$table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table');
			$uid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('uid');
			$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($uid);
			// Get default values for alt and title attributes from file properties
			$altText = $fileObject->getProperty('alternative');
			$titleText = $fileObject->getProperty('name');
			switch ($this->act) {
			case 'magic':
				$this->insertMagicImage($fileObject, $altText, $titleText, 'data-htmlarea-file-uid="' . $uid . '" data-htmlarea-file-table="' . $table . '"');
				die;
				break;
			case 'plain':
				$this->insertPlainImage($fileObject, $altText, $titleText, 'data-htmlarea-file-uid="' . $uid . '" data-htmlarea-file-table="' . $table . '"');
				die;
				break;
			default:
				// Call hook
				foreach ($this->hookObjects as $hookObject) {
					if (method_exists($hookObject, 'insertElement')) {
						$hookObject->insertElement($this->act);
					}
				}
				break;
			}
		}
	}

	/**
	 * Insert a magic image
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $fileObject: the image file
	 * @param 	string		$altText: text for the alt attribute of the image
	 * @param 	string		$titleText: text for the title attribute of the image
	 * @param 	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return 	void
	 */
	public function insertMagicImage(\TYPO3\CMS\Core\Resource\FileInterface $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
		if ($this->RTEImageStorageDir) {
			// Create the magic image
			/** @var $magicImageService \TYPO3\CMS\Core\Resource\Service\MagicImageService */
			$magicImageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\MagicImageService');
			$imageConfiguration = array(
				'width' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cWidth'),
				'height' => \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cHeight'),
				'maxW' => $this->magicMaxWidth,
				'maxH' => $this->magicMaxHeight
			);
			$magicImage = $magicImageService->createMagicImage($fileObject, $imageConfiguration, $this->getRTEImageStorageDir());
			if ($magicImage instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
				$filePath = $magicImage->getForLocalProcessing(FALSE);
				$imageInfo = @getimagesize($filePath);
				$imageUrl = $this->siteURL . substr($filePath, strlen(PATH_site));
				$this->imageInsertJS($imageUrl, $imageInfo[0], $imageInfo[1], $altText, $titleText, $additionalParams);
			}
		} else {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Attempt at creating a magic image failed due to absent RTE_imageStorageDir', $this->extKey . '/tx_rtehtmlarea_select_image', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
	}

	/**
	 * Insert a plain image
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $fileObject: the image file
	 * @param 	string		$altText: text for the alt attribute of the image
	 * @param 	string		$titleText: text for the title attribute of the image
	 * @param 	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return 	void
	 */
	public function insertPlainImage(\TYPO3\CMS\Core\Resource\FileInterface $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
		$filePath = $fileObject->getForLocalProcessing(FALSE);
		$imageInfo = @getimagesize($filePath);
		$imageUrl = $this->siteURL . substr($filePath, strlen(PATH_site));
		$this->imageInsertJS($imageUrl, $imageInfo[0], $imageInfo[1], $altText, $titleText, $additionalParams);
	}

	/**
	 * Echo the HTML page and JS that will insert the image
	 *
	 * @param 	string		$url: the url of the image
	 * @param 	integer		$width: the width of the image
	 * @param 	integer		$height: the height of the image
	 * @param 	string		$altText: text for the alt attribute of the image
	 * @param 	string		$titleText: text for the title attribute of the image
	 * @param 	string		$additionalParams: text representing more html attributes to be added on the img tag
	 * @return 	void
	 */
	protected function imageInsertJS($url, $width, $height, $altText = '', $titleText = '', $additionalParams = '') {
		echo '
<!DOCTYPE html>
<html>
<head>
	<title>Untitled</title>
	<script type="text/javascript">
	/*<![CDATA[*/
		var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Image");
		function insertImage(file,width,height,alt,title,additionalParams) {
			plugin.insertImage(\'<img src="\'+file+\'" width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'"\'' . ($this->defaultClass ? '+\' class="' . $this->defaultClass . '"\'' : '') . '+(alt?\' alt="\'+alt+\'"\':\'\')+(title?\' title="\'+title+\'"\':\'\')+(additionalParams?\' \'+additionalParams:\'\')+\' />\');
		}
	/*]]>*/
	</script>
</head>
<body>
<script type="text/javascript">
/*<![CDATA[*/
	insertImage(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($url, 1) . ',' . $width . ',' . $height . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($altText, 1) . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($titleText, 1) . ',' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($additionalParams, 1) . ');
/*]]>*/
</script>
</body>
</html>';
	}

	/**
	 * Generate JS code to be used on the image insert/modify dialogue
	 *
	 * @param 	string		$act: the action to be performed
	 * @param 	string		$editorNo: the number of the RTE instance on the page
	 * @param 	string		$sys_language_content: the language of the content element
	 * @return 	string		the generated JS code
	 * @todo Define visibility
	 */
	public function getJSCode($act, $editorNo, $sys_language_content) {
		$removedProperties = array();
		if (is_array($this->buttonConfig['properties.'])) {
			if ($this->buttonConfig['properties.']['removeItems']) {
				$removedProperties = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['removeItems'], 1);
			}
		}
		if ($this->buttonConfig['properties.']['class.']['allowedClasses']) {
			$classesImageArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['class.']['allowedClasses'], 1);
			$classesImageJSOptions = '<option value=""></option>';
			foreach ($classesImageArray as $class) {
				$classesImageJSOptions .= '<option value="' . $class . '">' . $class . '</option>';
			}
		}
		$lockPlainWidth = 'false';
		$lockPlainHeight = 'false';
		if (is_array($this->thisConfig['proc.']) && $this->thisConfig['proc.']['plainImageMode']) {
			$plainImageMode = $this->thisConfig['proc.']['plainImageMode'];
			$lockPlainWidth = $plainImageMode == 'lockDimensions' ? 'true' : 'false';
			$lockPlainHeight = $lockPlainWidth || $plainImageMode == 'lockRatio' || $plainImageMode == 'lockRatioWhenSmaller' ? 'true' : 'false';
		}
		$JScode = '
			var plugin = window.parent.RTEarea["' . $editorNo . '"].editor.getPlugin("TYPO3Image");
			var HTMLArea = window.parent.HTMLArea;

			HTMLArea.TYPO3Image.insertElement = function (table, uid, type, filename, filePath, fileExt, fileIcon) {
				return jumpToUrl(\'?editorNo=\' + \'' . $editorNo . '\' + \'&insertImage=\' + filePath + \'&table=\' + table + \'&uid=\' + uid + \'&type=\' + type + \'bparams=\' + \'' . $this->bparams . '\');
			}
			function insertElement(table, uid, type, fileName, filePath, fileExt, fileIcon, action, close) {
				return jumpToUrl(\'?editorNo=\' + \'' . $editorNo . '\' + \'&insertImage=\' + filePath + \'&table=\' + table + \'&uid=\' + uid + \'&type=\' + type + \'bparams=\' + \'' . $this->bparams . '\');
			}
			function initEventListeners() {
				if (Ext.isWebKit) {
					Ext.EventManager.addListener(window.document.body, "dragend", plugin.onDrop, plugin, { single: true });
				}
			}
			function jumpToUrl(URL,anchor) {
				var add_act = URL.indexOf("act=")==-1 ? "&act=' . $act . '" : "";
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo=' . $editorNo . '" : "";
				var add_sys_language_content = URL.indexOf("sys_language_content=")==-1 ? "&sys_language_content=' . $sys_language_content . '" : "";
				var RTEtsConfigParams = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams) . '";

				var cur_width = selectedImageRef ? "&cWidth="+selectedImageRef.style.width : "";
				var cur_height = selectedImageRef ? "&cHeight="+selectedImageRef.style.height : "";

				var theLocation = URL+add_act+add_editorNo+add_sys_language_content+RTEtsConfigParams+cur_width+cur_height+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function insertImage(file,width,height) {
				plugin.insertImage(\'<img src="\'+file+\'"' . ($this->defaultClass ? ' class="' . $this->defaultClass . '"' : '') . ' width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'" />\');
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus) {
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
				var classesImage = ' . ($this->buttonConfig['properties.']['class.']['allowedClasses'] || $this->thisConfig['classesImage'] ? 'true' : 'false') . ';
				if (classesImage) var styleSelector=\'<select id="iClass" name="iClass" style="width:140px;">' . $classesImageJSOptions . '</select>\';
				var floatSelector=\'<select id="iFloat" name="iFloat"><option value="">' . $GLOBALS['LANG']->getLL('notSet') . '</option><option value="none">' . $GLOBALS['LANG']->getLL('nonFloating') . '</option><option value="left">' . $GLOBALS['LANG']->getLL('left') . '</option><option value="right">' . $GLOBALS['LANG']->getLL('right') . '</option></select>\';
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
				' . (in_array('class', $removedProperties) ? '' : '
				if(classesImage) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iClass">' . $GLOBALS['LANG']->getLL('class') . ': </label></td><td>\'+styleSelector+\'</td></tr>\';
				}') . (in_array('width', $removedProperties) ? '' : '
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && ' . $lockPlainWidth . ')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iWidth">' . $GLOBALS['LANG']->getLL('width') . ': </label></td><td><input type="text" id="iWidth" name="iWidth" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';
				}') . (in_array('height', $removedProperties) ? '' : '
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && ' . $lockPlainHeight . ')) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iHeight">' . $GLOBALS['LANG']->getLL('height') . ': </label></td><td><input type="text" id="iHeight" name="iHeight" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';
				}') . (in_array('border', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iBorder">' . $GLOBALS['LANG']->getLL('border') . ': </label></td><td><input type="checkbox" id="iBorder" name="iBorder" value="1" /></td></tr>\';') . (in_array('float', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iFloat">' . $GLOBALS['LANG']->getLL('float') . ': </label></td><td>\'+floatSelector+\'</td></tr>\';') . (in_array('paddingTop', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingTop">' . $GLOBALS['LANG']->getLL('padding_top') . ': </label></td><td><input type="text" id="iPaddingTop" name="iPaddingTop" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . '></td></tr>\';') . (in_array('paddingRight', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingRight">' . $GLOBALS['LANG']->getLL('padding_right') . ': </label></td><td><input type="text" id="iPaddingRight" name="iPaddingRight" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('paddingBottom', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingBottom">' . $GLOBALS['LANG']->getLL('padding_bottom') . ': </label></td><td><input type="text" id="iPaddingBottom" name="iPaddingBottom" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('paddingLeft', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iPaddingLeft">' . $GLOBALS['LANG']->getLL('padding_left') . ': </label></td><td><input type="text" id="iPaddingLeft" name="iPaddingLeft" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('title', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iTitle">' . $GLOBALS['LANG']->getLL('title') . ': </label></td><td><input type="text" id="iTitle" name="iTitle"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /></td></tr>\';') . (in_array('alt', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iAlt">' . $GLOBALS['LANG']->getLL('alt') . ': </label></td><td><input type="text" id="iAlt" name="iAlt"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /></td></tr>\';') . (in_array('lang', $removedProperties) ? '' : '
				if (plugin.getButton("Language")) {
					sz+=\'<tr><td\'+bgColor+\'><label for="iLang">\' + plugin.editor.getPlugin("Language").localize(\'Language-Tooltip\') + \': </label></td><td>\' + languageSelector + \'</td></tr>\';
				}') . (in_array('clickenlarge', $removedProperties) || in_array('data-htmlarea-clickenlarge', $removedProperties) ? '' : '
				sz+=\'<tr><td\'+bgColor+\'><label for="iClickEnlarge">' . $GLOBALS['LANG']->sL('LLL:EXT:cms/locallang_ttc.php:image_zoom', 1) . ' </label></td><td><input type="checkbox" name="iClickEnlarge" id="iClickEnlarge" value="0" /></td></tr>\';') . '
				sz+=\'<tr><td><input type="submit" value="' . $GLOBALS['LANG']->getLL('update') . '" onClick="return setImageProperties();"></td></tr>\';
				sz+=\'</form></table>\';
				return sz;
			}
			function setImageProperties() {
				var classesImage = ' . ($this->buttonConfig['properties.']['class.']['allowedClasses'] || $this->thisConfig['classesImage'] ? 'true' : 'false') . ';
				if (selectedImageRef) {
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
							selectedImageRef.setAttribute("data-htmlarea-clickenlarge","1");
						} else {
							selectedImageRef.removeAttribute("data-htmlarea-clickenlarge");
							selectedImageRef.removeAttribute("clickenlarge");
						}
					}
					plugin.close();
				}
				return false;
			}
			function insertImagePropertiesInForm() {
				var classesImage = ' . ($this->buttonConfig['properties.']['class.']['allowedClasses'] || $this->thisConfig['classesImage'] ? 'true' : 'false') . ';
				if (selectedImageRef) {
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
						for (var a=0;a<l;a++) {
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
						if (selectedImageRef.getAttribute("data-htmlarea-clickenlarge") == "1" || selectedImageRef.getAttribute("clickenlarge") == "1") {
							document.imageData.iClickEnlarge.checked = 1;
						} else {
							document.imageData.iClickEnlarge.checked = 0;
						}
					}
					return false;
				}
			}

			var selectedImageRef = getCurrentImageRef();';
		// Setting this to a reference to the image object.
		return $JScode;
	}

	/**
	 * Session data for this class can be set from outside with this method.
	 * Call after init()
	 *
	 * @param 	array		Session data array
	 * @return 	array		Session data and boolean which indicates that data needs to be stored in session because it's changed
	 * @todo Define visibility
	 */
	public function processSessionData($data) {
		$store = FALSE;
		if ($this->act != 'image') {
			if (isset($this->act)) {
				$data['act'] = $this->act;
				$store = TRUE;
			} else {
				$this->act = $data['act'];
			}
		}
		if (isset($this->expandFolder)) {
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
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function main_rte() {
		// Starting content:
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('Insert Image', 1));
		// Making menu in top:
		$menuDef = array();
		if (in_array('image', $this->allowedItems) && ($this->act === 'image' || \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cWidth'))) {
			$menuDef['image']['isActive'] = FALSE;
			$menuDef['image']['label'] = $GLOBALS['LANG']->getLL('currentImage', 1);
			$menuDef['image']['url'] = '#';
			$menuDef['image']['addParams'] = 'onClick="jumpToUrl(\'?act=image&bparams=' . $this->bparams . '\');return false;"';
		}
		if (in_array('magic', $this->allowedItems)) {
			$menuDef['magic']['isActive'] = FALSE;
			$menuDef['magic']['label'] = $GLOBALS['LANG']->getLL('magicImage', 1);
			$menuDef['magic']['url'] = '#';
			$menuDef['magic']['addParams'] = 'onClick="jumpToUrl(\'?act=magic&bparams=' . $this->bparams . '\');return false;"';
		}
		if (in_array('plain', $this->allowedItems)) {
			$menuDef['plain']['isActive'] = FALSE;
			$menuDef['plain']['label'] = $GLOBALS['LANG']->getLL('plainImage', 1);
			$menuDef['plain']['url'] = '#';
			$menuDef['plain']['addParams'] = 'onClick="jumpToUrl(\'?act=plain&bparams=' . $this->bparams . '\');return false;"';
		}
		if (in_array('dragdrop', $this->allowedItems)) {
			$menuDef['dragdrop']['isActive'] = FALSE;
			$menuDef['dragdrop']['label'] = $GLOBALS['LANG']->getLL('dragDropImage', 1);
			$menuDef['dragdrop']['url'] = '#';
			$menuDef['dragdrop']['addParams'] = 'onClick="jumpToUrl(\'?act=dragdrop&bparams=' . $this->bparams . '\');return false;"';
		}
		// Call hook for extra options
		foreach ($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}
		// Order the menu items as specified in Page TSconfig
		$menuDef = $this->orderMenuDefinition($menuDef);
		// Set active menu item
		reset($menuDef);
		if ($this->act === FALSE || !in_array($this->act, $this->allowedItems)) {
			$this->act = key($menuDef);
		}
		$menuDef[$this->act]['isActive'] = TRUE;
		$this->content .= $this->doc->getTabMenuRaw($menuDef);
		switch ($this->act) {
		case 'image':
			$JScode = '
				document.write(printCurrentImageOptions());
				insertImagePropertiesInForm();';
			$this->content .= '<br />' . $this->doc->wrapScriptTags($JScode);
			break;
		case 'plain':

		case 'magic':
			// Create folder tree:
			$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\ImageFolderTree');
			$foldertree->thisScript = $this->thisScript;
			$tree = $foldertree->getBrowsableTree();
			// Get currently selected folder
			if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act'] != $this->act) {
				$cmpPath = '';
			} else {
				$cmpPath = $this->curUrlInfo['value'];
				if (!isset($this->expandFolder)) {
					$this->expandFolder = $cmpPath;
				}
			}
			// Get the selected folder
			if ($this->expandFolder) {
				$selectedFolder = FALSE;
				$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
				if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
					// it's a folder
					$selectedFolder = $fileOrFolderObject;
				} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
					// it's a file
					// @todo: find the parent folder, right now done a bit ugly, because the file does not
					// support finding the parent folder of a file on purpose
					$folderIdentifier = dirname($fileOrFolderObject->getIdentifier());
					$selectedFolder = $fileOrFolderObject->getStorage()->getFolder($folderIdentifier);
				}
			}
			// If no folder is selected, get the user's default upload folder
			if (!$selectedFolder) {
				$selectedFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
			}
			// Build the file upload and folder creation form
			$uploadForm = '';
			$createFolder = '';
			if ($selectedFolder && !$this->isReadOnlyFolder($selectedFolder)) {
				$uploadForm = $this->uploadForm($selectedFolder);
				if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
					$createFolder = $this->createFolder($selectedFolder);
				}
			}
			// Insert the upload form on top, if so configured
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$this->content .= $uploadForm;
			}
			// Render the filelist if there is a folder selected
			if ($selectedFolder) {
				$files = $this->TBE_expandFolder($selectedFolder, $this->act === 'plain' ? 'jpg,jpeg,gif,png' : $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInRTEimageSelect'));
			}
			// Setup filelist indexed elements:
			$this->doc->JScode .= $this->doc->wrapScriptTags('BrowseLinks.addElements(' . json_encode($this->elements) . ');');
			// Wrap tree
			$this->content .= '

			<!--
				Wrapper table for folder tree / file/folder list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader(($GLOBALS['LANG']->getLL('folderTree') . ':')) . $tree . '</td>
							<td class="c-wCell" valign="top">' . $files . '</td>
						</tr>
					</table>
					';
			// Add help message
			$helpMessage = $this->getHelpMessage($this->act);
			if ($helpMessage) {
				$this->content .= $this->getMsgBox($helpMessage);
			}
			// Adding create folder + upload form if applicable
			if (!$GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
				$this->content .= $uploadForm;
			}
			$this->content .= $createFolder;
			$this->content .= '<br />';
			break;
		case 'dragdrop':
			$foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TBE_FolderTree');
			$foldertree->thisScript = $this->thisScript;
			$foldertree->ext_noTempRecyclerDirs = TRUE;
			$tree = $foldertree->getBrowsableTree();
			// Get currently selected folder
			if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act'] != $this->act) {
				$cmpPath = '';
			} else {
				$cmpPath = $this->curUrlInfo['value'];
				if (!isset($this->expandFolder)) {
					$this->expandFolder = $cmpPath;
				}
			}
			if ($this->expandFolder) {
				try {
					$selectedFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->expandFolder);
				} catch (Exception $e) {
					$selectedFolder = FALSE;
				}
			}
			// Render the filelist if there is a folder selected
			if ($selectedFolder) {
				$files = $this->TBE_dragNDrop($selectedFolder, implode(',', $this->allowedFileTypes));
			}
			// Wrap tree
			$this->content .= '<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td style="vertical-align: top;">' . $this->barheader(($GLOBALS['LANG']->getLL('folderTree') . ':')) . $tree . '</td>
					<td>&nbsp;</td>
					<td style="vertical-align: top;">' . $files . '</td>
				</tr>
				</table>';
			break;
		default:
			// Call hook
			foreach ($this->hookObjects as $hookObject) {
				$this->content .= $hookObject->getTab($this->act);
			}
			break;
		}
		$this->content .= $this->doc->endPage();
		$this->doc->JScodeArray['rtehtmlarea'] = $this->getJSCode($this->act, $this->editorNo, $this->sys_language_content);
		$this->content = $this->doc->insertStylesAndJS($this->content);
		return $this->content;
	}

	/**
	 * Initializes the configuration variables
	 *
	 * @return 	void
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
	 * @return 	array		RTE configuration array
	 */
	protected function getRTEConfig() {
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($RTEtsConfigParts[5]));
		return \TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup($RTEsetup['properties'], $RTEtsConfigParts[0], $RTEtsConfigParts[2], $RTEtsConfigParts[4]);
	}

	/**
	 * Get the path of the image to be inserted or modified
	 *
	 * @return 	string		path to the image
	 */
	protected function getImgPath() {
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		return $RTEtsConfigParts[6];
	}

	/**
	 * Get the configuration of the image button
	 *
	 * @return 	array		the configuration array of the image button
	 */
	protected function getButtonConfig() {
		return is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.']) ? $this->thisConfig['buttons.']['image.'] : array();
	}

	/**
	 * Get the allowed items or tabs
	 *
	 * @param 	string		$items: initial list of possible items
	 * @return 	array		the allowed items
	 */
	public function getAllowedItems($items) {
		$allowedItems = explode(',', $items);
		$clientInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::clientInfo();
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
			$allowedItems = array_diff($allowedItems, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['removeItems'], 1));
		}
		return $allowedItems;
	}

	/**
	 * Order the definition of menu items according to configured order
	 *
	 * @param array $menuDefinition: definition of menu items
	 * @return array ordered menu definition
	 */
	public function orderMenuDefinition($menuDefinition) {
		$orderedMenuDefinition = array();
		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['orderItems']) {
			$orderItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['orderItems'], TRUE);
			$orderItems = array_intersect($orderItems, $this->allowedItems);
			foreach ($orderItems as $item) {
				$orderedMenuDefinition[$item] = $menuDefinition[$item];
			}
		} else {
			$orderedMenuDefinition = $menuDefinition;
		}
		return $orderedMenuDefinition;
	}

	/**
	 * Get the default image class
	 *
	 * @return 	string		the default class, if any
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
	 * @return 	void
	 */
	protected function setMaximumImageDimensions() {
		if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['plain.'])) {
			if ($this->buttonConfig['options.']['plain.']['maxWidth']) {
				$this->plainMaxWidth = $this->buttonConfig['options.']['plain.']['maxWidth'];
			}
			if ($this->buttonConfig['options.']['plain.']['maxHeight']) {
				$this->plainMaxHeight = $this->buttonConfig['options.']['plain.']['maxHeight'];
			}
		}
		if (!$this->plainMaxWidth) {
			$this->plainMaxWidth = 640;
		}
		if (!$this->plainMaxHeight) {
			$this->plainMaxHeight = 680;
		}
		if (is_array($this->buttonConfig['options.']) && is_array($this->buttonConfig['options.']['magic.'])) {
			if ($this->buttonConfig['options.']['magic.']['maxWidth']) {
				$this->magicMaxWidth = $this->buttonConfig['options.']['magic.']['maxWidth'];
			}
			if ($this->buttonConfig['options.']['magic.']['maxHeight']) {
				$this->magicMaxHeight = $this->buttonConfig['options.']['magic.']['maxHeight'];
			}
		}
		// These defaults allow images to be based on their width - to a certain degree - by setting a high height. Then we're almost certain the image will be based on the width
		if (!$this->magicMaxWidth) {
			$this->magicMaxWidth = 300;
		}
		if (!$this->magicMaxHeight) {
			$this->magicMaxHeight = 1000;
		}
	}

	/**
	 * Get the help message to be displayed on a given tab
	 *
	 * @param 	string	$act: the identifier of the tab
	 * @return 	string	the text of the message
	 */
	public function getHelpMessage($act) {
		switch ($act) {
		case 'plain':
			return sprintf($GLOBALS['LANG']->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight);
			break;
		case 'magic':
			return sprintf($GLOBALS['LANG']->getLL('magicImage_msg'));
			break;
		default:
			return '';
		}
	}

	/**
	 * Render list of files.
	 *
	 * @param 	array		List of files. See \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir
	 * @param 	string		If set a header with a folder icon and folder name are shown
	 * @param 	boolean		Whether to show thumbnails or not. If set, no thumbnails are shown.
	 * @return 	string		HTML output
	 * @todo Define visibility
	 */
	public function fileList(array $files, \TYPO3\CMS\Core\Resource\Folder $folder = NULL, $noThumbs = 0) {
		$out = '';
		// Listing the files:
		if (is_array($files)) {
			$lines = array();
			// Create headline (showing number of files):
			$filesCount = count($files);
			$out .= $this->barheader(sprintf($GLOBALS['LANG']->getLL('files') . ' (%s):', $filesCount));
			$out .= '<div id="filelist">';
			$out .= $this->getBulkSelector($filesCount);
			$titleLen = intval($GLOBALS['BE_USER']->uc['titleLen']);
			// Create the header of current folder:
			if ($folder) {
				$folderIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile('folder');
				$lines[] = '<tr class="t3-row-header">
					<td colspan="4">' . $folderIcon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), $titleLen)) . '</td>
				</tr>';
			}
			if ($filesCount == 0) {
				$lines[] = '
					<tr class="file_list_normal">
						<td colspan="4">No files found.</td>
					</tr>';
			}
			// Init graphic object for reading file and image dimensions:
			/** @var $imgObj \TYPO3\CMS\Core\Imaging\GraphicalFunctions */
			$imgObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$imgObj->init();
			$imgObj->mayScaleUp = 0;
			$imgObj->tempPath = PATH_site . $imgObj->tempPath;
			// Traverse the file list:
			/** @var $fileObject \TYPO3\CMS\Core\Resource\File */
			foreach ($files as $fileObject) {
				$fileExtension = $fileObject->getExtension();
				// Thumbnail/size generation:
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), strtolower($fileExtension)) && !$noThumbs) {
					$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => 64, 'height' => 64))->getPublicUrl(TRUE);
					$imgInfo = $imgObj->getImageDimensions($fileObject->getForLocalProcessing(FALSE));
					$pDim = $imgInfo[0] . 'x' . $imgInfo[1] . ' pixels';
					$clickIcon = '<img src="' . $imageUrl . '" hspace="5" vspace="5" border="1"';
				} else {
					$clickIcon = '';
					$pDim = '';
				}
				// Create file icon:
				$size = ' (' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($fileObject->getSize()) . 'bytes' . ($pDim ? ', ' . $pDim : '') . ')';
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension, array('title' => $fileObject->getName() . $size));
				// Create links for adding the file:
				$filesIndex = count($this->elements);
				$this->elements['file_' . $filesIndex] = array(
					'type' => 'file',
					'table' => 'sys_file',
					'uid' => $fileObject->getUid(),
					'fileName' => $fileObject->getName(),
					'filePath' => $fileObject->getUid(),
					'fileExt' => $fileExtension,
					'fileIcon' => $icon
				);
				$element = $this->elements['file_' . $filesIndex];
				if ($this->act === 'plain' && ($imgInfo[0] > $this->plainMaxWidth || $imgInfo[1] > $this->plainMaxHeight) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList('jpg,jpeg,gif,png', $fileExtension)) {
					$ATag = '';
					$ATag_alt = '';
					$ATag_e = '';
				} else {
					$this->elements['file_' . $filesIndex] = array(
						'type' => 'file',
						'table' => 'sys_file',
						'uid' => $fileObject->getUid(),
						'fileName' => $fileObject->getName(),
						'filePath' => $fileObject->getUid(),
						'fileExt' => $fileExtension,
						'fileIcon' => $icon
					);
					$ATag = '<a href="#" onclick="return BrowseLinks.File.insertElement(\'file_' . $filesIndex . '\');">';
					$ATag_alt = substr($ATag, 0, -4) . ',1);">';
					$ATag_e = '</a>';
				}
				// Create link to showing details about the file in a window:
				$Ahref = $GLOBALS['BACK_PATH'] . 'show_item.php?type=file&table=' . rawurlencode($fileObject->getCombinedIdentifier()) . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
				$ATag2 = '<a href="' . htmlspecialchars($Ahref) . '">';
				$ATag2_e = '</a>';
				// Combine the stuff:
				$filenameAndIcon = $ATag_alt . $icon . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($fileObject->getName(), $titleLen)) . $ATag_e;
				// Show element:
				if ($pDim) {
					// Image...
					$lines[] = '
						<tr class="file_list_normal">
							<td nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td nowrap="nowrap">&nbsp;' . $pDim . '</td>
						</tr>';
					$lines[] = '
						<tr>
							<td class="filelistThumbnail" colspan="4">' . $ATag_alt . $clickIcon . $ATag_e . '</td>
						</tr>';
				} else {
					$lines[] = '
						<tr class="file_list_normal">
							<td nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td>&nbsp;</td>
						</tr>';
				}
			}
			// Wrap all the rows in table tags:
			$out .= '



		<!--
			File listing
		-->
				<table cellpadding="0" cellspacing="0" id="typo3-filelist">
					' . implode('', $lines) . '
				</table>';
		}
		// Return accumulated content for filelisting:
		$out .= '</div>';
		return $out;
	}

}


?>