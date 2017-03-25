<?php
namespace TYPO3\CMS\Compatibility7\Report\Status;

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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Adds peak memory stats from frontend call.
 */
class SystemStatus implements StatusProviderInterface
{
    /**
     * Main API method
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $this->executeAdminCommand();
        $statuses = [
            'PhpPeakMemory' => $this->getPhpPeakMemoryStatus(),
        ];
        return $statuses;
    }

    /**
     * Executes commands like clearing the memory status flag
     *
     * @return void
     */
    protected function executeAdminCommand()
    {
        $command = GeneralUtility::_GET('adminCmd');
        switch ($command) {
            case 'clear_peak_memory_usage_flag':
                /** @var Registry $registry */
                $registry = GeneralUtility::makeInstance(Registry::class);
                $registry->remove('core', 'reports-peakMemoryUsage');
                break;
            default:
                // Do nothing
        }
    }

    /**
     * Checks if there was a request in the past which approached the memory limit
     *
     * @return \TYPO3\CMS\Reports\Status A status of whether the memory limit was approached by one of the requests
     */
    protected function getPhpPeakMemoryStatus()
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $peakMemoryUsage = $registry->get('core', 'reports-peakMemoryUsage');
        $memoryLimit = GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));
        $value = $this->getLanguageService()->getLL('status_ok');
        $message = '';
        $severity = ReportStatus::OK;
        $bytesUsed = $peakMemoryUsage['used'];
        $percentageUsed = $memoryLimit ? number_format($bytesUsed / $memoryLimit * 100, 1) . '%' : '?';
        $dateOfPeak = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $peakMemoryUsage['tstamp']);
        $urlOfPeak = '<a href="' . htmlspecialchars($peakMemoryUsage['url']) . '">' . htmlspecialchars($peakMemoryUsage['url']) . '</a>';
        $clearFlagUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=clear_peak_memory_usage_flag';
        if ($peakMemoryUsage['used']) {
            $message = sprintf($this->getLanguageService()->getLL('status_phpPeakMemoryTooHigh'), GeneralUtility::formatSize($peakMemoryUsage['used']), $percentageUsed, GeneralUtility::formatSize($memoryLimit), $dateOfPeak, $urlOfPeak);
            $message .= ' <a href="' . $clearFlagUrl . '">' . $this->getLanguageService()->getLL('status_phpPeakMemoryClearFlag') . '</a>.';
            $severity = ReportStatus::WARNING;
            $value = $percentageUsed;
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_phpPeakMemory'), $value, $message, $severity);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
