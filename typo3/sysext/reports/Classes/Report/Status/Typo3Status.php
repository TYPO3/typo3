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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;

/**
 * Performs basic checks about the TYPO3 install
 */
class Typo3Status implements \TYPO3\CMS\Reports\StatusProviderInterface
{
    /**
     * Returns the status for this report
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $statuses = [
            'oldXclassStatus' => $this->getOldXclassUsageStatus(),
            'registeredXclass' => $this->getRegisteredXclassStatus(),
            'compatibility6' => $this->getCompatibility6Status(),
        ];
        return $statuses;
    }

    /**
     * Check for usage of old way of implementing XCLASSes
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function getOldXclassUsageStatus()
    {
        $message = '';
        $value = $GLOBALS['LANG']->getLL('status_none');
        $severity = ReportStatus::OK;

        $xclasses = array_merge(
            (array)$GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'],
            (array)$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']
        );

        $numberOfXclasses = count($xclasses);
        if ($numberOfXclasses > 0) {
            $value = sprintf($GLOBALS['LANG']->getLL('status_oldXclassUsageFound'), $numberOfXclasses);
            $message = $GLOBALS['LANG']->getLL('status_oldXclassUsageFound_message') . '<br />';
            $message .= '<ol><li>' . implode('</li><li>', $xclasses) . '</li></ol>';
            $severity = ReportStatus::NOTICE;
        }

        return GeneralUtility::makeInstance(
            ReportStatus::class,
            $GLOBALS['LANG']->getLL('status_oldXclassUsage'),
            $value,
            $message,
            $severity
        );
    }

    /**
     * List any Xclasses registered in the system
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function getRegisteredXclassStatus()
    {
        $message = '';
        $value = $GLOBALS['LANG']->getLL('status_none');
        $severity = ReportStatus::OK;

        $xclassFoundArray = [];
        if (array_key_exists('Objects', $GLOBALS['TYPO3_CONF_VARS']['SYS'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] as $originalClass => $override) {
                if (array_key_exists('className', $override)) {
                    $xclassFoundArray[$originalClass] = $override['className'];
                }
            }
        }
        if (!empty($xclassFoundArray)) {
            $value = $GLOBALS['LANG']->getLL('status_xclassUsageFound');
            $message = $GLOBALS['LANG']->getLL('status_xclassUsageFound_message') . '<br />';
            $message .= '<ol>';
            foreach ($xclassFoundArray as $originalClass => $xClassName) {
                $messageDetail = sprintf(
                    $GLOBALS['LANG']->getLL('status_xclassUsageFound_message_detail'),
                    '<code>' . htmlspecialchars($originalClass) . '</code>',
                    '<code>' . htmlspecialchars($xClassName) . '</code>'
                );
                $message .= '<li>' . $messageDetail . '</li>';
            }
            $message .= '</ol>';
            $severity = ReportStatus::NOTICE;
        }

        return GeneralUtility::makeInstance(
            ReportStatus::class,
            $GLOBALS['LANG']->getLL('status_xclassUsage'),
            $value,
            $message,
            $severity
        );
    }

    /**
     * Check for usage of EXT:compatibility6
     *
     * @return \TYPO3\CMS\Reports\Status
     */
    protected function getCompatibility6Status()
    {
        $message = '';
        $value = $GLOBALS['LANG']->getLL('status_disabled');
        $severity = ReportStatus::OK;

        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('compatibility6')) {
            $value = $GLOBALS['LANG']->getLL('status_enabled');
            $message = $GLOBALS['LANG']->getLL('status_compatibility6Usage_message');
            $severity = ReportStatus::WARNING;
        }

        return GeneralUtility::makeInstance(
            ReportStatus::class,
            $GLOBALS['LANG']->getLL('status_compatibility6Usage'),
            $value,
            $message,
            $severity
        );
    }
}
