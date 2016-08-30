<?php
namespace TYPO3\CMS\Reports\Task;

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
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly to determine the system's status.
 */
class SystemStatusUpdateTask extends AbstractTask
{
    /**
     * Email addresses to send email notification to in case we find problems with
     * the system.
     *
     * @var string
     */
    protected $notificationEmail = null;

    /**
     * Executes the System Status Update task, determining the highest severity of
     * status reports and saving that to the registry to be displayed at login
     * if necessary.
     *
     * @see \TYPO3\CMS\Scheduler\Task\AbstractTask::execute()
     */
    public function execute()
    {
        /** @var $registry \TYPO3\CMS\Core\Registry */
        $registry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Registry::class);
        /** @var $statusReport \TYPO3\CMS\Reports\Report\Status\Status */
        $statusReport = GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Report\Status\Status::class);
        $systemStatus = $statusReport->getDetailedSystemStatus();
        $highestSeverity = $statusReport->getHighestSeverity($systemStatus);
        $registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
        if ($highestSeverity > Status::OK) {
            $this->sendNotificationEmail($systemStatus);
        }
        return true;
    }

    /**
     * Gets the notification email addresses.
     *
     * @return string Notification email addresses.
     */
    public function getNotificationEmail()
    {
        return $this->notificationEmail;
    }

    /**
     * Sets the notification email address.
     *
     * @param string $notificationEmail Notification email address.
     * @return void
     */
    public function setNotificationEmail($notificationEmail)
    {
        $this->notificationEmail = $notificationEmail;
    }

    /**
     * Sends a notification email, reporting system issues.
     *
     * @param Status[] $systemStatus Array of statuses
     * @return void
     */
    protected function sendNotificationEmail(array $systemStatus)
    {
        $systemIssues = [];
        foreach ($systemStatus as $statusProvider) {
            /** @var Status $status */
            foreach ($statusProvider as $status) {
                if ($status->getSeverity() > Status::OK) {
                    $systemIssues[] = (string)$status . CRLF . $status->getMessage() . CRLF . CRLF;
                }
            }
        }
        $notificationEmails = GeneralUtility::trimExplode(LF, $this->notificationEmail, true);
        $sendEmailsTo = [];
        foreach ($notificationEmails as $notificationEmail) {
            $sendEmailsTo[] = $notificationEmail;
        }
        $subject = sprintf($GLOBALS['LANG']->getLL('status_updateTask_email_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $message = sprintf($GLOBALS['LANG']->getLL('status_problemNotification'), '', '');
        $message .= CRLF . CRLF;
        $message .= $GLOBALS['LANG']->getLL('status_updateTask_email_site') . ': ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $message .= CRLF . CRLF;
        $message .= $GLOBALS['LANG']->getLL('status_updateTask_email_issues') . ': ' . CRLF;
        $message .= implode(CRLF, $systemIssues);
        $message .= CRLF . CRLF;
        $from = MailUtility::getSystemFrom();
        /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        $mail->setFrom($from);
        $mail->setTo($sendEmailsTo);
        $mail->setSubject($subject);
        $mail->setBody($message);
        $mail->send();
    }
}
