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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
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
    public function getStatus()
    {
        return $this->getStatusInternal(false);
    }

    /**
     * Returns the detailed status of an extension or (sub)system
     *
     * @return Status[]
     */
    public function getDetailedStatus()
    {
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
                case FlashMessage::ERROR:
                    $reportStatusTypes['error'][] = $message;
                    break;
                case FlashMessage::WARNING:
                    $reportStatusTypes['warning'][] = $message;
                    break;
                case FlashMessage::OK:
                    $reportStatusTypes['ok'][] = $message;
                    break;
                case FlashMessage::INFO:
                    $reportStatusTypes['information'][] = $message;
                    break;
                case FlashMessage::NOTICE:
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
                    $message .= '### ' . $statusObject->getTitle() . ': ' . $statusObject->getSeverity() . CRLF;
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

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
