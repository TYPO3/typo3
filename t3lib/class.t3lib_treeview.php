<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2002 Kasper Skårhøj (kasper@typo3.com)
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
 * Base class for creating a page/folder tree in HTML
 *
 * Revised for TYPO3 3.6 August/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * Maintained by René Fritz
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  104: class t3lib_treeView 
 *  251:     function init($clause='')	
 *  268:     function reset()	
 *  282:     function getBrowsableTree($addClause='')	
 *  356:     function printTree($treeArr='')	
 *  396:     function PMicon(&$row,$a,$c,$nextCount,$exp)
 *  418:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  436:     function wrapTitle($title,$v)
 *  449:     function wrapIcon($icon,&$row)
 *  474:     function wrapStop($str,&$row)	
 *  487:     function getCount($uid)
 *  508:     function addField($field,$noCheck=0)	
 *  524:     function expandNext($id)	
 *  534:     function savePosition()	
 *
 *              SECTION: functions that might be overwritten by extended classes
 *  551:     function getRootRecord($uid)
 *  561:     function getRootIcon($rec) 
 *  571:     function getRecord($uid)
 *  585:     function getId($v) 
 *  595:     function getJumpToParm($v) 
 *  605:     function getIcon(&$row)
 *  619:     function getTitleStr(&$row)	
 *  630:     function getTitleAttrib(&$row) 
 *
 *              SECTION: data handling
 *  663:     function getTree($uid, $depth=999, $depthData='',$blankLineCode='')
 *  737:     function getDataInit($uid)
 *  768:     function getDataCount($res) 
 *  783:     function getDataNext($res)
 *  798:     function getDataFree($res)
 *  816:     function setDataFromArray($dataArr,$recursive=0,$parent=0,$icount=1)	
 *
 * TOTAL FUNCTIONS: 27
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 









require_once (PATH_t3lib.'class.t3lib_iconworks.php');
require_once (PATH_t3lib.'class.t3lib_befunc.php');
require_once (PATH_t3lib.'class.t3lib_div.php');

