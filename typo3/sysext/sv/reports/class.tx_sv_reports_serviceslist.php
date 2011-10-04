<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Francois Suter <francois@typo3.org>
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
 * Code inspired by EXT:dam/lib/class.tx_dam_svlist.php by René Fritz
 *
 * @author	Francois Suter <francois@typo3.org>
 * @package TYPO3
 * @subpackage sv
 */
class tx_sv_reports_ServicesList implements tx_reports_Report {

	/**
	 * Back-reference to the calling reports module
	 *
	 * @var	tx_reports_Module	$reportsModule
	 */
	protected $reportsModule;

	/**
	 * Constructor for class tx_sv_reports_ServicesList
	 *
	 * @param tx_reports_Module $reportsModule Back-reference to the calling reports module
	 */
	public function __construct(tx_reports_Module $reportsModule) {
		$this->reportsModule = $reportsModule;
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
		$this->reportsModule->doc->getPageRenderer()->addCssFile(
			t3lib_extMgm::extRelPath('sv') . 'reports/tx_sv_report.css'
		);

		$content .= $this->renderHelp();
		$content .= $this->reportsModule->doc->spacer(10);
		$content .= $this->renderServicesList();
		$content .= $this->reportsModule->doc->spacer(10);
		$content .= $this->renderExecutablesSearchPathList();

		return $content;
	}

	/**
	 * Renders the help comments at the top of the module.
	 *
	 * @return	string	The help content for this module.
	 */
	protected function renderHelp() {
		$help = '<p class="help">'
			. $GLOBALS['LANG']->getLL('report_explanation')
			. '</p>';
		$help .= '<p class="help">'
			. $GLOBALS['LANG']->getLL('externals_explanation')
			. '</p>';

		return $help;
	}

	/**
	 * This method assembles a list of all installed services
	 *
	 * @return	string	HTML to display
	 */
	protected function renderServicesList() {
		$servicesList = '';
		$services = $this->getInstalledServices();

		foreach ($services as $serviceType => $installedServices) {
			$servicesList .= $this->renderServiceTypeList($serviceType, $installedServices);
		}

		return $servicesList;
	}

	/**
	 * Renders the services list for a single service type.
	 *
	 * @param string $serviceType The service type to render the installed services list for
	 * @param array $services List of services for the given type
	 * @return string Service list as HTML for one service type
	 */
	protected function renderServiceTypeList($serviceType, $services) {
		$header = '<h4>' . sprintf(
			$GLOBALS['LANG']->getLL('service_type'),
			$serviceType
		) . '</h4>';

		$serviceList = '
		<table cellspacing="1" cellpadding="2" border="0" class="tx_sv_reportlist services">
			<tr class="t3-row-header">
				<td style="width: 35%">' . $GLOBALS['LANG']->getLL('service') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('priority') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('quality') . '</td>
				<td style="width: 35%">' . $GLOBALS['LANG']->getLL('subtypes') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('os') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('externals') . '</td>
				<td>' . $GLOBALS['LANG']->getLL('available') . '</td>
			</tr>';

		foreach ($services as $serviceKey => $serviceInformation) {
			$serviceList .= $this->renderServiceRow($serviceKey, $serviceInformation);
		}

		$serviceList .= '
		</table>
		';

		return $header . $serviceList . $this->reportsModule->doc->spacer(10);
	}

