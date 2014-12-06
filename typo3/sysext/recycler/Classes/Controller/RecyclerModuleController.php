<?php
namespace TYPO3\CMS\Recycler\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Module 'Recycler' for the 'recycler' extension.
 *
 * @author Julian Kleinhans <typo3@kj187.de>
 */
class RecyclerModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var string
	 */
	protected $relativePath;

	/**
	 * @var array
	 */
	protected $pageRecord = array();

	/**
	 * @var bool
	 */
	protected $isAccessibleForCurrentUser = FALSE;

	/**
	 * @var bool
	 */
	protected $allowDelete = FALSE;

	/**
	 * @var int
	 */
	protected $recordsPageLimit = 50;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	/**
	 * @var \TYPO3\CMS\Lang\LanguageService
	 */
	protected $languageService;

	/**
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'web_txrecyclerM1';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->languageService = $GLOBALS['LANG'];
		$this->languageService->includeLLFile('EXT:recycler/mod1/locallang.xlf');

		$this->backendUser = $GLOBALS['BE_USER'];

		$this->MCONF = array(
			'name' => $this->moduleName,
		);
	}

	/**
	 * Initializes the Module
	 *
	 * @return void
	 */
	public function initialize() {
		parent::init();
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->setModuleTemplate(ExtensionManagementUtility::extPath('recycler') . 'mod1/mod_template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setExtDirectStateProvider();
		$this->pageRenderer = $this->doc->getPageRenderer();
		$this->relativePath = ExtensionManagementUtility::extRelPath('recycler');
		$this->pageRecord = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		$this->isAccessibleForCurrentUser = $this->id && is_array($this->pageRecord) || !$this->id && $this->isCurrentUserAdmin();
		//don't access in workspace
		if ($this->backendUser->workspace !== 0) {
			$this->isAccessibleForCurrentUser = FALSE;
		}
		//read configuration
		$modTS = $this->backendUser->getTSConfig('mod.recycler');
		if ($this->isCurrentUserAdmin()) {
			$this->allowDelete = TRUE;
		} else {
			$this->allowDelete = $modTS['properties']['allowDelete'] == '1';
		}
		if (isset($modTS['properties']['recordsPageLimit']) && (int)$modTS['properties']['recordsPageLimit'] > 0) {
			$this->recordsPageLimit = (int)$modTS['properties']['recordsPageLimit'];
		}
	}

	/**
	 * Renders the content of the module.
	 *
	 * @return void
	 */
	public function render() {
		$this->content .= $this->doc->header($this->languageService->getLL('title'));
		$this->content .= '<p class="lead">' . $this->languageService->getLL('description') . '</p>';
		if ($this->isAccessibleForCurrentUser) {
			$this->loadHeaderData();
			// div container for renderTo
			$this->content .= '<div id="recyclerContent"></div>';
		} else {
			// If no access or if ID == zero
			$this->content .= $this->doc->spacer(10);
		}
	}

	/**
	 * Flushes the rendered content to browser.
	 *
	 * @return void
	 */
	public function flush() {
		$content = $this->doc->moduleBody($this->pageRecord, $this->getDocHeaderButtons(), $this->getTemplateMarkers());
		// Renders the module page
		$content = $this->doc->render($this->languageService->getLL('title'), $content);
		$this->content = NULL;
		$this->doc = NULL;
		echo $content;
	}

	/**
	 * Determines whether the current user is admin.
	 *
	 * @return bool Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return (bool)$this->backendUser->user['admin'];
	}

	/**
	 * Loads data in the HTML head section (e.g. JavaScript or stylesheet information).
	 *
	 * @return void
	 */
	protected function loadHeaderData() {
		// Load CSS Stylesheets:
		$this->pageRenderer->addCssFile($this->relativePath . 'res/css/customExtJs.css');
		// Load Ext JS:
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();
		// Integrate dynamic JavaScript such as configuration or lables:
		$this->pageRenderer->addInlineSettingArray('Recycler', $this->getJavaScriptConfiguration());
		$this->pageRenderer->addInlineLanguageLabelArray($this->getJavaScriptLabels());
		// Load Recycler JavaScript:
		// Load Plugins
		$uxPath = $this->doc->backPath . 'js/extjs/ux/';
		$this->pageRenderer->addJsFile($uxPath . 'Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($uxPath . 'Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
		// Load main script
		$this->pageRenderer->addJsFile($this->relativePath . 'res/js/t3_recycler.js');
	}

	/**
	 * Gets the JavaScript configuration for the Ext JS interface.
	 *
	 * @return array The JavaScript configuration
	 */
	protected function getJavaScriptConfiguration() {
		$configuration = array(
			'pagingSize' => $this->recordsPageLimit,
			'showDepthMenu' => 1,
			'startUid' => $this->id,
			'tableDefault' => 'pages',
			'renderTo' => 'recyclerContent',
			'isSSL' => GeneralUtility::getIndpEnv('TYPO3_SSL'),
			'deleteDisable' => $this->allowDelete ? 0 : 1,
			'depthSelection' => $this->getDataFromSession('depthSelection', 0),
			'tableSelection' => $this->getDataFromSession('tableSelection', 'pages'),
			'States' => $this->backendUser->uc['moduleData']['web_recycler']['States']
		);
		return $configuration;
	}

	/**
	 * Gets the labels to be used in JavaScript in the Ext JS interface.
	 *
	 * @return array The labels to be used in JavaScript
	 */
	protected function getJavaScriptLabels() {
		$coreLabels = array(
			'title' => $this->languageService->getLL('title'),
			'path' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.path'),
			'table' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.table'),
			'depth' => $this->languageService->sL('LLL:EXT:lang/locallang_mod_web_perm.xlf:Depth'),
			'depth_0' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
			'depth_1' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
			'depth_2' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
			'depth_3' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
			'depth_4' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
			'depth_infi' => $this->languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
		);
		$extensionLabels = $this->getJavaScriptLabelsFromLocallang('js.', 'label_');
		$javaScriptLabels = array_merge($coreLabels, $extensionLabels);
		return $javaScriptLabels;
	}

	/**
	 * Gets labels to be used in JavaScript fetched from the current locallang file.
	 *
	 * @param string $selectionPrefix Prefix to select the correct labels (default: 'js.')
	 * @param string $stripFromSelectionName  Sub-prefix to be removed from label names in the result (default: '')
	 * @return array Labels to be used in JavaScript of the current locallang file
	 * @todo Check, whether this method can be moved in a generic way to $GLOBALS['LANG']
	 */
	protected function getJavaScriptLabelsFromLocallang($selectionPrefix = 'js.', $stripFromSelectionName = '') {
		$extraction = array();
		$labels = array_merge((array)$GLOBALS['LOCAL_LANG']['default'], (array)$GLOBALS['LOCAL_LANG'][$this->languageService->lang]);
		// Regular expression to strip the selection prefix and possibly something from the label name:
		$labelPattern = '#^' . preg_quote($selectionPrefix, '#') . '(' . preg_quote($stripFromSelectionName, '#') . ')?#';
		// Iterate through all locallang labels:
		foreach ($labels as $label => $value) {
			if (strpos($label, $selectionPrefix) === 0) {
				$key = preg_replace($labelPattern, '', $label);
				$extraction[$key] = $value;
			}
		}
		return $extraction;
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh' => BackendUtility::cshItem('_MOD_web_func', ''),
			'shortcut' => $this->getShortcutButton(),
			'save' => ''
		);
		// SAVE button
		$buttons['save'] = '';
		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($this->backendUser->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->moduleName);
		}
		return $result;
	}

	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkers() {
		$markers = array(
			'FUNC_MENU' => $this->getFunctionMenu(),
			'CONTENT' => $this->content,
			'TITLE' => $this->languageService->getLL('title')
		);
		return $markers;
	}

	/**
	 * Gets the function menu selector for this backend module.
	 *
	 * @return string The HTML representation of the function menu selector
	 */
	protected function getFunctionMenu() {
		return BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
	}

	/**
	 * Gets data from the session of the current backend user.
	 *
	 * @param string $identifier The identifier to be used to get the data
	 * @param string $default The default date to be used if nothing was found in the session
	 * @return string The accordant data in the session of the current backend user
	 */
	protected function getDataFromSession($identifier, $default = NULL) {
		$sessionData = &$this->backendUser->uc['tx_recycler'];
		if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
			$data = $sessionData[$identifier];
		} else {
			$data = $default;
		}
		return $data;
	}
}
