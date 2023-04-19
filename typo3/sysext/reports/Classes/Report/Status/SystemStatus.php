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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;
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
     * @return Status[] List of statuses
     */
    public function getStatus(): array
    {
        $statuses = [
            'PhpModules' => $this->getMissingPhpModulesOfExtensions(),
        ];
        return $statuses;
    }

    public function getLabel(): string
    {
        return 'system';
    }

    /**
     * Reports whether extensions need additional PHP modules different from standard core requirements
     *
     * @return \TYPO3\CMS\Reports\Status A status of missing PHP modules
     */
    protected function getMissingPhpModulesOfExtensions()
    {
        $modules = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules'] ?? null)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules'] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
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
                    $missingPhpModules[] = sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModulesGroup'), '(' . implode(', ', $module) . ')');
                }
            } elseif (!extension_loaded($module)) {
                $missingPhpModules[] = $module;
            }
        }
        if (!empty($missingPhpModules)) {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModulesMissing');
            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModulesList'), implode(', ', $missingPhpModules));
            $message .= ' ' . $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModulesInfo');
            $severity = ContextualFeedbackSeverity::ERROR;
        } else {
            $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModulesPresent');
            $message = '';
            $severity = ContextualFeedbackSeverity::OK;
        }
        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_phpModules'), $value, $message, $severity);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
