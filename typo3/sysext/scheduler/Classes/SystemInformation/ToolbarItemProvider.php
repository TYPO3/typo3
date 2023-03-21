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

namespace TYPO3\CMS\Scheduler\SystemInformation;

use TYPO3\CMS\Backend\Backend\Event\SystemInformationToolbarCollectorEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;

/**
 * Event listener to display information about last automated run, as stored in the system registry.
 */
final class ToolbarItemProvider
{
    /**
     * Scheduler last run registry information
     */
    protected array $lastRunInformation = [];

    /**
     * Gather initial information
     */
    public function __construct()
    {
        $this->lastRunInformation = GeneralUtility::makeInstance(Registry::class)->get('tx_scheduler', 'lastRun', []);
    }

    public function getItem(SystemInformationToolbarCollectorEvent $event): void
    {
        $systemInformationToolbarItem = $event->getToolbarItem();
        // No tasks configured, so nothing is shown at all
        if (!$this->hasConfiguredTasks()) {
            return;
        }
        $languageService = $this->getLanguageService();

        if (!$this->schedulerWasExecuted()) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            // Display system message if the Scheduler has never yet run
            $moduleIdentifier = 'scheduler_setupcheck';
            $systemInformationToolbarItem->addSystemMessage(
                sprintf(
                    $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:systemmessage.noLastRun'),
                    (string)$uriBuilder->buildUriFromRoute($moduleIdentifier)
                ),
                InformationStatus::STATUS_WARNING,
                1,
                $moduleIdentifier,
            );
        } else {
            // Display information about the last Scheduler execution
            if (!$this->lastRunInfoExists()) {
                // Show warning if the information of the last run is incomplete
                $message = $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.incompleteLastRun');
                $severity = InformationStatus::STATUS_WARNING;
            } else {
                $startDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $this->lastRunInformation['start']);
                $startTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $this->lastRunInformation['start']);
                $duration = BackendUtility::calcAge(
                    $this->lastRunInformation['end'] - $this->lastRunInformation['start'],
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                );
                $severity = '';
                $label = 'automatically';
                if ($this->lastRunInformation['type'] === 'manual') {
                    $label = 'manually';
                    $severity = InformationStatus::STATUS_INFO;
                }
                $type = $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.' . $label);
                $message = sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:systeminformation.lastRunValue'), $startDate, $startTime, $duration, $type);
            }
            $systemInformationToolbarItem->addSystemInformation(
                'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:systeminformation.lastRunLabel',
                $message,
                'actions-play',
                $severity
            );
        }
    }

    /**
     * Check whether the scheduler was already executed
     */
    private function schedulerWasExecuted(): bool
    {
        return !empty($this->lastRunInformation);
    }

    /**
     * Check if the last scheduler run array contains all information
     */
    private function lastRunInfoExists(): bool
    {
        return !empty($this->lastRunInformation['end'])
            || !empty($this->lastRunInformation['start'])
            || !empty($this->lastRunInformation['type']);
    }

    /**
     * See if there are any tasks configured at all.
     */
    private function hasConfiguredTasks(): bool
    {
        return GeneralUtility::makeInstance(SchedulerTaskRepository::class)->hasTasks();
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
