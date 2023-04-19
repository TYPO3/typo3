<?php

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

namespace TYPO3\CMS\Reports\Report\Status;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\RequestAwareReportInterface;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * The status report
 */
class Status implements RequestAwareReportInterface
{
    /**
     * Constructor for class tx_reports_report_Status
     */
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly StatusRegistry $statusRegistry
    ) {
        // This needs to be kept during v12 as backwards-compatibility for people still using getLL() in their status reports
        $this->getLanguageService()->includeLLFile('EXT:reports/Resources/Private/Language/locallang_reports.xlf');
    }

    /**
     * Takes care of creating / rendering the status report
     *
     * @param ServerRequestInterface|null $request the currently handled request
     * @return string The status report as HTML
     */
    public function getReport(ServerRequestInterface $request = null): string
    {
        $status = $this->getSystemStatus($request);
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('tx_reports', 'status.highestSeverity', $this->getHighestSeverity($status));
        return $this->renderStatus($request, $status);
    }

    public function getIdentifier(): string
    {
        return 'status';
    }

    public function getTitle(): string
    {
        return 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_title';
    }

    public function getDescription(): string
    {
        return 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_report_description';
    }

    public function getIconIdentifier(): string
    {
        return 'module-reports';
    }

    /**
     * Runs through all status providers and returns all statuses collected.
     *
     * @param ServerRequestInterface|null $request
     * @return ReportStatus[][]
     */
    public function getSystemStatus(ServerRequestInterface $request = null): array
    {
        $status = [];
        foreach ($this->statusRegistry->getProviders() as $statusProvider) {
            $statusProviderId = $statusProvider->getLabel();
            $status[$statusProviderId] ??= [];
            if ($statusProvider instanceof RequestAwareStatusProviderInterface) {
                $statuses = $statusProvider->getStatus($request);
            } else {
                $statuses = $statusProvider->getStatus();
            }
            $status[$statusProviderId] = array_merge($status[$statusProviderId], $statuses);
        }
        return $status;
    }

    /**
     * Runs through all status providers and returns all statuses collected, which are detailed.
     *
     * @return ReportStatus[][]
     */
    public function getDetailedSystemStatus(): array
    {
        $status = [];
        foreach ($this->statusRegistry->getProviders() as $statusProvider) {
            $statusProviderId = $statusProvider->getLabel();
            if ($statusProvider instanceof ExtendedStatusProviderInterface) {
                $statuses = $statusProvider->getDetailedStatus();
                $status[$statusProviderId] = array_merge($status[$statusProviderId] ?? [], $statuses);
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
    public function getHighestSeverity(array $statusCollection): int
    {
        $highestSeverity = ContextualFeedbackSeverity::NOTICE;
        foreach ($statusCollection as $providerStatuses) {
            /** @var ReportStatus $status */
            foreach ($providerStatuses as $status) {
                if ($status->getSeverity()->value > $highestSeverity->value) {
                    $highestSeverity = $status->getSeverity();
                }
                // Reached the highest severity level, no need to go on
                if ($highestSeverity === ContextualFeedbackSeverity::ERROR) {
                    break;
                }
            }
        }
        return $highestSeverity->value;
    }

    /**
     * Renders the system's status
     *
     * @param ServerRequestInterface $request Incoming request
     * @param array $statusCollection An array of statuses as returned by the available status providers
     * @return string The system status as an HTML table
     */
    protected function renderStatus(ServerRequestInterface $request, array $statusCollection): string
    {
        // Apply sorting to collection and the providers
        $statusCollection = $this->sortStatusProviders($statusCollection);

        foreach ($statusCollection as &$statuses) {
            $statuses = $this->sortStatuses($statuses);
        }
        unset($statuses);

        $view = $this->backendViewFactory->create($request);
        return $view->assignMultiple([
            'statusCollection' => $statusCollection,
            'severityIconMapping' => [
                ContextualFeedbackSeverity::NOTICE->value => 'actions-info',
                ContextualFeedbackSeverity::INFO->value => 'actions-info',
                ContextualFeedbackSeverity::OK->value => 'actions-check',
                ContextualFeedbackSeverity::WARNING->value => 'actions-exclamation',
                ContextualFeedbackSeverity::ERROR->value => 'actions-exclamation',
            ],
        ])->render('StatusReport');
    }

    /**
     * Sorts the status providers (alphabetically and puts primary status providers at the beginning)
     *
     * @param array $statusCollection A collection of statuses (with providers)
     * @return array The collection of statuses sorted by provider (beginning with provider "_install")
     */
    protected function sortStatusProviders(array $statusCollection): array
    {
        // Extract the primary status collections, i.e. the status groups
        // that must appear on top of the status report
        // Change their keys to localized collection titles
        $primaryStatuses = [
            $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_typo3') => $statusCollection['typo3'],
            $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_system') => $statusCollection['system'],
            $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_security') => $statusCollection['security'],
            $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_configuration') => $statusCollection['configuration'],
        ];
        unset($statusCollection['typo3'], $statusCollection['system'], $statusCollection['security'], $statusCollection['configuration']);
        // Assemble list of secondary status collections with left-over collections
        // Change their keys using localized labels if available
        $secondaryStatuses = [];
        foreach ($statusCollection as $statusProviderId => $collection) {
            if (str_starts_with($statusProviderId, 'LLL:')) {
                // Label provided by extension
                $label = $this->getLanguageService()->sL($statusProviderId);
            } else {
                // Generic label
                // @todo phase this out
                $label = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_' . $statusProviderId);
            }
            $providerLabel = empty($label) ? $statusProviderId : $label;
            $secondaryStatuses[$providerLabel] = $collection;
        }
        // Sort the secondary status collections alphabetically
        ksort($secondaryStatuses);
        return array_merge($primaryStatuses, $secondaryStatuses);
    }

    /**
     * Sorts the statuses by severity
     *
     * @param array $statusCollection A collection of statuses per provider
     * @return array The collection of statuses sorted by severity
     */
    protected function sortStatuses(array $statusCollection): array
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
