<?php
namespace TYPO3\CMS\Rtehtmlarea;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Service\MagicImageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Browser\ElementBrowser;

/**
 * Script Class
 */
class SelectImage extends ElementBrowser {

	/**
	 * These file extensions are allowed in the "plain" image selection mode.
	 *
	 * @const
	 */
	const PLAIN_MODE_IMAGE_FILE_EXTENSIONS = 'jpg,jpeg,gif,png';

	/**
	 * @var string
	 */
	public $extKey = 'rtehtmlarea';

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var array
	 */
	public $allowedItems;

	/**
	 * @var array
	 */
	public $allowedFileTypes = array();

	/**
	 * @var string
	 */
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

	/**
	 * @var string
	 */
	protected $imgPath;

	/**
	 * @var int
	 */
	public $editorNo;

	/**
	 * @var int
	 */
	public $sys_language_content;

	/**
	 * @var array
	 */
	public $thisConfig;

	/**
	 * @var array
	 */
	public $buttonConfig;

	/**
	 * @var bool
	 */
	public $addModifyTab;

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
		// Insert the image and exit
		$this->insertImage();
		// or render dialogue
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
		$this->addModifyTab = (bool)GeneralUtility::_GP('addModifyTab');
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

		$this->doc->bodyTagAdditions = 'onload="SelectImage.initEventListeners();"';
		$this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/LegacyTree', 'function(Tree) {
			Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";
		}');
		$this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Rtehtmlarea/Modules/SelectImage', 'function(SelectImage) {
			SelectImage.editorNo = ' . GeneralUtility::quoteJSvalue($this->editorNo) . ';
			SelectImage.act = ' . GeneralUtility::quoteJSvalue($this->act ?: reset($this->allowedItems)) . ';
			SelectImage.sys_language_content = ' . GeneralUtility::quoteJSvalue($this->sys_language_content) . ';
			SelectImage.RTEtsConfigParams = ' . GeneralUtility::quoteJSvalue(rawurlencode($this->RTEtsConfigParams)) . ';
			SelectImage.bparams = ' . GeneralUtility::quoteJSvalue($this->bparams) . ';
		}');
		$this->getPageRenderer()->addCssFile(ExtensionManagementUtility::extRelPath('t3skin') . 'rtehtmlarea/htmlarea.css');
		$this->doc->getContextMenuCode();
	}

	/**
	 * Insert the image in the editing area
	 *
	 * @return void
	 */
	protected function insertImage() {
		$uidList = (string)GeneralUtility::_GP('uidList');
		if (!GeneralUtility::_GP('insertImage') || !$uidList) {
			return;
		}
		$uids = explode('|', $uidList);
		$insertJsStatements = array();
		foreach ($uids as $uid) {
			/** @var $fileObject File */
			$fileObject = ResourceFactory::getInstance()->getFileObject((int)$uid);
			// Get default values for alt and title attributes from file properties
			$altText = $fileObject->getProperty('alternative');
			$titleText = $fileObject->getProperty('title');
			switch ($this->act) {
				case 'magic':
					$insertJsStatements[] = $this->insertMagicImage($fileObject, $altText, $titleText, 'data-htmlarea-file-uid="' . $uid . '"');
					break;
				case 'plain':
					$insertJsStatements[] = $this->insertPlainImage($fileObject, $altText, $titleText, 'data-htmlarea-file-uid="' . $uid . '"');
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
		$this->insertImages($insertJsStatements);
		die;
	}

	/**
	 * Insert a magic image
	 *
	 * @param File $fileObject: the image file
	 * @param string $altText: text for the alt attribute of the image
	 * @param string $titleText: text for the title attribute of the image
	 * @param string $additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return string the magic image JS insertion statement
	 */
	public function insertMagicImage(File $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
		// Create the magic image service
		/** @var $magicImageService MagicImageService */
		$magicImageService = GeneralUtility::makeInstance(MagicImageService::class);
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
		return $this->imageInsertJsStatement($imageUrl, $magicImage->getProperty('width'), $magicImage->getProperty('height'), $altText, $titleText, $additionalParams);
	}

	/**
	 * Insert a plain image
	 *
	 * @param \TYPO3\CMS\Core\Resource\File $fileObject: the image file
	 * @param string $altText: text for the alt attribute of the image
	 * @param string $titleText: text for the title attribute of the image
	 * @param string $additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return string the plain image JS insertion statement
	 */
	public function insertPlainImage(File $fileObject, $altText = '', $titleText = '', $additionalParams = '') {
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
		return $this->imageInsertJsStatement($imageUrl, $width, $height, $altText, $titleText, $additionalParams);
	}

	/**
	 * Assemble the image insertion JS statement
	 *
	 * @param string $url: the url of the image
	 * @param int $width: the width of the image
	 * @param int $height: the height of the image
	 * @param string $altText: text for the alt attribute of the image
	 * @param string $titleText: text for the title attribute of the image
	 * @param string $additionalParams: text representing more html attributes to be added on the img tag
	 * @return string the image insertion JS statement
	 */
	protected function imageInsertJsStatement($url, $width, $height, $altText = '', $titleText = '', $additionalParams = '') {
		return 'insertImage(' . GeneralUtility::quoteJSvalue($url) . ',' . $width . ',' . $height . ',' . GeneralUtility::quoteJSvalue($altText)
			. ',' . GeneralUtility::quoteJSvalue($titleText) . ',' . GeneralUtility::quoteJSvalue($additionalParams) . ');';
	}

	/**
	 * Echo the HTML page and JS that will insert the images
	 *
	 * @param array $insertJsStatements: array of image insertion JS statements
	 * @return void
	 */
	protected function insertImages($insertJsStatements) {
		echo '
<!DOCTYPE html>
<html>
<head>
	<title>Untitled</title>
	<script type="text/javascript">
		var plugin = window.parent.RTEarea[' . GeneralUtility::quoteJSvalue($this->editorNo) . '].editor.getPlugin("TYPO3Image");
		var imageTags = [];
		function insertImage(file,width,height,alt,title,additionalParams) {
			imageTags.push(\'<img src="\'+file+\'" width="\'+parseInt(width)+\'" height="\'+parseInt(height)+\'"\''
			. ($this->defaultClass ? '+\' class="' . $this->defaultClass . '"\'' : '')
			. '+ (alt ? \' alt="\' + alt + \'"\' : \'\') + (title ? \' title="\' + title + \'"\' : \'\') + (additionalParams ? \' \' + additionalParams : \'\') +\' />\');
		}
	</script>
</head>
<body>
<script type="text/javascript">
' . implode (LF, $insertJsStatements) . '
plugin.insertImage(imageTags.join(\' \'));
</script>
</body>
</html>';
	}

	/**
	 * Generate JS code to be used on the image insert/modify dialogue
	 *
	 * @param string $act: the action to be performed
	 * @param string $editorNo: the number of the RTE instance on the page
	 * @param string $sys_language_content: the language of the content element
	 * @return string the generated JS code
	 */
	public function getJSCode($act, $editorNo, $sys_language_content) {
		$JScode = '
			function insertElement(table, uid, type, fileName, filePath, fileExt, fileIcon, action, close) {
				return SelectImage.jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + "insertImage=1&uidList=" + uid);
			}
			function insertMultiple(table, uidList) {
				var uids = uidList.join("|");
				return SelectImage.jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + "insertImage=1&uidList=" + uids);
			}
			function jumpToUrl(URL,anchor) {
				SelectImage.jumpToUrl(URL, anchor);
			};';
		return $JScode;
	}

	/**
	 * Session data for this class can be set from outside with this method.
	 * Call after init()
	 *
	 * @param array $data Session data array
	 * @return array Session data and boolean which indicates that data needs to be stored in session because it's changed
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
	 * Rich Text Editor (RTE) image selector
	 *
	 * @param bool $wiz Not used here, kept for method signature compatibility with parent class
	 * @return string Modified content variable.
	 * @return string
	 */
	public function main_rte($wiz = FALSE) {
		// Starting content:
		$lang = $this->getLanguageService();
		$this->content = $this->doc->startPage($lang->getLL('Insert Image', TRUE));

		$this->content .= $this->doc->getTabMenuRaw($this->buildMenuArray($wiz, $this->allowedItems));
		switch ($this->act) {
			case 'image':
				$classesImage = $this->buttonConfig['properties.']['class.']['allowedClasses'] || $this->thisConfig['classesImage'] ? 'true' : 'false';
				$removedProperties = array();
				if (is_array($this->buttonConfig['properties.'])) {
					if ($this->buttonConfig['properties.']['removeItems']) {
						$removedProperties = GeneralUtility::trimExplode(',', $this->buttonConfig['properties.']['removeItems'], TRUE);
					}
				}
				$classesImageJSOptions = '';
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
				$labels = array('notSet','nonFloating','right','left','class','width','height','border','float','padding_top','padding_left','padding_bottom','padding_right','title','alt','update');
				foreach ($labels as $label) {
					$localizedLabels[$label] = $lang->getLL($label);
				}
				$localizedLabels['image_zoom'] = $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom', TRUE);
				$JScode = '
					require(["TYPO3/CMS/Rtehtmlarea/Modules/SelectImage"], function(SelectImage) {
						SelectImage.editorNo = "' . $this->editorNo . '";
						SelectImage.act = "' . $this->act . '";
						SelectImage.sys_language_content = "' . $this->sys_language_content . '";
						SelectImage.RTEtsConfigParams = "' . rawurlencode($this->RTEtsConfigParams) . '";
						SelectImage.bparams = "' . $this->bparams . '";
						SelectImage.classesImage =  ' . $classesImage . ';
						SelectImage.labels = ' . json_encode($localizedLabels) . ';
						SelectImage.Form.build(' . GeneralUtility::quoteJSvalue($classesImageJSOptions) . ', ' . json_encode($removedProperties) . ', ' . $lockPlainWidth . ', ' . $lockPlainHeight . ');
						SelectImage.Form.insertImageProperties();
					});';
				$this->content .= '<br />' . $this->doc->wrapScriptTags($JScode);
				break;
			case 'plain':
			case 'magic':
				// Create folder tree:
				$foldertree = GeneralUtility::makeInstance(FolderTree::class);
				$foldertree->thisScript = $this->thisScript;
				$tree = $foldertree->getBrowsableTree();
				// Get currently selected folder
				if ($this->curUrlInfo['value'] && $this->curUrlInfo['act'] === $this->act) {
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
						$fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
					} catch (\Exception $e) {
						// No path is selected
					}
					if ($fileOrFolderObject instanceof Folder) {
						// it's a folder
						$selectedFolder = $fileOrFolderObject;
					} elseif ($fileOrFolderObject instanceof FileInterface) {
						// it's a file
						try {
							$selectedFolder = $fileOrFolderObject->getParentFolder();
						} catch (\Exception $e) {
							// Accessing the parent folder failed for some reason. e.g. permissions
						}
					}
				}
				// If no folder is selected, get the user's default upload folder
				$backendUser = $this->getBackendUser();
				if (!$selectedFolder) {
					try {
						$selectedFolder = $backendUser->getDefaultUploadFolder();
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
				if ($backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
					$this->content .= $uploadForm;
				}
				// Render the filelist if there is a folder selected
				$files = '';
				if ($selectedFolder) {
					$files = $this->TBE_expandFolder(
						$selectedFolder,
						$this->act === 'plain' ? self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS : $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
						$backendUser->getTSConfigVal('options.noThumbsInRTEimageSelect')
					);
				}
				// Setup filelist indexed elements:
				$this->doc->JScode .= $this->doc->wrapScriptTags('
				require(["TYPO3/CMS/Backend/BrowseLinks"], function(BrowseLinks) {
					BrowseLinks.addElements(' . json_encode($this->elements) . ');
				});');
				// Wrap tree
				$this->content .= '

				<!--
					Wrapper table for folder tree / file/folder list:
				-->
						<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
							<tr>
								<td class="c-wCell" valign="top">' . $this->barheader(($lang->getLL('folderTree') . ':')) . $tree . '</td>
								<td class="c-wCell" valign="top">' . $files . '</td>
							</tr>
						</table>
						';
				// Add help message
				$helpMessage = $this->getHelpMessage($this->act);
				if ($helpMessage) {
					$this->content .= GeneralUtility::makeInstance(FlashMessage::class, $helpMessage, '', FlashMessage::INFO)
						->render();
				}
				// Adding create folder + upload form if applicable
				if (!$backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
					$this->content .= $uploadForm;
				}
				$this->content .= $createFolder;
				$this->content .= '<br />';
				break;
			case 'dragdrop':
				$foldertree = GeneralUtility::makeInstance(ElementBrowserFolderTreeView::class);
				$foldertree->thisScript = $this->thisScript;
				$foldertree->ext_noTempRecyclerDirs = TRUE;
				$tree = $foldertree->getBrowsableTree();
				// Get currently selected folder
				if ($this->curUrlInfo['value'] && $this->curUrlInfo['act'] === $this->act) {
					$cmpPath = $this->curUrlInfo['value'];
					if (!isset($this->expandFolder)) {
						$this->expandFolder = $cmpPath;
					}
				}
				$selectedFolder = FALSE;
				if ($this->expandFolder) {
					try {
						$selectedFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->expandFolder);
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
						<td style="vertical-align: top;">' . $this->barheader(($lang->getLL('folderTree') . ':')) . $tree . '</td>
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
		$this->doc->JScodeArray['rtehtmlarea'] = $this->getJSCode($this->act, $this->editorNo, $this->sys_language_content);
		$this->content = $this->doc->insertStylesAndJS($this->content);
		return $this->content;
	}

	/**
	 * Returns an array definition of the top menu
	 *
	 * @param bool $wiz
	 * @param array $allowedItems
	 * @return array
	 */
	protected function buildMenuArray($wiz, $allowedItems) {
		$menuDef = array();
		$lang = $this->getLanguageService();
		if (in_array('image', $this->allowedItems) && ($this->act === 'image' || $this->addModifyTab)) {
			$menuDef['image']['isActive'] = FALSE;
			$menuDef['image']['label'] = $lang->getLL('currentImage', TRUE);
			$menuDef['image']['url'] = '#';
			$menuDef['image']['addParams'] = 'onclick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + \'act=image\');return false;"';
		}
		if (in_array('magic', $this->allowedItems)) {
			$menuDef['magic']['isActive'] = FALSE;
			$menuDef['magic']['label'] = $lang->getLL('magicImage', TRUE);
			$menuDef['magic']['url'] = '#';
			$menuDef['magic']['addParams'] = 'onclick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + \'act=magic\');return false;"';
		}
		if (in_array('plain', $this->allowedItems)) {
			$menuDef['plain']['isActive'] = FALSE;
			$menuDef['plain']['label'] = $lang->getLL('plainImage', TRUE);
			$menuDef['plain']['url'] = '#';
			$menuDef['plain']['addParams'] = 'onclick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + \'act=plain\');return false;"';
		}
		if (in_array('dragdrop', $this->allowedItems)) {
			$menuDef['dragdrop']['isActive'] = FALSE;
			$menuDef['dragdrop']['label'] = $lang->getLL('dragDropImage', TRUE);
			$menuDef['dragdrop']['url'] = '#';
			$menuDef['dragdrop']['addParams'] = 'onclick="jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript()) . ' + \'act=dragdrop\');return false;"';
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
		return $menuDef;
	}

	/**
	 * Initializes the configuration variables
	 *
	 * @return void
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
	 * @return string path to the image
	 */
	protected function getImgPath() {
		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		return $RTEtsConfigParts[6];
	}

	/**
	 * Get the configuration of the image button
	 *
	 * @return array the configuration array of the image button
	 */
	protected function getButtonConfig() {
		return is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.']) ? $this->thisConfig['buttons.']['image.'] : array();
	}

	/**
	 * Get the allowed items or tabs
	 *
	 * @param string $items: initial list of possible items
	 * @return array the allowed items
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
		if ($this->act !== 'image' && !$this->addModifyTab) {
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
	 * @return string the default class, if any
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
	 * @return void
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
				return sprintf($this->getLanguageService()->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight);
				break;
			case 'magic':
				return sprintf($this->getLanguageService()->getLL('magicImage_msg'));
				break;
			default:
				return '';
		}
	}

	/**
	 * Checks if the given file is selectable in the filelist.
	 *
	 * In "plain" RTE mode only image files with a maximum width and height are selectable.
	 *
	 * @param FileInterface $file
	 * @param array $imgInfo Image dimensions from \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageDimensions()
	 * @return bool TRUE if file is selectable.
	 */
	protected function fileIsSelectableInFileList(FileInterface $file, array $imgInfo) {
		return (
			$this->act !== 'plain'
			|| (
				GeneralUtility::inList(self::PLAIN_MODE_IMAGE_FILE_EXTENSIONS, strtolower($file->getExtension()))
				&& $imgInfo[0] <= $this->plainMaxWidth
				&& $imgInfo[1] <= $this->plainMaxHeight
			)
		);
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
