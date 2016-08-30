<?php
namespace TYPO3\CMS\Install\Report;

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

use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provides an environment status report
 */
class EnvironmentStatusReport implements StatusProviderInterface, ExtendedStatusProviderInterface
{
    /**
     * Compile environment status report
     *
     * @return \TYPO3\CMS\Reports\Status[]
     */
    public function getStatus()
    {
        return $this->getStatusInternal(false);
    }

    /**
     * Returns the detailed status of an extension or (sub)system
     *
     * @return \TYPO3\CMS\Reports\Status[]
     */
    public function getDetailedStatus()
    {
        return $this->getStatusInternal(true);
    }

    /**
     * @param $verbose
     * @return \TYPO3\CMS\Reports\Status[]
     * @throws \TYPO3\CMS\Install\Exception
     */
    protected function getStatusInternal($verbose)
    {
        /** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\Check */
        $statusCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Install\SystemEnvironment\Check::class);
        $statusObjects = $statusCheck->getStatus();

        /** @var $statusCheck \TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck */
        $databaseStatusCheck = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck::class);
        $statusObjects = array_merge($statusObjects, $databaseStatusCheck->getStatus());

        $reportStatusTypes = [
            'error' => [],
            'warning' => [],
            'ok' => [],
            'information' => [],
            'notice' => [],
        ];

        /** @var $statusObject \TYPO3\CMS\Install\Status\AbstractStatus */
        foreach ($statusObjects as $statusObject) {
            $severityIdentifier = $statusObject->getSeverity();
            if (empty($severityIdentifier) || !is_array($reportStatusTypes[$severityIdentifier])) {
                throw new \TYPO3\CMS\Install\Exception('Unknown reports severity type', 1362602560);
            }
            $reportStatusTypes[$severityIdentifier][] = $statusObject;
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
                    $message = $GLOBALS['LANG']->sL($pathToXliff . ':environment.status.message.' . $type);
                }
                $severity = constant('\TYPO3\CMS\Reports\Status::' . strtoupper($type));
                $statusArray[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \TYPO3\CMS\Reports\Status::class,
                    $GLOBALS['LANG']->sL($pathToXliff . ':environment.status.title'),
                    sprintf($GLOBALS['LANG']->sL($pathToXliff . ':environment.status.value'), $value),
                    $message,
                    $severity
                );
            }
        }

        return $statusArray;
    }
}