/**
 * Base class for creating a page/folder tree in HTML
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @see t3lib_browsetree
 * @see t3lib_pagetree
 * @see t3lib_foldertree
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_treeView {

	/*
	 * Needs to be initialized with $GLOBALS['BE_USER']
	 */
	var $BE_USER='';

	/*
	 * Needs to be initialized with e.g. $GLOBALS['WEBMOUNTS']
	 */
	var $MOUNTS='';

	/**
	 * A prefix for table cell id's which will be wrapped around an item.
	 * Can be used for highlighting by JavaScript.
	 * Needs to be unique if multiple pages are on one HTML page.
	 */
	var $domIdPrefix = 'row';

	/*
	 * Database table to get the tree data from.
	 * Leave blank if data comes from an array.
	 */
	var $table='';

	/*
	 * Defines the field of $table which is the parent id field (like pid for table pages).
	 */
	var $parentField='pid';

	/*
	 * Unique name for the tree.
	 * Used as key for storing the tree into the BE users settings.
	 * Used as key to pass parameters in links.
	 * etc.
	 */
	var $treeName = '';

	/*
	 * Icon file name for item icons.
	 */
	var $iconName = 'default.gif';

	/*
	 * Icon file path.
	 */
	var $iconPath = '';

	/**
	 * Back path for icons
	 */
	var $backPath;


	/**
	 * If true, HTML code is also accumulated in ->tree array during rendering of the tree.
	 */
	var $makeHTML=1;

	/**
	 * If true, records as selected will be stored internally in the ->recs array
	 */
	var $setRecs = 0;

	/**
	 * WHERE clause used for selecting records for the tree. Is set by function init
	 * @see init()
	 */
	var $clause=' AND NOT deleted';


	/**
	 * Default set of fields selected from the tree table.
	 * @see addField()
	 */
	var $fieldArray = Array('uid','title');

	/**
	 * List of other fields which are ALLOWED to set
	 * @see addField()
	 */
	var $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate,cruser_id';


		// *********
		// internal
		// *********
		// For record trees:
	var $ids = Array();		// one-dim array of the id's selected.
	var $ids_hierarchy = array();	// The hierarchy of page ids
	var $buffer_idH = array();	// Temporary, internal array

		// For FOLDER trees:
	var $specUIDmap=array();	// Special UIDs for folders (integer-hashes of paths)

		// For both types
	var $tree = Array();	// Tree is accumulated in this variable





	/**
	 * The tree array. Stored for the BE user.
	 */
	var $stored = array();


	var $bank=0;
	var $thisScript='';
	var $expandAll=0;
	var $expandFirst=0;







		// which HTML attribute to use: alt/title
	var $titleAttrib = 'title';

		// $ext_IconMode = $BE_USER->getTSConfigVal("options.pageTree.disableIconLinkToContextmenu");
	var $ext_IconMode = false;

	var $addSelfId = 0;

		// used if the tree is made of records (not folders for ex.)
	var $title='no title';

	var $data = array();
	var $currData = array();
	var $currDataC = 0;

		// internal
	var $recs = array();

	var $dbres;


	/**
	 * Initialize the tree class. Needs to be overwritten
	 * Will set ->fieldsArray, ->backPath and ->clause
	 * 
	 * @param	string		record select clause
	 * @return	void		
	 */
	function init($clause='')	{
		$this->BE_USER = $GLOBALS['BE_USER'];
		$this->titleAttrib = t3lib_BEfunc::titleAttrib();
		$this->backPath = $GLOBALS['BACK_PATH'];

		$this->clause = $clause ? $clause : $this->clause;

		if(!is_array($this->MOUNTS)){
			$this->MOUNTS = array(0 => 0); // dummy
		}
	}

	/**
	 * Resets the tree, recs, ids, and ids_hierarchy internal variables
	 * 
	 * @return	void		
	 */
	function reset()	{
		$this->tree = array();
		$this->recs = array();
		$this->ids = array();
		$this->ids_hierarchy = array();
	}

	/**
	 * Will create and return the HTML code for a browsable tree
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 * 
	 * @param	string		Additional WHERE clause (this is additional clauses you can set!)
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree($addClause='')	{
#TODO: $this->init($addClause);
#TODO: $this->init(' AND '.$this->permsC().$addClause);

			// Get stored tree structure:
		$this->stored=unserialize($this->BE_USER->uc[$this->treeName]);
#debug($this->stored);
			// PM action
			//	(If an plus/minus icon has been clicked, the PM GET var is sent and we must update the stored positions in the tree):
		$PM = explode('_',t3lib_div::GPvar('PM'));
#debug($PM);

		if (count($PM)==4 && $PM[3]==$this->treeName)	{
			if (isset($this->MOUNTS[$PM[0]]))	{
				if ($PM[1])	{	// set
					$this->stored[$PM[0]][$PM[2]]=1;
					$this->savePosition();
				} else {	// clear
					unset($this->stored[$PM[0]][$PM[2]]);
					$this->savePosition();
				}
			}
		}

			// traverse mounts:
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		$treeArr=array();
		reset($this->MOUNTS);
		while(list($idx,$uid)=each($this->MOUNTS))	{
				// Set first:
			$this->bank=$idx;
			$isOpen = $this->stored[$idx][$uid] || $this->expandFirst;

			$curIds = $this->ids;	// save ids
			$this->reset();
			$this->ids = $curIds;

				// Set PM icon:
			$cmd=$this->bank.'_'.($isOpen?"0_":"1_").$uid.'_'.$this->treeName;
			$icon='<img src="'.$this->backPath.'t3lib/gfx/ol/'.($isOpen?'minus':'plus').'only.gif" width="18" height="16" align="top" border="0" alt="" />';
			$firstHtml= $this->PM_ATagWrap($icon,$cmd);

				// Preparing rootRec for the mount
			if ($uid>0)	{
				$rootRec=$this->getRecord($uid);
				$firstHtml.=$this->getIcon($rootRec);
			} else {
					// Artificial record for the tree root, id=0
				$rootRec=$this->getRootRecord($uid);
				$firstHtml.=$this->getRootIcon($rootRec);
			}

				// Add the root of the mount to ->tree
			$this->tree[]=array('HTML'=>$firstHtml,'row'=>$rootRec);

				// If the mount is expanded, go down:
			if ($isOpen)	{
					// Set depth:
				$depthD='<img src="'.$this->backPath.'t3lib/gfx/ol/blank.gif" width="18" height="16" align="top" alt="" />';
				if ($this->addSelfId)	$this->ids[] = $uid;
				$this->getTree($uid,999,$depthD);
			}

				// Add tree:
			$treeArr=array_merge($treeArr,$this->tree);
		}
		return $this->printTree($treeArr);
	}
	
	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param	array		"tree-array" - if blank string, the internal ->tree array is used.
	 * @return	string		The HTML code for the tree
	 */
	function printTree($treeArr='')	{
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;
		reset($treeArr);
		$out='';

			// put a table around it with IDs to access the rows from JS
			// not a problem if you don't need it
			// In XHTML there is no "name" attribute of <td> elements - but Mozilla will not be able to highlight rows if the name attribute is NOT there.
		$out .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
		while(list($k,$v)=each($treeArr))	{
			$idAttr = htmlspecialchars($this->domIdPrefix.$this->getId($v['row']));
			$out.='
			<tr>
				<td name="'.$idAttr.'" id="'.$idAttr.'" nowrap="nowrap">'.
					$v['HTML'].
					$this->wrapTitle(t3lib_div::fixed_lgd($this->getTitleStr($v['row']),$titleLen),$v['row']).
				'</td>
			</tr>
				';
		}
		$out .= '</table>';
		return $out;
	}



	/**
	 * Generate the plus/minus icon for the browsable tree.
	 * Extending parent function
	 * 
	 * @param	array		record for the entry
	 * @param	integer		The current entry number
	 * @param	integer		The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param	integer		The number of sub-elements to the current element.
	 * @param	boolean		The element was expanded to render subelements if this flag is set.
	 * @return	string		Image tag with the plus/minus icon.
	 * @access private
	 * @see t3lib_pageTree::PMicon()
	 */
	function PMicon(&$row,$a,$c,$nextCount,$exp)	{
		$PM = $nextCount ? ($exp?'minus':'plus') : 'join';
		$BTM = ($a==$c)?'bottom':'';
		$icon = '<img src="'.$this->backPath.'t3lib/gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" border="0" alt="" />';

		if ($nextCount)	{
			$cmd=$this->bank.'_'.($exp?'0_':'1_').$row['uid'].'_'.$this->treeName;
			$bMark=($this->bank.'_'.$row['uid']);
			$icon = $this->PM_ATagWrap($icon,$cmd,$bMark);
		}
		return $icon;
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
		$aUrl = $this->thisScript.'?PM='.$cmd.$anchor;
		return '<a href="'.htmlspecialchars($aUrl).'"'.$name.'>'.$icon.'</a>';
	}

	/**
	 * Wrapping $title in a-tags.
	 * $v is the array with item and other info.
	 * 
	 * @param	string		Title string
	 * @param	string		Not used, ignore
	 * @return	string		Either htmlspecialchar()'ed version of input value OR (if input was empty) a label like "[no title]"
	 * @access private
	 */
	function wrapTitle($title,$v)	{
		$aOnClick = 'return jumpTo('.$this->getJumpToParm($v).',this);';
		return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
	}

	/**
	 * Wrapping the image tag, $icon, for the row, $row
	 *
	 * @param	string		The image tag for the icon
	 * @param	array		The row for the current element
	 * @return	string		The processed icon input value.
	 * @access private
	 */
	function wrapIcon($icon,&$row)	{
			// Add title attribute to input icon tag
		$lockIcon='';
		$theIcon = substr($icon,0,-1);
		$theIcon .= $this->titleAttrib? (' '.$this->titleAttrib.'="'.$this->getTitleAttrib($row)).'"' : '';
		$theIcon .= ' border="0" />';

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$theIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theIcon,$this->treeName,$this->getId($row),0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo('.$this->getJumpToParm($row).',this);';
			$theIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$theIcon.'</a>';
		}
		return $theIcon.$lockIcon;
	}

	/**
	 * Adds a red "+" to the input string, $str, if the field "php_tree_stop" in the $row (pages) is set
	 * 
	 * @param	string		Input string, like a page title for the tree
	 * @param	array		record row with "php_tree_stop" field
	 * @return	string		Modified string
	 * @access private
	 */
	function wrapStop($str,&$row)	{
		if ($row['php_tree_stop'])	{
			$str.='<span class="typo3-red">+ </span>';
		}
		return $str;
	}
	/**
	 * Returns the number of records having the parent id, $uid
	 * 
	 * @param	integer		id to count subitems for
	 * @return	integer		
	 * @access private
	 */
	function getCount($uid)	{
		if ($this->table) {
			$query = 'SELECT count(*) FROM '.$this->table.
					' WHERE '.$this->parentField.'="'.addslashes($uid).'"'.
					$this->clause;
			$res = mysql(TYPO3_db, $query);
			$row=mysql_fetch_row($res);
			return $row[0];
		} else {
			$res = $this->getDataInit($uid);
			return $this->getDataCount($res);
		}
	}

	/**
	 * Adds a fieldname to the internal array ->fieldArray
	 * 
	 * @param	string		Field name to
	 * @param	boolean		If set, the fieldname will be set no matter what. Otherwise the field name must either be found as key in $TCA['pages']['columns'] or in the list ->defaultList
	 * @return	void		
	 */
	function addField($field,$noCheck=0)	{
		global $TCA;
		if ($noCheck || is_array($TCA[$this->table]['columns'][$field]) || t3lib_div::inList($this->defaultList,$field))	{
			$this->fieldArray[]=$field;
		}
	}

	/**
	 * Returns true/false if the next level for $id should be expanded - based on data in $this->stored[][] and ->expandAll flag.
	 * Extending parent function
	 * 
	 * @param	integer		record id/key
	 * @return	boolean		
	 * @access private
	 * @see t3lib_pageTree::expandNext()
	 */
	function expandNext($id)	{
		return ($this->stored[$this->bank][$id] || $this->expandAll)? 1 : 0;
	}

	/**
	 * Saves the content of ->stored (keeps track of expanded positions in the tree)
	 * $this->treeName will be used as key for BE_USER->uc[] to store it in
	 * 
	 * @return	void		
	 */
	function savePosition()	{
			$this->BE_USER->uc[$this->treeName] = serialize($this->stored);
			$this->BE_USER->writeUC();
	}

	
	
	/******************************
	 * 
	 * functions that might be overwritten by extended classes
	 * 
	 ********************************/
	 
	/**
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getRootRecord($uid) {
		return array(	'title'=>$this->title, 'uid'=>0 );
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$rec: ...
	 * @return	[type]		...
	 */
	function getRootIcon($rec) {
		return $this->wrapIcon('<img src="'.$this->backPath.'gfx/i/_icon_website.gif" width="18" height="16" align="top" alt="" />',$rec);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function getRecord($uid) {
		if($this->table) {
			return t3lib_befunc::getRecord($this->table,$uid);
		} else {
			return $this->data[$uid];
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function getId($v) {
		return $v['uid'];
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function getJumpToParm($v) {
		return "'".$this->getId($v)."'";
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$row: ...
	 * @return	[type]		...
	 */
	function getIcon(&$row) {
		if ($this->iconPath && $this->iconName) {
			return '<img src="'.$this->iconPath.$this->iconName.'" width="18" height="16" align="top" alt="" />';
		} else {
// rene[290903]: removed $this->wrapIcon() here, I don't think it have some side effects
			return '<img src="'.$this->backPath.t3lib_iconWorks::getIcon($this->table,$row).'" width="18" height="16" align="top" alt="" />';
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$row: ...
	 * @return	[type]		...
	 */
	function getTitleStr(&$row)	{
		$title = (!strcmp(trim($row['title']),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>' : htmlspecialchars($row['title']);
		return $title;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$row: ...
	 * @return	[type]		...
	 */
	function getTitleAttrib(&$row) {
		return $row['title'];
	}














	/********************************
	 *
	 * data handling
	 * works with records and arrays
	 *
	 ********************************/

	/**
	 * fetches the data for the tree
	 * 
	 * @param	integer		item id for which to select subitems.
	 * @param	integer		Max depth (recursivity limit)
	 * @param	string		HTML-code prefix for recursive calls.
	 * @param	string		? (internal)
	 * @return	integer		The count of pages on the level
	 */
	function getTree($uid, $depth=999, $depthData='',$blankLineCode='')	{
			// Buffer for id hierarchy is reset:
		$this->buffer_idH=array();

			// Init vars
		$depth=intval($depth);
		$HTML='';
		$a=0;

		$res = $this->getDataInit($uid);
		$c = $this->getDataCount($res);

			// Traverse the records:
		while ($row = $this->getDataNext($res))	{
			$a++;

			$newID =$row['uid'];
			$this->tree[]=array();		// Reserve space.
			end($this->tree);
			$treeKey = key($this->tree);	// Get the key for this space
			$LN = ($a==$c)?'blank':'line';

				// If records should be accumulated, do so
			if ($this->setRecs)	{
				$this->recs[$row['uid']] = $row;
			}

				// accumulate the id of the page in the internal arrays
			$this->ids[]=$idH[$row['uid']]['uid']=$row['uid'];
			$this->ids_hierarchy[$depth][]=$row['uid'];

				// Make a recursive call to the next level
			if ($depth>1 && $this->expandNext($newID) && !$row['php_tree_stop'])	{
				$nextCount=$this->getTree(
					$newID,
					$depth-1,
					$this->makeHTML?$depthData.'<img src="'.$this->backPath.'t3lib/gfx/ol/'.$LN.'.gif" width="18" height="16" align="top" alt="" />':'',
					$blankLineCode.','.$LN
					);
				if (count($this->buffer_idH))	$idH[$row['uid']]['subrow']=$this->buffer_idH;
				$exp=1;	// Set "did expanded" flag
			} else {
				$nextCount=$this->getCount($newID);
				$exp=0;	// Clear "did expanded" flag
			}
				// Set HTML-icons, if any:
			if ($this->makeHTML)	{
				$HTML = $depthData.$this->PMicon($row,$a,$c,$nextCount,$exp);
				$HTML.=$this->wrapStop($this->wrapIcon($this->getIcon($row),$row),$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = Array(
				'row'=>$row,
				'HTML'=>$HTML,
				'invertedDepth'=>$depth,
				'blankLineCode'=>$blankLineCode
			);

		}

		$this->getDataFree($res);
		$this->buffer_idH=$idH;
		return $c;
	}


	/**
	 * getting the tree data: init
	 *
	 * @param	integer		parent item id
	 * @return	mixed		data handle
	 */
	function getDataInit($parentId) {
		if ($this->table) {
			$query = 'SELECT '.implode($this->fieldArray,',').' FROM '.$this->table.
					' WHERE '.$this->parentField.'="'.addslashes($parentId).'"'.
					$this->clause;
			$res = mysql(TYPO3_db, $query);
			if (mysql_error())	{
				echo mysql_error();
				debug($query);
			}
			return $res;
		} else {
			if (!is_array($this->dataLookup[$parentId]['subLevel'])) {
				$parentId = -1;
			} else {
				reset($this->dataLookup[$parentId]['subLevel']);
			}
			return $parentId;
		}
	}

	/**
	 * getting the tree data: count
	 *
	 * @param	mixed		data handle
	 * @return	integer		number of items
	 */
	function getDataCount($res) {
		if ($this->table) {
			$c=mysql_num_rows($res);
			return $c;
		} else {
			return count($this->dataLookup[$res]['subLevel']);
		}
	}

	/**
	 * getting the tree data: next entry
	 *
	 * @param	mixed		data handle
	 * @return	array		item data array
	 */
	function getDataNext($res){
		if ($this->table) {
			return @mysql_fetch_assoc($res);
		} else {
			if ($res<0) {
				$row=FALSE;
			} else {
				list(,$row) = each($this->dataLookup[$res]['subLevel']);
			}
			return $row;
		}
	}

	/**
	 * getting the tree data: frees data handle
	 * 
	 * @param	mixed		data handle
	 * @return	void		
	 */
	function getDataFree($res){
		if ($this->table) {
			mysql_free_result($res);
		} else {
		#	unset();
		}
	}



	function setDataFromArray(&$dataArr,$traverse=FALSE,$pid=0)	{

		if (!$traverse) {
			$this->data = &$dataArr;
			$this->dataLookup=array();
				// add root
			$this->dataLookup[0]['subLevel']=&$dataArr;
		}

		foreach($dataArr as $uid => $val)	{

			$dataArr[$uid]['uid']=$uid;
			$dataArr[$uid]['pid']=$pid;

				// gives quick access to id's
			$this->dataLookup[$uid] = &$dataArr[$uid];

			if (is_array($val['subLevel'])) {
				$this->setDataFromArray($dataArr[$uid]['subLevel'],TRUE,$uid);
			}
		}
	}


	/*
		array(
			[id1] => array(
				'title'=>'title...',
				'id' => 'id1',
				'icon' => 'icon ref, relative to typo3/ folder...'
			),
			[id2] => array(
				'title'=>'title...',
				'id' => 'id2',
				'icon' => 'icon ref, relative to typo3/ folder...'
			),
			[id3] => array(
				'title'=>'title...',
				'id' => 'id3',
				'icon' => 'icon ref, relative to typo3/ folder...'
				'subLevel' => array(
					[id3_asdf#1] => array(
						'title'=>'title...',
						'id' => 'asdf#1',
						'icon' => 'icon ref, relative to typo3/ folder...'
					),
					[5] => array(
						'title'=>'title...',
						'id' => 'id...',
						'icon' => 'icon ref, relative to typo3/ folder...'
					),
					[6] => array(
						'title'=>'title...',
						'id' => 'id...', 
						'icon' => 'icon ref, relative to typo3/ folder...'
					),
				)
			),
		)
*/





}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_treeview.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_treeview.php']);
}
?>