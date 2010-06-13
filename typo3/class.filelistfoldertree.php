<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Folder navigation tree for the File main module
 *
 * @author	Benjamin Mack   <bmack@xnos.org>
 *
 *
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   71: class fileListTree extends t3lib_browseTree
 *   81:     function webPageTree()
 *   92:     function wrapIcon($icon,&$row)
 *  130:     function wrapStop($str,$row)
 *  146:     function wrapTitle($title,$row,$bank=0)
 *  165:     function printTree($treeArr = '')
 *  271:     function PMicon($row,$a,$c,$nextCount,$exp)
 *  292:     function PMiconATagWrap($icon, $cmd, $isExpand = true)
 *  309:     function getBrowsableTree()
 *  377:     function getTree($uid, $depth=999, $depthData='',$blankLineCode='',$subCSSclass='')
 *
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Extension class for the t3lib_filetree class, needed for drag and drop and ajax functionality
 *
 * @author	Sebastian Kurfuerst <sebastian@garbage-group.de>
 * @author	Benjamin Mack   <bmack@xnos.org>
 * @package TYPO3
 * @subpackage core
 * @see class t3lib_browseTree
 */
class filelistFolderTree extends t3lib_folderTree {

	var $ext_IconMode;
	var $ajaxStatus = false; // Indicates, whether the ajax call was successful, i.e. the requested page has been found

