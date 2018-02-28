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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * Post processes the warning messages found in about modules.
 */
class WarningMessagePostProcessor
{
    /**
     * Tries to get the highest severity of the system's status first, if
     * something is found it is assumed that the status update task is set up
     * properly or the status report has been checked manually and we take over
     * control over the system warning messages.
     *
     * @param array $warningMessages An array of messages related to already found issues.
     */
    public function displayWarningMessages_postProcess(array &$warningMessages)
    {
        if (!$this->getBackendUser()->isAdmin()) {
            return;
        }
        // Get highest severity
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $highestSeverity = $registry->get('tx_reports', 'status.highestSeverity', null);
        if ($highestSeverity !== null) {
            if ($highestSeverity > ReportStatus::OK) {
                // Display a message that there's something wrong and that
                // the admin should take a look at the detailed status report
                $this->getLanguageService()->includeLLFile('EXT:reports/Resources/Private/Language/locallang_reports.xlf');
                $reportModuleIdentifier = 'system_ReportsTxreportsm1';
                $reportModuleParameters = [
                    'tx_reports_system_reportstxreportsm1[extension]=tx_reports',
                    'tx_reports_system_reportstxreportsm1[report]=status',
                    'tx_reports_system_reportstxreportsm1[action]=detail',
                    'tx_reports_system_reportstxreportsm1[controller]=Report',
                ];
                $warningMessages['tx_reports_status_notification'] = sprintf(
                    $this->getLanguageService()->getLL('status_problemNotification'),
                    '<a href="javascript:top.goToModule(' . GeneralUtility::quoteJSvalue($reportModuleIdentifier) . ', 1, ' . GeneralUtility::quoteJSvalue('&' . implode('&', $reportModuleParameters)) . ');">',
                    '</a>'
                );
            }
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
