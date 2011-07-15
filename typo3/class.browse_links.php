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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


	// Include classes
require_once (PATH_typo3.'/class.db_list.inc');
require_once (PATH_typo3.'/class.db_list_extra.inc');




/**
 * Local version of the record list.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_browser_recordList extends localRecordList {
	var $thisScript = 'browse_links.php';

	/**
	 * Initializes the script path
	 *
	 * @return	void
	 */
	function __construct() {
		parent::__construct();
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
	}

	/**
	 * Compatibility constructor.
	 *
	 * @deprecated since TYPO3 4.6 and will be removed in TYPO3 4.8. Use __construct() instead.
	 */
	public function TBE_browser_recordList() {
		t3lib_div::logDeprecatedFunction();
			// Note: we cannot call $this->__construct() here because it would call the derived class constructor and cause recursion
			// This code uses official PHP behavior (http://www.php.net/manual/en/language.oop5.basic.php) when $this in the
			// statically called non-static method inherits $this from the caller's scope.
		TBE_browser_recordList::__construct();
	}

	/**
	 * Creates the URL for links
	 *
	 * @param	mixed		If not blank string, this is used instead of $this->id as the id value.
	 * @param	string		If this is "-1" then $this->table is used, otherwise the value of the input variable.
	 * @param	string		Commalist of fields NOT to pass as parameters (currently "sortField" and "sortRev")
	 * @return	string		Query-string for URL
	 */
	function listURL($altId='',$table=-1,$exclList='')	{
		return $this->thisScript.
			'?id='.(strcmp($altId,'')?$altId:$this->id).
			'&table='.rawurlencode($table==-1?$this->table:$table).
			($this->thumbs?'&imagemode='.$this->thumbs:'').
			($this->searchString?'&search_field='.rawurlencode($this->searchString):'').
			($this->searchLevels?'&search_levels='.rawurlencode($this->searchLevels):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortField')) && $this->sortField?'&sortField='.rawurlencode($this->sortField):'').
			((!$exclList || !t3lib_div::inList($exclList,'sortRev')) && $this->sortRev?'&sortRev='.rawurlencode($this->sortRev):'').
				// extra:
			$this->ext_addP()
			;
	}

	/**
	 * Returns additional, local GET parameters to include in the links of the record list.
	 *
	 * @return	string
	 */
	function ext_addP()	{
		$str = '&act='.$GLOBALS['SOBE']->browser->act.
				'&mode='.$GLOBALS['SOBE']->browser->mode.
				'&expandPage='.$GLOBALS['SOBE']->browser->expandPage.
				'&bparams='.rawurlencode($GLOBALS['SOBE']->browser->bparams);
		return $str;
	}

	/**
	 * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for "pages"-records a link to the level of that record...)
	 *
	 * @param	string		Table name
	 * @param	integer		UID (not used here)
	 * @param	string		Title string
	 * @param	array		Records array (from table name)
	 * @return	string
	 */
	function linkWrapItems($table,$uid,$code,$row)	{
		if (!$code) {
			$code = '<i>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</i>';
		} else {
			$code = t3lib_BEfunc::getRecordTitlePrep($code, $this->fixedL);
		}

		$title = t3lib_BEfunc::getRecordTitle($table,$row,FALSE,TRUE);
		$ficon = t3lib_iconWorks::getIcon($table,$row);
		$aOnClick = "return insertElement('".$table."', '".$row['uid']."', 'db', ".t3lib_div::quoteJSvalue($title).", '', '', '".$ficon."');";
		$ATag = '<a href="#" onclick="'.$aOnClick.'">';
		$ATag_alt = substr($ATag,0,-4).',\'\',1);">';
		$ATag_e = '</a>';

		return $ATag.
				'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') .
				' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' .
				$ATag_e.
				$ATag_alt.
				$code.
				$ATag_e;
	}

	/**
	 * Local version that sets allFields to TRUE to support userFieldSelect
	 *
	 * @return	void
	 * @see fieldSelectBox
	 */
	function generateList() {
		$this->allFields = TRUE;
		parent::generateList();
	}
}






/**
 * Class which generates the page tree
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_browseTree {

	/**
	 * whether the page ID should be shown next to the title, activate through userTSconfig (options.pageTree.showPageIdWithTitle)
	 * @boolean
	 */
	public $ext_showPageId = FALSE;

	/**
	 * Constructor. Just calling init()
	 *
	 * @return	void
	 */
	function __construct() {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

		$this->init();

		$this->clause = ' AND doktype!=' . t3lib_pageSelect::DOKTYPE_RECYCLER . $this->clause;
	}

	/**
	 * Compatibility constructor.
	 *
	 * @deprecated since TYPO3 4.6 and will be removed in TYPO3 4.8. Use __construct() instead.
	 */
	public function localPageTree() {
		t3lib_div::logDeprecatedFunction();
			// Note: we cannot call $this->__construct() here because it would call the derived class constructor and cause recursion
			// This code uses official PHP behavior (http://www.php.net/manual/en/language.oop5.basic.php) when $this in the
			// statically called non-static method inherits $this from the caller's scope.
		localPageTree::__construct();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, (must be ready for output, that means it must be htmlspecialchars()'ed).
	 * @param	array		The record
	 * @param	boolean		(Ignore)
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v,$ext_pArrPages='')	{
		if ($this->ext_isLinkable($v['doktype'],$v['uid']))	{
			$aOnClick = "return link_typo3Page('".$v['uid']."');";
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span style="color: #666666;">'.$title.'</span>';
		}
	}

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param	array		Tree array
	 * @return	string		HTML output.
	 */
	function printTree($treeArr='')	{
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass = ($c+1)%2 ? 'bgColor' : 'bgColor-10';
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act']=='page' && $GLOBALS['SOBE']->browser->curUrlInfo['pageid']==$v['row']['uid'] && $GLOBALS['SOBE']->browser->curUrlInfo['pageid'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}

			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandPage='.$v['row']['uid'].'\');';
			$cEbullet = $this->ext_isLinkable($v['row']['doktype'],$v['row']['uid']) ?
						'<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' :
						'';
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap"'.($v['row']['_CSSCLASS'] ? ' class="'.$v['row']['_CSSCLASS'].'"' : '').'>'.
					$v['HTML'].
					$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$this->ext_pArrPages).
					'</td>'.
					$arrCol.
					'<td>'.$cEbullet.'</td>
				</tr>';
		}
		$out='


			<!--
				Navigation Page Tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				'.$out.'
			</table>';
		return $out;
	}

	/**
	 * Returns TRUE if a doktype can be linked.
	 *
	 * @param	integer		Doktype value to test
	 * @param	integer		uid to test.
	 * @return	boolean
	 */
	function ext_isLinkable($doktype,$uid)	{
		if ($uid && $doktype<199)	{
			return TRUE;
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @param	boolean		If set, the link will have a anchor point (=$bMark) and a name attribute (=$bMark)
	 * @return	string		Link-wrapped input string
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		if ($bMark)	{
			$anchor = '#'.$bMark;
			$name=' name="'.$bMark.'"';
		}
		$aOnClick = "return jumpToUrl('".$this->thisScript.'?PM='.$cmd."','".$anchor."');";

		return '<a href="#"'.$name.' onclick="'.htmlspecialchars($aOnClick).'">'.$icon.'</a>';
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row
	 *
	 * @param	string		The image tag for the icon
	 * @param	array		The row for the current element
	 * @return	string		The processed icon input value.
	 */
	function wrapIcon($icon, $row) {
		$content = $this->addTagAttributes($icon, ' title="id=' . $row['uid'] . '"');
		if ($this->ext_showPageId) {
		 	$content .= '[' . $row['uid'] . ']&nbsp;';
		}
		return $content;
	}
}








/**
 * Page tree for the RTE - totally the same, no changes needed. (Just for the sake of beauty - or confusion... :-)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rtePageTree extends localPageTree {
}








/**
 * For TBE record browser
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_PageTree extends localPageTree {

	/**
	 * Returns TRUE if a doktype can be linked (which is always the case here).
	 *
	 * @param	integer		Doktype value to test
	 * @param	integer		uid to test.
	 * @return	boolean
	 */
	function ext_isLinkable($doktype,$uid)	{
		return TRUE;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, ready for output.
	 * @param	array		The record
	 * @param	boolean		If set, pages clicked will return immediately, otherwise reload page.
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v,$ext_pArrPages)	{
		if ($ext_pArrPages)	{
			$ficon=t3lib_iconWorks::getIcon('pages',$v);
			$onClick = "return insertElement('pages', '".$v['uid']."', 'db', ".t3lib_div::quoteJSvalue($v['title']).", '', '', '".$ficon."','',1);";
		} else {
			$onClick = htmlspecialchars('return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandPage='.$v['uid'].'\');');
		}
		return '<a href="#" onclick="'.$onClick.'">'.$title.'</a>';
	}
}








