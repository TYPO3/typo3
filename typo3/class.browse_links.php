<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  157: class TBE_browser_recordList extends localRecordList
 *  168:     function listURL($altId='',$table=-1,$exclList='')
 *  187:     function ext_addP()
 *  204:     function linkWrapItems($table,$uid,$code,$row)
 *  237:     function linkWrapTable($table,$code)
 *
 *
 *  254: class localPageTree extends t3lib_browseTree
 *  261:     function localPageTree()
 *  275:     function wrapTitle($title,$v,$ext_pArrPages='')
 *  290:     function printTree($treeArr='')
 *  340:     function ext_isLinkable($doktype,$uid)
 *  354:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  371:     function wrapIcon($icon,$row)
 *
 *
 *  390: class rtePageTree extends localPageTree
 *
 *
 *  407: class TBE_PageTree extends localPageTree
 *  416:     function ext_isLinkable($doktype,$uid)
 *  428:     function wrapTitle($title,$v,$ext_pArrPages)
 *
 *
 *  454: class localFolderTree extends t3lib_folderTree
 *  464:     function wrapTitle($title,$v)
 *  479:     function ext_isLinkable($v)
 *  496:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  511:     function printTree($treeArr='')
 *
 *
 *  577: class rteFolderTree extends localFolderTree
 *
 *
 *  593: class TBE_FolderTree extends localFolderTree
 *  602:     function ext_isLinkable($v)
 *  615:     function wrapTitle($title,$v)
 *
 *
 *  636: class SC_browse_links
 *  729:     function init()
 *  984:     function main()
 * 1026:     function printContent()
 *
 *              SECTION: Main functions
 * 1057:     function main_rte($wiz=0)
 * 1336:     function main_db()
 * 1380:     function main_file()
 *
 *              SECTION: Record listing
 * 1489:     function expandPage()
 * 1568:     function TBE_expandPage($tables)
 *
 *              SECTION: File listing
 * 1661:     function expandFolder($expandFolder=0,$extensionList='')
 * 1730:     function TBE_expandFolder($expandFolder=0,$extensionList='',$noThumbs=0)
 * 1753:     function fileList($files, $folderName='', $noThumbs=0)
 * 1870:     function TBE_dragNDrop($expandFolder=0,$extensionList='')
 *
 *              SECTION: Miscellaneous functions
 * 1997:     function isWebFolder($folder)
 * 2008:     function checkFolder($folder)
 * 2021:     function barheader($str)
 * 2038:     function getMsgBox($in_msg,$icon='icon_note')
 * 2060:     function printCurrentUrl($str)
 * 2080:     function parseCurUrl($href,$siteUrl)
 * 2142:     function uploadForm($path)
 * 2195:     function createFolder($path)
 *
 * TOTAL FUNCTIONS: 38
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_t3lib.'class.t3lib_browsetree.php');
require_once (PATH_t3lib.'class.t3lib_foldertree.php');
require_once (PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');


	// Include classes
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_recordlist.php');
require_once (PATH_typo3.'/class.db_list.inc');
require_once (PATH_typo3.'/class.db_list_extra.inc');



















/**
 * Local version of the record list.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_browser_recordList extends localRecordList {
	var $thisScript = 'browse_links.php';

	/**
	 * Initializes the script path
	 *
	 * @return void
	 */
	function TBE_browser_recordList () {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
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
		$str = '&act='.$GLOBALS['SOBE']->act.
				'&mode='.$GLOBALS['SOBE']->mode.
				'&expandPage='.$GLOBALS['SOBE']->expandPage.
				'&bparams='.rawurlencode($GLOBALS['SOBE']->bparams);
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
		global $TCA, $BACK_PATH;

		if (!$code) {
			$code = '<i>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</i>';
		} else {
			$code = htmlspecialchars(t3lib_div::fixed_lgd_cs($code,$this->fixedL));
		}

		$titleCol = $TCA[$table]['ctrl']['label'];
		$title = $row[$titleCol];

		$ficon = t3lib_iconWorks::getIcon($table,$row);
		$aOnClick = "return insertElement('".$table."', '".$row['uid']."', 'db', ".t3lib_div::quoteJSvalue($title).", '', '', '".$ficon."');";
		$ATag = '<a href="#" onclick="'.$aOnClick.'">';
		$ATag_alt = substr($ATag,0,-4).',\'\',1);">';
		$ATag_e = '</a>';

		return $ATag.
				'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' title="'.$GLOBALS['LANG']->getLL('addToList',1).'" alt="" />'.
				$ATag_e.
				$ATag_alt.
				$code.
				$ATag_e;
	}

	/**
	 * Returns the title (based on $code) of a table ($table) without a link
	 *
	 * @param	string		Table name
	 * @param	string		Table label
	 * @return	string		The linked table label
	 */
	function linkWrapTable($table,$code)	{
		return $code;
	}
}






