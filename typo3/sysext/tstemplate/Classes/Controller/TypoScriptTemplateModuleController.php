<?php
namespace TYPO3\CMS\Tstemplate\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Module: TypoScript Tools
 *
 * $TYPO3_CONF_VARS["MODS"]["web_ts"]["onlineResourceDir"]  = Directory of default resources. Eg. "fileadmin/res/" or so.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TypoScriptTemplateModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var string
	 */
	public $perms_clause;

	/**
	 * @var string
	 */
	public $e;

	/**
	 * @var string
	 */
	public $sObj;

	/**
	 * @var string
	 */
	public $edit;

	/**
	 * @var string
	 */
	public $textExtensions = 'html,htm,txt,css,tmpl,inc,js';

	/**
	 * @var string
	 */
	public $modMenu_type = '';

	/**
	 * @var string
	 */
	public $modMenu_dontValidateList = '';

	/**
	 * @var string
	 */
	public $modMenu_setDefaultList = '';

	/**
	 * @var array
	 */
	public $pageinfo = array();

	/**
	 * @var bool
	 */
	public $access = FALSE;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:tstemplate/ts/locallang.xlf');
		$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], TRUE);
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->id = (int)GeneralUtility::_GP('id');
		$this->e = GeneralUtility::_GP('e');
		$this->sObj = GeneralUtility::_GP('sObj');
		$this->edit = GeneralUtility::_GP('edit');
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
	}

	/**
	 * Clear cache
	 *
	 * @return void
	 */
	public function clearCache() {
		if (GeneralUtility::_GP('clear_all_cache')) {
			$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce->stripslashes_values = 0;
			$tce->start(array(), array());
			$tce->clear_cacheCmd('all');
		}
	}

	/**
	 * Main
	 *
	 * @return void
	 */
	public function main() {
		// Template markers
		$markers = array(
			'CSH' => '',
			'FUNC_MENU' => '',
			'CONTENT' => ''
		);

		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$this->access = is_array($this->pageinfo);

		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate doc */
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:tstemplate/Resources/Private/Templates/tstemplate.html');
		$this->doc->addStyleSheet('module', 'sysext/tstemplate/Resources/Public/Styles/styles.css');

		if ($this->id && $this->access) {
			$urlParameters = array(
				'id' => $this->id,
				'template' => 'all'
			);
			$aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
			$this->doc->form = '<form action="' . htmlspecialchars($aHref) . '" method="post" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '" name="editForm">';

			// JavaScript
			$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			function uFormUrl(aname) {
				document.forms[0].action = ' . GeneralUtility::quoteJSvalue(($aHref . '#'), TRUE) . '+aname;
			}
			function brPoint(lnumber,t) {
				window.location.href = ' . GeneralUtility::quoteJSvalue(($aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateObjectBrowserModuleFunctionController&SET[ts_browser_type]='), TRUE) . '+(t?"setup":"const")+"&breakPointLN="+lnumber;
				return false;
			}
		</script>
		';
			$this->doc->postCode = '
		<script language="javascript" type="text/javascript">
			if (top.fsMod) top.fsMod.recentIds["web"] = ' . $this->id . ';
		</script>
		';
			$this->doc->inDocStylesArray[] = '
				TABLE#typo3-objectBrowser { width: 100%; margin-bottom: 24px; }
				TABLE#typo3-objectBrowser A { text-decoration: none; }
				TABLE#typo3-objectBrowser .comment { color: maroon; font-weight: bold; }
				.ts-typoscript { width: 100%; }
				.tsob-menu-row2 {margin-top: 10px;}
				.tsob-search-submit {margin-left: 3px; margin-right: 3px;}
				.tst-analyzer-options { margin:5px 0; }
			';
			// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
			// Build the modulle content
			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('moduleTitle'));
			$this->extObjContent();
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['FUNC_MENU'] = BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
			$markers['CONTENT'] = $this->content;
		} else {
			// Template pages:
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pages.uid, count(*) AS count, max(sys_template.root) AS root_max_val, min(sys_template.root) AS root_min_val', 'pages,sys_template', 'pages.uid=sys_template.pid' . BackendUtility::deleteClause('pages') . BackendUtility::versioningPlaceholderClause('pages') . BackendUtility::deleteClause('sys_template') . BackendUtility::versioningPlaceholderClause('sys_template'), 'pages.uid');
			$templateArray = array();
			$pArray = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->setInPageArray($pArray, BackendUtility::BEgetRootLine($row['uid'], 'AND 1=1'), $row);
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);

			$table = '<table class="t3-table" id="ts-overview">' .
					'<thead>' .
					'<tr>' .
					'<th>' . $GLOBALS['LANG']->getLL('pageName') . '</th>' .
					'<th>' . $GLOBALS['LANG']->getLL('templates') . '</th>' .
					'<th>' . $GLOBALS['LANG']->getLL('isRoot') . '</th>' .
					'<th>' . $GLOBALS['LANG']->getLL('isExt') . '</th>' .
					'</tr>' .
					'</thead>' .
					'<tbody>' . implode('', $this->renderList($pArray)) . '</tbody>' .
					'</table>';

			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('moduleTitle'));
			$this->content .= $this->doc->section('', '<p class="lead">' . $GLOBALS['LANG']->getLL('overview') . '</p>' . $table);

			// RENDER LIST of pages with templates, END
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CONTENT'] = $this->content;
		}

		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render('Template Tools', $this->content);
	}

	/**
	 * Print content
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'back' => '',
			'close' => '',
			'new' => '',
			'save' => '',
			'save_close' => '',
			'view' => '',
			'shortcut' => ''
		);

		if ($this->id && $this->access) {
			// View page
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], $GLOBALS['BACK_PATH'], BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-view') . '</a>';
			if ($this->extClassConf['name'] == 'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController') {
				// NEW button
				$urlParameters = array(
					'id' => $this->id,
					'template' => 'all',
					'createExtension' => 'new'
				);
				$buttons['new'] = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('web_ts', $urlParameters)) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:db_new.php.pagetitle', TRUE) . '">' . IconUtility::getSpriteIcon('actions-document-new') . '</a>';
				if (!empty($this->e) && !GeneralUtility::_POST('saveclose')) {
					// no NEW-button while edit
					$buttons['new'] = '';
					// SAVE button
					$buttons['save'] = IconUtility::getSpriteIcon('actions-document-save', array(
						'html' => '<input type="image" class="c-inputButton" name="submit" src="clear.gif" ' . 'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" ' . 'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" ' . '/>'
					));
					// SAVE AND CLOSE button
					$buttons['save_close'] = IconUtility::getSpriteIcon('actions-document-save-close', array(
						'html' => '<input type="image" class="c-inputButton" name="saveclose" src="clear.gif" ' . 'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '" ' . 'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '" ' . '/>'
					));
					// CLOSE button
					$url = BackendUtility::getModuleUrl('web_ts', array('id' => $this->id));
					$buttons['close'] = '<a href="' . htmlspecialchars($url) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '">' .  IconUtility::getSpriteIcon('actions-document-close') .'</a>';
				}
			} elseif ($this->extClassConf['name'] == 'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateConstantEditorModuleFunctionController' && count($this->MOD_MENU['constant_editor_cat'])) {
				// SAVE button
				$buttons['save'] = IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" class="c-inputButton" name="submit" src="clear.gif" ' . 'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" ' . 'value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" ' . '/>'));
			} elseif ($this->extClassConf['name'] == 'TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateObjectBrowserModuleFunctionController') {
				if (!empty($this->sObj)) {
					// BACK
					$urlParameters = array(
						'id' => $this->id
					);
					$aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
					$buttons['back'] = '<a href="' . htmlspecialchars($aHref) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' . IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
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

	// OTHER FUNCTIONS:
	/**
	 * Wrap title for link in template
	 *
	 * @param string $title
	 * @param string $onlyKey
	 * @return string
	 */
	public function linkWrapTemplateTitle($title, $onlyKey = '') {
		$urlParameters = array(
			'id' => $this->id
		);
		$aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
		if ($onlyKey) {
			$title = '<a href="' . htmlspecialchars(($aHref . '&e[' . $onlyKey . ']=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController')) . '">' . htmlspecialchars($title) . '</a>';
		} else {
			$title = '<a href="' . htmlspecialchars(($aHref . '&e[constants]=1&e[config]=1&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateInformationModuleFunctionController')) . '">' . htmlspecialchars($title) . '</a>';
		}
		return $title;
	}

	/**
	 * No template
	 *
	 * @param int $newStandardTemplate
	 * @return string
	 */
	public function noTemplate($newStandardTemplate = 0) {
		// Defined global here!
		$tmpl = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);
		/** @var $tmpl \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService */
		// Do not log time-performance information
		$tmpl->tt_track = FALSE;
		$tmpl->init();
		$theOutput = '';
		$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('noTemplateDescription') . '<br />' . $GLOBALS['LANG']->getLL('createTemplateToEditConfiguration'), $GLOBALS['LANG']->getLL('noTemplate'), \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
		$theOutput .= $flashMessage->render();
		// New standard?
		if ($newStandardTemplate) {
			// Hook to change output, implemented for statictemplates
			if (isset(
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateModuleController']['newStandardTemplateView']
			)) {
				$selector = '';
				$staticsText = '';
				$reference = array(
					'selectorHtml' => &$selector,
					'staticsText' => &$staticsText
				);
				GeneralUtility::callUserFunction(
					$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateModuleController']['newStandardTemplateView'],
					$reference,
					$this
				);
				$selector = $reference['selectorHtml'];
				$staticsText = $reference['staticsText'];
			} else {
				$selector = '<input type="hidden" name="createStandard" value="" />';
				$staticsText = '';
			}
			// Extension?
			$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('newWebsite') . $staticsText, $GLOBALS['LANG']->getLL('newWebsiteDescription') . '<br /><br />' . $selector . '<input type="Submit" name="newWebsite" value="' . $GLOBALS['LANG']->getLL('newWebsiteAction') . '" />', 0, 1);
		}
		// Extension?
		$theOutput .= $this->doc->spacer(10);
		$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('extTemplate'), $GLOBALS['LANG']->getLL('extTemplateDescription') . '<br /><br />' . '<input type="submit" name="createExtension" value="' . $GLOBALS['LANG']->getLL('extTemplateAction') . '" />', 0, 1);
		// Go to first appearing...
		$first = $tmpl->ext_prevPageWithTemplate($this->id, $this->perms_clause);
		if ($first) {
			$theOutput .= $this->doc->spacer(10);
			$urlParameters = array(
				'id' => $first['uid']
			);
			$aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
			$theOutput .= $this->doc->section($GLOBALS['LANG']->getLL('goToClosest'), sprintf($GLOBALS['LANG']->getLL('goToClosestDescription') . '<br /><br />%s<strong>' . $GLOBALS['LANG']->getLL('goToClosestAction') . '</strong>%s', htmlspecialchars($first['title']), $first['uid'], '<a href="' . htmlspecialchars($aHref) . '">', '</a>'), 0, 1);
		}
		return $theOutput;
	}

	public function templateMenu() {
		// Defined global here!
		$tmpl = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);
		/** @var $tmpl \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService */
		// Do not log time-performance information
		$tmpl->tt_track = FALSE;
		$tmpl->init();
		$all = $tmpl->ext_getAllTemplates($this->id, $this->perms_clause);
		$menu = '';
		if (count($all) > 1) {
			$this->MOD_MENU['templatesOnPage'] = array();
			foreach ($all as $d) {
				$this->MOD_MENU['templatesOnPage'][$d['uid']] = $d['title'];
			}
		}
		$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name'], $this->modMenu_type, $this->modMenu_dontValidateList, $this->modMenu_setDefaultList);
		$menu = BackendUtility::getFuncMenu($this->id, 'SET[templatesOnPage]', $this->MOD_SETTINGS['templatesOnPage'], $this->MOD_MENU['templatesOnPage']);
		return $menu;
	}

	/**
	 * Create template
	 *
	 * @param int $id
	 * @param int $actTemplateId
	 * @return string
	 */
	public function createTemplate($id, $actTemplateId = 0) {
		if (GeneralUtility::_GP('createExtension') || GeneralUtility::_GP('createExtension_x')) {
			$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce->stripslashes_values = 0;
			$recData = array();
			$recData['sys_template']['NEW'] = array(
				'pid' => $actTemplateId ? -1 * $actTemplateId : $id,
				'title' => '+ext'
			);
			$tce->start($recData, array());
			$tce->process_datamap();
			return $tce->substNEWwithIDs['NEW'];
		} elseif (GeneralUtility::_GP('newWebsite')) {
			$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce->stripslashes_values = 0;
			$recData = array();
			// Hook to handle row data, implemented for statictemplates
			if (isset(
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateModuleController']['newStandardTemplateHandler']
			)) {
				$reference = array(
					'recData' => &$recData,
					'id' => $id,
				);
				GeneralUtility::callUserFunction(
					$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\\CMS\\Tstemplate\\Controller\\TypoScriptTemplateModuleController']['newStandardTemplateHandler'],
					$reference,
					$this
				);
				$recData = $reference['recData'];
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
'
				);
			}
			$tce->start($recData, array());
			$tce->process_datamap();
			$tce->clear_cacheCmd('all');
		}
	}

	// RENDER LIST of pages with templates, BEGIN
	/**
	 * Set page in array
	 *
	 * @param array $pArray
	 * @param array $rlArr
	 * @param array $row
	 * @return void
	 */
	public function setInPageArray(&$pArray, $rlArr, $row) {
		ksort($rlArr);
		reset($rlArr);
		if (!$rlArr[0]['uid']) {
			array_shift($rlArr);
		}
		$cEl = current($rlArr);
		$pArray[$cEl['uid']] = htmlspecialchars($cEl['title']);
		array_shift($rlArr);
		if (count($rlArr)) {
			if (!isset($pArray[($cEl['uid'] . '.')])) {
				$pArray[$cEl['uid'] . '.'] = array();
			}
			$this->setInPageArray($pArray[$cEl['uid'] . '.'], $rlArr, $row);
		} else {
			$pArray[$cEl['uid'] . '_'] = $row;
		}
	}

	/**
	 * Render the list
	 *
	 * @param array $pArray
	 * @param array $lines
	 * @param int $c
	 * @return array
	 */
	public function renderList($pArray, $lines = array(), $c = 0) {
		if (is_array($pArray)) {
			reset($pArray);
			static $i;
			foreach ($pArray as $k => $v) {
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($k)) {
					if (isset($pArray[$k . '_'])) {
						$lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap><img src="clear.gif" width="1" height="1" hspace=' . $c * 10 . ' align="top">' . '<a href="' . htmlspecialchars(GeneralUtility::linkThisScript(array('id' => $k))) . '">' . IconUtility::getSpriteIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $k), array('title' => ('ID: ' . $k))) . GeneralUtility::fixed_lgd_cs($pArray[$k], 30) . '</a></td>
							<td>' . $pArray[($k . '_')]['count'] . '</td>
							<td>' . ($pArray[$k . '_']['root_max_val'] > 0 ? IconUtility::getSpriteIcon('status-status-checked') : '&nbsp;') . '</td>
							<td>' . ($pArray[$k . '_']['root_min_val'] == 0 ? IconUtility::getSpriteIcon('status-status-checked') : '&nbsp;') . '</td>
							</tr>';
					} else {
						$lines[] = '<tr class="' . ($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6') . '">
							<td nowrap ><img src="clear.gif" width="1" height="1" hspace=' . $c * 10 . ' align=top>' . IconUtility::getSpriteIconForRecord('pages', BackendUtility::getRecordWSOL('pages', $k)) . GeneralUtility::fixed_lgd_cs($pArray[$k], 30) . '</td>
							<td></td>
							<td></td>
							<td></td>
							</tr>';
					}
					$lines = $this->renderList($pArray[$k . '.'], $lines, $c + 1);
				}
			}
		}
		return $lines;
	}

}
