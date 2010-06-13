<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Francois Suter <francois@typo3.org>
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
 * This class provides a report displaying a list of all installed services
 * Code inspired by EXT:dam/lib/class.tx_dam_svlist.php by Rene Fritz
 *
 * @author		Francois Suter <francois@typo3.org>
 * @package		TYPO3
 * @subpackage	sv
 *
 * $Id$
 */
class tx_sv_reports_ServicesList implements tx_reports_Report {
	/**
	 * Back-reference to the calling reports module
	 *
	 * @var	tx_reports_Module	$reportObject
	 */
	protected $reportObject;

	/**
	 * Constructor for class tx_sv_reports_ServicesList
	 *
	 * @param	tx_reports_Module	Back-reference to the calling reports module
	 */
	public function __construct(tx_reports_Module $reportObject) {
		$this->reportObject = $reportObject;
		$GLOBALS['LANG']->includeLLFile('EXT:sv/reports/locallang.xml');
	}

	/**
	 * This method renders the report
	 *
	 * @return	string	The status report as HTML
	 */
	public function getReport() {
		$content = '';

			// Add custom stylesheet
		$this->reportObject->doc->getPageRenderer()->addCssFile(t3lib_extMgm::extRelPath('sv') . 'reports/tx_sv_report.css');
			// Start assembling content
		$content .= '<p class="help">'
			. $GLOBALS['LANG']->getLL('report_explanation')
			. '</p>';
		$content .= '<p class="help">'
			. $GLOBALS['LANG']->getLL('externals_explanation')
			. '</p>';

			// Get list of installed services
		$content .= $this->displayServiceList();
			// Get list of binaries search paths
		$content .= $this->reportObject->doc->spacer(10);
		$content .= $this->displaySearchPaths();

		return $content;
	}

	/**
	 * This method assembles a list of all installed services
	 *
	 * @return	string	HTML to display
	 */
	protected function displayServiceList() {
		$content = '';
		$services = $this->getInstalledServices();
		$content .= '<table cellspacing="1" cellpadding="2" border="0" class="tx_sv_reportlist">';
		foreach ($services as $serviceType => $installedServices) {
			$content .= '<tr><td colspan="7">';
			$content .= '<h4>' . sprintf($GLOBALS['LANG']->getLL('service_type'), $serviceType) . '</h4>';
			$content .= '</td></tr>';
			$content .= '<tr class="bgColor2">';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('service') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('priority') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('quality') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('subtypes') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('os') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('externals') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('available') . '</td>';
			$content .= '</tr>';
			foreach ($installedServices as $serviceKey => $serviceInfo) {
				$content .= '<tr class="bgColor3-20">';
				$cellContent = '<p class="service-header"><span class="service-title">' . $serviceInfo['title'] . '</span> (' . $serviceInfo['extKey'] . ': ' . $serviceKey . ')</p>';
				if (!empty($serviceInfo['description'])) {
					$cellContent .= '<p class="service-description">' . $serviceInfo['description']. '</p>';
				}
				$content .= '<td class="cell">' . $cellContent . '</td>';
				$content .= '<td class="cell">' . $serviceInfo['priority'] . '</td>';
				$content .= '<td class="cell">' . $serviceInfo['quality'] . '</td>';
				$content .= '<td class="cell">' . ((empty($serviceInfo['serviceSubTypes'])) ? '-' : implode(', ', $serviceInfo['serviceSubTypes'])) . '</td>';
				$content .= '<td class="cell">' . ((empty($serviceInfo['os'])) ? $GLOBALS['LANG']->getLL('any') : $serviceInfo['os']) . '</td>';
				$content .= '<td class="cell">' . ((empty($serviceInfo['exec'])) ? '-' : $serviceInfo['exec']) . '</td>';
				$class = 'typo3-message message-error';
				$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
				if (t3lib_extmgm::findService($serviceKey, '*')) {
					$class = 'typo3-message message-ok';
					$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
				}
				$content .= '<td class="cell ' . $class . '">' . $message . '</td>';
				$content .= '</tr>';
			}
		}
		$content .= '</table>';
		return $content;
	}

	/**
	 * This method assembles a list of all defined search paths
	 *
	 * @return	string	HTML to display
	 */
	protected function displaySearchPaths() {
		$content = '<h3 class="uppercase">' . $GLOBALS['LANG']->getLL('search_paths') . '</h3>';
		$searchPaths = t3lib_exec::getPaths(true);
		if (count($searchPaths) == 0) {
			$content .= '<p>' . $GLOBALS['LANG']->getLL('no_search_paths') . '</p>';
		} else {
			$content .= '<table cellspacing="1" cellpadding="2" border="0" class="tx_sv_reportlist">';
			$content .= '<thead>';
			$content .= '<tr class="bgColor2">';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('path') . '</td>';
			$content .= '<td class="cell">' . $GLOBALS['LANG']->getLL('valid') . '</td>';
			$content .= '</tr>';
			$content .= '</thead>';
			$content .= '<tbody>';
			foreach ($searchPaths as $path => $isValid) {
				$content .= '<tr class="bgColor3-20">';
				$content .= '<td class="cell">' . t3lib_div::fixWindowsFilePath($path) . '</td>';
				$class = 'typo3-message message-error';
				$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
				if ($isValid) {
					$class = 'typo3-message message-ok';
					$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
				}
				$content .= '<td class="cell ' . $class . '">' . $message . '</td>';
				$content .= '</tr>';
			}
			$content .= '</tbody>';
			$content .= '</table>';
		}
		return $content;
	}

	/**
	 * This method filters the $T3_SERVICES global array to return a relevant,
	 * ordered list of installed services
	 *
	 * Every installed service appears twice in $T3_SERVICES: once as a service key
	 * for a given service type, and once a service type all by itself
	 * The list of services to display must avoid these duplicates
	 *
	 * Furthermore, inside each service type, installed services must be
	 * ordered by priority and quality
	 *
	 * @return	array	List of filtered and ordered services
	 */
	protected function getInstalledServices() {
		$filteredServices = array();

			// Loop on all installed services
		foreach ($GLOBALS['T3_SERVICES'] as $serviceType => $serviceList) {
				// If the (first) key of the service list is not the same as the service type,
				// it's a "true" service type. Keep it and sort it.
			if (key($serviceList) !== $serviceType) {
				uasort($serviceList, array('tx_sv_reports_ServicesList', 'sortServices'));
				$filteredServices[$serviceType] = $serviceList;
			}
		}
		return $filteredServices;
	}

	/**
	 * Utility method used to sort services according to their priority and quality
	 *
	 * @param	array		First service to compare
	 * @param	array		Second service to compare
	 *
	 * @return	integer		1, 0 or -1 if a is smaller, equal or greater than b, respectively
	 */
	public function sortServices(array $a, array $b) {
		$result = 0;
			// If priorities are the same, test quality
		if ($a['priority'] == $b['priority']) {
			if ($a['quality'] != $b['quality']) {
					// Service with highest quality should come first,
					// thus it must be marked as smaller
				$result = ($a['quality'] > $b['quality']) ? -1 : 1;
			}
		} else {
				// Service with highest priority should come first,
				// thus it must be marked as smaller
			$result = ($a['priority'] > $b['priority']) ? -1 : 1;
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php']);
}

?>