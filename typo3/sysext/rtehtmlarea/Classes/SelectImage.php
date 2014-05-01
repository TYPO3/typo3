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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource;

/**
 * Script Class
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 */
class SelectImage extends \TYPO3\CMS\Recordlist\Browser\ElementBrowser {

	/**
	 * These file extensions are allowed in the "plain" image selection mode.
	 *
	 * @const
	 */
	const PLAIN_MODE_IMAGE_FILE_EXTENSIONS = 'jpg,jpeg,gif,png';

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

	/**
	 * Relevant for RTE mode "plain": the maximum width an image must have to be selectable.
	 *
	 * @var int
	 */
	protected $plainMaxWidth;

	/**
	 * Relevant for RTE mode "plain": the maximum height an image must have to be selectable.
	 *
	 * @var int
	 */
	protected $plainMaxHeight;

	protected $imgPath;

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
		$this->initHookObjects('ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php');

		$this->allowedItems = $this->getAllowedItems('magic,plain,image');
		$this->insertImage();

		$this->initDocumentTemplate();
	}

	/**
	 * Initialize class variables
	 *
	 * @return void
	 */
	public function initVariables() {
		parent::initVariables();
		// Get "act"
		$this->act = GeneralUtility::_GP('act');
		if (!$this->act) {
			$this->act = FALSE;
		}
		// Process bparams
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $pRteArr[0];
		$this->sys_language_content = $pRteArr[1];
		$this->RTEtsConfigParams = $pArr[2];
		if (!$this->editorNo) {
			$this->editorNo = GeneralUtility::_GP('editorNo');
			$this->sys_language_content = GeneralUtility::_GP('sys_language_content');
			$this->RTEtsConfigParams = GeneralUtility::_GP('RTEtsConfigParams');
		}
		$pArr[1] = implode(':', array($this->editorNo, $this->sys_language_content));
		$pArr[2] = $this->RTEtsConfigParams;
		if ($this->act == 'dragdrop' || $this->act == 'plain') {
			$this->allowedFileTypes = explode(',', self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS);
		}
		$pArr[3] = implode(',', $this->allowedFileTypes);
		$this->bparams = implode('|', $pArr);
	}

	/**
	 * Initialize document template object
	 *
	 * @return void
	 */
	protected function initDocumentTemplate() {
		parent::initDocumentTemplate();

		$this->doc->bodyTagId = 'typo3-browse-links-php';
		$this->doc->bodyTagAdditions = $this->getBodyTagAdditions();

		$this->doc->JScode .= $this->doc->wrapScriptTags('
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		');
		$this->doc->getPageRenderer()->addCssFile($this->doc->backPath . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3skin') . 'rtehtmlarea/htmlarea.css');
		$this->doc->getContextMenuCode();
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
	 * @return  string the path to the folder where RTE images are stored
	 * @deprecated since 6.2, will be removed in two versions
	 */
	protected function getRTEImageStorageDir() {
		GeneralUtility::logDeprecatedFunction();
		return $this->imgPath ?: $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'];
	}

	/**
	 * Insert the image in the editing area
	 *
	 * @return 	void
	 */
	protected function insertImage() {
		$table = htmlspecialchars(GeneralUtility::_GP('table'));
		$uid = (int) GeneralUtility::_GP('uid');
		if (GeneralUtility::_GP('insertImage') && $uid) {
			/** @var $fileObject Resource\File */
			$fileObject = Resource\ResourceFactory::getInstance()->getFileObject($uid);
			// Get default values for alt and title attributes from file properties
			$altText = $fileObject->getProperty('alternative');
			$titleText = $fileObject->getProperty('title');
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
			}
		}
	}

	/**
	 * Insert a magic image
	 *
	 * @param Resource\File $fileObject: the image file
	 * @param string $altText: text for the alt attribute of the image
	 * @param string $titleText: text for the title attribute of the image
	 * @param string $additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return void
	 */
	public function insertMagicImage(Resource\File $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
		// Create the magic image service
		/** @var $magicImageService Resource\Service\MagicImageService */
		$magicImageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\MagicImageService');
		$magicImageService->setMagicImageMaximumDimensions($this->thisConfig);
		// Create the magic image
		$imageConfiguration = array(
			'width' => GeneralUtility::_GP('cWidth'),
			'height' => GeneralUtility::_GP('cHeight')
		);
		$magicImage = $magicImageService->createMagicImage($fileObject, $imageConfiguration);
		$imageUrl = $magicImage->getPublicUrl();
		// If file is local, make the url absolute
		if (substr($imageUrl, 0, 4) !== 'http') {
			$imageUrl = $this->siteURL . $imageUrl;
		}
		// Insert the magic image
		$this->imageInsertJS($imageUrl, $magicImage->getProperty('width'), $magicImage->getProperty('height'), $altText, $titleText, $additionalParams);
	}

	/**
	 * Insert a plain image
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject: the image file
	 * @param 	string		$altText: text for the alt attribute of the image
	 * @param 	string		$titleText: text for the title attribute of the image
	 * @param 	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return 	void
	 */
	public function insertPlainImage(Resource\File $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
		$width = $fileObject->getProperty('width');
		$height = $fileObject->getProperty('height');
		if (!$width || !$height) {
			$filePath = $fileObject->getForLocalProcessing(FALSE);
			$imageInfo = @getimagesize($filePath);
			$width = $imageInfo[0];
			$height = $imageInfo[1];
		}
		$imageUrl = $fileObject->getPublicUrl();
		// If file is local, make the url absolute
		if (substr($imageUrl, 0, 4) !== 'http') {
			$imageUrl = $this->siteURL . $imageUrl;
		}
		$this->imageInsertJS($imageUrl, $width, $height, $altText, $titleText, $additionalParams);
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
	insertImage(' . GeneralUtility::quoteJSvalue($url, 1) . ',' . $width . ',' . $height . ',' . GeneralUtility::quoteJSvalue($altText, 1) . ',' . GeneralUtility::quoteJSvalue($titleText, 1) . ',' . GeneralUtility::quoteJSvalue($additionalParams, 1) . ');
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
				$removedProperties = GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['removeItems'], TRUE);
			}
		}
		if ($this->buttonConfig['properties.']['class.']['allowedClasses']) {
			$classesImageArray = GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['class.']['allowedClasses'], TRUE);
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
				return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'editorNo=') . ' + \'' . $editorNo . '\' + \'&insertImage=\' + filePath + \'&table=\' + table + \'&uid=\' + uid + \'&type=\' + type + \'bparams=\' + \'' . $this->bparams . '\');
			}
			function insertElement(table, uid, type, fileName, filePath, fileExt, fileIcon, action, close) {
				return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'editorNo=') . ' + \'' . $editorNo . '\' + \'&insertImage=\' + filePath + \'&table=\' + table + \'&uid=\' + uid + \'&type=\' + type + \'bparams=\' + \'' . $this->bparams . '\');
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

				var theLocation = URL+add_act+add_editorNo+add_sys_language_content+RTEtsConfigParams+cur_width+cur_height+(typeof(anchor)=="string"?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function insertImage(file,width,height) {
				plugin.insertImage(\'<img src="\'+file+\'"' . ($this->defaultClass ? ' class="' . $this->defaultClass . '"' : '') . ' width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'" />\');
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
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
				var sz="";
				sz+=\'<form action="" name="imageData"><table class="htmlarea-window-table">\';
				' . (in_array('class', $removedProperties) ? '' : '
				if(classesImage) {
					sz+=\'<tr><td><label for="iClass">' . $GLOBALS['LANG']->getLL('class') . ': </label></td><td>\'+styleSelector+\'</td></tr>\';
				}') . (in_array('width', $removedProperties) ? '' : '
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && ' . $lockPlainWidth . ')) {
					sz+=\'<tr><td><label for="iWidth">' . $GLOBALS['LANG']->getLL('width') . ': </label></td><td><input type="text" id="iWidth" name="iWidth" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';
				}') . (in_array('height', $removedProperties) ? '' : '
				if (!(selectedImageRef && selectedImageRef.src.indexOf("RTEmagic") == -1 && ' . $lockPlainHeight . ')) {
					sz+=\'<tr><td><label for="iHeight">' . $GLOBALS['LANG']->getLL('height') . ': </label></td><td><input type="text" id="iHeight" name="iHeight" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';
				}') . (in_array('border', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iBorder">' . $GLOBALS['LANG']->getLL('border') . ': </label></td><td><input type="checkbox" id="iBorder" name="iBorder" value="1" /></td></tr>\';') . (in_array('float', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iFloat">' . $GLOBALS['LANG']->getLL('float') . ': </label></td><td>\'+floatSelector+\'</td></tr>\';') . (in_array('paddingTop', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iPaddingTop">' . $GLOBALS['LANG']->getLL('padding_top') . ': </label></td><td><input type="text" id="iPaddingTop" name="iPaddingTop" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . '></td></tr>\';') . (in_array('paddingRight', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iPaddingRight">' . $GLOBALS['LANG']->getLL('padding_right') . ': </label></td><td><input type="text" id="iPaddingRight" name="iPaddingRight" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('paddingBottom', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iPaddingBottom">' . $GLOBALS['LANG']->getLL('padding_bottom') . ': </label></td><td><input type="text" id="iPaddingBottom" name="iPaddingBottom" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('paddingLeft', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iPaddingLeft">' . $GLOBALS['LANG']->getLL('padding_left') . ': </label></td><td><input type="text" id="iPaddingLeft" name="iPaddingLeft" value=""' . $GLOBALS['TBE_TEMPLATE']->formWidth(4) . ' /></td></tr>\';') . (in_array('title', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iTitle">' . $GLOBALS['LANG']->getLL('title') . ': </label></td><td><input type="text" id="iTitle" name="iTitle"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /></td></tr>\';') . (in_array('alt', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iAlt">' . $GLOBALS['LANG']->getLL('alt') . ': </label></td><td><input type="text" id="iAlt" name="iAlt"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /></td></tr>\';') . (in_array('lang', $removedProperties) ? '' : '
				if (plugin.getButton("Language")) {
					sz+=\'<tr><td><label for="iLang">\' + plugin.editor.getPlugin("Language").localize(\'Language-Tooltip\') + \': </label></td><td>\' + languageSelector + \'</td></tr>\';
				}') . (in_array('clickenlarge', $removedProperties) || in_array('data-htmlarea-clickenlarge', $removedProperties) ? '' : '
				sz+=\'<tr><td><label for="iClickEnlarge">' . $GLOBALS['LANG']->sL('LLL:EXT:cms/locallang_ttc.xlf:image_zoom', TRUE) . ' </label></td><td><input type="checkbox" name="iClickEnlarge" id="iClickEnlarge" value="0" /></td></tr>\';') . '
				sz+=\'<tr><td></td><td><input type="submit" value="' . $GLOBALS['LANG']->getLL('update') . '" onClick="return setImageProperties();"></td></tr>\';
				sz+=\'</table></form>\';
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
	 * @param array $data Session data array
	 * @return array Session data and boolean which indicates that data needs to be stored in session because it's changed
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
	 * @return string
	 * @todo Define visibility
	 */
	public function main_rte() {
		// Starting content:
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('Insert Image', TRUE));
		// Making menu in top:
		$menuDef = array();
		if (in_array('image', $this->allowedItems) && ($this->act === 'image' || GeneralUtility::_GP('cWidth'))) {
			$menuDef['image']['isActive'] = FALSE;
			$menuDef['image']['label'] = $GLOBALS['LANG']->getLL('currentImage', TRUE);
			$menuDef['image']['url'] = '#';
			$menuDef['image']['addParams'] = 'onClick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=image&bparams=' . $this->bparams) . ');return false;"';
		}
		if (in_array('magic', $this->allowedItems)) {
			$menuDef['magic']['isActive'] = FALSE;
			$menuDef['magic']['label'] = $GLOBALS['LANG']->getLL('magicImage', TRUE);
			$menuDef['magic']['url'] = '#';
			$menuDef['magic']['addParams'] = 'onClick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=magic&bparams=' . $this->bparams) . ');return false;"';
		}
		if (in_array('plain', $this->allowedItems)) {
			$menuDef['plain']['isActive'] = FALSE;
			$menuDef['plain']['label'] = $GLOBALS['LANG']->getLL('plainImage', TRUE);
			$menuDef['plain']['url'] = '#';
			$menuDef['plain']['addParams'] = 'onClick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=plain&bparams=' . $this->bparams) . ');return false;"';
		}
		if (in_array('dragdrop', $this->allowedItems)) {
			$menuDef['dragdrop']['isActive'] = FALSE;
			$menuDef['dragdrop']['label'] = $GLOBALS['LANG']->getLL('dragDropImage', TRUE);
			$menuDef['dragdrop']['url'] = '#';
			$menuDef['dragdrop']['addParams'] = 'onClick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=dragdrop&bparams=' . $this->bparams) . ');return false;"';
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
				$foldertree = GeneralUtility::makeInstance('TYPO3\\CMS\\Rtehtmlarea\\FolderTree');
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
				$selectedFolder = FALSE;
				if ($this->expandFolder) {
					$fileOrFolderObject = NULL;
					try {
						$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
					} catch (\Exception $e) {
						// No path is selected
					}
					if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
						// it's a folder
						$selectedFolder = $fileOrFolderObject;
					} elseif ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\FileInterface) {
						// it's a file
						$selectedFolder = $fileOrFolderObject->getParentFolder();
					}
				}
				// If no folder is selected, get the user's default upload folder
				if (!$selectedFolder) {
					try {
						$selectedFolder = $GLOBALS['BE_USER']->getDefaultUploadFolder();
					} catch (\Exception $e) {
						// The configured default user folder does not exist
					}
				}
				// Build the file upload and folder creation form
				$uploadForm = '';
				$createFolder = '';
				if ($selectedFolder) {
					$uploadForm = $this->uploadForm($selectedFolder);
					$createFolder = $this->createFolder($selectedFolder);
				}
				// Insert the upload form on top, if so configured
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
					$this->content .= $uploadForm;
				}
				// Render the filelist if there is a folder selected
				$files = '';
				if ($selectedFolder) {
					$files = $this->TBE_expandFolder($selectedFolder, $this->act === 'plain' ? self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS : $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInRTEimageSelect'));
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
				$foldertree = GeneralUtility::makeInstance('TBE_FolderTree');
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
				$selectedFolder = FALSE;
				if ($this->expandFolder) {
					try {
						$selectedFolder = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->expandFolder);
					} catch (\Exception $e) {
					}
				}
				// Render the filelist if there is a folder selected
				$files = '';
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
		}
		$this->content .= $this->doc->endPage();

		// unset the default jumpToUrl() function
		unset($this->doc->JScodeArray['jumpToUrl']);

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
		$this->defaultClass = $this->getDefaultClass();
		$this->setMaximumPlainImageDimensions();
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
		$clientInfo = GeneralUtility::clientInfo();
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
			$allowedItems = array_diff($allowedItems, GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['removeItems'], TRUE));
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
			$orderItems = GeneralUtility::trimExplode(',', $this->buttonConfig['options.']['orderItems'], TRUE);
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
	 * Set variables for maximum plain image dimensions
	 *
	 * @return 	void
	 */
	protected function setMaximumPlainImageDimensions() {
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
	 * Checks if the given file is selectable in the file list.
	 *
	 * In "plain" RTE mode only image files with a maximum width and height are selectable.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param array $imgInfo Image dimensions from \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageDimensions()
	 * @return bool TRUE if file is selectable.
	 */
	protected function fileIsSelectableInFileList(\TYPO3\CMS\Core\Resource\FileInterface $file, array $imgInfo) {
		return (
			$this->act !== 'plain'
			|| (
				GeneralUtility::inList(self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS, strtolower($file->getExtension()))
				&& $imgInfo[0] <= $this->plainMaxWidth
				&& $imgInfo[1] <= $this->plainMaxHeight
			)
		);
	}
}
