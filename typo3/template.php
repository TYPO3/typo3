<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML-trans compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *  133: function fw($str)	
 *
 *
 *  157: class template 
 *  212:     function template()	
 *
 *              SECTION: EVALUATION FUNCTIONS
 *  279:     function wrapClickMenuOnIcon($str,$table,$uid='',$listFr=1)	
 *  295:     function viewPageIcon($id,$backPath,$addParams='hspace="3"')	
 *  320:     function issueCommand($params,$rUrl='')	
 *  335:     function isCMlayers()	
 *  345:     function thisBlur()	
 *  355:     function helpStyle()	
 *  372:     function getHeader($table,$row,$path,$noViewPageIcon=0,$tWrap=array('',''))	
 *  398:     function getFileheader($title,$path,$iconfile)	
 *  413:     function makeShortcutIcon($gvList,$setList,$modName,$motherModName="")	
 *  446:     function makeShortcutUrl($gvList,$setList)	
 *  468:     function formWidth($size=48,$textarea=0,$styleOverride='') 
 *  493:     function formWidthText($size=48,$styleOverride='',$wrap='') 
 *  510:     function redirectUrls($thisLocation='')	
 *  534:     function formatTime($tstamp,$type)	
 *  547:     function parseTime()	
 *
 *              SECTION: PAGE BUILDING FUNCTIONS.
 *  580:     function startPage($title)	
 *  649:     function endPage()	
 *  674:     function header($text)	
 *  695:     function section($label,$text,$nostrtoupper=FALSE,$sH=FALSE,$type=0,$allowHTMLinHeader=FALSE)	
 *  719:     function divider($dist)	
 *  735:     function spacer($dist)	
 *  754:     function sectionHeader($label,$sH=FALSE,$addAttrib='')	
 *  771:     function sectionBegin()	
 *  792:     function sectionEnd()	
 *  812:     function middle()	
 *  821:     function endPageJS()	
 *  838:     function docBodyTagBegin()	
 *  848:     function docStyle()	
 *  889:     function insertStylesAndJS($content)	
 *  909:     function initCharset()	
 *  921:     function generator()	
 *
 *              SECTION: OTHER ELEMENTS
 *  953:     function icons($type)	
 *  982:     function t3Button($onClick,$label)	
 *  993:     function dfw($string)	
 * 1003:     function rfw($string)	
 * 1013:     function wrapInCData($string)	
 * 1030:     function wrapScriptTags($string, $linebreak=TRUE)	
 * 1067:     function table($arr)	
 * 1107:     function menuTable($arr1,$arr2=array(), $arr3=array())	
 * 1140:     function funcMenu($content,$menu)	
 * 1158:     function clearCacheMenu($id,$addSaveOptions=0)	
 * 1194:     function getContextMenuCode()	
 * 1340:     function getTabMenu($id,$elementName,$currentValue,$menuItems,$script='',$addparams='')	
 *
 *
 * 1452: class bigDoc extends template 
 *
 *
 * 1461: class noDoc extends template 
 *
 *
 * 1470: class smallDoc extends template 
 *
 *
 * 1479: class mediumDoc extends template 
 *
 * TOTAL FUNCTIONS: 45
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



if (!defined('TYPO3_MODE'))	die("Can't include this file directly.");












/**
 * Depreciated fontwrap function. Is just transparent now.
 * 
 * @param	string		Input string
 * @return	string		Output string (in the old days this was wrapped in <font> tags)
 * @depreciated
 */
