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

namespace TYPO3\CMS\Install\Report;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides an environment status report
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class EnvironmentStatusReport implements StatusProviderInterface, ExtendedStatusProviderInterface
{
    /**
     * Compile environment status report
     *
     * @return Status[]
     */
    public function getStatus(): array
    {
        if (Environment::isCli()) {
            return [];
        }
        return $this->getStatusInternal(false);
    }

    public function getLabel(): string
    {
        return 'system';
    }

    /**
     * Returns the detailed status of an extension or (sub)system
     *
     * @return Status[]
     */
    public function getDetailedStatus()
    {
        if (Environment::isCli()) {
            return [];
        }
        return $this->getStatusInternal(true);
    }

    /**
     * @param bool $verbose
     * @return Status[]
     */
    protected function getStatusInternal($verbose)
    {
        $statusMessageQueue = new FlashMessageQueue('install');
        foreach (GeneralUtility::makeInstance(Check::class)->getStatus() as $message) {
            $statusMessageQueue->enqueue($message);
        }
        foreach (GeneralUtility::makeInstance(SetupCheck::class)->getStatus() as $message) {
            $statusMessageQueue->enqueue($message);
        }
        foreach (GeneralUtility::makeInstance(DatabaseCheck::class)->getStatus() as $message) {
            $statusMessageQueue->enqueue($message);
        }
        $reportStatusTypes = [
            'error' => [],
            'warning' => [],
            'ok' => [],
            'information' => [],
            'notice' => [],
        ];
        foreach ($statusMessageQueue->toArray() as $message) {
            switch ($message->getSeverity()) {
                case ContextualFeedbackSeverity::ERROR:
                    $reportStatusTypes['error'][] = $message;
                    break;
                case ContextualFeedbackSeverity::WARNING:
                    $reportStatusTypes['warning'][] = $message;
                    break;
                case ContextualFeedbackSeverity::OK:
                    $reportStatusTypes['ok'][] = $message;
                    break;
                case ContextualFeedbackSeverity::INFO:
                    $reportStatusTypes['information'][] = $message;
                    break;
                case ContextualFeedbackSeverity::NOTICE:
                    $reportStatusTypes['notice'][] = $message;
                    break;
            }
        }

        $statusArray = [];
        foreach ($reportStatusTypes as $type => $statusObjects) {
            $value = count($statusObjects);
            $message = '';
            if ($verbose) {
                foreach ($statusObjects as $statusObject) {
                    $message .= '### ' . $statusObject->getTitle() . ': ' . $statusObject->getSeverity()->value . CRLF;
                }
            }

            if ($value > 0) {
                $pathToXliff = 'LLL:EXT:install/Resources/Private/Language/Report/locallang.xlf';
                // Map information type to abbreviation which is used in \TYPO3\CMS\Reports\Status class
                if ($type === 'information') {
                    $type = 'info';
                }
                if (!$verbose) {
                    $message = $this->getLanguageService()->sL($pathToXliff . ':environment.status.message.' . $type);
                }
                $severity = constant('\TYPO3\CMS\Reports\Status::' . strtoupper($type));
                $statusArray[] = GeneralUtility::makeInstance(
                    Status::class,
                    $this->getLanguageService()->sL($pathToXliff . ':environment.status.title'),
                    sprintf($this->getLanguageService()->sL($pathToXliff . ':environment.status.value'), $value),
                    $message,
                    $severity
                );
            }
        }

        return $statusArray;
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
