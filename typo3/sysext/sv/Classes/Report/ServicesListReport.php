<?php
namespace TYPO3\CMS\Sv\Report;

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

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Controller\ReportController;
use TYPO3\CMS\Reports\ReportInterface;

/**
 * This class provides a report displaying a list of all installed services
 * Code inspired by EXT:dam/lib/class.tx_dam_svlist.php by René Fritz
 */
class ServicesListReport implements ReportInterface
{
    /**
     * Back-reference to the calling reports module
     *
     * @var ReportController
     */
    protected $reportsModule;

    /**
     * Constructor
     *
     * @param ReportController $reportsModule Back-reference to the calling reports module
     */
    public function __construct(ReportController $reportsModule)
    {
        $this->reportsModule = $reportsModule;
        $this->getLanguageService()->includeLLFile('EXT:sv/Resources/Private/Language/locallang.xlf');
    }

    /**
     * This method renders the report
     *
     * @return string The status report as HTML
     */
    public function getReport()
    {
        $content = '';
        $content .= $this->renderHelp();
        $content .= $this->renderServicesList();
        $content .= $this->renderExecutablesSearchPathList();
        return $content;
    }

    /**
     * Renders the help comments at the top of the module.
     *
     * @return string The help content for this module.
     */
    protected function renderHelp()
    {
        $help = '<p class="lead">' . $this->getLanguageService()->getLL('report_explanation') . '</p>';
        $help .= '<p class="help">' . $this->getLanguageService()->getLL('externals_explanation') . '</p>';
        return $help;
    }

    /**
     * This method assembles a list of all installed services
     *
     * @return string HTML to display
     */
    protected function renderServicesList()
    {
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
    protected function renderServiceTypeList($serviceType, $services)
    {
        $lang = $this->getLanguageService();
        $header = '<h3>' . sprintf($lang->getLL('service_type'), $serviceType) . '</h3>';
        $serviceList = '
			<table class="table table-striped table-hover tx_sv_reportlist">
				<thead>
					<tr>
						<td style="width: 35%">' . $lang->getLL('service') . '</td>
						<td>' . $lang->getLL('priority') . '</td>
						<td>' . $lang->getLL('quality') . '</td>
						<td style="width: 35%">' . $lang->getLL('subtypes') . '</td>
						<td>' . $lang->getLL('os') . '</td>
						<td>' . $lang->getLL('externals') . '</td>
						<td>' . $lang->getLL('available') . '</td>
					</tr>
				</thead>
			<tbody>';

        foreach ($services as $serviceKey => $serviceInformation) {
            $serviceList .= $this->renderServiceRow($serviceKey, $serviceInformation);
        }

        $serviceList .= '
			</tbody>
			</table>
		';
        return $header . $serviceList;
    }

    /**
     * Renders a single service's row.
     *
     * @param string $serviceKey The service key to access the service.
     * @param array $serviceInformation registration information of the service.
     * @return string HTML row for the service.
     */
    protected function renderServiceRow($serviceKey, $serviceInformation)
    {
        $serviceDescription = '
			<p class="service-header">
				<span class="service-title">' . $serviceInformation['title'] . '</span> (' . $serviceInformation['extKey'] . ': ' . $serviceKey . ')
			</p>';
        if (!empty($serviceInformation['description'])) {
            $serviceDescription .= '<p class="service-description">' . $serviceInformation['description'] . '</p>';
        }
        $serviceSubtypes = $serviceInformation['serviceSubTypes'] ? implode(', ', $serviceInformation['serviceSubTypes']) : '-';
        $serviceOperatingSystem = $serviceInformation['os'] ?: $this->getLanguageService()->getLL('any');
        $serviceRequiredExecutables = $serviceInformation['exec'] ?: '-';
        $serviceAvailabilityClass = 'danger';
        $serviceAvailable = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:no');
        try {
            $serviceDetails = ExtensionManagementUtility::findServiceByKey($serviceKey);
            if ($serviceDetails['available']) {
                $serviceAvailabilityClass = 'success';
                $serviceAvailable = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:yes');
            }
        } catch (Exception $e) {
        }
        $serviceRow = '
			<tr class="service ' . $serviceAvailabilityClass . '">
				<td class="first-cell">' . $serviceDescription . '</td>
				<td class="cell">' . $serviceInformation['priority'] . '</td>
				<td class="cell">' . $serviceInformation['quality'] . '</td>
				<td class="cell">' . $serviceSubtypes . '</td>
				<td class="cell">' . $serviceOperatingSystem . '</td>
				<td class="cell">' . $serviceRequiredExecutables . '</td>
				<td class="last-cell">' . $serviceAvailable . '</td>
			</tr>';
        return $serviceRow;
    }

    /**
     * This method assembles a list of all defined executables search paths
     *
     * @return string HTML to display
     */
    protected function renderExecutablesSearchPathList()
    {
        $searchPaths = CommandUtility::getPaths(true);
        $content = '<h3>' . $this->getLanguageService()->getLL('search_paths') . '</h3>';
        if (empty($searchPaths)) {
            $content .= '<p>' . $this->getLanguageService()->getLL('no_search_paths') . '</p>';
        } else {
            $content .= '
			<table class="table table-striped table-hover tx_sv_reportlist">
				<thead>
					<tr>
						<td>' . $this->getLanguageService()->getLL('path') . '</td>
						<td>' . $this->getLanguageService()->getLL('valid') . '</td>
					</tr>
				</thead>
				<tbody>';
            foreach ($searchPaths as $path => $isValid) {
                $pathAccessibleClass = 'danger';
                $pathAccessible = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:no');
                if ($isValid) {
                    $pathAccessibleClass = 'success';
                    $pathAccessible = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:yes');
                }
                $content .= '
					<tr class="' . $pathAccessibleClass . '">
						<td class="first-cell">' . GeneralUtility::fixWindowsFilePath($path) . '</td>
						<td class="last-cell">' . $pathAccessible . '</td>
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
     * @return array List of filtered and ordered services
     */
    protected function getInstalledServices()
    {
        $filteredServices = [];
        foreach ($GLOBALS['T3_SERVICES'] as $serviceType => $serviceList) {
            // If the (first) key of the service list is not the same as the service type,
            // it's a "true" service type. Keep it and sort it.
            if (key($serviceList) !== $serviceType) {
                uasort($serviceList, [$this, 'sortServices']);
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
     * @return int 1, 0 or -1 if $a is smaller, equal or greater than $b, respectively
     */
    protected function sortServices(array $a, array $b)
    {
        $result = 0;
        // If priorities are the same, test quality
        if ($a['priority'] == $b['priority']) {
            if ($a['quality'] != $b['quality']) {
                // Service with highest quality should come first,
                // thus it must be marked as smaller
                $result = $a['quality'] > $b['quality'] ? -1 : 1;
            }
        } else {
            // Service with highest priority should come first,
            // thus it must be marked as smaller
            $result = $a['priority'] > $b['priority'] ? -1 : 1;
        }
        return $result;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
