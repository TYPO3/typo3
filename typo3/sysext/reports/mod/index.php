<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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


$LANG->includeLLFile('EXT:reports/mod/locallang.xml');
	// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, 1);


/**
 * Module 'Reports' for the 'reports' extension.
 *
 * @author		Ingo Renner <ingo@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_reports
 */
class tx_reports_Module extends t3lib_SCbase {

	protected $pageinfo;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function __construct() {
		parent::init();

			// initialize document
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(
			t3lib_extMgm::extPath('reports') . 'mod/mod_template.html'
		);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->addStyleSheet(
			'tx_reports',
			'../' . t3lib_extMgm::siteRelPath('reports') . 'mod/mod_styles.css'
		);
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	public function menuConfig() {
		$reportsMenuItems = array();
		$this->MOD_MENU   = array('function' => array());

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] as $extKey => $reports) {
			foreach ($reports as $reportName => $report) {
				$reportsMenuItems[$extKey . '.' . $reportName] = $GLOBALS['LANG']->sL($report['title']);
			}
		}

		asort($reportsMenuItems);
		$reportsMenuItems = array_merge(
			array('index' => $GLOBALS['LANG']->getLL('reports_overview')),
			$reportsMenuItems
		);

		foreach ($reportsMenuItems as $key => $title) {
			$this->MOD_MENU['function'][$key] = $title;
		}

		parent::menuConfig();
	}

	/**
	 * Creates the module's content. In this case it rather acts as a kind of #
	 * dispatcher redirecting requests to specific reports.
	 *
	 * @return	void
	 */
	public function main() {
		$docHeaderButtons = $this->getButtons();

			// Access check!
			// The page will show only if user has admin rights
		if ($GLOBALS['BE_USER']->user['admin']) {

				// Draw the form
			$this->doc->form = '<form action="" method="post" enctype="multipart/form-data">';
				// JavaScript
			$this->doc->JScodeArray[] = '
				script_ended = 0;
				function jumpToUrl(URL) {
					document.location = URL;
				}
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) {
						top.fsMod.recentIds["web"] = 0;
					}
				</script>
			';
				// Render content:
			$this->renderModuleContent();
		} else {
				// If no access or if ID == 0
			$docHeaderButtons['save'] = '';
			$this->content.=$this->doc->spacer(10);
		}

			// compile document
		$markers['FUNC_MENU'] = $GLOBALS['LANG']->getLL('choose_report')
			. t3lib_BEfunc::getFuncMenu(
				0,
				'SET[function]',
				$this->MOD_SETTINGS['function'],
				$this->MOD_MENU['function']
			);
		$markers['CONTENT'] = $this->content;

				// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// Renders the module page
		$this->content = $this->doc->render(
			$GLOBALS['LANG']->getLL('title'),
			$this->content
		);
	}

	/**
	 * Prints out the module's HTML
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Generates the module content by calling the selected report
	 *
	 * @return	void
	 */
	protected function renderModuleContent() {
		$action  = (string) $this->MOD_SETTINGS['function'];
		$title   = '';
		$content = '';

		if ($action == 'index') {
			$content = $this->indexAction();
			$title   = $GLOBALS['LANG']->getLL('reports_overview');
		} else {
			$content = '';
			list($extensionKey, $reportName) = explode('.', $action, 2);

			$reportClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extensionKey][$reportName]['report'];
			$title       = $GLOBALS['LANG']->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extensionKey][$reportName]['title']);

			$reportInstance = t3lib_div::makeInstance($reportClass, $this);

			if ($reportInstance instanceof tx_reports_Report) {
				$content = $reportInstance->getReport();
			} else {
				$content = $reportClass . ' does not implement the Report Interface which is necessary to be displayed here.';
			}
		}

		$this->content .= $this->doc->section($title, $content, false, true);
	}

	/**
	 * Shows an overview list of available reports.
	 *
	 * @return	string	list of available reports
	 */
	protected function indexAction() {
		$defaultIcon = t3lib_extMgm::extRelPath('reports') . 'mod/moduleicon.gif';
		$content = '<dl class="t3-overview-list">';
		$reports = array();

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] as $extKey => $extensionReports) {
			foreach ($extensionReports as $reportName => $report) {
				$action = $extKey . '.' . $reportName;
				$link = 'mod.php?M=tools_txreportsM1' . htmlspecialchars('&SET[function]=') . $action;

				$reportTitle = $GLOBALS['LANG']->sL($report['title']);

					// Set default report icon
				$icon = $defaultIcon;
					// Check for custom icon
				if (!empty($report['icon'])) {
					$absIconPath = t3lib_div::getFileAbsFilename($report['icon']);
						// If the file indeed exists, assemble relative path to it
					if (file_exists($absIconPath)) {
						$icon = $GLOBALS['BACK_PATH'] . '../' . str_replace(PATH_site, '', $absIconPath);
					}
				}
				$icon = '<img' . t3lib_iconworks::skinImg($GLOBALS['BACK_PATH'], $icon, 'width="16" height="16"') . ' title="' . $reportTitle . '" alt="' . $reportTitle . '" />';
				$reportContent  = '<dt><a href="' . $link . '">' . $icon . $reportTitle . '</a></dt>';
				$reportContent .= '<dd>' . $GLOBALS['LANG']->sL($report['description']) . '</dd>';

				$reports[$reportTitle] = $reportContent;
			}
		}

		ksort($reports);

		foreach ($reports as $reportContent) {
			$content .= $reportContent;
		}

		return $content . '</dl>';
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise
	 * perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}


}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/mod/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/mod/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_reports_Module');

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>