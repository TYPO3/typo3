<?php
namespace TYPO3\CMS\Backend\Controller;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class for 'db_new'
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class NewRecordController {

	/**
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * @todo Define visibility
	 */
	public $pidInfo;

	/**
	 * @todo Define visibility
	 */
	public $newPagesInto;

	/**
	 * @todo Define visibility
	 */
	public $newContentInto;

	/**
	 * @todo Define visibility
	 */
	public $newPagesAfter;

	/**
	 * Determines, whether "Select Position" for new page should be shown
	 *
	 * @var boolean $newPagesSelectPosition
	 */
	protected $newPagesSelectPosition = TRUE;

	/**
	 * @todo Define visibility
	 */
	public $web_list_modTSconfig;

	/**
	 * @todo Define visibility
	 */
	public $allowedNewTables;

	/**
	 * @todo Define visibility
	 */
	public $deniedNewTables;

	/**
	 * @todo Define visibility
	 */
	public $web_list_modTSconfig_pid;

	/**
	 * @todo Define visibility
	 */
	public $allowedNewTables_pid;

	/**
	 * @todo Define visibility
	 */
	public $deniedNewTables_pid;

	/**
	 * @todo Define visibility
	 */
	public $code;

	/**
	 * @todo Define visibility
	 */
	public $R_URI;

	// Internal, static: GPvar
	// see init()
	/**
	 * @todo Define visibility
	 */
	public $id;

	// Return url.
	/**
	 * @todo Define visibility
	 */
	public $returnUrl;

	// pagesOnly flag.
	/**
	 * @todo Define visibility
	 */
	public $pagesOnly;

	// Internal
	// see init()
	/**
	 * @todo Define visibility
	 */
	public $perms_clause;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Accumulated HTML output
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @todo Define visibility
	 */
	public $tRows;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['SOBE'] = $this;
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');
		$GLOBALS['BACK_PATH'] = '';

		$this->init();
	}

	/**
	 * Constructor function for the class
	 *
	 * @return void
	 */
	protected function init() {
		// Page-selection permission clause (reading)
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		// This will hide records from display - it has nothing to do with user rights!!
		if ($pidList = $GLOBALS['BE_USER']->getTSConfigVal('options.hideRecords.pages')) {
			if ($pidList = $GLOBALS['TYPO3_DB']->cleanIntList($pidList)) {
				$this->perms_clause .= ' AND pages.uid NOT IN (' . $pidList . ')';
			}
		}
		// Setting GPvars:
		// The page id to operate from
		$this->id = (int)GeneralUtility::_GP('id');
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
		$this->pagesOnly = GeneralUtility::_GP('pagesOnly');
		// Create instance of template class for output
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/db_new.html');
		$this->doc->JScode = '';
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		// Creating content
		$this->content = '';
		$this->content .= $this->doc->header($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle'));
		// Id a positive id is supplied, ask for the page record with permission information contained:
		if ($this->id > 0) {
			$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		}
		// If a page-record was returned, the user had read-access to the page.
		if ($this->pageinfo['uid']) {
			// Get record of parent page
			$this->pidInfo = BackendUtility::getRecord('pages', $this->pageinfo['pid']);
			// Checking the permissions for the user with regard to the parent page: Can he create new pages, new content record, new page after?
			if ($GLOBALS['BE_USER']->doesUserHaveAccess($this->pageinfo, 8)) {
				$this->newPagesInto = 1;
			}
			if ($GLOBALS['BE_USER']->doesUserHaveAccess($this->pageinfo, 16)) {
				$this->newContentInto = 1;
			}
			if (($GLOBALS['BE_USER']->isAdmin() || is_array($this->pidInfo)) && $GLOBALS['BE_USER']->doesUserHaveAccess($this->pidInfo, 8)) {
				$this->newPagesAfter = 1;
			}
		} elseif ($GLOBALS['BE_USER']->isAdmin()) {
			// Admins can do it all
			$this->newPagesInto = 1;
			$this->newContentInto = 1;
			$this->newPagesAfter = 0;
		} else {
			// People with no permission can do nothing
			$this->newPagesInto = 0;
			$this->newContentInto = 0;
			$this->newPagesAfter = 0;
		}
	}

	/**
	 * Main processing, creating the list of new record tables to select from
	 *
	 * @return void
	 */
	public function main() {
		// If there was a page - or if the user is admin (admins has access to the root) we proceed:
		if ($this->pageinfo['uid'] || $GLOBALS['BE_USER']->isAdmin()) {
			// Acquiring TSconfig for this module/current page:
			$this->web_list_modTSconfig = BackendUtility::getModTSconfig($this->pageinfo['uid'], 'mod.web_list');
			$this->allowedNewTables = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig['properties']['allowedNewTables'], TRUE);
			$this->deniedNewTables = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig['properties']['deniedNewTables'], TRUE);
			// Acquiring TSconfig for this module/parent page:
			$this->web_list_modTSconfig_pid = BackendUtility::getModTSconfig($this->pageinfo['pid'], 'mod.web_list');
			$this->allowedNewTables_pid = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig_pid['properties']['allowedNewTables'], TRUE);
			$this->deniedNewTables_pid = GeneralUtility::trimExplode(',', $this->web_list_modTSconfig_pid['properties']['deniedNewTables'], TRUE);
			// More init:
			if (!$this->showNewRecLink('pages')) {
				$this->newPagesInto = 0;
			}
			if (!$this->showNewRecLink('pages', $this->allowedNewTables_pid, $this->deniedNewTables_pid)) {
				$this->newPagesAfter = 0;
			}
			// Set header-HTML and return_url
			if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
				$iconImgTag = IconUtility::getSpriteIconForRecord('pages', $this->pageinfo, array('title' => htmlspecialchars($this->pageinfo['_thePath'])));
				$title = strip_tags($this->pageinfo[$GLOBALS['TCA']['pages']['ctrl']['label']]);
			} else {
				$iconImgTag = IconUtility::getSpriteIcon('apps-pagetree-root', array('title' => htmlspecialchars($this->pageinfo['_thePath'])));
				$title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
			}
			$this->code = '<span class="typo3-moduleHeader">' . $this->doc->wrapClickMenuOnIcon($iconImgTag, 'pages', $this->pageinfo['uid']) . htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, 45)) . '</span><br />';
			$this->R_URI = $this->returnUrl;
			// GENERATE the HTML-output depending on mode (pagesOnly is the page wizard)
			// Regular new element:
			if (!$this->pagesOnly) {
				$this->regularNew();
			} elseif ($this->showNewRecLink('pages')) {
				// Pages only wizard
				$this->pagesOnly();
			}
			// Add all the content to an output section
			$this->content .= $this->doc->section('', $this->code);
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['CONTENT'] = $this->content;
			// Build the <body> for the module
			$this->content = $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle'));
			$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			$this->content .= $this->doc->endPage();
			$this->content = $this->doc->insertStylesAndJS($this->content);
		}
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'back' => '',
			'view' => '',
			'new_page' => ''
		);
		// Regular new element:
		if (!$this->pagesOnly) {
			// New page
			if ($this->showNewRecLink('pages')) {
				$buttons['new_page'] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(array('pagesOnly' => '1'))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xlf:newPage', TRUE) . '">' . IconUtility::getSpriteIcon('actions-page-new') . '</a>';
			}
			// CSH
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'new_regular', $GLOBALS['BACK_PATH'], '', TRUE);
		} elseif ($this->showNewRecLink('pages')) {
			// Pages only wizard
			// CSH
			$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'new_pages', $GLOBALS['BACK_PATH'], '', TRUE);
		}
		// Back
		if ($this->R_URI) {
			$buttons['back'] = '<a href="' . htmlspecialchars($this->R_URI) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' . IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
		}
		if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
			// View
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], $this->backPath, BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
		}
		return $buttons;
	}

	/**
	 * Creates the position map for pages wizard
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function pagesOnly() {
		$numberOfPages = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'pages', '1=1' . BackendUtility::deleteClause('pages'));
		if ($numberOfPages > 0) {
			$this->code .= '
				<h3>' . htmlspecialchars($GLOBALS['LANG']->getLL('selectPosition')) . ':</h3>
			';
			$positionMap = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PagePositionMap', 'newRecordLocalPageTree');
			/** @var $positionMap \TYPO3\CMS\Backend\Tree\View\PagePositionMap */
			$this->code .= $positionMap->positionTree($this->id, $this->pageinfo, $this->perms_clause, $this->R_URI);
		} else {
			// No pages yet, no need to prompt for position, redirect to page creation.
			$javascript = BackendUtility::editOnClick('returnUrl=%2Ftypo3%2Fdb_new.php%3Fid%3D0%26pagesOnly%3D1&edit[pages][0]=new&returnNewPageId=1');
			$startPos = strpos($javascript, 'href=\'') + 6;
			$endPos = strpos($javascript, '\';');
			$url = substr($javascript, $startPos, $endPos - $startPos);
			@ob_end_clean();
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($url);
		}
	}

	/**
	 * Create a regular new element (pages and records)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function regularNew() {
		$doNotShowFullDescr = FALSE;
		// Initialize array for accumulating table rows:
		$this->tRows = array();
		// tree images
		$halfLine = '<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/line.gif', 'width="18" height="16"') . ' alt="" />';
		$firstLevel = '<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/join.gif', 'width="18" height="16"') . ' alt="" />';
		$secondLevel = '<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/line.gif', 'width="18" height="16"') . ' alt="" />' .
						'<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/join.gif', 'width="18" height="16"') . ' alt="" />';
		$secondLevelLast = '<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/line.gif', 'width="18" height="16"') . ' alt="" />' .
						'<img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/joinbottom.gif', 'width="18" height="16"') . ' alt="" />';
		// Get TSconfig for current page
		$pageTS = BackendUtility::getPagesTSconfig($this->id);
		// Finish initializing new pages options with TSconfig
		// Each new page option may be hidden by TSconfig
		// Enabled option for the position of a new page
		$this->newPagesSelectPosition = !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageSelectPosition']);
		// Pseudo-boolean (0/1) for backward compatibility
		$displayNewPagesIntoLink = $this->newPagesInto && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageInside']) ? 1 : 0;
		$displayNewPagesAfterLink = $this->newPagesAfter && !empty($pageTS['mod.']['wizards.']['newRecord.']['pages.']['show.']['pageAfter']) ? 1 : 0;
		// Slight spacer from header:
		$this->code .= '<div class="typo3-newRecord-treeline">' . $halfLine . '</div>';
		// New Page
		$table = 'pages';
		$v = $GLOBALS['TCA'][$table];
		$pageIcon = IconUtility::getSpriteIconForRecord($table, array());
		$newPageIcon = IconUtility::getSpriteIcon('actions-page-new');
		$rowContent = '';
		// New pages INSIDE this pages
		$newPageLinks = array();
		if ($displayNewPagesIntoLink && $this->isTableAllowedForThisPage($this->pageinfo, 'pages') && $this->getBackendUserAuthentication()->check('tables_modify', 'pages') && $this->getBackendUserAuthentication()->workspaceCreateNewRecord(($this->pageinfo['_ORIG_uid'] ?: $this->id), 'pages')) {
			// Create link to new page inside:
			$newPageLinks[] = $this->linkWrap(IconUtility::getSpriteIconForRecord($table, array()) . $GLOBALS['LANG']->sL($v['ctrl']['title'], TRUE) . ' (' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.inside', TRUE) . ')', $table, $this->id);
		}
		// New pages AFTER this pages
		if ($displayNewPagesAfterLink && $this->isTableAllowedForThisPage($this->pidInfo, 'pages') && $this->getBackendUserAuthentication()->check('tables_modify', 'pages') && $this->getBackendUserAuthentication()->workspaceCreateNewRecord($this->pidInfo['uid'], 'pages')) {
			$newPageLinks[] = $this->linkWrap($pageIcon . $GLOBALS['LANG']->sL($v['ctrl']['title'], TRUE) . ' (' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.after', TRUE) . ')', 'pages', -$this->id);
		}
		// New pages at selection position
		if ($this->newPagesSelectPosition) {
			// Link to page-wizard:
			$newPageLinks[] = '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(array('pagesOnly' => 1))) . '">' . $pageIcon . htmlspecialchars($GLOBALS['LANG']->getLL('pageSelectPosition')) . '</a>';
		}
		// Assemble all new page links
		$numPageLinks = count($newPageLinks);
		for ($i = 0; $i < $numPageLinks; $i++) {
			// For the last link, use the "branch bottom" icon
			if ($i == $numPageLinks - 1) {
				$treeComponent = $secondLevelLast;
			} else {
				$treeComponent = $secondLevel;
			}
			$rowContent .= '<div class="typo3-newRecord-treeline">' . $treeComponent . $newPageLinks[$i] . '</div>';
		}
		// Add row header and half-line if not empty
		if (!empty($rowContent)) {
			$rowContent .= '<div class="typo3-newRecord-treeline">' . $halfLine . '</div>';
			$rowContent = '<div class="typo3-newRecord-treeline">' . $firstLevel . $newPageIcon . '&nbsp;<strong>' .
				$GLOBALS['LANG']->getLL('createNewPage') . '</strong></div>' . $rowContent;
		}
		// Compile table row to show the icon for "new page (select position)"
		$startRows = array();
		if ($this->showNewRecLink('pages') && !empty($rowContent)) {
			$startRows[] = '
				<tr>
					<td nowrap="nowrap">' . $rowContent . '</td>
					<td>' . BackendUtility::wrapInHelp($table, '') . '</td>
				</tr>
			';
		}
		// New tables (but not pages) INSIDE this pages
		$isAdmin = $GLOBALS['BE_USER']->isAdmin();
		$newContentIcon = IconUtility::getSpriteIcon('actions-document-new');
		if ($this->newContentInto) {
			if (is_array($GLOBALS['TCA'])) {
				$groupName = '';
				foreach ($GLOBALS['TCA'] as $table => $v) {
					$count = count($GLOBALS['TCA'][$table]);
					$counter = 1;
					if ($table != 'pages'
						&& $this->showNewRecLink($table)
						&& $this->isTableAllowedForThisPage($this->pageinfo, $table)
						&& $GLOBALS['BE_USER']->check('tables_modify', $table)
						&& (($v['ctrl']['rootLevel'] xor $this->id) || $v['ctrl']['rootLevel'] == -1)
						&& $GLOBALS['BE_USER']->workspaceCreateNewRecord(($this->pageinfo['_ORIG_uid'] ? $this->pageinfo['_ORIG_uid'] : $this->id), $table)
					) {
						$newRecordIcon = IconUtility::getSpriteIconForRecord($table, array());
						$rowContent = '';
						// Create new link for record:
						$newLink = $this->linkWrap($newRecordIcon . $GLOBALS['LANG']->sL($v['ctrl']['title'], TRUE), $table, $this->id);
						// If the table is 'tt_content' (from "cms" extension), create link to wizard
						if ($table == 'tt_content') {
							$groupName = $GLOBALS['LANG']->getLL('createNewContent');
							$rowContent = '<div class="typo3-newRecord-treeline">' . $firstLevel . $newContentIcon . '&nbsp;<strong>' . $GLOBALS['LANG']->getLL('createNewContent') . '</strong></div>';
							// If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's wizard instead:
							$overrideExt = $this->web_list_modTSconfig['properties']['newContentWiz.']['overrideWithExtension'];
							$pathToWizard = ExtensionManagementUtility::isLoaded($overrideExt) ? ExtensionManagementUtility::extRelPath($overrideExt) . 'mod1/db_new_content_el.php' : 'sysext/cms/layout/db_new_content_el.php';
							$href = $pathToWizard . '?id=' . $this->id . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
							$rowContent .= '<div class="typo3-newRecord-treeline">' . $secondLevel . $newLink . '</div><div class="typo3-newRecord-treeline">' . $secondLevelLast . '<a href="' . htmlspecialchars($href) . '">' . $newContentIcon . htmlspecialchars($GLOBALS['LANG']->getLL('clickForWizard')) . '</a></div>';
							// Half-line added:
							$rowContent .= '<div class="typo3-newRecord-treeline">' . $halfLine . '</div>';
						} else {
							// Get the title
							if ($v['ctrl']['readOnly'] || $v['ctrl']['hideTable'] || $v['ctrl']['is_static']) {
								continue;
							}
							if ($v['ctrl']['adminOnly'] && !$isAdmin) {
								continue;
							}
							$nameParts = explode('_', $table);
							$thisTitle = '';
							if ($nameParts[0] == 'tx' || $nameParts[0] == 'tt') {
								// Try to extract extension name
								if (substr($v['ctrl']['title'], 0, 8) == 'LLL:EXT:') {
									$_EXTKEY = substr($v['ctrl']['title'], 8);
									$_EXTKEY = substr($_EXTKEY, 0, strpos($_EXTKEY, '/'));
									if ($_EXTKEY != '') {
										// First try to get localisation of extension title
										$temp = explode(':', substr($v['ctrl']['title'], 9 + strlen($_EXTKEY)));
										$langFile = $temp[0];
										$thisTitle = $GLOBALS['LANG']->sL('LLL:EXT:' . $_EXTKEY . '/' . $langFile . ':extension.title');
										// If no localisation available, read title from ext_emconf.php
										if (!$thisTitle && is_file(ExtensionManagementUtility::extPath($_EXTKEY) . 'ext_emconf.php')) {
											include ExtensionManagementUtility::extPath($_EXTKEY) . 'ext_emconf.php';
											$thisTitle = $EM_CONF[$_EXTKEY]['title'];
										}
										$iconFile[$_EXTKEY] = '<img ' . 'src="' . ExtensionManagementUtility::extRelPath($_EXTKEY) . $GLOBALS['TYPO3_LOADED_EXT'][$_EXTKEY]['ext_icon'] . '" ' . 'width="16" height="16" ' . 'alt="' . $thisTitle . '" />';
									}
								}
								if (empty($thisTitle)) {
									$_EXTKEY = $nameParts[1];
									$thisTitle = $nameParts[1];
									$iconFile[$_EXTKEY] = '';
								}
							} else {
								$_EXTKEY = 'system';
								$thisTitle = $GLOBALS['LANG']->getLL('system_records');
								$iconFile['system'] = IconUtility::getSpriteIcon('apps-pagetree-root');
							}
							if ($groupName == '' || $groupName != $_EXTKEY) {
								$groupName = empty($v['ctrl']['groupName']) ? $_EXTKEY : $v['ctrl']['groupName'];
							}
							$rowContent .= $newLink;
							$counter++;
						}
						// Compile table row:
						if ($table == 'tt_content') {
							$startRows[] = '
								<tr>
									<td nowrap="nowrap">' . $rowContent . '</td>
									<td>' . BackendUtility::wrapInHelp($table, '') . '</td>
								</tr>';
						} else {
							$this->tRows[$groupName]['title'] = $thisTitle;
							$this->tRows[$groupName]['html'][] = $rowContent;
							$this->tRows[$groupName]['table'][] = $table;
						}
					}
				}
			}
		}
		// User sort
		if (isset($pageTS['mod.']['wizards.']['newRecord.']['order'])) {
			$this->newRecordSortList = GeneralUtility::trimExplode(',', $pageTS['mod.']['wizards.']['newRecord.']['order'], TRUE);
		}
		uksort($this->tRows, array($this, 'sortNewRecordsByConfig'));
		// Compile table row:
		$finalRows = array();
		$finalRows[] = implode('', $startRows);
		foreach ($this->tRows as $key => $value) {
			$row = '<tr>
						<td nowrap="nowrap"><div class="typo3-newRecord-treeline">' . $halfLine . '</div><div class="typo3-newRecord-treeline">' . $firstLevel .
				$iconFile[$key] . '&nbsp;<strong>' . $value['title'] . '</strong></div></td><td>&nbsp;<br />' . BackendUtility::wrapInHelp($key, '') . '</td>
						</tr>';
			$count = count($value['html']) - 1;
			foreach ($value['html'] as $recordKey => $record) {
				$row .= '
					<tr>
						<td nowrap="nowrap"><div class="typo3-newRecord-treeline">' . ($recordKey < $count ? $secondLevel : $secondLevelLast) . $record . '</div></td>
						<td>' . BackendUtility::wrapInHelp($value['table'][$recordKey], '') . '</td>
					</tr>';
			}
			$finalRows[] = $row;
		}
		// end of tree
		$finalRows[] = '
			<tr>
				<td><img' . IconUtility::skinImg($this->doc->backPath, 'gfx/ol/stopper.gif', 'width="18" height="16"') . ' alt="" /></td>
				<td></td>
			</tr>
		';
		// Make table:
		$this->code .= '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-newRecord">
			' . implode('', $finalRows) . '
			</table>
		';
	}

	/**
	 * User array sort function used by regularNew
	 *
	 * @param string $a First array element for compare
	 * @param string $b First array element for compare
	 * @return integer -1 for lower, 0 for equal, 1 for greater
	 * @todo Define visibility
	 */
	public function sortNewRecordsByConfig($a, $b) {
		if (count($this->newRecordSortList)) {
			if (in_array($a, $this->newRecordSortList) && in_array($b, $this->newRecordSortList)) {
				// Both are in the list, return relative to position in array
				$sub = array_search($a, $this->newRecordSortList) - array_search($b, $this->newRecordSortList);
				$ret = ($sub < 0 ? -1 : $sub == 0) ? 0 : 1;
			} elseif (in_array($a, $this->newRecordSortList)) {
				// First element is in array, put to top
				$ret = -1;
			} elseif (in_array($b, $this->newRecordSortList)) {
				// Second element is in array, put first to bottom
				$ret = 1;
			} else {
				// No element is in array, return alphabetic order
				$ret = strnatcasecmp($this->tRows[$a]['title'], $this->tRows[$b]['title']);
			}
			return $ret;
		} else {
			// Return alphabetic order
			return strnatcasecmp($this->tRows[$a]['title'], $this->tRows[$b]['title']);
		}
	}

	/**
	 * Ending page output and echo'ing content to browser.
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Links the string $code to a create-new form for a record in $table created on page $pid
	 *
	 * @param string $linkText Link text
	 * @param string $table Table name (in which to create new record)
	 * @param integer $pid PID value for the "&edit['.$table.']['.$pid.']=new" command (positive/negative)
	 * @param boolean $addContentTable If $addContentTable is set, then a new contentTable record is created together with pages
	 * @return string The link.
	 * @todo Define visibility
	 */
	public function linkWrap($linkText, $table, $pid, $addContentTable = FALSE) {
		$parameters = '&edit[' . $table . '][' . $pid . ']=new';
		if ($table == 'pages' && $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] && isset($GLOBALS['TCA'][$GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']]) && $addContentTable) {
			$parameters .= '&edit[' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] . '][prev]=new&returnNewPageId=1';
		} elseif ($table == 'pages_language_overlay') {
			$parameters .= '&overrideVals[pages_language_overlay][doktype]=' . (int)$this->pageinfo['doktype'];
		}
		$onClick = BackendUtility::editOnClick($parameters, '', $this->returnUrl);
		return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $linkText . '</a>';
	}

	/**
	 * Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 *
	 * @param array $pid_row Record for parent page.
	 * @param string $checkTable Table name to check
	 * @return boolean Returns TRUE if the tablename $checkTable is allowed to be created on the page with record $pid_row
	 * @todo Define visibility
	 */
	public function isTableAllowedForThisPage($pid_row, $checkTable) {
		if (!is_array($pid_row)) {
			if ($GLOBALS['BE_USER']->user['admin']) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
		// be_users and be_groups may not be created anywhere but in the root.
		if ($checkTable == 'be_users' || $checkTable == 'be_groups') {
			return FALSE;
		}
		// Checking doktype:
		$doktype = (int)$pid_row['doktype'];
		if (!($allowedTableList = $GLOBALS['PAGES_TYPES'][$doktype]['allowedTables'])) {
			$allowedTableList = $GLOBALS['PAGES_TYPES']['default']['allowedTables'];
		}
		// If all tables or the table is listed as a allowed type, return TRUE
		if (strstr($allowedTableList, '*') || GeneralUtility::inList($allowedTableList, $checkTable)) {
			return TRUE;
		}
	}

	/**
	 * Returns TRUE if:
	 * - $allowedNewTables and $deniedNewTables are empty
	 * - the table is not found in $deniedNewTables and $allowedNewTables is not set or the $table tablename is found in $allowedNewTables
	 *
	 * If $table tablename is found in $allowedNewTables and $deniedNewTables, $deniedNewTables
	 * has priority over $allowedNewTables.
	 *
	 * @param string $table Table name to test if in allowedTables
	 * @param array $allowedNewTables Array of new tables that are allowed.
	 * @param array $deniedNewTables Array of new tables that are not allowed.
	 * @return boolean Returns TRUE if a link for creating new records should be displayed for $table
	 * @todo Define visibility
	 */
	public function showNewRecLink($table, array $allowedNewTables = array(), array $deniedNewTables = array()) {

		if (!$this->getBackendUserAuthentication()->check('tables_modify', $table)) {
			return FALSE;
		}

		$allowedNewTables = $allowedNewTables ?: $this->allowedNewTables;
		$deniedNewTables = $deniedNewTables ?: $this->deniedNewTables;
		// No deny/allow tables are set:
		if (!count($allowedNewTables) && !count($deniedNewTables)) {
			return TRUE;
		} elseif (!in_array($table, $deniedNewTables) && (!count($allowedNewTables) || in_array($table, $allowedNewTables))) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Returns the global BackendUserAuthentication object.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}
}
