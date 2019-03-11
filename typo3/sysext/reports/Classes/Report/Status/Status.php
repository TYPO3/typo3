<?php
namespace TYPO3\CMS\Reports\Report\Status;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\RequestAwareReportInterface;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * The status report
 */
class Status implements RequestAwareReportInterface
{
    /**
     * @var StatusProviderInterface[][]
     */
    protected $statusProviders = [];

    /**
     * Constructor for class tx_reports_report_Status
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:reports/Resources/Private/Language/locallang_reports.xlf');
        $this->getStatusProviders();
    }

    /**
     * Takes care of creating / rendering the status report
     *
     * @param ServerRequestInterface|null $request the currently handled request
     * @return string The status report as HTML
     */
    public function getReport(ServerRequestInterface $request = null)
    {
        $content = '';
        $status = $this->getSystemStatus($request);
        $highestSeverity = $this->getHighestSeverity($status);
        // Updating the registry
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
        $content .= '<p class="lead">' . $this->getLanguageService()->getLL('status_report_explanation') . '</p>';
        return $content . $this->renderStatus($status);
    }

    /**
     * Gets all registered status providers and creates instances of them.
     */
    protected function getStatusProviders()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'] as $key => $statusProvidersList) {
            $this->statusProviders[$key] = [];
            foreach ($statusProvidersList as $statusProvider) {
                $statusProviderInstance = GeneralUtility::makeInstance($statusProvider);
                if ($statusProviderInstance instanceof StatusProviderInterface) {
                    $this->statusProviders[$key][] = $statusProviderInstance;
                }
            }
        }
    }

    /**
     * Runs through all status providers and returns all statuses collected.
     *
     * @param ServerRequestInterface $request
     * @return \TYPO3\CMS\Reports\Status[]
     */
    public function getSystemStatus(ServerRequestInterface $request = null)
    {
        $status = [];
        foreach ($this->statusProviders as $statusProviderId => $statusProviderList) {
            $status[$statusProviderId] = [];
            foreach ($statusProviderList as $statusProvider) {
                if ($statusProvider instanceof RequestAwareStatusProviderInterface) {
                    $statuses = $statusProvider->getStatus($request);
                } else {
                    $statuses = $statusProvider->getStatus();
                }
                $status[$statusProviderId] = array_merge($status[$statusProviderId], $statuses);
            }
        }
        return $status;
    }

    /**
     * Runs through all status providers and returns all statuses collected, which are detailed.
     *
     * @return \TYPO3\CMS\Reports\Status[]
     */
    public function getDetailedSystemStatus()
    {
        $status = [];
        foreach ($this->statusProviders as $statusProviderId => $statusProviderList) {
            $status[$statusProviderId] = [];
            foreach ($statusProviderList as $statusProvider) {
                if ($statusProvider instanceof ExtendedStatusProviderInterface) {
                    $statuses = $statusProvider->getDetailedStatus();
                    $status[$statusProviderId] = array_merge($status[$statusProviderId], $statuses);
                }
            }
        }
        return $status;
    }

    /**
     * Determines the highest severity from the given statuses.
     *
     * @param array $statusCollection An array of \TYPO3\CMS\Reports\Status objects.
     * @return int The highest severity found from the statuses.
     */
    public function getHighestSeverity(array $statusCollection)
    {
        $highestSeverity = ReportStatus::NOTICE;
        foreach ($statusCollection as $statusProvider => $providerStatuses) {
            /** @var ReportStatus $status */
            foreach ($providerStatuses as $status) {
                if ($status->getSeverity() > $highestSeverity) {
                    $highestSeverity = $status->getSeverity();
                }
                // Reached the highest severity level, no need to go on
                if ($highestSeverity == ReportStatus::ERROR) {
                    break;
                }
            }
        }
        return $highestSeverity;
    }

    /**
     * Renders the system's status
     *
     * @param array $statusCollection An array of statuses as returned by the available status providers
     * @return string The system status as an HTML table
     */
    protected function renderStatus(array $statusCollection)
    {
        $content = '';
        $template = '
			<tr>
				<td class="###CLASS### col-xs-6">###HEADER###</td>
				<td class="###CLASS### col-xs-6">###STATUS###<br>###CONTENT###</td>
			</tr>
		';
        $statuses = $this->sortStatusProviders($statusCollection);
        $id = 0;
        foreach ($statuses as $provider => $providerStatus) {
            $providerState = $this->sortStatuses($providerStatus);
            $id++;
            $classes = [
                ReportStatus::NOTICE => 'notice',
                ReportStatus::INFO => 'info',
                ReportStatus::OK => 'success',
                ReportStatus::WARNING => 'warning',
                ReportStatus::ERROR => 'danger'
            ];
            $messages = '';
            /** @var ReportStatus $status */
            foreach ($providerState as $status) {
                $severity = $status->getSeverity();
                $messages .= strtr($template, [
                    '###CLASS###' => $classes[$severity],
                    '###HEADER###' => $status->getTitle(),
                    '###STATUS###' => $status->getValue(),
                    '###CONTENT###' => $status->getMessage()
                ]);
            }
            $header = '<h2>' . $provider . '</h2>';
            $table = '<table class="table table-striped table-hover">';
            $table .= '<tbody>' . $messages . '</tbody>';
            $table .= '</table>';

            $content .= $header . $table;
        }
        return $content;
    }

    /**
     * Sorts the status providers (alphabetically and puts primary status providers at the beginning)
     *
     * @param array $statusCollection A collection of statuses (with providers)
     * @return array The collection of statuses sorted by provider (beginning with provider "_install")
     */
    protected function sortStatusProviders(array $statusCollection)
    {
        // Extract the primary status collections, i.e. the status groups
        // that must appear on top of the status report
        // Change their keys to localized collection titles
        $primaryStatuses = [
            $this->getLanguageService()->getLL('status_typo3') => $statusCollection['typo3'],
            $this->getLanguageService()->getLL('status_system') => $statusCollection['system'],
            $this->getLanguageService()->getLL('status_security') => $statusCollection['security'],
            $this->getLanguageService()->getLL('status_configuration') => $statusCollection['configuration']
        ];
        unset($statusCollection['typo3'], $statusCollection['system'], $statusCollection['security'], $statusCollection['configuration']);
        // Assemble list of secondary status collections with left-over collections
        // Change their keys using localized labels if available
        // @todo extract into getLabel() method
        $secondaryStatuses = [];
        foreach ($statusCollection as $statusProviderId => $collection) {
            $label = '';
            if (strpos($statusProviderId, 'LLL:') === 0) {
                // Label provided by extension
                $label = $this->getLanguageService()->sL($statusProviderId);
            } else {
                // Generic label
                $label = $this->getLanguageService()->getLL('status_' . $statusProviderId);
            }
            $providerLabel = empty($label) ? $statusProviderId : $label;
            $secondaryStatuses[$providerLabel] = $collection;
        }
        // Sort the secondary status collections alphabetically
        ksort($secondaryStatuses);
        $orderedStatusCollection = array_merge($primaryStatuses, $secondaryStatuses);
        return $orderedStatusCollection;
    }

    /**
     * Sorts the statuses by severity
     *
     * @param array $statusCollection A collection of statuses per provider
     * @return array The collection of statuses sorted by severity
     */
    protected function sortStatuses(array $statusCollection)
    {
        $statuses = [];
        $sortTitle = [];
        $header = null;
        /** @var ReportStatus $status */
        foreach ($statusCollection as $status) {
            if ($status->getTitle() === 'TYPO3') {
                $header = $status;
                continue;
            }
            $statuses[] = $status;
            $sortTitle[] = $status->getSeverity();
        }
        array_multisort($sortTitle, SORT_DESC, $statuses);
        // Making sure that the core version information is always on the top
        if (is_object($header)) {
            array_unshift($statuses, $header);
        }
        return $statuses;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
