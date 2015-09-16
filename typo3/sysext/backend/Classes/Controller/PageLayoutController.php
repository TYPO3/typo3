<?php
namespace TYPO3\CMS\Backend\Controller;

/*
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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\CMS\Recordlist\RecordList;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\Tree\View\ContentLayoutPagePositionMap;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Script Class for Web > Layout module
 */
class PageLayoutController {

	/**
	 * Page Id for which to make the listing
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Pointer - for browsing list of records.
	 *
	 * @var int
	 */
	public $pointer;

	/**
	 * Thumbnails or not
	 *
	 * @var string
	 */
	public $imagemode;

	/**
	 * Search-fields
	 *
	 * @var string
	 */
	public $search_field;

	/**
	 * Search-levels
	 *
	 * @var int
	 */
	public $search_levels;

	/**
	 * Show-limit
	 *
	 * @var int
	 */
	public $showLimit;

	/**
	 * Return URL
	 *
	 * @var string
	 */
	public $returnUrl;

	/**
	 * Clear-cache flag - if set, clears page cache for current id.
	 *
	 * @var bool
	 */
	public $clear_cache;

	/**
	 * PopView id - for opening a window with the page
	 *
	 * @var bool
	 */
	public $popView;

	/**
	 * QuickEdit: Variable, that tells quick edit what to show/edit etc.
	 * Format is [tablename]:[uid] with some exceptional values for both parameters (with special meanings).
	 *
	 * @var string
	 */
	public $edit_record;

	/**
	 * QuickEdit: If set, this variable tells quick edit that the last edited record had
	 * this value as UID and we should look up the new, real uid value in sys_log.
	 *
	 * @var string
	 */
	public $new_unique_uid;

	/**
	 * Page select perms clause
	 *
	 * @var string
	 */
	public $perms_clause;

	/**
	 * Module TSconfig
	 *
	 * @var array
	 */
	public $modTSconfig;

	/**
	 * Module shared TSconfig
	 *
	 * @var array
	 */
	public $modSharedTSconfig;

	/**
	 * Current ids page record
	 *
	 * @var array
	 */
	public $pageinfo;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * "Pseudo" Description -table name
	 *
	 * @var string
	 */
	public $descrTable;

	/**
	 * List of column-integers to edit. Is set from TSconfig, default is "1,0,2,3"
	 *
	 * @var string
	 */
	public $colPosList;

	/**
	 * Flag: If content can be edited or not.
	 *
	 * @var bool
	 */
	public $EDIT_CONTENT;

	/**
	 * Users permissions integer for this page.
	 *
	 * @var int
	 */
	public $CALC_PERMS;

	/**
	 * Currently selected language for editing content elements
	 *
	 * @var int
	 */
	public $current_sys_language;

	/**
	 * Module configuration
	 *
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * Menu configuration
	 *
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * Module settings (session variable)
	 *
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * Array of tables to be listed by the Web > Page module in addition to the default tables
	 *
	 * @var array
	 */
	public $externalTables = array();

	/**
	 * Module output accumulation
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Function menu temporary storage
	 *
	 * @var string
	 */
	public $topFuncMenu;

	/**
	 * List of column-integers accessible to the current BE user.
	 * Is set from TSconfig, default is $colPosList
	 *
	 * @var string
	 */
	public $activeColPosList;

	/**
	 * Markers array
	 *
	 * @var array
	 */
	protected $markers = array();

	/**
	 * @var array
	 */
	protected $eRParts = array();

	/**
	 * @var string
	 */
	protected $editSelect;

	/**
	 * @var bool
	 */
	protected $deleteButton;

	/**
	 * @var bool
	 */
	protected $undoButton;

	/**
	 * @var array
	 */
	protected $undoButtonR;

	/**
	 * @var string
	 */
	protected $R_URI;

	/**
	 * @var string
	 */
	protected $closeUrl;

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Initializing the module
	 *
	 * @return void
	 */
	public function init() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$this->getLanguageService()->includeLLFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');

		// Setting module configuration / page select clause
		$this->MCONF = $GLOBALS['MCONF'];
		$this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
		// Get session data
		$sessionData = $this->getBackendUser()->getSessionData(RecordList::class);
		$this->search_field = !empty($sessionData['search_field']) ? $sessionData['search_field'] : '';
		// GPvars:
		$this->id = (int)GeneralUtility::_GP('id');
		$this->pointer = GeneralUtility::_GP('pointer');
		$this->imagemode = GeneralUtility::_GP('imagemode');
		$this->clear_cache = GeneralUtility::_GP('clear_cache');
		$this->popView = GeneralUtility::_GP('popView');
		$this->edit_record = GeneralUtility::_GP('edit_record');
		$this->new_unique_uid = GeneralUtility::_GP('new_unique_uid');
		$this->search_field = GeneralUtility::_GP('search_field');
		$this->search_levels = GeneralUtility::_GP('search_levels');
		$this->showLimit = GeneralUtility::_GP('showLimit');
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
		$this->externalTables = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables'];
		$sessionData['search_field'] = $this->search_field;
		// Store session data
		$this->getBackendUser()->setAndSaveSessionData(RecordList::class, $sessionData);
		// Load page info array:
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		// Initialize menu
		$this->menuConfig();
		// Setting sys language from session var:
		$this->current_sys_language = (int)$this->MOD_SETTINGS['language'];
		// CSH / Descriptions:
		$this->descrTable = '_MOD_' . $this->MCONF['name'];

