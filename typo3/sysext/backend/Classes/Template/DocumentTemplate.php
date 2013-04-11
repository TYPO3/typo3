<?php
namespace TYPO3\CMS\Backend\Template;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * TYPO3 Backend Template Class
 *
 * This class contains functions for starting and ending the HTML of backend modules
 * It also contains methods for outputting sections of content.
 * Further there are functions for making icons, links, setting form-field widths etc.
 * Color scheme and stylesheet definitions are also available here.
 * Finally this file includes the language class for TYPO3's backend.
 *
 * After this file $LANG and $TBE_TEMPLATE are global variables / instances of their respective classes.
 * This file is typically included right after the init.php file,
 * if language and layout is needed.
 *
 * Please refer to Inside TYPO3 for a discussion of how to use this API.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DocumentTemplate {

	// Vars you typically might want to/should set from outside after making instance of this class:
	// 'backPath' pointing back to the PATH_typo3
	/**
	 * @todo Define visibility
	 */
	public $backPath = '';

	// This can be set to the HTML-code for a formtag. Useful when you need a form to span the whole page; Inserted exactly after the body-tag.
	/**
	 * @todo Define visibility
	 */
	public $form = '';

	// Similar to $JScode (see below) but used as an associative array to prevent double inclusion of JS code. This is used to include certain external Javascript libraries before the inline JS code. <script>-Tags are not wrapped around automatically
	/**
	 * @todo Define visibility
	 */
	public $JScodeLibArray = array();

	// Additional header code (eg. a JavaScript section) could be accommulated in this var. It will be directly outputted in the header.
	/**
	 * @todo Define visibility
	 */
	public $JScode = '';

	// Additional header code for ExtJS. It will be included in document header and inserted in a Ext.onReady(function()
	/**
	 * @todo Define visibility
	 */
	public $extJScode = '';

	// Similar to $JScode but for use as array with associative keys to prevent double inclusion of JS code. a <script> tag is automatically wrapped around.
	/**
	 * @todo Define visibility
	 */
	public $JScodeArray = array();

	// Additional 'page-end' code could be accommulated in this var. It will be outputted at the end of page before </body> and some other internal page-end code.
	/**
	 * @todo Define visibility
	 */
	public $postCode = '';

	// Doc-type used in the header. Default is xhtml_trans. You can also set it to 'html_3', 'xhtml_strict' or 'xhtml_frames'.
	/**
	 * @todo Define visibility
	 */
	public $docType = '';

	// HTML template with markers for module
	/**
	 * @todo Define visibility
	 */
	public $moduleTemplate = '';

	// the base file (not overlaid by TBE_STYLES) for the current module, useful for hooks when finding out which modules is rendered currently
	protected $moduleTemplateFilename = '';

	// Other vars you can change, but less frequently used:
	// Script ID.
	/**
	 * @todo Define visibility
	 */
	public $scriptID = '';

	// Id which can be set for the body tag. Default value is based on script ID
	/**
	 * @todo Define visibility
	 */
	public $bodyTagId = '';

	// You can add additional attributes to the body-tag through this variable.
	/**
	 * @todo Define visibility
	 */
	public $bodyTagAdditions = '';

	// Additional CSS styles which will be added to the <style> section in the header
	/**
	 * @todo Define visibility
	 */
	public $inDocStyles = '';

	// Like $inDocStyles but for use as array with associative keys to prevent double inclusion of css code
	/**
	 * @todo Define visibility
	 */
	public $inDocStylesArray = array();

	// Multiplication factor for formWidth() input size (default is 48* this value).
	/**
	 * @todo Define visibility
	 */
	public $form_rowsToStylewidth = 9.58;

	// Compensation for large documents (used in \TYPO3\CMS\Backend\Form\FormEngine)
	/**
	 * @todo Define visibility
	 */
	public $form_largeComp = 1.33;

	// If set, then a JavaScript section will be outputted in the bottom of page which will try and update the top.busy session expiry object.
	/**
	 * @todo Define visibility
	 */
	public $endJS = 1;

	// TYPO3 Colorscheme.
	// If you want to change this, please do so through a skin using the global var $GLOBALS['TBE_STYLES']
	// Light background color
	/**
	 * @todo Define visibility
	 */
	public $bgColor = '#F7F3EF';

	// Steel-blue
	/**
	 * @todo Define visibility
	 */
	public $bgColor2 = '#9BA1A8';

	// dok.color
	/**
	 * @todo Define visibility
	 */
	public $bgColor3 = '#F6F2E6';

	// light tablerow background, brownish
	/**
	 * @todo Define visibility
	 */
	public $bgColor4 = '#D9D5C9';

	// light tablerow background, greenish
	/**
	 * @todo Define visibility
	 */
	public $bgColor5 = '#ABBBB4';

	// light tablerow background, yellowish, for section headers. Light.
	/**
	 * @todo Define visibility
	 */
	public $bgColor6 = '#E7DBA8';

	/**
	 * @todo Define visibility
	 */
	public $hoverColor = '#254D7B';

	// Filename of stylesheet (relative to PATH_typo3)
	/**
	 * @todo Define visibility
	 */
	public $styleSheetFile = '';

	// Filename of stylesheet #2 - linked to right after the $this->styleSheetFile script (relative to PATH_typo3)
	/**
	 * @todo Define visibility
	 */
	public $styleSheetFile2 = '';

	// Filename of a post-stylesheet - included right after all inline styles.
	/**
	 * @todo Define visibility
	 */
	public $styleSheetFile_post = '';

	// Background image of page (relative to PATH_typo3)
	/**
	 * @todo Define visibility
	 */
	public $backGroundImage = '';

	// Inline css styling set from TBE_STYLES array
	/**
	 * @todo Define visibility
	 */
	public $inDocStyles_TBEstyle = '';

	/**
	 * Whether to use the X-UA-Compatible meta tag
	 *
	 * @var boolean
	 */
	protected $useCompatibilityTag = TRUE;

	/**
	 * X-Ua-Compatible version output in meta tag
	 *
	 * @var string
	 */
	protected $xUaCompatibilityVersion = 'IE=9';

	// Skinning
	// stylesheets from core
	protected $stylesheetsCore = array(
		'structure' => 'stylesheets/structure/',
		'visual' => 'stylesheets/visual/',
		'generatedSprites' => '../typo3temp/sprites/'
	);

	// Include these CSS directories from skins by default
	protected $stylesheetsSkins = array(
		'structure' => 'stylesheets/structure/',
		'visual' => 'stylesheets/visual/'
	);

	/**
	 * JavaScript files loaded for every page in the Backend
	 *
	 * @var array
	 */
	protected $jsFiles = array(
		'modernizr' => 'contrib/modernizr/modernizr.min.js'
	);

	// DEV:
	// Will output the parsetime of the scripts in milliseconds (for admin-users). Set this to FALSE when releasing TYPO3. Only for dev.
	/**
	 * @todo Define visibility
	 */
	public $parseTimeFlag = 0;

	/**
	 * internal character set, nowadays utf-8 for everything
	 */
	protected $charset = 'utf-8';

	// Internal: Indicates if a <div>-output section is open
	/**
	 * @todo Define visibility
	 */
	public $sectionFlag = 0;

	// (Default) Class for wrapping <DIV>-tag of page. Is set in class extensions.
	/**
	 * @todo Define visibility
	 */
	public $divClass = '';

	/**
	 * @todo Define visibility
	 */
	public $pageHeaderBlock = '';

	/**
	 * @todo Define visibility
	 */
	public $endOfPageJsBlock = '';

	/**
	 * @todo Define visibility
	 */
	public $hasDocheader = TRUE;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	// Alternative template file
	protected $pageHeaderFooterTemplateFile = '';

	protected $extDirectStateProvider = FALSE;

	/**
	 * Whether flashmessages should be rendered or not
	 *
	 * @var boolean $showFlashMessages
	 */
	public $showFlashMessages = TRUE;

	const STATUS_ICON_ERROR = 3;
	const STATUS_ICON_WARNING = 2;
	const STATUS_ICON_NOTIFICATION = 1;
	const STATUS_ICON_OK = -1;
	/**
	 * Constructor
	 * Imports relevant parts from global $GLOBALS['TBE_STYLES'] (colorscheme)
	 */
	public function __construct() {
		// Initializes the page rendering object:
		$this->getPageRenderer();
		// Setting default scriptID:
		if (($temp_M = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M')) && $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M]) {
			$this->scriptID = preg_replace('/^.*\\/(sysext|ext)\\//', 'ext/', $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M] . 'index.php');
		} else {
			$this->scriptID = preg_replace('/^.*\\/(sysext|ext)\\//', 'ext/', substr(PATH_thisScript, strlen(PATH_site)));
		}
		if (TYPO3_mainDir != 'typo3/' && substr($this->scriptID, 0, strlen(TYPO3_mainDir)) == TYPO3_mainDir) {
			// This fixes if TYPO3_mainDir has been changed so the script ids are STILL "typo3/..."
			$this->scriptID = 'typo3/' . substr($this->scriptID, strlen(TYPO3_mainDir));
		}
		$this->bodyTagId = preg_replace('/[^A-Za-z0-9-]/', '-', $this->scriptID);
		// Individual configuration per script? If so, make a recursive merge of the arrays:
		if (is_array($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID])) {
			// Make copy
			$ovr = $GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID];
			// merge styles.
			$GLOBALS['TBE_STYLES'] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($GLOBALS['TBE_STYLES'], $ovr);
			// Have to unset - otherwise the second instantiation will do it again!
			unset($GLOBALS['TBE_STYLES']['scriptIDindex'][$this->scriptID]);
		}
		// Color scheme:
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor']) {
			$this->bgColor = $GLOBALS['TBE_STYLES']['mainColors']['bgColor'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor1']) {
			$this->bgColor1 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor1'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor2']) {
			$this->bgColor2 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor2'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor3']) {
			$this->bgColor3 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor3'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor4']) {
			$this->bgColor4 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor4'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor5']) {
			$this->bgColor5 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor5'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['bgColor6']) {
			$this->bgColor6 = $GLOBALS['TBE_STYLES']['mainColors']['bgColor6'];
		}
		if ($GLOBALS['TBE_STYLES']['mainColors']['hoverColor']) {
			$this->hoverColor = $GLOBALS['TBE_STYLES']['mainColors']['hoverColor'];
		}
		// Main Stylesheets:
		if ($GLOBALS['TBE_STYLES']['stylesheet']) {
			$this->styleSheetFile = $GLOBALS['TBE_STYLES']['stylesheet'];
		}
		if ($GLOBALS['TBE_STYLES']['stylesheet2']) {
			$this->styleSheetFile2 = $GLOBALS['TBE_STYLES']['stylesheet2'];
		}
		if ($GLOBALS['TBE_STYLES']['styleSheetFile_post']) {
			$this->styleSheetFile_post = $GLOBALS['TBE_STYLES']['styleSheetFile_post'];
		}
		if ($GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle']) {
			$this->inDocStyles_TBEstyle = $GLOBALS['TBE_STYLES']['inDocStyles_TBEstyle'];
		}
		// include all stylesheets
		foreach ($this->getSkinStylesheetDirectories() as $stylesheetDirectory) {
			$this->addStylesheetDirectory($stylesheetDirectory);
		}
		// Background image
		if ($GLOBALS['TBE_STYLES']['background']) {
			$this->backGroundImage = $GLOBALS['TBE_STYLES']['background'];
		}
	}

	/**
	 * Gets instance of PageRenderer configured with the current language, file references and debug settings
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
			$this->pageRenderer->setTemplateFile(TYPO3_mainDir . 'templates/template_page_backend.html');
			$this->pageRenderer->setLanguage($GLOBALS['LANG']->lang);
			$this->pageRenderer->enableConcatenateFiles();
			$this->pageRenderer->enableCompressCss();
			$this->pageRenderer->enableCompressJavascript();
			// Add all JavaScript files defined in $this->jsFiles to the PageRenderer
			foreach ($this->jsFiles as $file) {
				$this->pageRenderer->addJsFile($GLOBALS['BACK_PATH'] . $file);
			}
		}
		if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) === 1) {
			$this->pageRenderer->enableDebugMode();
		}
		return $this->pageRenderer;
	}

	/**
	 * Sets inclusion of StateProvider
	 *
	 * @return void
	 */
	public function setExtDirectStateProvider() {
		$this->extDirectStateProvider = TRUE;
	}

	/*****************************************
	 *
	 * EVALUATION FUNCTIONS
	 * Various centralized processing
	 *
	 *****************************************/
	/**
	 * Makes click menu link (context sensitive menu)
	 * Returns $str (possibly an <|img> tag/icon) wrapped in a link which will activate the context sensitive menu for the record ($table/$uid) or file ($table = file)
	 * The link will load the top frame with the parameter "&item" which is the table,uid and listFr arguments imploded by "|": rawurlencode($table.'|'.$uid.'|'.$listFr)
	 *
	 * @param string $str String to be wrapped in link, typ. image tag.
	 * @param string $table Table name/File path. If the icon is for a database record, enter the tablename from $GLOBALS['TCA']. If a file then enter the absolute filepath
	 * @param integer $uid If icon is for database record this is the UID for the record from $table
	 * @param boolean $listFr Tells the top frame script that the link is coming from a "list" frame which means a frame from within the backend content frame.
	 * @param string $addParams Additional GET parameters for the link to alt_clickmenu.php
	 * @param string $enDisItems Enable / Disable click menu items. Example: "+new,view" will display ONLY these two items (and any spacers in between), "new,view" will display all BUT these two items.
	 * @param boolean $returnOnClick If set, will return only the onclick JavaScript, not the whole link.
	 * @return string The link-wrapped input string.
	 * @todo Define visibility
	 */
	public function wrapClickMenuOnIcon($str, $table, $uid = 0, $listFr = TRUE, $addParams = '', $enDisItems = '', $returnOnClick = FALSE) {
		$backPath = rawurlencode($this->backPath) . '|' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(($this->backPath . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']));
		$onClick = 'showClickmenu("' . $table . '","' . ($uid !== 0 ? $uid : '') . '","' . strval($listFr) . '","' . str_replace('+', '%2B', $enDisItems) . '","' . str_replace('&', '&amp;', addcslashes($backPath, '"')) . '","' . str_replace('&', '&amp;', addcslashes($addParams, '"')) . '");return false;';
		return $returnOnClick ? $onClick : '<a href="#" onclick="' . htmlspecialchars($onClick) . '" oncontextmenu="' . htmlspecialchars($onClick) . '">' . $str . '</a>';
	}

	/**
	 * Makes link to page $id in frontend (view page)
	 * Returns an magnifier-glass icon which links to the frontend index.php document for viewing the page with id $id
	 * $id must be a page-uid
	 * If the BE_USER has access to Web>List then a link to that module is shown as well (with return-url)
	 *
	 * @param integer $id The page id
	 * @param string $backPath The current "BACK_PATH" (the back relative to the typo3/ directory)
	 * @param string $addParams Additional parameters for the image tag(s)
	 * @return string HTML string with linked icon(s)
	 * @todo Define visibility
	 */
	public function viewPageIcon($id, $backPath, $addParams = 'hspace="3"') {
		// If access to Web>List for user, then link to that module.
		$str = \TYPO3\CMS\Backend\Utility\BackendUtility::getListViewLink(array(
			'id' => $id,
			'returnUrl' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')
		), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showList'));
		// Make link to view page
		$str .= '<a href="#" onclick="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($id, $backPath, \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id))) . '">' . '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($backPath, 'gfx/zoom.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', 1) . '"' . ($addParams ? ' ' . trim($addParams) : '') . ' hspace="3" alt="" />' . '</a>';
		return $str;
	}

	/**
	 * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
	 * See description of the API elsewhere.
	 *
	 * @param string $params is a set of GET params to send to tce_db.php. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
	 * @param string $redirectUrl Redirect URL if any other that \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') is wished
	 * @return string URL to tce_db.php + parameters (backpath is taken from $this->backPath)
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick()
	 * @todo Define visibility
	 */
	public function issueCommand($params, $redirectUrl = '') {
		$redirectUrl = $redirectUrl ? $redirectUrl : \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
		$commandUrl = $this->backPath . 'tce_db.php?' . $params . '&redirect=' . ($redirectUrl == -1 ? '\'+T3_THIS_LOCATION+\'' : rawurlencode($redirectUrl)) . '&vC=' . rawurlencode($GLOBALS['BE_USER']->veriCode()) . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '&prErr=1&uPT=1';
		return $commandUrl;
	}

	/**
	 * Returns TRUE if click-menu layers can be displayed for the current user/browser
	 * Use this to test if click-menus (context sensitive menus) can and should be displayed in the backend.
	 *
	 * @return boolean
	 * @deprecated since TYPO3 4.7, will be removed in TYPO3 6.1 - This function makes no sense anymore
	 * @todo Define visibility
	 */
	public function isCMlayers() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return !$GLOBALS['BE_USER']->uc['disableCMlayers'] && $GLOBALS['CLIENT']['FORMSTYLE'] && !($GLOBALS['CLIENT']['SYSTEM'] == 'mac' && $GLOBALS['CLIENT']['BROWSER'] == 'Opera');
	}

	/**
	 * Makes the header (icon+title) for a page (or other record). Used in most modules under Web>*
	 * $table and $row must be a tablename/record from that table
	 * $path will be shown as alt-text for the icon.
	 * The title will be truncated to 45 chars.
	 *
	 * @param string $table Table name
	 * @param array $row Record row
	 * @param string $path Alt text
	 * @param boolean $noViewPageIcon Set $noViewPageIcon TRUE if you don't want a magnifier-icon for viewing the page in the frontend
	 * @param array $tWrap is an array with indexes 0 and 1 each representing HTML-tags (start/end) which will wrap the title
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function getHeader($table, $row, $path, $noViewPageIcon = FALSE, $tWrap = array('', '')) {
		$viewPage = '';
		if (is_array($row) && $row['uid']) {
			$iconImgTag = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row, array('title' => htmlspecialchars($path)));
			$title = strip_tags(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $row));
			$viewPage = $noViewPageIcon ? '' : $this->viewPageIcon($row['uid'], $this->backPath, '');
			if ($table == 'pages') {
				$path .= ' - ' . \TYPO3\CMS\Backend\Utility\BackendUtility::titleAttribForPages($row, '', 0);
			}
		} else {
			$iconImgTag = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-page-domain', array('title' => htmlspecialchars($path)));
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}
		return '<span class="typo3-moduleHeader">' . $this->wrapClickMenuOnIcon($iconImgTag, $table, $row['uid']) . $viewPage . $tWrap[0] . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, 45)) . $tWrap[1] . '</span>';
	}

	/**
	 * Like ->getHeader() but for files in the File>* main module/submodules
	 * Returns the file-icon with the path of the file set in the alt/title attribute. Shows the file-name after the icon.
	 *
	 * @param string $title Title string, expected to be the filepath
	 * @param string $path Alt text
	 * @param string $iconfile The icon file (relative to TYPO3 dir)
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function getFileheader($title, $path, $iconfile) {
		$fileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($title);
		$title = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($fileInfo['path'], -35)) . '<strong>' . htmlspecialchars($fileInfo['file']) . '</strong>';
		return '<span class="typo3-moduleHeader"><img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, $iconfile, 'width="18" height="16"') . ' title="' . htmlspecialchars($path) . '" alt="" />' . $title . '</span>';
	}

	/**
	 * Returns a linked shortcut-icon which will call the shortcut frame and set a shortcut there back to the calling page/module
	 *
	 * @param string $gvList Is the list of GET variables to store (if any)
	 * @param string $setList Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
	 * @param string $modName Module name string
	 * @param string $motherModName Is used to enter the "parent module name" if the module is a submodule under eg. Web>* or File>*. You can also set this value to "1" in which case the currentLoadedModule is sent to the shortcut script (so - not a fixed value!) - that is used in file_edit.php and wizard_rte.php scripts where those scripts are really running as a part of another module.
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function makeShortcutIcon($gvList, $setList, $modName, $motherModName = '') {
		$backPath = $this->backPath;
		$storeUrl = $this->makeShortcutUrl($gvList, $setList);
		$pathInfo = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		// Add the module identifier automatically if typo3/mod.php is used:
		if (preg_match('/typo3\\/mod\\.php$/', $pathInfo['path']) && isset($GLOBALS['TBE_MODULES']['_PATHS'][$modName])) {
			$storeUrl = '&M=' . $modName . $storeUrl;
		}
		if (!strcmp($motherModName, '1')) {
			$mMN = '&motherModName=\'+top.currentModuleLoaded+\'';
		} elseif ($motherModName) {
			$mMN = '&motherModName=' . rawurlencode($motherModName);
		} else {
			$mMN = '';
		}
		$onClick = 'top.ShortcutManager.createShortcut(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark')) . ', ' . '\'' . $backPath . '\', ' . '\'' . rawurlencode($modName) . '\', ' . '\'' . rawurlencode(($pathInfo['path'] . '?' . $storeUrl)) . $mMN . '\'' . ');return false;';
		$sIcon = '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.makeBookmark', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-shortcut-new') . '</a>';
		return $sIcon;
	}

	/**
	 * MAKE url for storing
	 * Internal func
	 *
	 * @param string $gvList Is the list of GET variables to store (if any)
	 * @param string $setList Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
	 * @return string
	 * @access private
	 * @see makeShortcutIcon()
	 * @todo Define visibility
	 */
	public function makeShortcutUrl($gvList, $setList) {
		$GET = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		$storeArray = array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::compileSelectedGetVarsFromArray($gvList, $GET), array('SET' => \TYPO3\CMS\Core\Utility\GeneralUtility::compileSelectedGetVarsFromArray($setList, (array) $GLOBALS['SOBE']->MOD_SETTINGS)));
		$storeUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $storeArray);
		return $storeUrl;
	}

	/**
	 * Returns <input> attributes to set the width of an text-type input field.
	 * For client browsers with no CSS support the cols/size attribute is returned.
	 * For CSS compliant browsers (recommended) a ' style="width: ...px;"' is returned.
	 *
	 * @param integer $size A relative number which multiplied with approx. 10 will lead to the width in pixels
	 * @param boolean $textarea A flag you can set for textareas - DEPRECATED, use ->formWidthText() for textareas!!!
	 * @param string $styleOverride A string which will be returned as attribute-value for style="" instead of the calculated width (if CSS is enabled)
	 * @return string Tag attributes for an <input> tag (regarding width)
	 * @see formWidthText()
	 * @todo Define visibility
	 */
	public function formWidth($size = 48, $textarea = FALSE, $styleOverride = '') {
		$wAttrib = $textarea ? 'cols' : 'size';
		// If not setting the width by style-attribute
		if (!$GLOBALS['CLIENT']['FORMSTYLE']) {
			$retVal = ' ' . $wAttrib . '="' . $size . '"';
		} else {
			// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$pixels = ceil($size * $this->form_rowsToStylewidth);
			$retVal = $styleOverride ? ' style="' . $styleOverride . '"' : ' style="width:' . $pixels . 'px;"';
		}
		return $retVal;
	}

	/**
	 * This function is dedicated to textareas, which has the wrapping on/off option to observe.
	 * EXAMPLE:
	 * <textarea rows="10" wrap="off" '.$GLOBALS["TBE_TEMPLATE"]->formWidthText(48, "", "off").'>
	 * or
	 * <textarea rows="10" wrap="virtual" '.$GLOBALS["TBE_TEMPLATE"]->formWidthText(48, "", "virtual").'>
	 *
	 * @param integer $size A relative number which multiplied with approx. 10 will lead to the width in pixels
	 * @param string $styleOverride A string which will be returned as attribute-value for style="" instead of the calculated width (if CSS is enabled)
	 * @param string $wrap Pass on the wrap-attribute value you use in your <textarea>! This will be used to make sure that some browsers will detect wrapping alright.
	 * @return string Tag attributes for an <input> tag (regarding width)
	 * @see formWidth()
	 * @todo Define visibility
	 */
	public function formWidthText($size = 48, $styleOverride = '', $wrap = '') {
		$wTags = $this->formWidth($size, 1, $styleOverride);
		// Netscape 6+/Mozilla seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap)) != 'off' && $GLOBALS['CLIENT']['BROWSER'] == 'net' && $GLOBALS['CLIENT']['VERSION'] >= 5) {
			$wTags .= ' cols="' . $size . '"';
		}
		return $wTags;
	}

	/**
	 * Returns JavaScript variables setting the returnUrl and thisScript location for use by JavaScript on the page.
	 * Used in fx. db_list.php (Web>List)
	 *
	 * @param string $thisLocation URL to "this location" / current script
	 * @return string Urls are returned as JavaScript variables T3_RETURN_URL and T3_THIS_LOCATION
	 * @see typo3/db_list.php
	 * @todo Define visibility
	 */
	public function redirectUrls($thisLocation = '') {
		$thisLocation = $thisLocation ? $thisLocation : \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array(
			'CB' => '',
			'SET' => '',
			'cmd' => '',
			'popViewId' => ''
		));
		$out = '
	var T3_RETURN_URL = \'' . str_replace('%20', '', rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl')))) . '\';
	var T3_THIS_LOCATION = \'' . str_replace('%20', '', rawurlencode($thisLocation)) . '\';
		';
		return $out;
	}

	/**
	 * Returns a formatted string of $tstamp
	 * Uses $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] and $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] to format date and time
	 *
	 * @param integer $tstamp UNIX timestamp, seconds since 1970
	 * @param integer $type How much data to show: $type = 1: hhmm, $type = 10:	ddmmmyy
	 * @return string Formatted timestamp
	 * @todo Define visibility
	 */
	public function formatTime($tstamp, $type) {
		$dateStr = '';
		switch ($type) {
		case 1:
			$dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $tstamp);
			break;
		case 10:
			$dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $tstamp);
			break;
		}
		return $dateStr;
	}

	/**
	 * Returns script parsetime IF ->parseTimeFlag is set and user is "admin"
	 * Automatically outputted in page end
	 *
	 * @return string HTML formated with <p>-tags
	 * @todo Define visibility
	 */
	public function parseTime() {
		if ($this->parseTimeFlag && $GLOBALS['BE_USER']->isAdmin()) {
			return '<p>(ParseTime: ' . (\TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $GLOBALS['PARSETIME_START']) . ' ms</p>
					<p>REQUEST_URI-length: ' . strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . ')</p>';
		}
	}

	/**
	 * Defines whether to use the X-UA-Compatible meta tag.
	 *
	 * @param boolean $useCompatibilityTag Whether to use the tag
	 * @return void
	 */
	public function useCompatibilityTag($useCompatibilityTag = TRUE) {
		$this->useCompatibilityTag = (bool) $useCompatibilityTag;
	}

	/*****************************************
	 *
	 *	PAGE BUILDING FUNCTIONS.
	 *	Use this to build the HTML of your backend modules
	 *
	 *****************************************/
	/**
	 * Returns page start
	 * This includes the proper header with charset, title, meta tag and beginning body-tag.
	 *
	 * @param string $title HTML Page title for the header
	 * @param boolean $includeCsh flag for including CSH
	 * @return string Returns the whole header section of a HTML-document based on settings in internal variables (like styles, javascript code, charset, generator and docType)
	 * @see endPage()
	 * @todo Define visibility
	 */
	public function startPage($title, $includeCsh = TRUE) {
		// hook pre start page
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'])) {
			$preStartPageHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'];
			if (is_array($preStartPageHook)) {
				$hookParameters = array(
					'title' => &$title
				);
				foreach ($preStartPageHook as $hookFunction) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}
		$this->pageRenderer->backPath = $this->backPath;
		// alternative template for Header and Footer
		if ($this->pageHeaderFooterTemplateFile) {
			$file = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->pageHeaderFooterTemplateFile, TRUE);
			if ($file) {
				$this->pageRenderer->setTemplateFile($file);
			}
		}
		// Send HTTP header for selected charset. Added by Robert Lemke 23.10.2003
		header('Content-Type:text/html;charset=' . $this->charset);
		// Standard HTML tag
		$htmlTag = '<html xmlns="http://www.w3.org/1999/xhtml">';
		switch ($this->docType) {
		case 'html_3':
			$headerStart = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">';
			$htmlTag = '<html>';
			// Disable rendering of XHTML tags
			$this->getPageRenderer()->setRenderXhtml(FALSE);
			break;
		case 'xhtml_strict':
			$headerStart = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
			break;
		case 'xhtml_frames':
			$headerStart = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
			break;
		case 'xhtml_trans':
			$headerStart = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			break;
		case 'html5':

		default:
			// The fallthrough is intended as HTML5, as this is the default for the BE since TYPO3 4.5
			$headerStart = '<!DOCTYPE html>' . LF;
			$htmlTag = '<html>';
			// Disable rendering of XHTML tags
			$this->getPageRenderer()->setRenderXhtml(FALSE);
			break;
		}
		$this->pageRenderer->setHtmlTag($htmlTag);
		// This loads the tabulator-in-textarea feature. It automatically modifies
		// every textarea which is found.
		if (!$GLOBALS['BE_USER']->uc['disableTabInTextarea']) {
			$this->loadJavascriptLib('tab.js');
		}
		// Include the JS for the Context Sensitive Help
		if ($includeCsh) {
			$this->loadCshJavascript();
		}
		// Get the browser info
		$browserInfo = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'));
		// Set the XML prologue
		$xmlPrologue = '<?xml version="1.0" encoding="' . $this->charset . '"?>';
		// Set the XML stylesheet
		$xmlStylesheet = '<?xml-stylesheet href="#internalStyle" type="text/css"?>';
		// Add the XML prologue for XHTML doctypes
		if (strpos($this->docType, 'xhtml') !== FALSE) {
			// Put the XML prologue before or after the doctype declaration according to browser
			if ($browserInfo['browser'] === 'msie' && $browserInfo['version'] < 7) {
				$headerStart = $headerStart . LF . $xmlPrologue;
			} else {
				$headerStart = $xmlPrologue . LF . $headerStart;
			}
			// Add the xml stylesheet according to doctype
			if ($this->docType !== 'xhtml_frames') {
				$headerStart = $headerStart . LF . $xmlStylesheet;
			}
		}
		$this->pageRenderer->setXmlPrologAndDocType($headerStart);
		$this->pageRenderer->setHeadTag('<head>' . LF . '<!-- TYPO3 Script ID: ' . htmlspecialchars($this->scriptID) . ' -->');
		$this->pageRenderer->setCharSet($this->charset);
		$this->pageRenderer->addMetaTag($this->generator());
		$this->pageRenderer->addMetaTag('<meta name="robots" content="noindex,follow" />');
		$this->pageRenderer->setFavIcon($this->getBackendFavicon());
		if ($this->useCompatibilityTag) {
			$this->pageRenderer->addMetaTag($this->xUaCompatible($this->xUaCompatibilityVersion));
		}
		$this->pageRenderer->setTitle($title);
		// add docstyles
		$this->docStyle();
		if ($this->extDirectStateProvider) {
			$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ExtDirect.StateProvider.js');
		}
		// Add jsCode for overriding the console with a debug panel connection
		$this->pageRenderer->addJsInlineCode('consoleOverrideWithDebugPanel', 'if (typeof top.Ext === "object") {
				top.Ext.onReady(function() {
					if (typeof console === "undefined") {
						if (top && top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.DebugConsole) {
							console = top.TYPO3.Backend.DebugConsole;
						} else {
							console = {
								log: Ext.log,
								info: Ext.log,
								warn: Ext.log,
								error: Ext.log
							};
						}
					}
				});
			}
			', FALSE);
		$this->pageRenderer->addHeaderData($this->JScode);
		foreach ($this->JScodeArray as $name => $code) {
			$this->pageRenderer->addJsInlineCode($name, $code, FALSE);
		}
		if (count($this->JScodeLibArray)) {
			foreach ($this->JScodeLibArray as $library) {
				$this->pageRenderer->addHeaderData($library);
			}
		}
		if ($this->extJScode) {
			$this->pageRenderer->addExtOnReadyCode($this->extJScode);
		}
		// hook for additional headerData
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'])) {
			$preHeaderRenderHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'];
			if (is_array($preHeaderRenderHook)) {
				$hookParameters = array(
					'pageRenderer' => &$this->pageRenderer
				);
				foreach ($preHeaderRenderHook as $hookFunction) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}
		// Construct page header.
		$str = $this->pageRenderer->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_HEADER);
		$this->JScodeLibArray = array();
		$this->JScode = ($this->extJScode = '');
		$this->JScodeArray = array();
		$this->endOfPageJsBlock = $this->pageRenderer->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		if ($this->docType == 'xhtml_frames') {
			return $str;
		} else {
			$str .= $this->docBodyTagBegin() . ($this->divClass ? '

<!-- Wrapping DIV-section for whole page BEGIN -->
<div class="' . $this->divClass . '">
' : '') . trim($this->form);
		}
		return $str;
	}

	/**
	 * Returns page end; This includes finishing form, div, body and html tags.
	 *
	 * @return string The HTML end of a page
	 * @see startPage()
	 * @todo Define visibility
	 */
	public function endPage() {
		$str = $this->sectionEnd() . $this->postCode . $this->endPageJS() . $this->wrapScriptTags(\TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode()) . $this->parseTime() . ($this->form ? '
</form>' : '');
		// If something is in buffer like debug, put it to end of page
		if (ob_get_contents()) {
			$str .= ob_get_clean();
			if (!headers_sent()) {
				header('Content-Encoding: None');
			}
		}
		if ($this->docType !== 'xhtml_frames') {
			$str .= ($this->divClass ? '

<!-- Wrapping DIV-section for whole page END -->
</div>' : '') . $this->endOfPageJsBlock;
		}
		// Logging: Can't find better place to put it:
		if (TYPO3_DLOG) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('END of BACKEND session', 'TYPO3\\CMS\\Backend\\Template\\DocumentTemplate', 0, array('_FLUSH' => TRUE));
		}
		return $str;
	}

	/**
	 * Shortcut for render the complete page of a module
	 *
	 * @param string $title page title
	 * @param string $content page content
	 * @param boolean $includeCsh flag for including csh code
	 * @return string complete page
	 */
	public function render($title, $content, $includeCsh = TRUE) {
		$pageContent = $this->startPage($title, $includeCsh);
		$pageContent .= $content;
		$pageContent .= $this->endPage();
		return $this->insertStylesAndJS($pageContent);
	}

	/**
	 * Returns the header-bar in the top of most backend modules
	 * Closes section if open.
	 *
	 * @param string $text The text string for the header
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function header($text) {
		$str = '

	<!-- MAIN Header in page top -->
	<h2>' . htmlspecialchars($text) . '</h2>
';
		return $this->sectionEnd() . $str;
	}

	/**
	 * Begins an output section and sets header and content
	 *
	 * @param string $label The header
	 * @param string $text The HTML-content
	 * @param boolean $nostrtoupper	A flag that will prevent the header from being converted to uppercase
	 * @param boolean $sH Defines the type of header (if set, "<h3>" rather than the default "h4")
	 * @param integer $type The number of an icon to show with the header (see the icon-function). -1,1,2,3
	 * @param boolean $allowHTMLinHeader If set, HTML tags are allowed in $label (otherwise this value is by default htmlspecialchars()'ed)
	 * @return string HTML content
	 * @see icons(), sectionHeader()
	 * @todo Define visibility
	 */
	public function section($label, $text, $nostrtoupper = FALSE, $sH = FALSE, $type = 0, $allowHTMLinHeader = FALSE) {
		$str = '';
		// Setting header
		if ($label) {
			if (!$allowHTMLinHeader) {
				$label = htmlspecialchars($label);
			}
			$str .= $this->sectionHeader($this->icons($type) . $label, $sH, $nostrtoupper ? '' : ' class="uppercase"');
		}
		// Setting content
		$str .= '

	<!-- Section content -->
' . $text;
		return $this->sectionBegin() . $str;
	}

	/**
	 * Inserts a divider image
	 * Ends a section (if open) before inserting the image
	 *
	 * @param integer $dist The margin-top/-bottom of the <hr> ruler.
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function divider($dist) {
		$dist = intval($dist);
		$str = '

	<!-- DIVIDER -->
	<hr style="margin-top: ' . $dist . 'px; margin-bottom: ' . $dist . 'px;" />
';
		return $this->sectionEnd() . $str;
	}

	/**
	 * Returns a blank <div>-section with a height
	 *
	 * @param integer $dist Padding-top for the div-section (should be margin-top but konqueror (3.1) doesn't like it :-(
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function spacer($dist) {
		if ($dist > 0) {
			return '

	<!-- Spacer element -->
	<div style="padding-top: ' . intval($dist) . 'px;"></div>
';
		}
	}

	/**
	 * Make a section header.
	 * Begins a section if not already open.
	 *
	 * @param string $label The label between the <h3> or <h4> tags. (Allows HTML)
	 * @param boolean $sH If set, <h3> is used, otherwise <h4>
	 * @param string $addAttrib Additional attributes to h-tag, eg. ' class=""'
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function sectionHeader($label, $sH = FALSE, $addAttrib = '') {
		$tag = $sH ? 'h3' : 'h4';
		if ($addAttrib && substr($addAttrib, 0, 1) !== ' ') {
			$addAttrib = ' ' . $addAttrib;
		}
		$str = '

	<!-- Section header -->
	<' . $tag . $addAttrib . '>' . $label . '</' . $tag . '>
';
		return $this->sectionBegin() . $str;
	}

	/**
	 * Begins an output section.
	 * Returns the <div>-begin tag AND sets the ->sectionFlag TRUE (if the ->sectionFlag is not already set!)
	 * You can call this function even if a section is already begun since the function will only return something if the sectionFlag is not already set!
	 *
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function sectionBegin() {
		if (!$this->sectionFlag) {
			$this->sectionFlag = 1;
			$str = '

	<!-- ***********************
	      Begin output section.
	     *********************** -->
	<div>
';
			return $str;
		} else {
			return '';
		}
	}

	/**
	 * Ends and output section
	 * Returns the </div>-end tag AND clears the ->sectionFlag (but does so only IF the sectionFlag is set - that is a section is 'open')
	 * See sectionBegin() also.
	 *
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function sectionEnd() {
		if ($this->sectionFlag) {
			$this->sectionFlag = 0;
			return '
	</div>
	<!-- *********************
	      End output section.
	     ********************* -->
';
		} else {
			return '';
		}
	}

	/**
	 * If a form-tag is defined in ->form then and end-tag for that <form> element is outputted
	 * Further a JavaScript section is outputted which will update the top.busy session-expiry object (unless $this->endJS is set to FALSE)
	 *
	 * @return string HTML content (<script> tag section)
	 * @todo Define visibility
	 */
	public function endPageJS() {
		return $this->endJS ? '
	<script type="text/javascript">
		  /*<![CDATA[*/
		if (top.busy && top.busy.loginRefreshed) {
			top.busy.loginRefreshed();
		}
		 /*]]>*/
	</script>' : '';
	}

	/**
	 * Creates the bodyTag.
	 * You can add to the bodyTag by $this->bodyTagAdditions
	 *
	 * @return string HTML body tag
	 * @todo Define visibility
	 */
	public function docBodyTagBegin() {
		$bodyContent = 'body onclick="if (top.menuReset) top.menuReset();" ' . trim(($this->bodyTagAdditions . ($this->bodyTagId ? ' id="' . $this->bodyTagId . '"' : '')));
		return '<' . trim($bodyContent) . '>';
	}

	/**
	 * Outputting document style
	 *
	 * @return string HTML style section/link tags
	 * @todo Define visibility
	 */
	public function docStyle() {
		// Request background image:
		if ($this->backGroundImage) {
			$this->inDocStylesArray[] = ' BODY { background-image: url(' . $this->backPath . $this->backGroundImage . '); }';
		}
		// Add inDoc styles variables as well:
		$this->inDocStylesArray[] = $this->inDocStyles;
		$this->inDocStylesArray[] = $this->inDocStyles_TBEstyle;
		// Implode it all:
		$inDocStyles = implode(LF, $this->inDocStylesArray);
		if ($this->styleSheetFile) {
			$this->pageRenderer->addCssFile($this->backPath . $this->styleSheetFile);
		}
		if ($this->styleSheetFile2) {
			$this->pageRenderer->addCssFile($this->backPath . $this->styleSheetFile2);
		}
		$this->pageRenderer->addCssInlineBlock('inDocStyles', $inDocStyles . LF . '/*###POSTCSSMARKER###*/');
		if ($this->styleSheetFile_post) {
			$this->pageRenderer->addCssFile($this->backPath . $this->styleSheetFile_post);
		}
	}

	/**
	 * Insert additional style sheet link
	 *
	 * @param string $key some key identifying the style sheet
	 * @param string $href uri to the style sheet file
	 * @param string $title value for the title attribute of the link element
	 * @param string $relation value for the rel attribute of the link element
	 * @return void
	 * @todo Define visibility
	 */
	public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet') {
		if (strpos($href, '://') !== FALSE || substr($href, 0, 1) === '/') {
			$file = $href;
		} else {
			$file = $this->backPath . $href;
		}
		$this->pageRenderer->addCssFile($file, $relation, 'screen', $title);
	}

	/**
	 * Add all *.css files of the directory $path to the stylesheets
	 *
	 * @param string $path directory to add
	 * @return void
	 * @todo Define visibility
	 */
	public function addStyleSheetDirectory($path) {
		// Calculation needed, when TYPO3 source is used via a symlink
		// absolute path to the stylesheets
		$filePath = dirname(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_FILENAME')) . '/' . $GLOBALS['BACK_PATH'] . $path;
		// Clean the path
		$resolvedPath = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($filePath);
		// Read all files in directory and sort them alphabetically
		$files = \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($resolvedPath, 'css', FALSE, 1);
		foreach ($files as $file) {
			$this->pageRenderer->addCssFile($GLOBALS['BACK_PATH'] . $path . $file, 'stylesheet', 'all');
		}
	}

	/**
	 * Insert post rendering document style into already rendered content
	 * This is needed for extobjbase
	 *
	 * @param string $content style-content to insert.
	 * @return string content with inserted styles
	 * @todo Define visibility
	 */
	public function insertStylesAndJS($content) {
		// Insert accumulated CSS
		$this->inDocStylesArray[] = $this->inDocStyles;
		$styles = LF . implode(LF, $this->inDocStylesArray);
		$content = str_replace('/*###POSTCSSMARKER###*/', $styles, $content);
		// Insert accumulated JS
		$jscode = $this->JScode . LF . $this->wrapScriptTags(implode(LF, $this->JScodeArray));
		$content = str_replace('<!--###POSTJSMARKER###-->', $jscode, $content);
		return $content;
	}

	/**
	 * Returns an array of all stylesheet directories belonging to core and skins
	 *
	 * @return array Stylesheet directories
	 */
	public function getSkinStylesheetDirectories() {
		$stylesheetDirectories = array();
		// Add default core stylesheets
		foreach ($this->stylesheetsCore as $stylesheetDir) {
			$stylesheetDirectories[] = $stylesheetDir;
		}
		// Stylesheets from skins
		// merge default css directories ($this->stylesheetsSkin) with additional ones and include them
		if (is_array($GLOBALS['TBE_STYLES']['skins'])) {
			// loop over all registered skins
			foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinExtKey => $skin) {
				$skinStylesheetDirs = $this->stylesheetsSkins;
				// Skins can add custom stylesheetDirectories using
				// $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories']
				if (is_array($skin['stylesheetDirectories'])) {
					$skinStylesheetDirs = array_merge($skinStylesheetDirs, $skin['stylesheetDirectories']);
				}
				// Add all registered directories
				foreach ($skinStylesheetDirs as $stylesheetDir) {
					// for EXT:myskin/stylesheets/ syntax
					if (substr($stylesheetDir, 0, 4) === 'EXT:') {
						list($extKey, $path) = explode('/', substr($stylesheetDir, 4), 2);
						if (strcmp($extKey, '') && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey) && strcmp($path, '')) {
							$stylesheetDirectories[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extKey) . $path;
						}
					} else {
						// For relative paths
						$stylesheetDirectories[] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($skinExtKey) . $stylesheetDir;
					}
				}
			}
		}
		return $stylesheetDirectories;
	}

	/**
	 * Initialize the charset.
	 * Sets the internal $this->charset variable to the charset defined in $GLOBALS["LANG"] (or the default as set in this class)
	 * Returns the meta-tag for the document header
	 *
	 * @return string <meta> tag with charset from $this->charset or $GLOBALS['LANG']->charSet
	 * @todo Define visibility
	 * @deprecated since TYPO3 6.0, remove in 6.2. The charset is utf-8 all the time for the backend now
	 */
	public function initCharset() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// Return meta tag:
		return '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->charset . '" />';
	}

	/**
	 * Returns generator meta tag
	 *
	 * @return string <meta> tag with name "generator
	 * @todo Define visibility
	 */
	public function generator() {
		$str = 'TYPO3 ' . TYPO3_branch . ', ' . TYPO3_URL_GENERAL . ', &#169; Kasper Sk&#229;rh&#248;j ' . TYPO3_copyright_year . ', extensions are copyright of their respective owners.';
		return '<meta name="generator" content="' . $str . '" />';
	}

	/**
	 * Returns X-UA-Compatible meta tag
	 *
	 * @param string $content Content of the compatible tag (default: IE-8)
	 * @return string <meta http-equiv="X-UA-Compatible" content="???" />
	 */
	public function xUaCompatible($content = 'IE=8') {
		return '<meta http-equiv="X-UA-Compatible" content="' . $content . '" />';
	}

	/*****************************************
	 *
	 * OTHER ELEMENTS
	 * Tables, buttons, formatting dimmed/red strings
	 *
	 ******************************************/
	/**
	 * Returns an image-tag with an 18x16 icon of the following types:
	 *
	 * $type:
	 * -1:	OK icon (Check-mark)
	 * 1:	Notice (Speach-bubble)
	 * 2:	Warning (Yellow triangle)
	 * 3:	Fatal error (Red stop sign)
	 *
	 * @param integer $type See description
	 * @param string $styleAttribValue Value for style attribute
	 * @return string HTML image tag (if applicable)
	 * @todo Define visibility
	 */
	public function icons($type, $styleAttribValue = '') {
		switch ($type) {
		case self::STATUS_ICON_ERROR:
			$icon = 'status-dialog-error';
			break;
		case self::STATUS_ICON_WARNING:
			$icon = 'status-dialog-warning';
			break;
		case self::STATUS_ICON_NOTIFICATION:
			$icon = 'status-dialog-notification';
			break;
		case self::STATUS_ICON_OK:
			$icon = 'status-dialog-ok';
			break;
		default:
			break;
		}
		if ($icon) {
			return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon);
		}
	}

	/**
	 * Returns an <input> button with the $onClick action and $label
	 *
	 * @param string $onClick The value of the onclick attribute of the input tag (submit type)
	 * @param string $label The label for the button (which will be htmlspecialchar'ed)
	 * @return string A <input> tag of the type "submit
	 * @todo Define visibility
	 */
	public function t3Button($onClick, $label) {
		$button = '<input type="submit" onclick="' . htmlspecialchars($onClick) . '; return false;" value="' . htmlspecialchars($label) . '" />';
		return $button;
	}

	/**
	 * Dimmed-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be gray/dimmed
	 *
	 * @param string $string Input string
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function dfw($string) {
		return '<span class="typo3-dimmed">' . $string . '</span>';
	}

	/**
	 * red-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be red
	 *
	 * @param string $string Input string
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function rfw($string) {
		return '<span class="typo3-red">' . $string . '</span>';
	}

	/**
	 * Returns string wrapped in CDATA "tags" for XML / XHTML (wrap content of <script> and <style> sections in those!)
	 *
	 * @param string $string Input string
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function wrapInCData($string) {
		$string = '/*<![CDATA[*/' . $string . '/*]]>*/';
		return $string;
	}

	/**
	 * Wraps the input string in script tags.
	 * Automatic re-identing of the JS code is done by using the first line as ident reference.
	 * This is nice for identing JS code with PHP code on the same level.
	 *
	 * @param string $string Input string
	 * @param boolean $linebreak Wrap script element in linebreaks? Default is TRUE.
	 * @return string Output string
	 * @todo Define visibility
	 */
	public function wrapScriptTags($string, $linebreak = TRUE) {
		if (trim($string)) {
			// <script wrapped in nl?
			$cr = $linebreak ? LF : '';
			// Remove nl from the beginning
			$string = preg_replace('/^\\n+/', '', $string);
			// Re-ident to one tab using the first line as reference
			$match = array();
			if (preg_match('/^(\\t+)/', $string, $match)) {
				$string = str_replace($match[1], TAB, $string);
			}
			$string = $cr . '<script type="text/javascript">
/*<![CDATA[*/
' . $string . '
/*]]>*/
</script>' . $cr;
		}
		return trim($string);
	}

	// These vars defines the layout for the table produced by the table() function.
	// You can override these values from outside if you like.
	/**
	 * @todo Define visibility
	 */
	public $tableLayout = array(
		'defRow' => array(
			'defCol' => array('<td valign="top">', '</td>')
		)
	);

	/**
	 * @todo Define visibility
	 */
	public $table_TR = '<tr>';

	/**
	 * @todo Define visibility
	 */
	public $table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" id="typo3-tmpltable">';

	/**
	 * Returns a table based on the input $data
	 *
	 * @param array $data Multidim array with first levels = rows, second levels = cells
	 * @param array $layout If set, then this provides an alternative layout array instead of $this->tableLayout
	 * @return string The HTML table.
	 * @internal
	 * @todo Define visibility
	 */
	public function table($data, $layout = NULL) {
		$result = '';
		if (is_array($data)) {
			$tableLayout = is_array($layout) ? $layout : $this->tableLayout;
			$rowCount = 0;
			foreach ($data as $tableRow) {
				if ($rowCount % 2) {
					$layout = is_array($tableLayout['defRowOdd']) ? $tableLayout['defRowOdd'] : $tableLayout['defRow'];
				} else {
					$layout = is_array($tableLayout['defRowEven']) ? $tableLayout['defRowEven'] : $tableLayout['defRow'];
				}
				$rowLayout = is_array($tableLayout[$rowCount]) ? $tableLayout[$rowCount] : $layout;
				$rowResult = '';
				if (is_array($tableRow)) {
					$cellCount = 0;
					foreach ($tableRow as $tableCell) {
						$cellWrap = is_array($layout[$cellCount]) ? $layout[$cellCount] : $layout['defCol'];
						$cellWrap = is_array($rowLayout['defCol']) ? $rowLayout['defCol'] : $cellWrap;
						$cellWrap = is_array($rowLayout[$cellCount]) ? $rowLayout[$cellCount] : $cellWrap;
						$rowResult .= $cellWrap[0] . $tableCell . $cellWrap[1];
						$cellCount++;
					}
				}
				$rowWrap = is_array($layout['tr']) ? $layout['tr'] : array($this->table_TR, '</tr>');
				$rowWrap = is_array($rowLayout['tr']) ? $rowLayout['tr'] : $rowWrap;
				$result .= $rowWrap[0] . $rowResult . $rowWrap[1];
				$rowCount++;
			}
			$tableWrap = is_array($tableLayout['table']) ? $tableLayout['table'] : array($this->table_TABLE, '</table>');
			$result = $tableWrap[0] . $result . $tableWrap[1];
		}
		return $result;
	}

	/**
	 * Constructs a table with content from the $arr1, $arr2 and $arr3.
	 * Used in eg. ext/belog/mod/index.php - refer to that for examples
	 *
	 * @param array $arr1 Menu elements on first level
	 * @param array $arr2 Secondary items
	 * @param array $arr3 Third-level items
	 * @return string HTML content, <table>...</table>
	 * @todo Define visibility
	 */
	public function menuTable($arr1, $arr2 = array(), $arr3 = array()) {
		$rows = max(array(count($arr1), count($arr2), count($arr3)));
		$menu = '
		<table border="0" cellpadding="0" cellspacing="0" id="typo3-tablemenu">';
		for ($a = 0; $a < $rows; $a++) {
			$menu .= '<tr>';
			$cls = array();
			$valign = 'middle';
			$cls[] = '<td valign="' . $valign . '">' . $arr1[$a][0] . '</td><td>' . $arr1[$a][1] . '</td>';
			if (count($arr2)) {
				$cls[] = '<td valign="' . $valign . '">' . $arr2[$a][0] . '</td><td>' . $arr2[$a][1] . '</td>';
				if (count($arr3)) {
					$cls[] = '<td valign="' . $valign . '">' . $arr3[$a][0] . '</td><td>' . $arr3[$a][1] . '</td>';
				}
			}
			$menu .= implode($cls, '<td>&nbsp;&nbsp;</td>');
			$menu .= '</tr>';
		}
		$menu .= '
		</table>
		';
		return $menu;
	}

	/**
	 * Returns a one-row/two-celled table with $content and $menu side by side.
	 * The table is a 100% width table and each cell is aligned left / right
	 *
	 * @param string $content Content cell content (left)
	 * @param string $menu Menu cell content (right)
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function funcMenu($content, $menu) {
		return '
			<table border="0" cellpadding="0" cellspacing="0" width="100%" id="typo3-funcmenu">
				<tr>
					<td valign="top" nowrap="nowrap">' . $content . '</td>
					<td valign="top" align="right" nowrap="nowrap">' . $menu . '</td>
				</tr>
			</table>';
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied
	 *
	 * @param string $lib: Library name. Call it with the full path like "contrib/prototype/prototype.js" to load it
	 * @return void
	 * @todo Define visibility
	 */
	public function loadJavascriptLib($lib) {
		$this->pageRenderer->addJsFile($this->backPath . $lib);
	}

	/**
	 * Includes the necessary Javascript function for the clickmenu (context sensitive menus) in the document
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function getContextMenuCode() {
		$this->pageRenderer->loadPrototype();
		$this->loadJavascriptLib('js/clickmenu.js');
		$this->JScodeArray['clickmenu'] = '
			Clickmenu.clickURL = "' . $this->backPath . 'alt_clickmenu.php";
			Clickmenu.ajax     = true;';
	}

	/**
	 * Includes the necessary javascript file (tree.js) for use on pages which have the
	 * drag and drop functionality (usually pages and folder display trees)
	 *
	 * @param string $table indicator of which table the drag and drop function should work on (pages or folders)
	 * @return void
	 * @todo Define visibility
	 */
	public function getDragDropCode($table) {
		$this->pageRenderer->loadPrototype();
		$this->loadJavascriptLib('js/common.js');
		$this->loadJavascriptLib('js/tree.js');
		// Setting prefs for drag & drop
		$this->JScodeArray['dragdrop'] = '
			DragDrop.changeURL = "' . $this->backPath . 'alt_clickmenu.php";
			DragDrop.backPath  = "' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5(('' . '|' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) . '";
			DragDrop.table     = "' . $table . '";
		';
	}

	/**
	 * This loads everything needed for the Context Sensitive Help (CSH)
	 *
	 * @return void
	 */
	protected function loadCshJavascript() {
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/contexthelp.js');
		$this->pageRenderer->addExtDirectCode();
	}

	/**
	 * Creates a tab menu from an array definition
	 *
	 * Returns a tab menu for a module
	 * Requires the JS function jumpToUrl() to be available
	 *
	 * @param mixed $mainParams is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
	 * @param string $elementName it the form elements name, probably something like "SET[...]
	 * @param string $currentValue is the value to be selected currently.
	 * @param array $menuItems is an array with the menu items for the selector box
	 * @param string $script is the script to send the &id to, if empty it's automatically found
	 * @param string $addparams is additional parameters to pass to the script.
	 * @return string HTML code for tab menu
	 * @todo Define visibility
	 */
	public function getTabMenu($mainParams, $elementName, $currentValue, $menuItems, $script = '', $addparams = '') {
		$content = '';
		if (is_array($menuItems)) {
			if (!is_array($mainParams)) {
				$mainParams = array('id' => $mainParams);
			}
			$mainParams = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $mainParams);
			if (!$script) {
				$script = basename(PATH_thisScript);
			}
			$menuDef = array();
			foreach ($menuItems as $value => $label) {
				$menuDef[$value]['isActive'] = !strcmp($currentValue, $value);
				$menuDef[$value]['label'] = \TYPO3\CMS\Core\Utility\GeneralUtility::deHSCentities(htmlspecialchars($label));
				$menuDef[$value]['url'] = $script . '?' . $mainParams . $addparams . '&' . $elementName . '=' . $value;
			}
			$content = $this->getTabMenuRaw($menuDef);
		}
		return $content;
	}

	/**
	 * Creates the HTML content for the tab menu
	 *
	 * @param array $menuItems Menu items for tabs
	 * @return string Table HTML
	 * @access private
	 * @todo Define visibility
	 */
	public function getTabMenuRaw($menuItems) {
		$content = '';
		if (is_array($menuItems)) {
			$options = '';
			$count = count($menuItems);
			$widthLeft = 1;
			$addToAct = 5;
			$widthRight = max(1, floor(30 - pow($count, 1.72)));
			$widthTabs = 100 - $widthRight - $widthLeft;
			$widthNo = floor(($widthTabs - $addToAct) / $count);
			$addToAct = max($addToAct, $widthTabs - $widthNo * $count);
			$widthAct = $widthNo + $addToAct;
			$widthRight = 100 - ($widthLeft + $count * $widthNo + $addToAct);
			foreach ($menuItems as $id => $def) {
				$isActive = $def['isActive'];
				$class = $isActive ? 'tabact' : 'tab';
				$width = $isActive ? $widthAct : $widthNo;
				// @rene: Here you should probably wrap $label and $url in htmlspecialchars() in order to make sure its XHTML compatible! I did it for $url already since that is VERY likely to break.
				$label = $def['label'];
				$url = htmlspecialchars($def['url']);
				$params = $def['addParams'];
				$options .= '<td width="' . $width . '%" class="' . $class . '"><a href="' . $url . '" ' . $params . '>' . $label . '</a></td>';
			}
			if ($options) {
				$content .= '
				<!-- Tab menu -->
				<table cellpadding="0" cellspacing="0" border="0" width="100%" id="typo3-tabmenu">
					<tr>
							<td width="' . $widthLeft . '%">&nbsp;</td>
							' . $options . '
						<td width="' . $widthRight . '%">&nbsp;</td>
					</tr>
				</table>
				<div class="hr" style="margin:0px"></div>';
			}
		}
		return $content;
	}

	/**
	 * Creates a DYNAMIC tab-menu where the tabs are switched between with DHTML.
	 * Should work in MSIE, Mozilla, Opera and Konqueror. On Konqueror I did find a serious problem: <textarea> fields loose their content when you switch tabs!
	 *
	 * @param array $menuItems Numeric array where each entry is an array in itself with associative keys: "label" contains the label for the TAB, "content" contains the HTML content that goes into the div-layer of the tabs content. "description" contains description text to be shown in the layer. "linkTitle" is short text for the title attribute of the tab-menu link (mouse-over text of tab). "stateIcon" indicates a standard status icon (see ->icon(), values: -1, 1, 2, 3). "icon" is an image tag placed before the text.
	 * @param string $identString Identification string. This should be unique for every instance of a dynamic menu!
	 * @param integer $toggle If "1", then enabling one tab does not hide the others - they simply toggles each sheet on/off. This makes most sense together with the $foldout option. If "-1" then it acts normally where only one tab can be active at a time BUT you can click a tab and it will close so you have no active tabs.
	 * @param boolean $foldout If set, the tabs are rendered as headers instead over each sheet. Effectively this means there is no tab menu, but rather a foldout/foldin menu. Make sure to set $toggle as well for this option.
	 * @param boolean $noWrap If set, tab table cells are not allowed to wrap their content
	 * @param boolean $fullWidth If set, the tabs will span the full width of their position
	 * @param integer $defaultTabIndex Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1 (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing) will result in no default tab open.
	 * @param integer $dividers2tabs If set to '1' empty tabs will be remove, If set to '2' empty tabs will be disabled
	 * @return string JavaScript section for the HTML header.
	 */
	public function getDynTabMenu($menuItems, $identString, $toggle = 0, $foldout = FALSE, $noWrap = TRUE, $fullWidth = FALSE, $defaultTabIndex = 1, $dividers2tabs = 2) {
		// Load the static code, if not already done with the function below
		$this->loadJavascriptLib('js/tabmenu.js');
		$content = '';
		if (is_array($menuItems)) {
			// Init:
			$options = array(array());
			$divs = array();
			$JSinit = array();
			$id = $this->getDynTabMenuId($identString);
			$noWrap = $noWrap ? ' nowrap="nowrap"' : '';
			// Traverse menu items
			$c = 0;
			$tabRows = 0;
			$titleLenCount = 0;
			foreach ($menuItems as $index => $def) {
				// Need to add one so checking for first index in JavaScript
				// is different than if it is not set at all.
				$index += 1;
				// Switch to next tab row if needed
				if (!$foldout && ($def['newline'] === TRUE && $titleLenCount > 0)) {
					$titleLenCount = 0;
					$tabRows++;
					$options[$tabRows] = array();
				}
				if ($toggle == 1) {
					$onclick = 'this.blur(); DTM_toggle("' . $id . '","' . $index . '"); return false;';
				} else {
					$onclick = 'this.blur(); DTM_activate("' . $id . '","' . $index . '", ' . ($toggle < 0 ? 1 : 0) . '); return false;';
				}
				$isEmpty = !(strcmp(trim($def['content']), '') || strcmp(trim($def['icon']), ''));
				// "Removes" empty tabs
				if ($isEmpty && $dividers2tabs == 1) {
					continue;
				}
				$mouseOverOut = ' onmouseover="DTM_mouseOver(this);" onmouseout="DTM_mouseOut(this);"';
				$requiredIcon = '<img name="' . $id . '-' . $index . '-REQ" src="' . $GLOBALS['BACK_PATH'] . 'gfx/clear.gif" class="t3-TCEforms-reqTabImg" alt="" />';
				if (!$foldout) {
					// Create TAB cell:
					$options[$tabRows][] = '
							<td class="' . ($isEmpty ? 'disabled' : 'tab') . '" id="' . $id . '-' . $index . '-MENU"' . $noWrap . $mouseOverOut . '>' . ($isEmpty ? '' : '<a href="#" onclick="' . htmlspecialchars($onclick) . '"' . ($def['linkTitle'] ? ' title="' . htmlspecialchars($def['linkTitle']) . '"' : '') . '>') . $def['icon'] . ($def['label'] ? htmlspecialchars($def['label']) : '&nbsp;') . $requiredIcon . $this->icons($def['stateIcon'], 'margin-left: 10px;') . ($isEmpty ? '' : '</a>') . '</td>';
					$titleLenCount += strlen($def['label']);
				} else {
					// Create DIV layer for content:
					$divs[] = '
						<div class="' . ($isEmpty ? 'disabled' : 'tab') . '" id="' . $id . '-' . $index . '-MENU"' . $mouseOverOut . '>' . ($isEmpty ? '' : '<a href="#" onclick="' . htmlspecialchars($onclick) . '"' . ($def['linkTitle'] ? ' title="' . htmlspecialchars($def['linkTitle']) . '"' : '') . '>') . $def['icon'] . ($def['label'] ? htmlspecialchars($def['label']) : '&nbsp;') . $requiredIcon . ($isEmpty ? '' : '</a>') . '</div>';
				}
				// Create DIV layer for content:
				$divs[] = '
						<div style="display: none;" id="' . $id . '-' . $index . '-DIV" class="c-tablayer">' . ($def['description'] ? '<p class="c-descr">' . nl2br(htmlspecialchars($def['description'])) . '</p>' : '') . $def['content'] . '</div>';
				// Create initialization string:
				$JSinit[] = '
						DTM_array["' . $id . '"][' . $c . '] = "' . $id . '-' . $index . '";
				';
				// If not empty and we have the toggle option on, check if the tab needs to be expanded
				if ($toggle == 1 && !$isEmpty) {
					$JSinit[] = '
						if (top.DTM_currentTabs["' . $id . '-' . $index . '"]) { DTM_toggle("' . $id . '","' . $index . '",1); }
					';
				}
				$c++;
			}
			// Render menu:
			if (count($options)) {
				// Tab menu is compiled:
				if (!$foldout) {
					$tabContent = '';
					for ($a = 0; $a <= $tabRows; $a++) {
						$tabContent .= '

					<!-- Tab menu -->
					<table cellpadding="0" cellspacing="0" border="0"' . ($fullWidth ? ' width="100%"' : '') . ' class="typo3-dyntabmenu">
						<tr>
								' . implode('', $options[$a]) . '
						</tr>
					</table>';
					}
					$content .= '<div class="typo3-dyntabmenu-tabs">' . $tabContent . '</div>';
				}
				// Div layers are added:
				$content .= '
				<!-- Div layers for tab menu: -->
				<div class="typo3-dyntabmenu-divs' . ($foldout ? '-foldout' : '') . '">
				' . implode('', $divs) . '</div>';
				// Java Script section added:
				$content .= '
				<!-- Initialization JavaScript for the menu -->
				<script type="text/javascript">
					DTM_array["' . $id . '"] = new Array();
					' . implode('', $JSinit) . '
					' . ($toggle <= 0 ? 'DTM_activate("' . $id . '", top.DTM_currentTabs["' . $id . '"]?top.DTM_currentTabs["' . $id . '"]:' . intval($defaultTabIndex) . ', 0);' : '') . '
				</script>

				';
			}
		}
		return $content;
	}

	/**
	 * Creates the id for dynTabMenus.
	 *
	 * @param string $identString Identification string. This should be unique for every instance of a dynamic menu!
	 * @return string The id with a short MD5 of $identString and prefixed "DTM-", like "DTM-2e8791854a
	 * @todo Define visibility
	 */
	public function getDynTabMenuId($identString) {
		$id = 'DTM-' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($identString);
		return $id;
	}

	/**
	 * Creates the version selector for the page id inputted.
	 * Requires the core version management extension, "version" to be loaded.
	 *
	 * @param integer $id Page id to create selector for.
	 * @param boolean $noAction If set, there will be no button for swapping page.
	 * @return string
	 */
	public function getVersionSelector($id, $noAction = FALSE) {
		if (
				\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version') &&
				!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')
		) {
			$versionGuiObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\View\\VersionView');
			return $versionGuiObj->getVersionSelector($id, $noAction);
		}
	}

	/**
	 * Function to load a HTML template file with markers.
	 * When calling from own extension, use  syntax getHtmlTemplate('EXT:extkey/template.html')
	 *
	 * @param string $filename tmpl name, usually in the typo3/template/ directory
	 * @return string HTML of template
	 * @todo Define visibility
	 */
	public function getHtmlTemplate($filename) {
		// setting the name of the original HTML template
		$this->moduleTemplateFilename = $filename;
		if ($GLOBALS['TBE_STYLES']['htmlTemplates'][$filename]) {
			$filename = $GLOBALS['TBE_STYLES']['htmlTemplates'][$filename];
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($filename, 'EXT:')) {
			$filename = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($filename, TRUE, TRUE);
		} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAbsPath($filename)) {
			$filename = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->backPath . $filename);
		} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($filename)) {
			$filename = '';
		}
		$htmlTemplate = '';
		if ($filename !== '') {
			$htmlTemplate = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($filename);
		}
		return $htmlTemplate;
	}

	/**
	 * Define the template for the module
	 *
	 * @param string $filename filename
	 * @return void
	 */
	public function setModuleTemplate($filename) {
		// Load Prototype lib for IE event
		$this->pageRenderer->loadPrototype();
		$this->loadJavascriptLib('js/iecompatibility.js');
		$this->moduleTemplate = $this->getHtmlTemplate($filename);
	}

	/**
	 * Put together the various elements for the module <body> using a static HTML
	 * template
	 *
	 * @param array $pageRecord Record of the current page, used for page path and info
	 * @param array $buttons HTML for all buttons
	 * @param array $markerArray HTML for all other markers
	 * @param array $subpartArray HTML for the subparts
	 * @return string Composite HTML
	 */
	public function moduleBody($pageRecord = array(), $buttons = array(), $markerArray = array(), $subpartArray = array()) {
		// Get the HTML template for the module
		$moduleBody = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->moduleTemplate, '###FULLDOC###');
		// Add CSS
		$this->inDocStylesArray[] = 'html { overflow: hidden; }';
		// Get the page path for the docheader
		$markerArray['PAGEPATH'] = $this->getPagePath($pageRecord);
		// Get the page info for the docheader
		$markerArray['PAGEINFO'] = $this->getPageInfo($pageRecord);
		// Get all the buttons for the docheader
		$docHeaderButtons = $this->getDocHeaderButtons($buttons);
		// Merge docheader buttons with the marker array
		$markerArray = array_merge($markerArray, $docHeaderButtons);
		// replacing subparts
		foreach ($subpartArray as $marker => $content) {
			$moduleBody = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($moduleBody, $marker, $content);
		}
		// adding flash messages
		if ($this->showFlashMessages) {
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$flashMessages = $defaultFlashMessageQueue->renderFlashMessages();
			if (!empty($flashMessages)) {
				$markerArray['FLASHMESSAGES'] = '<div id="typo3-messages">' . $flashMessages . '</div>';
				// If there is no dedicated marker for the messages present
				// then force them to appear before the content
				if (strpos($moduleBody, '###FLASHMESSAGES###') === FALSE) {
					$moduleBody = str_replace('###CONTENT###', '###FLASHMESSAGES######CONTENT###', $moduleBody);
				}
			}
		}
		// Hook for adding more markers/content to the page, like the version selector
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess'])) {
			$params = array(
				'moduleTemplateFilename' => &$this->moduleTemplateFilename,
				'moduleTemplate' => &$this->moduleTemplate,
				'moduleBody' => &$moduleBody,
				'markers' => &$markerArray,
				'parentObject' => &$this
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
			}
		}
		// Replacing all markers with the finished markers and return the HTML content
		return \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($moduleBody, $markerArray, '###|###');
	}

	/**
	 * Fill the button lists with the defined HTML
	 *
	 * @param array $buttons HTML for all buttons
	 * @return array Containing HTML for both buttonlists
	 */
	protected function getDocHeaderButtons($buttons) {
		$markers = array();
		// Fill buttons for left and right float
		$floats = array('left', 'right');
		foreach ($floats as $key) {
			// Get the template for each float
			$buttonTemplate = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->moduleTemplate, '###BUTTON_GROUPS_' . strtoupper($key) . '###');
			// Fill the button markers in this float
			$buttonTemplate = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($buttonTemplate, $buttons, '###|###', TRUE);
			// getting the wrap for each group
			$buttonWrap = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($this->moduleTemplate, '###BUTTON_GROUP_WRAP###');
			// looping through the groups (max 6) and remove the empty groups
			for ($groupNumber = 1; $groupNumber < 6; $groupNumber++) {
				$buttonMarker = '###BUTTON_GROUP' . $groupNumber . '###';
				$buttonGroup = \TYPO3\CMS\Core\Html\HtmlParser::getSubpart($buttonTemplate, $buttonMarker);
				if (trim($buttonGroup)) {
					if ($buttonWrap) {
						$buttonGroup = \TYPO3\CMS\Core\Html\HtmlParser::substituteMarker($buttonWrap, '###BUTTONS###', $buttonGroup);
					}
					$buttonTemplate = \TYPO3\CMS\Core\Html\HtmlParser::substituteSubpart($buttonTemplate, $buttonMarker, trim($buttonGroup));
				}
			}
			// Replace the marker with the template and remove all line breaks (for IE compat)
			$markers['BUTTONLIST_' . strtoupper($key)] = str_replace(LF, '', $buttonTemplate);
		}
		// Hook for manipulating docHeaderButtons
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'])) {
			$params = array(
				'buttons' => $buttons,
				'markers' => &$markers,
				'pObj' => &$this
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
			}
		}
		return $markers;
	}

	/**
	 * Generate the page path for docheader
	 *
	 * @param array $pageRecord Current page
	 * @return string Page path
	 */
	protected function getPagePath($pageRecord) {
		// Is this a real page
		if (is_array($pageRecord) && $pageRecord['uid']) {
			$title = substr($pageRecord['_thePathFull'], 0, -1);
			// Remove current page title
			$pos = strrpos($title, '/');
			if ($pos !== FALSE) {
				$title = substr($title, 0, $pos) . '/';
			}
		} else {
			$title = '';
		}
		// Setting the path of the page
		$pagePath = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';
		// crop the title to title limit (or 50, if not defined)
		$cropLength = empty($GLOBALS['BE_USER']->uc['titleLen']) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
		$croppedTitle = \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($title, -$cropLength);
		if ($croppedTitle !== $title) {
			$pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
		} else {
			$pagePath .= htmlspecialchars($title);
		}
		$pagePath .= '</span>';
		return $pagePath;
	}

	/**
	 * Setting page icon with clickmenu + uid for docheader
	 *
	 * @param array $pageRecord Current page
	 * @return string Page info
	 */
	protected function getPageInfo($pageRecord) {
		// Add icon with clickmenu, etc:
		// If there IS a real page
		if (is_array($pageRecord) && $pageRecord['uid']) {
			$alttext = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($pageRecord, 'pages');
			$iconImg = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord('pages', $pageRecord, array('title' => $alttext));
			// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
			$uid = $pageRecord['uid'];
			$title = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $pageRecord);
		} else {
			// On root-level of page tree
			// Make Icon
			$iconImg = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])));
			if ($GLOBALS['BE_USER']->user['admin']) {
				$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', 0);
			} else {
				$theIcon = $iconImg;
			}
			$uid = '0';
			$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}
		// Setting icon with clickmenu + uid
		$pageInfo = $theIcon . '<strong>' . htmlspecialchars($title) . '&nbsp;[' . $uid . ']</strong>';
		return $pageInfo;
	}

	/**
	 * Makes a collapseable section. See reports module for an example
	 *
	 * @param string $title
	 * @param string $html
	 * @param string $id
	 * @param string $saveStatePointer
	 * @return string
	 */
	public function collapseableSection($title, $html, $id, $saveStatePointer = '') {
		$hasSave = $saveStatePointer ? TRUE : FALSE;
		$collapsedStyle = ($collapsedClass = '');
		if ($hasSave) {
			/** @var $settings \TYPO3\CMS\Backend\User\ExtDirect\BackendUserSettingsDataProvider */
			$settings = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\User\\ExtDirect\\BackendUserSettingsDataProvider');
			$value = $settings->get($saveStatePointer . '.' . $id);
			if ($value) {
				$collapsedStyle = ' style="display: none"';
				$collapsedClass = ' collapsed';
			} else {
				$collapsedStyle = '';
				$collapsedClass = ' expanded';
			}
		}
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->addExtOnReadyCode('
			Ext.select("h2.section-header").each(function(element){
				element.on("click", function(event, tag) {
					var state = 0,
						el = Ext.fly(tag),
						div = el.next("div"),
						saveKey = el.getAttribute("rel");
					if (el.hasClass("collapsed")) {
						el.removeClass("collapsed").addClass("expanded");
						div.slideIn("t", {
							easing: "easeIn",
							duration: .5
						});
					} else {
						el.removeClass("expanded").addClass("collapsed");
						div.slideOut("t", {
							easing: "easeOut",
							duration: .5,
							remove: false,
							useDisplay: true
						});
						state = 1;
					}
					if (saveKey) {
						try {
							top.TYPO3.BackendUserSettings.ExtDirect.set(saveKey + "." + tag.id, state, function(response) {});
						} catch(e) {}
					}
				});
			});
		');
		return '
		  <h2 id="' . $id . '" class="section-header' . $collapsedClass . '" rel="' . $saveStatePointer . '"> ' . $title . '</h2>
		  <div' . $collapsedStyle . '>' . $html . '</div>
		';
	}

	/**
	* Retrieves configured favicon for backend (with fallback)
	*
	* @return string
	*/
	protected function getBackendFavicon() {
		return \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/favicon.ico', '', 1);
	}
}


?>