<?php
namespace TYPO3\CMS\Reports\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Georg Ringer <typo3@ringerge.org>
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
 * Reports controller
 *
 */
class ReportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Redirect to the saved report
	 *
	 * @return void
	 */
	public function initializeAction() {
		$vars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_reports_tools_reportstxreportsm1');
		if (!isset($vars['redirect']) && $vars['action'] !== 'index' && !isset($vars['extension']) && is_array($GLOBALS['BE_USER']->uc['reports']['selection'])) {
			$previousSelection = $GLOBALS['BE_USER']->uc['reports']['selection'];
			if (!empty($previousSelection['extension']) && !empty($previousSelection['report'])) {
				$this->redirect('detail', 'Report', NULL, array('extension' => $previousSelection['extension'], 'report' => $previousSelection['report'], 'redirect' => 1));
			} else {
				$this->redirect('index');
			}
		}
	}

	/**
	 * Overview
	 *
	 * @return void
	 */
	public function indexAction() {
		$this->view->assignMultiple(array(
			'reports' => $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'],
			'menu' => $this->getMenu()
		));
		$this->saveState();
	}

	/**
	 * Display a single report
	 *
	 * @param string $extension Extension
	 * @param string $report Report
	 * @return void
	 */
	public function detailAction($extension, $report) {
		$content = ($error = '');
		$reportClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report]['report'];
		$reportInstance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($reportClass, $this);
		if ($reportInstance instanceof \TYPO3\CMS\Reports\ReportInterface) {
			$content = $reportInstance->getReport();
			$this->saveState($extension, $report);
		} else {
			$error = $reportClass . ' does not implement the Report Interface which is necessary to be displayed here.';
		}
		$this->view->assignMultiple(array(
			'content' => $content,
			'error' => $error,
			'report' => $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'][$extension][$report],
			'menu' => $this->getMenu()
		));
	}

	/**
	 * Generate the menu
	 *
	 * @return array Menu items
	 */
	protected function getMenu() {
		$reportsMenuItems = array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports'] as $extKey => $reports) {
			foreach ($reports as $reportName => $report) {
				$reportsMenuItems[] = array(
					'title' => $GLOBALS['LANG']->sL($report['title']),
					'extension' => $extKey,
					'report' => $reportName
				);
			}
		}
		return $reportsMenuItems;
	}

	/**
	 * Save the selected report
	 *
	 * @param string $extension Extension name
	 * @param string $report Report name
	 * @return void
	 */
	protected function saveState($extension = '', $report = '') {
		$GLOBALS['BE_USER']->uc['reports']['selection'] = array('extension' => $extension, 'report' => $report);
		$GLOBALS['BE_USER']->writeUC();
	}

}


?>