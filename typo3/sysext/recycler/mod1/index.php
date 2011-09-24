<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Julian Kleinhans <typo3@kj187.de>
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

$LANG->includeLLFile('EXT:recycler/mod1/locallang.xml');
$BE_USER->modAccess($MCONF, 1);	// This checks permissions and exits if the users has no permission for entry.

/**
 * Module 'Recycler' for the 'recycler' extension.
 *
 * @author	Julian Kleinhans <typo3@kj187.de>
 * @package	TYPO3
 * @subpackage	tx_recycler
 */
class  tx_recycler_module1 extends t3lib_SCbase {
	/**
	 * @var template
	 */
	public $doc;

	protected $relativePath;
	protected $pageRecord = array();
	protected $isAccessibleForCurrentUser = FALSE;

	protected $allowDelete = FALSE;
	protected $recordsPageLimit = 50;

	/**
	 * @var t3lib_pageRenderer
	 */
	protected $pageRenderer;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function initialize() {
		parent::init();
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('recycler') . 'mod1/mod_template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setExtDirectStateProvider();

		$this->pageRenderer = $this->doc->getPageRenderer();

		$this->relativePath = t3lib_extMgm::extRelPath('recycler');
		$this->pageRecord = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$this->isAccessibleForCurrentUser = (
			$this->id && is_array($this->pageRecord) || !$this->id && $this->isCurrentUserAdmin()
		);

		//don't access in workspace
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$this->isAccessibleForCurrentUser = FALSE;
		}

