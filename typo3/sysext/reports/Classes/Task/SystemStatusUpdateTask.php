<?php

declare(strict_types=1);

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
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
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
    protected $notificationEmail = '';

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
        if (($highestSeverity > ContextualFeedbackSeverity::OK->value) || $this->notificationAll) {
            $this->sendNotificationEmail($systemStatus);
        }
        return true;
    }

    /**
     * Sends a notification email, reporting system issues.
     *
     * @param Status[][] $systemStatus Array of statuses
     */
    protected function sendNotificationEmail(array $systemStatus): void
    {
        $systemIssues = [];
        foreach ($systemStatus as $statusProvider) {
            foreach ($statusProvider as $status) {
                if ($this->notificationAll || ($status->getSeverity()->value > ContextualFeedbackSeverity::OK->value)) {
                    $systemIssues[] = (string)$status . CRLF . $status->getMessage() . CRLF . CRLF;
                }
            }
        }
        $notificationEmails = GeneralUtility::trimExplode(LF, $this->notificationEmail, true);
        $sendEmailsTo = [];
        foreach ($notificationEmails as $notificationEmail) {
            $sendEmailsTo[] = new Address($notificationEmail);
        }
        $subject = sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTask_email_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $message = sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:' . ($this->notificationAll ? 'status_allNotification' : 'status_problemNotification')), '', '');
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTask_email_site') . ': ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTask_email_issues') . ': ' . CRLF;
        $message .= implode(CRLF, $systemIssues);
        $message .= CRLF . CRLF;

        $templatePaths = new TemplatePaths();
        $templatePaths->setTemplateRootPaths(array_replace(
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'] ?? [],
            [20 => 'EXT:reports/Resources/Private/Templates/Email/'],
        ));
        $templatePaths->setLayoutRootPaths($GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'] ?? []);
        $templatePaths->setPartialRootPaths($GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'] ?? []);

        $email = GeneralUtility::makeInstance(FluidEmail::class, $templatePaths);
        $email
            ->to(...$sendEmailsTo)
            ->format('plain')
            ->subject($subject)
            ->setTemplate('Report')
            ->assign('message', $message);
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $email->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        // TODO: DI should be used to inject the MailerInterface
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);
    }

    public function getAdditionalInformation()
    {
        return sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateAdditionalInformation'), preg_replace('#\s+#', ', ', trim($this->notificationEmail)));
    }

    public function getTaskParameters(): array
    {
        return [
            'tx_reports_notification_email' => $this->notificationEmail,
            'tx_reports_notification_all' => $this->notificationAll,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->notificationEmail = $parameters['notificationEmail'] ?? $parameters['tx_reports_notification_email'] ?? '';
        $this->notificationAll = (bool)($parameters['notificationAll'] ?? $parameters['tx_reports_notification_all'] ?? false);
    }

    public function validateTaskParameters(array $parameters): bool
    {
        $validInput = true;
        $notificationEmails = GeneralUtility::trimExplode(LF, $parameters['tx_reports_notification_email'] ?? '', true);
        foreach ($notificationEmails as $notificationEmail) {
            if (!GeneralUtility::validEmail($notificationEmail)) {
                $validInput = false;
                break;
            }
        }
        if (!$validInput || empty($parameters['tx_reports_notification_email'] ?? '')) {
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->addMessage(
                GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskField_notificationEmails_invalid'), '', ContextualFeedbackSeverity::ERROR)
            );
            $validInput = false;
        }
        return $validInput;
    }
}
