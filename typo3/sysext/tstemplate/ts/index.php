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
 * Module: TypoScript Tools
 *
 *
 * 	$TYPO3_CONF_VARS["MODS"]["web_ts"]["onlineResourceDir"]  = Directory of default resources. Eg. "fileadmin/res/" or so.
 *
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */



unset($MCONF);
require('conf.php');
require($BACK_PATH . 'init.php');
require($BACK_PATH . 'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:tstemplate/ts/locallang.xml');

$BE_USER->modAccess($MCONF, true);


// ***************************
// Script Classes
// ***************************
class SC_mod_web_ts_index extends t3lib_SCbase {
	var $perms_clause;
	var $e;
	var $sObj;
	var $edit;
	var $textExtensions = 'html,htm,txt,css,tmpl,inc,js';

	var $modMenu_type = '';
	var $modMenu_dontValidateList = '';
	var $modMenu_setDefaultList = '';

	function init() {

		parent::init();

		$this->id = intval(t3lib_div::_GP('id'));
		$this->e = t3lib_div::_GP('e');
		$this->sObj = t3lib_div::_GP('sObj');
		$this->edit = t3lib_div::_GP('edit');

		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);

		if (t3lib_div::_GP('clear_all_cache')) {
			$this->include_once[] = PATH_t3lib . 'class.t3lib_tcemain.php';
		}
	}

	function clearCache() {
		if (t3lib_div::_GP('clear_all_cache')) {
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->stripslashes_values = 0;
			$tce->start(array(), array());
			$tce->clear_cacheCmd('all');
		}
	}

	function main() {

			// Template markers
		$markers = array(
			'CSH' => '',
			'FUNC_MENU' => '',
			'CONTENT' => ''
		);

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$this->access = is_array($this->pageinfo) ? 1 : 0;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/tstemplate.html');

		if ($this->id && $this->access) {
			$this->doc->form = '<form action="index.php?id=' . $this->id . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" name="editForm">';


				// JavaScript
			$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL) {
				window.location.href = URL;
			}
			function uFormUrl(aname) {
				document.forms[0].action = "index.php?id=' . $this->id . '#"+aname;
			}
			function brPoint(lnumber,t) {
				window.location.href = "index.php?id=' . $this->id . '&SET[function]=tx_tstemplateobjbrowser&SET[ts_browser_type]="+(t?"setup":"const")+"&breakPointLN="+lnumber;
				return false;
			}
		</script>
		';

			$this->doc->postCode = '
		<script language="javascript" type="text/javascript">
			script_ended = 1;
			if (top.fsMod) top.fsMod.recentIds["web"] = ' . intval($this->id) . ';
		</script>
		';

			$this->doc->inDocStylesArray[] = '
				TABLE#typo3-objectBrowser A { text-decoration: none; }
				TABLE#typo3-objectBrowser .comment { color: maroon; font-weight: bold; }
				TABLE#ts-analyzer tr.t3-row-header { background-color: #A2AAB8; }
				TABLE#ts-analyzer tr td {padding: 0 2px;}
				TABLE#ts-analyzer tr.t3-row-header td { padding: 2px 4px; font-weight:bold; color: #fff; }
				.tsob-menu label, .tsob-menu-row2 label, .tsob-conditions label {padding: 0 5px; vertical-align: text-top;}
				.tsob-menu-row2 {margin-top: 10px;}
				.tsob-conditions {padding: 1px 2px;}
				.tsob-search-submit {margin-left: 3px; margin-right: 3px;}
				.tst-analyzer-options { margin:5px 0; }
				.tst-analyzer-options label {padding-left:5px; vertical-align:text-top; }
				.bgColor0 {background-color:#fff;}
			';


				// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();

				// Build the modulle content
			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('moduleTitle'));
			$this->extObjContent();
			$this->content .= $this->doc->spacer(10);

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			// $markers['CSH'] = $docHeaderButtons['csh'];
			$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
			$markers['CONTENT'] = $this->content;
		} else {
				// If no access or if ID == zero

			$this->doc->inDocStylesArray[] = '
				TABLE#ts-overview tr.t3-row-header { background-color: #A2AAB8; }
				TABLE#ts-overview tr td {padding: 2px;}
				TABLE#ts-overview tr.t3-row-header td { padding: 2px 4px; font-weight:bold; color: #fff; }
			';
				// Template pages:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'pages.uid, count(*) AS count, max(sys_template.root) AS root_max_val, min(sys_template.root) AS root_min_val',
						'pages,sys_template',
						'pages.uid=sys_template.pid'.
							t3lib_BEfunc::deleteClause('pages').
							t3lib_BEfunc::versioningPlaceholderClause('pages').
							t3lib_BEfunc::deleteClause('sys_template').
							t3lib_BEfunc::versioningPlaceholderClause('sys_template'),
						'pages.uid'
					);
			$templateArray = array();
			$pArray = array();

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->setInPageArray(
					$pArray,
					t3lib_BEfunc::BEgetRootLine($row['uid'], 'AND 1=1'),
					$row
				);
			}

			$lines = array();
			$lines[] = '<tr class="t3-row-header">
				<td nowrap>' . $GLOBALS['LANG']->getLL('pageName') . '</td>
				<td nowrap>' . $GLOBALS['LANG']->getLL('templates') . '</td>
				<td nowrap>' . $GLOBALS['LANG']->getLL('isRoot') . '</td>
				<td nowrap>' . $GLOBALS['LANG']->getLL('isExt') . '</td>
				</tr>';
			$lines = array_merge($lines, $this->renderList($pArray));

			$table = '<table border="0" cellpadding="0" cellspacing="1" id="ts-overview">' . implode('', $lines) . '</table>';
			$this->content = $this->doc->section($GLOBALS['LANG']->getLL('moduleTitle'), '
			<br />
			' . $GLOBALS['LANG']->getLL('overview') . '
			<br /><br />' . $table);

			// ********************************************
			// RENDER LIST of pages with templates, END
			// ********************************************

			$this->content .= $this->doc->spacer(10);

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			// $markers['CSH'] = $docHeaderButtons['csh'];
			$markers['CONTENT'] = $this->content;
		}

			// Build the <body> for the module
		$this->content = $this->doc->startPage('Template Tools');
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {

		$buttons = array(
			'back' => '',
			'close' => '',
			'new' => '',
			'save' => '',
			'save_close' => '',
			'view' => '',
			'record_list' => '',
			'shortcut' => '',
		);

		if ($this->id && $this->access) {
				// View page
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-view') . 
					'</a>';

				// If access to Web>List for user, then link to that module.
			if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
				$href = $GLOBALS['BACK_PATH'] . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
				$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-system-list-open') . 
						'</a>';
			}

			if ($this->extClassConf['name'] == 'tx_tstemplateinfo') {
					// NEW button
				$buttons['new'] = '<input type="image" class="c-inputButton" name="createExtension" value="New"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/new_el.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:db_new.php.pagetitle', TRUE) . '" />';

				if (!empty($this->e) && !t3lib_div::_POST('abort') && !t3lib_div::_POST('saveclose')) {
						// no NEW-button while edit
					$buttons['new'] = '';

						// SAVE button
					$buttons['save'] = t3lib_iconWorks::getSpriteIcon('actions-document-save',
						array(
							'html' => '<input type="image" class="c-inputButton" name="submit" src="clear.gif" ' .
								'title="'. $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) .'" ' . 
								'value="'. $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) .'" ' . 
								'/>'
						));

						// SAVE AND CLOSE button
					$buttons['save_close'] = t3lib_iconWorks::getSpriteIcon('actions-document-save-close',
						array(
							'html' => '<input type="image" class="c-inputButton" name="saveclose" src="clear.gif" '.
								'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', TRUE) . '" ' .
								'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc', TRUE) . '" ' .
								'/>'
						));

						// CLOSE button
					$buttons['close'] = t3lib_iconWorks::getSpriteIcon('actions-document-close',
						array(
							'html' => '<input type="image" class="c-inputButton" name="abort" src="clear.gif" ' . 
								'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', TRUE) . '" ' .
								'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc', TRUE) . '" ' .
								'/>'
						));

				}
			} elseif($this->extClassConf['name'] == 'tx_tstemplateceditor' && count($this->MOD_MENU['constant_editor_cat'])) {
					// SAVE button
				$buttons['save'] = t3lib_iconWorks::getSpriteIcon('actions-document-save',
					array('html' => '<input type="image" class="c-inputButton" name="submit" src="clear.gif" '.
						'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) . '" ' .
						'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', TRUE) . '" ' .
						'/>'));
			} elseif($this->extClassConf['name'] == 'tx_tstemplateobjbrowser') {
				if(!empty($this->sObj)) {
						// BACK
					$buttons['back'] = '<a href="index.php?id=' . $this->id . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.goBack', TRUE) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-view-go-back') . 
								'</a>';
				}
			}

				// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
			}
		} else {
				// Shortcut
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('id', '', $this->MCONF['name']);
			}
		}

		return $buttons;
	}

	// ***************************
	// OTHER FUNCTIONS:
	// ***************************

	/**
	* Counts the records in the system cache_* tables and returns these values.
	*
	* @param boolean $humanReadable: Returns human readable string instead of an array
	* @return mixed The number of records in cache_* tables as array or string
	* @deprecated since TYPO3 4.2.0
	*/
	function getCountCacheTables($humanReadable) {
		$out = array();

		$out['cache_pages'] = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('id', 'cache_pages');
		$out['cache_pagesection'] = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('id', 'cache_pagesection');
		$out['cache_hash'] = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('id', 'cache_hash');

		if ($humanReadable) {
			$newOut = array();
			foreach ($out as $k => $v) {
				$newOut[] = $k . ":" . $v;
			}
			$out = implode(', ', $newOut);
		}
		return $out;
	}

	function linkWrapTemplateTitle($title, $onlyKey = '') {
		if ($onlyKey) {
			$title = '<a href="index.php?id=' . $GLOBALS['SOBE']->id . '&e[' . $onlyKey . ']=1&SET[function]=tx_tstemplateinfo">' . htmlspecialchars($title) . '</a>';
		} else {
			$title = '<a href="index.php?id=' . $GLOBALS['SOBE']->id . '&e[constants]=1&e[config]=1&SET[function]=tx_tstemplateinfo">' . htmlspecialchars($title) . '</a>';
		}
		return $title;
	}

	function noTemplate($newStandardTemplate = 0) {

		$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
		/* @var $tmpl t3lib_tsparser_ext */
		$tmpl->tt_track = false;	// Do not log time-performance information
		$tmpl->init();

			// No template
		$theOutput .= $this->doc->spacer(10);
		
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$GLOBALS['LANG']->getLL('noTemplateDescription') . '<br />' . $GLOBALS['LANG']->getLL('createTemplateToEditConfiguration'),
			$GLOBALS['LANG']->getLL('noTemplate'),
			t3lib_FlashMessage::INFO
		);
		$theOutput .= $flashMessage->render();
		
		
			// New standard?
		if ($newStandardTemplate) {
			if (t3lib_extMgm::isLoaded('statictemplates')) { // check wether statictemplates are supported
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title,uid', 'static_template', '', '', 'title');
				$opt = '';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					if (substr(trim($row['title']), 0, 8) == 'template') {
						$opt .= '<option value="' . $row['uid'] . '">' . htmlspecialchars($row['title']) . '</option>';
					}
 				}
				$selector = '<select name="createStandard"><option></option>' . $opt . '</select><br />';
				$staticsText = ', optionally based on one of the standard templates';
			} else {
				$selector = '<input type="hidden" name="createStandard" value="" />';
				$staticsText = '';
 			}

				// Extension?
			$theOutput .= $this->doc->spacer(10);
			$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('newWebsite') . $staticsText, $GLOBALS['LANG']->getLL('newWebsiteDescription') . '<br /><br />' .
			$selector . 
			'<input type="Submit" name="newWebsite" value="' . $GLOBALS['LANG']->getLL('newWebsiteAction') . '" />', 0, 1);
		}
			// Extension?
		$theOutput .= $this->doc->spacer(10);
		$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('extTemplate'), $GLOBALS['LANG']->getLL('extTemplateDescription') . '<br /><br />' .
			'<input type="submit" name="createExtension" value="' . $GLOBALS['LANG']->getLL('extTemplateAction') . '" />', 0, 1);

			// Go to first appearing...
		$first = $tmpl->ext_prevPageWithTemplate($this->id, $this->perms_clause);
		if ($first) {
			$theOutput .= $this->doc->spacer(10);
			$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('goToClosest'),
					sprintf($GLOBALS['LANG']->getLL('goToClosestDescription') . '<br /><br />%s<strong>' . $GLOBALS['LANG']->getLL('goToClosestAction') . '</strong>%s', htmlspecialchars($first['title']), $first['uid'],
					'<a href="index.php?id=' . $first['uid'] . '">', '</a>'), 0, 1);
		}
		return $theOutput;
	}

	function templateMenu() {
		$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
		/* @var $tmpl t3lib_tsparser_ext */
		$tmpl->tt_track = false;	// Do not log time-performance information
		$tmpl->init();
		$all = $tmpl->ext_getAllTemplates($this->id, $this->perms_clause);
		$menu = '';

		if (count($all) > 1) {
			$this->MOD_MENU['templatesOnPage'] = array();
			foreach ($all as $d) {
				$this->MOD_MENU['templatesOnPage'][$d['uid']] = $d['title'];
			}
		}

		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
		$menu = t3lib_BEfunc::getFuncMenu($this->id, 'SET[templatesOnPage]', $this->MOD_SETTINGS['templatesOnPage'], $this->MOD_MENU['templatesOnPage']);

		return $menu;
	}

	function createTemplate($id, $actTemplateId = 0) {
		if (t3lib_div::_GP('createExtension')) {
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->stripslashes_values = 0;
			$recData = array();
			$recData['sys_template']['NEW'] = array(
				'pid' => $actTemplateId ? -1 * $actTemplateId : $id,
				'title' => "+ext",
			);

			$tce->start($recData, array());
			$tce->process_datamap();
			return $tce->substNEWwithIDs['NEW'];
		} elseif (t3lib_div::_GP('newWebsite')) {
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			/* @var $tce t3lib_TCEmain */
			$tce->stripslashes_values = 0;
			$recData = array();

			if (intval(t3lib_div::_GP('createStandard'))) {
				$staticT = intval(t3lib_div::_GP('createStandard'));
				$recData['sys_template']['NEW'] = array(
					'pid' => $id,
					'title' => $GLOBALS['LANG']->getLL('titleNewSiteStandard'),
					'sorting' => 0,
					'root' => 1,
					'clear' => 3,
					'include_static' => $staticT . ',57',	// 57 is cSet
				);
			} else {
				$recData['sys_template']['NEW'] = array(
					'pid' => $id,
					'title' => $GLOBALS['LANG']->getLL('titleNewSite'),
					'sorting' => 0,
					'root' => 1,
					'clear' => 3,
					'config' => '
# Default PAGE object:
page = PAGE
page.10 = TEXT
page.10.value = HELLO WORLD!
',
				);
			}
			$tce->start($recData, array());
			$tce->process_datamap();
			$tce->clear_cacheCmd('all');
		}
	}

	// ********************************************
	// RENDER LIST of pages with templates, BEGIN
	// ********************************************
	function setInPageArray(&$pArray, $rlArr, $row) {
		ksort($rlArr);
		reset($rlArr);
		if (!$rlArr[0]['uid']) {
			array_shift($rlArr);
		}

		$cEl = current($rlArr);
		$pArray[$cEl['uid']] = htmlspecialchars($cEl['title']);
		array_shift($rlArr);
		if (count($rlArr)) {
			if (!isset($pArray[$cEl['uid'] . '.'])) {
				$pArray[$cEl['uid'] . '.'] = array();
			}
			$this->setInPageArray($pArray[$cEl['uid'] . '.'], $rlArr, $row);
		} else {
			$pArray[$cEl['uid'] . '_'] = $row;
		}
	}

	function renderList($pArray, $lines = array(), $c = 0) {
		if (is_array($pArray)) {
			reset($pArray);
			static $i;
			foreach ($pArray as $k => $v) {
				if (t3lib_div::testInt($k)) {
					if (isset($pArray[$k . "_"])) {
						$lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap><img src="clear.gif" width="1" height="1" hspace=' . ($c * 10) . ' align="top">' .
							'<a href="' . t3lib_div::linkThisScript(array('id' => $k)) . '">' .
							t3lib_iconWorks::getSpriteIconForRecord('pages', t3lib_BEfunc::getRecordWSOL('pages', $k), array("title"=>'ID: ' . $k )) .
							t3lib_div::fixed_lgd_cs($pArray[$k], 30) . '</a></td>
							<td align="center">' . $pArray[$k . '_']['count'] . '</td>
							<td align="center" class="bgColor5">' . ($pArray[$k . '_']['root_max_val'] > 0 ? t3lib_iconWorks::getSpriteIcon('status-status-checked') : "&nbsp;") .
							'</td>
							<td align="center">' . ($pArray[$k . '_']['root_min_val'] == 0 ? t3lib_iconWorks::getSpriteIcon('status-status-checked') : "&nbsp;") .
							'</td>
							</tr>';
						} else {
							$lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap ><img src="clear.gif" width="1" height="1" hspace=' . ($c * 10) . ' align=top>' .
							t3lib_iconWorks::getSpriteIconForRecord('pages', t3lib_BEfunc::getRecordWSOL('pages', $k)) .
							t3lib_div::fixed_lgd_cs($pArray[$k], 30) . '</td>
							<td align="center"></td>
							<td align="center" class="bgColor5"></td>
							<td align="center"></td>
							</tr>';
					}
					$lines = $this->renderList($pArray[$k . '.'], $lines, $c + 1);
				}
			}
		}
		return $lines;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tstemplate/ts/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tstemplate/ts/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_web_ts_index');
/* @var $SOBE SC_mod_web_ts_index */
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}
$SOBE->checkExtObj();	// Checking for first level external objects

$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();

?>