function fw($str)	{
	return $str;
}


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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class template {

		// Vars you typically might want to/should set from outside after making instance of this class:
	var $backPath = '';				// 'backPath' pointing back to the PATH_typo3
	var $form='';					// This can be set to the HTML-code for a formtag. Useful when you need a form to span the whole page; Inserted exactly after the body-tag.
	var $JScode='';					// Additional header code (eg. a JavaScript section) could be accommulated in this var. It will be directly outputted in the header.
	var $JScodeArray=array();		// Similar to $JScode but for use as array with assioziative keys to prevent double inclusion of JS code. a <script> tag is automatically wrapped around.
	var $postCode='';				// Additional 'page-end' code could be accommulated in this var. It will be outputted at the end of page before </body> and some other internal page-end code.
	var $docType='';				// Doc-type used in the header. Default is HTML 3.2. You can also set it to 'strict', 'xhtml_trans', or 'xhtml_frames'.

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
	var $styleSheetFile = 'stylesheet.css';	// Filename of stylesheet (relative to PATH_typo3)
	var $styleSheetFile2 = '';		// Filename of stylesheet #2 - linked to right after the $this->styleSheetFile script (relative to PATH_typo3)
	var $styleSheetFile_post = '';	// Filename of a post-stylesheet - included right after all inline styles.
	var $backGroundImage = '';		// Background image of page (relative to PATH_typo3)
	var $inDocStyles_TBEstyle = '';	// Inline css styling set from TBE_STYLES array

		// DEV:	
	var $parseTimeFlag = 0;			// Will output the parsetime of the scripts in milliseconds (for admin-users). Set this to false when releasing TYPO3. Only for dev.
	
		// INTERNAL
	var $charset = 'iso-8859-1';	// Default charset. see function initCharset()
	
	var $sectionFlag=0;				// Internal: Indicates if a <div>-output section is open
	var $divClass = '';				// (Default) Class for wrapping <DIV>-tag of page. Is set in class extensions.






	/**
	 * Constructor 
	 * Imports relevant parts from global $TBE_STYLES (colorscheme)
	 * 
	 * @return	void		
	 */
	function template()	{
		global $TBE_STYLES;

			// Setting default scriptID:
		$this->scriptID = ereg_replace('^.*\/(sysext|ext)\/','ext/',substr(PATH_thisScript,strlen(PATH_site)));
		if (TYPO3_mainDir!='typo3/' && substr($this->scriptID,0,strlen(TYPO3_mainDir)) == TYPO3_mainDir)	{
			$this->scriptID = 'typo3/'.substr($this->scriptID,strlen(TYPO3_mainDir));	// This fixes if TYPO3_mainDir has been changed so the script ids are STILL "typo3/..."
		}
		
		$this->bodyTagId = ereg_replace('[^[:alnum:]-]','-',$this->scriptID);

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

			// Background image
		if ($TBE_STYLES['background'])	$this->backGroundImage = $TBE_STYLES['background'];
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
	 * @param	string		Table name/File path. If the icon is for a database record, enter the tablename from $TCA. If a file then enter the absolute filepath
	 * @param	integer		If icon is for database record this is the UID for the record from $table
	 * @param	boolean		Tells the top frame script that the link is coming from a "list" frame which means a frame from within the backend content frame.
	 * @return	string		The link-wrapped input string.
	 */
	function wrapClickMenuOnIcon($str,$table,$uid='',$listFr=1)	{
		$backPath = '&backPath='.rawurlencode($this->backPath).'|'.t3lib_div::shortMD5($this->backPath.'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
		$onClick = 'top.loadTopMenu(\''.$this->backPath.'alt_clickmenu.php?item='.rawurlencode($table.'|'.$uid.'|'.$listFr).$backPath.'\');'.$this->thisBlur().'return false;';
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
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
		global $BE_USER;
		$str = '';
			// If access to Web>List for user, then link to that module.
		if ($BE_USER->check('modules','web_list'))	{
			$href=$backPath.'db_list.php?id='.$id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
			$str.= '<a href="'.htmlspecialchars($href).'">'.
					'<img'.t3lib_iconWorks::skinImg($backPath,'gfx/list.gif','width="11" height="11"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList',1).'"'.($addParams?' '.trim($addParams):'').' alt="" />'.
					'</a>';
		}
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
	 */
	function issueCommand($params,$rUrl='')	{
		$rUrl = $rUrl ? $rUrl : t3lib_div::getIndpEnv('REQUEST_URI');
		return $this->backPath.'tce_db.php?'.
				$params.
				'&redirect='.($rUrl==-1?"'+T3_THIS_LOCATION+'":rawurlencode($rUrl)).
				'&vC='.rawurlencode($GLOBALS['BE_USER']->veriCode()).
				'&prErr=1&uPT=1';
	}

	/**
	 * Returns true if click-menu layers can be displayed for the current user/browser
	 * Use this to test if click-menus (context sensitive menus) can and should be displayed in the backend.
	 * 
	 * @return	boolean		
	 */
	function isCMlayers()	{
		return !$GLOBALS['BE_USER']->uc['disableCMlayers'] && $GLOBALS['CLIENT']['FORMSTYLE'] && $GLOBALS['CLIENT']['SYSTEM']!='mac';
	}

	/**
	 * Returns 'this.blur();' if the client supports CSS styles
	 * Use this in links to remove the underlining after being clicked
	 * 
	 * @return	string		
	 */
	function thisBlur()	{
		return ($GLOBALS['CLIENT']['FORMSTYLE']?'this.blur();':'');
	}

	/**
	 * Returns ' style='cursor:help;'' if the client supports CSS styles
	 * Use for <a>-links to help texts
	 * 
	 * @return	string		
	 */
	function helpStyle()	{
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
		global $TCA;
		if (is_array($row) && $row['uid'])	{
			$iconImgTag=t3lib_iconWorks::getIconImage($table,$row,$this->backPath,'title="'.htmlspecialchars($path).'"');
			$title= strip_tags($row[$TCA[$table]['ctrl']['label']]);
			$viewPage = $noViewPageIcon ? '' : $this->viewPageIcon($row['uid'],$this->backPath,'');
			if ($table=='pages')	$path.=' - '.t3lib_BEfunc::titleAttribForPages($row,'',0);
		} else {
			$iconImgTag='<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif',$wHattribs='width="18" height="16"').' title="'.htmlspecialchars($path).'" alt="" />';
			$title=$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		}

		return '<span class="typo3-moduleHeader">'.$this->wrapClickMenuOnIcon($iconImgTag,$table,$row['uid']).
				$viewPage.
				$tWrap[0].htmlspecialchars(t3lib_div::fixed_lgd($title,45)).$tWrap[1].'</span>';
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
		$title = htmlspecialchars(t3lib_div::fixed_lgd_pre($fileInfo['path'],35)).'<b>'.htmlspecialchars($fileInfo['file']).'</b>';
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
		
		if (!strcmp($motherModName,'1'))	{
			$mMN="&motherModName='+top.currentModuleLoaded+'";
		} elseif ($motherModName)	{
			$mMN='&motherModName='.rawurlencode($motherModName);
		} else $mMN="";
		
		$onClick = 'if (top.shortcutFrame && confirm('.
					$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.makeShortcut')).
					')){top.shortcutFrame.document.location=\''.$backPath.'alt_shortcut.php?modName='.rawurlencode($modName).
					'&URL='.rawurlencode($pathInfo['path']."?".$storeUrl).
					$mMN.
					'\';}return false;';
			
		$sIcon = '<a href="#" onclick="'.htmlspecialchars($onClick).'">
				<img'.t3lib_iconWorks::skinImg($backPath,'gfx/shortcut.gif','width="14" height="14"').' title="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.makeShortcut',1).'" alt="" /></a>';
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
		global $HTTP_GET_VARS;
		
		$storeArray = array_merge(
			t3lib_div::compileSelectedGetVarsFromArray($gvList,$HTTP_GET_VARS),
			array('SET'=>t3lib_div::compileSelectedGetVarsFromArray($setList,$GLOBALS['SOBE']->MOD_SETTINGS))
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
	 * @param	boolean		A flag you can set for textareas - DEPRECIATED, use ->formWidthText() for textareas!!!
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
	var T3_RETURN_URL = '".str_replace('%20','',rawurlencode(t3lib_div::GPvar('returnUrl')))."';
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
		switch($type)	{
			case 1: return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],$tstamp); break;	
			case 10: return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],$tstamp); break;		
		}
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
	 * @return	string		Returns the whole header section of a HTML-document based on settings in internal variables (like styles, javascript code, charset, generator and docType)
	 * @see endPage()
	 */
	function startPage($title)	{
			// Get META tag containing the currently selected charset for backend output. The function sets $this->charSet.
		$charSet = $this->initCharset();
		$generator = $this->generator();
		
			// For debugging: If this outputs "QuirksMode"/"BackCompat" (IE) the browser runs in quirks-mode. Otherwise the value is "CSS1Compat"
#		$this->JScodeArray[]='alert(document.compatMode);';
		
			// Send HTTP header for selected charset. Added by Robert Lemke 23.10.2003
		header ('Content-Type:text/html;charset='.$this->charset);

		switch($this->docType)	{
			case 'xhtml_strict':
				$headerStart= '<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?xml version="1.0" encoding="'.$this->charset.'"?>
<?xml-stylesheet href="#internalStyle" type="text/css"?>
';
			break;
			case 'xhtml_trans':
				$headerStart= '<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?xml version="1.0" encoding="'.$this->charset.'"?>
<?xml-stylesheet href="#internalStyle" type="text/css"?>
';
			break;
			case 'xhtml_frames':
				$headerStart= '<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<?xml version="1.0" encoding="'.$this->charset.'"?>
';
			break;
			default:
				$headerStart='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">';
			break;
		}
		
			// Construct page header.
		$str = $headerStart.'
<html>
<head>
	<!-- TYPO3 Script ID: '.htmlspecialchars($this->scriptID).' -->
	'.$charSet.'
	'.$generator.'
	<title>'.htmlspecialchars($title).'</title>
	'.$this->docStyle().'
	'.$this->JScode.'
	'.$this->wrapScriptTags(implode("\n", $this->JScodeArray)).'
	<!--###POSTJSMARKER###-->
</head>
';
		$this->JScode='';
		$this->JScodeArray=array();

		if ($this->docType=='xhtml_frames')	{
			return $str;
		} else 
$str.=$this->docBodyTagBegin().
($this->divClass?'

<!-- Wrapping DIV-section for whole page BEGIN -->
<div class="'.$this->divClass.'">
':'').trim($this->form);	
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
				t3lib_BEfunc::getSetUpdateSignal().
				$this->parseTime().
				($this->form?'
</form>':'').
				($this->divClass?'

<!-- Wrapping DIV-section for whole page END -->
</div>':'').
				'
</body>
</html>	';
		return $str;
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
	 * @param	integer		Padding-top for the div-section (should be margin-top but konquorer (3.1) don't like it :-(
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
	function sectionHeader($label,$sH=FALSE,$addAttrib='')	{
		$tag = ($sH?'h3':'h4');
		$str='

	<!-- Section header -->
	<'.$tag.$addAttrib.'>'.$label.'</'.$tag.'>
';
		return $this->sectionBegin().$str;
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
	 * Originally it printed a kind of divider.
	 * Depreciated. Just remove function calls to it or call the divider() function instead.
	 * 
	 * @return	void		
	 * @internal
	 * @depreciated
	 */
	function middle()	{
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
		$bodyContent = 'body '.trim($this->bodyTagAdditions.($this->bodyTagId ? ' id="'.$this->bodyTagId.'"' : ''));
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
		$inDocStyles = implode('
					',$this->inDocStylesArray);

			// The default color scheme should also in full be represented in the stylesheet.
		$style=trim('
			'.($this->styleSheetFile?'<link rel="stylesheet" type="text/css" href="'.$this->backPath.$this->styleSheetFile.'" />':'').'
			'.($this->styleSheetFile2?'<link rel="stylesheet" type="text/css" href="'.$this->backPath.$this->styleSheetFile2.'" />':'').'
			'.(trim($inDocStyles) ? '
			<style type="text/css" id="internalStyle">
				/*<![CDATA[*/
					'.trim($inDocStyles).'
				/*]]>*/
			</style>' : '').'
			'.($this->styleSheetFile_post?'<link rel="stylesheet" type="text/css" href="'.$this->backPath.$this->styleSheetFile_post.'" />':'')
		)
		;
		$this->inDocStyles='';
		$this->inDocStylesArray=array();

		return '
			'.$style;
	}

	/**
	 * Insert post rendering document style into already rendered content
	 * 
	 * @param	string		style-content to insert.
	 * @return	string		content with inserted styles
	 */
	function insertStylesAndJS($content)	{
			// insert accumulated CSS
		$this->inDocStylesArray[] = $this->inDocStyles;
		$styles = "\n".implode("\n", $this->inDocStylesArray);
		$content = str_replace('/*###POSTCSSMARKER###*/',$styles,$content);

			// insert accumulated JS
		$jscode = $this->JScode."\n".$this->wrapScriptTags(implode("\n", $this->JScodeArray));
		$content = str_replace('<!--###POSTJSMARKER###-->',$jscode,$content);

		return $content;
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
	 * @return	string		<meta> tag with name "GENERATOR"
	 */
	function generator()	{
		$str = 'TYPO3 '.$GLOBALS['TYPO_VERSION'].', http://typo3.com, &#169; Kasper Sk&#229;rh&#248;j 1998-2003, extensions are copyright of their respective owners.';
		return '<meta name="GENERATOR" content="'.$str .'" />';
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
	 * @return	string		HTML image tag (if applicable)
	 */
	function icons($type)	{
		switch($type)	{
			case '3':
				$icon = 'gfx/icon_fatalerror.gif';
			break;
			case '2':
				$icon = 'gfx/icon_warning.gif';
			break;
			case '1':
				$icon = 'gfx/icon_note.gif';
			break;
			case '-1':
				$icon = 'gfx/icon_ok.gif';
			break;
			default:
			break;
		}
		if ($icon)	{
			return '<img'.t3lib_iconWorks::skinImg($this->backPath,$icon,'width="18" height="16"').' class="absmiddle" alt="" />';
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
			$cr = $linebreak? "\n" : '';

				// remove nl from the beginning
			$string = preg_replace ('/^\n+/', '', $string);
				// re-ident to one tab using the first line as reference
			if(preg_match('/^(\t+)/',$string,$match)) {
				$string = str_replace($match[1],"\t", $string);
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
	var $tableLayout = Array (
		'defRow' => Array (
			'defCol' => Array('<td valign="top">','</td>')
		)
	);
	var $table_TR = '<tr>';
	var $table_TABLE = '<table border="0" cellspacing="0" cellpadding="0" id="typo3-tmpltable">';

	/**
	 * Returns a table based on the input $arr
	 * 
	 * @param	array		Multidim array with first levels = rows, second levels = cells
	 * @return	string		The HTML table.
	 * @internal
	 */
	function table($arr)	{
		if (is_array($arr))	{
			reset($arr);
			$code='';
			$rc=0;
			while(list(,$val)=each($arr))	{
				if ($rc % 2) {
					$layout = is_array($this->tableLayout['defRowOdd']) ? $this->tableLayout['defRowOdd'] : $this->tableLayout['defRow'];
				} else {
					$layout = is_array($this->tableLayout['defRowEven']) ? $this->tableLayout['defRowEven'] : $this->tableLayout['defRow'];
				}
				$layout = is_array($this->tableLayout[$rc]) ? $this->tableLayout[$rc] : $layout;
				$code_td='';
				if (is_array($val))	{
					$cc=0;
					while(list(,$content)=each($val))	{
						$wrap= is_array($layout[$cc]) ? $layout[$cc] : $layout['defCol'];
						$code_td.=$wrap[0].$content.$wrap[1];
						$cc++;
					}
				}
				$trWrap = is_array($layout['tr']) ? $layout['tr'] : array($this->table_TR, '</tr>');
				$code.=$trWrap[0].$code_td.$trWrap[1];
				$rc++;
			}
			$tableWrap = is_array($this->tableLayout['table']) ? $this->tableLayout['table'] : array($this->table_TABLE, '</table>');
			$code=$tableWrap[0].$code.$tableWrap[1];
		}
		return $code;
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
		global $BE_USER;
		$opt=$addOptions;
		if ($addSaveOptions)	{
			$opt[]='<option value="">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.menu',1).'</option>';
			$opt[]='<option value="TBE_EDITOR_checkAndDoSubmit(1);">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'</option>';
			$opt[]='<option value="document.editform.closeDoc.value=-2; TBE_EDITOR_checkAndDoSubmit(1);">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'</option>';
			if ($BE_USER->uc['allSaveFunctions'])	$opt[]='<option value="document.editform.closeDoc.value=-3; TBE_EDITOR_checkAndDoSubmit(1);">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseAllDocs',1).'</option>';
			$opt[]='<option value="document.editform.closeDoc.value=2; document.editform.submit();">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'</option>';
			$opt[]='<option value="document.editform.closeDoc.value=3; document.editform.submit();">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeAllDocs',1).'</option>';
			$opt[]='<option value=""></option>';
		}
		$opt[]='<option value="">[ '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_clearCache',1).' ]</option>';
		if ($id) $opt[]='<option value="'.$id.'">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_thisPage',1).'</option>';
		if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.clearCache.pages')) $opt[]='<option value="pages">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_pages',1).'</option>';
		if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.clearCache.all')) $opt[]='<option value="all">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.clearCache_all',1).'</option>';

		$onChange = 'if (!this.options[this.selectedIndex].value) {
				this.selectedIndex=0;
			} else if (this.options[this.selectedIndex].value.indexOf(\';\')!=-1) {
				eval(this.options[this.selectedIndex].value);
			}else{
				document.location=\''.$this->backPath.'tce_db.php?vC='.$BE_USER->veriCode().'&redirect='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')).'&cacheCmd=\'+this.options[this.selectedIndex].value;
			}';
		$af_content = '<select name="cacheCmd" onchange="'.htmlspecialchars($onChange).'">'.implode('',$opt).'</select>';

		if (count($opt)>2)	{
			return $af_content;
		}
	}
	
	/**
	 * Returns an array with parts (JavaScript, init-functions, <div>-layers) for use on pages which displays the clickmenu layers (context sensitive menus)
	 * 
	 * @return	array		If values are present: [0] = A <script> section for the HTML page header, [1] = onmousemove/onload handler for HTML tag or alike, [2] = Two empty <div> layers for the context menu
	 */
	function getContextMenuCode()	{
		if ($this->isCMlayers())	{
			$content='
			<script type="text/javascript">
			/*<![CDATA[*/
				var GLV_gap=10;
				var GLV_curLayerX=new Array(0,0);
				var GLV_curLayerY=new Array(0,0);
				var GLV_curLayerWidth=new Array(0,0);
				var GLV_curLayerHeight=new Array(0,0);
				var GLV_isVisible=new Array(0,0);
				var GLV_x=0;
				var GLV_y=0;
				var GLV_xRel=0;
				var GLV_yRel=0;
				var layerObj=new Array();
				var layerObjCss=new Array();
				
					//browsercheck...
				function GL_checkBrowser(){	//
					this.dom= (document.getElementById);
					this.op=  (navigator.userAgent.indexOf("Opera")>-1);
					this.op7=  this.op && (navigator.appVersion.indexOf("7")>-1);  // check for Opera version 7
					this.konq=  (navigator.userAgent.indexOf("Konq")>-1);
					this.ie4= (document.all && !this.dom && !this.op && !this.konq);
					this.ie5= (document.all && this.dom && !this.op && !this.konq);
					this.ns4= (document.layers && !this.dom && !this.konq);
					this.ns5= (!document.all && this.dom && !this.op && !this.konq);
					this.ns6= (this.ns5);
					this.bw=  (this.ie4 || this.ie5 || this.ns4 || this.ns6 || this.op || this.konq);
					return this;
				}
				bw= new GL_checkBrowser();	
				
					// GL_getObj(obj)
				function GL_getObj(obj){	//
					nest="";
					this.el= (bw.ie4||bw.op7)?document.all[obj]:bw.ns4?eval(nest+"document."+obj):document.getElementById(obj);	
				   	this.css= bw.ns4?this.el:this.el.style;
					this.ref= bw.ns4?this.el.document:document;		
					this.x= (bw.ns4||bw.op)?this.css.left:this.el.offsetLeft;
					this.y= (bw.ns4||bw.op)?this.css.top:this.el.offsetTop;
					this.height= (bw.ie4||bw.ie5||bw.ns6||this.konq||bw.op7)?this.el.offsetHeight:bw.ns4?this.ref.height:bw.op?this.css.pixelHeight:0;
					this.width= (bw.ie4||bw.ie5||bw.ns6||this.konq||bw.op7)?this.el.offsetWidth:bw.ns4?this.ref.width:bw.op?this.css.pixelWidth:0;
					return this;
				}
					// GL_getObjCss(obj)
				function GL_getObjCss(obj){	//
					return bw.dom? document.getElementById(obj).style:bw.ie4?document.all[obj].style:bw.ns4?document.layers[obj]:0;
				}
					// GL_getMouse(event)
				function GL_getMouse(event) {	//
					if (layerObj)	{
//						GLV_x= (bw.ns4||bw.ns5)?event.pageX:(bw.ie4||bw.op)?event.clientX:(event.clientX-2)+document.body.scrollLeft;
//						GLV_y= (bw.ns4||bw.ns5)?event.pageY:(bw.ie4||bw.op)?event.clientY:(event.clientY-2)+document.body.scrollTop;
							// 17/12 2003: When documents run in XHTML standard compliance mode, the old scrollLeft/Top properties of document.body is gone - and for Opera/MSIE we have to use document.documentElement:
						
						GLV_xRel = event.clientX-2;
						GLV_yRel = event.clientY-2;
						GLV_x = GLV_xRel + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft);
						GLV_y = GLV_yRel + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);

					//	status = (GLV_x+GLV_gap-GLV_curLayerX[0]) + " | " + (GLV_y+GLV_gap-GLV_curLayerY[0]);
						if (GLV_isVisible[1])	{
							if (outsideLayer(1))	hideSpecific(1);
						} else if (GLV_isVisible[0])	{
							if (outsideLayer(0))	hideSpecific(0);
						}
					}
				}
					// outsideLayer(level)
				function outsideLayer(level)	{	//
					return GLV_x+GLV_gap-GLV_curLayerX[level] <0 ||
							GLV_y+GLV_gap-GLV_curLayerY[level] <0 ||
							GLV_curLayerX[level]+GLV_curLayerWidth[level]+GLV_gap-GLV_x <0 ||
							GLV_curLayerY[level]+GLV_curLayerHeight[level]+GLV_gap-GLV_y <0;
				}
					// setLayerObj(html,level)
				function setLayerObj(html,level)	{	//
					var tempLayerObj = GL_getObj("contentMenu"+level);
					var tempLayerObjCss = GL_getObjCss("contentMenu"+level);
	
					if (tempLayerObj && (level==0 || GLV_isVisible[level-1]))	{
						tempLayerObj.el.innerHTML = html;
						tempLayerObj.width= (bw.ie4||bw.ie5||bw.ns6||bw.konq||bw.op7)?this.el.offsetWidth:bw.ns4?this.ref.width:bw.op?this.css.pixelWidth:0;
						tempLayerObj.height= (bw.ie4||bw.ie5||bw.ns6||bw.konq||bw.op7)?this.el.offsetHeight:bw.ns4?this.ref.height:bw.op?this.css.pixelHeight:0;
						
							// Adjusting the Y-height of the layer to fit it into the window frame if it goes under the window frame in the bottom:
						tempLayerObj.winHeight = document.documentElement.clientHeight && !bw.op7 ? document.documentElement.clientHeight : document.body.clientHeight;
						if (tempLayerObj.winHeight-tempLayerObj.height < GLV_yRel)	{ 
							if (GLV_yRel < tempLayerObj.height) {
								GLV_y+= (tempLayerObj.winHeight-tempLayerObj.height-GLV_yRel); 		// Setting it so bottom is just above window height.
							} else {
								GLV_y-= tempLayerObj.height-8; 		// Showing the menu upwards
							}
						}
						
						GLV_curLayerX[level] = GLV_x;
						GLV_curLayerY[level] = GLV_y;
						tempLayerObjCss.left = GLV_x+"px";
						tempLayerObjCss.top = GLV_y+"px";
						tempLayerObjCss.visibility = "visible";
						if (bw.ie5)	showHideSelectorBoxes("hidden");

						GLV_isVisible[level]=1;
						GLV_curLayerWidth[level] = tempLayerObj.width;
						GLV_curLayerHeight[level] = tempLayerObj.height;
					}
				}
					// hideEmpty()
				function hideEmpty()	{	//
					hideSpecific(0);
					hideSpecific(1);
					return false;
				}
					// hideSpecific(level)
				function hideSpecific(level)	{	//
					GL_getObjCss("contentMenu"+level).visibility = "hidden";
					GL_getObj("contentMenu"+level).el.innerHTML = "";
					GLV_isVisible[level]=0;
					
					if (bw.ie5 && level==0)	showHideSelectorBoxes("visible");
				}
					// debugObj(obj,name)
				function debugObj(obj,name)	{	//
					var acc;
					for (i in obj) {if (obj[i])	{acc+=i+":  "+obj[i]+"\n";}}			  
					alert("Object: "+name+"\n\n"+acc);
				}
					// initLayer()
				function initLayer(){	//
					if (document.all)   {
						window.onmousemove=GL_getMouse;
					}
					layerObj = GL_getObj("contentMenu1");
					layerObjCss = GL_getObjCss("contentMenu1");
				}
				function showHideSelectorBoxes(action)	{	// This function by Michiel van Leening
					for (i=0;i<document.forms.length;i++) {
						for (j=0;j<document.forms[i].elements.length;j++) {
							if(document.forms[i].elements[j].type=="select-one") {
								document.forms[i].elements[j].style.visibility=action;
							}
						}
					}					
				}
			/*]]>*/
			</script>		
			';
			return array(
				$content,
				' onmousemove="GL_getMouse(event);" onload="initLayer();"',
				'<div id="contentMenu0" style="z-index:1; position:absolute;visibility:hidden"></div><div id="contentMenu1" style="z-index:2; position:absolute;visibility:hidden"></div>'
			);
		} else return array('','','');
	}




	/**
	 * creates a tab menu from an array definition
	 * 
	 * Returns a tab menu for a module
	 * Requires the JS function jumpToUrl() to be available
	 * 
	 * @param	string		$id is the "&id=" parameter value to be sent to the module
	 * @param	string		$elementName it the form elements name, probably something like "SET[...]"
	 * @param	string		$currentValue is the value to be selected currently.
	 * @param	array		$menuItems is an array with the menu items for the selector box
	 * @param	string		$script is the script to send the &id to, if empty it's automatically found
	 * @param	string		$addParams is additional parameters to pass to the script.
	 * @return	string		HTML code for tab menu
	 * @todo	Update the HTML code to use the stylesheet.css instead.
	 * @author	René Fritz <r.fritz@colorcube.de>
	 */
	function getTabMenu($id,$elementName,$currentValue,$menuItems,$script='',$addparams='')	{
		$content='';

		if (is_array($menuItems))	{
			if (!$script) {$script=basename(PATH_thisScript);}
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

			$first=true;
			foreach($menuItems as $value => $label) {
				$isActive = !strcmp($currentValue,$value);
				$class = $isActive ? "tabact" : "tab";
				$width = $isActive ? $widthAct : $widthNo;

				$label = t3lib_div::deHSCentities(htmlspecialchars($label));
				$link = htmlspecialchars($script.'?id='.rawurlencode($id).$addparams.'&'.$elementName.'='.$value);
				if($first) {
					$options.= '
							<td width="'.$width.'%" class="'.$class.'" style="border-left: solid #000 1px;"><a href="'.$link.'" class="'.$class.'" style="padding-left:5px;padding-right:2px;">'.$label.'</a></td>';
				} else {
					$options.='
							<td width="'.$width.'%" class="'.$class.'"><a href="'.$link.'" class="'.$class.'">'.$label.'</a></td>';
				}
				$first=false;
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
					<div class="hr" style="display:block;margin:0px;padding:0px;"></div>';
			}

				// include CSS
			$actBgColor = t3lib_div::modifyHTMLcolorAll($this->bgColor6,0);
			$lgBgColor = t3lib_div::modifyHTMLcolorAll($this->bgColor5,25);
			$hoverBgColor = t3lib_div::modifyHTMLcolorAll($this->bgColor6,15);

			$this->inDocStylesArray['getTabMenu'] = '
				td.tabact {
					border: solid black 1px;
					background: '.$actBgColor.';
					color:#000;
				}
				td.tabact>a {
					color:#000;
				}
				td.tab {
					border: solid #555 1px;
					border-left: solid #aaa 3px;
					background: '.$lgBgColor.';
					color:grey;
				}
				td.tab, td.tabact {
					border-bottom: none;
					border-radius: 3px;
					-moz-border-radius: 3px;
				}
				a.tab, a.tabact {
					color:grey;
					text-decoration:none;
					display: block;
					width:auto;
					padding:2px;
					padding-left:3px;
					padding-right:5px;
				}
				a.tabact {
					padding-left:10px;
					padding-right:10px;
				}
				a.tab:hover,a.tabact:hover {
					color:black;
					background: '.$hoverBgColor.';
					text-decoration:none;
					cursor: pointer;
				}';
		}
		return $content;
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



// Include extension to the template class?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/template.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/template.php']);
}



// ******************************************************
// The backend language engine is started (ext: "lang")
// ******************************************************
require_once(PATH_typo3.'sysext/lang/lang.php');
$LANG = t3lib_div::makeInstance('language');
$LANG->init($BE_USER->uc['lang']);



// ******************************
// The template is loaded
// ******************************
$TBE_TEMPLATE = t3lib_div::makeInstance('template');
?>
