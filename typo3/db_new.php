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
 * New database item menu
 *
 * This script lets users choose a new database element to create.
 * Includes a wizard mode for visually pointing out the position of new pages
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant (not with pages wizard yet... position map and other classes needs cleaning)
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   92: class localPageTree extends t3lib_pageTree 
 *  101:     function wrapIcon($icon,$row)	
 *  112:     function expandNext($id)	
 *
 *
 *  125: class SC_db_new 
 *  151:     function init()	
 *  208:     function main()	
 *  265:     function pagesOnly()	
 *  281:     function regularNew()	
 *  414:     function printContent()	
 *  431:     function linkWrap($code,$table,$pid,$addContentTable=0)	
 *  451:     function isTableAllowedForThisPage($pid_row, $checkTable)	
 *  481:     function showNewRecLink($table,$allowedNewTables='')	
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 

 
$BACK_PATH='';
require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_misc.php');

// ***************************
// Including classes
// ***************************
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_positionmap.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');




// ***************************
// Script Classes
// ***************************

/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_pageTree {

	/**
	 * Inserting uid-information in title-text for an icon
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,$row)	{
		return substr($icon,0,-1).' title="id='.htmlspecialchars($row['uid']).'">';
	}

	/**
	 * Determines whether to expand a branch or not.
	 * Here the branch is expanded if the current id matches the global id for the listing/new
	 * 
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function expandNext($id)	{
		return $id==$GLOBALS['SOBE']->id ? 1 : 0;
	}
}


/**
 * Script class for 'db_new'
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_new {
	var $pageinfo;
	var $pidInfo;
	var $newPagesInto;
	var $newContentInto;
	var $newPagesAfter;
	var $web_list_modTSconfig;
	var $allowedNewTables;
	var $web_list_modTSconfig_pid;
	var $allowedNewTables_pid;
	var $code;
	var $R_URI;
	var $code;	
	
		// Internal
	var $perms_clause;	// see init()
	var $id;			// see init()
	var $doc;			// see init()
	var $content;		// Accumulated HTML output 


	/**
	 * Constructor
	 * 
	 * @return	[type]		...
	 */
	function init()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA;

			// page-selection permission clause (reading)
		$this->perms_clause = $BE_USER->getPagePermsClause(1);
			// The page id to operate from
		$this->id = intval(t3lib_div::GPvar('id'));
		
			// Create instance of template class for output
		$this->doc = t3lib_div::makeInstance('smallDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType= 'xhtml_trans';
		$this->doc->JScode='';
		
			// Creating content
		$this->content='';
		$this->content.=$this->doc->startPage($LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.pagetitle'));
		$this->content.=$this->doc->header($LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.pagetitle'));
		
			// Id a positive id is supplied, ask for the page record with permission information contained:
		if ($this->id > 0)	{
			$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		}
		
			// If a page-record was returned, the user had read-access to the page.
		if ($this->pageinfo['uid'])	{
				// Get record of parent page
			$this->pidInfo=t3lib_BEfunc::getRecord('pages',$this->pageinfo['pid']);
				// Checking the permissions for the user with regard to the parent page: Can he create new pages, new content record, new page after?
			if ($BE_USER->doesUserHaveAccess($this->pageinfo,8))	{	
				$this->newPagesInto=1;
			}
			if ($BE_USER->doesUserHaveAccess($this->pageinfo,16))	{
				$this->newContentInto=1;
			}
		
			if (($BE_USER->isAdmin()||is_array($this->pidInfo)) && $BE_USER->doesUserHaveAccess($this->pidInfo,8))	{
				$this->newPagesAfter=1;
			}
		} elseif ($BE_USER->isAdmin())	{
				// Admins can do it all
			$this->newPagesInto=1;
			$this->newContentInto=1;
			$this->newPagesAfter=0;
		} else {
				// People with no permission can do nothing
			$this->newPagesInto=0;
			$this->newContentInto=0;
			$this->newPagesAfter=0;
		}
	}

	/**
	 * Main processing
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA;

			// If there was a page - or if the user is admin (admins has access to the root) we proceed:
		if ($this->pageinfo['uid'] || $BE_USER->isAdmin())	{
				// Acquiring TSconfig for this module/current page:
			$this->web_list_modTSconfig = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
			$this->allowedNewTables = t3lib_div::trimExplode(',',$this->web_list_modTSconfig['properties']['allowedNewTables'],1);
		
				// Acquiring TSconfig for this module/parent page:
			$this->web_list_modTSconfig_pid = t3lib_BEfunc::getModTSconfig($this->pageinfo['pid'],'mod.web_list');
			$this->allowedNewTables_pid = t3lib_div::trimExplode(',',$this->web_list_modTSconfig_pid['properties']['allowedNewTables'],1);
		
				// More init:
			if (!$this->showNewRecLink('pages'))	{
				$this->newPagesInto=0;
			}
			if (!$this->showNewRecLink('pages',$this->allowedNewTables_pid))	{
				$this->newPagesAfter=0;
			}
		
		
				// Set header-HTML and return_url
			$this->code=$this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />
			';
			$this->R_URI=t3lib_div::GPvar('returnUrl');
		
				// If CSH is enabled (Context Sensitive Help), load descriptions for 'pages' in any case:
			if ($BE_USER->uc['edit_showFieldHelp'])	{
				$LANG->loadSingleTableDescription('pages');
			}
		
				// GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
			if (!t3lib_div::GPvar('pagesOnly'))	{	// Regular new element:
				$this->regularNew();
			} elseif ($this->showNewRecLink('pages')) {	// Pages only wizard
				$this->pagesOnly();
			}
		
				// Create go-back link.
			if ($this->R_URI)	{
				$this->code.='<br />
		<a href="'.htmlspecialchars($this->R_URI).'" class="typo3-goBack">'.
		'<img src="gfx/goback.gif" width="14" height="14" hspace="2" border="0" align="top" alt="" />'.
		'<strong>'.$LANG->getLL('goBack').'</strong>'.
		'</a>';
			}
				// Add all the content to an output section
			$this->content.=$this->doc->section('',$this->code);
		}
	}

	/**
	 * Creates the position map for pages wizard
	 * 
	 * @return	[type]		...
	 */
	function pagesOnly()	{
		global $LANG;

		$posMap = t3lib_div::makeInstance('t3lib_positionMap');
		$this->code.='<br />
		<strong>'.htmlspecialchars($LANG->getLL('selectPosition')).':</strong><br />
		<br />
		';
		$this->code.= $posMap->positionTree($this->id,$this->pageinfo,$this->perms_clause,$this->R_URI);
	}

	/**
	 * Create a regular new element (pages and records)
	 * 
	 * @return	[type]		...
	 */
	function regularNew()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA;

			// Slight spacer from header:
		$this->code.='<img src="'.$BACK_PATH.'t3lib/gfx/ol/halfline.gif" width="18" height="8" align="top" alt="" /><br />';
	
			// New pages INSIDE this pages
		if ($this->newPagesInto && $this->isTableAllowedForThisPage($this->pageinfo, 'pages') && $BE_USER->check('tables_modify','pages'))	{

				// Create link to new page inside:
			$t='pages';
			$v=$TCA[$t];
			$this->code.=$this->linkWrap(
						'<img src="'.$BACK_PATH.'t3lib/gfx/ol/join.gif" width="18" height="16" align="top" border="0" alt="" />'.
							'<img src="'.$BACK_PATH.'gfx/i/'.($v['ctrl']['iconfile'] ? $v['ctrl']['iconfile'] : $t.'.gif').'" width="18" height="16" align="top" border="0" alt="" /> '.
							$LANG->sL($v['ctrl']['title'],1).' ('.$LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.inside',1).')',
						$t,
						$this->id);

				// Link to CSH:
			if (isset($TCA_DESCR[$t]['columns']['']))	{
				$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$t.'.\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
				$this->code.='<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
							'<img src="gfx/helpbubble.gif" width="14" height="14" hspace="4" border="0" align="absmiddle"'.$this->doc->helpStyle().' alt="" />'.
							'</a>';
			}
			$this->code.='<br />
			';

				// Link to page-wizard:
			$this->code.='<img src="gfx/ol/line.gif" width="18" height="16" border="0" align="top" alt="" /><img src="gfx/ol/joinbottom.gif" width="18" height="16" border="0" align="top" alt="" />'.
				'<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('pagesOnly'=>1))).'">'.
				'<img src="gfx/new_page.gif" width="13" height="12" border="0" align="top" alt="" /> '.
				htmlspecialchars($LANG->getLL('clickForWizard')).
				'</a><br />
				';
			$this->code.='<img src="gfx/ol/halfline.gif" width="18" height="8" border="0" align="top" alt="" /><br />
			';
		}

			// New tables (but not pages) INSIDE this pages
		if ($this->newContentInto)	{
			if (is_array($TCA))	{
				reset($TCA);
				while(list($t,$v)=each($TCA))	{
					if ($t!='pages' 
							&& $this->showNewRecLink($t)
							&& $this->isTableAllowedForThisPage($this->pageinfo, $t) 
							&& $BE_USER->check('tables_modify',$t) 
							&& (($v['ctrl']['rootLevel'] xor $this->id) || $v['ctrl']['rootLevel']==-1)
							)	{

							// Create new link for record:
						$this->code.=$this->linkWrap(
							'<img src="'.$BACK_PATH.'t3lib/gfx/ol/join.gif" width="18" height="16" align="top" border="0" alt="" />'.
								'<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon($t).'" width="18" height="16" align="top" border="0" alt="" /> '.
								$LANG->sL($v['ctrl']['title'],1)
							,$t
							,$this->id);

							// Create CSH link for table:
						if ($BE_USER->uc['edit_showFieldHelp'])	{
							$LANG->loadSingleTableDescription($t);
							if (isset($TCA_DESCR[$t]['columns']['']))	{
								$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$t.'.\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
								$this->code.='<a href="#" onclick="'.htmlspecialchars($onClick).'">'.
									'<img src="gfx/helpbubble.gif" width="14" height="14" hspace="4" border="0" align="absmiddle"'.$this->doc->helpStyle().' alt="" />'.
									'</a>';
							}
						}
						$this->code.='<br />
						';

							// If the table is 'tt_content' (from "cms" extension), create link to wizard
						if ($t=='tt_content')	{
							$href = 'db_new_content_el.php?id='.$this->id.'&returnUrl='.rawurlencode($this->R_URI);
							$this->code.='<img src="gfx/ol/line.gif" width="18" height="16" border="0" align="top" alt="" />'.
										'<img src="gfx/ol/joinbottom.gif" width="18" height="16" border="0" align="top" alt="" />'.
										'<a href="'.htmlspecialchars($href).'"><img src="gfx/new_record.gif" width="16" height="12" border="0" align="top" alt="" /> '.
										htmlspecialchars($LANG->getLL('clickForWizard')).
										'</a><br />
										';
							$this->code.='<img src="gfx/ol/halfline.gif" width="18" height="8" border="0" align="top" alt="" /><br />
							';
						}
					}
				}
			}
		}

			// New pages AFTER this pages
		if ($this->newPagesAfter && $this->isTableAllowedForThisPage($this->pidInfo,'pages') && $BE_USER->check('tables_modify','pages'))	{

				// Create link to new page after
			$t='pages';
			$v=$TCA[$t];
			$this->code.=$this->linkWrap(
					'<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon($t).'" width="18" height="16" align="top" border="0" alt="" /> '.
						$LANG->sL($v['ctrl']['title'],1).' ('.$LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.after',1).')',
					"pages",
					-$this->id);

				// Link to CSH for pages table:
			if (isset($TCA_DESCR[$t]['columns']['']))	{
				$onClick = 'vHWin=window.open(\'view_help.php?tfID='.$t.'.\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
				$this->code.='<a href="#" onclick="'.htmlspecialchars($onCLick).'">'.
							'<img src="gfx/helpbubble.gif" width="14" height="14" hspace="4" border="0" align="absmiddle"'.$this->doc->helpStyle().' alt="" />'.
							'</a>';
			}
			$this->code.='<br />
			';
		} else {
			$this->code.='<img src="'.$BACK_PATH.'t3lib/gfx/ol/stopper.gif" width="18" height="16" align="top" alt="" /><br />
			';
		}
		
			// Create a link to the new-pages wizard.
		if ($this->showNewRecLink('pages'))	{
			$this->code.='<br />
				<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('pagesOnly'=>'1'))).'">'.
				'<img src="gfx/new_page.gif" width="13" height="12" border="0" align="top" alt="" />'.
				'<img src="clear.gif" width="3" height="1" align="top" border="0" alt="" /><strong>'.
				htmlspecialchars($LANG->getLL('createNewPage')).
				'</strong></a><br />
				';
		}
	}

	/**
	 * Ending page output and echo'ing content to browser.
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Links the string $code to a create-new form for a record in $table created on page $pid
	 * If $addContentTable is set, then a new contentTable record is created together with pages
	 * 
	 * @param	[type]		$code: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$pid: ...
	 * @param	[type]		$addContentTable: ...
	 * @return	[type]		...
	 */
	function linkWrap($code,$table,$pid,$addContentTable=0)	{
		$params = '&edit['.$table.']['.$pid.']=new'.
			($table=='pages' 
				&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] 
				&& isset($GLOBALS["TCA"][$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']]) 
				&& $addContentTable	?
				'&edit['.$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'].'][prev]=new&returnNewPageId=1'	:
				''
			);
		$onClick = t3lib_BEfunc::editOnClick($params,'',t3lib_div::GPvar('returnUrl'));
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$code.'</a>';
	}

	/**
	 * Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 * 
	 * @param	[type]		$pid_row: ...
	 * @param	[type]		$checkTable: ...
	 * @return	[type]		...
	 */
	function isTableAllowedForThisPage($pid_row, $checkTable)	{
		global $TCA, $PAGES_TYPES;
		if (!is_array($pid_row))	{
			if ($GLOBALS['BE_USER']->user['admin'])	{
				return true;
			} else {
				return false;
			}
		}
			// be_users and be_groups may not be created anywhere but in the root.
		if ($checkTable=='be_users' || $checkTable=='be_groups')	{
			return false;
		}
			// Checking doktype:
		$doktype = intval($pid_row['doktype']);
		if (!$allowedTableList = $PAGES_TYPES[$doktype]['allowedTables'])	{
			$allowedTableList = $PAGES_TYPES['default']['allowedTables'];
		}
		if (strstr($allowedTableList,'*') || t3lib_div::inList($allowedTableList,$checkTable))	{		// If all tables or the table is listed as a allowed type, return true
			return true;
		}
	}

	/**
	 * Returns true if the $table tablename is found in $allowedNewTables (or if $allowedNewTables is empty)
	 * 
	 * @param	[type]		$table: ...
	 * @param	[type]		$allowedNewTables: ...
	 * @return	[type]		...
	 */
	function showNewRecLink($table,$allowedNewTables='')	{
		$allowedNewTables = is_array($allowedNewTables) ? $allowedNewTables : $this->allowedNewTables;
		return !count($allowedNewTables) || in_array($table,$allowedNewTables);
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/db_new.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/db_new.php']);
}





// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_new');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
