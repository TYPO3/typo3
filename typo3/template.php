<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class with layout/output function for TYPO3 Backend Scripts
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML-trans compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *  145: function fw($str)
 *
 *
 *  169: class template
 *  224:     function template()
 *
 *              SECTION: EVALUATION FUNCTIONS
 *  298:     function wrapClickMenuOnIcon($str,$table,$uid='',$listFr=1,$addParams='',$enDisItems='', $returnOnClick=FALSE)
 *  315:     function viewPageIcon($id,$backPath,$addParams='hspace="3"')
 *  341:     function issueCommand($params,$rUrl='')
 *  356:     function isCMlayers()
 *  366:     function thisBlur()
 *  376:     function helpStyle()
 *  393:     function getHeader($table,$row,$path,$noViewPageIcon=0,$tWrap=array('',''))
 *  419:     function getFileheader($title,$path,$iconfile)
 *  434:     function makeShortcutIcon($gvList,$setList,$modName,$motherModName="")
 *  467:     function makeShortcutUrl($gvList,$setList)
 *  488:     function formWidth($size=48,$textarea=0,$styleOverride='')
 *  513:     function formWidthText($size=48,$styleOverride='',$wrap='')
 *  530:     function redirectUrls($thisLocation='')
 *  554:     function formatTime($tstamp,$type)
 *  571:     function parseTime()
 *
 *              SECTION: PAGE BUILDING FUNCTIONS.
 *  604:     function startPage($title)
 *  686:     function endPage()
 *  720:     function header($text)
 *  741:     function section($label,$text,$nostrtoupper=FALSE,$sH=FALSE,$type=0,$allowHTMLinHeader=FALSE)
 *  765:     function divider($dist)
 *  781:     function spacer($dist)
 *  800:     function sectionHeader($label,$sH=FALSE,$addAttrib='')
 *  817:     function sectionBegin()
 *  838:     function sectionEnd()
 *  858:     function middle()
 *  867:     function endPageJS()
 *  884:     function docBodyTagBegin()
 *  894:     function docStyle()
 *  936:     function insertStylesAndJS($content)
 *  956:     function initCharset()
 *  968:     function generator()
 *
 *              SECTION: OTHER ELEMENTS
 * 1001:     function icons($type, $styleAttribValue='')
 * 1030:     function t3Button($onClick,$label)
 * 1041:     function dfw($string)
 * 1051:     function rfw($string)
 * 1061:     function wrapInCData($string)
 * 1078:     function wrapScriptTags($string, $linebreak=TRUE)
 * 1117:     function table($arr, $layout='')
 * 1159:     function menuTable($arr1,$arr2=array(), $arr3=array())
 * 1192:     function funcMenu($content,$menu)
 * 1210:     function clearCacheMenu($id,$addSaveOptions=0)
 * 1246:     function getContextMenuCode()
 * 1251:     function showClickmenu(table, uid, listFr, enDisItems, backPath, addParams)
 * 1280:     function showClickmenu_noajax(url)
 * 1287:     function showClickmenu_ajax(t3ajax)
 * 1472:     function getDragDropCode($table)
 * 1483:     function cancelDragEvent(event)
 * 1496:     function mouseMoveEvent (event)
 * 1509:     function dragElement(id,elementID)
 * 1528:     function dropElement(id)
 * 1577:     function getTabMenu($mainParams,$elementName,$currentValue,$menuItems,$script='',$addparams='')
 * 1607:     function getTabMenuRaw($menuItems)
 * 1676:     function getDynTabMenu($menuItems,$identString,$toggle=0,$foldout=FALSE,$newRowCharLimit=50,$noWrap=1,$fullWidth=FALSE,$defaultTabIndex=1)
 * 1801:     function getDynTabMenuJScode()
 * 1892:     function getVersionSelector($id,$noAction=FALSE)
 *
 *
 * 2060: class bigDoc extends template
 *
 *
 * 2069: class noDoc extends template
 *
 *
 * 2078: class smallDoc extends template
 *
 *
 * 2087: class mediumDoc extends template
 *
 * TOTAL FUNCTIONS: 57
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



