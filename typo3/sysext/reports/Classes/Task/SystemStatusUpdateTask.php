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

namespace TYPO3\CMS\Reports\Task;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly to determine the system's status.
 * @internal This class is a specific scheduler task implementation and is not considered part of the Public TYPO3 API.
 */
class SystemStatusUpdateTask extends AbstractTask
{
    /**
     * Email addresses to send email notification to in case we find problems with
     * the system.
     *
     * @var string
     */
    protected $notificationEmail;

    /**
     * Checkbox for to send all types of notification, not only problems
     *
     * @var bool
     */
    protected $notificationAll = false;

    /**
     * Executes the System Status Update task, determining the highest severity of
     * status reports and saving that to the registry to be displayed at login
     * if necessary.
     *
     * @see \TYPO3\CMS\Scheduler\Task\AbstractTask::execute()
     */
    public function execute()
    {
        $registry = GeneralUtility::makeInstance(Registry::class);
        $statusReport = GeneralUtility::makeInstance(\TYPO3\CMS\Reports\Report\Status\Status::class);
        $systemStatus = $statusReport->getDetailedSystemStatus();
        $highestSeverity = $statusReport->getHighestSeverity($systemStatus);
        $registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
        if (($highestSeverity > Status::OK) || $this->getNotificationAll()) {
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
     */
    public function setNotificationEmail($notificationEmail)
    {
        $this->notificationEmail = $notificationEmail;
    }

    /**
     * Sends a notification email, reporting system issues.
     *
     * @param Status[][] $systemStatus Array of statuses
     */
    protected function sendNotificationEmail(array $systemStatus)
    {
        $systemIssues = [];
        foreach ($systemStatus as $statusProvider) {
            foreach ($statusProvider as $status) {
                if ($this->getNotificationAll() || ($status->getSeverity() > Status::OK)) {
                    $systemIssues[] = (string)$status . CRLF . $status->getMessage() . CRLF . CRLF;
                }
            }
        }
        $notificationEmails = GeneralUtility::trimExplode(LF, $this->notificationEmail, true);
        $sendEmailsTo = [];
        foreach ($notificationEmails as $notificationEmail) {
            $sendEmailsTo[] = new Address($notificationEmail);
        }
        $subject = sprintf($this->getLanguageService()->getLL('status_updateTask_email_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $message = sprintf($this->getLanguageService()->getLL($this->getNotificationAll() ? 'status_allNotification' : 'status_problemNotification'), '', '');
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->getLL('status_updateTask_email_site') . ': ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->getLL('status_updateTask_email_issues') . ': ' . CRLF;
        $message .= implode(CRLF, $systemIssues);
        $message .= CRLF . CRLF;

        $templateConfiguration = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        $templateConfiguration['templateRootPaths'][20] = 'EXT:reports/Resources/Private/Templates/Email/';

        $email = GeneralUtility::makeInstance(FluidEmail::class, new TemplatePaths($templateConfiguration));
        $email
            ->to(...$sendEmailsTo)
            ->format('plain')
            ->subject($subject)
            ->setTemplate('Report')
            ->assign('message', $message);
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            $email->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        GeneralUtility::makeInstance(Mailer::class)->send($email);
    }

    /**
     * @return bool
     */
    public function getNotificationAll(): bool
    {
        return $this->notificationAll;
    }

    /**
     * @param bool $notificationAll
     */
    public function setNotificationAll(bool $notificationAll)
    {
        $this->notificationAll = $notificationAll;
    }
}
