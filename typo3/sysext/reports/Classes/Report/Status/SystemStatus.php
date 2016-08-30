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

/**
 * Performs several checks about the system's health
 */
class SystemStatus implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * Determines the Install Tool's status, mainly concerning its protection.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $this->executeAdminCommand();
        $statuses = [
            'PhpPeakMemory' => $this->getPhpPeakMemoryStatus(),
            'PhpModules' => $this->getMissingPhpModulesOfExtensions()
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
        $command = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('adminCmd');
        switch ($command) {
            case 'clear_peak_memory_usage_flag':
                /** @var $registry \TYPO3\CMS\Core\Registry */
                $registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
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
        /** @var $registry \TYPO3\CMS\Core\Registry */
        $registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        $peakMemoryUsage = $registry->get('core', 'reports-peakMemoryUsage');
        $memoryLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));
        $value = $GLOBALS['LANG']->getLL('status_ok');
        $message = '';
        $severity = \TYPO3\CMS\Reports\Status::OK;
        $bytesUsed = $peakMemoryUsage['used'];
        $percentageUsed = $memoryLimit ? number_format($bytesUsed / $memoryLimit * 100, 1) . '%' : '?';
        $dateOfPeak = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $peakMemoryUsage['tstamp']);
        $urlOfPeak = '<a href="' . htmlspecialchars($peakMemoryUsage['url']) . '">' . htmlspecialchars($peakMemoryUsage['url']) . '</a>';
        $clearFlagUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=clear_peak_memory_usage_flag';
        if ($peakMemoryUsage['used']) {
            $message = sprintf($GLOBALS['LANG']->getLL('status_phpPeakMemoryTooHigh'), \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($peakMemoryUsage['used']), $percentageUsed, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($memoryLimit), $dateOfPeak, $urlOfPeak);
            $message .= ' <a href="' . $clearFlagUrl . '">' . $GLOBALS['LANG']->getLL('status_phpPeakMemoryClearFlag') . '</a>.';
            $severity = \TYPO3\CMS\Reports\Status::WARNING;
            $value = $percentageUsed;
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_phpPeakMemory'), $value, $message, $severity);
    }

    /**
     * Reports whether extensions need additional PHP modules different from standard core requirements
     *
     * @return \TYPO3\CMS\Reports\Status A status of missing PHP modules
     */
    protected function getMissingPhpModulesOfExtensions()
    {
        $modules = [];
        if (is_array(${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']})) {
            foreach (${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']} as $classData) {
                $hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
                $modules = $hookObject->setRequiredPhpModules($modules, $this);
            }
        }
        $missingPhpModules = [];
        foreach ($modules as $module) {
            if (is_array($module)) {
                $detectedSubmodules = false;
                foreach ($module as $submodule) {
                    if (extension_loaded($submodule)) {
                        $detectedSubmodules = true;
                    }
                }
                if ($detectedSubmodules === false) {
                    $missingPhpModules[] = sprintf($GLOBALS['LANG']->getLL('status_phpModulesGroup'), '(' . implode(', ', $module) . ')');
                }
            } elseif (!extension_loaded($module)) {
                $missingPhpModules[] = $module;
            }
        }
        if (!empty($missingPhpModules)) {
            $value = $GLOBALS['LANG']->getLL('status_phpModulesMissing');
            $message = sprintf($GLOBALS['LANG']->getLL('status_phpModulesList'), implode(', ', $missingPhpModules));
            $message .= ' ' . $GLOBALS['LANG']->getLL('status_phpModulesInfo');
            $severity = \TYPO3\CMS\Reports\Status::ERROR;
        } else {
            $value = $GLOBALS['LANG']->getLL('status_phpModulesPresent');
            $message = '';
            $severity = \TYPO3\CMS\Reports\Status::OK;
        }
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Status::class, $GLOBALS['LANG']->getLL('status_phpModules'), $value, $message, $severity);
    }
}
