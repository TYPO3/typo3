<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Library with Workspace GUI related functionality. It is used by main workspace
 * module but also can be used from extensions. Originally 99.9%% of the code
 * was written by Kasper and only transfered here by Dmitry.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   84: class wslib_gui
 *
 *              SECTION: Public functions
 *  128:     function getWorkspaceOverview(&$doc, $wsid = null, $filter = 0, $pageId = -1)
 *  192:     function hlSubelements(origId, verId, over, diffLayer)
 *
 *              SECTION: Private functions (do not use outside of this class!)
 *  224:     function initVars()
 *  253:     function displayWorkspaceOverview_setInPageArray(&$pArray, $rlArr, $table, $row)
 *  284:     function markupNewOriginals()
 *  309:     function displayWorkspaceOverview_list($pArray, $tableRows=array(), $c=0, $warnAboutVersions=FALSE)
 *  504:     function displayWorkspaceOverview_pageTreeIconTitle($pageUid, $title, $indentCount)
 *  518:     function formatVerId($verId)
 *  529:     function formatWorkspace($wsid)
 *  559:     function createDiffView($table, $diff_1_record, $diff_2_record)
 *  676:     function versionsInOtherWS($table, $uid)
 *  705:     function showStageChangeLog($table,$id,$stageCommands)
 *  757:     function displayWorkspaceOverview_commandLinks($table,&$rec_on,&$rec_off,$vType)
 *  830:     function formatCount($count)
 *  860:     function subElements($uid,$treeLevel,$origId=0)
 *  963:     function subElements_getNonPageRecords($tN, $uid, &$recList)
 *  993:     function subElements_renderItem(&$tCell,$tN,$uid,$rec,$origId,$iconMode,$HTMLdata)
 * 1066:     function displayWorkspaceOverview_commandLinksSub($table,$rec,$origId)
 * 1113:     function displayWorkspaceOverview_stageCmd($table,&$rec_off)
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once('class.wslib.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_user_ws.xml');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');