	/**
	 * Calls init functions
	 *
	 * @return	void
	 */
	function filelistFolderTree() {
		parent::t3lib_folderTree();
	}

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param	string		Icon IMG code
	 * @param	array		Data row for element.
	 * @return	string		Page icon
	 */
	function wrapIcon($theFolderIcon, &$row)	{

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$theFolderIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theFolderIcon,$row['path'],'',0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$this->getId($row).'\','.$this->bank.');';
			$theFolderIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$theFolderIcon.'</a>';
		}
			// Wrap icon in a drag/drop span.
		return '<span class="dragIcon" id="dragIconID_'.$this->getJumpToParam($row).'">'.$theFolderIcon.'</span>';
	}


	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param	string		Title string
	 * @param	string		Item record
	 * @param	integer		Bank pointer (which mount point number)
	 * @return	string
	 * @access private
	 */
	function wrapTitle($title,$row,$bank=0)	{
		$aOnClick = 'return jumpTo(\''.$this->getJumpToParam($row).'\',this,\''.$this->domIdPrefix.$this->getId($row).'\','.$bank.');';
		$CSM = '';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['useOnContextMenuHandler'])	{
			$CSM = ' oncontextmenu="'.htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('',$row['path'],'',0,'&bank='.$this->bank,'',TRUE)).'"';
		}
		$theFolderTitle='<a href="#" onclick="'.htmlspecialchars($aOnClick).'"'.$CSM.'>'.$title.'</a>';

			// Wrap title in a drag/drop span.
		return '<span class="dragTitle" id="dragTitleID_'.$this->getJumpToParam($row).'">'.$theFolderTitle.'</span>';
	}




	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param	array		"tree-array" - if blank string, the internal ->tree array is used.
	 * @return	string		The HTML code for the tree
	 */
	function printTree($treeArr='')	{
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr = $this->tree;

		$out = '
			<!-- TYPO3 folder tree structure. -->
			<ul class="tree" id="treeRoot">
		';
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;

			// -- evaluate AJAX request
			// IE takes anchor as parameter
		$PM = t3lib_div::_GP('PM');
		if(($PMpos = strpos($PM, '#')) !== false) { $PM = substr($PM, 0, $PMpos); }
		$PM = explode('_', $PM);
		if((TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) && is_array($PM) && count($PM)==4) {
			if($PM[1])	{
				$expandedFolderUid = $PM[2];
				$ajaxOutput = '';
				$invertedDepthOfAjaxRequestedItem = 0; // We don't know yet. Will be set later.
				$doExpand = true;
			} else	{
				$expandedFolderUid = $PM[2];
				$doCollapse = true;
			}
		}


		// we need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
		$closeDepth = array();

		foreach($treeArr as $k => $v)	{
			$classAttr = $v['row']['_CSSCLASS'];
			$uid	   = $v['row']['uid'];
			$idAttr	= htmlspecialchars($this->domIdPrefix.$this->getId($v['row']).'_'.$v['bank']);
			$itemHTML  = '';

			// if this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if($v['isFirst'] && !($doCollapse) && !($doExpand && $expandedFolderUid == $uid))	{
				$itemHTML = "<ul>\n";
			}

			// add CSS classes to the list item
			if($v['hasSub']) { $classAttr = ($classAttr) ? ' expanded': 'expanded'; }
			if($v['isLast']) { $classAttr = ($classAttr) ? ' last'	: 'last';	 }

			$itemHTML .='
				<li id="'.$idAttr.'"'.($classAttr ? ' class="'.$classAttr.'"' : '').'><div class="treeLinkItem">'.
					$v['HTML'].
					$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$v['bank']) . '</div>';


			if(!$v['hasSub']) { $itemHTML .= "</li>\n"; }

			// we have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if($v['isLast'] && !($doExpand && $expandedFolderUid == $uid)) { $closeDepth[$v['invertedDepth']] = 1; }


			// if this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if($v['isLast'] && !$v['hasSub'] && !$doCollapse && !($doExpand && $expandedFolderUid == $uid)) {
				for ($i = $v['invertedDepth']; $closeDepth[$i] == 1; $i++) {
					$closeDepth[$i] = 0;
					$itemHTML .= "</ul></li>\n";
				}
			}

			// ajax request: collapse
			if($doCollapse && $expandedFolderUid == $uid) {
				$this->ajaxStatus = true;
				return $itemHTML;
			}

			// ajax request: expand
			if($doExpand && $expandedFolderUid == $uid) {
				$ajaxOutput .= $itemHTML;
				$invertedDepthOfAjaxRequestedItem = $v['invertedDepth'];
			} elseif($invertedDepthOfAjaxRequestedItem) {
				if($v['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
					$ajaxOutput .= $itemHTML;
				} else {
					$this->ajaxStatus = true;
					return $ajaxOutput;
				}
			}

			$out .= $itemHTML;
		}

		if($ajaxOutput) {
			$this->ajaxStatus = true;
			return $ajaxOutput;
		}

		// finally close the first ul
		$out .= "</ul>\n";
		return $out;
	}


	/**
	 * Generate the plus/minus icon for the browsable tree.
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
	function PMicon($row,$a,$c,$nextCount,$exp)	{
		$PM   = $nextCount ? ($exp ? 'minus' : 'plus') : 'join';
		$BTM  = ($a == $c) ? 'bottom' : '';
		$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.$PM.$BTM.'.gif','width="18" height="16"').' alt="" />';

		if ($nextCount) {
			$cmd = $this->bank.'_'.($exp?'0_':'1_').$row['uid'].'_'.$this->treeName;
			$icon = $this->PMiconATagWrap($icon,$cmd,!$exp);
		}
		return $icon;
	}


	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param	string		HTML string to wrap, probably an image tag.
	 * @param	string		Command for 'PM' get var
	 * @return	string		Link-wrapped input string
	 * @access private
	 */
	function PMiconATagWrap($icon, $cmd, $isExpand = true)	{
		if ($this->thisScript) {
				// activate dynamic ajax-based tree
			$js = htmlspecialchars('Tree.load(\''.$cmd.'\', '.intval($isExpand).', this);');
			return '<a class="pm" onclick="'.$js.'">'.$icon.'</a>';
		} else {
			return $icon;
		}
	}



	/**
	 * Will create and return the HTML code for a browsable tree of folders.
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return	string		HTML code for the browsable tree
	 */
	function getBrowsableTree()	{

			// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();

			// Init done:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

			// Traverse mounts:
		foreach($this->MOUNTS as $key => $val)	{
			$hasSub = false;
			$specUID = t3lib_div::md5int($val['path']);
			$this->specUIDmap[$specUID] = $val['path'];

				// Set first:
			$this->bank = $val['nkey'];
			$isOpen = $this->stored[$val['nkey']][$specUID] || $this->expandFirst;
			$this->reset();

				// Set PM icon:
			$cmd = $this->bank.'_'.($isOpen ? '0_' : '1_').$specUID.'_'.$this->treeName;
			$icon='<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.($isOpen? 'minus':'plus').'only.gif').' alt="" />';
			$firstHtml= $this->PM_ATagWrap($icon,$cmd);

			switch ($val['type']) {
				case 'user':
					$icon = 'apps-filetree-folder-user';
					break;
				case 'group':
					$icon = 'apps-filetree-folder-user';
					break;
				case 'readonly':
					$icon = 'apps-filetree-folder-locked';
					break;
				default:
					$icon = 'apps-filetree-mount';
					break;
			}

				// Preparing rootRec for the mount
			$firstHtml.=$this->wrapIcon(t3lib_iconWorks::getSpriteIcon($icon),$val);
			$row=array();
			$row['uid']   = $specUID;
			$row['path']  = $val['path'];
			$row['title'] = $val['name'];

				// hasSub is true when the root of the mount is expanded
			if ($isOpen) {
				$hasSub = true;
			}
				// Add the root of the mount to ->tree
			$this->tree[] = array('HTML' => $firstHtml, 'row' => $row, 'bank' => $this->bank, 'hasSub' => $hasSub);

				// If the mount is expanded, go down:
			if ($isOpen)
				$this->getFolderTree($val['path'], 999, $val['type']);

				// Add tree:
			$treeArr = array_merge($treeArr, $this->tree);
			
				// if this is an AJAX call, don't run through all mounts, only 
				// show the expansion of the current one, not the rest of the mounts
			if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
				break;
			}
		}
		return $this->printTree($treeArr);
	}



	/**
	 * Fetches the data for the tree
	 *
	 * @param	string		Abs file path
	 * @param	integer		Max depth (recursivity limit)
	 * @return	integer		The count of items on the level
	 * @see getBrowsableTree()
	 */
	function getFolderTree($files_path, $depth=999, $type='')	{

			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);
		if (!is_array($dirs)) return 0;

		sort($dirs);
		$c = count($dirs);

		$depth = intval($depth);
		$HTML = '';
		$a = 0;

		foreach($dirs as $key => $val)	{
			$a++;
			$this->tree[] = array();	// Reserve space.
			end($this->tree);
			$treeKey = key($this->tree);	// Get the key for this space

			$val = preg_replace('/^\.\//','',$val);
			$title = $val;
			$path = $files_path.$val.'/';

			$specUID = t3lib_div::md5int($path);
			$this->specUIDmap[$specUID] = $path;

			$row = array();
			$row['path']  = $path;
			$row['uid']   = $specUID;
			$row['title'] = $title;

			// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($specUID))	{
				$nextCount = $this->getFolderTree(
					$path,
					$depth-1,
					$this->makeHTML ? '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.($a == $c ? 'blank' : 'line').'.gif','width="18" height="16"').' alt="" />' : '',
					$type
				);
				$exp = 1;	// Set "did expand" flag
			} else {
				$nextCount = $this->getCount($path);
				$exp = 0;	// Clear "did expand" flag
			}

				// Set HTML-icons, if any:
			if ($this->makeHTML)	{
				$HTML = $this->PMicon($row,$a,$c,$nextCount,$exp);

				$webpath = t3lib_BEfunc::getPathType_web_nonweb($path);

				if (is_writable($path)) {
					$type = '';
					$overlays = array();
				} else {
					$type = 'readonly';
					$overlays= array('status-overlay-locked'=>array());
					
				}

				if($webpath == 'web') {
					$icon = 'apps-filetree-folder-default';
				} else {
					$icon = 'apps-filetree-folder-default';
				}
				if ($val == '_temp_')	{
					$icon = 'apps-filetree-folder-temp';
					$row['title'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:temp', true);
					$row['_title'] = '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:temp', true) . '</strong>';
				}
				if ($val == '_recycler_')	{
					$icon = 'apps-filetree-recycler';
					$row['title'] = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:recycler', true);
					$row['_title'] = '<strong>' .$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:recycler', true) . '</strong>';
				}
				$HTML .= $this->wrapIcon(t3lib_iconWorks::getSpriteIcon($icon,array('title'=>$row['title']),$overlays),$row);
			}

				// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = Array(
				'row'    => $row,
				'HTML'   => $HTML,
				'hasSub' => $nextCount && $this->expandNext($specUID),
				'isFirst'=> ($a == 1),
				'isLast' => false,
				'invertedDepth'=> $depth,
				'bank'   => $this->bank
			);
		}

		if($a) { $this->tree[$treeKey]['isLast'] = true; }
		return $c;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.filelistfoldertree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/class.filelistfoldertree.php']);
}

?>