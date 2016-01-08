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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs several checks about the system's health
 */
class SystemStatus implements StatusProviderInterface
{
    /**
     * Determines the Install Tool's status, mainly concerning its protection.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $this->executeAdminCommand();
        $statuses = array(
            'PhpPeakMemory' => $this->getPhpPeakMemoryStatus(),
            'PhpModules' => $this->getMissingPhpModulesOfExtensions()
        );
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
     * Reports whether extensions need additional PHP modules different from standard core requirements
     *
     * @return \TYPO3\CMS\Reports\Status A status of missing PHP modules
     */
    protected function getMissingPhpModulesOfExtensions()
    {
        $modules = array();
        if (is_array(${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']})) {
            foreach (${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']} as $classData) {
                $hookObject = GeneralUtility::getUserObj($classData);
                $modules = $hookObject->setRequiredPhpModules($modules, $this);
            }
        }
        $missingPhpModules = array();
        foreach ($modules as $module) {
            if (is_array($module)) {
                $detectedSubmodules = false;
                foreach ($module as $submodule) {
                    if (extension_loaded($submodule)) {
                        $detectedSubmodules = true;
                    }
                }
                if ($detectedSubmodules === false) {
                    $missingPhpModules[] = sprintf($this->getLanguageService()->getLL('status_phpModulesGroup'), '(' . implode(', ', $module) . ')');
                }
            } elseif (!extension_loaded($module)) {
                $missingPhpModules[] = $module;
            }
        }
        if (!empty($missingPhpModules)) {
            $value = $this->getLanguageService()->getLL('status_phpModulesMissing');
            $message = sprintf($this->getLanguageService()->getLL('status_phpModulesList'), implode(', ', $missingPhpModules));
            $message .= ' ' . $this->getLanguageService()->getLL('status_phpModulesInfo');
            $severity = ReportStatus::ERROR;
        } else {
            $value = $this->getLanguageService()->getLL('status_phpModulesPresent');
            $message = '';
            $severity = ReportStatus::OK;
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_phpModules'), $value, $message, $severity);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