if (!defined('TYPO3_MODE'))	die("Can't include this file directly.");


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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class template {

		// Vars you typically might want to/should set from outside after making instance of this class:
	var $backPath = '';				// 'backPath' pointing back to the PATH_typo3
	var $form='';					// This can be set to the HTML-code for a formtag. Useful when you need a form to span the whole page; Inserted exactly after the body-tag.
	var $JScodeLibArray = array();		// Similar to $JScode (see below) but used as an associative array to prevent double inclusion of JS code. This is used to include certain external Javascript libraries before the inline JS code. <script>-Tags are not wrapped around automatically
	var $JScode='';					// Additional header code (eg. a JavaScript section) could be accommulated in this var. It will be directly outputted in the header.
	var $extJScode = '';				// Additional header code for ExtJS. It will be included in document header and inserted in a Ext.onReady(function()
	var $JScodeArray = array();		// Similar to $JScode but for use as array with associative keys to prevent double inclusion of JS code. a <script> tag is automatically wrapped around.
	var $postCode='';				// Additional 'page-end' code could be accommulated in this var. It will be outputted at the end of page before </body> and some other internal page-end code.
	var $docType = '';				// Doc-type used in the header. Default is xhtml_trans. You can also set it to 'html_3', 'xhtml_strict' or 'xhtml_frames'.
	var $moduleTemplate = '';		// HTML template with markers for module
	protected $moduleTemplateFilename = '';	// the base file (not overlaid by TBE_STYLES) for the current module, useful for hooks when finding out which modules is rendered currently

		// Other vars you can change, but less frequently used:
	var $scriptID='';				// Script ID.
	var $bodyTagId='';				// Id which can be set for the body tag. Default value is based on script ID
	var $bodyTagAdditions='';		// You can add additional attributes to the body-tag through this variable.
	var $inDocStyles='';			// Additional CSS styles which will be added to the <style> section in the header
	var $inDocStylesArray=array();		// Like $inDocStyles but for use as array with associative keys to prevent double inclusion of css code
	var $form_rowsToStylewidth = 9.58;	// Multiplication factor for formWidth() input size (default is 48* this value).
	var $form_largeComp = 1.33;		// Compensation for large documents (used in class.t3lib_tceforms.php)
	var $endJS=1;					// If set, then a JavaScript section will be outputted in the bottom of page which will try and update the top.busy session expiry object.

		// TYPO3 Colorscheme.
		// If you want to change this, please do so through a skin using the global var $TBE_STYLES
	var $bgColor = '#F7F3EF';		// Light background color
	var $bgColor2 = '#9BA1A8';		// Steel-blue
	var $bgColor3 = '#F6F2E6';		// dok.color
	var $bgColor4 = '#D9D5C9';		// light tablerow background, brownish
	var $bgColor5 = '#ABBBB4';		// light tablerow background, greenish
	var $bgColor6 = '#E7DBA8';		// light tablerow background, yellowish, for section headers. Light.
	var $hoverColor = '#254D7B';
	var $styleSheetFile = '';	// Filename of stylesheet (relative to PATH_typo3)
	var $styleSheetFile2 = '';		// Filename of stylesheet #2 - linked to right after the $this->styleSheetFile script (relative to PATH_typo3)
	var $styleSheetFile_post = '';	// Filename of a post-stylesheet - included right after all inline styles.
	var $backGroundImage = '';		// Background image of page (relative to PATH_typo3)
	var $inDocStyles_TBEstyle = '';	// Inline css styling set from TBE_STYLES array

	/**
	 * Whether to use the X-UA-Compatible meta tag
	 * @var boolean
	 */
	protected $useCompatibilityTag = TRUE;

		// Skinning
		// stylesheets from core
	protected $stylesheetsCore = array(
		'structure' => 'stylesheets/structure/',
		'visual' => 'stylesheets/visual/',
		'generatedSprites' => '../typo3temp/sprites/',
	);

		// include these CSS directories from skins by default
	protected $stylesheetsSkins = array(
		'structure' => 'stylesheets/structure/',
		'visual' => 'stylesheets/visual/',
	);

	/**
	 * JavaScript files loaded for every page in the Backend
	 * @var array
	 */
	protected $jsFiles = array(
		'modernizr' => 'contrib/modernizr/modernizr.min.js',
	);

		// DEV:
	var $parseTimeFlag = 0;			// Will output the parsetime of the scripts in milliseconds (for admin-users). Set this to false when releasing TYPO3. Only for dev.

		// INTERNAL
	var $charset = 'iso-8859-1';	// Default charset. see function initCharset()

	var $sectionFlag=0;				// Internal: Indicates if a <div>-output section is open
	var $divClass = '';				// (Default) Class for wrapping <DIV>-tag of page. Is set in class extensions.

	var $pageHeaderBlock = '';
	var $endOfPageJsBlock = '';

	var $hasDocheader = true;

	/**
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;
	protected $pageHeaderFooterTemplateFile = '';	// alternative template file

	protected $extDirectStateProvider = FALSE;

	/**
	 * Whether flashmessages should be rendered or not
	 *
	 * @var $showFlashMessages
	 */
	public $showFlashMessages = TRUE;

	/**
	 * Constructor
	 * Imports relevant parts from global $TBE_STYLES (colorscheme)
	 *
	 * @return	void
	 */
	function template()	{
		global $TBE_STYLES;

			// Initializes the page rendering object:
		$this->getPageRenderer();

			// Setting default scriptID:
		if (($temp_M = (string) t3lib_div::_GET('M')) && $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M]) {
			$this->scriptID = preg_replace('/^.*\/(sysext|ext)\//', 'ext/', $GLOBALS['TBE_MODULES']['_PATHS'][$temp_M] . 'index.php');
		} else {
			$this->scriptID = preg_replace('/^.*\/(sysext|ext)\//', 'ext/', substr(PATH_thisScript, strlen(PATH_site)));
		}
		if (TYPO3_mainDir!='typo3/' && substr($this->scriptID,0,strlen(TYPO3_mainDir)) == TYPO3_mainDir)	{
			$this->scriptID = 'typo3/'.substr($this->scriptID,strlen(TYPO3_mainDir));	// This fixes if TYPO3_mainDir has been changed so the script ids are STILL "typo3/..."
		}

		$this->bodyTagId = preg_replace('/[^A-Za-z0-9-]/','-',$this->scriptID);

			// Individual configuration per script? If so, make a recursive merge of the arrays:
		if (is_array($TBE_STYLES['scriptIDindex'][$this->scriptID]))	{
			$ovr = $TBE_STYLES['scriptIDindex'][$this->scriptID];		// Make copy
			$TBE_STYLES = t3lib_div::array_merge_recursive_overrule($TBE_STYLES,$ovr);		// merge styles.
			unset($TBE_STYLES['scriptIDindex'][$this->scriptID]);	// Have to unset - otherwise the second instantiation will do it again!
		}

			// Color scheme:
		if ($TBE_STYLES['mainColors']['bgColor'])	$this->bgColor=$TBE_STYLES['mainColors']['bgColor'];
		if ($TBE_STYLES['mainColors']['bgColor1'])	$this->bgColor1=$TBE_STYLES['mainColors']['bgColor1'];
		if ($TBE_STYLES['mainColors']['bgColor2'])	$this->bgColor2=$TBE_STYLES['mainColors']['bgColor2'];
		if ($TBE_STYLES['mainColors']['bgColor3'])	$this->bgColor3=$TBE_STYLES['mainColors']['bgColor3'];
		if ($TBE_STYLES['mainColors']['bgColor4'])	$this->bgColor4=$TBE_STYLES['mainColors']['bgColor4'];
		if ($TBE_STYLES['mainColors']['bgColor5'])	$this->bgColor5=$TBE_STYLES['mainColors']['bgColor5'];
		if ($TBE_STYLES['mainColors']['bgColor6'])	$this->bgColor6=$TBE_STYLES['mainColors']['bgColor6'];
		if ($TBE_STYLES['mainColors']['hoverColor'])	$this->hoverColor=$TBE_STYLES['mainColors']['hoverColor'];

			// Main Stylesheets:
		if ($TBE_STYLES['stylesheet'])	$this->styleSheetFile = $TBE_STYLES['stylesheet'];
		if ($TBE_STYLES['stylesheet2'])	$this->styleSheetFile2 = $TBE_STYLES['stylesheet2'];
		if ($TBE_STYLES['styleSheetFile_post'])	$this->styleSheetFile_post = $TBE_STYLES['styleSheetFile_post'];
		if ($TBE_STYLES['inDocStyles_TBEstyle'])	$this->inDocStyles_TBEstyle = $TBE_STYLES['inDocStyles_TBEstyle'];

			// include all stylesheets
		foreach ($this->getSkinStylesheetDirectories() as $stylesheetDirectory) {
			$this->addStylesheetDirectory($stylesheetDirectory);
		}

			// Background image
		if ($TBE_STYLES['background'])	$this->backGroundImage = $TBE_STYLES['background'];
	}


	/**
	 * Gets instance of PageRenderer
	 *
	 * @return	t3lib_PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = t3lib_div::makeInstance('t3lib_PageRenderer');
			$this->pageRenderer->setTemplateFile(
				TYPO3_mainDir . 'templates/template_page_backend.html'
			);
			$this->pageRenderer->setLanguage($GLOBALS['LANG']->lang);
			$this->pageRenderer->enableConcatenateFiles();
			$this->pageRenderer->enableCompressCss();
			$this->pageRenderer->enableCompressJavascript();

				// add all JavaScript files defined in $this->jsFiles to the PageRenderer
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
	 * @param	string		String to be wrapped in link, typ. image tag.
	 * @param	string		Table name/File path. If the icon is for a database record, enter the tablename from $GLOBALS['TCA']. If a file then enter the absolute filepath
	 * @param	integer		If icon is for database record this is the UID for the record from $table
	 * @param	boolean		Tells the top frame script that the link is coming from a "list" frame which means a frame from within the backend content frame.
	 * @param	string		Additional GET parameters for the link to alt_clickmenu.php
	 * @param	string		Enable / Disable click menu items. Example: "+new,view" will display ONLY these two items (and any spacers in between), "new,view" will display all BUT these two items.
	 * @param	boolean		If set, will return only the onclick JavaScript, not the whole link.
	 * @return	string		The link-wrapped input string.
	 */
	function wrapClickMenuOnIcon($str,$table,$uid='',$listFr=1,$addParams='',$enDisItems='', $returnOnClick=FALSE)	{
		$backPath = rawurlencode($this->backPath).'|'.t3lib_div::shortMD5($this->backPath.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		$onClick = 'showClickmenu("'.$table.'","'.$uid.'","'.$listFr.'","'.str_replace('+','%2B',$enDisItems).'","'.str_replace('&','&amp;',addcslashes($backPath,'"')).'","'.str_replace('&','&amp;',addcslashes($addParams,'"')).'");return false;';
		return $returnOnClick ? $onClick : '<a href="#" onclick="'.htmlspecialchars($onClick).'"'.($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler'] ? ' oncontextmenu="'.htmlspecialchars($onClick).'"' : '').'>'.$str.'</a>';
	}

	/**
	 * Makes link to page $id in frontend (view page)
	 * Returns an magnifier-glass icon which links to the frontend index.php document for viewing the page with id $id
	 * $id must be a page-uid
	 * If the BE_USER has access to Web>List then a link to that module is shown as well (with return-url)
	 *
	 * @param	integer		The page id
	 * @param	string		The current "BACK_PATH" (the back relative to the typo3/ directory)
	 * @param	string		Additional parameters for the image tag(s)
	 * @return	string		HTML string with linked icon(s)
	 */
	function viewPageIcon($id,$backPath,$addParams='hspace="3"')	{

			// If access to Web>List for user, then link to that module.
		$str = t3lib_BEfunc::getListViewLink(
			array(
				'id' => $id,
				'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI'),
			),
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList')
		);

			// Make link to view page
		$str.= '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($id,$backPath,t3lib_BEfunc::BEgetRootLine($id))).'">'.
				'<img'.t3lib_iconWorks::skinImg($backPath,'gfx/zoom.gif','width="12" height="12"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage',1).'"'.($addParams?' '.trim($addParams):"").' hspace="3" alt="" />'.
				'</a>';
		return $str;
	}

	/**
	 * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
	 * See description of the API elsewhere.
	 *
	 * @param	string		$params is a set of GET params to send to tce_db.php. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World"
	 * @param	string		Redirect URL if any other that t3lib_div::getIndpEnv('REQUEST_URI') is wished
	 * @return	string		URL to tce_db.php + parameters (backpath is taken from $this->backPath)
	 * @see t3lib_BEfunc::editOnClick()
	 */
	function issueCommand($params,$rUrl='')	{
		$rUrl = $rUrl ? $rUrl : t3lib_div::getIndpEnv('REQUEST_URI');
		$commandUrl = $this->backPath.'tce_db.php?' .
				$params .
				'&redirect=' . ($rUrl==-1 ? "'+T3_THIS_LOCATION+'" : rawurlencode($rUrl)) .
				'&vC='.rawurlencode($GLOBALS['BE_USER']->veriCode()) .
				t3lib_BEfunc::getUrlToken('tceAction') .
				'&prErr=1&uPT=1';

		return $commandUrl;
	}

	/**
	 * Returns true if click-menu layers can be displayed for the current user/browser
	 * Use this to test if click-menus (context sensitive menus) can and should be displayed in the backend.
	 *
	 * @return	boolean
	 */
	function isCMlayers()	{
		return !$GLOBALS['BE_USER']->uc['disableCMlayers'] && $GLOBALS['CLIENT']['FORMSTYLE'] && !($GLOBALS['CLIENT']['SYSTEM']=='mac' && $GLOBALS['CLIENT']['BROWSER']=='Opera');
	}

	/**
	 * Returns 'this.blur();' if the client supports CSS styles
	 * Use this in links to remove the underlining after being clicked
	 *
	 * @return	string
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function thisBlur()	{
		t3lib_div::logDeprecatedFunction();
		return ($GLOBALS['CLIENT']['FORMSTYLE']?'this.blur();':'');
	}

	/**
	 * Returns ' style='cursor:help;'' if the client supports CSS styles
	 * Use for <a>-links to help texts
	 *
	 * @return	string
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function helpStyle()	{
		t3lib_div::logDeprecatedFunction();
		return $GLOBALS['CLIENT']['FORMSTYLE'] ? ' style="cursor:help;"':'';
	}

	/**
	 * Makes the header (icon+title) for a page (or other record). Used in most modules under Web>*
	 * $table and $row must be a tablename/record from that table
	 * $path will be shown as alt-text for the icon.
	 * The title will be truncated to 45 chars.
	 *
	 * @param	string		Table name
	 * @param	array		Record row
	 * @param	string		Alt text
	 * @param	boolean		Set $noViewPageIcon true if you don't want a magnifier-icon for viewing the page in the frontend
	 * @param	array		$tWrap is an array with indexes 0 and 1 each representing HTML-tags (start/end) which will wrap the title
	 * @return	string		HTML content
	 */
	function getHeader($table,$row,$path,$noViewPageIcon=0,$tWrap=array('',''))	{
		if (is_array($row) && $row['uid'])	{
			$iconImgTag=t3lib_iconWorks::getSpriteIconForRecord($table, $row , array('title' => htmlspecialchars($path)));
			$title = strip_tags($row[$GLOBALS['TCA'][$table]['ctrl']['label']]);
			$viewPage = $noViewPageIcon ? '' : $this->viewPageIcon($row['uid'],$this->backPath,'');
			if ($table=='pages')	$path.=' - '.t3lib_BEfunc::titleAttribForPages($row,'',0);
		} else {
			$iconImgTag = t3lib_iconWorks::getSpriteIcon('apps-pagetree-page-domain', array('title' => htmlspecialchars($path)));
			$title=$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}

		return '<span class="typo3-moduleHeader">'.$this->wrapClickMenuOnIcon($iconImgTag,$table,$row['uid']).
				$viewPage.
				$tWrap[0].htmlspecialchars(t3lib_div::fixed_lgd_cs($title,45)).$tWrap[1].'</span>';
	}

	/**
	 * Like ->getHeader() but for files in the File>* main module/submodules
	 * Returns the file-icon with the path of the file set in the alt/title attribute. Shows the file-name after the icon.
	 *
	 * @param	string		Title string, expected to be the filepath
	 * @param	string		Alt text
	 * @param	string		The icon file (relative to TYPO3 dir)
	 * @return	string		HTML content
	 */
	function getFileheader($title,$path,$iconfile)	{
		$fileInfo = t3lib_div::split_fileref($title);
		$title = htmlspecialchars(t3lib_div::fixed_lgd_cs($fileInfo['path'],-35)).'<strong>'.htmlspecialchars($fileInfo['file']).'</strong>';
		return '<span class="typo3-moduleHeader"><img'.t3lib_iconWorks::skinImg($this->backPath,$iconfile,'width="18" height="16"').' title="'.htmlspecialchars($path).'" alt="" />'.$title.'</span>';
	}

	/**
	 * Returns a linked shortcut-icon which will call the shortcut frame and set a shortcut there back to the calling page/module
	 *
	 * @param	string		Is the list of GET variables to store (if any)
	 * @param	string		Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
	 * @param	string		Module name string
	 * @param	string		Is used to enter the "parent module name" if the module is a submodule under eg. Web>* or File>*. You can also set this value to "1" in which case the currentLoadedModule is sent to the shortcut script (so - not a fixed value!) - that is used in file_edit.php and wizard_rte.php scripts where those scripts are really running as a part of another module.
	 * @return	string		HTML content
	 */
	function makeShortcutIcon($gvList,$setList,$modName,$motherModName="")	{
		$backPath=$this->backPath;
		$storeUrl=$this->makeShortcutUrl($gvList,$setList);
		$pathInfo = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));

			// Add the module identifier automatically if typo3/mod.php is used:
		if (preg_match('/typo3\/mod\.php$/', $pathInfo['path']) && isset($GLOBALS['TBE_MODULES']['_PATHS'][$modName])) {
			$storeUrl = '&M='.$modName.$storeUrl;
		}

		if (!strcmp($motherModName,'1'))	{
			$mMN="&motherModName='+top.currentModuleLoaded+'";
		} elseif ($motherModName)	{
			$mMN='&motherModName='.rawurlencode($motherModName);
		} else $mMN='';

		$onClick = 'top.ShortcutManager.createShortcut('
			.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.makeBookmark')).', '
			.'\''.$backPath.'\', '
			.'\''.rawurlencode($modName).'\', '
			.'\''.rawurlencode($pathInfo['path']."?".$storeUrl).$mMN.'\''
		.');return false;';

		$sIcon = '<a href="#" onclick="' . htmlspecialchars($onClick).'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.makeBookmark', TRUE) . '">'
			. t3lib_iconworks::getSpriteIcon('actions-system-shortcut-new') . '</a>';
		return $sIcon;
	}

	/**
	 * MAKE url for storing
	 * Internal func
	 *
	 * @param	string		Is the list of GET variables to store (if any)
	 * @param	string		Is the list of SET[] variables to store (if any) - SET[] variables a stored in $GLOBALS["SOBE"]->MOD_SETTINGS for backend modules
	 * @return	string
	 * @access private
	 * @see makeShortcutIcon()
	 */
	function makeShortcutUrl($gvList,$setList)	{
		$GET = t3lib_div::_GET();
		$storeArray = array_merge(
			t3lib_div::compileSelectedGetVarsFromArray($gvList,$GET),
			array('SET'=>t3lib_div::compileSelectedGetVarsFromArray($setList, (array)$GLOBALS['SOBE']->MOD_SETTINGS))
		);
		$storeUrl = t3lib_div::implodeArrayForUrl('',$storeArray);
		return $storeUrl;
	}

	/**
	 * Returns <input> attributes to set the width of an text-type input field.
	 * For client browsers with no CSS support the cols/size attribute is returned.
	 * For CSS compliant browsers (recommended) a ' style="width: ...px;"' is returned.
	 *
	 * @param	integer		A relative number which multiplied with approx. 10 will lead to the width in pixels
	 * @param	boolean		A flag you can set for textareas - DEPRECATED, use ->formWidthText() for textareas!!!
	 * @param	string		A string which will be returned as attribute-value for style="" instead of the calculated width (if CSS is enabled)
	 * @return	string		Tag attributes for an <input> tag (regarding width)
	 * @see formWidthText()
	 */
	function formWidth($size=48,$textarea=0,$styleOverride='') {
		$wAttrib = $textarea?'cols':'size';
		if (!$GLOBALS['CLIENT']['FORMSTYLE'])	{	// If not setting the width by style-attribute
			$size = $size;
			$retVal = ' '.$wAttrib.'="'.$size.'"';
		} else {	// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$pixels = ceil($size*$this->form_rowsToStylewidth);
			$retVal = $styleOverride ? ' style="'.$styleOverride.'"' : ' style="width:'.$pixels.'px;"';
		}
		return $retVal;
	}

	/**
	 * This function is dedicated to textareas, which has the wrapping on/off option to observe.
	 * EXAMPLE:
	 * 		<textarea rows="10" wrap="off" '.$GLOBALS["TBE_TEMPLATE"]->formWidthText(48,"","off").'>
	 *   or
	 * 		<textarea rows="10" wrap="virtual" '.$GLOBALS["TBE_TEMPLATE"]->formWidthText(48,"","virtual").'>
	 *
	 * @param	integer		A relative number which multiplied with approx. 10 will lead to the width in pixels
	 * @param	string		A string which will be returned as attribute-value for style="" instead of the calculated width (if CSS is enabled)
	 * @param	string		Pass on the wrap-attribute value you use in your <textarea>! This will be used to make sure that some browsers will detect wrapping alright.
	 * @return	string		Tag attributes for an <input> tag (regarding width)
	 * @see formWidth()
	 */
	function formWidthText($size=48,$styleOverride='',$wrap='') {
		$wTags = $this->formWidth($size,1,$styleOverride);
			// Netscape 6+/Mozilla seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap))!='off' && $GLOBALS['CLIENT']['BROWSER']=='net' && $GLOBALS['CLIENT']['VERSION']>=5)	{
			$wTags.=' cols="'.$size.'"';
		}
		return $wTags;
	}

	/**
	 * Returns JavaScript variables setting the returnUrl and thisScript location for use by JavaScript on the page.
	 * Used in fx. db_list.php (Web>List)
	 *
	 * @param	string		URL to "this location" / current script
	 * @return	string
	 * @see typo3/db_list.php
	 */
	function redirectUrls($thisLocation='')	{
		$thisLocation = $thisLocation?$thisLocation:t3lib_div::linkThisScript(
		array(
			'CB'=>'',
			'SET'=>'',
			'cmd' => '',
			'popViewId'=>''
		));

		$out ="
	var T3_RETURN_URL = '".str_replace('%20','',rawurlencode(t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'))))."';
	var T3_THIS_LOCATION = '".str_replace('%20','',rawurlencode($thisLocation))."';
		";
		return $out;
	}

	/**
	 * Returns a formatted string of $tstamp
	 * Uses $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] and $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] to format date and time
	 *
	 * @param	integer		UNIX timestamp, seconds since 1970
	 * @param	integer		How much data to show: $type = 1: hhmm, $type = 10:	ddmmmyy
	 * @return	string		Formatted timestamp
	 */
	function formatTime($tstamp,$type)	{
		$dateStr = '';
		switch($type)	{
			case 1: $dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],$tstamp);
			break;
			case 10: $dateStr = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],$tstamp);
			break;
		}
		return $dateStr;
	}

	/**
	 * Returns script parsetime IF ->parseTimeFlag is set and user is "admin"
	 * Automatically outputted in page end
	 *
	 * @return	string
	 */
	function parseTime()	{
		if ($this->parseTimeFlag && $GLOBALS['BE_USER']->isAdmin()) {
			return '<p>(ParseTime: '.(t3lib_div::milliseconds()-$GLOBALS['PARSETIME_START']).' ms</p>
					<p>REQUEST_URI-length: '.strlen(t3lib_div::getIndpEnv('REQUEST_URI')).')</p>';
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
	 * @param	string		HTML Page title for the header
	 * @param	boolean		flag for including CSH
	 * @return	string		Returns the whole header section of a HTML-document based on settings in internal variables (like styles, javascript code, charset, generator and docType)
	 * @see endPage()
	 */
	function startPage($title, $includeCsh = TRUE) {
			// hook	pre start page
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook']))	{
			$preStartPageHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'];
			if (is_array($preStartPageHook)) {
				$hookParameters = array(
					'title' => &$title,
				);
				foreach ($preStartPageHook as $hookFunction)	{
					t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}

		$this->pageRenderer->backPath = $this->backPath;

			// alternative template for Header and Footer
		if ($this->pageHeaderFooterTemplateFile) {
			$file =  t3lib_div::getFileAbsFileName($this->pageHeaderFooterTemplateFile, TRUE);
			if ($file) {
				$this->pageRenderer->setTemplateFile($file);
			}
		}
			// For debugging: If this outputs "QuirksMode"/"BackCompat" (IE) the browser runs in quirks-mode. Otherwise the value is "CSS1Compat"
#		$this->JScodeArray[]='alert(document.compatMode);';

			// Send HTTP header for selected charset. Added by Robert Lemke 23.10.2003
		$this->initCharset();
		header ('Content-Type:text/html;charset='.$this->charset);

			// Standard HTML tag
		$htmlTag = '<html xmlns="http://www.w3.org/1999/xhtml">';

		switch($this->docType)	{
			case 'html_3':
				$headerStart = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">';
				$htmlTag = '<html>';
				// disable rendering of XHTML tags
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
				// The fallthrough is intended as HTML5, as this is the default for the BE since TYPO3 4.5
			case 'html5':
			default:
				$headerStart = '<!DOCTYPE html>' . LF;
				$htmlTag = '<html>';
				// disable rendering of XHTML tags
				$this->getPageRenderer()->setRenderXhtml(FALSE);
				break;
		}

		$this->pageRenderer->setHtmlTag($htmlTag);

		// This loads the tabulator-in-textarea feature. It automatically modifies
		// every textarea which is found.
		if (!$GLOBALS['BE_USER']->uc['disableTabInTextarea']) {
			$this->loadJavascriptLib('tab.js');
		}

			// include the JS for the Context Sensitive Help
		if ($includeCsh) {
			$this->loadCshJavascript();
		}

			// Get the browser info
		$browserInfo = t3lib_utility_Client::getBrowserInfo(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));

			// Set the XML prologue
		$xmlPrologue = '<?xml version="1.0" encoding="' . $this->charset . '"?>';

			// Set the XML stylesheet
		$xmlStylesheet = '<?xml-stylesheet href="#internalStyle" type="text/css"?>';

			// Add the XML prologue for XHTML doctypes
		if (strpos($this->doctype, 'xhtml') !== FALSE) {
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
		$this->pageRenderer->setHeadTag('<head>' . LF. '<!-- TYPO3 Script ID: '.htmlspecialchars($this->scriptID).' -->');
		$this->pageRenderer->setCharSet($this->charset);
		$this->pageRenderer->addMetaTag($this->generator());
		if ($this->useCompatibilityTag) {
			$this->pageRenderer->addMetaTag($this->xUaCompatible());
		}
		$this->pageRenderer->setTitle($title);

		// add docstyles
		$this->docStyle();

	   if ($this->extDirectStateProvider) {
			$this->pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ExtDirect.StateProvider.js');
		}

			// add jsCode for overriding the console with a debug panel connection
		$this->pageRenderer->addJsInlineCode(
			'consoleOverrideWithDebugPanel',
			'if (typeof top.Ext === "object") {
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
		');

		$this->pageRenderer->addHeaderData($this->JScode);

		foreach ($this->JScodeArray as $name => $code) {
			$this->pageRenderer->addJsInlineCode($name, $code);
		}

		if (count($this->JScodeLibArray)) {
			foreach($this->JScodeLibArray as $library) {
				$this->pageRenderer->addHeaderData($library);
			}
		}

		if ($this->extJScode) {
			$this->pageRenderer->addExtOnReadyCode($this->extJScode);
		}

			// hook for additional headerData
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'])) {
			$preHeaderRenderHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preHeaderRenderHook'];
			if (is_array($preHeaderRenderHook)) {
				$hookParameters = array(
					'pageRenderer' => &$this->pageRenderer,
				);
				foreach ($preHeaderRenderHook as $hookFunction) {
					t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}

			// Construct page header.
		$str = $this->pageRenderer->render(t3lib_PageRenderer::PART_HEADER);

		$this->JScodeLibArray = array();
		$this->JScode = $this->extJScode = '';
		$this->JScodeArray = array();

		$this->endOfPageJsBlock = $this->pageRenderer->render(t3lib_PageRenderer::PART_FOOTER);

		if ($this->docType=='xhtml_frames')	{
			return $str;
		} else
$str.=$this->docBodyTagBegin().
($this->divClass?'

<!-- Wrapping DIV-section for whole page BEGIN -->
<div class="' . $this->divClass . '">
' : '' ) . trim($this->form);
		return $str;
	}

	/**
	 * Returns page end; This includes finishing form, div, body and html tags.
	 *
	 * @return	string		The HTML end of a page
	 * @see startPage()
	 */
	function endPage()	{
		$str = $this->sectionEnd().
				$this->postCode.
				$this->endPageJS().
				$this->wrapScriptTags(t3lib_BEfunc::getUpdateSignalCode()).
				$this->parseTime().
				($this->form?'
</form>':'');
			// if something is in buffer like debug, put it to end of page
		if (ob_get_contents()) {
			$str .= ob_get_clean();
			header('Content-Encoding: None');
		}

		if ($this->docType !== 'xhtml_frames') {

			$str .= ($this->divClass?'

<!-- Wrapping DIV-section for whole page END -->
</div>':'') . $this->endOfPageJsBlock ;
			t3lib_formprotection_Factory::get()->persistTokens();
		}


			// Logging: Can't find better place to put it:
		if (TYPO3_DLOG)	t3lib_div::devLog('END of BACKEND session', 'template', 0, array('_FLUSH' => true));

		return $str;
	}

	/**
	 * Shortcut for render the complete page of a module
	 *
	 * @param  $title  page title
	 * @param  $content  page content
	 * @param bool $includeCsh  flag for including csh code
	 * @return string complete page
	 */
	public function render($title, $content, $includeCsh = TRUE)  {
		$pageContent = $this->startPage($title, $includeCsh);
		$pageContent .= $content;
		$pageContent .= $this->endPage();

		return $this->insertStylesAndJS($pageContent);
	}

	/**
	 * Returns the header-bar in the top of most backend modules
	 * Closes section if open.
	 *
	 * @param	string		The text string for the header
	 * @return	string		HTML content
	 */
	function header($text)	{
		$str='

	<!-- MAIN Header in page top -->
	<h2>'.htmlspecialchars($text).'</h2>
';
		return $this->sectionEnd().$str;
	}

	/**
	 * Begins an output section and sets header and content
	 *
	 * @param	string		The header
	 * @param	string		The HTML-content
	 * @param	boolean		A flag that will prevent the header from being converted to uppercase
	 * @param	boolean		Defines the type of header (if set, "<h3>" rather than the default "h4")
	 * @param	integer		The number of an icon to show with the header (see the icon-function). -1,1,2,3
	 * @param	boolean		If set, HTML tags are allowed in $label (otherwise this value is by default htmlspecialchars()'ed)
	 * @return	string		HTML content
	 * @see icons(), sectionHeader()
	 */
	function section($label,$text,$nostrtoupper=FALSE,$sH=FALSE,$type=0,$allowHTMLinHeader=FALSE)	{
		$str='';

			// Setting header
		if ($label)	{
			if (!$allowHTMLinHeader)	$label = htmlspecialchars($label);
			$str.=$this->sectionHeader($this->icons($type).$label, $sH, $nostrtoupper ? '' : ' class="uppercase"');
		}
			// Setting content
		$str.='

	<!-- Section content -->
'.$text;

		return $this->sectionBegin().$str;
	}

	/**
	 * Inserts a divider image
	 * Ends a section (if open) before inserting the image
	 *
	 * @param	integer		The margin-top/-bottom of the <hr> ruler.
	 * @return	string		HTML content
	 */
	function divider($dist)	{
		$dist = intval($dist);
		$str='

	<!-- DIVIDER -->
	<hr style="margin-top: '.$dist.'px; margin-bottom: '.$dist.'px;" />
';
		return $this->sectionEnd().$str;
	}

	/**
	 * Returns a blank <div>-section with a height
	 *
	 * @param	integer		Padding-top for the div-section (should be margin-top but konqueror (3.1) doesn't like it :-(
	 * @return	string		HTML content
	 */
	function spacer($dist)	{
		if ($dist>0)	{
			return '

	<!-- Spacer element -->
	<div style="padding-top: '.intval($dist).'px;"></div>
';
		}
	}

	/**
	 * Make a section header.
	 * Begins a section if not already open.
	 *
	 * @param	string		The label between the <h3> or <h4> tags. (Allows HTML)
	 * @param	boolean		If set, <h3> is used, otherwise <h4>
	 * @param	string		Additional attributes to h-tag, eg. ' class=""'
	 * @return	string		HTML content
	 */
	function sectionHeader($label, $sH=FALSE, $addAttrib='') {
		$tag = ($sH ? 'h3' : 'h4');
		if ($addAttrib && substr($addAttrib, 0, 1) !== ' ') {
			$addAttrib = ' ' . $addAttrib;
		}
		$str='

	<!-- Section header -->
	<' . $tag . $addAttrib . '>' . $label . '</' . $tag . '>
';
		return $this->sectionBegin() . $str;
	}

	/**
	 * Begins an output section.
	 * Returns the <div>-begin tag AND sets the ->sectionFlag true (if the ->sectionFlag is not already set!)
	 * You can call this function even if a section is already begun since the function will only return something if the sectionFlag is not already set!
	 *
	 * @return	string		HTML content
	 */
	function sectionBegin()	{
		if (!$this->sectionFlag)	{
			$this->sectionFlag=1;
			$str='

	<!-- ***********************
	      Begin output section.
	     *********************** -->
	<div>
';
			return $str;
		} else return '';
	}

	/**
	 * Ends and output section
	 * Returns the </div>-end tag AND clears the ->sectionFlag (but does so only IF the sectionFlag is set - that is a section is 'open')
	 * See sectionBegin() also.
	 *
	 * @return	string		HTML content
	 */
	function sectionEnd()	{
		if ($this->sectionFlag)	{
			$this->sectionFlag=0;
			return '
	</div>
	<!-- *********************
	      End output section.
	     ********************* -->
';
		} else return '';
	}

	/**
	 * If a form-tag is defined in ->form then and end-tag for that <form> element is outputted
	 * Further a JavaScript section is outputted which will update the top.busy session-expiry object (unless $this->endJS is set to false)
	 *
	 * @return	string		HTML content (<script> tag section)
	 */
	function endPageJS()	{
		return ($this->endJS?'
	<script type="text/javascript">
		  /*<![CDATA[*/
		if (top.busy && top.busy.loginRefreshed) {
			top.busy.loginRefreshed();
		}
		 /*]]>*/
	</script>':'');
	}

	/**
	 * Creates the bodyTag.
	 * You can add to the bodyTag by $this->bodyTagAdditions
	 *
	 * @return	string		HTML body tag
	 */
	function docBodyTagBegin()	{
		$bodyContent = 'body onclick="if (top.menuReset) top.menuReset();" '.trim($this->bodyTagAdditions.($this->bodyTagId ? ' id="'.$this->bodyTagId.'"' : ''));
		return '<'.trim($bodyContent).'>';
	}

	/**
	 * Outputting document style
	 *
	 * @return	string		HTML style section/link tags
	 */
	function docStyle()	{

			// Request background image:
		if ($this->backGroundImage)	{
			$this->inDocStylesArray[]=' BODY { background-image: url('.$this->backPath.$this->backGroundImage.'); }';
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
	 * @param	string		$key: some key identifying the style sheet
	 * @param	string		$href: uri to the style sheet file
	 * @param	string		$title: value for the title attribute of the link element
	 * @return	string		$relation: value for the rel attribute of the link element
	 * @return	void
	 */
	function addStyleSheet($key, $href, $title='', $relation='stylesheet') {
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
	 * @param	string		directory to add
	 * @return	void
	 */
	function addStyleSheetDirectory($path) {
			// calculation needed, when TYPO3 source is used via a symlink
			// absolute path to the stylesheets
		$filePath = dirname(t3lib_div::getIndpEnv('SCRIPT_FILENAME')) . '/' . $GLOBALS['BACK_PATH'] . $path;
			// clean the path
		$resolvedPath = t3lib_div::resolveBackPath($filePath);
			// read all files in directory and sort them alphabetically
		$files = t3lib_div::getFilesInDir($resolvedPath, 'css', FALSE, 1);
		foreach ($files as $file) {
			$this->pageRenderer->addCssFile($GLOBALS['BACK_PATH'] . $path . $file, 'stylesheet', 'all');
		}
	}

	/**
	 * Insert post rendering document style into already rendered content
	 * This is needed for extobjbase
	 *
	 * @param	string		style-content to insert.
	 * @return	string		content with inserted styles
	 */
	function insertStylesAndJS($content)	{
			// insert accumulated CSS
		$this->inDocStylesArray[] = $this->inDocStyles;
		$styles = LF.implode(LF, $this->inDocStylesArray);
		$content = str_replace('/*###POSTCSSMARKER###*/',$styles,$content);

			// insert accumulated JS
		$jscode = $this->JScode.LF.$this->wrapScriptTags(implode(LF, $this->JScodeArray));
		$content = str_replace('<!--###POSTJSMARKER###-->',$jscode,$content);

		return $content;
	}

	/**
	 * Returns an array of all stylesheet directories belonging to core and skins
	 *
	 * @return	array	Stylesheet directories
	 */
	public function getSkinStylesheetDirectories() {
		$stylesheetDirectories = array();

			// add default core stylesheets
		foreach ($this->stylesheetsCore as $stylesheetDir) {
			$stylesheetDirectories[] = $stylesheetDir;
		}

			// Stylesheets from skins
			// merge default css directories ($this->stylesheetsSkin) with additional ones and include them
		if (is_array($GLOBALS['TBE_STYLES']['skins'])) {
				// loop over all registered skins
			foreach ($GLOBALS['TBE_STYLES']['skins'] as $skinExtKey => $skin) {
				$skinStylesheetDirs = $this->stylesheetsSkins;

					// skins can add custom stylesheetDirectories using
					// $TBE_STYLES['skins'][$_EXTKEY]['stylesheetDirectories']
				if (is_array($skin['stylesheetDirectories'])) {
					$skinStylesheetDirs = array_merge($skinStylesheetDirs, $skin['stylesheetDirectories']);
				}

					// add all registered directories
				foreach ($skinStylesheetDirs as $stylesheetDir) {
						// for EXT:myskin/stylesheets/ syntax
					if (substr($stylesheetDir, 0, 4) === 'EXT:') {
						list($extKey, $path) = explode('/', substr($stylesheetDir, 4), 2);
						if (strcmp($extKey, '') && t3lib_extMgm::isLoaded($extKey) && strcmp($path, '')) {
							$stylesheetDirectories[] = t3lib_extMgm::extRelPath($extKey) . $path;
						}
					} else {
						// for relative paths
						$stylesheetDirectories[] = t3lib_extMgm::extRelPath($skinExtKey) . $stylesheetDir;
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
	 * @return	string		<meta> tag with charset from $this->charset or $GLOBALS['LANG']->charSet
	 */
	function initCharset()	{
			// Set charset to the charset provided by the current backend users language selection:
		$this->charset = $GLOBALS['LANG']->charSet ? $GLOBALS['LANG']->charSet : $this->charset;
			// Return meta tag:
		return '<meta http-equiv="Content-Type" content="text/html; charset='.$this->charset.'" />';
	}

	/**
	 * Returns generator meta tag
	 *
	 * @return	string		<meta> tag with name "generator"
	 */
	function generator()	{
		$str = 'TYPO3 '.TYPO3_branch.', ' . TYPO3_URL_GENERAL . ', &#169; Kasper Sk&#229;rh&#248;j 1998-2009, extensions are copyright of their respective owners.';
		return '<meta name="generator" content="'.$str .'" />';
	}

	/**
	 * Returns X-UA-Compatible meta tag
	 *
	 * @param	string		$content Content of the compatible tag (default: IE-8)
	 * @return	string		<meta http-equiv="X-UA-Compatible" content="???" />
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
	 * @param	integer		See description
	 * @param	string		Value for style attribute
	 * @return	string		HTML image tag (if applicable)
	 */
	function icons($type, $styleAttribValue='')	{
		switch($type)	{
			case '3':
				$icon = 'status-dialog-error';
			break;
			case '2':
				$icon = 'status-dialog-warning';
			break;
			case '1':
				$icon = 'status-dialog-notification';
			break;
			case '-1':
				$icon = 'status-dialog-ok';
			break;
			default:
			break;
		}
		if ($icon)	{
			return t3lib_iconWorks::getSpriteIcon($icon);
		}
	}

	/**
	 * Returns an <input> button with the $onClick action and $label
	 *
	 * @param	string		The value of the onclick attribute of the input tag (submit type)
	 * @param	string		The label for the button (which will be htmlspecialchar'ed)
	 * @return	string		A <input> tag of the type "submit"
	 */
	function t3Button($onClick,$label)	{
		$button = '<input type="submit" onclick="'.htmlspecialchars($onClick).'; return false;" value="'.htmlspecialchars($label).'" />';
		return $button;
	}

	/**
	 * dimmed-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be gray/dimmed
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function dfw($string)	{
		return '<span class="typo3-dimmed">'.$string.'</span>';
	}

	/**
	 * red-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be red
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function rfw($string)	{
		return '<span class="typo3-red">'.$string.'</span>';
	}

	/**
	 * Returns string wrapped in CDATA "tags" for XML / XHTML (wrap content of <script> and <style> sections in those!)
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function wrapInCData($string)	{
		$string = '/*<![CDATA[*/'.
			$string.
			'/*]]>*/';

		return $string;
	}

	/**
	 * Wraps the input string in script tags.
	 * Automatic re-identing of the JS code is done by using the first line as ident reference.
	 * This is nice for identing JS code with PHP code on the same level.
	 *
	 * @param	string		Input string
	 * @param	boolean		Wrap script element in linebreaks? Default is TRUE.
	 * @return	string		Output string
	 */
	function wrapScriptTags($string, $linebreak=TRUE)	{
		if(trim($string)) {
				// <script wrapped in nl?
			$cr = $linebreak? LF : '';

				// remove nl from the beginning
			$string = preg_replace ('/^\n+/', '', $string);
				// re-ident to one tab using the first line as reference
			$match = array();
			if(preg_match('/^(\t+)/',$string,$match)) {
				$string = str_replace($match[1],TAB, $string);
			}
			$string = $cr.'<script type="text/javascript">
/*<![CDATA[*/
'.$string.'
/*]]>*/
</script>'.$cr;
		}
		return trim($string);
	}

		// These vars defines the layout for the table produced by the table() function.
		// You can override these values from outside if you like.
	var $tableLayout = array(
		'defRow' => array(
			'defCol' => array('<td valign="top">','</td>')
		)
	);
	var $table_TR = '<tr>';
	var $table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist" id="typo3-tmpltable">';

	/**
	 * Returns a table based on the input $data
	 *
	 * @param	array		Multidim array with first levels = rows, second levels = cells
	 * @param	array		If set, then this provides an alternative layout array instead of $this->tableLayout
	 * @return	string		The HTML table.
	 * @internal
	 */
	function table($data, $layout = '') {
		$result = '';
		if (is_array($data)) {
			$tableLayout = (is_array($layout) ? $layout : $this->tableLayout);

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
						$cellWrap = (is_array($layout[$cellCount])    ? $layout[$cellCount]    : $layout['defCol']);
						$cellWrap = (is_array($rowLayout['defCol'])   ? $rowLayout['defCol']   : $cellWrap);
						$cellWrap = (is_array($rowLayout[$cellCount]) ? $rowLayout[$cellCount] : $cellWrap);
						$rowResult .= $cellWrap[0] . $tableCell . $cellWrap[1];
						$cellCount++;
					}
				}
				$rowWrap = (is_array($layout['tr'])    ? $layout['tr']    : array($this->table_TR, '</tr>'));
				$rowWrap = (is_array($rowLayout['tr']) ? $rowLayout['tr'] : $rowWrap);
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
	 * @param	array		Menu elements on first level
	 * @param	array		Secondary items
	 * @param	array		Third-level items
	 * @return	string		HTML content, <table>...</table>
	 */
	function menuTable($arr1,$arr2=array(), $arr3=array())	{
		$rows = max(array(count($arr1),count($arr2),count($arr3)));

		$menu='
		<table border="0" cellpadding="0" cellspacing="0" id="typo3-tablemenu">';
		for($a=0;$a<$rows;$a++)	{
			$menu.='<tr>';
			$cls=array();
			$valign='middle';
			$cls[]='<td valign="'.$valign.'">'.$arr1[$a][0].'</td><td>'.$arr1[$a][1].'</td>';
			if (count($arr2))	{
				$cls[]='<td valign="'.$valign.'">'.$arr2[$a][0].'</td><td>'.$arr2[$a][1].'</td>';
				if (count($arr3))	{
					$cls[]='<td valign="'.$valign.'">'.$arr3[$a][0].'</td><td>'.$arr3[$a][1].'</td>';
				}
			}
			$menu.=implode($cls,'<td>&nbsp;&nbsp;</td>');
			$menu.='</tr>';
		}
		$menu.='
		</table>
		';
		return $menu;
	}

	/**
	 * Returns a one-row/two-celled table with $content and $menu side by side.
	 * The table is a 100% width table and each cell is aligned left / right
	 *
	 * @param	string		Content cell content (left)
	 * @param	string		Menu cell content (right)
	 * @return	string		HTML output
	 */
	function funcMenu($content,$menu)	{
		return '
			<table border="0" cellpadding="0" cellspacing="0" width="100%" id="typo3-funcmenu">
				<tr>
					<td valign="top" nowrap="nowrap">'.$content.'</td>
					<td valign="top" align="right" nowrap="nowrap">'.$menu.'</td>
				</tr>
			</table>';
	}

	/**
	 * Creates a selector box with clear-cache items.
	 * Rather specialized functions - at least don't use it with $addSaveOptions unless you know what you do...
	 *
	 * @param	integer		The page uid of the "current page" - the one that will be cleared as "clear cache for this page".
	 * @param	boolean		If $addSaveOptions is set, then also the array of save-options for TCE_FORMS will appear.
	 * @return	string		<select> tag with content - a selector box for clearing the cache
	 */
	function clearCacheMenu($id,$addSaveOptions=0)	{
		$opt=array();
		if ($addSaveOptions)	{
			$opt[]='<option value="">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.menu',1).'</option>';
			$opt[]='<option value="TBE_EDITOR.checkAndDoSubmit(1);">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'</option>';
			$opt[]='<option value="document.editform.closeDoc.value=-2; TBE_EDITOR.checkAndDoSubmit(1);">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'</option>';
			if ($GLOBALS['BE_USER']->uc['allSaveFunctions']) {
				$opt[] = '<option value="document.editform.closeDoc.value=-3; TBE_EDITOR.checkAndDoSubmit(1);">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseAllDocs', 1) . '</option>';
			}
			$opt[]='<option value="document.editform.closeDoc.value=2; document.editform.submit();">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'</option>';
			$opt[]='<option value="document.editform.closeDoc.value=3; document.editform.submit();">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeAllDocs',1).'</option>';
			$opt[]='<option value=""></option>';
		}
		$opt[]='<option value="">[ '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_clearCache',1).' ]</option>';
		if ($id) $opt[]='<option value="'.$id.'">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_thisPage',1).'</option>';
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.pages')) {
			$opt[] = '<option value="pages">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_pages', 1) . '</option>';
		}
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.all')) {
			$opt[] = '<option value="all">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_all', 1) . '</option>';
		}

		$onChange = 'if (!this.options[this.selectedIndex].value) {
				this.selectedIndex=0;
			} else if (this.options[this.selectedIndex].value.indexOf(\';\')!=-1) {
				eval(this.options[this.selectedIndex].value);
			} else {
				window.location.href=\'' . $this->backPath .
						'tce_db.php?vC=' . $GLOBALS['BE_USER']->veriCode() .
						t3lib_BEfunc::getUrlToken('tceAction') .
						'&redirect=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) .
						'&cacheCmd=\'+this.options[this.selectedIndex].value;
			}';
		$af_content = '<select name="cacheCmd" onchange="'.htmlspecialchars($onChange).'">'.implode('',$opt).'</select>';

		if (count($opt)>1)	{
			return $af_content;
		}
	}


 	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied
	 *
	 * @param	string		$lib: Library name. Call it with the full path
	 * 				like "contrib/prototype/prototype.js" to load it
	 * @return	void
	 */
	function loadJavascriptLib($lib)	{
		$this->pageRenderer->addJsFile($this->backPath . $lib);
	}



	/**
	 * Includes the necessary Javascript function for the clickmenu (context sensitive menus) in the document
	 *
	 * @return	array	Deprecated: Includes the code already in the doc, so the return array is always empty.
	 *			Please just call this function without expecting a return value for future calls
	 */
	function getContextMenuCode()   {
	       $this->pageRenderer->loadPrototype();
	       $this->loadJavascriptLib('js/clickmenu.js');

	       $this->JScodeArray['clickmenu'] = '
			       Clickmenu.clickURL = "'.$this->backPath.'alt_clickmenu.php";
			       Clickmenu.ajax     = '.($this->isCMLayers() ? 'true' : 'false' ).';';

		       // return array deprecated since 4.2
	       return array('','','');
	}

	/**
	 * Includes the necessary javascript file (tree.js) for use on pages which have the
	 * drag and drop functionality (usually pages and folder display trees)
	 *
	 * @param	string		indicator of which table the drag and drop function should work on (pages or folders)
	 * @return	array		If values are present: [0] = A <script> section for the HTML page header, [1] = onmousemove/onload handler for HTML tag or alike, [2] = One empty <div> layer for the follow-mouse drag element
	 */
	function getDragDropCode($table)	{
		$this->pageRenderer->loadPrototype();
		$this->loadJavascriptLib('js/common.js');
		$this->loadJavascriptLib('js/tree.js');

			// setting prefs for drag & drop
		$this->JScodeArray['dragdrop'] = '
			DragDrop.changeURL = "'.$this->backPath.'alt_clickmenu.php";
			DragDrop.backPath  = "'.t3lib_div::shortMD5(''.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']).'";
			DragDrop.table     = "'.$table.'";
		';

		       // return array deprecated since 4.2
	       return array('','','');
	}

	 /**
	 * This loads everything needed for the Context Sensitive Help (CSH)
	 *
	 * @return void
	 */
	protected function loadCshJavascript() {
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->addJsFile($this->backPath .'../t3lib/js/extjs/contexthelp.js');
		$this->pageRenderer->addExtDirectCode();
	}

	/**
	 * Creates a tab menu from an array definition
	 *
	 * Returns a tab menu for a module
	 * Requires the JS function jumpToUrl() to be available
	 *
	 * @param	mixed		$id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	array		$menuItems is an array with the menu items for the selector box
	 * @param	string		$script is the script to send the &id to, if empty it's automatically found
	 * @param	string		$addParams is additional parameters to pass to the script.
	 * @return	string		HTML code for tab menu
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	function getTabMenu($mainParams,$elementName,$currentValue,$menuItems,$script='',$addparams='')	{
		$content='';

		if (is_array($menuItems))	{
			if (!is_array($mainParams)) {
				$mainParams = array('id' => $mainParams);
			}
			$mainParams = t3lib_div::implodeArrayForUrl('',$mainParams);

			if (!$script) {$script=basename(PATH_thisScript);}

			$menuDef = array();
			foreach($menuItems as $value => $label) {
				$menuDef[$value]['isActive'] = !strcmp($currentValue,$value);
				$menuDef[$value]['label'] = t3lib_div::deHSCentities(htmlspecialchars($label));
				$menuDef[$value]['url'] = $script . '?' . $mainParams . $addparams . '&' . $elementName . '=' . $value;
			}
			$content = $this->getTabMenuRaw($menuDef);

		}
		return $content;
	}

	/**
	 * Creates the HTML content for the tab menu
	 *
	 * @param	array		Menu items for tabs
	 * @return	string		Table HTML
	 * @access private
	 */
	function getTabMenuRaw($menuItems)	{
		$content='';

		if (is_array($menuItems))	{
			$options='';

			$count = count($menuItems);
			$widthLeft = 1;
			$addToAct = 5;

			$widthRight = max (1,floor(30-pow($count,1.72)));
			$widthTabs = 100 - $widthRight - $widthLeft;
			$widthNo = floor(($widthTabs - $addToAct)/$count);
			$addToAct = max ($addToAct,$widthTabs-($widthNo*$count));
			$widthAct = $widthNo + $addToAct;
			$widthRight = 100 - ($widthLeft + ($count*$widthNo) + $addToAct);

			foreach($menuItems as $id => $def) {
				$isActive = $def['isActive'];
				$class = $isActive ? 'tabact' : 'tab';
				$width = $isActive ? $widthAct : $widthNo;

					// @rene: Here you should probably wrap $label and $url in htmlspecialchars() in order to make sure its XHTML compatible! I did it for $url already since that is VERY likely to break.
				$label = $def['label'];
				$url = htmlspecialchars($def['url']);
				$params = $def['addParams'];

				$options .= '<td width="' . $width . '%" class="' . $class . '"><a href="' . $url . '" ' . $params . '>' . $label . '</a></td>';
			}

			if ($options)	{
				$content .= '
				<!-- Tab menu -->
				<table cellpadding="0" cellspacing="0" border="0" width="100%" id="typo3-tabmenu">
					<tr>
							<td width="'.$widthLeft.'%">&nbsp;</td>
							'.$options.'
						<td width="'.$widthRight.'%">&nbsp;</td>
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
	 * @param	array		Numeric array where each entry is an array in itself with associative keys: "label" contains the label for the TAB, "content" contains the HTML content that goes into the div-layer of the tabs content. "description" contains description text to be shown in the layer. "linkTitle" is short text for the title attribute of the tab-menu link (mouse-over text of tab). "stateIcon" indicates a standard status icon (see ->icon(), values: -1, 1, 2, 3). "icon" is an image tag placed before the text.
	 * @param	string		Identification string. This should be unique for every instance of a dynamic menu!
	 * @param	integer		If "1", then enabling one tab does not hide the others - they simply toggles each sheet on/off. This makes most sense together with the $foldout option. If "-1" then it acts normally where only one tab can be active at a time BUT you can click a tab and it will close so you have no active tabs.
	 * @param	boolean		If set, the tabs are rendered as headers instead over each sheet. Effectively this means there is no tab menu, but rather a foldout/foldin menu. Make sure to set $toggle as well for this option.
	 * @param	integer		Character limit for a new row, 0 by default, because this parameter is deprecated since TYPO3 4.5
	 * @param	boolean		If set, tab table cells are not allowed to wrap their content
	 * @param	boolean		If set, the tabs will span the full width of their position
	 * @param	integer		Default tab to open (for toggle <=0). Value corresponds to integer-array index + 1 (index zero is "1", index "1" is 2 etc.). A value of zero (or something non-existing) will result in no default tab open.
	 * @param	integer		If set to '1' empty tabs will be remove, If set to '2' empty tabs will be disabled
	 * @return	string		JavaScript section for the HTML header.
	 */
	public function getDynTabMenu($menuItems, $identString, $toggle = 0, $foldout = FALSE, $newRowCharLimit = 0, $noWrap = 1, $fullWidth = FALSE, $defaultTabIndex = 1, $dividers2tabs = 2) {
			// load the static code, if not already done with the function below
		$this->loadJavascriptLib('js/tabmenu.js');

		$content = '';

		if (is_array($menuItems))	{

				// Init:
			$options = array(array());
			$divs = array();
			$JSinit = array();
			$id = $this->getDynTabMenuId($identString);
			$noWrap = $noWrap ? ' nowrap="nowrap"' : '';

				// Traverse menu items
			$c=0;
			$tabRows=0;
			$titleLenCount = 0;
			foreach($menuItems as $index => $def) {
					// Need to add one so checking for first index in JavaScript
					// is different than if it is not set at all.
				$index += 1;

					// Switch to next tab row if needed
				if (!$foldout && (($newRowCharLimit > 0 && $titleLenCount > $newRowCharLimit) | ($def['newline'] === TRUE && $titleLenCount > 0))) {
					$titleLenCount=0;
					$tabRows++;
					$options[$tabRows] = array();
				}

				if ($toggle==1)	{
					$onclick = 'this.blur(); DTM_toggle("'.$id.'","'.$index.'"); return false;';
				} else {
					$onclick = 'this.blur(); DTM_activate("'.$id.'","'.$index.'", '.($toggle<0?1:0).'); return false;';
				}

				$isEmpty = !(strcmp(trim($def['content']),'') || strcmp(trim($def['icon']),''));

					// "Removes" empty tabs
				if ($isEmpty && $dividers2tabs == 1) {
					continue;
				}

				$mouseOverOut = ' onmouseover="DTM_mouseOver(this);" onmouseout="DTM_mouseOut(this);"';
				$requiredIcon = '<img name="' . $id . '-' . $index . '-REQ" src="' . $GLOBALS['BACK_PATH'] . 'gfx/clear.gif" class="t3-TCEforms-reqTabImg" alt="" />';

				if (!$foldout)	{
						// Create TAB cell:
					$options[$tabRows][] = '
							<td class="'.($isEmpty ? 'disabled' : 'tab').'" id="'.$id.'-'.$index.'-MENU"'.$noWrap.$mouseOverOut.'>'.
							($isEmpty ? '' : '<a href="#" onclick="'.htmlspecialchars($onclick).'"'.($def['linkTitle'] ? ' title="'.htmlspecialchars($def['linkTitle']).'"':'').'>').
							$def['icon'].
							($def['label'] ? htmlspecialchars($def['label']) : '&nbsp;').
							$requiredIcon.
							$this->icons($def['stateIcon'],'margin-left: 10px;').
							($isEmpty ? '' : '</a>').
							'</td>';
					$titleLenCount+= strlen($def['label']);
				} else {
						// Create DIV layer for content:
					$divs[] = '
						<div class="'.($isEmpty ? 'disabled' : 'tab').'" id="'.$id.'-'.$index.'-MENU"'.$mouseOverOut.'>'.
							($isEmpty ? '' : '<a href="#" onclick="'.htmlspecialchars($onclick).'"'.($def['linkTitle'] ? ' title="'.htmlspecialchars($def['linkTitle']).'"':'').'>').
							$def['icon'].
							($def['label'] ? htmlspecialchars($def['label']) : '&nbsp;').
							$requiredIcon.
							($isEmpty ? '' : '</a>').
							'</div>';
				}

					// Create DIV layer for content:
				$divs[] = '
						<div style="display: none;" id="'.$id.'-'.$index.'-DIV" class="c-tablayer">'.
							($def['description'] ? '<p class="c-descr">'.nl2br(htmlspecialchars($def['description'])).'</p>' : '').
							$def['content'].
							'</div>';
					// Create initialization string:
				$JSinit[] = '
						DTM_array["'.$id.'"]['.$c.'] = "'.$id.'-'.$index.'";
				';
					// If not empty and we have the toggle option on, check if the tab needs to be expanded
				if ($toggle == 1 && !$isEmpty) {
					$JSinit[] = '
						if (top.DTM_currentTabs["'.$id.'-'.$index.'"]) { DTM_toggle("'.$id.'","'.$index.'",1); }
					';
				}

				$c++;
			}

				// Render menu:
			if (count($options))	{

					// Tab menu is compiled:
				if (!$foldout)	{
					$tabContent = '';
					for($a=0;$a<=$tabRows;$a++)	{
						$tabContent.= '

					<!-- Tab menu -->
					<table cellpadding="0" cellspacing="0" border="0"'.($fullWidth ? ' width="100%"' : '').' class="typo3-dyntabmenu">
						<tr>
								'.implode('',$options[$a]).'
						</tr>
					</table>';
					}
					$content.= '<div class="typo3-dyntabmenu-tabs">'.$tabContent.'</div>';
				}

					// Div layers are added:
				$content.= '
				<!-- Div layers for tab menu: -->
				<div class="typo3-dyntabmenu-divs'.($foldout?'-foldout':'').'">
				'.implode('',$divs).'</div>';

					// Java Script section added:
				$content.= '
				<!-- Initialization JavaScript for the menu -->
				<script type="text/javascript">
					DTM_array["'.$id.'"] = new Array();
					'.implode('',$JSinit).'
					'.($toggle<=0 ? 'DTM_activate("'.$id.'", top.DTM_currentTabs["'.$id.'"]?top.DTM_currentTabs["'.$id.'"]:'.intval($defaultTabIndex).', 0);' : '').'
				</script>

				';
			}

		}
		return $content;
	}

	/**
	 * Creates the id for dynTabMenus.
	 *
	 * @param	string		$identString: Identification string. This should be unique for every instance of a dynamic menu!
	 * @return	string		The id with a short MD5 of $identString and prefixed "DTM-", like "DTM-2e8791854a"
	 */
	function getDynTabMenuId($identString) {
		$id = 'DTM-'.t3lib_div::shortMD5($identString);
		return $id;
	}

	/**
	 * Returns dynamic tab menu header JS code.
	 * This is now incorporated automatically when the function template::getDynTabMenu is called
	 * (as long as it is called before $this->startPage())
	 * The return value is not needed anymore
	 *
	 * @deprecated since TYPO3 4.5, as the getDynTabMenu() function includes the function automatically since TYPO3 4.3
	 * @return	void
	 */
	function getDynTabMenuJScode() {
		t3lib_div::logDeprecatedFunction();
		$this->loadJavascriptLib('js/tabmenu.js');
	}

	/**
	 * Creates the version selector for the page id inputted.
	 * Requires the core version management extension, "version" to be loaded.
	 *
	 * @param	integer		Page id to create selector for.
	 * @param	boolean		If set, there will be no button for swapping page.
	 * @return	void
	 */
	public function getVersionSelector($id, $noAction = FALSE) {
		if (t3lib_extMgm::isLoaded('version')) {
			$versionGuiObj = t3lib_div::makeInstance('tx_version_gui');
			return $versionGuiObj->getVersionSelector($id, $noAction);
		}
	}

	/**
	 * Function to load a HTML template file with markers.
	 * When calling from own extension, use  syntax getHtmlTemplate('EXT:extkey/template.html')
	 *
	 * @param	string		tmpl name, usually in the typo3/template/ directory
	 * @return	string		HTML of template
	 */
	function getHtmlTemplate($filename)	{
			// setting the name of the original HTML template
		$this->moduleTemplateFilename = $filename;

		if ($GLOBALS['TBE_STYLES']['htmlTemplates'][$filename]) {
			$filename = $GLOBALS['TBE_STYLES']['htmlTemplates'][$filename];
		}
		if (t3lib_div::isFirstPartOfStr($filename, 'EXT:')) {
			$filename = t3lib_div::getFileAbsFileName($filename, TRUE, TRUE);
		} elseif (!t3lib_div::isAbsPath($filename)) {
			$filename = t3lib_div::resolveBackPath($this->backPath . $filename);
		} elseif (!t3lib_div::isAllowedAbsPath($filename)) {
			$filename = '';
		}
		$htmlTemplate = '';
		if ($filename !== '') {
			$htmlTemplate = t3lib_div::getURL($filename);
		}
		return $htmlTemplate;
	}

	/**
	 * Define the template for the module
	 *
	 * @param	string		filename
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
	 * @param	array		Record of the current page, used for page path and info
	 * @param	array		HTML for all buttons
	 * @param	array		HTML for all other markers
	 * @return	string		Composite HTML
	 */
	public function moduleBody($pageRecord = array(), $buttons = array(), $markerArray = array(), $subpartArray = array()) {
			// Get the HTML template for the module
		$moduleBody = t3lib_parsehtml::getSubpart($this->moduleTemplate, '###FULLDOC###');
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
			$moduleBody = t3lib_parsehtml::substituteSubpart($moduleBody, $marker, $content);
		}

			// adding flash messages
		if ($this->showFlashMessages) {
			$flashMessages = t3lib_FlashMessageQueue::renderFlashMessages();
			if (!empty($flashMessages)) {
				$markerArray['FLASHMESSAGES'] = '<div id="typo3-messages">' . $flashMessages . '</div>';

					// if there is no dedicated marker for the messages present
					// then force them to appear before the content
				if (strpos($moduleBody, '###FLASHMESSAGES###') === FALSE) {
					$moduleBody = str_replace(
						'###CONTENT###',
						'###FLASHMESSAGES######CONTENT###',
						$moduleBody
					);
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
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}

			// replacing all markers with the finished markers and return the HTML content
		return t3lib_parsehtml::substituteMarkerArray($moduleBody, $markerArray, '###|###');

	}

	/**
	 * Fill the button lists with the defined HTML
	 *
	 * @param	array		HTML for all buttons
	 * @return	array		Containing HTML for both buttonlists
	 */
	protected function getDocHeaderButtons($buttons) {
		$markers = array();
			// Fill buttons for left and right float
		$floats = array('left', 'right');
		foreach($floats as $key) {
				// Get the template for each float
			$buttonTemplate = t3lib_parsehtml::getSubpart($this->moduleTemplate, '###BUTTON_GROUPS_' . strtoupper($key) . '###');
				// Fill the button markers in this float
			$buttonTemplate = t3lib_parsehtml::substituteMarkerArray($buttonTemplate, $buttons, '###|###', true);
				// getting the wrap for each group
			$buttonWrap = t3lib_parsehtml::getSubpart($this->moduleTemplate, '###BUTTON_GROUP_WRAP###');
				// looping through the groups (max 6) and remove the empty groups
			for ($groupNumber = 1; $groupNumber < 6; $groupNumber++) {
				$buttonMarker = '###BUTTON_GROUP' . $groupNumber . '###';
				$buttonGroup = t3lib_parsehtml::getSubpart($buttonTemplate, $buttonMarker);
				if (trim($buttonGroup)) {
					if ($buttonWrap) {
						$buttonGroup = t3lib_parsehtml::substituteMarker($buttonWrap, '###BUTTONS###', $buttonGroup);
					}
					$buttonTemplate = t3lib_parsehtml::substituteSubpart($buttonTemplate, $buttonMarker, trim($buttonGroup));
				}
			}
				// replace the marker with the template and remove all line breaks (for IE compat)
			$markers['BUTTONLIST_' . strtoupper($key)] = str_replace(LF, '', $buttonTemplate);
		}

			// Hook for manipulating docHeaderButtons
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'])) {
			$params = array(
				'buttons'	=> $buttons,
				'markers' 	=> &$markers,
				'pObj' 		=> &$this
			);
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['docHeaderButtonsHook'] as $funcRef)	{
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}

		return $markers;
	}

	/**
	 * Generate the page path for docheader
	 *
	 * @param 	array	Current page
	 * @return	string	Page path
	 */
	protected function getPagePath($pageRecord) {
			// Is this a real page
		if ($pageRecord['uid'])	{
			$title = substr($pageRecord['_thePathFull'], 0, -1);
				// remove current page title
			$pos = strrpos($title, '/');
			if ($pos !== FALSE) {
				$title = substr($title, 0, $pos) . '/';
			}
		} else {
			$title = '';
		}

			// Setting the path of the page
		$pagePath = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path', 1) . ': <span class="typo3-docheader-pagePath">';

			// crop the title to title limit (or 50, if not defined)
		$cropLength = (empty($GLOBALS['BE_USER']->uc['titleLen'])) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
		$croppedTitle = t3lib_div::fixed_lgd_cs($title, -$cropLength);
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
	 * @param 	array	Current page
	 * @return	string	Page info
	 */
	protected function getPageInfo($pageRecord) {

				// Add icon with clickmenu, etc:
		if ($pageRecord['uid'])	{	// If there IS a real page
			$alttext = t3lib_BEfunc::getRecordIconAltText($pageRecord, 'pages');
			$iconImg = t3lib_iconWorks::getSpriteIconForRecord('pages', $pageRecord, array('title'=>$alttext));
				// Make Icon:
			$theIcon = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
			$uid = $pageRecord['uid'];
			$title = t3lib_BEfunc::getRecordTitle('pages', $pageRecord);
		} else {	// On root-level of page tree
				// Make Icon
			$iconImg = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root', array('title' => htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])));
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
	 * @param  string  $title
	 * @param  string  $html
	 * @param  string  $id
	 * @param  string $saveStatePointer
	 * @return string
	 */
	public function collapseableSection($title, $html, $id, $saveStatePointer = '') {
		$hasSave = $saveStatePointer ? TRUE : FALSE;
		$collapsedStyle =  $collapsedClass = '';

		if ($hasSave) {
			/** @var $settings extDirect_DataProvider_BackendUserSettings */
			$settings = t3lib_div::makeInstance('extDirect_DataProvider_BackendUserSettings');
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
		  <div' . $collapsedStyle  . '>' . $html . '</div>
		';

	}


}


// ******************************
// Extension classes of the template class.
// These are meant to provide backend screens with different widths.
// They still do because of the different class-prefixes used for the <div>-sections
// but obviously the final width is determined by the stylesheet used.
// ******************************

/**
 * Extension class for "template" - used for backend pages which are wide. Typically modules taking up all the space in the "content" frame of the backend
 * The class were more significant in the past than today.
 *
 */
class bigDoc extends template {
	var $divClass = 'typo3-bigDoc';
}

/**
 * Extension class for "template" - used for backend pages without the "document" background image
 * The class were more significant in the past than today.
 *
 */
class noDoc extends template {
	var $divClass = 'typo3-noDoc';
}

/**
 * Extension class for "template" - used for backend pages which were narrow (like the Web>List modules list frame. Or the "Show details" pop up box)
 * The class were more significant in the past than today.
 *
 */
class smallDoc extends template {
	var $divClass = 'typo3-smallDoc';
}

/**
 * Extension class for "template" - used for backend pages which were medium wide. Typically submodules to Web or File which were presented in the list-frame when the content frame were divided into a navigation and list frame.
 * The class were more significant in the past than today. But probably you should use this one for most modules you make.
 *
 */
class mediumDoc extends template {
	var $divClass = 'typo3-mediumDoc';
}


/**
 * Extension class for "template" - used in the context of frontend editing.
 */
class frontendDoc extends template {

	/**
	 * Gets instance of PageRenderer
	 *
	 * @return	t3lib_PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = $GLOBALS['TSFE']->getPageRenderer();
		}
		return $this->pageRenderer;
	}

	/**
	 * Used in the frontend context to insert header data via TSFE->additionalHeaderData.
	 * Mimics header inclusion from template->startPage().
	 *
	 * @return	void
	 */
	public function insertHeaderData() {

		$this->backPath = $GLOBALS['TSFE']->backPath = TYPO3_mainDir;
		$this->pageRenderer->setBackPath($this->backPath);
		$this->docStyle();

			// add applied JS/CSS to $GLOBALS['TSFE']
		if ($this->JScode) {
			$this->pageRenderer->addHeaderData($this->JScode);
		}
		if (count($this->JScodeArray)) {
			foreach ($this->JScodeArray as $name => $code) {
				$this->pageRenderer->addJsInlineCode($name, $code);
			}
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/template.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/template.php']);
}



// ******************************
// The template is loaded
// ******************************
$GLOBALS['TBE_TEMPLATE'] = t3lib_div::makeInstance('template');


?>