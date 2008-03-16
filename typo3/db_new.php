<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 *   90: class localPageTree extends t3lib_pageTree
 *   99:     function wrapIcon($icon,$row)
 *  110:     function expandNext($id)
 *
 *
 *  128: class SC_db_new
 *  157:     function init()
 *  224:     function main()
 *  276:     function pagesOnly()
 *  294:     function regularNew()
 *  458:     function printContent()
 *  473:     function linkWrap($code,$table,$pid,$addContentTable=0)
 *  493:     function isTableAllowedForThisPage($pid_row, $checkTable)
 *  523:     function showNewRecLink($table,$allowedNewTables='')
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */




$BACK_PATH='';
require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');

// ***************************
// Including classes
// ***************************
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_positionmap.php');
require_once(PATH_t3lib.'class.t3lib_pagetree.php');





/**
 * Extension for the tree class that generates the tree of pages in the page-wizard mode
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class localPageTree extends t3lib_pageTree {

	/**
	 * Inserting uid-information in title-text for an icon
	 *
	 * @param	string		Icon image
	 * @param	array		Item row
	 * @return	string		Wrapping icon image.
	 */
	function wrapIcon($icon,$row)	{
		return $this->addTagAttributes($icon,' title="id='.htmlspecialchars($row['uid']).'"');
	}

	/**
	 * Determines whether to expand a branch or not.
	 * Here the branch is expanded if the current id matches the global id for the listing/new
	 *
	 * @param	integer		The ID (page id) of the element
	 * @return	boolean		Returns true if the IDs matches
	 */
	function expandNext($id)	{
		return $id==$GLOBALS['SOBE']->id ? 1 : 0;
	}
}