/**
 * Class which generates the page tree
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_browseTree {

	/**
	 * Constructor. Just calling init()
	 *
	 * @return	void
	 */
	function localPageTree() {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

		$this->init();

		$this->clause = ' AND doktype!=255'.$this->clause;
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
		global $BACK_PATH;
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass = ($c+1)%2 ? 'bgColor' : 'bgColor-10';
			if ($GLOBALS['SOBE']->curUrlInfo['act']=='page' && $GLOBALS['SOBE']->curUrlInfo['pageid']==$v['row']['uid'] && $GLOBALS['SOBE']->curUrlInfo['pageid'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}

			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->act.'&mode='.$GLOBALS['SOBE']->mode.'&expandPage='.$v['row']['uid'].'\');';
			$cEbullet = $this->ext_isLinkable($v['row']['doktype'],$v['row']['uid']) ?
						'<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' :
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
	 * Returns true if a doktype can be linked.
	 *
	 * @param	integer		Doktype value to test
	 * @param	integer		uid to test.
	 * @return	boolean
	 */
	function ext_isLinkable($doktype,$uid)	{
		if ($uid && $doktype<199)	{
			return true;
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
	function wrapIcon($icon,$row)	{
		return $this->addTagAttributes($icon,' title="id='.$row['uid'].'"');
	}
}








/**
 * Page tree for the RTE - totally the same, no changes needed. (Just for the sake of beauty - or confusion... :-)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rtePageTree extends localPageTree {
}








/**
 * For TBE record browser
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_PageTree extends localPageTree {

	/**
	 * Returns true if a doktype can be linked (which is always the case here).
	 *
	 * @param	integer		Doktype value to test
	 * @param	integer		uid to test.
	 * @return	boolean
	 */
	function ext_isLinkable($doktype,$uid)	{
		return true;
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
			$onClick = htmlspecialchars('return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->act.'&mode='.$GLOBALS['SOBE']->mode.'&expandPage='.$v['uid'].'\');');
		}
		return '<a href="#" onclick="'.$onClick.'">'.$title.'</a>';
	}
}








/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localFolderTree extends t3lib_folderTree {
	var $ext_IconMode=1;


	/**
	 * Initializes the script path
	 *
	 * @return void
	 */
	function localFolderTree() {
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
		$this->t3lib_folderTree();
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
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->act.'&mode='.$GLOBALS['SOBE']->mode.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Returns true if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		True is returned if the path is found in the web-part of the server and is NOT a recycler or temp folder
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
		global $BACK_PATH;
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

			// Preparing the current-path string (if found in the listing we will see a red blinking arrow).
		if (!$GLOBALS['SOBE']->curUrlInfo['value'])	{
			$cmpPath='';
		} else if (substr(trim($GLOBALS['SOBE']->curUrlInfo['info']),-1)!='/')	{
			$cmpPath=PATH_site.dirname($GLOBALS['SOBE']->curUrlInfo['info']).'/';
		} else {
			$cmpPath=PATH_site.$GLOBALS['SOBE']->curUrlInfo['info'];
		}

			// Traverse rows for the tree and print them into table rows:
		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass=($c+1)%2 ? 'bgColor' : 'bgColor-10';

				// Creating blinking arrow, if applicable:
			if ($GLOBALS['SOBE']->curUrlInfo['act']=='file' && $cmpPath==$v['row']['path'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}
				// Create arrow-bullet for file listing (if folder path is linkable):
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->act.'&mode='.$GLOBALS['SOBE']->mode.'&expandFolder='.rawurlencode($v['row']['path']).'\');';
			$cEbullet = $this->ext_isLinkable($v['row']) ? '<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' : '';

				// Put table row with folder together:
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap">'.$v['HTML'].$this->wrapTitle(t3lib_div::fixed_lgd_cs($v['row']['title'],$titleLen),$v['row']).'</td>
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class rteFolderTree extends localFolderTree {
}







/**
 * For TBE File Browser
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class TBE_FolderTree extends localFolderTree {
	var $ext_noTempRecyclerDirs=0;		// If file-drag mode is set, temp and recycler folders are filtered out.

	/**
	 * Returns true if the input "record" contains a folder which can be linked.
	 *
	 * @param	array		Array with information about the folder element. Contains keys like title, uid, path, _title
	 * @return	boolean		True is returned if the path is NOT a recycler or temp folder AND if ->ext_noTempRecyclerDirs is not set.
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
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->act.'&mode='.$GLOBALS['SOBE']->mode.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}
}





/**
 * class for the Element Browser window.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class browse_links {

		// Internal, static:
	var $siteURL;			// Current site URL (Frontend)
	var $thisScript;		// the script to link to
	var $thisConfig;		// RTE specific TSconfig
	var $setTarget;			// Target (RTE specific)
	var $setTitle;      		// title (RTE specific)
	var $doc;				// Backend template object

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
	 * 0: form field name reference
	 * 1: old/unused?
	 * 2: old/unused?
	 * 3: allowed types. Eg. "tt_content" or "gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai"
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
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;

			// Main GPvars:
		$this->pointer = t3lib_div::_GP('pointer');
		$this->bparams = t3lib_div::_GP('bparams');
		$this->P = t3lib_div::_GP('P');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->PM = t3lib_div::_GP('PM');

			// Find "mode"
		$this->mode=t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode='rte';
		}

			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode=='wizard')	{
			$currentLinkParts = t3lib_div::trimExplode(' ',$this->P['currentValue']);
			$this->curUrlArray = array(
				'target' => $currentLinkParts[1]
			);
			$this->curUrlInfo=$this->parseCurUrl($this->siteURL.'?id='.$currentLinkParts[0],$this->siteURL);
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
		if ((string)$this->mode=='rte')	{
			$RTEtsConfigParts = explode(':',$this->RTEtsConfigParams);
			$addPassOnParams.='&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams);
			$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}

			// Initializing the target value (RTE)
		$this->setTarget = $this->curUrlArray['target'];
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target']))	{
			$this->setTarget=$this->thisConfig['defaultLinkTarget'];
		}

			// Initializing the title value (RTE)
		$this->setTitle = $this->curUrlArray['title'];



			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;

			// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode.= '
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";

			var cur_href="'.($this->curUrlArray['href']?$this->curUrlArray['href']:'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";

			function setTarget(target)	{	//
				cur_target=target;
				add_target="&curUrl[target]="+escape(target);
			}
			function setTitle(title)	{	//
				cur_title=title;
				add_title="&curUrl[title]="+escape(title);
			}
			function setValue(value)	{	//
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
		';


		if ($this->mode=='wizard')	{	// Functions used, if the link selector is in wizard mode (= TCEforms fields)
			unset($this->P['fieldChangeFunc']['alert']);
			reset($this->P['fieldChangeFunc']);
			$update='';
			while(list($k,$v)=each($this->P['fieldChangeFunc']))	{
				$update.= '
				window.opener.'.$v;
			}

			$P2=array();
			$P2['itemName']=$this->P['itemName'];
			$P2['formName']=$this->P['formName'];
			$P2['fieldChangeFunc']=$this->P['fieldChangeFunc'];
			$addPassOnParams.=t3lib_div::implodeArrayForUrl('P',$P2);

			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					updateValueInMainForm(id+(anchor?anchor:"")+" "+cur_target);
					close();
					return false;
				}
				function link_folder(folder)	{	//
					updateValueInMainForm(folder+" "+cur_target);
					close();
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						var setValue = cur_href+" "+cur_target+" "+cur_title;
						if (setValue.substr(0,7)=="http://")	setValue = setValue.substr(7);
						if (setValue.substr(0,7)=="mailto:")	setValue = setValue.substr(7);
						updateValueInMainForm(setValue);
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
						field.value = input;
						'.$update.'
					}
				}
			';
		} else {	// Functions used, if the link selector is in RTE mode:
			$JScode.='
				function link_typo3Page(id,anchor)	{	//
					var theLink = \''.$this->siteURL.'?id=\'+id+(anchor?anchor:"");
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_folder(folder)	{	//
					var theLink = \''.$this->siteURL.'\'+folder;
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_spec(theLink)	{	//
					self.parent.parent.renderPopup_addLink(theLink,cur_target,cur_title);
					return false;
				}
				function link_current()	{	//
					if (cur_href!="http://" && cur_href!="mailto:")	{
						self.parent.parent.renderPopup_addLink(cur_href,cur_target,cur_title);
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
				var theLocation = URL+add_act+add_mode+add_href+add_target+add_title+add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':'').'+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
		';


			// This is JavaScript especially for the TBE Element Browser!
		$pArr = explode('|',$this->bparams);
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';
		$JScode.='
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("'.$BACK_PATH.'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener
				&& parent.window.opener.content
				&& parent.window.opener.content.document.editform
				&& parent.window.opener.content.document.editform["'.$formFieldName.'"]
						) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename,fp,filetype,imagefile,action, close)	{	//
				if (1=='.($pArr[0]&&!$pArr[1]&&!$pArr[2] ? 1 : 0).')	{
					addElement(filename,table+"_"+uid,fp,close);
				} else {
					if (setReferences())	{
						parent.window.opener.group_change("add","'.$pArr[0].'","'.$pArr[1].'","'.$pArr[2].'",elRef,targetDoc);
					} else {
						alert("Error - reference to main window is not set properly!");
					}
					if (close)	{
						parent.window.opener.focus();
						parent.close();
					}
				}
				return false;
			}
			function addElement(elName,elValue,altElValue,close)	{	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
					parent.window.opener.setFormValueFromBrowseWin("'.$pArr[0].'",altElValue?altElValue:elValue,elName);
					if (close)	{
						parent.window.opener.focus();
						parent.close();
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
		';

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScode = $this->doc->wrapScriptTags($JScode);

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
	 * @param array Session data array
	 * @return array Session data and boolean which indicates that data needs to be stored in session because it's changed
	 */
	function processSessionData($data) {
		$store = false;

		switch((string)$this->mode)	{
			case 'db':
				if (isset($this->expandPage))	{
					$data['expandPage']=$this->expandPage;
					$store = true;
				} else {
					$this->expandPage=$data['expandPage'];
				}
			break;
			case 'file':
			case 'filedrag':
				if (isset($this->expandFolder))	{
					$data['expandFolder']=$this->expandFolder;
					$store = true;
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
		global $LANG, $BACK_PATH;

			// Starting content:
		$content=$this->doc->startPage('RTE link');

			// Initializing the action value, possibly removing blinded values etc:
		$allowedItems = array_diff(explode(',','page,file,url,mail,spec'),t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		reset($allowedItems);
		if (!in_array($this->act,$allowedItems))	$this->act = current($allowedItems);

			// Making menu in top:
		$menuDef = array();
		if (!$wiz)	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $LANG->getLL('removeLink',1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="self.parent.parent.renderPopup_unLink();return false;"';
		}
		if (in_array('page',$allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $LANG->getLL('page',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\'?act=page\');return false;"';
		}
		if (in_array('file',$allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] = $LANG->getLL('file',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\'?act=file\');return false;"';
		}
		if (in_array('url',$allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $LANG->getLL('extUrl',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\'?act=url\');return false;"';
		}
		if (in_array('mail',$allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $LANG->getLL('email',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\'?act=mail\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $LANG->getLL('special',1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\'?act=spec\');return false;"';
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
								<td>'.$GLOBALS['LANG']->getLL('emailAddress',1).':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setTarget(\'\');setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
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
								<td>URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="'.$GLOBALS['LANG']->getLL('setLink',1).'" onclick="setValue(document.lurlform.lurl.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
			break;
			case 'file':
				$foldertree = t3lib_div::makeInstance('rteFolderTree');
				$foldertree->thisScript = $this->thisScript;
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder))			$this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

				$content.= '

			<!--
				Wrapper table for folder tree / file list:
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
					reset($v);
					while(list($k2)=each($v))	{
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title=$LANG->sL($title);
							}
								// Description:
							$description=$v[$k2i.'.']['description'] ? $LANG->sL($v[$k2i.'.']['description'],1).'<br />' : '';

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="setTarget('".$v[$k2i.'.']['target']."');";
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
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
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
								<td class="bgColor5" class="c-wCell" valign="top"><strong>'.$LANG->getLL('special',1).'</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
			default:
				$pagetree = t3lib_div::makeInstance('rtePageTree');
				$pagetree->thisScript = $this->thisScript;
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();
				$content.= '

			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($GLOBALS['LANG']->getLL('pageTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
		}

		$content .= '



			<!--
				Selecting title for link:
			-->
				<form action="" name="ltitleform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('title',1).'</td>
							<td><input type="text" name="ltitle" onchange="setTitle(this.value);" value="'.htmlspecialchars($this->setTitle).'"'.$this->doc->formWidth(10).' /></td>
							<td><input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" /></td>
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
							<td><input type="text" name="ltarget" onchange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget).'"'.$this->doc->formWidth(10).' /></td>
							<td>
								<select name="ltarget_type" onchange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
									<option></option>
									<option value="_top">'.$GLOBALS['LANG']->getLL('top',1).'</option>
									<option value="_blank">'.$GLOBALS['LANG']->getLL('newWindow',1).'</option>
								</select>
							</td>
							<td>';

			if (($this->curUrlInfo['act']=="page" || $this->curUrlInfo['act']=='file') && $this->curUrlArray['href'])	{
				$ltarget.='
							<input type="submit" value="'.$GLOBALS['LANG']->getLL('update',1).'" onclick="return link_current();" />';
			}

			$selectJS = '
				if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+"x"+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					setTarget(document.ltargetform.ltarget.value);
          			setTitle(document.ltitleform.ltitle.value);
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
		$content=$this->doc->startPage('TBE file selector');

			// Init variable:
		$pArr = explode('|',$this->bparams);

			// Making the browsable pagetree:
		$pagetree = t3lib_div::makeInstance('TBE_PageTree');
		$pagetree->thisScript=$this->thisScript;
		$pagetree->ext_pArrPages = !strcmp($pArr[3],'pages')?1:0;
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
		global $BE_USER;

			// Starting content:
		$content.=$this->doc->startPage('TBE file selector');

			// Init variable:
		$pArr = explode('|',$this->bparams);

			// Create upload/create folder forms, if a path is given:
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
		if ($BE_USER->getTSConfigVal('options.uploadFieldsInTopOfEB'))	$content.=$uploadForm;

			// Getting flag for showing/not showing thumbnails:
		$noThumbs = $GLOBALS['BE_USER']->getTSConfigVal('options.noThumbsInEB');

		if (!$noThumbs)	{
				// MENU-ITEMS, fetching the setting for thumbnails from File>List module:
			$_MOD_MENU = array('displayThumbs' => '');
			$_MCONF['name']='file_list';
			$_MOD_SETTINGS = t3lib_BEfunc::getModuleData($_MOD_MENU, t3lib_div::_GP('SET'), $_MCONF['name']);
			$addParams = '&act='.$this->act.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
			$thumbNailCheck = t3lib_BEfunc::getFuncCheck('','SET[displayThumbs]',$_MOD_SETTINGS['displayThumbs'],$this->thisScript,$addParams).' '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.php:displayThumbs',1);
		} else {
			$thumbNailCheck='';
		}
		$noThumbs = $noThumbs?$noThumbs:!$_MOD_SETTINGS['displayThumbs'];

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
		$content.=$thumbNailCheck;

			// Adding create folder + upload forms if applicable:
		if (!$BE_USER->getTSConfigVal('options.uploadFieldsInTopOfEB'))	$content.=$uploadForm;
		if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB'))	$content.=$createFolder;

			// Add some space
		$content.='<br /><br />';

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
		global $BE_USER, $BACK_PATH;

		$out='';
		$expPageId = $this->expandPage;		// Set page id (if any) to expand

			// If there is an anchor value (content element reference) in the element reference, then force an ID to expand:
		if (!$this->expandPage && $this->curUrlInfo['cElement'])	{
			$expPageId = $this->curUrlInfo['pageid'];	// Set to the current link page id.
		}

			// Draw the record list IF there is a page id to expand:
		if ($expPageId && t3lib_div::testInt($expPageId) && $BE_USER->isInWebMount($expPageId))	{

				// Set header:
			$out.=$this->barheader($GLOBALS['LANG']->getLL('contentElements').':');

				// Create header for listing, showing the page title/icon:
			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
			$mainPageRec = t3lib_BEfunc::getRecordWSOL('pages',$expPageId);
			$picon=t3lib_iconWorks::getIconImage('pages',$mainPageRec,'','');
			$picon.= htmlspecialchars(t3lib_div::fixed_lgd_cs($mainPageRec['title'],$titleLen));
			$out.=$picon.'<br />';

				// Look up tt_content elements from the expanded page:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'uid,header,hidden,starttime,endtime,fe_group,CType,colpos,bodytext',
							'tt_content',
							'pid='.intval($expPageId).
								t3lib_BEfunc::deleteClause('tt_content').
								t3lib_BEfunc::versioningPlaceholderClause('tt_content'),
							'',
							'colpos,sorting'
						);
			$cc = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

				// Traverse list of records:
			$c=0;
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$c++;
				$icon=t3lib_iconWorks::getIconImage('tt_content',$row,$BACK_PATH,'');
				if ($this->curUrlInfo['act']=='page' && $this->curUrlInfo['cElement']==$row['uid'])	{
					$arrCol='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
				} else {
					$arrCol='';
				}
					// Putting list element HTML together:
				$out.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
						$arrCol.
						'<a href="#" onclick="return link_typo3Page(\''.$expPageId.'\',\'#'.$row['uid'].'\');">'.
						$icon.
						htmlspecialchars(t3lib_div::fixed_lgd_cs($row['header'],$titleLen)).
						'</a><br />';

					// Finding internal anchor points:
				if (t3lib_div::inList('text,textpic', $row['CType']))	{
					$split = preg_split('/(<a[^>]+name=[\'"]?([^"\'>[:space:]]+)[\'"]?[^>]*>)/i', $row['bodytext'], -1, PREG_SPLIT_DELIM_CAPTURE);

					foreach($split as $skey => $sval)	{
						if (($skey%3)==2)	{
								// Putting list element HTML together:
							$sval = substr($sval,0,100);
							$out.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/line.gif','width="18" height="16"').' alt="" />'.
									'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join'.($skey+3>count($split)?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
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
		global $TCA,$BE_USER, $BACK_PATH;

		$out='';
		if ($this->expandPage>=0 && t3lib_div::testInt($this->expandPage) && $BE_USER->isInWebMount($this->expandPage))	{

				// Set array with table names to list:
			if (!strcmp(trim($tables),'*'))	{
				$tablesArr = array_keys($TCA);
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
			if (in_array('pages',$tablesArr))	{
				$ficon=t3lib_iconWorks::getIcon('pages',$mainPageRec);
				$ATag="<a href=\"#\" onclick=\"return insertElement('pages', '".$mainPageRec['uid']."', 'db', ".t3lib_div::quoteJSvalue($mainPageRec['title']).", '', '', '".$ficon."','',1);\">";
				$ATag2="<a href=\"#\" onclick=\"return insertElement('pages', '".$mainPageRec['uid']."', 'db', ".t3lib_div::quoteJSvalue($mainPageRec['title']).", '', '', '".$ficon."','',0);\">";
				$ATag_alt=substr($ATag,0,-4).",'',1);\">";
				$ATag_e='</a>';
			}
			$picon=t3lib_iconWorks::getIconImage('pages',$mainPageRec,$BACK_PATH,'');
			$pBicon=$ATag2?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' alt="" />':'';
			$pText=htmlspecialchars(t3lib_div::fixed_lgd_cs($mainPageRec['title'],$titleLen));
			$out.=$picon.$ATag2.$pBicon.$ATag_e.$ATag.$pText.$ATag_e.'<br />';

				// Initialize the record listing:
			$id = $this->expandPage;
			$pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$pageinfo = t3lib_BEfunc::readPageAccess($id,$perms_clause);
			$table='';

				// Generate the record list:
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
			$out.=$dblist->getSearchBox();
		}

			// Return accumulated content:
		return $out;
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
		global $BACK_PATH;

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
			$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
			$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($expandFolder),$titleLen));
			$picon='<a href="#" onclick="return link_folder(\''.t3lib_div::rawUrlEncodeFP(substr($expandFolder,strlen(PATH_site))).'\');">'.$picon.'</a>';
			$out.=$picon.'<br />';

				// Get files from the folder:
			$files = t3lib_div::getFilesInDir($expandFolder,$extensionList,1,1);	// $extensionList="",$prependPath=0,$order='')
			$c=0;
			$cc=count($files);

			if (is_array($files))	{
				foreach($files as $filepath)	{
					$c++;
					$fI=pathinfo($filepath);

						// File icon:
					$icon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));

						// If the listed file turns out to be the CURRENT file, then show blinking arrow:
					if ($this->curUrlInfo['act']=="file" && $cmpPath==$filepath)	{
						$arrCol='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_left.gif','width="5" height="9"').' class="c-blinkArrowL" alt="" />';
					} else {
						$arrCol='';
					}

						// Get size and icon:
					$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes)';
					$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$icon.'','width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" alt="" />';

						// Put it all together for the file element:
					$out.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join'.($c==$cc?'bottom':'').'.gif','width="18" height="16"').' alt="" />'.
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
		global $LANG;

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
		global $LANG, $BACK_PATH;

		$out='';

			// Listing the files:
		if (is_array($files))	{

				// Create headline (showing number of files):
			$out.=$this->barheader(sprintf($GLOBALS['LANG']->getLL('files').' (%s):',count($files)));

			$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

				// Create the header of current folder:
			if($folderName) {
				$picon='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/_icon_webfolders.gif','width="18" height="16"').' alt="" />';
				$picon.=htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($folderName),$titleLen));
				$out.=$picon.'<br />';
			}

				// Init graphic object for reading file dimensions:
			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				// Traverse the file list:
			$lines=array();
			foreach($files as $filepath)	{
				$fI=pathinfo($filepath);

					// Thumbnail/size generation:
				if (t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],$fI['extension']) && !$noThumbs)	{
					$imgInfo = $imgObj->getImageDimensions($filepath);
					$pDim = $imgInfo[0].'x'.$imgInfo[1].' pixels';
					$clickIcon = t3lib_BEfunc::getThumbNail($BACK_PATH.'thumbs.php',$filepath,'hspace="5" vspace="5" border="1"');
				} else {
					$clickIcon = '';
					$pDim = '';
				}

					// Create file icon:
				$ficon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
				$size=' ('.t3lib_div::formatSize(filesize($filepath)).'bytes'.($pDim?', '.$pDim:'').')';
				$icon = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/fileicons/'.$ficon,'width="18" height="16"').' title="'.htmlspecialchars($fI['basename'].$size).'" class="absmiddle" alt="" />';

					// Create links for adding the file:
				if (strstr($filepath,',') || strstr($filepath,'|'))	{	// In case an invalid character is in the filepath, display error message:
					$eMsg = $LANG->JScharCode(sprintf($LANG->getLL('invalidChar'),', |'));
					$ATag = $ATag_alt = "<a href=\"#\" onclick=\"alert(".$eMsg.");return false;\">";
				} else {	// If filename is OK, just add it:
					$ATag = "<a href=\"#\" onclick=\"return insertElement('','".t3lib_div::shortMD5($filepath)."', 'file', '".rawurlencode($fI['basename'])."', unescape('".rawurlencode($filepath)."'), '".$fI['extension']."', '".$ficon."');\">";
					$ATag_alt = substr($ATag,0,-4).",'',1);\">";
				}
				$ATag_e='</a>';

					// Create link to showing details about the file in a window:
				$Ahref = $BACK_PATH.'show_item.php?table='.rawurlencode($filepath).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$ATag2='<a href="'.htmlspecialchars($Ahref).'">';
				$ATag2_e='</a>';

					// Combine the stuff:
					$filenameAndIcon=$ATag_alt.$icon.htmlspecialchars(t3lib_div::fixed_lgd_cs(basename($filepath),$titleLen)).$ATag_e;

					// Show element:
				if ($pDim)	{		// Image...
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' title="'.$LANG->getLL('addToList',1).'" alt="" />'.$ATag_e.'</td>
							<td nowrap="nowrap">'.($ATag2.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('info',1).'" alt="" /> '.$LANG->getLL('info',1).$ATag2_e).'</td>
							<td nowrap="nowrap">&nbsp;'.$pDim.'</td>
						</tr>';
					$lines[]='
						<tr>
							<td colspan="4">'.$ATag_alt.$clickIcon.$ATag_e.'</td>
						</tr>';
				} else {
					$lines[]='
						<tr class="bgColor4">
							<td nowrap="nowrap">'.$filenameAndIcon.'&nbsp;</td>
							<td>'.$ATag.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/plusbullet2.gif','width="18" height="16"').' title="'.$LANG->getLL('addToList',1).'" alt="" />'.$ATag_e.'</td>
							<td nowrap="nowrap">'.($ATag2.'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/zoom2.gif','width="12" height="12"').' title="'.$LANG->getLL('info',1).'" alt="" /> '.$LANG->getLL('info',1).$ATag2_e).'</td>
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
				<table border="0" cellpadding="0" cellspacing="1" id="typo3-fileList">
					'.implode('',$lines).'
				</table>';
		}

			// Return accumulated content for filelisting:
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

		$expandFolder = $expandFolder ? $expandFolder : $this->expandFolder;
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

		 				// Fraverse files:
					while(list(,$filepath)=each($files))	{
						$fI=pathinfo($filepath);

							// URL of image:
						$iurl = $this->siteURL.t3lib_div::rawurlencodeFP(substr($filepath,strlen(PATH_site)));

							// Show only web-images
						if (t3lib_div::inList('gif,jpeg,jpg,png',$fI['extension']))	{
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
	 * @return	boolean		If the input path is found in PATH_site then it returns true.
	 */
	function isWebFolder($folder)	{
		$folder = ereg_replace('\/$','',$folder).'/';
		return t3lib_div::isFirstPartOfStr($folder,PATH_site) ? TRUE : FALSE;
	}

	/**
	 * Checks, if a path is within the mountpoints of the backend user
	 *
	 * @param	string		Absolute filepath
	 * @return	boolean		If the input path is found in the backend users filemounts, then return true.
	 */
	function checkFolder($folder)	{
		$fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

		return $fileProcessor->checkPathAgainstMounts(ereg_replace('\/$','',$folder).'/') ? TRUE : FALSE;
	}

	/**
	 * Prints a 'header' where string is in a tablecell
	 *
	 * @param	string		The string to print in the header. The value is htmlspecialchars()'ed before output.
	 * @return	string		The header HTML (wrapped in a table)
	 */
	function barheader($str)	{
		return '

			<!--
				Bar header:
			-->
			<h3 class="bgColor5">'.htmlspecialchars($str).'</h3>
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
		global $BACK_PATH;

		$msg = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/'.$icon.'.gif','width="18" height="16"').' alt="" />'.htmlspecialchars($in_msg);
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
		return '

			<!--
				Print current URL
			-->
			<table border="0" cellpadding="0" cellspacing="0" class="bgColor5" id="typo3-curUrl">
				<tr>
					<td>'.$GLOBALS['LANG']->getLL('currentLink',1).': '.htmlspecialchars(rawurldecode($str)).'</td>
				</tr>
			</table>';
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
				if (@file_exists(PATH_site.rawurldecode($rel)))	{	// URL is a file, which exists:
					$info['value']=rawurldecode($rel);
					$info['act']='file';
				} else {	// URL is a page (id parameter)
					$uP=parse_url($rel);
					if (!trim($uP['path']))	{
						$pp = explode('id=',$uP['query']);
						$id = $pp[1];
						if ($id)	{
								// Checking if the id-parameter is an alias.
							if (!t3lib_div::testInt($id))	{
								list($idPartR) = t3lib_BEfunc::getRecordsByField('pages','alias',$id);
								$id=intval($idPartR['uid']);
							}

							$pageRow = t3lib_BEfunc::getRecordWSOL('pages',$id);
							$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
							$info['value']=$GLOBALS['LANG']->getLL('page',1)." '".htmlspecialchars(t3lib_div::fixed_lgd_cs($pageRow['title'],$titleLen))."' (ID:".$id.($uP['fragment']?', #'.$uP['fragment']:'').')';
							$info['pageid']=$id;
							$info['cElement']=$uP['fragment'];
							$info['act']='page';
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
		$redirectValue = $this->thisScript.'?act='.$this->act.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
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
		$redirectValue = $this->thisScript.'?act='.$this->act.'&mode='.$this->mode.'&expandFolder='.rawurlencode($path).'&bparams='.rawurlencode($this->bparams);
		$code.='<input type="hidden" name="redirect" value="'.htmlspecialchars($redirectValue).'" />'.
				'<input type="submit" name="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_newfolder.php.submit',1).'" />';

		$code.='</td>
					</tr>
				</table>
			</form>';

		return $code;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.browse_links.php']);
}


?>