	/**
	 * Renders a single service's row.
	 *
	 * @param	string	$serviceKey The service key to access the service.
	 * @param	array	$serviceInformation registration information of the service.
	 * @return	string	HTML row for the service.
	 */
	protected function renderServiceRow($serviceKey, $serviceInformation) {
		$serviceDescription = '
			<p class="service-header">
				<span class="service-title">' . $serviceInformation['title'] . '</span> (' . $serviceInformation['extKey'] . ': ' . $serviceKey . ')
			</p>';

		if (!empty($serviceInformation['description'])) {
			$serviceDescription .= '<p class="service-description">' . $serviceInformation['description']. '</p>';
		}

		$serviceSubtypes = empty($serviceInformation['serviceSubTypes']) ?
			'-' :
			implode(', ', $serviceInformation['serviceSubTypes']);

		$serviceOperatingSystem = empty($serviceInformation['os']) ?
			$GLOBALS['LANG']->getLL('any') :
			$serviceInformation['os'];

		$serviceRequiredExecutables = empty($serviceInformation['exec']) ?
			'-' :
			$serviceInformation['exec'];

		$serviceAvailabilityClass = 'typo3-message message-error';
		$serviceAvailable = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
		try {
			$serviceDetails = t3lib_extmgm::findServiceByKey($serviceKey);
			if ($serviceDetails['available']) {
				$serviceAvailabilityClass = 'typo3-message message-ok';
				$serviceAvailable = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
			}
		}
		catch (t3lib_exception $e) {
			// Nothing to do, the default display is already not available
		}

		$serviceRow = '
		<tr class="service">
			<td class="first-cell ' . $serviceAvailabilityClass . '">' . $serviceDescription . '</td>
			<td class="cell ' . $serviceAvailabilityClass . '">' . $serviceInformation['priority'] . '</td>
			<td class="cell ' . $serviceAvailabilityClass . '">' . $serviceInformation['quality'] . '</td>
			<td class="cell ' . $serviceAvailabilityClass . '">' . $serviceSubtypes . '</td>
			<td class="cell ' . $serviceAvailabilityClass . '">' . $serviceOperatingSystem . '</td>
			<td class="cell ' . $serviceAvailabilityClass . '">' . $serviceRequiredExecutables . '</td>
			<td class="last-cell ' . $serviceAvailabilityClass . '">' . $serviceAvailable . '</td>
		</tr>';

		return $serviceRow;
	}

	/**
	 * This method assembles a list of all defined executables search paths
	 *
	 * @return	string	HTML to display
	 */
	protected function renderExecutablesSearchPathList() {
		$searchPaths = t3lib_exec::getPaths(TRUE);

		$content = '<h3 class="divider">' . $GLOBALS['LANG']->getLL('search_paths') . '</h3>';

		if (count($searchPaths) == 0) {
			$content .= '<p>' . $GLOBALS['LANG']->getLL('no_search_paths') . '</p>';
		} else {
			$content .= '
			<table cellspacing="1" cellpadding="2" border="0" class="tx_sv_reportlist paths">
				<thead>
					<tr class="t3-row-header">
						<td>' . $GLOBALS['LANG']->getLL('path') . '</td>
						<td>' . $GLOBALS['LANG']->getLL('valid') . '</td>
					</tr>
				</thead>
				<tbody>';

			foreach ($searchPaths as $path => $isValid) {
				$pathAccessibleClass = 'typo3-message message-error';
				$pathAccessible = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:no');
				if ($isValid) {
					$pathAccessibleClass = 'typo3-message message-ok';
					$pathAccessible = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
				}

				$content .= '
					<tr>
						<td class="first-cell ' . $pathAccessibleClass . '">' . t3lib_div::fixWindowsFilePath($path) . '</td>
						<td class="last-cell ' . $pathAccessibleClass . '">' . $pathAccessible . '</td>
					</tr>';
			}

			$content .= '
				</tbody>
			</table>';
		}

		return $content;
	}

	/**
	 * This method filters the $T3_SERVICES global array to return a relevant,
	 * ordered list of installed services.
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

		foreach ($GLOBALS['T3_SERVICES'] as $serviceType => $serviceList) {
				// If the (first) key of the service list is not the same as the service type,
				// it's a "true" service type. Keep it and sort it.
			if (key($serviceList) !== $serviceType) {
				uasort($serviceList, array($this, 'sortServices'));
				$filteredServices[$serviceType] = $serviceList;
			}
		}

		return $filteredServices;
	}

	/**
	 * Utility method used to sort services according to their priority and
	 * quality.
	 *
	 * @param array $a First service to compare
	 * @param array $b Second service to compare
	 *
	 * @return integer 1, 0 or -1 if a is smaller, equal or greater than b, respectively
	 */
	protected function sortServices(array $a, array $b) {
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


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php']);
}

?>