/**
 * Library with Workspace GUI related functionality. It is used by main workspace
 * module but also can be used from extensions. Originally 99.9%% of the code
 * was written by Kasper and only transfered here by Dmitry.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class wslib_gui {

	// Static:
	var $pageTreeIndent = 8;
	var $pageTreeIndent_titleLgd = 30;

	// Options
	var	$diff = false;
	var	$expandSubElements = false;
	var	$alwaysDisplayHeader = false;

	// Internal
	var	$showWorkspaceCol = 0;
	var	$doc;
	var	$formatWorkspace_cache = array();
	var	$targets = array();						// Accumulation of online targets.
	var	$be_user_Array = array();
	var	$pageModule = '';
	var $formatCount_cache = array();
	var $stageIndex = array();
	var	$addHlSubelementsScript = false;

	/*********************************
	 *
	 * Public functions
	 *
	 *********************************/

	/**
	 * Creates HTML to display workspace overview. Can be used to display overview for all possible records or only for single page.
	 *
	 * The following code is <strong>required</strong> in BE module when this function is used:
	 * <code>
	 * 	$this->doc->getContextMenuCode();
	 * </code>
	 * or click-menu will not be generated properly!
	 *
	 * @param	object		$doc	Document (to use for formatting)
	 * @param	int		$wsid	Workspace ID, If <code>null</code>, the value is obtained from current BE user
	 * @param	int		$filter	If 0, than no filtering, if 10 than select for publishing, otherwise stage value
	 * @param	int		$pageId	If greater than zero, than it is UID of page in LIVE workspaces to select records for
	 * @return	string		Generated HTML
	 */
	function getWorkspaceOverview(&$doc, $wsid = null, $filter = 0, $pageId = -1) {
		global	$LANG;

		// Setup
		$this->workspaceId = (!is_null($wsid) ? $wsid : $GLOBALS['BE_USER']->workspace);
		$this->showWorkspaceCol = $GLOBALS['BE_USER']->workspace == 0 && $this->workspaceId <= -98;
		$this->doc = $doc;
		$this->initVars();

		// Initialize workspace object and request all pending versions:
		$wslibObj = t3lib_div::makeInstance('wslib');

		// Selecting ALL versions belonging to the workspace:
		$versions = $wslibObj->selectVersionsInWorkspace($this->workspaceId, $filter, -99, $pageId);

		// Traverse versions and build page-display array:
		$pArray = array();
		$wmArray = array();	// is page in web mount?
		$rlArray = array();	// root line of page
		$pagePermsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		foreach($versions as $table => $records)	{
			if (is_array($records)) {
				foreach($records as $rec)	{
					$pageIdField = $table==='pages' ? 't3ver_oid' : 'realpid';
					$recPageId = $rec[$pageIdField];
					if (!isset($wmArray[$recPageId]))	{
						$wmArray[$recPageId] = $GLOBALS['BE_USER']->isInWebMount($recPageId,$pagePermsClause);
					}
					if ($wmArray[$recPageId])	{
						if (!isset($rlArray[$recPageId]))	{
							$rlArray[$recPageId] = t3lib_BEfunc::BEgetRootLine($recPageId, 'AND 1=1');
						}
						$this->displayWorkspaceOverview_setInPageArray(
							$pArray,
							$rlArray[$recPageId],
							$table,
							$rec
						);
					}
				}
			}
		}

			// Page-browser:
		$resultsPerPage = 50;
		$pointer = t3lib_div::_GP('browsePointer');
		$browseStat = $this->cropWorkspaceOverview_list($pArray,$pointer,$resultsPerPage);
		$browse = '';
		$browse .= '<h3>Showing ' . $browseStat['begin'] . ' to ' . ($browseStat['end'] ? $browseStat['end'] . ' out of ' . $browseStat['allItems'] : $browseStat['allItems']) . ' versions:</h3>';
		if (!($browseStat['begin']==1 && !$browseStat['end']))	{
			for($a=0;$a<ceil($browseStat['allItems']/$resultsPerPage);$a++)	{
				$browse.=($a==(int)$pointer?'<strong>':'').'<a href="'.htmlspecialchars('index.php?browsePointer='.rawurlencode($a)).'">['.($a+1).']</a>'.($a==(int)$pointer?'</strong>':'').' ';
			}
		}

		$workspaceOverviewList = $this->displayWorkspaceOverview_list($pArray);
		if ($workspaceOverviewList || $this->alwaysDisplayHeader) {
			// Make header of overview:
			$tableRows = array();
			$tableHeader = '
				<tr class="t3-row-header">
					<td nowrap="nowrap" width="100">' . $LANG->getLL('label_pagetree') . '</td>
					<td nowrap="nowrap" colspan="2">' . $LANG->getLL('label_live_version') . '</td>
					<td nowrap="nowrap" colspan="2">' . $LANG->getLL('label_draft_versions') . '</td>
					<td nowrap="nowrap">' . $LANG->getLL('label_stage') . '</td>
					<td nowrap="nowrap">' . $LANG->getLL('label_publish') . '</td>
					<td><select name="_with_selected_do" onchange="if (confirm(\'' . $LANG->getLL('submit_apply_action_on_selected_elements') . '\')) {document.forms[0].submit();}">
						<option value="_">' . $LANG->getLL('label_doaction_default') . '</option>';

			if ($this->publishAccess && !($GLOBALS['BE_USER']->workspaceRec['publish_access'] & 1))	{
				$tableHeader .= '<option value="publish">' . $LANG->getLL('label_doaction_publish') . '</option>';
				if ($GLOBALS['BE_USER']->workspaceSwapAccess())	{
					$tableHeader .= '<option value="swap">' . $LANG->getLL('label_doaction_swap') . '</option>';
				}
			}
			if ($GLOBALS['BE_USER']->workspace !== 0) {
				$tableHeader .= '<option value="release">' . $LANG->getLL('label_doaction_release') . '</option>';
			}
			$tableHeader .= $GLOBALS['BE_USER']->workspaceCheckStageForCurrent('-1') ? '<option value="stage_-1">' . $LANG->getLL('label_doaction_stage_reject') . '</option>' : '';
			$tableHeader .= $GLOBALS['BE_USER']->workspaceCheckStageForCurrent('0') ? '<option value="stage_0">' . $LANG->getLL('label_doaction_stage_editing') . '</option>' : '';
			$tableHeader .= $GLOBALS['BE_USER']->workspaceCheckStageForCurrent('1') ? '<option value="stage_1">' . $LANG->getLL('label_doaction_stage_review') . '</option>' : '';
			$tableHeader .= $GLOBALS['BE_USER']->workspaceCheckStageForCurrent('10') ? '<option value="stage_10">' . $LANG->getLL('label_doaction_stage_publish') . '</option>' : '';

			$tableHeader .= '<option value="flush">' . $LANG->getLL('label_doaction_flush') . '</option>
					</select></td>
					<td>' . $LANG->getLL('label_lifecycle') . '</td>
					'.($this->showWorkspaceCol ? '<td>' . $LANG->getLL('label_workspace') . '</td>' : '').'
				</tr>';
			$tableRows[] = $tableHeader;

			// Add lines from overview:
			$tableRows = array_merge($tableRows, $workspaceOverviewList);

			$table = '<table border="0" cellpadding="0" cellspacing="0" id="t3-user-ws-wsoverview-table" class="typo3-dblist">' . implode('', $tableRows) . '</table>';

			// script
			if ($this->addHlSubelementsScript && !strstr($this->doc->JScode, 'function hlSubelements(')) {
				$table = $this->doc->wrapScriptTags('
					function hlSubelements(origId, verId, over, diffLayer)	{
						if (over)	{
							document.getElementById(\'orig_\'+origId).attributes.getNamedItem("class").nodeValue = \'typo3-ver-hl\';
							document.getElementById(\'ver_\'+verId).attributes.getNamedItem("class").nodeValue = \'typo3-ver-hl\';
							if (diffLayer)	{
								document.getElementById(\'diff_\'+verId).style.visibility = \'visible\';
							}
						} else {
							document.getElementById(\'orig_\'+origId).attributes.getNamedItem("class").nodeValue = \'typo3-ver\';
							document.getElementById(\'ver_\'+verId).attributes.getNamedItem("class").nodeValue = \'typo3-ver\';
							if (diffLayer)	{
								document.getElementById(\'diff_\'+verId).style.visibility = \'hidden\';
							}
						}
					}
				') . $table;
			}

			return $browse . $table . $this->markupNewOriginals();
		}
		return '';
	}

	/*********************************
	 *
	 * Private functions (do not use outside of this class!)
	 *
	 *********************************/

	/**
	 * Initializes several class variables
	 *
	 * @return	void
	 */
	function initVars() {
		// Init users
		$be_group_Array = t3lib_BEfunc::getListGroupNames('title,uid');
		$groupArray = array_keys($be_group_Array);
		// Need 'admin' field for t3lib_iconWorks::getIconImage()
		$this->be_user_Array = t3lib_BEfunc::getUserNames('username,usergroup,usergroup_cached_list,uid,admin,workspace_perms');
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			$this->be_user_Array = t3lib_BEfunc::blindUserNames($this->be_user_Array,$groupArray,1);
		}

		// If another page module was specified, replace the default Page module with the new one
		$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
		$this->pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

		// Setting publish access permission for workspace:
		$this->publishAccess = $GLOBALS['BE_USER']->workspacePublishAccess($GLOBALS['BE_USER']->workspace);	// FIXME Should be $this->workspaceId here?
	}

	/**
	 * Building up of the $pArray variable which is a hierarchical storage of table-rows arranged according to the level in the rootline the element was found
	 * (Internal)
	 * Made for recursive calling
	 *
	 * @param	array		Array that is built up with the page tree structure
	 * @param	array		Root line for element (table / row); The element is stored in pArray according to this root line.
	 * @param	string		Table name
	 * @param	array		Table row
	 * @return	void		$pArray is passed by reference and modified internally
	 */
	function displayWorkspaceOverview_setInPageArray(&$pArray, $rlArr, $table, $row)	{
		// Initialize:
		ksort($rlArr);
		reset($rlArr);
		if (!$rlArr[0]['uid']) {
			array_shift($rlArr);
		}

		// Get and remove first element in root line:
		$cEl = current($rlArr);
		$pUid = $cEl['t3ver_oid'] ? $cEl['t3ver_oid'] : $cEl['uid'];		// Done to pile up "false versions" in the right branch...

		$pArray[$pUid] = $cEl['title'];
		array_shift($rlArr);

		// If there are elements left in the root line, call this function recursively (to build $pArray in depth)
		if (count($rlArr))	{
			if (!isset($pArray[$pUid.'.'])) {
				$pArray[$pUid.'.'] = array();
			}
			$this->displayWorkspaceOverview_setInPageArray($pArray[$pUid.'.'], $rlArr, $table, $row);
		} else {	// If this was the last element, set the value:
			$pArray[$pUid.'_'][$table][$row['t3ver_oid']][] = $row;
		}
	}

	/**
	 * JavaScript code to mark up new records that are online (in sub element lists)
	 *
	 * @return	string		HTML javascript section
	 */
	function markupNewOriginals()	{

		if (count($this->targets))	{
			$scriptCode = '';
			foreach($this->targets as $key => $rec)	{
				$scriptCode.='
					document.getElementById(\''.$key.'\').attributes.getNamedItem("class").nodeValue = \'typo3-ver-new\';
				';
			}

			return $this->doc->wrapScriptTags($scriptCode);
		}
	}


	/**
	 * Rendering the content for the publish / review overview:
	 * (Made for internal recursive calling)
	 *
	 * @param	array		Hierarchical storage of the elements to display (see displayWorkspaceOverview() / displayWorkspaceOverview_setInPageArray())
	 * @param	array		Existing array of table rows to add to
	 * @param	array		Depth counter
	 * @param	boolean		If set, a warning is shown if versions are found (internal flag)
	 * @return	array		Table rows, see displayWorkspaceOverview()
	 */
	function displayWorkspaceOverview_list($pArray, $tableRows=array(), $c=0, $warnAboutVersions=FALSE)	{
		global $LANG;

		// Initialize:
		$fullColSpan = $this->showWorkspaceCol ? 10 : 9;

		// Traverse $pArray
		if (is_array($pArray))	{
			foreach($pArray as $k => $v)	{
				if (t3lib_div::testInt($k))	{

					// If there are elements on this level, output a divider row which just creates a little visual space.
					if (is_array($pArray[$k.'_']))	{
						$tableRows[] = '
							<tr>
								<td colspan="'.$fullColSpan.'"><img src="clear.gif" width="1" height="3" alt="" /></td>
							</tr>';
					}

					// Printing a level from the page tree with no additional content:
					// If there are NO elements on this level OR if there are NO pages on a level with elements, then show page tree icon and title (otherwise it is shown with the display of the elements)
					if (!is_array($pArray[$k.'_']) || !is_array($pArray[$k.'_']['pages']))	{
						$tableRows[] = '
							<tr class="bgColor4-20">
								<td nowrap="nowrap" colspan="'.$fullColSpan.'">'.
								$this->displayWorkspaceOverview_pageTreeIconTitle($k,$pArray[$k],$c).
								'</td>
							</tr>';
					}

					// If there ARE elements on this level, print them:
					$warnAboutVersions_next = $warnAboutVersions;
					$warnAboutVersions_nonPages = FALSE;
					$warnAboutVersions_page = FALSE;
					if (is_array($pArray[$k.'_']))	{
						foreach($pArray[$k.'_'] as $table => $oidArray)	{
							foreach($oidArray as $oid => $recs)	{

								// Get CURRENT online record and icon based on "t3ver_oid":
								$rec_on = t3lib_BEfunc::getRecord($table,$oid);
								$icon = t3lib_iconWorks::getIconImage($table, $rec_on, $this->doc->backPath,' align="top" title="'.t3lib_BEfunc::getRecordIconAltText($rec_on,$table).'"');
								if ($GLOBALS['BE_USER']->workspace===0) {	// Only edit online records if in ONLINE workspace:
									$icon = $this->doc->wrapClickMenuOnIcon($icon, $table, $rec_on['uid'], 2, '', '+edit,view,info,delete');
								}

								// MAIN CELL / Online version display:
								// Create the main cells which will span over the number of versions there is. If the table is "pages" then it will show the page tree icon and title (which was not shown by the code above)
								$verLinkUrl = t3lib_extMgm::isLoaded('version') && $GLOBALS['TCA'][$table]['ctrl']['versioningWS'];
								$origElement = $icon.
								($verLinkUrl ? '<a href="'.htmlspecialchars($this->doc->backPath.t3lib_extMgm::extRelPath('version').'cm1/index.php?table='.$table.'&uid='.$rec_on['uid']).'">' : '').
												t3lib_BEfunc::getRecordTitle($table,$rec_on,TRUE).
												($verLinkUrl ? '</a>' : '');
								$mainCell_rowSpan = count($recs)>1 ? ' rowspan="'.count($recs).'"' : '';
								$mainCell = $table==='pages' ? '
											<td class="bgColor4-20" nowrap="nowrap"'.$mainCell_rowSpan.'>'.
											$this->displayWorkspaceOverview_pageTreeIconTitle($k,$pArray[$k],$c).
											'</td>' : '
											<td class="bgColor"'.$mainCell_rowSpan.'></td>';
								$mainCell.= '
											<td align="center"'.$mainCell_rowSpan.'>'.$this->formatVerId($rec_on['t3ver_id']).'</td>
											<td nowrap="nowrap"'.$mainCell_rowSpan.'>'.
								$origElement.
								'###SUB_ELEMENTS###'.	// For substitution with sub-elements, if any.
								'</td>';

								// Offline versions display:
								// Traverse the versions of the element
								foreach($recs as $rec)	{

									// Get the offline version record:
									$rec_off = t3lib_BEfunc::getRecord($table,$rec['uid']);

									// Prepare swap-mode values:
									if ($table==='pages' && $rec_off['t3ver_swapmode']!=-1)	{
										if ($rec_off['t3ver_swapmode']>0)	{
											$vType = 'branch';	// Do not translate!
										} else {
											$vType = 'page';	// Do not translate!
										}
									} else {
										$vType = 'element';	// Do not translate!
									}

									// Get icon:
									$icon = t3lib_iconWorks::getIconImage($table, $rec_off, $this->doc->backPath, ' align="top" title="'.t3lib_BEfunc::getRecordIconAltText($rec_off,$table).'"');
									$tempUid = ($table != 'pages' || $vType==='branch' || $GLOBALS['BE_USER']->workspace == 0 ? $rec_off['uid'] : $rec_on['uid']);
									$icon = $this->doc->wrapClickMenuOnIcon($icon, $table, $tempUid, 2, '', '+edit,' . ($table == 'pages' ? 'view,info,' : '') . 'delete');

									// Prepare diff-code:
									if ($this->diff)	{
										$diffCode = '';
										list($diffHTML,$diffPct) = $this->createDiffView($table, $rec_off, $rec_on);
										if ($rec_on['t3ver_state']==1)	{	// New record:
											$diffCode.= $this->doc->icons(1) . $LANG->getLL('label_newrecord') . '<br />';
											$diffCode.= $diffHTML;
										} elseif ($rec_off['t3ver_state']==2)	{
											$diffCode.= $this->doc->icons(2) . $LANG->getLL('label_deletedrecord') . '<br/>';
										} elseif ($rec_on['t3ver_state']==3)	{
											$diffCode.= $this->doc->icons(1) . $LANG->getLL('label_moveto_placeholder') . '<br/>';
										} elseif ($rec_off['t3ver_state']==4)	{
											$diffCode.= $this->doc->icons(1) . $LANG->getLL('label_moveto_pointer') . '<br/>';
										} else {
											$diffCode .= ($diffPct < 0 ? $LANG->getLL('label_notapplicable') :
												($diffPct ? sprintf($LANG->getLL('label_percentChange'), $diffPct) : ''));
											$diffCode.= $diffHTML;
										}

									} else $diffCode = '';

									switch($vType) {
										case 'element':
											$swapLabel = ' ['.$LANG->getLL('label_element').']';
											$swapClass = 'ver-element';	// Do not translate!
											$warnAboutVersions_nonPages = $warnAboutVersions_page;	// Setting this if sub elements are found with a page+content (must be rendered prior to this of course!)
											break;
										case 'page':
											$swapLabel = ' ['.$LANG->getLL('label_page').']';
											$swapClass = 'ver-page';	// Do not translate!
											$warnAboutVersions_page = !$this->showWorkspaceCol;		// This value is true only if multiple workspaces are shown and we need the opposite here.
											break;
										case 'branch':
											$swapLabel = ' ['.$LANG->getLL('label_branch').']';
											$swapClass = 'ver-branch';	// Do not translate!
											$warnAboutVersions_next = !$this->showWorkspaceCol;		// This value is true only if multiple workspaces are shown and we need the opposite here.
											break;
									}

									// Modify main cell based on first version shown:
									$subElements = array();
									if ($table==='pages' && $rec_off['t3ver_swapmode']!=-1 && $mainCell)	{	// For "Page" and "Branch" swap modes where $mainCell is still carrying content (only first version)
										$subElements['on'] = $this->subElements($rec_on['uid'], $rec_off['t3ver_swapmode']);
										$subElements['off'] = $this->subElements($rec_off['uid'],$rec_off['t3ver_swapmode'],$rec_on['uid']);
									}
									$mainCell = str_replace('###SUB_ELEMENTS###', $subElements['on'], $mainCell);

									// Create version element:
									$versionsInOtherWS = $this->versionsInOtherWS($table, $rec_on['uid']);
									$versionsInOtherWSWarning = $versionsInOtherWS && $GLOBALS['BE_USER']->workspace !== 0 ? '<br />' . $this->doc->icons(2) . $LANG->getLL('label_otherversions') . ' ' . $versionsInOtherWS : '';
									$multipleWarning = (!$mainCell && $GLOBALS['BE_USER']->workspace !==0 ? '<br />' . $this->doc->icons(3) . '<strong>' . $LANG->getLL('label_multipleversions') . '</strong>' : '');
									$verWarning = $warnAboutVersions || ($warnAboutVersions_nonPages && $GLOBALS['TCA'][$table]['ctrl']['versioning_followPages']) ? '<br />' . $this->doc->icons(3) . '<strong>' . $LANG->getLL('label_nestedversions') . '</strong>' : '';
									$verElement = $icon.
										'<a href="'.htmlspecialchars($this->doc->backPath.t3lib_extMgm::extRelPath('version').'cm1/index.php?id='.($table==='pages'?$rec_on['uid']:$rec_on['pid']).'&details='.rawurlencode($table.':'.$rec_off['uid']).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
										t3lib_BEfunc::getRecordTitle($table,$rec_off,TRUE).
										'</a>'.
										$versionsInOtherWSWarning.
										$multipleWarning.
										$verWarning;
									if ($diffCode)	{
										$verElement = '
										<table border="0" cellpadding="0" cellspacing="0" class="ver-verElement">
											<tr>
												<td nowrap="nowrap" width="180">'.$verElement.'&nbsp;&nbsp;</td>
												<td class="c-diffCell">'.$diffCode.'</td>
											</tr>
										</table>';
									}

									// Create version cell:
									$verCell = '
											<td align="center">'.$this->formatVerId($rec_off['t3ver_id']).'</td>
											<td nowrap="nowrap">'.
									$verElement.
									$subElements['off'].
									'</td>';

									// Compile table row:
									$tableRows[] = '
										<tr class="bgColor4">
											'.$mainCell.$verCell.'
											<td nowrap="nowrap">'.$this->showStageChangeLog($table,$rec_off['uid'],$this->displayWorkspaceOverview_stageCmd($table,$rec_off)).'</td>
											<td nowrap="nowrap" class="'.$swapClass.'">'.
									$this->displayWorkspaceOverview_commandLinks($table,$rec_on,$rec_off,$vType).
									htmlspecialchars($swapLabel).
									'</td>
											<td nowrap="nowrap" align="center"><input type="checkbox" name="items['.$table.':'.$rec_off['uid'].']" id="items['.$table.':'.$rec_off['uid'].']" value="1"/></td>
											<td nowrap="nowrap">'.htmlspecialchars($this->formatCount($rec_off['t3ver_count'])).'</td>'.		// Lifecycle
									($this->showWorkspaceCol ? '
											<td nowrap="nowrap">'.htmlspecialchars($this->formatWorkspace($rec_off['t3ver_wsid'])).'</td>' : '').'
										</tr>';

									// Reset the main cell:
									$mainCell = '';
								}
							}
						}
					}
					// Call recursively for sub-rows:
					$tableRows = $this->displayWorkspaceOverview_list($pArray[$k.'.'], $tableRows, $c+1, $warnAboutVersions_next);
				}
			}
		}
		return $tableRows;
	}

	/**
	 * Filtering out items in pArray according to pointer and result-per-page setting
	 *
	 * @param	array		Hierarchical storage of the elements to display (see displayWorkspaceOverview() / displayWorkspaceOverview_setInPageArray())
	 * @return	array		Returns statistics about the pointer state.
	 */
	function cropWorkspaceOverview_list(&$pArray,$pointer=0,$resPerPage=50,$stat=array())	{

			// Traverse $pArray
		if (is_array($pArray))	{
			foreach($pArray as $k => $v)	{
				if (t3lib_div::testInt($k))	{

					if (is_array($pArray[$k.'_']))	{
						foreach($pArray[$k.'_'] as $table => $oidArray)	{
							foreach($oidArray as $oid => $recs)	{

									// Check, if the item count has reached the point where we want to set the in-point.
								$beginWasSet = FALSE;
								if (!isset($stat['begin']) && (int)$stat['allItems'] >= $pointer*$resPerPage)	{
									$stat['begin']=(int)$stat['allItems']+1;
									$beginWasSet = TRUE;
								}

									// If in-point is not set, unset the previous items.
								if (!isset($stat['begin']))	{
									unset($pArray[$k.'_'][$table][$oid]);
								}

									// Increase counter:
								$stat['allItems']+=count($recs);

									// Check if end-point is reached:
								if (!$beginWasSet && !isset($stat['end']) && $stat['allItems'] > ($pointer+1)*$resPerPage)	{
									$stat['end']=$stat['allItems']-1;
								}

									// If end-point is reached, unset following items.
								if (isset($stat['end']))	{
									unset($pArray[$k.'_'][$table][$oid]);
								}
							}

								// Clean-up if no more items:
							if (!count($pArray[$k.'_'][$table]))	{
								unset($pArray[$k.'_'][$table]);
							}
						}

							// Clean-up if no more items:
						if (!count($pArray[$k.'_']))	{
							unset($pArray[$k.'_']);
						}
					}

						// Call recursively for sub-rows:
					if (is_array($pArray[$k.'.']))	{
						$stat = $this->cropWorkspaceOverview_list($pArray[$k.'.'],$pointer,$resPerPage,$stat);
					}
				}
			}
		}
		return $stat;
	}

	/**
	 * Create indentation, icon and title for the page tree identification for the list.
	 *
	 * @param	integer		Page UID (record will be looked up again)
	 * @param	string		Page title
	 * @param	integer		Depth counter from displayWorkspaceOverview_list() used to indent the icon and title
	 * @return	string		HTML content
	 */
	function displayWorkspaceOverview_pageTreeIconTitle($pageUid, $title, $indentCount)	{
		$pRec = t3lib_BEfunc::getRecord('pages',$pageUid);
		return '<img src="clear.gif" width="1" height="1" hspace="'.($indentCount * $this->pageTreeIndent).'" align="top" alt="" />'.	// Indenting page tree
			t3lib_iconWorks::getSpriteIconForRecord('pages', $pRec, array('title'=> t3lib_BEfunc::getRecordIconAltText($pRec,'pages'))) .
			htmlspecialchars(t3lib_div::fixed_lgd_cs($title,$this->pageTreeIndent_titleLgd)).
			'&nbsp;&nbsp;';
	}

	/**
	 * Formatting the version number for HTML output
	 *
	 * @param	integer		Version number
	 * @return	string		Version number for output
	 */
	function formatVerId($verId)	{
		return '1.'.$verId;
	}


	/**
	 * Formatting workspace ID into a visual label
	 *
	 * @param	integer		Workspace ID
	 * @return	string		Workspace title
	 */
	function formatWorkspace($wsid)	{

		// Render, if not cached:
		if (!isset($this->formatWorkspace_cache[$wsid]))	{
			switch($wsid)	{
				case -1:
					$this->formatWorkspace_cache[$wsid] = '[Draft]';
					break;
				case 0:
					$this->formatWorkspace_cache[$wsid] = '';	// Does not output anything for ONLINE because it might confuse people to think that the elemnet IS online which is not the case - only that it exists as an offline version in the online workspace...
					break;
				default:
					$titleRec = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('title', 'sys_workspace', 'uid=' . intval($wsid) . t3lib_BEfunc::deleteClause('sys_workspace'));
					$this->formatWorkspace_cache[$wsid] = '['.$wsid.'] '.$titleRec['title'];
					break;
			}
		}

		return $this->formatWorkspace_cache[$wsid];
	}


	/**
	 * Create visual difference view of two records. Using t3lib_diff library
	 *
	 * @param	string		Table name
	 * @param	array		New version record (green)
	 * @param	array		Old version record (red)
	 * @return	array		Array with two keys (0/1) with HTML content / percentage integer (if -1, then it means N/A) indicating amount of change
	 */
	function createDiffView($table, $diff_1_record, $diff_2_record)	{
		global $LANG;

		// Initialize:
		$pctChange = 'N/A';

		// Check that records are arrays:
		if (is_array($diff_1_record) && is_array($diff_2_record))	{

			// Load full table description and initialize diff-object:
			t3lib_div::loadTCA($table);
			$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');

			// Add header row:
			$tRows = array();
			$tRows[] = '
				<tr class="bgColor5 tableheader">
					<td>' . $GLOBALS['LANG']->getLL('diffview_label_field_name') . '</td>
					<td width="98%" nowrap="nowrap">' . $GLOBALS['LANG']->getLL('diffview_label_colored_diff_view') . '</td>
				</tr>
			';

			// Initialize variables to pick up string lengths in:
			$allStrLen = 0;
			$diffStrLen = 0;

			// Traversing the first record and process all fields which are editable:
			foreach($diff_1_record as $fN => $fV)	{
				if ($GLOBALS['TCA'][$table]['columns'][$fN] && $GLOBALS['TCA'][$table]['columns'][$fN]['config']['type']!='passthrough' && !t3lib_div::inList('t3ver_label',$fN))	{

					// Check if it is files:
					$isFiles = FALSE;
					if (strcmp(trim($diff_1_record[$fN]),trim($diff_2_record[$fN])) &&
					$GLOBALS['TCA'][$table]['columns'][$fN]['config']['type']=='group' &&
					$GLOBALS['TCA'][$table]['columns'][$fN]['config']['internal_type']=='file')	{

						// Initialize:
						$uploadFolder = $GLOBALS['TCA'][$table]['columns'][$fN]['config']['uploadfolder'];
						$files1 = array_flip(t3lib_div::trimExplode(',', $diff_1_record[$fN],1));
						$files2 = array_flip(t3lib_div::trimExplode(',', $diff_2_record[$fN],1));

						// Traverse filenames and read their md5 sum:
						foreach($files1 as $filename => $tmp)	{
							$files1[$filename] = @is_file(PATH_site.$uploadFolder.'/'.$filename) ? md5(t3lib_div::getUrl(PATH_site.$uploadFolder.'/'.$filename)) : $filename;
						}
						foreach($files2 as $filename => $tmp)	{
							$files2[$filename] = @is_file(PATH_site.$uploadFolder.'/'.$filename) ? md5(t3lib_div::getUrl(PATH_site.$uploadFolder.'/'.$filename)) : $filename;
						}

						// Implode MD5 sums and set flag:
						$diff_1_record[$fN] = implode(' ',$files1);
						$diff_2_record[$fN] = implode(' ',$files2);
						$isFiles = TRUE;
					}

					// If there is a change of value:
					if (strcmp(trim($diff_1_record[$fN]),trim($diff_2_record[$fN])))	{


						// Get the best visual presentation of the value and present that:
						$val1 = t3lib_BEfunc::getProcessedValue($table,$fN,$diff_2_record[$fN],0,1);
						$val2 = t3lib_BEfunc::getProcessedValue($table,$fN,$diff_1_record[$fN],0,1);

						// Make diff result and record string lenghts:
						$diffres = $t3lib_diff_Obj->makeDiffDisplay($val1,$val2,$isFiles?'div':'span');
						$diffStrLen+= $t3lib_diff_Obj->differenceLgd;
						$allStrLen+= strlen($val1.$val2);

						// If the compared values were files, substituted MD5 hashes:
						if ($isFiles)	{
							$allFiles = array_merge($files1,$files2);
							foreach($allFiles as $filename => $token)	{
								if (strlen($token)==32 && strstr($diffres,$token))	{
									$filename =
									t3lib_BEfunc::thumbCode(array($fN=>$filename),$table,$fN,$this->doc->backPath).
									$filename;
									$diffres = str_replace($token,$filename,$diffres);
								}
							}
						}

						// Add table row with result:
						$tRows[] = '
							<tr class="bgColor4">
								<td>'.htmlspecialchars($GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($table,$fN))).'</td>
								<td width="98%">'.$diffres.'</td>
							</tr>
						';
					} else {
						// Add string lengths even if value matched - in this was the change percentage is not high if only a single field is changed:
						$allStrLen+=strlen($diff_1_record[$fN].$diff_2_record[$fN]);
					}
				}
			}

			// Calculate final change percentage:
			$pctChange = $allStrLen ? ceil($diffStrLen*100/$allStrLen) : -1;

			// Create visual representation of result:
			if (count($tRows)>1)	{
				$content.= '<table border="0" cellpadding="1" cellspacing="1" class="diffTable">'.implode('',$tRows).'</table>';
			} else {
				$content.= '<span class="nobr">'.$this->doc->icons(1).$LANG->getLL('diffview_complete_match').'</span>';
			}
		} else $content.= $this->doc->icons(3).$LANG->getLL('diffview_cannot_find_records');

		// Return value:
		return array($content,$pctChange);
	}

	/**
	 * Looking for versions of a record in other workspaces than the current
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @return	string		List of other workspace IDs
	 */
	function versionsInOtherWS($table, $uid)	{
		// Check for duplicates:
		// Select all versions of record NOT in this workspace:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				't3ver_wsid',
				$table,
				'pid=-1
				AND t3ver_oid='.intval($uid).'
				AND t3ver_wsid!='.intval($GLOBALS['BE_USER']->workspace).// TODO should be $this->workspaceId here???
				' AND (t3ver_wsid=-1 OR t3ver_wsid>0)'.
				t3lib_BEfunc::deleteClause($table),
				'',
				't3ver_wsid',
				'',
				't3ver_wsid'
		);
		if (count($rows)) {
			return implode(',',array_keys($rows));
		}
	}

	/**
	 * Looks up stage changes for version and displays a formatted view on mouseover.
	 *
	 * @param	string		Table name
	 * @param	integer		Record ID
	 * @param	string		HTML string to wrap the mouseover around (should be stage change links)
	 * @return	string		HTML code.
	 */
	function showStageChangeLog($table,$id,$stageCommands)	{
		global	$LANG;

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'log_data,tstamp,userid',
				'sys_log',
				'action=6 and details_nr=30
				AND tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_log').'
				AND recuid='.intval($id)
		);

		$entry = array();
		foreach($rows as $dat)	{
			$data = unserialize($dat['log_data']);
			$username = $this->be_user_Array[$dat['userid']] ? $this->be_user_Array[$dat['userid']]['username'] : '['.$dat['userid'].']';

			switch($data['stage'])	{
				case 1:
					$text = $LANG->getLL('stage_sent_to_review');
					break;
				case 10:
					$text = $LANG->getLL('stage_approved_for_publish');
					break;
				case -1:
					$text = $LANG->getLL('stage_rejected');
					break;
				case 0:
					$text = $LANG->getLL('stage_reset_to_editing');
					break;
				default:
					$text = $LANG->getLL('stage_undefined');
					break;
			}
			$text = t3lib_BEfunc::datetime($dat['tstamp']).': ' . sprintf($text, htmlspecialchars($username));
			$text.= ($data['comment'] ? '<br />' . $LANG->getLL('stage_label_user_comment') . ' <em>' . htmlspecialchars($data['comment']) . '</em>' : '');

			$entry[] = $text;
		}

		return count($entry) ? '<span onmouseover="document.getElementById(\'log_' . $table . $id . '\').style.visibility = \'visible\';" onmouseout="document.getElementById(\'log_' . $table . $id . '\').style.visibility = \'hidden\';">' . $stageCommands . ' (' . count($entry) . ')</span>' .
				'<div class="t3-version-infolayer logLayer" id="log_' . $table . $id . '">' . implode('<hr/>', $entry) . '</div>' : $stageCommands;
	}

	/**
	 * Links to publishing etc of a version
	 *
	 * @param	string		Table name
	 * @param	array		Online record
	 * @param	array		Offline record (version)
	 * @param	string		Swap type, "branch", "page" or "element"
	 * @return	string		HTML content, mainly link tags and images.
	 */
	function displayWorkspaceOverview_commandLinks($table,&$rec_on,&$rec_off,$vType)	{
		global	$LANG;

		if ($this->publishAccess && (!($GLOBALS['BE_USER']->workspaceRec['publish_access']&1) || (int)$rec_off['t3ver_stage']===-10))	{
			$actionLinks =
				'<a href="'.htmlspecialchars($this->doc->issueCommand(
				'&cmd['.$table.']['.$rec_on['uid'].'][version][action]=swap'.
				'&cmd['.$table.']['.$rec_on['uid'].'][version][swapWith]='.$rec_off['uid']
				)).' " title="' . $LANG->getLL('img_title_publish') . '">'.
				  t3lib_iconWorks::getSpriteIcon('actions-version-workspace-sendtostage') .
				'</a>';
			if ($GLOBALS['BE_USER']->workspaceSwapAccess())	{
				$actionLinks.=
					'<a href="'.htmlspecialchars($this->doc->issueCommand(
					'&cmd['.$table.']['.$rec_on['uid'].'][version][action]=swap'.
					'&cmd['.$table.']['.$rec_on['uid'].'][version][swapWith]='.$rec_off['uid'].
					'&cmd['.$table.']['.$rec_on['uid'].'][version][swapIntoWS]=1'
								)).'" title="' . $LANG->getLL('img_title_swap') . '">'.
					  t3lib_iconWorks::getSpriteIcon('actions-version-swap-workspace') .
					'</a>';
			}
		}

		if (!$GLOBALS['BE_USER']->workspaceCannotEditOfflineVersion($table,$rec_off)) {
			if ($GLOBALS['BE_USER']->workspace!==0) {
				// Release
				$confirm = $LANG->JScharCode($LANG->getLL('remove_from_ws_confirmation'));
				$actionLinks.=
				'<a href="'.htmlspecialchars($this->doc->issueCommand('&cmd['.$table.']['.$rec_off['uid'].'][version][action]=clearWSID')).'" onclick="return confirm(' . $confirm . ');" title="' . $LANG->getLL('img_title_remove_from_ws') . '">'.
				  t3lib_iconWorks::getSpriteIcon('actions-version-document-remove') .
				'</a>';
			}

			// Edit
			if ($table==='pages' && $vType!=='element')	{
				$tempUid = ($vType==='branch' || $GLOBALS['BE_USER']->workspace===0 ? $rec_off['uid'] : $rec_on['uid']);
				$actionLinks.=
					'<a href="#" onclick="top.loadEditId('.$tempUid.');top.goToModule(\''.$this->pageModule.'\'); return false;" title="' . $LANG->getLL('img_title_edit_page') . '">'.
					  t3lib_iconWorks::getSpriteIcon('actions-page-open') .
					'</a>';
			} else {
				$params = '&edit['.$table.']['.$rec_off['uid'].']=edit';
				$actionLinks.=
					'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->doc->backPath)).'" title="' . $LANG->getLL('img_title_edit_element') . '">'.
					  t3lib_iconWorks::getSpriteIcon('actions-document-open') .
					'</a>';
			}
		}

		// History/Log
		$actionLinks.=
			'<a href="'.htmlspecialchars($this->doc->backPath.'show_rechis.php?element='.rawurlencode($table.':'.$rec_off['uid']).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'" title="' . $LANG->getLL('img_title_show_log') . '">' .
			  t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
			'</a>';

		// View
		if ($table==='pages')	{
			$tempUid = ($vType==='branch' || $GLOBALS['BE_USER']->workspace===0 ? $rec_off['uid'] : $rec_on['uid']);
			$actionLinks.=
				'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($tempUid,$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($tempUid))).'">'.
				  t3lib_iconWorks::getSpriteIcon('actions-document-view') .
				'</a>';
		}

		return $actionLinks;
	}

	/**
	 * Format publishing count for version (lifecycle state)
	 *
	 * @param	integer		t3ver_count value (number of times it has been online)
	 * @return	string		String translation of count.
	 */
	function formatCount($count)	{
		global	$LANG;

		// Render, if not cached:
		if (!isset($this->formatCount_cache[$count]))	{
			switch($count)	{
				case 0:
					$this->formatCount_cache[$count] = $LANG->getLL('workspace_list_publishing_count_draft');
					break;
				case 1:
					$this->formatCount_cache[$count] = $LANG->getLL('workspace_list_publishing_count_archive');
					break;
				default:
					$this->formatCount_cache[$count] = sprintf($LANG->getLL('workspace_list_publishing_count'), $count);
					break;
			}
		}

		return $this->formatCount_cache[$count];
	}


	/**
	 * Creates display of sub elements of a page when the swap mode is either "Page" or "Branch" (0 / ALL)
	 *
	 * @param	integer		Page uid (for either online or offline version, but it MUST have swapmode/treeLevel set to >0 (not -1 indicating element versioning)
	 * @param	integer		The treeLevel value, >0 indicates "branch" while 0 means page+content. (-1 would have meant element versioning, but that should never happen for a call to this function!)
	 * @param	integer		For offline versions; This is t3ver_oid, the original ID of the online page.
	 * @return	string		HTML content.
	 */
	function subElements($uid,$treeLevel,$origId=0)	{
		global $LANG;

		if ($GLOBALS['BE_USER']->workspace===0 || !$this->expandSubElements)	{	// In online workspace we have a reduced view because otherwise it will bloat the listing:
			return '<br />
					<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/joinbottom.gif','width="18" height="16"').' align="top" alt="" title="" />'.
			($origId ?
			'<a href="'.htmlspecialchars($this->doc->backPath.t3lib_extMgm::extRelPath('version').'cm1/index.php?id='.$uid.'&details='.rawurlencode('pages:'.$uid).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
			'<span class="typo3-dimmed"><em>['.$GLOBALS['LANG']->getLL('label_subelementsdetails').']</em><span></a>' :
			'<span class="typo3-dimmed"><em>['.$GLOBALS['LANG']->getLL('label_subelements').']</em><span>');
		} else {	// For an offline workspace, show sub elements:

			$tCell = array();

			// Find records that follow pages when swapping versions:
			$recList = array();
			foreach($GLOBALS['TCA'] as $tN => $tCfg)	{
				if ($tN!='pages' && ($treeLevel>0 || $GLOBALS['TCA'][$tN]['ctrl']['versioning_followPages']))	{
					$this->subElements_getNonPageRecords($tN, $uid, $recList);
				}
			}

			// Render records collected above:
			$elCount = count($recList)-1;
			foreach($recList as $c => $comb)	{
				list($tN,$rec) = $comb;

				$this->subElements_renderItem(
					$tCell,
					$tN,
					$uid,
					$rec,
					$origId,
					$c==$elCount && $treeLevel==0 ? 1 : 0,		// If true, will show bottom-join icon.
					''
				);
			}

			// For branch, dive into the subtree:
			if ($treeLevel>0) {

				// Drawing tree:
				/** @var $tree t3lib_pageTree */
				$tree = t3lib_div::makeInstance('t3lib_pageTree');
				$tree->init('AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));
				$tree->makeHTML = 2;		// 2=Also rendering depth-data into the result array
				$tree->getTree($uid, 99, '');

				// Traverse page tree:
				foreach($tree->tree as $data)	{

					// Render page in table cell:
					$this->subElements_renderItem(
						$tCell,
						'pages',
						$uid,
						t3lib_BEfunc::getRecord('pages',$data['row']['uid']),	// Needs all fields, at least more than what is given in $data['row']...
						$origId,
						2,		// 2=the join icon and icon for the record is not rendered for pages (where all is in $data['HTML']
						$data['HTML']
					);

					// Find all records from page and collect in $recList:
					$recList = array();
					foreach($GLOBALS['TCA'] as $tN => $tCfg)	{
						if ($tN!=='pages')	{
							$this->subElements_getNonPageRecords($tN, $data['row']['uid'], $recList);
						}
					}

					// Render records collected above:
					$elCount = count($recList)-1;
					foreach($recList as $c => $comb)	{
						list($tN,$rec) = $comb;

						$this->subElements_renderItem(
							$tCell,
							$tN,
							$uid,
							$rec,
							$origId,
							$c==$elCount?1:0,	// If true, will show bottom-join icon.
							$data['HTML_depthData']
						);
					}
				}
			}

			return '
					<!-- Sub-element tree for versions -->
					<table border="0" cellpadding="0" cellspacing="1" class="ver-subtree">
						'.implode('',$tCell).'
					</table>';
		}
	}

	/**
	 * Select records from a table and add them to recList
	 *
	 * @param	string		Table name (from TCA)
	 * @param	integer		PID to select records from
	 * @param	array		Array where records are accumulated, passed by reference
	 * @return	void
	 */
	function subElements_getNonPageRecords($tN, $uid, &$recList)	{
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$tN,
			'pid='.intval($uid).
			($GLOBALS['TCA'][$tN]['ctrl']['versioningWS'] ? ' AND t3ver_state=0' : '').
			t3lib_BEfunc::deleteClause($tN),
			'',
			$GLOBALS['TCA'][$tN]['ctrl']['sortby'] ? $GLOBALS['TCA'][$tN]['ctrl']['sortby'] : $GLOBALS['TYPO3_DB']->stripOrderBy($GLOBALS['TCA'][$tN]['ctrl']['default_sortby'])
		);

		foreach($records as $rec)	{
			$recList[] = array($tN,$rec);
		}
	}

	/**
	 * Render a single item in a subelement list into a table row:
	 *
	 * @param	array		Table rows, passed by reference
	 * @param	string		Table name
	 * @param	integer		Page uid for which the subelements are selected/shown
	 * @param	array		Row of element in list
	 * @param	integer		The uid of the online version of $uid. If zero it means we are drawing a row for the online version itself while a value means we are drawing display for an offline version.
	 * @param	integer		Mode of icon display: 0=not the last, 1= is the last in list (make joinbottom icon then), 2=do not shown icons are all (for pages from the page tree already rendered)
	 * @param	string		Prefix HTML data (icons for tree rendering)
	 * @return	void		(Content accumulated in $tCell!)
	 */
	function subElements_renderItem(&$tCell,$tN,$uid,$rec,$origId,$iconMode,$HTMLdata)	{

		// Initialize:
		$origUidFields = $GLOBALS['TCA'][$tN]['ctrl']['origUid'];
		$diffCode = '';

		if ($origUidFields)	{	// If there is a field for this table with original uids we will use that to connect records:
			if (!$origId)	{	// In case we are displaying the online originals:
				$this->targets['orig_'.$uid.'_'.$tN.'_'.$rec['uid']] = $rec;	// Build up target array (important that
				$tdParams =  ' id="orig_'.$uid.'_'.$tN.'_'.$rec['uid'].'" class="typo3-ver"';		// Setting ID of the table row
			} else {	// Version branch:
				if ($this->targets['orig_'.$origId.'_'.$tN.'_'.$rec[$origUidFields]])	{	// If there IS a corresponding original record...:

					// Prepare Table row parameters:
					$this->addHlSubelementsScript = true;
					$tdParams =  ' onmouseover="hlSubelements(\''.$origId.'_'.$tN.'_'.$rec[$origUidFields].'\', \''.$uid.'_'.$tN.'_'.$rec[$origUidFields].'\', 1, '.($this->diff==2?1:0).');"'.
						' onmouseout="hlSubelements(\''.$origId.'_'.$tN.'_'.$rec[$origUidFields].'\', \''.$uid.'_'.$tN.'_'.$rec[$origUidFields].'\', 0, '.($this->diff==2?1:0).');"'.
						' id="ver_'.$uid.'_'.$tN.'_'.$rec[$origUidFields].'" class="typo3-ver"';

					// Create diff view:
					if ($this->diff)	{
						list($diffHTML,$diffPct) = $this->createDiffView($tN, $rec, $this->targets['orig_'.$origId.'_'.$tN.'_'.$rec[$origUidFields]]);

						if ($this->diff==2)	{
							$diffCode =
								($diffPct ? '<span class="nobr">'.$diffPct.'% change</span>' : '-').
								'<div style="visibility: hidden; position: absolute;" id="diff_'.$uid.'_'.$tN.'_'.$rec[$origUidFields].'" class="diffLayer">'.
								$diffHTML.
								'</div>';
						} else {
							$diffCode =
								($diffPct<0 ? 'N/A' : ($diffPct ? $diffPct.'% change:' : '')).
								$diffHTML;
						}
					}

					// Unsetting the target fields allows us to mark all originals without a version in the subtree (see ->markupNewOriginals())
					unset($this->targets['orig_'.$origId.'_'.$tN.'_'.$rec[$origUidFields]]);
				} else {	// No original record, so must be new:
					$tdParams =  ' class="typo3-ver-new"';
				}
			}
		} else {	// If no original uid column is supported for this table we are forced NOT to display any diff or highlighting.
			$tdParams = ' class="typo3-ver-noComp"';
		}

		// Compile the cell:
		$tCell[] = '
						<tr'.$tdParams.'>
							<td class="iconTitle">'.
							$HTMLdata.
							($iconMode < 2 ?
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/ol/join'.($iconMode ? 'bottom' : '').'.gif','width="18" height="16"').' alt="" />'.
							t3lib_iconWorks::getIconImage($tN, $rec, $this->doc->backPath,'') : '').
							t3lib_BEfunc::getRecordTitle($tN, $rec, TRUE).
							'</td>
							<td class="cmdCell">'.
							$this->displayWorkspaceOverview_commandLinksSub($tN,$rec,$origId).
							'</td>'.($origId ? '<td class="diffCell">'.
							$diffCode.
							'</td>':'').'
						</tr>';
	}

	/**
	 * Links to publishing etc of a version
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @param	integer		The uid of the online version of $uid. If zero it means we are drawing a row for the online version itself while a value means we are drawing display for an offline version.
	 * @return	string		HTML content, mainly link tags and images.
	 */
	function displayWorkspaceOverview_commandLinksSub($table,$rec,$origId)	{
		global $LANG;

		$uid = $rec['uid'];
		if ($origId || $GLOBALS['BE_USER']->workspace===0)	{
			if (!$GLOBALS['BE_USER']->workspaceCannotEditRecord($table,$rec))	{
				// Edit
				if ($table==='pages')	{
					$actionLinks.=
						'<a href="#" onclick="top.loadEditId('.$uid.');top.goToModule(\''.$this->pageModule.'\'); return false;" title="' . $LANG->getLL('img_title_edit_page') . '">'.
						  t3lib_iconWorks::getSpriteIcon('actions-page-open') .
						'</a>';
				} else {
					$params = '&edit['.$table.']['.$uid.']=edit';
					$actionLinks.=
						'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick($params,$this->doc->backPath)).'" title="' . $LANG->getLL('img_title_edit_element') . '">'.
						  t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a>';
				}
			}

			// History/Log
			$actionLinks.=
				'<a href="'.htmlspecialchars($this->doc->backPath.'show_rechis.php?element='.rawurlencode($table.':'.$uid).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'" title="' . $LANG->getLL('img_title_show_log') . '">'.
				  t3lib_iconWorks::getSpriteIcon('actions-document-history-open') .
				'</a>';
		}

		// View
		if ($table==='pages')	{
			$actionLinks.=
				'<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($uid,$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($uid))).'">'.
				  t3lib_iconWorks::getSpriteIcon('actions-document-view') .
				'</a>';
		}

		return $actionLinks;
	}


	/**
	 * Links to stage change of a version
	 *
	 * @param	string		Table name
	 * @param	array		Offline record (version)
	 * @return	string		HTML content, mainly link tags and images.
	 */
	function displayWorkspaceOverview_stageCmd($table,&$rec_off)	{
		global $LANG;

		switch((int)$rec_off['t3ver_stage'])	{
			case 0:
				$sId = 1;
				$sLabel = $LANG->getLL('stage_editing');
				$color = '#666666';	// TODO Use CSS?
				$label = $LANG->getLL('label_commentforreviewer');
				$titleAttrib = $LANG->getLL('label_sendtoreview');
				break;
			case 1:
				$sId = 10;
				$sLabel = $LANG->getLL('label_review');
				$color = '#6666cc';	// TODO Use CSS?
				$label = $LANG->getLL('label_commentforpublisher');
				$titleAttrib = $LANG->getLL('label_approveforpublishing');
				break;
			case 10:
				$sLabel = $LANG->getLL('label_publish');
				$color = '#66cc66';	// TODO Use CSS?
				break;
			case -1:
				$sLabel = $this->doc->icons(2).$LANG->getLL('label_rejected');
				$sId = 0;
				$color = '#ff0000';	// TODO Use CSS?
				$label = $LANG->getLL('stage_label_user_comment');
				$titleAttrib = $LANG->getLL('label_resetstage');
				break;
			default:
				$sLabel = $LANG->getLL('label_undefined');
				$sId = 0;
				$color = '';
				break;
		}
		#debug($sId);

		$raiseOk = !$GLOBALS['BE_USER']->workspaceCannotEditOfflineVersion($table,$rec_off);

		if ($raiseOk && $rec_off['t3ver_stage'] != -1 && $GLOBALS['BE_USER']->workspaceCheckStageForCurrent($sId))	{
			$onClick = 'var commentTxt=window.prompt("'.$LANG->getLL('explain_reject').'","");
							if (commentTxt!=null) {window.location.href="'.$this->doc->issueCommand(
			'&cmd['.$table.']['.$rec_off['uid'].'][version][action]=setStage'.
			'&cmd['.$table.']['.$rec_off['uid'].'][version][stageId]=-1'
							).'&cmd['.$table.']['.$rec_off['uid'].'][version][comment]="+escape(commentTxt);}'.
			' return false;';
			// Reject:
			$actionLinks.=
			'<a href="#" onclick="'.htmlspecialchars($onClick).'"title="'.$LANG->getLL('label_reject').'"> '.
			  t3lib_iconWorks::getSpriteIcon('actions-move-down') .
			'</a>';
		} else {
			// Reject:
			$actionLinks.=
			'<img src="'.$this->doc->backPath.'gfx/clear.gif" width="14" height="14" alt="" align="top" title="" />';
		}

		// TODO Use CSS?
		$actionLinks.= '<span style="background-color: '.$color.'; color: white;">'.$sLabel.'</span>';

		// Raise
		if ($raiseOk && $GLOBALS['BE_USER']->workspaceCheckStageForCurrent($sId))	{
			$onClick = 'var commentTxt=window.prompt("'.$label.'","");
							if (commentTxt!=null) {window.location.href="'.$this->doc->issueCommand(
			'&cmd['.$table.']['.$rec_off['uid'].'][version][action]=setStage'.
			'&cmd['.$table.']['.$rec_off['uid'].'][version][stageId]='.$sId
			).'&cmd['.$table.']['.$rec_off['uid'].'][version][comment]="+escape(commentTxt);}'.
			' return false;';
			if ($rec_off['t3ver_stage']!=10)	{
				$actionLinks.=
				'<a href="#" onclick="'.htmlspecialchars($onClick).'" title="'.htmlspecialchars($titleAttrib).'">'.
				  t3lib_iconWorks::getSpriteIcon('actions-move-up') .
				'</a>';

				$this->stageIndex[$sId][$table][] = $rec_off['uid'];
			}
		}

		return $actionLinks;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.wslib_gui.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/user/ws/class.wslib_gui.php']);
}

?>