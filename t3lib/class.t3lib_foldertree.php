<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2002 Kasper Skaarhoj (kasper@typo3.com)
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
 * Generate a folder tree
 *
 * $Id$
 * Revised for TYPO3 3.6 August/2003 by Kasper Skaarhoj
 * Maintained by René Fritz
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   82: class t3lib_folderTree extends t3lib_treeView  
 *   87:     function t3lib_folderTree()	
 *  107:     function wrapTitle($title,&$row)	
 *  119:     function wrapIcon($icon,&$row)	
 *  139:     function getId(&$v) 
 *  149:     function getJumpToParm(&$v) 
 *  159:     function getTitleStr(&$row)	
 *  168:     function getBrowsableTree()	
 *  249:     function getFolderTree($files_path, $depth=999, $depthData='')	
 *  318:     function getCount($files_path)	
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_t3lib.'class.t3lib_treeview.php');














/**
 * Extension class for the t3lib_browsetree class, specially made for browsing folders in the File module
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 * @see class t3lib_treeView
 */
class t3lib_folderTree extends t3lib_treeView  {

	/**
	 * @return	[type]		...
	 */
	function t3lib_folderTree()	{
		$this->BE_USER = $GLOBALS['BE_USER'];
		$this->titleAttrib = t3lib_BEfunc::titleAttrib();
		$this->backPath = $GLOBALS['BACK_PATH'];

		$this->MOUNTS = $GLOBALS['FILEMOUNTS'];
		$this->treeName='folder';
		$this->titleAttrib=''; //don't apply any title
		$this->domIdPrefix = 'folder';
		// unsused $this->iconName = 'folder';
	}

