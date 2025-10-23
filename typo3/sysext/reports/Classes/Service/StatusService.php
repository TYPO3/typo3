<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Reports\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\RequestAwareStatusProviderInterface;
use TYPO3\CMS\Reports\Status;

/**
 * Service for collecting and processing system status information
 *
 * @internal This is not part of the public API and may change at any time
 */
#[Autoconfigure(public: true)]
final readonly class StatusService
{
    public function __construct(
        protected StatusRegistry $statusRegistry,
        protected Registry $registry,
    ) {}

    /**
     * Runs through all status providers and returns all statuses collected.
     *
     * @param ServerRequestInterface|null $request
     * @return Status[][]
     */
    public function getSystemStatus(?ServerRequestInterface $request = null): array
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
     * @return Status[][]
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
     * @param array<string, array<string, Status>> $statusCollection An array of Status objects.
     * @return int The highest severity found from the statuses.
     */
    public function getHighestSeverity(array $statusCollection): int
    {
        $highestSeverity = ContextualFeedbackSeverity::NOTICE;
        foreach ($statusCollection as $providerStatuses) {
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
     * Collects system status and stores the highest severity in the registry.
     * This is useful for displaying warnings at login or in the backend.
     *
     * @param ServerRequestInterface|null $request
     */
    public function collectAndStoreSystemStatus(?ServerRequestInterface $request = null): void
    {
        $status = $this->getSystemStatus($request);
        $this->registry->set('tx_reports', 'status.highestSeverity', $this->getHighestSeverity($status));
    }

    /**
     * Sorts the status providers (alphabetically and puts primary status providers at the beginning)
     *
     * @param array<string, array<string, Status>> $statusCollection A collection of statuses (with providers)
     * @return array<string, array<string, Status>> The collection of statuses sorted by provider
     */
    public function sortStatusProviders(array $statusCollection): array
    {
        $languageService = $this->getLanguageService();

        // Extract the primary status collections, i.e. the status groups
        // that must appear on top of the status report
        // Change their keys to localized collection titles
        $primaryStatuses = [
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_typo3') => $statusCollection['typo3'] ?? [],
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_system') => $statusCollection['system'] ?? [],
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_security') => $statusCollection['security'] ?? [],
            $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_configuration') => $statusCollection['configuration'] ?? [],
        ];
        unset($statusCollection['typo3'], $statusCollection['system'], $statusCollection['security'], $statusCollection['configuration']);

        // Assemble list of secondary status collections with left-over collections
        // Change their keys using localized labels if available
        $secondaryStatuses = [];
        foreach ($statusCollection as $statusProviderId => $collection) {
            if (str_starts_with($statusProviderId, 'LLL:')) {
                // Label provided by extension
                $label = $languageService->sL($statusProviderId);
            } else {
                // Generic label
                // @todo phase this out
                $label = $languageService->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_' . $statusProviderId);
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
     * @param array<string, Status> $statusCollection A collection of statuses per provider
     * @return list<Status> The collection of statuses sorted by severity
     */
    public function sortStatuses(array $statusCollection): array
    {
        $statuses = [];
        $sortTitle = [];
        $header = null;
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