/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localFolderTree extends t3lib_folderTree {
	var $ext_IconMode=1;


	/**
	 * Initializes the script path
	 *
	 * @return	void
	 */
	function __construct() {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
		parent::__construct();
	}

	/**
	 * Compatibility constructor.
	 *
	 * @deprecated since TYPO3 4.6 and will be removed in TYPO3 4.8. Use __construct() instead.
	 */
	public function localFolderTree() {
		t3lib_div::logDeprecatedFunction();
			// Note: we cannot call $this->__construct() here because it would call the derived class constructor and cause recursion
			// This code uses official PHP behavior (http://www.php.net/manual/en/language.oop5.basic.php) when $this in the
			// statically called non-static method inherits $this from the caller's scope.
		localFolderTree::__construct();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, ready for output.
	 * @param	array		The "record"
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		TRUE is returned if the path is found in the web-part of the server and is NOT a recycler or temp folder
	 */
	function ext_isLinkable($v)	{
		$webpath=t3lib_BEfunc::getPathType_web_nonweb($v['path']);	// Checking, if the input path is a web-path.
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
		$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?PM='.$cmd.'\',\''.$anchor.'\');';
		return '<a href="#"'.$name.' onclick="'.htmlspecialchars($aOnClick).'">'.$icon.'</a>';
	}

	/**
	 * Create the folder navigation tree in HTML
	 *
	 * @param	mixed		Input tree array. If not array, then $this->tree is used.
	 * @return	string		HTML output of the tree.
	 */
	function printTree($treeArr='')	{
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

			// Preparing the current-path string (if found in the listing we will see a red blinking arrow).
		if (!$GLOBALS['SOBE']->browser->curUrlInfo['value'])	{
			$cmpPath='';
		} elseif (substr(trim($GLOBALS['SOBE']->browser->curUrlInfo['info']),-1)!='/')	{
			$cmpPath=PATH_site.dirname($GLOBALS['SOBE']->browser->curUrlInfo['info']).'/';
		} else {
			$cmpPath=PATH_site.$GLOBALS['SOBE']->browser->curUrlInfo['info'];
		}

			// Traverse rows for the tree and print them into table rows:
		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass=($c+1)%2 ? 'bgColor' : 'bgColor-10';

				// Creating blinking arrow, if applicable:
			if (($GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'file' || $GLOBALS['SOBE']->browser->curUrlInfo['act'] == 'folder') && $cmpPath == $v['row']['path']) {
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}
				// Create arrow-bullet for file listing (if folder path is linkable):
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['row']['path']).'\');';
			$cEbullet = $this->ext_isLinkable($v['row']) ? '<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' : '';

				// Put table row with folder together:
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap">' . $v['HTML'] . $this->wrapTitle(htmlspecialchars(t3lib_div::fixed_lgd_cs($v['row']['title'], $titleLen)), $v['row']) . '</td>
					'.$arrCol.'
					<td>'.$cEbullet.'</td>
				</tr>';
		}

		$out='

			<!--
				Folder tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				'.$out.'
			</table>';
		return $out;
	}
}






/**
 * Folder tree for the RTE - totally the same, no changes needed. (Just for the sake of beauty - or confusion... :-)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rteFolderTree extends localFolderTree {
}







/**
 * For TBE File Browser
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_FolderTree extends localFolderTree {
	var $ext_noTempRecyclerDirs=0;		// If file-drag mode is set, temp and recycler folders are filtered out.

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		TRUE is returned if the path is NOT a recycler or temp folder AND if ->ext_noTempRecyclerDirs is not set.
	 */
	function ext_isLinkable($v)	{
		if ($this->ext_noTempRecyclerDirs && (substr($v['path'],-7)=='_temp_/' || substr($v['path'],-11)=='_recycler_/'))	{
			return 0;
		} return 1;
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, ready for output.
	 * @param	array		The 'record'
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v)	{
		if ($this->ext_isLinkable($v))	{
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}
}