		//read configuration
		$modTS = $GLOBALS['BE_USER']->getTSConfig('mod.recycler');
		if ($this->isCurrentUserAdmin()) {
			$this->allowDelete = TRUE;
		} else {
			$this->allowDelete = ($modTS['properties']['allowDelete'] == '1');
		}
		if (isset($modTS['properties']['recordsPageLimit']) && intval($modTS['properties']['recordsPageLimit']) > 0) {
			$this->recordsPageLimit = intval($modTS['properties']['recordsPageLimit']);
		}
	}

	/**
	 * Renders the content of the module.
	 *
	 * @return	void
	 */
	public function render() {
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->section('', $GLOBALS['LANG']->getLL('description'));
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
	 * @return	void
	 */
	public function flush() {
		$content = $this->doc->moduleBody(
			$this->pageRecord,
			$this->getDocHeaderButtons(),
			$this->getTemplateMarkers()
		);
			// Renders the module page
		$content = $this->doc->render(
			$GLOBALS['LANG']->getLL('title'),
			$content
		);

		$this->content = NULL;
		$this->doc = NULL;

		echo $content;
	}

	/**
	 * Determines whether the current user is admin.
	 *
	 * @return	boolean		Whether the current user is admin
	 */
	protected function isCurrentUserAdmin() {
		return (bool)$GLOBALS['BE_USER']->user['admin'];
	}

	/**
	 * Loads data in the HTML head section (e.g. JavaScript or stylesheet information).
	 *
	 * @return	void
	 */
	protected function loadHeaderData() {
			// Load CSS Stylesheets:
		$this->pageRenderer->addCssFile($this->relativePath . 'res/css/customExtJs.css');

			// Load Ext JS:
		$this->pageRenderer->loadExtJS();
		$this->pageRenderer->enableExtJSQuickTips();

			// Integrate dynamic JavaScript such as configuration or lables:
		$this->pageRenderer->addInlineSettingArray(
			'Recycler',
			$this->getJavaScriptConfiguration()
		);
		$this->pageRenderer->addInlineLanguageLabelArray(
			$this->getJavaScriptLabels()
		);


			// Load Recycler JavaScript:

			// Load Plugins
		$uxPath = $this->doc->backpath . '../t3lib/js/extjs/ux/';
		$this->pageRenderer->addJsFile($uxPath . 'Ext.grid.RowExpander.js');
		$this->pageRenderer->addJsFile($uxPath . 'Ext.app.SearchField.js');
		$this->pageRenderer->addJsFile($uxPath . 'Ext.ux.FitToParent.js');
			// Load main script
		$this->pageRenderer->addJsFile($this->relativePath . 'res/js/t3_recycler.js');
	}

	/**
	 * Gets the JavaScript configuration for the Ext JS interface.
	 *
	 * @return	array		The JavaScript configuration
	 */
	protected function getJavaScriptConfiguration() {
		$configuration = array(
			'pagingSize' => $this->recordsPageLimit,
			'showDepthMenu' => 1,
			'startUid' => $this->id,
			'tableDefault' => 'pages',
			'renderTo' => 'recyclerContent',
			'isSSL' => t3lib_div::getIndpEnv('TYPO3_SSL'),
			'ajaxController' => $this->doc->backPath . 'ajax.php?ajaxID=tx_recycler::controller',
			'deleteDisable' => $this->allowDelete ? 0 : 1,
			'depthSelection' => $this->getDataFromSession('depthSelection', 0),
			'tableSelection' => $this->getDataFromSession('tableSelection', 'pages'),
			'States' => $GLOBALS['BE_USER']->uc['moduleData']['web_recycler']['States'],
		);
		return $configuration;
	}

	/**
	 * Gets the labels to be used in JavaScript in the Ext JS interface.
	 *
	 * @return	array		The labels to be used in JavaScript
	 */
	protected function getJavaScriptLabels() {
		$coreLabels = array(
			'title'			=> $GLOBALS['LANG']->getLL('title'),
			'path'			=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path'),
			'table'			=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.table'),
			'depth'			=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_perm.xml:Depth'),
			'depth_0'		=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_0'),
			'depth_1'		=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_1'),
			'depth_2'		=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_2'),
			'depth_3'		=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_3'),
			'depth_4'		=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_4'),
			'depth_infi'	=> $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.depth_infi'),
		);

		$extensionLabels = $this->getJavaScriptLabelsFromLocallang('js.', 'label_');
		$javaScriptLabels = array_merge($coreLabels, $extensionLabels);

			// Convert labels back to UTF-8 since json_encode() only works with UTF-8:
		if ($GLOBALS['LANG']->charSet !== 'utf-8') {
			$GLOBALS['LANG']->csConvObj->convArray($javaScriptLabels, $GLOBALS['LANG']->charSet, 'utf-8');
		}

		return $javaScriptLabels;
	}

	/**
	 * Gets labels to be used in JavaScript fetched from the current locallang file.
	 *
	 * @param	string		$selectionPrefix: Prefix to select the correct labels (default: 'js.')
	 * @param	string		$stripFromSelectionName: Sub-prefix to be removed from label names in the result (default: '')
	 * @return	array		Lables to be used in JavaScript of the current locallang file
	 * @todo	Check, whether this method can be moved in a generic way to $GLOBALS['LANG']
	 */
	protected function getJavaScriptLabelsFromLocallang($selectionPrefix = 'js.', $stripFromSelectionName = '') {
		$extraction = array();
		$labels = array_merge(
			(array)$GLOBALS['LOCAL_LANG']['default'],
			(array)$GLOBALS['LOCAL_LANG'][$GLOBALS['LANG']->lang]
		);
			// Regular expression to strip the selection prefix and possibly something from the label name:
		$labelPattern = '#^' . preg_quote($selectionPrefix, '#') . '(' . preg_quote($stripFromSelectionName, '#') . ')?#';
			// Iterate through all locallang lables:
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
	 * @return	array		Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'csh'		=> t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']),
			'shortcut'	=> $this->getShortcutButton(),
			'save'		=> ''
		);

			// SAVE button
		$buttons['save'] = ''; //<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc', 1) . '" />';

		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return	string		HTML representation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $result;
	}

	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return	array		The filled marker array
	 */
	protected function getTemplateMarkers() {
		$markers = array(
			'FUNC_MENU'	=> $this->getFunctionMenu(),
			'CONTENT'	=> $this->content,
			'TITLE'		=> $GLOBALS['LANG']->getLL('title'),
		);
		return $markers;
	}

	/**
	 * Gets the function menu selector for this backend module.
	 *
	 * @return	string		The HTML representation of the function menu selector
	 */
	protected function getFunctionMenu() {
		return t3lib_BEfunc::getFuncMenu(
			0,
			'SET[function]',
			$this->MOD_SETTINGS['function'],
			$this->MOD_MENU['function']
		);
	}

	/**
	 * Gets data from the session of the current backend user.
	 *
	 * @param	string		$identifier: The identifier to be used to get the data
	 * @param	string		$default: The default date to be used if nothing was found in the session
	 * @return	string		The accordant data in the session of the current backend user
	 */
	protected function getDataFromSession($identifier, $default = NULL) {
		$sessionData =& $GLOBALS['BE_USER']->uc['tx_recycler'];
		if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
			$data = $sessionData[$identifier];
		} else {
			$data = $default;
		}
		return $data;
	}
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/mod1/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/mod1/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_recycler_module1');
$SOBE->initialize();

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->render();
$SOBE->flush();

?>