		$this->markers['SEARCHBOX'] = '';
		$this->markers['BUTTONLIST_ADDITIONAL'] = '';
	}

	/**
	 * Initialize menu array
	 *
	 * @return void
	 */
	public function menuConfig() {
		$lang = $this->getLanguageService();
		// MENU-ITEMS:
		$this->MOD_MENU = array(
			'tt_content_showHidden' => '',
			'function' => array(
				0 => $lang->getLL('m_function_0'),
				1 => $lang->getLL('m_function_1'),
				2 => $lang->getLL('m_function_2')
			),
			'language' => array(
				0 => $lang->getLL('m_default')
			)
		);
		// example settings:
		// 	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['tx_myext'] =
		//		array ('default' => array(
		//				'MENU' => 'LLL:EXT:tx_myext/locallang_db.xlf:menuDefault',
		//				'fList' =>  'title,description,image',
		//				'icon' => TRUE));
		if (is_array($this->externalTables)) {
			foreach ($this->externalTables as $table => $tableSettings) {
				// delete the default settings from above
				if (is_array($this->MOD_MENU[$table])) {
					unset($this->MOD_MENU[$table]);
				}
				if (is_array($tableSettings) && count($tableSettings) > 1) {
					foreach ($tableSettings as $key => $settings) {
						$this->MOD_MENU[$table][$key] = $lang->sL($settings['MENU']);
					}
				}
			}
		}
		// First, select all pages_language_overlay records on the current page. Each represents a possibility for a language on the page. Add these to language selector.
		$res = $this->exec_languageQuery($this->id);
		while ($lRow = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
			if ($this->getBackendUser()->checkLanguageAccess($lRow['uid'])) {
				$this->MOD_MENU['language'][$lRow['uid']] = $lRow['hidden'] ? '(' . $lRow['title'] . ')' : $lRow['title'];
			}
		}
		// Find if there are ANY languages at all (and if not, remove the language option from function menu).
		$count = $this->getDatabaseConnection()->exec_SELECTcountRows('uid', 'sys_language', $this->getBackendUser()->isAdmin() ? '' : 'hidden=0');
		if (!$count) {
			unset($this->MOD_MENU['function']['2']);
		}
		// page/be_user TSconfig settings and blinding of menu-items
		$this->modSharedTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.SHARED');
		$this->modTSconfig = BackendUtility::getModTSconfig($this->id, 'mod.' . $this->MCONF['name']);
		if ($this->modTSconfig['properties']['QEisDefault']) {
			ksort($this->MOD_MENU['function']);
		}
		$this->MOD_MENU['function'] = BackendUtility::unsetMenuItems($this->modTSconfig['properties'], $this->MOD_MENU['function'], 'menu.function');
		// Remove QuickEdit as option if page type is not...
		if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'] . ',6', $this->pageinfo['doktype'])) {
			unset($this->MOD_MENU['function'][0]);
		}
		// Setting alternative default label:
		if (($this->modSharedTSconfig['properties']['defaultLanguageLabel'] || $this->modTSconfig['properties']['defaultLanguageLabel']) && isset($this->MOD_MENU['language'][0])) {
			$this->MOD_MENU['language'][0] = $this->modTSconfig['properties']['defaultLanguageLabel'] ? $this->modSharedTSconfig['properties']['defaultLanguageLabel'] : $this->modSharedTSconfig['properties']['defaultLanguageLabel'];
		}
		// Clean up settings
		$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), 'web_layout');
		// For all elements to be shown in draft workspaces & to also show hidden elements by default if user hasn't disabled the option
		if ($this->getBackendUser()->workspace != 0 || $this->MOD_SETTINGS['tt_content_showHidden'] !== '0') {
			$this->MOD_SETTINGS['tt_content_showHidden'] = 1;
		}
	}

	/**
	 * Clears page cache for the current id, $this->id
	 *
	 * @return void
	 */
	public function clearCache() {
		if ($this->clear_cache) {
			$tce = GeneralUtility::makeInstance(DataHandler::class);
			$tce->stripslashes_values = FALSE;
			$tce->start(array(), array());
			$tce->clear_cacheCmd($this->id);
		}
	}

	/**
	 * Generate the flashmessages for current pid
	 *
	 * @return string HTML content with flashmessages
	 */
	protected function getHeaderFlashMessagesForCurrentPid() {
		$content = '';
		$lang = $this->getLanguageService();
		// If page is a folder
		if ($this->pageinfo['doktype'] == PageRepository::DOKTYPE_SYSFOLDER) {
			// Access to list module
			$moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
			$moduleLoader->load($GLOBALS['TBE_MODULES']);
			$modules = $moduleLoader->modules;
			if (is_array($modules['web']['sub']['list'])) {
				$title = $lang->getLL('goToListModule');
				$message = '<p>' . $lang->getLL('goToListModuleMessage') . '</p>';
				$message .= '<a class="btn btn-info" href="javascript:top.goToModule(\'web_list\',1);">' . $lang->getLL('goToListModule') . '</a>';

				$view = GeneralUtility::makeInstance(StandaloneView::class);
				$view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
				$view->assignMultiple(array(
					'title' => $title,
					'message' => $message,
					'state' => InfoboxViewHelper::STATE_INFO
				));
				$content .= $view->render();
			}
		}
		// If content from different pid is displayed
		if ($this->pageinfo['content_from_pid']) {
			$contentPage = BackendUtility::getRecord('pages', (int)$this->pageinfo['content_from_pid']);
			$title = BackendUtility::getRecordTitle('pages', $contentPage);
			$linkToPid = $this->local_linkThisScript(array('id' => $this->pageinfo['content_from_pid']));
			$link = '<a href="' . $linkToPid . '">' . htmlspecialchars($title) . ' (PID ' . (int)$this->pageinfo['content_from_pid'] . ')</a>';
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, sprintf($lang->getLL('content_from_pid_title'), $link), '', FlashMessage::INFO);
			$content .= $flashMessage->render();
		}
		return $content;
	}

	/**
	 *
	 * @return string $title
	 */
	protected function getLocalizedPageTitle() {
		if ($this->current_sys_language > 0) {
			$overlayRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
				'title',
				'pages_language_overlay',
				'pid = ' . (int)$this->id .
						' AND sys_language_uid = ' . (int)$this->current_sys_language .
						BackendUtility::deleteClause('pages_language_overlay') .
						BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
				'',
				'',
				'',
				'sys_language_uid'
			);
			return $overlayRecord['title'];
		} else {
			return $this->pageinfo['title'];
		}
	}

	/**
	 * Injects the request object for the current request or subrequest
	 * As this controller goes only through the main() method, it is rather simple for now
	 *
	 * @param ServerRequestInterface $request the current request
	 * @param ResponseInterface $response
	 * @return ResponseInterface the response with the content
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$GLOBALS['SOBE'] = $this;
		$this->init();
		$this->clearCache();
		$this->main();
		$response->getBody()->write($this->content);
		return $response;
	}

	/**
	 * Main function.
	 * Creates some general objects and calls other functions for the main rendering of module content.
	 *
	 * @return void
	 */
	public function main() {
		$lang = $this->getLanguageService();
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$access = is_array($this->pageinfo) ? 1 : 0;
		if ($this->id && $access) {
			// Initialize permission settings:
			$this->CALC_PERMS = $this->getBackendUser()->calcPerms($this->pageinfo);
			$this->EDIT_CONTENT = $this->pageIsNotLockedForEditors();

			// Start document template object:
			$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
			$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/db_layout.html');

			// override the default jumpToUrl
			$this->doc->JScodeArray['jumpToUrl'] = '
				function jumpToUrl(URL,formEl) {
					if (document.editform && TBE_EDITOR.isFormChanged)	{	// Check if the function exists... (works in all browsers?)
						if (!TBE_EDITOR.isFormChanged()) {
							window.location.href = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else {
						window.location.href = URL;
					}
				}
';

			$this->doc->JScode .= $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
				if (top.fsMod) top.fsMod.navFrameHighlightedID["web"] = "pages' . (int)$this->id . '_"+top.fsMod.currentBank; ' . (int)$this->id . ';
			' . ($this->popView ? BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id)) : '') . '

				function deleteRecord(table,id,url) {	//
					if (confirm(' . GeneralUtility::quoteJSvalue($lang->getLL('deleteWarning')) . ')) {
						window.location.href = ' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('tce_db') . '&cmd[') . '+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC=' . $this->getBackendUser()->veriCode() . '&prErr=1&uPT=1";
					}
					return false;
				}
			');
			// Setting doc-header
			$this->doc->form = '<form action="' . htmlspecialchars(
				BackendUtility::getModuleUrl(
					'web_layout', array('id' => $this->id, 'imagemode' =>  $this->imagemode)
				)) . '" method="post">';
			// Creating the top function menu:
			$this->topFuncMenu = BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'], '', '');
			$languageMenu = count($this->MOD_MENU['language']) > 1 ? $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.language', TRUE) . BackendUtility::getFuncMenu($this->id, 'SET[language]', $this->current_sys_language, $this->MOD_MENU['language'], '', '') : '';
			// Find backend layout / coumns
			$backendLayout = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getSelectedBackendLayout', $this->id, $this);
			if (!empty($backendLayout['__colPosList'])) {
				$this->colPosList = implode(',', $backendLayout['__colPosList']);
			}
			// Removing duplicates, if any
			$this->colPosList = array_unique(GeneralUtility::intExplode(',', $this->colPosList));
			// Accessible columns
			if (isset($this->modSharedTSconfig['properties']['colPos_list']) && trim($this->modSharedTSconfig['properties']['colPos_list']) !== '') {
				$this->activeColPosList = array_unique(GeneralUtility::intExplode(',', trim($this->modSharedTSconfig['properties']['colPos_list'])));
				// Match with the list which is present in the colPosList for the current page
				if (!empty($this->colPosList) && !empty($this->activeColPosList)) {
					$this->activeColPosList = array_unique(array_intersect(
						$this->activeColPosList,
						$this->colPosList
					));
				}
			} else {
				$this->activeColPosList = $this->colPosList;
			}
			$this->activeColPosList = implode(',', $this->activeColPosList);
			$this->colPosList = implode(',', $this->colPosList);

			$body = '';
			$body .= $this->getHeaderFlashMessagesForCurrentPid();
			// Render the primary module content:
			if ($this->MOD_SETTINGS['function'] == 0) {
				// QuickEdit
				$body .= $this->renderQuickEdit();
			} else {
				// Page title
				$body .= $this->doc->header($this->getLocalizedPageTitle());
				// All other listings
				$body .= $this->renderListContent();
			}
			// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons($this->MOD_SETTINGS['function'] == 0 ? 'quickEdit' : '');
			$this->markers['CSH'] = $docHeaderButtons['csh'];
			$this->markers['TOP_FUNCTION_MENU'] = $this->topFuncMenu . $this->editSelect;
			$this->markers['LANGSELECTOR'] = $languageMenu;
			$this->markers['CONTENT'] = $body;
			// Build the <body> for the module
			$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $this->markers);
			// Renders the module page
			$this->content = $this->doc->render($lang->getLL('title'), $this->content);
		} else {
			// If no access or id value, create empty document:
			$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
			$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/db_layout.html');
			$this->doc->JScode = $this->doc->wrapScriptTags('
				if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';
			');

			$body = $this->doc->header($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);

			$title = $lang->getLL('clickAPage_header');
			$message = $lang->getLL('clickAPage_content');

			$view = GeneralUtility::makeInstance(StandaloneView::class);
			$view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/InfoBox.html'));
			$view->assignMultiple(array(
				'title' => $title,
				'message' => $message,
				'state' => InfoboxViewHelper::STATE_INFO
			));
			$body .= $view->render();

			// Setting up the buttons and markers for docheader
			$docHeaderButtons = array(
				'view' => '',
				'history_page' => '',
				'new_content' => '',
				'move_page' => '',
				'move_record' => '',
				'new_page' => '',
				'edit_page' => '',
				'csh' => '',
				'shortcut' => '',
				'cache' => '',
				'savedok' => '',
				'savedokshow' => '',
				'closedok' => '',
				'deletedok' => '',
				'undo' => '',
				'history_record' => '',
				'edit_language' => ''
			);
			$this->markers['CSH'] = '';
			$this->markers['TOP_FUNCTION_MENU'] = '';
			$this->markers['LANGSELECTOR'] = '';
			$this->markers['CONTENT'] = $body;
			$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $this->markers);
			// Renders the module page
			$this->content = $this->doc->render($lang->getLL('title'), $this->content);
		}
	}

	/**
	 * Rendering the quick-edit view.
	 *
	 * @return string
	 */
	public function renderQuickEdit() {
		$databaseConnection = $this->getDatabaseConnection();
		$beUser = $this->getBackendUser();
		$lang = $this->getLanguageService();
		// Alternative template
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/db_layout_quickedit.html');
		// Alternative form tag; Quick Edit submits its content to tce_db.php.
		$this->doc->form = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db', ['prErr' => 1, 'uPT' => 1])) . '" method="post" enctype="multipart/form-data" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		// Set the edit_record value for internal use in this function:
		$edit_record = $this->edit_record;
		// If a command to edit all records in a column is issue, then select all those elements, and redirect to FormEngine
		if (substr($edit_record, 0, 9) == '_EDIT_COL') {
			$res = $databaseConnection->exec_SELECTquery('*', 'tt_content', 'pid=' . (int)$this->id . ' AND colPos=' . (int)substr($edit_record, 10) . ' AND sys_language_uid=' . (int)$this->current_sys_language . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : BackendUtility::BEenableFields('tt_content')) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content'), '', 'sorting');
			$idListA = array();
			while ($cRow = $databaseConnection->sql_fetch_assoc($res)) {
				$idListA[] = $cRow['uid'];
			}
			$url = BackendUtility::getModuleUrl('record_edit', array(
				'edit[tt_content][' . implode(',', $idListA) . ']' => 'edit',
				'returnUrl' => $this->local_linkThisScript(array('edit_record' => ''))
			));
			HttpUtility::redirect($url);
		}
		// If the former record edited was the creation of a NEW record, this will look up the created records uid:
		if ($this->new_unique_uid) {
			$res = $databaseConnection->exec_SELECTquery('*', 'sys_log', 'userid=' . (int)$beUser->user['uid'] . ' AND NEWid=' . $databaseConnection->fullQuoteStr($this->new_unique_uid, 'sys_log'));
			$sys_log_row = $databaseConnection->sql_fetch_assoc($res);
			if (is_array($sys_log_row)) {
				$edit_record = $sys_log_row['tablename'] . ':' . $sys_log_row['recuid'];
			}
		}
		// Creating the selector box, allowing the user to select which element to edit:
		$opt = array();
		$is_selected = 0;
		$languageOverlayRecord = '';
		if ($this->current_sys_language) {
			list($languageOverlayRecord) = BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $this->id, 'AND sys_language_uid=' . (int)$this->current_sys_language);
		}
		if (is_array($languageOverlayRecord)) {
			$inValue = 'pages_language_overlay:' . $languageOverlayRecord['uid'];
			$is_selected += (int)$edit_record == $inValue;
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $lang->getLL('editLanguageHeader', TRUE) . ' ]</option>';
		} else {
			$inValue = 'pages:' . $this->id;
			$is_selected += (int)$edit_record == $inValue;
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $lang->getLL('editPageProperties', TRUE) . ' ]</option>';
		}
		// Selecting all content elements from this language and allowed colPos:
		$whereClause = 'pid=' . (int)$this->id . ' AND sys_language_uid=' . (int)$this->current_sys_language . ' AND colPos IN (' . $this->colPosList . ')' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? '' : BackendUtility::BEenableFields('tt_content')) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content');
		if (!$this->getBackendUser()->user['admin']) {
			$whereClause .= ' AND editlock = 0';
		}
		$res = $databaseConnection->exec_SELECTquery('*', 'tt_content', $whereClause, '', 'colPos,sorting');
		$colPos = NULL;
		$first = 1;
		// Page is the pid if no record to put this after.
		$prev = $this->id;
		while ($cRow = $databaseConnection->sql_fetch_assoc($res)) {
			BackendUtility::workspaceOL('tt_content', $cRow);
			if (is_array($cRow)) {
				if ($first) {
					if (!$edit_record) {
						$edit_record = 'tt_content:' . $cRow['uid'];
					}
					$first = 0;
				}
				if (!isset($colPos) || $cRow['colPos'] !== $colPos) {
					$colPos = $cRow['colPos'];
					$opt[] = '<option value=""></option>';
					$opt[] = '<option value="_EDIT_COL:' . $colPos . '">__' . $lang->sL(BackendUtility::getLabelFromItemlist('tt_content', 'colPos', $colPos), TRUE) . ':__</option>';
				}
				$inValue = 'tt_content:' . $cRow['uid'];
				$is_selected += (int)$edit_record == $inValue;
				$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(($cRow['header'] ? $cRow['header'] : '[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title') . '] ' . strip_tags($cRow['bodytext'])), $beUser->uc['titleLen'])) . '</option>';
				$prev = -$cRow['uid'];
			}
		}
		// If edit_record is not set (meaning, no content elements was found for this language) we simply set it to create a new element:
		if (!$edit_record) {
			$edit_record = 'tt_content:new/' . $prev . '/' . $colPos;
			$inValue = 'tt_content:new/' . $prev . '/' . $colPos;
			$is_selected += (int)$edit_record == $inValue;
			$opt[] = '<option value="' . $inValue . '"' . ($edit_record == $inValue ? ' selected="selected"' : '') . '>[ ' . $lang->getLL('newLabel', 1) . ' ]</option>';
		}
		// If none is yet selected...
		if (!$is_selected) {
			$opt[] = '<option value=""></option>';
			$opt[] = '<option value="' . $edit_record . '"  selected="selected">[ ' . $lang->getLL('newLabel', TRUE) . ' ]</option>';
		}
		// Splitting the edit-record cmd value into table/uid:
		$this->eRParts = explode(':', $edit_record);
		$tableName = $this->eRParts[0];
		// Delete-button flag?
		$this->deleteButton = MathUtility::canBeInterpretedAsInteger($this->eRParts[1]) && $edit_record && ($tableName !== 'pages' && $this->EDIT_CONTENT || $tableName === 'pages' && $this->CALC_PERMS & Permission::PAGE_DELETE);
		// If undo-button should be rendered (depends on available items in sys_history)
		$this->undoButton = FALSE;
		$undoRes = $databaseConnection->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $databaseConnection->fullQuoteStr($tableName, 'sys_history') . ' AND recuid=' . (int)$this->eRParts[1], '', 'tstamp DESC', '1');
		if ($this->undoButtonR = $databaseConnection->sql_fetch_assoc($undoRes)) {
			$this->undoButton = TRUE;
		}
		// Setting up the Return URL for coming back to THIS script (if links take the user to another script)
		$R_URL_parts = parse_url(GeneralUtility::getIndpEnv('REQUEST_URI'));
		$R_URL_getvars = GeneralUtility::_GET();
		unset($R_URL_getvars['popView']);
		unset($R_URL_getvars['new_unique_uid']);
		$R_URL_getvars['edit_record'] = $edit_record;
		$this->R_URI = $R_URL_parts['path'] . '?' . GeneralUtility::implodeArrayForUrl('', $R_URL_getvars);
		// Setting close url/return url for exiting this script:
		// Goes to 'Columns' view if close is pressed (default)
		$this->closeUrl = $this->local_linkThisScript(array('SET' => array('function' => 1)));
		if ($this->returnUrl) {
			$this->closeUrl = $this->returnUrl;
		}
		// Return-url for JavaScript:
		$retUrlStr = $this->returnUrl ? '+\'&returnUrl=\'+' . GeneralUtility::quoteJSvalue(rawurlencode($this->returnUrl)) : '';
		// Drawing the edit record selectbox
		$this->editSelect = '<select name="edit_record" onchange="' . htmlspecialchars('jumpToUrl(' . GeneralUtility::quoteJSvalue(
			BackendUtility::getModuleUrl('web_layout') . '&id=' . $this->id . '&edit_record='
		) . '+escape(this.options[this.selectedIndex].value)' . $retUrlStr . ',this);') . '">' . implode('', $opt) . '</select>';

		// Creating editing form:
		$content = '';

		if ($edit_record) {
			// Splitting uid parts for special features, if new:
			list($uidVal, $neighborRecordUid, $ex_colPos) = explode('/', $this->eRParts[1]);

			if ($uidVal === 'new') {
				$command = 'new';
				// Page id of this new record
				$theUid = $this->id;
				if ($neighborRecordUid) {
					$theUid = $neighborRecordUid;
				}
			} else {
				$command = 'edit';
				$theUid = $uidVal;
				// Convert $uidVal to workspace version if any:
				$draftRecord = BackendUtility::getWorkspaceVersionOfRecord($beUser->workspace, $tableName, $theUid, 'uid');
				if ($draftRecord) {
					$theUid = $draftRecord['uid'];
				}
			}

			// @todo: Hack because DatabaseInitializeNewRow reads from _GP directly
			$GLOBALS['_GET']['defVals'][$tableName] = array(
				'colPos' => (int)$ex_colPos,
				'sys_language_uid' => (int)$this->current_sys_language
			);

			/** @var TcaDatabaseRecord $formDataGroup */
			$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
			/** @var FormDataCompiler $formDataCompiler */
			$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
			/** @var NodeFactory $nodeFactory */
			$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

			try {
				$formDataCompilerInput = [
					'tableName' => $tableName,
					'vanillaUid' => (int)$theUid,
					'command' => $command,
				];
				$formData = $formDataCompiler->compile($formDataCompilerInput);

				if ($command !== 'new') {
					BackendUtility::lockRecords($tableName, $formData['databaseRow']['uid'], $tableName === 'tt_content' ? $formData['databaseRow']['pid'] : 0);
				}

				$formData['renderType'] = 'outerWrapContainer';
				$formResult = $nodeFactory->create($formData)->render();

				$panel = $formResult['html'];
				$formResult['html'] = '';

				/** @var FormResultCompiler $formResultCompiler */
				$formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
				$formResultCompiler->mergeResult($formResult);

				$row = $formData['databaseRow'];
				$new_unique_uid = '';
				if ($command === 'new') {
					$new_unique_uid = $row['uid'];
				}

				// Add hidden fields:
				if ($uidVal == 'new') {
					$panel .= '<input type="hidden" name="data[' . $tableName . '][' . $row['uid'] . '][pid]" value="' . $row['pid'] . '" />';
				}
				$panel .= '
					<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />
					<input type="hidden" name="edit_record" value="' . $edit_record . '" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars(($uidVal == 'new' ? BackendUtility::getModuleUrl(
						'web_layout',
						array(
							'id' => $this->id,
							'new_unique_uid' => $new_unique_uid,
							'returnUrl' => $this->returnUrl
						)
					) : $this->R_URI)) . '" />
					';
				// Add JavaScript as needed around the form:
				$panel = $formResultCompiler->JStop() . $panel . $formResultCompiler->printNeededJSFunctions();
				$content = $this->doc->section('', $panel);

				// Display "is-locked" message:
				if ($command === 'edit') {
					$lockInfo = BackendUtility::isRecordLocked($tableName, $formData['databaseRow']['uid']);
					if ($lockInfo) {
						/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
						$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, htmlspecialchars($lockInfo['msg']), '', FlashMessage::WARNING);
						/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
						$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
						/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
						$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
						$defaultFlashMessageQueue->enqueue($flashMessage);
					}
				}
			} catch (AccessDeniedException $e) {
				// If no edit access, print error message:
				$content = $this->doc->section($lang->getLL('noAccess'), $lang->getLL('noAccess_msg')
					. '<br /><br />'
					. ($beUser->errorMsg ? 'Reason: ' . $beUser->errorMsg . '<br /><br />' : ''), 0, 1
				);
			}
		} else {
			// If no edit access, print error message:
			$content = $this->doc->section($lang->getLL('noAccess'), $lang->getLL('noAccess_msg') . '<br /><br />', 0, 1);
		}

		// Bottom controls (function menus):
		$q_count = $this->getNumberOfHiddenElements();
		if ($q_count) {
			$h_func_b = '<div class="checkbox">' .
				'<label for="checkTt_content_showHidden">' .
				BackendUtility::getFuncCheck($this->id, 'SET[tt_content_showHidden]', $this->MOD_SETTINGS['tt_content_showHidden'], '', '', 'id="checkTt_content_showHidden"') .
				(!$q_count ? ('<span class="text-muted">' . $lang->getLL('hiddenCE', TRUE) . '</span>') : $lang->getLL('hiddenCE', TRUE) . ' (' . $q_count . ')') .
				'</label>' .
				'</div>';

			$content .= $this->doc->section('', $h_func_b, 0, 0);
			$content .= $this->doc->spacer(10);
		}

		// Select element matrix:
		if ($tableName === 'tt_content' && MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
			$posMap = GeneralUtility::makeInstance(ContentLayoutPagePositionMap::class);
			$posMap->cur_sys_language = $this->current_sys_language;
			$HTMLcode = '';
			// CSH:
			$HTMLcode .= BackendUtility::cshItem($this->descrTable, 'quickEdit_selElement', NULL, '|<br />');
			$HTMLcode .= $posMap->printContentElementColumns($this->id, $this->eRParts[1], $this->colPosList, $this->MOD_SETTINGS['tt_content_showHidden'], $this->R_URI);
			$content .= $this->doc->spacer(20);
			$content .= $this->doc->section($lang->getLL('CEonThisPage'), $HTMLcode, 0, 1);
			$content .= $this->doc->spacer(20);
		}

		return $content;
	}

	/**
	 * Rendering all other listings than QuickEdit
	 *
	 * @return string
	 */
	public function renderListContent() {
		/** @var $dbList \TYPO3\CMS\Backend\View\PageLayoutView */
		$dbList = GeneralUtility::makeInstance(PageLayoutView::class);
		$dbList->thumbs = $this->imagemode;
		$dbList->no_noWrap = 1;
		$dbList->descrTable = $this->descrTable;
		$this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
		$dbList->script = BackendUtility::getModuleUrl('web_layout');
		$dbList->showIcon = 0;
		$dbList->setLMargin = 0;
		$dbList->doEdit = $this->EDIT_CONTENT;
		$dbList->ext_CALC_PERMS = $this->CALC_PERMS;
		$dbList->agePrefixes = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears');
		$dbList->id = $this->id;
		$dbList->nextThree = MathUtility::forceIntegerInRange($this->modTSconfig['properties']['editFieldsAtATime'], 0, 10);
		$dbList->option_newWizard = $this->modTSconfig['properties']['disableNewContentElementWizard'] ? 0 : 1;
		$dbList->defLangBinding = $this->modTSconfig['properties']['defLangBinding'] ? 1 : 0;
		if (!$dbList->nextThree) {
			$dbList->nextThree = 1;
		}
		$dbList->externalTables = $this->externalTables;
		// Create menu for selecting a table to jump to (this is, if more than just pages/tt_content elements are found on the page!)
		// also fills $dbList->activeTables
		$dbList->getTableMenu($this->id);
		// Initialize other variables:
		$h_func = '';
		$tableOutput = array();
		$tableJSOutput = array();
		$CMcounter = 0;
		// Traverse the list of table names which has records on this page (that array is populated
		// by the $dblist object during the function getTableMenu()):
		foreach ($dbList->activeTables as $table => $value) {
			$h_func_b = '';
			if (!isset($dbList->externalTables[$table])) {
				$q_count = $this->getNumberOfHiddenElements();

				if ($q_count > 0) {
					$h_func_b =
						'<div class="checkbox">'
						. '<label for="checkTt_content_showHidden">'
						. '<input type="checkbox" id="checkTt_content_showHidden" class="checkbox" name="SET[tt_content_showHidden]" value="1" ' . ($this->MOD_SETTINGS['tt_content_showHidden'] ? 'checked="checked"' : '') . ' />'
						. $this->getLanguageService()->getLL('hiddenCE', TRUE) . ' (<span class="t3js-hidden-counter">' . $q_count . '</span>)'
						. '</label>'
						. '</div>';
				}
				// Boolean: Display up/down arrows and edit icons for tt_content records
				$dbList->tt_contentConfig['showCommands'] = 1;
				// Boolean: Display info-marks or not
				$dbList->tt_contentConfig['showInfo'] = 1;
				// Setting up the tt_content columns to show:
				if (is_array($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['items'])) {
					$colList = array();
					$tcaItems = GeneralUtility::callUserFunction(BackendLayoutView::class . '->getColPosListItemsParsed', $this->id, $this);
					foreach ($tcaItems as $temp) {
						$colList[] = $temp[1];
					}
				} else {
					// ... should be impossible that colPos has no array. But this is the fallback should it make any sense:
					$colList = array('1', '0', '2', '3');
				}
				if ($this->colPosList !== '') {
					$colList = array_intersect(GeneralUtility::intExplode(',', $this->colPosList), $colList);
				}
				// The order of the rows: Default is left(1), Normal(0), right(2), margin(3)
				$dbList->tt_contentConfig['cols'] = implode(',', $colList);
				$dbList->tt_contentConfig['activeCols'] = $this->activeColPosList;
				$dbList->tt_contentConfig['showHidden'] = $this->MOD_SETTINGS['tt_content_showHidden'];
				$dbList->tt_contentConfig['sys_language_uid'] = (int)$this->current_sys_language;
				// If the function menu is set to "Language":
				if ($this->MOD_SETTINGS['function'] == 2) {
					$dbList->tt_contentConfig['languageMode'] = 1;
					$dbList->tt_contentConfig['languageCols'] = $this->MOD_MENU['language'];
					$dbList->tt_contentConfig['languageColsPointer'] = $this->current_sys_language;
				}
			} else {
				if (isset($this->MOD_SETTINGS) && isset($this->MOD_MENU)) {
					$h_func = BackendUtility::getFuncMenu($this->id, 'SET[' . $table . ']', $this->MOD_SETTINGS[$table], $this->MOD_MENU[$table], '', '');
				} else {
					$h_func = '';
				}
			}
			// Start the dblist object:
			$dbList->itemsLimitSingleTable = 1000;
			$dbList->start($this->id, $table, $this->pointer, $this->search_field, $this->search_levels, $this->showLimit);
			$dbList->counter = $CMcounter;
			$dbList->ext_function = $this->MOD_SETTINGS['function'];
			// Render versioning selector:
			$dbList->HTMLcode .= $this->doc->getVersionSelector($this->id);
			// Generate the list of elements here:
			$dbList->generateList();
			// Adding the list content to the tableOutput variable:
			$tableOutput[$table] = ($h_func ? $h_func . '<br /><span style="width: 1px; height: 4px; display: inline-block;"></span><br />' : '') . $dbList->HTMLcode . ($h_func_b ? '<span style="width: 1px; height: 10px; display:inline-block;"></span><br />' . $h_func_b : '');
			// ... and any accumulated JavaScript goes the same way!
			$tableJSOutput[$table] = $dbList->JScode;
			// Increase global counter:
			$CMcounter += $dbList->counter;
			// Reset variables after operation:
			$dbList->HTMLcode = '';
			$dbList->JScode = '';
			$h_func = '';
		}
		// END: traverse tables
		// For Context Sensitive Menus:
		$this->doc->getContextMenuCode();
		// Init the content
		$content = '';
		// Additional header content
		$headerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook'];
		if (is_array($headerContentHook)) {
			foreach ($headerContentHook as $hook) {
				$params = array();
				$content .= GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
		// Add the content for each table we have rendered (traversing $tableOutput variable)
		foreach ($tableOutput as $table => $output) {
			$content .= $this->doc->section('', $output, TRUE, TRUE, 0, TRUE);
			$content .= $this->doc->sectionEnd();
		}
		// Making search form:
		if (!$this->modTSconfig['properties']['disableSearchBox'] && !empty($tableOutput)) {
			$this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ToggleSearchToolbox');
			$this->markers['BUTTONLIST_ADDITIONAL'] = '<a href="#" class="t3js-toggle-search-toolbox" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchIcon', TRUE) . '">' . $this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL) . '</a>';
			$this->markers['SEARCHBOX'] = $dbList->getSearchBox(0);
		}
		// Additional footer content
		$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'];
		if (is_array($footerContentHook)) {
			foreach ($footerContentHook as $hook) {
				$params = array();
				$content .= GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
		return $content;
	}

	/**
	 * Print accumulated content of module
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		echo $this->content;
	}

	/***************************
	 *
	 * Sub-content functions, rendering specific parts of the module content.
	 *
	 ***************************/
	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @param string $function Identifier for function of module
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons($function = '') {
		$lang = $this->getLanguageService();
		$buttons = array(
			'view' => '',
			'history_page' => '',
			'new_content' => '',
			'move_page' => '',
			'move_record' => '',
			'new_page' => '',
			'edit_page' => '',
			'edit_language' => '',
			'csh' => '',
			'shortcut' => '',
			'cache' => '',
			'savedok' => '',
			'save_close' => '',
			'savedokshow' => '',
			'closedok' => '',
			'deletedok' => '',
			'undo' => '',
			'history_record' => ''
		);
		// View page
		if (!VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
			$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::viewOnClick($this->pageinfo['uid'], '', BackendUtility::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL) . '</a>';
		}
		// Shortcut
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}
		// Cache
		if (!$this->modTSconfig['properties']['disableAdvanced']) {
			$buttons['cache'] = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('web_layout', array('id' => $this->pageinfo['uid'], 'clear_cache' => '1'))) . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.clear_cache', TRUE) . '">' . $this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL)->render() . '</a>';
		}
		if (!$this->modTSconfig['properties']['disableIconToolbar']) {
			// Move record
			if (MathUtility::canBeInterpretedAsInteger($this->eRParts[1])) {
				$urlParameters = [
					'table' => $this->eRParts[0],
					'uid' => $this->eRParts[1],
					'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
				];
				$buttons['move_record'] = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('move_element', $urlParameters)) . '" title="' . $lang->getLL('move_' . ($this->eRParts[0] == 'tt_content' ? 'record' : 'page'), TRUE) . '">' . $this->iconFactory->getIcon('actions-' . ($this->eRParts[0] == 'tt_content' ? 'document' : 'page') . '-move', Icon::SIZE_SMALL)->render() . '</a>';
			}

			// Edit page properties and page language overlay icons
			if ($this->pageIsNotLockedForEditors()) {

				// Edit localized page_language_overlay only when one specific language is selected
				if ($this->MOD_SETTINGS['function'] == 1 && $this->current_sys_language > 0) {
					$overlayRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
						'uid',
						'pages_language_overlay',
						'pid = ' . (int)$this->id . ' ' .
						'AND sys_language_uid = ' . (int)$this->current_sys_language .
						BackendUtility::deleteClause('pages_language_overlay') .
						BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
						'',
						'',
						'',
						'sys_language_uid'
					);

					$editLanguageOnClick = htmlspecialchars(BackendUtility::editOnClick('&edit[pages_language_overlay][' . $overlayRecord['uid'] . ']=edit'));
					$buttons['edit_language'] = '<a href="#" ' .
						'onclick="' . $editLanguageOnClick . '"' .
						'title="' . $lang->getLL('editPageLanguageOverlayProperties', TRUE) . '">' .
						$this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL) .
						'</a>';
				}


				// Edit page properties
				$editPageOnClick = htmlspecialchars(BackendUtility::editOnClick('&edit[pages][' . $this->id . ']=edit'));
				$buttons['edit_page'] = '<a href="#" onclick="' . $editPageOnClick . '" title="' . $lang->getLL('editPageProperties', TRUE) . '">'
					. $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)
					. '</a>';
			}

			// Add CSH (Context Sensitive Help) icon to tool bar
			if ($function == 'quickEdit') {
				$buttons['csh'] = BackendUtility::cshItem($this->descrTable, 'quickEdit');
			} else {
				$buttons['csh'] = BackendUtility::cshItem($this->descrTable, 'columns_' . $this->MOD_SETTINGS['function']);
			}
			if ($function == 'quickEdit') {
				// Save record
				$buttons['savedok'] = '<button class="c-inputButton" name="_savedok_x" value="1" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '">'
					. $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)
					. '</button>';
				// Save and close
				$buttons['save_close'] = '<button class="c-inputButton" name="_saveandclosedok_x" value="1" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '">'
					. $this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL)
					. '</button>';
				// Save record and show page
				$buttons['savedokshow'] = '<a href="#" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow', TRUE) . '">'
					. $this->iconFactory->getIcon('actions-document-save-view', Icon::SIZE_SMALL)
					. '</a>';
				// Close record
				$buttons['closedok'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(' . GeneralUtility::quoteJSvalue($this->closeUrl) . '); return false;') . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '">'
					. $this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL)
					. '</a>';
				// Delete record
				if ($this->deleteButton) {
					$buttons['deletedok'] = '<a href="#" onclick="' . htmlspecialchars('return deleteRecord(' . GeneralUtility::quoteJSvalue($this->eRParts[0]) . ',' . GeneralUtility::quoteJSvalue($this->eRParts[1]) . ',' . GeneralUtility::quoteJSvalue(GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?id=' . $this->id) . ');') . '" title="' . $lang->getLL('deleteItem', TRUE) . '">'
						. $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)
						. '</a>';
				}
				if ($this->undoButton) {
					// Undo button
					$buttons['undo'] = '<a href="#"
						onclick="' . htmlspecialchars('window.location.href=' .
							GeneralUtility::quoteJSvalue(
								BackendUtility::getModuleUrl(
									'record_history',
									array(
										'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
										'revert' => 'ALL_FIELDS',
										'sumUp' => -1,
										'returnUrl' => $this->R_URI,
									)
								)
							) . '; return false;') . '"
						title="' . htmlspecialchars(sprintf($lang->getLL('undoLastChange'), BackendUtility::calcAge($GLOBALS['EXEC_TIME'] - $this->undoButtonR['tstamp'], $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')))) . '">' . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL) . '</a>';
					// History button
					$buttons['history_record'] = '<a href="#"
						onclick="' . htmlspecialchars('jumpToUrl(' .
							GeneralUtility::quoteJSvalue(
								BackendUtility::getModuleUrl(
									'record_history',
									array(
										'element' => $this->eRParts[0] . ':' . $this->eRParts[1],
										'returnUrl' => $this->R_URI,
									)
								) . '#latest'
							) . ');return false;') . '"
						title="' . $lang->getLL('recordHistory', TRUE) . '">' . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL) . '</a>';
				}
			}
		}
		return $buttons;
	}

	/*******************************
	 *
	 * Other functions
	 *
	 ******************************/
	/**
	 * Returns the number of hidden elements (including those hidden by start/end times)
	 * on the current page (for the current sys_language)
	 *
	 * @return int
	 */
	public function getNumberOfHiddenElements() {
		return $this->getDatabaseConnection()->exec_SELECTcountRows(
			'uid',
			'tt_content',
			'pid=' . (int)$this->id . ' AND sys_language_uid=' . (int)$this->current_sys_language . BackendUtility::BEenableFields('tt_content', 1) . BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content')
		);
	}

	/**
	 * Returns URL to the current script.
	 * In particular the "popView" and "new_unique_uid" Get vars are unset.
	 *
	 * @param array $params Parameters array, merged with global GET vars.
	 * @return string URL
	 */
	public function local_linkThisScript($params) {
		$params['popView'] = '';
		$params['new_unique_uid'] = '';
		return GeneralUtility::linkThisScript($params);
	}

	/**
	 * Returns a SQL query for selecting sys_language records.
	 *
	 * @param int $id Page id: If zero, the query will select all sys_language records from root level which are NOT hidden. If set to another value, the query will select all sys_language records that has a pages_language_overlay record on that page (and is not hidden, unless you are admin user)
	 * @return string Return query string.
	 */
	public function exec_languageQuery($id) {
		if ($id) {
			$exQ = BackendUtility::deleteClause('pages_language_overlay') .
				($this->getBackendUser()->isAdmin() ? '' : ' AND sys_language.hidden=0');
			return $this->getDatabaseConnection()->exec_SELECTquery(
				'sys_language.*',
				'pages_language_overlay,sys_language',
				'pages_language_overlay.sys_language_uid=sys_language.uid AND pages_language_overlay.pid=' . (int)$id . $exQ .
					BackendUtility::versioningPlaceholderClause('pages_language_overlay'),
				'pages_language_overlay.sys_language_uid,sys_language.uid,sys_language.pid,sys_language.tstamp,sys_language.hidden,sys_language.title,sys_language.language_isocode,sys_language.static_lang_isocode,sys_language.flag',
				'sys_language.title'
			);
		} else {
			return $this->getDatabaseConnection()->exec_SELECTquery(
				'sys_language.*',
				'sys_language',
				'sys_language.hidden=0',
				'',
				'sys_language.title'
			);
		}
	}

	/**
	 * Check the editlock access
	 *
	 * @return bool
	 */
	public function pageIsNotLockedForEditors() {
		return $this->getBackendUser()->isAdmin() || !($this->CALC_PERMS & Permission::PAGE_EDIT && $this->pageinfo['editlock']);
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns the database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns current PageRenderer
	 *
	 * @return PageRenderer
	 */
	protected function getPageRenderer() {
		return GeneralUtility::makeInstance(PageRenderer::class);
	}
}