/**
 * Script class for 'db_new'
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
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
	var $deniedNewTables;
	var $web_list_modTSconfig_pid;
	var $allowedNewTables_pid;
	var $deniedNewTables_pid;
	var $code;
	var $R_URI;

		// Internal, static: GPvar
	var $id;			// see init()
	var $returnUrl;		// Return url.
	var $pagesOnly;		// pagesOnly flag.

		// Internal
	var $perms_clause;	// see init()

	/**
	 * Document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;
	var $content;		// Accumulated HTML output


	/**
	 * Constructor function for the class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH;

			// page-selection permission clause (reading)
		$this->perms_clause = $BE_USER->getPagePermsClause(1);

			// this will hide records from display - it has nothing todo with user rights!!
		if ($pidList = $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')) {
			if ($pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList)) {
				$this->perms_clause .= ' AND pages.uid NOT IN ('.$pidList.')';
			}
		}
			// Setting GPvars:
		$this->id = intval(t3lib_div::_GP('id'));	// The page id to operate from
		$this->returnUrl = t3lib_div::_GP('returnUrl');
		$this->pagesOnly = t3lib_div::_GP('pagesOnly');

			// Create instance of template class for output
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/db_new.html');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->JScode='';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Creating content
		$this->content='';
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
	 * Main processing, creating the list of new record tables to select from
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG;

			// If there was a page - or if the user is admin (admins has access to the root) we proceed:
		if ($this->pageinfo['uid'] || $BE_USER->isAdmin())	{
				// Acquiring TSconfig for this module/current page:
			$this->web_list_modTSconfig = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
			$this->allowedNewTables = t3lib_div::trimExplode(',',$this->web_list_modTSconfig['properties']['allowedNewTables'],1);
			$this->deniedNewTables = t3lib_div::trimExplode(',',$this->web_list_modTSconfig['properties']['deniedNewTables'],1);

				// Acquiring TSconfig for this module/parent page:
			$this->web_list_modTSconfig_pid = t3lib_BEfunc::getModTSconfig($this->pageinfo['pid'],'mod.web_list');
			$this->allowedNewTables_pid = t3lib_div::trimExplode(',',$this->web_list_modTSconfig_pid['properties']['allowedNewTables'],1);
			$this->deniedNewTables_pid = t3lib_div::trimExplode(',',$this->web_list_modTSconfig_pid['properties']['deniedNewTables'],1);

				// More init:
			if (!$this->showNewRecLink('pages'))	{
				$this->newPagesInto=0;
			}
			if (!$this->showNewRecLink('pages', $this->allowedNewTables_pid, $this->deniedNewTables_pid))	{
				$this->newPagesAfter=0;
			}


				// Set header-HTML and return_url
			if (is_array($this->pageinfo) && $this->pageinfo['uid'])	{
				$iconImgTag = t3lib_iconWorks::getIconImage('pages', $this->pageinfo, $this->backPath, 'title="' . htmlspecialchars($this->pageinfo['_thePath']) . '"');
				$title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
			} else {
				$iconImgTag = '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/i/_icon_website.gif') . ' title="' . htmlspecialchars($this->pageinfo['_thePath']) . '" alt="" />';
				$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
			}

			$this->code = '<span class="typo3-moduleHeader">' . $this->doc->wrapClickMenuOnIcon($iconImgTag, 'pages', $this->pageinfo['uid']) . htmlspecialchars(t3lib_div::fixed_lgd_cs($title, 45)) . '</span><br />';

			$this->R_URI = $this->returnUrl;

				// GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
			if (!$this->pagesOnly)	{	// Regular new element:
				$this->regularNew();
			} elseif ($this->showNewRecLink('pages')) {	// Pages only wizard
				$this->pagesOnly();
			}

				// Add all the content to an output section
			$this->content.=$this->doc->section('',$this->code);

							// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];

			$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
			$this->content = $this->doc->startPage($LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.pagetitle'));
			$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			$this->content.= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
		}
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	private function getButtons()	{
		global $LANG, $BACK_PATH;

		$buttons = array(
			'csh' => '',
			'back' => '',
			'view' => '',
			'new_page' => '',
			'record_list' => ''
		);


		if (!$this->pagesOnly)	{	// Regular new element:
				// New page
			if ($this->showNewRecLink('pages'))	{
				$buttons['new_page'] = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('pagesOnly' => '1'))) . '">' .
					'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/new_page.gif') . ' alt="" />' .
					'</a>';
			}
				// CSH
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_regular', $GLOBALS['BACK_PATH']);
		} elseif($this->showNewRecLink('pages')) {	// Pages only wizard
				// CSH
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_pages', $GLOBALS['BACK_PATH']);
		}

			// Back
		if ($this->R_URI) {
			$buttons['back'] = '<a href="' . htmlspecialchars($this->R_URI) . '" class="typo3-goBack">' .
				'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/goback.gif') . ' alt="" />' .
				'</a>';
		}

		if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
				// View
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->pageinfo['uid'], $this->backPath, t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '">' .
				'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/zoom.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '" alt="" />' .
				'</a>';

				// Record list
			if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
				$href = $this->backPath . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '">' .
					'<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/list.gif') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', 1) . '" alt="" />' .
					'</a>';
			}
		}



		return $buttons;
	}

	/**
	 * Creates the position map for pages wizard
	 *
	 * @return	void
	 */
	function pagesOnly()	{
		global $LANG;

		$posMap = t3lib_div::makeInstance('t3lib_positionMap');
		$this->code.='
			<h3>'.htmlspecialchars($LANG->getLL('selectPosition')).':</h3>
		';
		$this->code.= $posMap->positionTree($this->id,$this->pageinfo,$this->perms_clause,$this->R_URI);
	}

	/**
	 * Create a regular new element (pages and records)
	 *
	 * @return	void
	 */
	function regularNew()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA;

		$doNotShowFullDescr = FALSE;

			// Slight spacer from header:
		$this->code.='<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/halfline.gif','width="18" height="8"').' alt="" /><br />';

			// Initialize array for accumulating table rows:
		$tRows = array();

			// New pages INSIDE this pages
		if ($this->newPagesInto
			&& $this->isTableAllowedForThisPage($this->pageinfo, 'pages')
			&& $BE_USER->check('tables_modify','pages')
			&& $BE_USER->workspaceCreateNewRecord($this->pageinfo['_ORIG_uid']?$this->pageinfo['_ORIG_uid']:$this->id, 'pages')
			)	{

				// Create link to new page inside:
			$t = 'pages';
			$v = $TCA[$t];
			$rowContent = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join.gif','width="18" height="16"').' alt="" />'.
					$this->linkWrap(
						'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/i/'.($v['ctrl']['iconfile'] ? $v['ctrl']['iconfile'] : $t.'.gif'),'width="18" height="16"').' alt="" />'.
							$LANG->sL($v['ctrl']['title'],1).' ('.$LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.inside',1).')',
						$t,
						$this->id).'<br/>';

				// Link to page-wizard:
			$rowContent.= '<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/line.gif','width="18" height="16"').' alt="" /><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/joinbottom.gif','width="18" height="16"').' alt="" />'.
				'<a href="'.htmlspecialchars(t3lib_div::linkThisScript(array('pagesOnly'=>1))).'">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_page.gif','width="13" height="12"').' alt="" /> '.
				htmlspecialchars($LANG->getLL('clickForWizard')).
				'</a>';
				// Half-line:
			$rowContent.= '<br /><img'.t3lib_iconWorks::skinImg('','gfx/ol/halfline.gif','width="18" height="8"').' alt="" />';

				// Compile table row:
			$tRows[]='
				<tr>
					<td nowrap="nowrap">'.$rowContent.'</td>
					<td>'.t3lib_BEfunc::cshItem($t,'',$GLOBALS['BACK_PATH'],'',$doNotShowFullDescr).'</td>
				</tr>
			';
		}

			// New tables (but not pages) INSIDE this pages
		if ($this->newContentInto)	{
			if (is_array($TCA))	{
				foreach($TCA as $t => $v)	{
					if ($t!='pages'
							&& $this->showNewRecLink($t)
							&& $this->isTableAllowedForThisPage($this->pageinfo, $t)
							&& $BE_USER->check('tables_modify',$t)
							&& (($v['ctrl']['rootLevel'] xor $this->id) || $v['ctrl']['rootLevel']==-1)
							&& $BE_USER->workspaceCreateNewRecord($this->pageinfo['_ORIG_uid']?$this->pageinfo['_ORIG_uid']:$this->id, $t)
							)	{

							// Create new link for record:
						$rowContent = '<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/join.gif','width="18" height="16"').' alt="" />'.
								$this->linkWrap(
								t3lib_iconWorks::getIconImage($t,array(),$BACK_PATH,'').
								$LANG->sL($v['ctrl']['title'],1)
							,$t
							,$this->id);

							// If the table is 'tt_content' (from "cms" extension), create link to wizard
						if ($t=='tt_content')	{

								// If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's wizard instead:
							$overrideExt = $this->web_list_modTSconfig['properties']['newContentWiz.']['overrideWithExtension'];
							$pathToWizard = (t3lib_extMgm::isLoaded($overrideExt)) ? (t3lib_extMgm::extRelPath($overrideExt).'mod1/db_new_content_el.php') : 'sysext/cms/layout/db_new_content_el.php';

							$href = $pathToWizard.'?id='.$this->id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
							$rowContent.= '<br /><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/line.gif','width="18" height="16"').' alt="" />'.
										'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/joinbottom.gif','width="18" height="16"').' alt="" />'.
										'<a href="'.htmlspecialchars($href).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/new_record.gif','width="16" height="12"').' alt="" /> '.
										htmlspecialchars($LANG->getLL('clickForWizard')).
										'</a>';

								// Half-line added:
							$rowContent.= '<br /><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/halfline.gif','width="18" height="8"').' alt="" />';
						}


							// Compile table row:
						$tRows[] = '
				<tr>
					<td nowrap="nowrap">'.$rowContent.'</td>
					<td>'.t3lib_BEfunc::cshItem($t,'',$GLOBALS['BACK_PATH'],'',$doNotShowFullDescr).'</td>
				</tr>
			';

					}
				}
			}
		}

			// New pages AFTER this pages
		if ($this->newPagesAfter
			&& $this->isTableAllowedForThisPage($this->pidInfo,'pages')
			&& $BE_USER->check('tables_modify','pages')
			&& $BE_USER->workspaceCreateNewRecord($this->pidInfo['uid'], 'pages')
			)	{

				// Create link to new page after
			$t = 'pages';
			$v = $TCA[$t];
			$rowContent = $this->linkWrap(
					t3lib_iconWorks::getIconImage($t,array(),$BACK_PATH,'').
						$LANG->sL($v['ctrl']['title'],1).' ('.$LANG->sL('LLL:EXT:lang/locallang_core.php:db_new.php.after',1).')',
					'pages',
					-$this->id
				);

				// Compile table row:
			$tRows[] = '
				<tr>
					<td nowrap="nowrap">'.$rowContent.'</td>
					<td>'.t3lib_BEfunc::cshItem($t,'',$GLOBALS['BACK_PATH'],'',$doNotShowFullDescr).'</td>
				</tr>
			';
		} else {
				// Compile table row:
			$tRows[]='
				<tr>
					<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/stopper.gif','width="18" height="16"').' alt="" /></td>
					<td></td>
				</tr>
			';
		}

			// Make table:
		$this->code.='
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-newRecord">
			'.implode('',$tRows).'
			</table>
		';
	}

	/**
	 * Ending page output and echo'ing content to browser.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Links the string $code to a create-new form for a record in $table created on page $pid
	 *
	 * @param	string		Link string
	 * @param	string		Table name (in which to create new record)
	 * @param	integer		PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
	 * @param	boolean		If $addContentTable is set, then a new contentTable record is created together with pages
	 * @return	string		The link.
	 */
	function linkWrap($code,$table,$pid,$addContentTable=0)	{
		$params = '&edit['.$table.']['.$pid.']=new'.
			($table=='pages'
				&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']
				&& isset($GLOBALS['TCA'][$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']])
				&& $addContentTable	?
				'&edit['.$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'].'][prev]=new&returnNewPageId=1'	:
				''
			);
		$onClick = t3lib_BEfunc::editOnClick($params,'',$this->returnUrl);
		return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$code.'</a>';
	}

	/**
	 * Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 *
	 * @param	array		Record for parent page.
	 * @param	string		Table name to check
	 * @return	boolean		Returns true if the tablename $checkTable is allowed to be created on the page with record $pid_row
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
	 * Returns true if:
	 * - $allowedNewTables and $deniedNewTables are empty
	 * - the table is not found in $deniedNewTables and $allowedNewTables is not set or the $table tablename is found in $allowedNewTables
	 *
	 * If $table tablename is found in $allowedNewTables and $deniedNewTables, $deniedNewTables
	 * has priority over $allowedNewTables.
	 *
	 * @param	string		Table name to test if in allowedTables
	 * @param	array		Array of new tables that are allowed.
	 * @param	array		Array of new tables that are not allowed.
	 * @return	boolean		Returns true if a link for creating new records should be displayed for $table
	 */
	function showNewRecLink($table, array $allowedNewTables=array(), array $deniedNewTables=array()) {
		$allowedNewTables = ($allowedNewTables ? $allowedNewTables : $this->allowedNewTables);
		$deniedNewTables = ($deniedNewTables ? $deniedNewTables : $this->deniedNewTables);
			// No deny/allow tables are set:
		if (!count($allowedNewTables) && !count($deniedNewTables)) {
			return true;
			// If table is not denied (which takes precedence over allowed tables):
		} elseif (!in_array($table, $deniedNewTables) && (!count($allowedNewTables) || in_array($table, $allowedNewTables))) {
			return true;
			// If table is denied or allowed tables are set, but table is not part of:
		} else {
			return false;
		}
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