/**
 * class for the Element Browser window.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class browse_links {

		// Internal, static:
	var $siteURL;			// Current site URL (Frontend)
	var $thisScript;		// the script to link to
	var $thisConfig;		// RTE specific TSconfig
	var $setTarget;			// Target (RTE specific)
	var $setClass;			// CSS Class (RTE specific)
	var $setTitle;      		// title (RTE specific)
	var $doc;			// Backend template object
	var $elements = array();	// Holds information about files

		// GPvars:	(Input variables from outside)
	/**
	 * The mode determines the main kind of output from the element browser.
	 * There are these options for values: rte, db, file, filedrag, wizard.
	 * "rte" will show the link selector for the Rich Text Editor (see main_rte())
	 * "db" will allow you to browse for pages or records in the page tree (for TCEforms, see main_db())
	 * "file"/"filedrag" will allow you to browse for files or folders in the folder mounts (for TCEforms, main_file())
	 * "wizard" will allow you to browse for links (like "rte") which are passed back to TCEforms (see main_rte(1))
	 *
	 * @see main()
	 */
	var $mode;

	/**
	 * Link selector action.
	 * page,file,url,mail,spec are allowed values.
	 * These are only important with the link selector function and in that case they switch between the various menu options.
	 */
	var $act;

	/**
	 * When you click a page title/expand icon to see the content of a certain page, this value will contain that value (the ID of the expanded page). If the value is NOT set, then it will be restored from the module session data (see main(), mode="db")
	 */
	var $expandPage;

	/**
	 * When you click a folder name/expand icon to see the content of a certain file folder, this value will contain that value (the path of the expanded file folder). If the value is NOT set, then it will be restored from the module session data (see main(), mode="file"/"filedrag"). Example value: "/www/htdocs/typo3/32/3dsplm/fileadmin/css/"
	 */
	var $expandFolder;



	/**
	 * TYPO3 Element Browser, wizard mode parameters. There is a heap of parameters there, better debug() them out if you need something... :-)
	 */
	var $P;

	/**
	 * Active with TYPO3 Element Browser: Contains the name of the form field for which this window opens - thus allows us to make references back to the main window in which the form is.
	 * Example value: "data[pages][39][bodytext]|||tt_content|" or "data[tt_content][NEW3fba56fde763d][image]|||gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai|"
	 *
	 * Values:
	 * 0: form field name reference, eg. "data[tt_content][123][image]"
	 * 1: htlmArea RTE parameters: editorNo:contentTypo3Language:contentTypo3Charset
	 * 2: RTE config parameters: RTEtsConfigParams
	 * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
	 * 4: IRRE uniqueness: target level object-id to perform actions/checks on, eg. "data[79][tt_address][1][<field>][<foreign_table>]"
	 * 5: IRRE uniqueness: name of function in opener window that checks if element is already used, eg. "inline.checkUniqueElement"
	 * 6: IRRE uniqueness: name of function in opener window that performs some additional(!) action, eg. "inline.setUniqueElement"
	 * 7: IRRE uniqueness: name of function in opener window that performs action instead of using addElement/insertElement, eg. "inline.importElement"
	 *
	 * $pArr = explode('|',$this->bparams);
	 * $formFieldName = $pArr[0];
	 * $allowedTablesOrFileTypes = $pArr[3];
	 */
	var $bparams;

	/**
	 * Used with the Rich Text Editor.
	 * Example value: "tt_content:NEW3fba58c969f5c:bodytext:23:text:23:"
	 */
	var $RTEtsConfigParams;




	/**
	 * Plus/Minus icon value. Used by the tree class to open/close notes on the trees.
	 */
	var $PM;

	/**
	 * Pointer, used when browsing a long list of records etc.
	 */
	var $pointer;




	/**
	 * Used with the link selector: Contains the GET input information about the CURRENT link in the RTE/TCEform field. This consists of "href", "target" and "title" keys. This information is passed around in links.
	 */
	var $curUrlArray;

	/**
	 * Used with the link selector: Contains a processed version of the input values from curUrlInfo. This is splitted into pageid, content element id, label value etc. This is used for the internal processing of that information.
	 */
	var $curUrlInfo;

	/**
	 * array which holds hook objects (initialised in init() )
	 */
	protected $hookObjects = array();


	/**
	 * object for t3lib_basicFileFunctions
	 */
	public $fileProcessor;


	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return	void
	 */
	function init()	{
			// Main GPvars:
		$this->pointer           = t3lib_div::_GP('pointer');
		$this->bparams           = t3lib_div::_GP('bparams');
		$this->P                 = t3lib_div::_GP('P');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$this->expandPage        = t3lib_div::_GP('expandPage');
		$this->expandFolder      = t3lib_div::_GP('expandFolder');
		$this->PM                = t3lib_div::_GP('PM');

			// Find "mode"
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode = 'rte';
		}
			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
			// Load the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/browse_links.js');

			// init hook objects:
		$this->hookObjects = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'] as $classData) {
				$processObject = t3lib_div::getUserObj($classData);

				if(!($processObject instanceof t3lib_browseLinksHook)) {
					throw new UnexpectedValueException('$processObject must implement interface t3lib_browseLinksHook', 1195039394);
				}

				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}

			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// init fileProcessor
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);


			// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode == 'wizard')	{
			$currentValues = t3lib_div::trimExplode(LF, trim($this->P['currentValue']));
			if (count($currentValues) > 0) {
				$currentValue = array_pop($currentValues);
			} else {
				$currentValue = '';
			}
			$currentLinkParts = t3lib_div::unQuoteFilenames($currentValue, TRUE);
			$initialCurUrlArray = array (
				'href'   => $currentLinkParts[0],
				'target' => $currentLinkParts[1],
				'class'  => $currentLinkParts[2],
				'title'  => $currentLinkParts[3],
				'params'  => $currentLinkParts[4]
			);
			$this->curUrlArray = (is_array(t3lib_div::_GP('curUrl'))) ?
				array_merge($initialCurUrlArray, t3lib_div::_GP('curUrl')) :
				$initialCurUrlArray;
			$this->curUrlInfo = $this->parseCurUrl($this->siteURL.'?id='.$this->curUrlArray['href'], $this->siteURL);
			if ($this->curUrlInfo['pageid'] == 0 && $this->curUrlArray['href']) { // pageid == 0 means that this is not an internal (page) link
				if (file_exists(PATH_site.rawurldecode($this->curUrlArray['href'])))	{ // check if this is a link to a file
					if (t3lib_div::isFirstPartOfStr($this->curUrlArray['href'], PATH_site)) {
						$currentLinkParts[0] = substr($this->curUrlArray['href'], strlen(PATH_site));
					}
					$this->curUrlInfo = $this->parseCurUrl($this->siteURL.$this->curUrlArray['href'], $this->siteURL);
				} elseif (strstr($this->curUrlArray['href'], '@')) { // check for email link
					if (t3lib_div::isFirstPartOfStr($this->curUrlArray['href'], 'mailto:')) {
						$currentLinkParts[0] = substr($this->curUrlArray['href'], 7);
					}
					$this->curUrlInfo = $this->parseCurUrl('mailto:'.$this->curUrlArray['href'], $this->siteURL);
				} else { // nothing of the above. this is an external link
					if(strpos($this->curUrlArray['href'], '://') === FALSE) {
						$currentLinkParts[0] = 'http://' . $this->curUrlArray['href'];
					}
					$this->curUrlInfo = $this->parseCurUrl($currentLinkParts[0], $this->siteURL);
				}
			} elseif (!$this->curUrlArray['href']) {
				$this->curUrlInfo = array();
				$this->act = 'page';
			} else {
				$this->curUrlInfo = $this->parseCurUrl($this->siteURL.'?id='.$this->curUrlArray['href'], $this->siteURL);
			}
		} else {
			$this->curUrlArray = t3lib_div::_GP('curUrl');
			if ($this->curUrlArray['all'])	{
				$this->curUrlArray=t3lib_div::get_tag_attributes($this->curUrlArray['all']);
			}
			$this->curUrlInfo=$this->parseCurUrl($this->curUrlArray['href'],$this->siteURL);
		}

			// Determine nature of current url:
		$this->act=t3lib_div::_GP('act');
		if (!$this->act)	{
			$this->act=$this->curUrlInfo['act'];
		}

			// Rich Text Editor specific configuration:
		$addPassOnParams='';
		if ((string)$this->mode == 'rte')	{
			$RTEtsConfigParts = explode(':',$this->RTEtsConfigParams);
			$addPassOnParams.='&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams);
			$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}

			// Initializing the target value (RTE)
		$this->setTarget = ($this->curUrlArray['target'] != '-') ? $this->curUrlArray['target'] : '';
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target']))	{
			$this->setTarget=$this->thisConfig['defaultLinkTarget'];
		}

			// Initializing the class value (RTE)
		$this->setClass = ($this->curUrlArray['class'] != '-') ? $this->curUrlArray['class'] : '';

			// Initializing the title value (RTE)
		$this->setTitle = ($this->curUrlArray['title'] != '-') ? $this->curUrlArray['title'] : '';

			// Initializing the params value
		$this->setParams = ($this->curUrlArray['params'] != '-') ? $this->curUrlArray['params'] : '';

			// BEGIN accumulation of header JavaScript:
		$JScode = '
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_class="'.($this->setClass ? '&curUrl[class]='.rawurlencode($this->setClass) : '').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";

			var cur_href="'.($this->curUrlArray['href']?$this->curUrlArray['href']:'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_class = "' . ($this->setClass ? $this->setClass : '') . '";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";
			var cur_params="' . ($this->setParams ? $this->setParams : '') . '";

			function browse_links_setTarget(target)	{	//
				cur_target=target;
				add_target="&curUrl[target]="+escape(target);
			}
			function browse_links_setClass(cssClass) {   //
				cur_class = cssClass;
				add_class = "&curUrl[class]=" + escape(cssClass);
			}
			function browse_links_setTitle(title)	{	//
				cur_title=title;
				add_title="&curUrl[title]="+escape(title);
			}
			function browse_links_setValue(value) {	//
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
			function browse_links_setParams(params)	{	//
				cur_params=params;
				add_params="&curUrl[params]="+escape(params);
			}
		';

		if ($this->mode == 'wizard')	{	// Functions used, if the link selector is in wizard mode (= TCEforms fields)
			if (!$this->areFieldChangeFunctionsValid() && !$this->areFieldChangeFunctionsValid(TRUE)) {
				$this->P['fieldChangeFunc'] = array();
			}
			unset($this->P['fieldChangeFunc']['alert']);
			$update='';
			foreach ($this->P['fieldChangeFunc'] as $k => $v) {
				$update.= '
				window.opener.'.$v;
			}

			$P2=array();
			$P2['itemName']=$this->P['itemName'];
			$P2['formName']=$this->P['formName'];
			$P2['fieldChangeFunc']=$this->P['fieldChangeFunc'];
			$P2['fieldChangeFuncHash'] = t3lib_div::hmac(serialize($this->P['fieldChangeFunc']));
			$P2['params']['allowedExtensions']=$this->P['params']['allowedExtensions'];
			$P2['params']['blindLinkOptions']=$this->P['params']['blindLinkOptions'];
			$addPassOnParams.=t3lib_div::implodeArrayForUrl('P',$P2);

			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					updateValueInMainForm(id + (anchor ? anchor : ""));
					close();
					return false;
				}
				function link_folder(folder)	{	//
					updateValueInMainForm(folder);
					close();
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						returnBeforeCleaned = cur_href;
						if (returnBeforeCleaned.substr(0, 7) == "http://") {
							returnToMainFormValue = returnBeforeCleaned.substr(7);
						} else if (returnBeforeCleaned.substr(0, 7) == "mailto:") {
							if (returnBeforeCleaned.substr(0, 14) == "mailto:mailto:") {
								returnToMainFormValue = returnBeforeCleaned.substr(14);
							} else {
								returnToMainFormValue = returnBeforeCleaned.substr(7);
							}
						} else {
							returnToMainFormValue = returnBeforeCleaned;
						}
						updateValueInMainForm(returnToMainFormValue);
						close();
					}
					return false;
				}
				function checkReference()	{	//
					if (window.opener && window.opener.document && window.opener.document.'.$this->P['formName'].' && window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"] )	{
						return window.opener.document.'.$this->P['formName'].'["'.$this->P['itemName'].'"];
					} else {
						close();
					}
				}
				function updateValueInMainForm(input)	{	//
					var field = checkReference();
					if (field)	{
						if (cur_target == "" && (cur_class != "" || cur_title != "" || cur_params != "")) {
							cur_target = "-";
						}
						if (cur_class == "" && (cur_title != "" || cur_params != "")) {
							cur_class = "-";
						}
						cur_class = cur_class.replace(/[\'\"]/g, "");
						if (cur_class.indexOf(" ") != -1) {
							cur_class = "\"" + cur_class + "\"";
						}
						if (cur_title == "" && cur_params != "") {
 							cur_title = "-";
 						}
						cur_title = cur_title.replace(/(^\")|(\"$)/g, "");
						if (cur_title.indexOf(" ") != -1) {
							cur_title = "\"" + cur_title + "\"";
						}
						if (cur_params) {
							cur_params = cur_params.replace(/\bid\=.*?(\&|$)/, "");
						}
						input = input + " " + cur_target + " " + cur_class + " " + cur_title + " " + cur_params;
						if(field.value && field.className.search(/textarea/) != -1) {
							field.value += "\n" + input;
						} else {
							field.value = input;
						}
						'.$update.'
					}
				}
			';
		} else {	// Functions used, if the link selector is in RTE mode:
			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					var theLink = \''.$this->siteURL.'?id=\'+id+(anchor?anchor:"");
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_folder(folder)	{	//
					var theLink = \''.$this->siteURL.'\'+folder;
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_spec(theLink)	{	//
					self.parent.parent.renderPopup_addLink(theLink, cur_target, cur_class, cur_title);
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						self.parent.parent.renderPopup_addLink(cur_href, cur_target, cur_class, cur_title);
					}
					return false;
				}
			';
		}

			// General "jumpToUrl" function:
		$JScode.='
			function jumpToUrl(URL,anchor)	{	//
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$this->mode.'" : "";
				var theLocation = URL + add_act + add_mode + add_href + add_target + add_class + add_title + add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':'').'+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';


		/**
		 * Splits parts of $this->bparams
		 * @see $bparams
		 */
		$pArr = explode('|',$this->bparams);

			// This is JavaScript especially for the TBE Element Browser!
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';

			// insertElement - Call check function (e.g. for uniqueness handling):
		if ($pArr[4] && $pArr[5]) {
			$JScodeCheck = '
					// Call a check function in the opener window (e.g. for uniqueness handling):
				if (parent.window.opener) {
					var res = parent.window.opener.'.$pArr[5].'("'.addslashes($pArr[4]).'",table,uid,type);
					if (!res.passed) {
						if (res.message) alert(res.message);
						performAction = false;
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			';
		}
			// insertElement - Call helper function:
		if ($pArr[4] && $pArr[6]) {
			$JScodeHelper = '
						// Call helper function to manage data in the opener window:
					if (parent.window.opener) {
						parent.window.opener.'.$pArr[6].'("'.addslashes($pArr[4]).'",table,uid,type,"'.addslashes($pArr[0]).'");
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		}
			// insertElement - perform action commands:
		if ($pArr[4] && $pArr[7]) {
				// Call user defined action function:
			$JScodeAction = '
					if (parent.window.opener) {
						parent.window.opener.'.$pArr[7].'("'.addslashes($pArr[4]).'",table,uid,type);
						focusOpenerAndClose(close);
					} else {
						alert("Error - reference to main window is not set properly!");
						parent.close();
					}
			';
		} elseif ($pArr[0] && !$pArr[1] && !$pArr[2]) {
			$JScodeAction = '
					addElement(filename,table+"_"+uid,fp,close);
			';
		} else {
			$JScodeAction = '
					if (setReferences()) {
						parent.window.opener.group_change("add","'.$pArr[0].'","'.$pArr[1].'","'.$pArr[2].'",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					focusOpenerAndClose(close);
			';
		}

		$JScode.='
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$GLOBALS['BACK_PATH'].'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener && parent.window.opener.content && parent.window.opener.content.document.editform && parent.window.opener.content.document.editform["'.$formFieldName.'"]) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{	//
				var performAction = true;
				'.$JScodeCheck.'
					// Call performing function and finish this action:
				if (performAction) {
						'.$JScodeHelper.$JScodeAction.'
				}
				return false;
			}
			function addElement(elName,elValue,altElValue,close)	{	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
					parent.window.opener.setFormValueFromBrowseWin("'.$pArr[0].'",altElValue?altElValue:elValue,elName);
					focusOpenerAndClose(close);
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
			function focusOpenerAndClose(close)	{	//
				BrowseLinks.focusOpenerAndClose(close);
			}
		';

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScode.= $this->doc->wrapScriptTags($JScode);

			// Debugging:
		if (FALSE) debug(array(
			'pointer' => $this->pointer,
			'act' => $this->act,
			'mode' => $this->mode,
			'curUrlInfo' => $this->curUrlInfo,
			'curUrlArray' => $this->curUrlArray,
			'P' => $this->P,
			'bparams' => $this->bparams,
			'RTEtsConfigParams' => $this->RTEtsConfigParams,
			'expandPage' => $this->expandPage,
			'expandFolder' => $this->expandFolder,
			'PM' => $this->PM,
		),'Internal variables of Script Class:');
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

		switch((string)$this->mode)	{
			case 'db':
				if (isset($this->expandPage))	{
					$data['expandPage']=$this->expandPage;
					$store = TRUE;
				} else {
					$this->expandPage=$data['expandPage'];
				}
			break;
			case 'file':
			case 'filedrag':
			case 'folder':
				if (isset($this->expandFolder))	{
					$data['expandFolder']=$this->expandFolder;
					$store = TRUE;
				} else {
					$this->expandFolder=$data['expandFolder'];
				}
			break;
		}

		return array($data, $store);
	}




	/******************************************************************
	 *
	 * Main functions
	 *
	 ******************************************************************/

	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return	string		Modified content variable.
	 */
	function main_rte($wiz=0)	{
			// Starting content:
		$content=$this->doc->startPage('RTE link');

			// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(
			explode(',','page,file,folder,url,mail,spec'),
			t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1)
		);
		$allowedItems = array_diff(
			$allowedItems,
			t3lib_div::trimExplode(',',$this->P['params']['blindLinkOptions'])
		);

			//call hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$allowedItems = $hookObject->addAllowedItems($allowedItems);
		}

			// if $this->act is not allowed, default to first allowed
		if (!in_array($this->act, $allowedItems)) {
			$this->act = reset($allowedItems);
		}

			// Making menu in top:
		$menuDef = array();
		if (!$wiz)	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $GLOBALS['LANG']->getLL('removeLink', 1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="self.parent.parent.renderPopup_unLink();return false;"';
		}
		if (in_array('page',$allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $GLOBALS['LANG']->getLL('page', 1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array('file',$allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] = $GLOBALS['LANG']->getLL('file', 1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array('folder',$allowedItems)){
			$menuDef['folder']['isActive']  = $this->act == 'folder';
			$menuDef['folder']['label']     = $GLOBALS['LANG']->getLL('folder', 1);
			$menuDef['folder']['url']       = '#';
			$menuDef['folder']['addParams'] = 'onclick="jumpToUrl(\'?act=folder\');return false;"';
		}
		if (in_array('url',$allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $GLOBALS['LANG']->getLL('extUrl', 1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array('mail',$allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $GLOBALS['LANG']->getLL('email', 1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $GLOBALS['LANG']->getLL('special', 1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
		}

			// call hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}

		$content .= $this->doc->getTabMenuRaw($menuDef);

			// Adding the menu and header to the top of page:
		$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';

			// Depending on the current action we will create the actual module content for selecting a link:
		switch($this->act)	{
			case 'mail':
				$extUrl='

			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('emailAddress', 1) . ':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="browse_links_setTarget(\'\');browse_links_setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'url':
				$extUrl='

			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td style="width: 96px;">URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(30).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="' . $GLOBALS['LANG']->getLL('setLink', 1) . '" onclick="browse_links_setValue(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'file':
			case 'folder':
				$foldertree             = t3lib_div::makeInstance('rteFolderTree');
				$foldertree->thisScript = $this->thisScript;
				$tree                   = $foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act'] != $this->act)	{
					$cmpPath = '';
				} elseif (substr(trim($this->curUrlInfo['info']), -1) != '/')	{
					$cmpPath = PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder)) {
						$this->expandFolder = $cmpPath;
					}
				} else {
					$cmpPath = PATH_site.$this->curUrlInfo['info'];
					if (!isset($this->expandFolder) && $this->curUrlInfo['act'] == 'folder') {
						$this->expandFolder = $cmpPath;
					}
				}

				list(, , $specUid) = explode('_', $this->PM);
				$files = $this->expandFolder(
					$foldertree->specUIDmap[$specUid],
					$this->P['params']['allowedExtensions']
				);
				$content.= '

			<!--
				Wrapper table for folder tree / file/folder list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
			break;
			case 'spec':
				if (is_array($this->thisConfig['userLinks.']))	{
					$subcats=array();
					$v=$this->thisConfig['userLinks.'];
					foreach ($v as $k2 => $value) {
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title = $GLOBALS['LANG']->sL($title);
							}
								// Description:
							$description = ($v[$k2i . '.']['description'] ? $GLOBALS['LANG']->sL($v[$k2i . '.']['description'], 1) . '<br />' : '');

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="browse_links_setTarget('".$v[$k2i.'.']['target']."');";
							$v[$k2i.'.']['url'] = str_replace('###_URL###',$this->siteURL,$v[$k2i.'.']['url']);
							if (substr($v[$k2i.'.']['url'],0,7)=='http://' || substr($v[$k2i.'.']['url'],0,7)=='mailto:')	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i.'.']['url'])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i.'.']['url'])."'));";
							}

								// Link:
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');

								// Adding link to menu of user defined links:
							$subcats[$k2i]='
								<tr>
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
								</tr>';
						}
					}

						// Sort by keys:
					ksort($subcats);

						// Add menu to content:
					$content.= '

			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>' . $GLOBALS['LANG']->getLL('special', 1) . '</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
				$pagetree = t3lib_div::makeInstance('rtePageTree');
				$pagetree->thisScript = $this->thisScript;
				$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
				$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
				$pagetree->addField('nav_title');
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();

				// Outputting Temporary DB mount notice:
				if (intval($GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint')))	{
					$link = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => 0))) . '">' .
										$GLOBALS['LANG']->sl('LLL:EXT:lang/locallang_core.xml:labels.temporaryDBmount', 1) .
									'</a>';
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$link,
						'',
						t3lib_FlashMessage::INFO
					);
					$dbmount = $flashMessage->render();
				}

				$content.= '

			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader($GLOBALS['LANG']->getLL('pageTree') . ':') . $dbmount . $tree . '</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
			default:
					//call hook
				foreach($this->hookObjects as $hookObject) {
					$content .= $hookObject->getTab($this->act);
				}
			break;
		}

		$content .= '
			<!--
				Selecting params for link:
			-->
				<form action="" name="lparamsform" id="lparamsform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkParams">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('params', 1) . '</td>
							<td><input type="text" name="lparams" class="typo3-link-input" onchange="browse_links_setParams(this.value);" value="' . htmlspecialchars($this->setParams) . '" /></td>
						</tr>
					</table>
				</form>

			<!--
				Selecting class for link:
			-->
				<form action="" name="lclassform" id="lclassform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkClass">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('class', 1) . '</td>
							<td><input type="text" name="lclass" class="typo3-link-input" onchange="browse_links_setClass(this.value);" value="' . htmlspecialchars($this->setClass) . '" /></td>
						</tr>
					</table>
				</form>

			<!--
				Selecting title for link:
			-->
				<form action="" name="ltitleform" id="ltitleform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTitle">
						<tr>
							<td style="width: 96px;">' . $GLOBALS['LANG']->getLL('title', 1) . '</td>
							<td><input type="text" name="ltitle" class="typo3-link-input" onchange="browse_links_setTitle(this.value);" value="' . htmlspecialchars($this->setTitle) . '" /></td>
						</tr>
					</table>
				</form>
';

			// Target:
		if ($this->act!='mail')	{
			$ltarget='



			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target',1).':</td>
							<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="' . htmlspecialchars($this->setTarget) . '"' . $this->doc->formWidth(10) . ' /></td>
							<td>
								<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">'.$GLOBALS['LANG']->getLL('top',1).'</option>
									<option value="_blank">'.$GLOBALS['LANG']->getLL('newWindow',1).'</option>
								</select>
							</td>
							<td>';
			if (($this->curUrlInfo['act'] == 'page' || $this->curUrlInfo['act'] == 'file' || $this->curUrlInfo['act'] == 'folder') && $this->curUrlArray['href'] && $this->curUrlInfo['act'] == $this->act) {
				$ltarget.='
							<input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" />';
			}

			$selectJS = '
				if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					browse_links_setTarget(document.ltargetform.ltarget.value);
					browse_links_setClass(document.lclassform.lclass.value);
					browse_links_setTitle(document.ltitleform.ltitle.value);
					browse_links_setParams(document.lparamsform.lparams.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}
			';

			$ltarget.='		</td>
						</tr>
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('target_popUpWindow',1).':</td>
							<td colspan="3">
								<select name="popup_width" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_width',1).'</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="'.htmlspecialchars($selectJS).'">
									<option value="0">'.$GLOBALS['LANG']->getLL('target_popUpWindow_height',1).'</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>
					</table>
				</form>';

				// Add "target selector" box to content:
			$content.=$ltarget;

				// Add some space
			$content.='<br /><br />';
		}

			// End page, return content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a page tree and allows you to browse for records
	 *
	 * @return	string		HTML content for the module
	 */
	function main_db()	{

			// Starting content:
		$content=$this->doc->startPage('TBE record selector');

			// Init variable:
		$pArr = explode('|',$this->bparams);

			// Making the browsable pagetree:
		$pagetree = t3lib_div::makeInstance('TBE_PageTree');
		$pagetree->thisScript=$this->thisScript;
		$pagetree->ext_pArrPages = !strcmp($pArr[3],'pages')?1:0;
		$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
		$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
		$pagetree->addField('nav_title');
		$tree=$pagetree->getBrowsableTree();

			// Making the list of elements, if applicable:
		$cElements = $this->TBE_expandPage($pArr[3]);

			// Putting the things together, side by side:
		$content.= '

			<!--
				Wrapper table for page tree / record list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBrecords">
				<tr>
					<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('pageTree').':').$tree.'</td>
					<td class="c-wCell" valign="top">'.$cElements.'</td>
				</tr>
			</table>
			';

			// Add some space
		$content.='<br /><br />';

			// End page, return content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a folder tree, allowing you to browse for files.
	 *
	 * @return	string		HTML content for the module
	 */
	function main_file()	{

			// Starting content:
		$content = $this->doc->startPage('TBE file selector');

			// Init variable:
		$pArr = explode('|',$this->bparams);

			// Create upload/create folder forms, if a path is given:
		$path=$this->expandFolder;
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
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
			$content .= $uploadForm;
		}

			// Getting flag for showing/not showing thumbnails:
		$noThumbs = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInEB');

		if (!$noThumbs)	{
				// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
			$_MOD_MENU = array('displayThumbs' => '');
			$_MCONF['name']='file_list';
			$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
		}
		$noThumbs = $noThumbs ? $noThumbs : !$_MOD_SETTINGS['displayThumbs'];

			// Create folder tree:
		$foldertree = t3lib_div::makeInstance('TBE_FolderTree');
		$foldertree->thisScript=$this->thisScript;
		$foldertree->ext_noTempRecyclerDirs = ($this->mode == 'filedrag');
		$tree=$foldertree->getBrowsableTree();

		list(,,$specUid) = explode('_',$this->PM);

		if ($this->mode=='filedrag')	{
			$files = $this->TBE_dragNDrop($foldertree->specUIDmap[$specUid],$pArr[3]);
		} else {
			$files = $this->TBE_expandFolder($foldertree->specUIDmap[$specUid],$pArr[3],$noThumbs);
		}

			// Putting the parts together, side by side:
		$content.= '

			<!--
				Wrapper table for folder tree / file list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
					<td class="c-wCell" valign="top">'.$files.'</td>
				</tr>
			</table>
			';

			// Adding create folder + upload forms if applicable:
		if (!$GLOBALS['BE_USER']->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
			$content .= $uploadForm;
		}
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
			$content .= $createFolder;
		}

			// Add some space
		$content.='<br /><br />';

			// Setup indexed elements:
		$this->doc->JScode.= $this->doc->wrapScriptTags('BrowseLinks.addElements(' . json_encode($this->elements) . ');');
			// Ending page, returning content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);

		return $content;
	}

	/**
	 * TYPO3 Element Browser: Showing a folder tree, allowing you to browse for folders.
	 *
	 * @return	string		HTML content for the module
	 */
	function main_folder() {

			// Starting content:
		$content = $this->doc->startPage('TBE folder selector');

			// Init variable:
		$parameters = explode('|', $this->bparams);


		$path = $this->expandFolder;
		if (!$path || !@is_dir($path)) {
				// The closest TEMP-path is found
			$path = $this->fileProcessor->findTempFolder().'/';
		}
		if ($path != '/' && @is_dir($path)) {
			$createFolder = $this->createFolder($path);
		} else {
			$createFolder='';
		}

			// Create folder tree:
		$foldertree                         = t3lib_div::makeInstance('TBE_FolderTree');
		$foldertree->thisScript             = $this->thisScript;
		$foldertree->ext_noTempRecyclerDirs = ($this->mode == 'filedrag');
		$tree                                = $foldertree->getBrowsableTree(FALSE);

		list(, , $specUid) = explode('_', $this->PM);

		if($this->mode == 'filedrag') {
			$folders = $this->TBE_dragNDrop(
				$foldertree->specUIDmap[$specUid],
				$parameters[3]
			);
		} else {
			$folders = $this->TBE_expandSubFolders($foldertree->specUIDmap[$specUid]);
		}

			// Putting the parts together, side by side:
		$content.= '

			<!--
				Wrapper table for folder tree / folder list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('folderTree').':').$tree.'</td>
					<td class="c-wCell" valign="top">'.$folders.'</td>
				</tr>
			</table>
			';

			// Adding create folder if applicable:
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.createFoldersInEB')) {
			$content .= $createFolder;
		}

			// Add some space
		$content .= '<br /><br />';

			// Ending page, returning content:
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);

		return $content;
	}

















	/******************************************************************
	 *
	 * Record listing
	 *
	 ******************************************************************/
	/**
	 * For RTE: This displays all content elements on a page and lets you create a link to the element.
	 *
	 * @return	string		HTML output. Returns content only if the ->expandPage value is set (pointing to a page uid to show tt_content records from ...)
	 */
	function expandPage()	{
		$out='';
		$expPageId = $this->expandPage;		// Set page id (if any) to expand

			// If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
		if (!$this->expandPage && $this->curUrlInfo['cElement'])	{
			$expPageId = $this->curUrlInfo['pageid'];	// Set to the current link page id.
		}

			// Draw the record list IF there is a page id to expand:
		if ($expPageId && t3lib_utility_Math::canBeInterpretedAsInteger($expPageId) && $GLOBALS['BE_USER']->isInWebMount($expPageId)) {

				// Set header:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('contentElements').':');

				// Create header for listing, showing the page title/icon:
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = t3lib_BEfunc::getRecordWSOL('pages',$expPageId);
			$picon = t3lib_iconWorks::getSpriteIconForRecord('pages', $mainPageRec);
			$picon .= t3lib_BEfunc::getRecordTitle('pages', $mainPageRec, TRUE);
			$out.=$picon.'<br />';

				// Look up tt_content elements from the expanded page:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'uid,header,hidden,starttime,endtime,fe_group,CType,colPos,bodytext',
							'tt_content',
							'pid='.intval($expPageId).
								t3lib_BEfunc::deleteClause('tt_content').
								t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
							'',
							'colPos,sorting'
						);
			$cc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

				// Traverse list of records:
			$c=0;
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$c++;
				$icon = t3lib_iconWorks::getSpriteIconForRecord('tt_content', $row);
				if ($this->curUrlInfo['act']=='page' && $this->curUrlInfo['cElement']==$row['uid'])	{
					$arrCol='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
				} else {
					$arrCol='';
				}
					// Putting list element HTML together:
				$out.='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
						$arrCol.
						'<a href="#" onclick="return link_typo3Page(\''.$expPageId.'\',\'#'.$row['uid'].'\');">'.
						$icon.
						t3lib_BEfunc::getRecordTitle('tt_content', $row, TRUE) .
						'</a><br />';

					// Finding internal anchor points:
				if (t3lib_div::inList('text,textpic', $row['CType']))	{
					$split = preg_split('/(<a[^>]+name=[\'"]?([^"\'>[:space:]]+)[\'"]?[^>]*>)/i', $row['bodytext'], -1, PREG_SPLIT_DELIM_CAPTURE);

					foreach($split as $skey => $sval)	{
						if (($skey%3)==2)	{
								// Putting list element HTML together:
							$sval = substr($sval,0,100);
							$out.='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/line.gif','width="18" height="16"').' alt="" />'.
									'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/join'.($skey+3>count($split)?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
									'<a href="#" onclick="return link_typo3Page(\''.$expPageId.'\',\'#'.rawurlencode($sval).'\');">'.
									htmlspecialchars(' <A> '.$sval).
									'</a><br />';
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * For TYPO3 Element Browser: This lists all content elements from the given list of tables
	 *
	 * @param	string		Commalist of tables. Set to "*" if you want all tables.
	 * @return	string		HTML output.
	 */
	function TBE_expandPage($tables)	{
		$out='';
		if ($this->expandPage >= 0 && t3lib_utility_Math::canBeInterpretedAsInteger($this->expandPage) && $GLOBALS['BE_USER']->isInWebMount($this->expandPage)) {

				// Set array with table names to list:
			if (!strcmp(trim($tables),'*'))	{
				$tablesArr = array_keys($GLOBALS['TCA']);
			} else {
				$tablesArr = t3lib_div::trimExplode(',',$tables,1);
			}
			reset($tablesArr);

				// Headline for selecting records:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('selectRecords').':');

				// Create the header, showing the current page for which the listing is. Includes link to the page itself, if pages are amount allowed tables.
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = t3lib_BEfunc::getRecordWSOL('pages',$this->expandPage);
			$ATag='';
			$ATag_e='';
			$ATag2='';
			$picon = '';
			if (is_array($mainPageRec)) {
				$picon = t3lib_iconWorks::getSpriteIconForRecord('pages', $mainPageRec);
				if (in_array('pages', $tablesArr)) {
					$ATag = "<a href=\"#\" onclick=\"return insertElement('pages', '" . $mainPageRec['uid'] .
						"', 'db', " . t3lib_div::quoteJSvalue($mainPageRec['title']) . ", '', '', '','',1);\">";
					$ATag2 = "<a href=\"#\" onclick=\"return insertElement('pages', '" . $mainPageRec['uid'] .
						"', 'db', " . t3lib_div::quoteJSvalue($mainPageRec['title']) . ", '', '', '','',0);\">";
					$ATag_alt = substr($ATag, 0, -4) . ",'',1);\">";
					$ATag_e = '</a>';
				}
			}
			$pBicon = ($ATag2 ? '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' alt="" />' : '');
			$pText=htmlspecialchars(t3lib_div::fixed_lgd_cs($mainPageRec['title'],$titleLen));
			$out.=$picon.$ATag2.$pBicon.$ATag_e.$ATag.$pText.$ATag_e.'<br />';

				// Initialize the record listing:
			$id = $this->expandPage;
			$pointer = t3lib_utility_Math::forceIntegerInRange($this->pointer,0,100000);
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$pageinfo = t3lib_BEfunc::readPageAccess($id,$perms_clause);
			$table='';

				// Generate the record list:
			/** @var $dblist TBE_browser_recordList */
			$dblist = t3lib_div::makeInstance('TBE_browser_recordList');
			$dblist->thisScript=$this->thisScript;
			$dblist->backPath = $GLOBALS['BACK_PATH'];
			$dblist->thumbs = 0;
			$dblist->calcPerms = $GLOBALS['BE_USER']->calcPerms($pageinfo);
			$dblist->noControlPanels=1;
			$dblist->clickMenuEnabled=0;
			$dblist->tableList=implode(',',$tablesArr);

			$dblist->start($id,t3lib_div::_GP('table'),$pointer,
				t3lib_div::_GP('search_field'),
				t3lib_div::_GP('search_levels'),
				t3lib_div::_GP('showLimit')
			);
			$dblist->setDispFields();
			$dblist->generateList();
			$dblist->writeBottom();

				//	Add the HTML for the record list to output variable:
			$out.=$dblist->HTMLcode;

				// Add support for fieldselectbox in singleTableMode
			if ($dblist->table) {
				$out.= $dblist->fieldSelectBox($dblist->table);
			}

			$out.=$dblist->getSearchBox();
		}

			// Return accumulated content:
		return $out;
	}


	/**
	 * Render list of folders inside a folder.
	 *
	 * @param	string		string of the current folder
	 * @return	string		HTML output
	 */
	function TBE_expandSubFolders($expandFolder=0) {
		$content = '';

		$expandFolder = $expandFolder ?
			$expandFolder :
			$this->expandFolder;

		if($expandFolder && $this->checkFolder($expandFolder)) {
			if(t3lib_div::isFirstPartOfStr($expandFolder, PATH_site)) {
				$rootFolder = substr($expandFolder, strlen(PATH_site));
			}

			$folders = array();

				// Listing the folders:
			$folders = t3lib_div::get_dirs($expandFolder);
			if(count($folders) > 0) {
				foreach($folders as $index => $folder) {
					$folders[$index] = $rootFolder.$folder.'/';
				}
			}
			$content.= $this->folderList($rootFolder, $folders);
		}

			// Return accumulated content for folderlisting:
		return $content;
	}











	/******************************************************************
	 *
	 * File listing
	 *
	 ******************************************************************/
	/**
	 * For RTE: This displays all files from folder. No thumbnails shown
	 *
	 * @param	string		The folder path to expand
	 * @param	string		List of fileextensions to show
	 * @return	string		HTML output
	 */
	function expandFolder($expandFolder=0,$extensionList='')	{
		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{

				// Create header for filelisting:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('files').':');

				// Prepare current path value for comparison (showing red arrow)
			if (!$this->curUrlInfo['value'])	{
				$cmpPath='';
			} else {
				$cmpPath=PATH_site.$this->curUrlInfo['info'];
			}


				// Create header element; The folder from which files are listed.
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$picon='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
			$picon='<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($expandFolder,strlen(PATH_site))).'\');">'.$picon.'</a>';
			if ($this->curUrlInfo['act'] == 'folder' && $cmpPath == $expandFolder)	{
				$out.= '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="c-blinkArrowL" alt="" />';
			}
			$out.=$picon.'<br />';

				// Get files from the folder:
			if ($this->mode == 'wizard' && $this->act == 'folder') {
				$files = t3lib_div::get_dirs($expandFolder);
			} else {
				$files = t3lib_div::getFilesInDir($expandFolder, $extensionList, 1, 1);	// $extensionList='', $prependPath=0, $order='')
			}

			$c=0;
			$cc=count($files);
			if (is_array($files))	{
				foreach($files as $filepath)	{
					$c++;
					$fI=pathinfo($filepath);

					if ($this->mode == 'wizard' && $this->act == 'folder') {
						$filepath = $expandFolder.$filepath.'/';
						$icon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/i/_icon_webfolders.gif', 'width="18" height="16"') . ' alt="" />';
					} else {
							// File icon:
						$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));

							// Get size and icon:
						$size = ' (' . t3lib_div::formatSize(filesize($filepath)) . 'bytes)';
						$icon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/fileicons/' . $icon . '', 'width="18" height="16"') . ' title="' . htmlspecialchars($fI['basename'] . $size) . '" alt="" />';
					}

						// If the listed file turns out to be the CURRENT file, then show blinking arrow:
					if (($this->curUrlInfo['act'] == 'file' || $this->curUrlInfo['act'] == 'folder') && $cmpPath == $filepath) {
						$arrCol='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
					} else {
						$arrCol='';
					}

						// Put it all together for the file element:
					$out.='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
							$arrCol.
							'<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($filepath,strlen(PATH_site))).'\');">'.
							$icon.
							htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).
							'</a><br />';
				}
			}
		}
		return $out;
	}

	/**
	 * For TYPO3 Element Browser: Expand folder of files.
	 *
	 * @param	string		The folder path to expand
	 * @param	string		List of fileextensions to show
	 * @param	boolean		Whether to show thumbnails or not. If set, no thumbnails are shown.
	 * @return	string		HTML output
	 */
	function TBE_expandFolder($expandFolder=0,$extensionList='',$noThumbs=0)	{
		$extensionList = ($extensionList == '*') ? '' : $extensionList;
		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{
				// Listing the files:
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')
			$out.= $this->fileList($files, $expandFolder, $noThumbs);
		}

			// Return accumulated content for filelisting:
		return $out;
	}

	/**
	 * Render list of files.
	 *
	 * @param	array		List of files. See t3lib_div::getFilesInDir
	 * @param	string		If set a header with a folder icon and folder name are shown
	 * @param	boolean		Whether to show thumbnails or not. If set, no thumbnails are shown.
	 * @return	string		HTML output
	 */
	function fileList($files, $folderName='', $noThumbs=0) {
		$out='';

			// Listing the files:
		if (is_array($files))	{

				// Create headline (showing number of files):
			$filesCount = count($files);
			$out.=$this->barheader(sprintf($GLOBALS['LANG']->getLL('files').' (%s):', $filesCount));
			$out .= '<div id="filelist">';
			$out.=$this->getBulkSelector($filesCount);

			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

				// Create the header of current folder:
			if($folderName) {
				$picon = '<div id="currentFolderHeader">';
				$picon .= '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/i/_icon_webfolders.gif', 'width="18" height="16"') . ' alt="" /> ';
				$picon .= htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($folderName), $titleLen));
				$picon .= '</div>';
				$out .= $picon;
			}

				// Init graphic object for reading file dimensions:
			/** @var $imgObj t3lib_stdGraphic */
			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				// Traverse the file list:
			$lines=array();
			foreach($files as $filepath)	{
				$fI=pathinfo($filepath);

					// Thumbnail/size generation:
				if (t3lib_div::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),strtolower($fI['extension'])) && !$noThumbs)	{
					$imgInfo = $imgObj->getImageDimensions($filepath);
					$pDim = $imgInfo[0].'x'.$imgInfo[1].' pixels';
					$clickIcon = t3lib_BEfunc::getThumbNail($GLOBALS['BACK_PATH'].'thumbs.php',$filepath,'hspace="5" vspace="5" border="1"');
				} else {
					$clickIcon = '';
					$pDim = '';
				}

					// Create file icon:
				$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
				$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes'.($pDim?', '.$pDim:'').')';
				$icon = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/fileicons/'.$ficon,'width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" class="absmiddle" alt="" />';

					// Create links for adding the file:
				if (strstr($filepath,',') || strstr($filepath,'|'))	{	// In case an invalid character is in the filepath, display error message:
					$eMsg = $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('invalidChar'), ', |'));
					$ATag = $ATag_alt = "<a href=\"#\" onclick=\"alert(".$eMsg.");return false;\">";
					$bulkCheckBox = '';
				} else {	// If filename is OK, just add it:
					$filesIndex = count($this->elements);
					$this->elements['file_'.$filesIndex] = array(
						'md5' => t3lib_div::shortMD5($filepath),
						'type' => 'file',
						'fileName' => $fI['basename'],
						'filePath' => $filepath,
						'fileExt' => $fI['extension'],
						'fileIcon' => $ficon,
					);
					$ATag = "<a href=\"#\" onclick=\"return BrowseLinks.File.insertElement('file_$filesIndex');\">";
					$ATag_alt = substr($ATag,0,-4).",1);\">";
					$bulkCheckBox = '<input type="checkbox" class="typo3-bulk-item" name="file_'.$filesIndex.'" value="0" /> ';
				}
				$ATag_e='</a>';

					// Create link to showing details about the file in a window:
				$Ahref = $GLOBALS['BACK_PATH'].'show_item.php?table='.rawurlencode($filepath).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$ATag2='<a href="'.htmlspecialchars($Ahref).'">';
				$ATag2_e='</a>';

					// Combine the stuff:
				$filenameAndIcon=$bulkCheckBox.$ATag_alt.$icon.htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).$ATag_e;

					// Show element:
				if ($pDim)	{		// Image...
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>' . $ATag . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList',1) . '" alt="" />' . $ATag_e . '</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/zoom2.gif', 'width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td nowrap="nowrap">&nbsp;'.$pDim.'</td>
						</tr>';
					$lines[]='
						<tr>
							<td class="filelistThumbnail" colspan="4">' . $ATag_alt . $clickIcon . $ATag_e . '</td>
						</tr>';
				} else {
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>' . $ATag . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif','width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList',1) . '" alt="" />' . $ATag_e . '</td>
							<td nowrap="nowrap">' . ($ATag2 . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/zoom2.gif','width="12" height="12"') . ' title="' . $GLOBALS['LANG']->getLL('info', 1) . '" alt="" /> ' . $GLOBALS['LANG']->getLL('info', 1) . $ATag2_e) . '</td>
							<td>&nbsp;</td>
						</tr>';
				}
				$lines[]='
						<tr>
							<td colspan="3"><img src="clear.gif" width="1" height="3" alt="" /></td>
						</tr>';
			}

				// Wrap all the rows in table tags:
			$out.='



		<!--
			File listing
		-->
				<table cellpadding="0" cellspacing="0" id="typo3-fileList">
					'.implode('',$lines).'
				</table>';
		}
			// Return accumulated content for filelisting:
		$out .= '</div>';
		return $out;
	}

	/**
	 * Render list of folders.
	 *
	 * @param	array		List of folders. See t3lib_div::get_dirs
	 * @param	string		If set a header with a folder icon and folder name are shown
	 * @return	string		HTML output
	 */
	function folderList($baseFolder, $folders) {
		$content = '';

			// Create headline (showing number of folders):
		$content.=$this->barheader(
			sprintf($GLOBALS['LANG']->getLL('folders').' (%s):',count($folders))
		);

		$titleLength = intval($GLOBALS['BE_USER']->uc['titleLen']);

			// Create the header of current folder:
		if($baseFolder) {
			if (strstr($baseFolder, ',') || strstr($baseFolder, '|'))	{
					// In case an invalid character is in the filepath, display error message:
				$errorMessage     = $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('invalidChar'), ', |'));
				$aTag = $aTag_alt = "<a href=\"#\" onclick=\"alert(".$errorMessage.");return false;\">";
			} else {
					// If foldername is OK, just add it:
				$aTag = "<a href=\"#\" onclick=\"return insertElement('','".rawurlencode($baseFolder)."', 'folder', '".rawurlencode($baseFolder)."', unescape('".rawurlencode($baseFolder)."'), '".$fI['extension']."', '".$ficon."');\">";
				$aTag_alt = substr($aTag,0,-4).",'',1);\">";
			}
			$aTag_e = '</a>';

				// add the foder icon
			$folderIcon = $aTag_alt;
			$folderIcon.= '<img'.t3lib_iconWorks::skinImg(
				$GLOBALS['BACK_PATH'],
				'gfx/i/_icon_webfolders.gif','width="18" height="16"'
			).' alt="" />';
			$folderIcon.= htmlspecialchars(
				t3lib_div::fixed_lgd_cs(basename($baseFolder),$titleLength)
			);
			$folderIcon.= $aTag_e;

			$content.=$folderIcon.'<br />';
		}

			// Listing the folders:
		if(is_array($folders)) {
			if(count($folders) > 0) {
					// Traverse the folder list:
				$lines = array();
				foreach($folders as $folderPath)	{
					$pathInfo = pathinfo($folderPath);

						// Create folder icon:
					$icon = '<img src="clear.gif" width="16" height="16" alt="" /><img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/i/_icon_webfolders.gif','width="16" height="16"').' title="'.htmlspecialchars($pathInfo['basename'].$size).'" class="absmiddle" alt="" />';

						// Create links for adding the folder:
					if($this->P['itemName'] != '' && $this->P['formName'] != '') {
						$aTag = "<a href=\"#\" onclick=\"return set_folderpath(unescape('".rawurlencode($folderPath)."'));\">";
					} else {
						$aTag = "<a href=\"#\" onclick=\"return insertElement('','".rawurlencode($folderPath)."', 'folder', '".rawurlencode($folderPath)."', unescape('".rawurlencode($folderPath)."'), '".$pathInfo['extension']."', '".$ficon."');\">";
					}

					if (strstr($folderPath,',') || strstr($folderPath,'|'))	{
							// In case an invalid character is in the filepath, display error message:
						$errorMessage     = $GLOBALS['LANG']->JScharCode(sprintf($GLOBALS['LANG']->getLL('invalidChar'), ', |'));
						$aTag = $aTag_alt = "<a href=\"#\" onclick=\"alert(".$errorMessage.");return false;\">";
					} else {
							// If foldername is OK, just add it:
						$aTag_alt = substr($aTag,0,-4).",'',1);\">";
					}
					$aTag_e='</a>';

						// Combine icon and folderpath:
					$foldernameAndIcon = $aTag_alt.$icon.htmlspecialchars(
						t3lib_div::fixed_lgd_cs(basename($folderPath),$titleLength)
					).$aTag_e;

					if($this->P['itemName'] != '') {
						$lines[] = '
							<tr class="bgColor4">
								<td nowrap="nowrap">'.$foldernameAndIcon.'&nbsp;</td>
								<td>&nbsp;</td>
							</tr>';
					} else {
						$lines[] = '
							<tr class="bgColor4">
								<td nowrap="nowrap">'.$foldernameAndIcon.'&nbsp;</td>
								<td>' . $aTag . '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/plusbullet2.gif', 'width="18" height="16"') . ' title="' . $GLOBALS['LANG']->getLL('addToList', 1) . '" alt="" />' . $aTag_e .' </td>
								<td>&nbsp;</td>
							</tr>';
					}

					$lines[] = '
							<tr>
								<td colspan="3"><img src="clear.gif" width="1" height="3" alt="" /></td>
							</tr>';
				}
			}

				// Wrap all the rows in table tags:
			$content.='

		<!--
			Folder listing
		-->
				<table border="0" cellpadding="0" cellspacing="1" id="typo3-folderList">
					'.implode('', $lines).'
				</table>';
		}

			// Return accumulated content for folderlisting:
		return $content;
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
		$extensionList = ($extensionList == '*') ? '' : $extensionList;
		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
		$out='';
		if ($expandFolder && $this->checkFolder($expandFolder))	{
			if ($this->isWebFolder($expandFolder))	{

					// Read files from directory:
				$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')
				if (is_array($files))	{
					$out.=$this->barheader(sprintf($GLOBALS['LANG']->getLL('files').' (%s):',count($files)));

					$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
					$picon='<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
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
							$icon = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/fileicons/'.$ficon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';
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
														'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/icon_warning2.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->getLL('clickToRedrawFullSize',1).'" alt="" />'.
														'</a>':'').
									$pDim.'&nbsp;</td>
								</tr>';

							$lines[]='
								<tr>
									<td colspan="2"><img src="'.$iurl.'" width="'.$IW.'" height="'.$IH.'" border="1" alt="" /></td>
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












	/******************************************************************
	 *
	 * Miscellaneous functions
	 *
	 ******************************************************************/


	/**
	 * Verifies that a path is a web-folder:
	 *
	 * @param	string		Absolute filepath
	 * @return	boolean		If the input path is found in PATH_site then it returns TRUE.
	 */
	function isWebFolder($folder)	{
		$folder = rtrim($folder, '/').'/';
		return t3lib_div::isFirstPartOfStr($folder,PATH_site) ? TRUE : FALSE;
	}

	/**
	 * Checks, if a path is within the mountpoints of the backend user
	 *
	 * @param	string		Absolute filepath
	 * @return	boolean		If the input path is found in the backend users filemounts, then return TRUE.
	 */
	function checkFolder($folder)	{
		return $this->fileProcessor->checkPathAgainstMounts(rtrim($folder, '/') . '/') ? TRUE : FALSE;
	}

	/**
	 * Checks, if a path is within a read-only mountpoint of the backend user
	 *
	 * @param	string		Absolute filepath
	 * @return	boolean		If the input path is found in the backend users filemounts and if the filemount is of type readonly, then return TRUE.
	 */
	function isReadOnlyFolder($folder) {
		return ($GLOBALS['FILEMOUNTS'][$this->fileProcessor->checkPathAgainstMounts(rtrim($folder, '/') . '/')]['type'] == 'readonly');
	}

	/**
	 * Prints a 'header' where string is in a tablecell
	 *
	 * @param	string		The string to print in the header. The value is htmlspecialchars()'ed before output.
	 * @return	string		The header HTML (wrapped in a table)
	 */
	function barheader($str)	{
		return '
			<!-- Bar header: -->
			<h3>' . htmlspecialchars($str) . '</h3>
			';
	}

	/**
	 * Displays a message box with the input message
	 *
	 * @param	string		Input message to show (will be htmlspecialchars()'ed inside of this function)
	 * @param	string		Icon filename body from gfx/ (default is "icon_note") - meant to allow change to warning type icons...
	 * @return	string		HTML for the message (wrapped in a table).
	 */
	function getMsgBox($in_msg,$icon='icon_note')	{
		$msg = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/'.$icon.'.gif','width="18" height="16"').' alt="" />'.htmlspecialchars($in_msg);
		$msg = '

			<!--
				Message box:
			-->
			<table cellspacing="0" class="bgColor4" id="typo3-msgBox">
				<tr>
					<td>'.$msg.'</td>
				</tr>
			</table>
			';
		return $msg;
	}

	/**
	 * For RTE/link: This prints the 'currentUrl'
	 *
	 * @param	string		URL value.  The value is htmlspecialchars()'ed before output.
	 * @return	string		HTML content, wrapped in a table.
	 */
	function printCurrentUrl($str)	{
		if (strlen($str)) {
			return '
				<!-- Print current URL -->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-curUrl">
					<tr>
						<td>' . $GLOBALS['LANG']->getLL('currentLink',1) . ': ' .htmlspecialchars(rawurldecode($str)) . '</td>
					</tr>
				</table>';
		} else {
			return '';
		}
	}

	/**
	 * For RTE/link: Parses the incoming URL and determines if it's a page, file, external or mail address.
	 *
	 * @param	string		HREF value tp analyse
	 * @param	string		The URL of the current website (frontend)
	 * @return	array		Array with URL information stored in assoc. keys: value, act (page, file, spec, mail), pageid, cElement, info
	 */
	function parseCurUrl($href,$siteUrl)	{
		$href = trim($href);
		if ($href)	{
			$info=array();

				// Default is "url":
			$info['value']=$href;
			$info['act']='url';

			$specialParts = explode('#_SPECIAL',$href);
			if (count($specialParts)==2)	{	// Special kind (Something RTE specific: User configurable links through: "userLinks." from ->thisConfig)
				$info['value']='#_SPECIAL'.$specialParts[1];
				$info['act']='spec';
			} elseif (t3lib_div::isFirstPartOfStr($href,$siteUrl))	{	// If URL is on the current frontend website:
				$rel = substr($href,strlen($siteUrl));
				if (file_exists(PATH_site.rawurldecode($rel)))	{	// URL is a file, which exists:
					$info['value']=rawurldecode($rel);
					if (@is_dir(PATH_site . $info['value'])) {
						$info['act'] = 'folder';
					} else {
						$info['act'] = 'file';
					}
				} else {	// URL is a page (id parameter)
					$uP=parse_url($rel);
					if (!trim($uP['path']))	{
						$pp = preg_split('/^id=/', $uP['query']);
						$pp[1] = preg_replace( '/&id=[^&]*/', '', $pp[1]);
						$parameters = explode('&', $pp[1]);
						$id = array_shift($parameters);
						if ($id)	{
								// Checking if the id-parameter is an alias.
							if (!t3lib_utility_Math::canBeInterpretedAsInteger($id))	{
								list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$id);
								$id=intval($idPartR['uid']);
							}

							$pageRow = t3lib_BEfunc::getRecordWSOL('pages',$id);
							$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
							$info['value']=$GLOBALS['LANG']->getLL('page',1)." '".htmlspecialchars(t3lib_div::fixed_lgd_cs($pageRow['title'],$titleLen))."' (ID:".$id.($uP['fragment']?', #'.$uP['fragment']:'').')';
							$info['pageid']=$id;
							$info['cElement']=$uP['fragment'];
							$info['act']='page';
							$info['query'] = $parameters[0]?'&'.implode('&', $parameters):'';
						}
					}
				}
			} else {	// Email link:
				if (strtolower(substr($href,0,7))=='mailto:')	{
					$info['value']=trim(substr($href,7));
					$info['act']='mail';
				}
			}
			$info['info'] = $info['value'];
		} else {	// NO value inputted:
			$info=array();
			$info['info']=$GLOBALS['LANG']->getLL('none');
			$info['value']='';
			$info['act']='page';
		}

			// let the hook have a look
		foreach($this->hookObjects as $hookObject) {
			$info = $hookObject->parseCurrentUrl($href, $siteUrl, $info);
		}

		return $info;
	}

	/**
	 * For TBE: Makes an upload form for uploading files to the filemount the user is browsing.
	 * The files are uploaded to the tce_file.php script in the core which will handle the upload.
	 *
	 * @param	string		Absolute filepath on server to which to upload.
	 * @return	string		HTML for an upload form.
	 */
	function uploadForm($path)	{
		if ($this->isReadOnlyFolder($path)) return '';

			// Read configuration of upload field count
		$userSetting = $GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.uploadFieldsInLinkBrowser');
		$count = isset($userSetting) ? $userSetting : 3;
		if ($count === '0') {
			return '';
		}
		$count = intval($count) == 0 ? 3 : intval($count);

			// Create header, showing upload path:
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code = '

			<!--
				Form, for uploading files:
			-->
			<form action="' . $GLOBALS['BACK_PATH'] . 'tce_file.php" method="post" name="editform" id="typo3-uplFilesForm" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-uplFiles">
					<tr>
						<td>' . $this->barheader($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.pagetitle', 1) . ':') . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $GLOBALS['LANG']->getLL('path', 1) . ':</strong> ' . htmlspecialchars($header) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';

			// Traverse the number of upload fields (default is 3):
		for ($a=1;$a<=$count;$a++)	{
			$code.='<input type="file" name="upload_'.$a.'"'.$this->doc->formWidth(35).' size="50" />
				<input type="hidden" name="file[upload]['.$a.'][target]" value="'.htmlspecialchars($path).'" />
				<input type="hidden" name="file[upload]['.$a.'][data]" value="'.$a.'" /><br />';
		}

			// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript.'?act='.$this->act.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
		$code .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirectValue) . '" />';

		$code.='
			<div id="c-override">
				<label><input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" /> ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:overwriteExistingFiles', 1) . '</label>
			</div>
			<input type="submit" name="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit', 1) . '" />
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
		if ($this->isReadOnlyFolder($path)) return '';

			// Don't show Folder-create form if it's denied
		if ($GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.hideCreateFolder')) {
			return '';
		}
			// Create header, showing upload path:
		$header = t3lib_div::isFirstPartOfStr($path,PATH_site)?substr($path,strlen(PATH_site)):$path;
		$code = '

			<!--
				Form, for creating new folders:
			-->
			<form action="' . $GLOBALS['BACK_PATH'] . 'tce_file.php" method="post" name="editform2" id="typo3-crFolderForm">
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-crFolder">
					<tr>
						<td>' . $this->barheader($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_newfolder.php.pagetitle') . ':') . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell"><strong>' . $GLOBALS['LANG']->getLL('path', 1) . ':</strong> ' . htmlspecialchars($header) . '</td>
					</tr>
					<tr>
						<td class="c-wCell c-hCell">';

			// Create the new-folder name field:
		$a=1;
		$code.='<input'.$this->doc->formWidth(20).' type="text" name="file[newfolder]['.$a.'][data]" />'.
				'<input type="hidden" name="file[newfolder]['.$a.'][target]" value="'.htmlspecialchars($path).'" />';

			// Make footer of upload form, including the submit button:
		$redirectValue = $this->thisScript.'?act='.$this->act.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
		$code.='<input type="hidden" name="redirect" value="'.htmlspecialchars($redirectValue).'" />'.
				'<input type="submit" name="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_newfolder.php.submit',1).'" />';

		$code.='</td>
					</tr>
				</table>
			</form>';

		return $code;
	}

	/**
	 * Get the HTML data required for a bulk selection of files of the TYPO3 Element Browser.
	 *
	 * @param	integer		$filesCount: Number of files currently displayed
	 * @return	string		HTML data required for a bulk selection of files - if $filesCount is 0, nothing is returned
	 */
	function getBulkSelector($filesCount) {
		if ($filesCount) {
			$labelToggleSelection = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.php:toggleSelection',1);
			$labelImportSelection = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_browse_links.php:importSelection',1);

			// Getting flag for showing/not showing thumbnails:
			$noThumbsInEB = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInEB');

			$out = $this->doc->spacer(15).'<div>' .
					'<a href="#" onclick="BrowseLinks.Selector.toggle()">' .
						'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/clip_select.gif','width="12" height="12"').' title="'.$labelToggleSelection.'" alt="" /> ' .
						$labelToggleSelection.'</a>'.$this->doc->spacer(5) .
					'<a href="#" onclick="BrowseLinks.Selector.handle()">' .
						'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/import.gif','width="12" height="12"').' title="'.$labelImportSelection.'" alt="" /> ' .
						$labelImportSelection.'</a>' .
				'</div>';

			$thumbNailCheck = '';
			if (!$noThumbsInEB) {
				$path = $this->expandFolder;
				if (!$path || !@is_dir($path)) {
						// The closest TEMP-path is found
					$path = $this->fileProcessor->findTempFolder() . '/';
				}
					// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
				$_MOD_MENU = array('displayThumbs' => '');
				$_MCONF['name'] = 'file_list';
				$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
				$addParams = '&act=' . $this->act . '&mode=' . $this->mode . '&expandFolder=' . rawurlencode($path) . '&bparams=' . rawurlencode($this->bparams);
				$thumbNailCheck = t3lib_BEfunc::getFuncCheck('', 'SET[displayThumbs]', $_MOD_SETTINGS['displayThumbs'], $this->thisScript, $addParams, 'id="checkDisplayThumbs"') . ' <label for="checkDisplayThumbs">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.php:displayThumbs', 1) . '</label>';
				$out .= $this->doc->spacer(5) . $thumbNailCheck . $this->doc->spacer(15);
			} else {
				$out .= $this->doc->spacer(15);
			}
		}
		return $out;
	}

	/**
	 * Determines whether submitted field change functions are valid
	 * and are coming from the system and not from an external abuse.
	 *
	 * @param boolean $allowFlexformSections Whether to handle flexform sections differently
	 * @return boolean Whether the submitted field change functions are valid
	 */
	protected function areFieldChangeFunctionsValid($handleFlexformSections = FALSE) {
		$result = FALSE;

		if (isset($this->P['fieldChangeFunc']) && is_array($this->P['fieldChangeFunc']) && isset($this->P['fieldChangeFuncHash'])) {
			$matches = array();
			$pattern = '#\[el\]\[(([^]-]+-[^]-]+-)(idx\d+-)([^]]+))\]#i';

			$fieldChangeFunctions = $this->P['fieldChangeFunc'];

				// Special handling of flexform sections:
				// Field change functions are modified in JavaScript, thus the hash is always invalid
			if ($handleFlexformSections && preg_match($pattern, $this->P['itemName'], $matches)) {
				$originalName = $matches[1];
				$cleanedName = $matches[2] . $matches[4];

				foreach ($fieldChangeFunctions as &$value) {
					$value = str_replace($originalName, $cleanedName, $value);
				}
				unset($value);
			}

			$result = ($this->P['fieldChangeFuncHash'] === t3lib_div::hmac(serialize($fieldChangeFunctions)));
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php']);
}

?>
