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
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Reports\Controller\ReportController;
use TYPO3\CMS\Reports\ReportInterface;

/**
 * This class provides a report displaying a list of all installed services
 */
class ServicesListReport implements ReportInterface
{
    /**
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
        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:sv/Resources/Private/Templates/ServicesListReport.html'
        ));

        $view->assignMultiple([
            'servicesList' => $this->getServicesList(),
            'searchPaths' => $this->getExecutablesSearchPathList()
        ]);

        return $view->render();
    }

    /**
     * This method assembles a list of all installed services
     *
     * @return array with data to display
     */
    protected function getServicesList()
    {
        $servicesList = [];
        $services = $this->getInstalledServices();
        foreach ($services as $serviceType => $installedServices) {
            $servicesList[] = $this->getServiceTypeList($serviceType, $installedServices);
        }
        return $servicesList;
    }

    /**
     * Get the services list for a single service type.
     *
     * @param string $serviceType The service type to render the installed services list for
     * @param array $services List of services for the given type
     * @return array Service list as array for one service type
     */
    protected function getServiceTypeList($serviceType, $services)
    {
        $lang = $this->getLanguageService();
        $serviceList = [];
        $serviceList['Type'] = sprintf($lang->getLL('service_type'), $serviceType);

        $serviceList['Services'] = [];
        foreach ($services as $serviceKey => $serviceInformation) {
            $serviceList['Services'][] = $this->getServiceRow($serviceKey, $serviceInformation);
        }

        return $serviceList;
    }

    /**
     * Get data of a single service's row.
     *
     * @param string $serviceKey The service key to access the service.
     * @param array $serviceInformation registration information of the service.
     * @return array data for one row for the service.
     */
    protected function getServiceRow($serviceKey, $serviceInformation)
    {
        $result = [
            'Key' => $serviceKey,
            'Information' => $serviceInformation,
            'Subtypes' => $serviceInformation['serviceSubTypes'] ? implode(', ', $serviceInformation['serviceSubTypes']) : '-',
            'OperatingSystem' => $serviceInformation['os'] ?: $this->getLanguageService()->getLL('any'),
            'RequiredExecutables' => $serviceInformation['exec'] ?: '-',
            'AvailabilityClass' => 'danger',
            'Available' => $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:no'),
        ];

        try {
            $serviceDetails = ExtensionManagementUtility::findServiceByKey($serviceKey);
            if ($serviceDetails['available']) {
                $result['AvailabilityClass'] = 'success';
                $result['Available'] = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:yes');
            }
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * This method assembles a list of all defined executables search paths
     *
     * @return array data to display
     */
    protected function getExecutablesSearchPathList()
    {
        $searchPaths = CommandUtility::getPaths(true);
        $result = [];

        foreach ($searchPaths as $path => $isValid) {
            $searchPathData = $this->getServicePathStatus($isValid);
            $result[] = [
                'class' => $searchPathData['statusCSSClass'],
                'accessible' => 'LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:' . $searchPathData['accessible'],
                'path' => GeneralUtility::fixWindowsFilePath($path),
            ];
        }

        return $result;
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
        if ($a['priority'] === $b['priority']) {
            if ($a['quality'] !== $b['quality']) {
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
     * Method to check if the service in path is available
     * @param bool|string $isValid
     * @return array
     */
    private function getServicePathStatus($isValid): array
    {
        $statusCSSClass = 'danger';
        $accessible = 'no';

        if ($isValid) {
            $statusCSSClass = 'success';
            $accessible = 'yes';
        }
        return [
            'statusCSSClass' => $statusCSSClass,
            'accessible' => $accessible
        ];
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