	/**
	 * Wrapping $title in a-tags.
	 * $row is the array with path and other info.
	 * 
	 * @param	[type]		$title: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,&$row)	{
		$aOnClick = 'return jumpTo('.$this->getJumpToParm($row).',this,'.$this->getId($row).');';
		return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
	}

	/**
	 * Wrapping the folder icon
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,&$row)	{
			// Add border attribute...
		$theFolderIcon = substr($icon,0,-1).' border="0" />';

			// Wrap icon in click-menu link.
		if (!$this->ext_IconMode)	{
			$theFolderIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theFolderIcon,$row['path'],'',0);
		} elseif (!strcmp($this->ext_IconMode,'titlelink'))	{
			$aOnClick = 'return jumpTo('.$this->getJumpToParm($row).'\',this,'.$this->getId($row).');';
			$theFolderIcon='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$theFolderIcon.'</a>';
		}
		return $theFolderIcon;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$v: ...
	 * @return	[type]		...
	 */
	function getId(&$v) {
		return t3lib_div::md5Int($v['path']);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$v: ...
	 * @return	[type]		...
	 */
	function getJumpToParm(&$v) {
		return "'".rawurlencode($v['path'])."'";
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$$row: ...
	 * @return	[type]		...
	 */
	function getTitleStr(&$row)	{
		return $row['_title'] ? $row['_title'] : parent::getTitleStr($row);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function getBrowsableTree()	{
		$this->init($addClause);

			// Get stored tree structure:
		$this->stored=unserialize($this->BE_USER->uc[$this->treeName]);
		
			// Mapping md5-hash to shorter number:
		$hashMap=array();
		reset($this->MOUNTS);
		while (list($key,$val) = each($this->MOUNTS))	{
			$nkey = hexdec(substr($key,0,4));
			$hashMap[$nkey]=$key;
			$this->MOUNTS[$key]['nkey']=$nkey;
		}

			// PM action:
		$PM = explode('_',t3lib_div::GPvar('PM'));
		if (count($PM)==4 && $PM[3]==$this->treeName)	{
			if (isset($this->MOUNTS[$hashMap[$PM[0]]]))	{
				if ($PM[1])	{	// set
					$this->stored[$PM[0]][$PM[2]]=1;
					$this->savePosition($this->treeName);
				} else {	// clear
					unset($this->stored[$PM[0]][$PM[2]]);
					$this->savePosition($this->treeName);
				}
			}
		}


			// traverse mounts:
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		$treeArr=array();
		reset($this->MOUNTS);
		while (list($key,$val) = each($this->MOUNTS))	{
			$md5_uid = md5($val['path']);
			$specUID=hexdec(substr($md5_uid,0,6));
			$this->specUIDmap[$specUID]=$val['path'];

				// Set first:
			$this->bank=$val['nkey'];
			$isOpen = $this->stored[$val['nkey']][$specUID] || $this->expandFirst;
			$this->reset();

				// Set PM icon:
			$cmd=$this->bank.'_'.($isOpen?'0_':'1_').$specUID.'_'.$this->treeName;
			$icon='<img src="'.$this->backPath.'t3lib/gfx/ol/'.($isOpen?'minus':'plus').'only.gif" width="18" height="16" align="top" border="0" alt="" \></a>';
			$firstHtml= $this->PM_ATagWrap($icon,$cmd);

			switch($val['type'])	{
				case 'user':	$icon = 'gfx/i/_icon_ftp_user.gif';	break;
				case 'group':	$icon = 'gfx/i/_icon_ftp_group.gif'; break;
				default:		$icon = 'gfx/i/_icon_ftp.gif'; break;
			}
			
			$firstHtml.=$this->wrapIcon('<img src="'.$this->backPath.$icon.'" width="18" height="16" align="top" alt="" \>',$val);
				$row=array();
				$row['path']=$val['path'];
				$row['uid']=$specUID;
				$row['title']=$val['name'];
			$this->tree[]=array('HTML'=>$firstHtml,'row'=>$row);

			if ($isOpen)	{
					// Set depth:
				$depthD='<img src="'.$this->backPath.'t3lib/gfx/ol/blank.gif" width="18" height="16" align="top" alt="" \>';
				$this->getFolderTree($val['path'],999,$depthD);
			}
				// Add tree:
			$treeArr=array_merge($treeArr,$this->tree);
		}
		return $this->printTree($treeArr);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$files_path: ...
	 * @param	[type]		$depth: ...
	 * @param	[type]		$depthData: ...
	 * @return	[type]		...
	 */
	function getFolderTree($files_path, $depth=999, $depthData='')	{
			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);
//		debug($dirs);
		$c=0;
		if (is_array($dirs))	{
			$depth=intval($depth);
			$HTML='';
			$a=0;
			$c=count($dirs);
			sort($dirs);

			while (list($key,$val)= each($dirs))	{
				$a++;
				$this->tree[]=array();		// Reserve space.
				end($this->tree);
				$treeKey = key($this->tree);	// Get the key for this space
				$LN = ($a==$c)?'blank':'line';

				$val = ereg_replace('^\./','',$val);
				$title = $val;
				$path = $files_path.$val.'/';
				$webpath=t3lib_BEfunc::getPathType_web_nonweb($path);

				$md5_uid = md5($path);
				$specUID=hexdec(substr($md5_uid,0,6));
				$this->specUIDmap[$specUID]=$path;
				$row=array();
				$row['path']=$path;
				$row['uid']=$specUID;
				$row['title']=$title;

				if ($depth>1 && $this->expandNext($specUID))	{
					$nextCount=$this->getFolderTree($path, $depth-1, $this->makeHTML?$depthData.'<img src="'.$this->backPath.'t3lib/gfx/ol/'.$LN.'.gif" width="18" height="16" align="top" alt="" \>':'');
					$exp=1;
				} else {
					$nextCount=$this->getCount($path);
					$exp=0;
				}
	
					// Set HTML-icons, if any:
				if ($this->makeHTML)	{
					$HTML=$depthData.$this->PMicon($row,$a,$c,$nextCount,$exp);

					$icon = 'gfx/i/_icon_'.$webpath.'folders.gif';
					if ($val=='_temp_')	{
						$icon = 'gfx/i/sysf.gif';
						$row['title']='TEMP';
						$row['_title']='<b>TEMP</b>';
					}
					if ($val=='_recycler_')	{
						$icon = 'gfx/i/recycler.gif';
						$row['title']='RECYCLER';
						$row['_title']='<b>RECYCLER</b>';
					}
					$HTML.=$this->wrapIcon('<img src="'.$this->backPath.$icon.'" width="18" height="16" align="top" alt="" \>',$row);
				}
				$this->tree[$treeKey] = Array('row'=>$row, 'HTML'=>$HTML);
			}
		}
		return $c;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$files_path: ...
	 * @return	[type]		...
	 */
	function getCount($files_path)	{
			// This generates the directory tree
		$dirs = t3lib_div::get_dirs($files_path);
		$c=0;
		if (is_array($dirs))	{
			$c=count($dirs);
		}
		return $c;
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_foldertree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_foldertree.php']);
}
?>