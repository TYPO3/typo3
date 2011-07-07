<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Versioning module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:version/locallang.xml');
	// DEFAULT initialization of a module [END]

require_once('../ws/class.wslib.php');



/**
 * Versioning module, including workspace management
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_version_cm1 extends t3lib_SCbase {

		// Default variables for backend modules
	var $MCONF = array();				// Module configuration
	var $MOD_MENU = array();			// Module menu items
	var $MOD_SETTINGS = array();		// Module session settings

	/**
	 * document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;
	var $content;						// Accumulated content


		// Internal:
	var $showWorkspaceCol = 0;
	var $formatWorkspace_cache = array();
	var $formatCount_cache = array();
	var $targets = array();		// Accumulation of online targets.
	var $pageModule = '';			// Name of page module
	var $publishAccess = FALSE;
	var $be_user_Array = array();
	var $stageIndex = array();
	var $recIndex = array();

	/*********************************
	 *
	 * Standard module initialization
	 *
	 *********************************/

	/**
	 * Initialize menu configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return	void
	 */
	function main()	{
			// Template markers
		$markers = array(
			'CSH' => '',
			'FUNC_MENU' => '',
			'WS_MENU' => '',
			'CONTENT' => ''
		);

			// Setting module configuration:
		$this->MCONF = $GLOBALS['MCONF'];

		$this->REQUEST_URI = str_replace('&sendToReview=1','',t3lib_div::getIndpEnv('REQUEST_URI'));

			// Draw the header.
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/version.html');

			// Add styles
		$this->doc->inDocStylesArray[$GLOBALS['MCONF']['name']] = '
.version-diff-1 { background-color: green; }
.version-diff-2 { background-color: red; }
';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Getting input data:
		$this->id = intval(t3lib_div::_GP('id'));		// Page id. If set, indicates activation from Web>Versioning module
		$this->uid = intval(t3lib_div::_GP('uid'));		// Record uid. Goes with table name to indicate specific record
		$this->table = t3lib_div::_GP('table');			// Record table. Goes with uid to indicate specific record
		$this->details = t3lib_div::_GP('details');		// Page id. If set, indicates activation from Web>Versioning module
		$this->diffOnly = t3lib_div::_GP('diffOnly');		// Flag. If set, shows only the offline version and with diff-view

			// Force this setting:
		$this->MOD_SETTINGS['expandSubElements'] = TRUE;
		$this->MOD_SETTINGS['diff'] = $this->details || $this->MOD_SETTINGS['diff']?1:0;

			// Reading the record:
		$record = t3lib_BEfunc::getRecord($this->table,$this->uid);
		if ($record['pid']==-1)	{
			$record = t3lib_BEfunc::getRecord($this->table,$record['t3ver_oid']);
		}

		$this->recordFound = is_array($record);

		$pidValue = $this->table==='pages' ? $this->uid : $record['pid'];

			// Checking access etc.
		if ($this->recordFound && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS'] && !$this->id)	{
			$this->doc->form='<form action="" method="post">';
			$this->uid = $record['uid']; 	// Might have changed if new live record was found!

				// Access check!
				// The page will show only if there is a valid page and if this page may be viewed by the user
			$this->pageinfo = t3lib_BEfunc::readPageAccess($pidValue,$this->perms_clause);
			$access = is_array($this->pageinfo) ? 1 : 0;

			if (($pidValue && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$pidValue)) {

					// JavaScript
				$this->doc->JScode.= '
					<script language="javascript" type="text/javascript">
						script_ended = 0;
						function jumpToUrl(URL)	{
							window.location.href = URL;
						}

						function hlSubelements(origId, verId, over, diffLayer)	{	//
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
					</script>
				';

					// If another page module was specified, replace the default Page module with the new one
				$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
				$this->pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

					// Setting publish access permission for workspace:
				$this->publishAccess = $GLOBALS['BE_USER']->workspacePublishAccess($GLOBALS['BE_USER']->workspace);

					// Render content:
				$this->versioningMgm();
			}

			$this->content.=$this->doc->spacer(10);

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
			$markers['CONTENT'] = $this->content;
		} else {
				// If no access or id value, create empty document
			$this->content = $this->doc->section($GLOBALS['LANG']->getLL('clickAPage_header'), $GLOBALS['LANG']->getLL('clickAPage_content'), 0, 1);

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CONTENT'] = $this->content;
		}
			// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputs accumulated module content to browser.
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		$buttons = array(
			'csh' => '',
			'view' => '',
			'record_list' => '',
			'shortcut' => '',
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_txversionM1', '', $GLOBALS['BACK_PATH']);

		if ($this->recordFound && $GLOBALS['TCA'][$this->table]['ctrl']['versioningWS']) {
				// View page
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-view') .
					'</a>';

				// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}

				// If access to Web>List for user, then link to that module.
			$buttons['record_list'] = t3lib_BEfunc::getListViewLink(
				array(
					'id' => $this->pageinfo['uid'],
					'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI'),
				),
				'',
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList')
 			);
		}
		return $buttons;
	}








	/******************************
	 *
	 * Versioning management
	 *
	 ******************************/

	/**
	 * Management of versions for record
	 *
	 * @return	void
	 */
	function versioningMgm()	{

			// Diffing:
		$diff_1 = t3lib_div::_POST('diff_1');
		$diff_2 = t3lib_div::_POST('diff_2');
		if (t3lib_div::_POST('do_diff'))	{
			$content='';
			$content.='<h3>' . $GLOBALS['LANG']->getLL('diffing') . ':</h3>';
			if ($diff_1 && $diff_2)	{
				$diff_1_record = t3lib_BEfunc::getRecord($this->table, $diff_1);
				$diff_2_record = t3lib_BEfunc::getRecord($this->table, $diff_2);

				if (is_array($diff_1_record) && is_array($diff_2_record))	{
					t3lib_div::loadTCA($this->table);
					$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');

					$tRows=array();
								$tRows[] = '
									<tr class="bgColor5 tableheader">
										<td>' . $GLOBALS['LANG']->getLL('fieldname') . '</td>
										<td width="98%">' . $GLOBALS['LANG']->getLL('coloredDiffView') . ':</td>
									</tr>
								';
					foreach($diff_1_record as $fN => $fV)	{
						if ($GLOBALS['TCA'][$this->table]['columns'][$fN] && $GLOBALS['TCA'][$this->table]['columns'][$fN]['config']['type'] !== 'passthrough'
								&& !t3lib_div::inList('t3ver_label', $fN)) {

							if (strcmp($diff_1_record[$fN],$diff_2_record[$fN]))	{

								$diffres = $t3lib_diff_Obj->makeDiffDisplay(
									t3lib_BEfunc::getProcessedValue($this->table,$fN,$diff_2_record[$fN],0,1),
									t3lib_BEfunc::getProcessedValue($this->table,$fN,$diff_1_record[$fN],0,1)
								);

								$tRows[] = '
									<tr class="bgColor4">
										<td>'.$fN.'</td>
										<td width="98%">'.$diffres.'</td>
									</tr>
								';
							}
						}
					}

					if (count($tRows)>1)	{
						$content .= '<table border="0" cellpadding="1" cellspacing="1" width="100%">' . implode('', $tRows) . '</table><br /><br />';
					} else {
						$content .= $GLOBALS['LANG']->getLL('recordsMatchesCompletely');
					}
				} else $content .= $GLOBALS['LANG']->getLL('errorRecordsNotFound');
			} else {
				$content .= $GLOBALS['LANG']->getLL('errorDiffSources');
			}
		}

			// Element:
		$record = t3lib_BEfunc::getRecord($this->table,$this->uid);
		$recordIcon = t3lib_iconWorks::getSpriteIconForRecord($this->table, $record);
		$recTitle = t3lib_BEfunc::getRecordTitle($this->table,$record,TRUE);

			// Display versions:
		$content.='
			'.$recordIcon.$recTitle.'
			<form name="theform" action="'.str_replace('&sendToReview=1','',$this->REQUEST_URI).'" method="post">
			<table border="0" cellspacing="1" cellpadding="1">';
			$content.='
				<tr class="bgColor5 tableheader">
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_title') . '">' . $GLOBALS['LANG']->getLL('tblHeader_title') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_uid') . '">' . $GLOBALS['LANG']->getLL('tblHeader_uid') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_oid') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_oid') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_id') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_id') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_wsid') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_wsid') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_state') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_state') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_stage') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_stage') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_count') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_count') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_pid') . '">' . $GLOBALS['LANG']->getLL('tblHeader_pid') . '</td>
					<td title="' . $GLOBALS['LANG']->getLL('tblHeaderDesc_t3ver_label') . '">' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_label') . '</td>
					<td colspan="2"><input type="submit" name="do_diff" value="' . $GLOBALS['LANG']->getLL('diff') . '" /></td>
				</tr>';

		$versions = t3lib_BEfunc::selectVersionsOfRecord($this->table, $this->uid, '*', $GLOBALS['BE_USER']->workspace);
		foreach($versions as $row)	{
			$adminLinks = $this->adminLinks($this->table,$row);

			$content.='
				<tr class="' . ($row['uid'] != $this->uid ? 'bgColor4' : 'bgColor2 tableheader') . '">
					<td>'.($row['uid']!=$this->uid ? '<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][action]=swap').'" title="' . $GLOBALS['LANG']->getLL('swapWithCurrent', TRUE) . '">'.
						t3lib_iconWorks::getSpriteIcon('actions-version-swap-version') .
						'</a>' /* (
							$this->table == 'pages' ?
							'<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][action]=swap&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][swapContent]=1').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/insert2.gif','width="14" height="14"').' alt="" title="Publish page AND content!" />'.
						'</a>'.
							'<a href="'.$this->doc->issueCommand('&cmd['.$this->table.']['.$this->uid.'][version][action]=swap&cmd['.$this->table.']['.$this->uid.'][version][swapWith]='.$row['uid'].'&cmd['.$this->table.']['.$this->uid.'][version][swapContent]=ALL').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/insert4.gif','width="14" height="14"').' alt="" title="Publish page AND content! - AND ALL SUBPAGES!" />'.
						'</a>' : '') */ : t3lib_iconWorks::getSpriteIcon('status-status-current', array('title' =>  $GLOBALS['LANG']->getLL('currentOnlineVersion', TRUE)))) . '</td>
					<td nowrap="nowrap">'.$adminLinks.'</td>
					<td nowrap="nowrap">'.t3lib_BEfunc::getRecordTitle($this->table,$row,TRUE).'</td>
					<td>'.$row['uid'].'</td>
					<td>'.$row['t3ver_oid'].'</td>
					<td>'.$row['t3ver_id'].'</td>
					<td>'.$row['t3ver_wsid'].'</td>
					<td>'.$row['t3ver_state'].'</td>
					<td>'.$row['t3ver_stage'].'</td>
					<td>'.$row['t3ver_count'].'</td>
					<td>'.$row['pid'].'</td>
					<td nowrap="nowrap"><a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit['.$this->table.']['.$row['uid'].']=edit&columnsOnly=t3ver_label',$this->doc->backPath)).'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.edit', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a>' . htmlspecialchars($row['t3ver_label']) . '</td>
					<td class="version-diff-1"><input type="radio" name="diff_1" value="'.$row['uid'].'"'.($diff_1==$row['uid'] ? ' checked="checked"':'').'/></td>
					<td class="version-diff-2"><input type="radio" name="diff_2" value="'.$row['uid'].'"'.($diff_2==$row['uid'] ? ' checked="checked"':'').'/></td>
				</tr>';

				// Show sub-content if the table is pages AND it is not the online branch (because that will mostly render the WHOLE tree below - not smart;)
			if ($this->table == 'pages' && $row['uid']!=$this->uid)	{
				$sub = $this->pageSubContent($row['uid']);

				if ($sub)	{
					$content.='
						<tr>
							<td></td>
							<td></td>
							<td colspan="10">'.$sub.'</td>
							<td colspan="2"></td>
						</tr>';
				}
			}
		}
		$content.='</table></form>';

		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('title'), $content, 0, 1);


			// Create new:
		$content='

			<form action="'.$this->doc->backPath.'tce_db.php" method="post">
			' . $GLOBALS['LANG']->getLL('tblHeader_t3ver_label') . ': <input type="text" name="cmd[' . $this->table . '][' . $this->uid . '][version][label]" /><br />
			'.(($this->table == 'pages' && $GLOBALS['TYPO3_CONF_VARS']['BE']['elementVersioningOnly'] == FALSE)? '<select name="cmd['.$this->table.']['.$this->uid.'][version][treeLevels]">
				'.($GLOBALS['BE_USER']->workspaceVersioningTypeAccess(0) ? '<option value="0">' . $GLOBALS['LANG']->getLL('cmdPid0') . '</option>' : '').'
				'.($GLOBALS['BE_USER']->workspaceVersioningTypeAccess(1) ? '<option value="100">' . $GLOBALS['LANG']->getLL('cmdPid100') . '</option>' : '').'
				'.($GLOBALS['BE_USER']->workspaceVersioningTypeAccess(-1) ? '<option value="-1">' . $GLOBALS['LANG']->getLL('cmdPid1') . '</option>' : '').'
			</select>' : '').'
			<br /><input type="hidden" name="cmd[' . $this->table . '][' . $this->uid . '][version][action]" value="new" />
			<input type="hidden" name="prErr" value="1" />
			<input type="hidden" name="redirect" value="'.htmlspecialchars($this->REQUEST_URI).'" />
			<input type="submit" name="_" value="' . $GLOBALS['LANG']->getLL('createNewVersion') . '" />
			' . t3lib_TCEforms::getHiddenTokenField('tceAction') . '
			</form>

		';

		$this->content.=$this->doc->spacer(15);
		$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('createNewVersion'), $content,0,1);

	}

	/**
	 * Recursively look for children for page version with $pid
	 *
	 * @param	integer		UID of page record for which to look up sub-elements following that version
	 * @param	integer		Counter, do not set (limits to 100 levels)
	 * @return	string		Table with content if any
	 */
	function pageSubContent($pid,$c=0)	{
		$tableNames = t3lib_div::removeArrayEntryByValue(array_keys($GLOBALS['TCA']), 'pages');
		$tableNames[] = 'pages';

		foreach($tableNames as $tN)	{
				// Basically list ALL tables - not only those being copied might be found!
			#if ($GLOBALS['TCA'][$tN]['ctrl']['versioning_followPages'] || $tN=='pages')	{
				$mres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$tN,
					'pid=' . intval($pid) . t3lib_BEfunc::deleteClause($tN),
					'',
					($GLOBALS['TCA'][$tN]['ctrl']['sortby'] ? $GLOBALS['TCA'][$tN]['ctrl']['sortby'] : '')
				);

				if ($GLOBALS['TYPO3_DB']->sql_num_rows($mres))	{
					$content.='
						<tr>
							<td colspan="4" class="' . ($GLOBALS['TCA'][$tN]['ctrl']['versioning_followPages'] ? 'bgColor6' : ($tN == 'pages' ? 'bgColor5' : 'bgColor-10')) . '"' . (!$GLOBALS['TCA'][$tN]['ctrl']['versioning_followPages'] && $tN !== 'pages' ? ' style="color: #666666; font-style:italic;"' : '') . '>' . $tN . '</td>
						</tr>';
					while ($subrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($mres))	{
						$ownVer = $this->lookForOwnVersions($tN,$subrow['uid']);
						$content.='
							<tr>
								<td>'.$this->adminLinks($tN,$subrow).'</td>
								<td>'.$subrow['uid'].'</td>
								'.($ownVer>1 ? '<td style="font-weight: bold; background-color: yellow;"><a href="index.php?table='.rawurlencode($tN).'&uid='.$subrow['uid'].'">'.($ownVer-1).'</a></td>' : '<td></td>').'
								<td width="98%">'.t3lib_BEfunc::getRecordTitle($tN,$subrow,TRUE).'</td>
							</tr>';

						if ($tN == 'pages' && $c<100)	{
							$sub = $this->pageSubContent($subrow['uid'],$c+1);

							if ($sub)	{
								$content.='
									<tr>
										<td></td>
										<td></td>
										<td></td>
										<td width="98%">'.$sub.'</td>
									</tr>';
							}
						}
					}
				}
			#}
		}

		return $content ? '<table border="1" cellpadding="1" cellspacing="0" width="100%">'.$content.'</table>' : '';
	}

	/**
	 * Look for number of versions of a record
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @return	integer		Number of versions for record, FALSE if none.
	 */
	function lookForOwnVersions($table,$uid)	{
		$versions = t3lib_BEfunc::selectVersionsOfRecord($table, $uid, 'uid');
		if (is_array($versions))	{
			return count($versions);
		}
		return FALSE;
	}

	/**
	 * Administrative links for a table / record
	 *
	 * @param	string		Table name
	 * @param	array		Record for which administrative links are generated.
	 * @return	string		HTML link tags.
	 */
	function adminLinks($table,$row)	{

			// Edit link:
		$adminLink = '<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit['.$table.']['.$row['uid'].']=edit',$this->doc->backPath)).'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.edit', TRUE) . '">'.
							t3lib_iconWorks::getSpriteIcon('actions-document-open') .
						'</a>';

			// Delete link:
		$adminLink.= '<a href="'.htmlspecialchars($this->doc->issueCommand('&cmd['.$table.']['.$row['uid'].'][delete]=1')).'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.delete', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
						'</a>';



		if ($table == 'pages')	{

				// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
			$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

				// Perform some acccess checks:
			$a_wl = $GLOBALS['BE_USER']->check('modules', 'web_list');
			$a_wp = t3lib_extMgm::isLoaded('cms') && $GLOBALS['BE_USER']->check('modules', $pageModule);

			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\''.$pageModule.'\'); return false;">'.
							t3lib_iconWorks::getSpriteIcon('actions-page-open') .
						'</a>';
			$adminLink.='<a href="#" onclick="top.loadEditId('.$row['uid'].');top.goToModule(\'web_list\'); return false;">'.
							t3lib_iconWorks::getSpriteIcon('actions-system-list-open') .
						'</a>';

				// "View page" icon is added:
			$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['uid'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['uid']))).'">'.
					t3lib_iconWorks::getSpriteIcon('actions-document-view') .
				'</a>';
		} else {
			if ($row['pid']==-1)	{
				$getVars = '&ADMCMD_vPrev['.rawurlencode($table.':'.$row['t3ver_oid']).']='.$row['uid'];

					// "View page" icon is added:
				$adminLink.='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::viewOnClick($row['_REAL_PID'],$this->doc->backPath,t3lib_BEfunc::BEgetRootLine($row['_REAL_PID']),'','',$getVars)).'">'.
						t3lib_iconWorks::getSpriteIcon('actions-document-view') .
					'</a>';
			}
		}

		return $adminLink;
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/cm1/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/version/cm1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_version_cm1');
$SOBE->init();


$SOBE->main();
$SOBE->printContent();

?>
