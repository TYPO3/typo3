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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\TemplatedEmailFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Service\StatusService;
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
        $statusService = GeneralUtility::makeInstance(StatusService::class);
        $systemStatus = $statusService->getDetailedSystemStatus();
        $highestSeverity = $statusService->getHighestSeverity($systemStatus);
        $registry = GeneralUtility::makeInstance(Registry::class);
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
        $subject = sprintf($this->getLanguageService()->sL('reports.reports:status_updateTask_email_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
        $message = sprintf($this->getLanguageService()->sL('reports.reports:' . ($this->notificationAll ? 'status_allNotification' : 'status_problemNotification')), '', '');
        if (Environment::isCli()) {
            $message .= CRLF . CRLF;
            $message .= $this->getLanguageService()->sL('reports.reports:status_problem_notification_cli_disclaimer');
        }
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->sL('reports.reports:status_updateTask_email_site') . ': ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        $message .= CRLF . CRLF;
        $message .= $this->getLanguageService()->sL('reports.reports:status_updateTask_email_issues') . ': ' . CRLF;
        $message .= implode(CRLF, $systemIssues);
        $message .= CRLF . CRLF;

        $request = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface ? $GLOBALS['TYPO3_REQUEST'] : null;
        // @todo DI should be used to inject the MailerInterface in v15.0
        $email = GeneralUtility::makeInstance(TemplatedEmailFactory::class)->createWithOverrides(
            [20 => 'EXT:reports/Resources/Private/Templates/Email/'],
            [],
            [],
            $request,
        );
        $email
            ->to(...$sendEmailsTo)
            ->format('plain')
            ->subject($subject)
            ->setTemplate('Report')
            ->assign('message', $message);

        // @todo DI should be used to inject the MailerInterface in v15.0
        GeneralUtility::makeInstance(MailerInterface::class)->send($email);
    }

    public function getAdditionalInformation()
    {
        return sprintf($this->getLanguageService()->sL('reports.reports:status_updateAdditionalInformation'), preg_replace('#\s+#', ', ', trim($this->notificationEmail)));
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
                GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->sL('reports.reports:status_updateTaskField_notificationEmails_invalid'), '', ContextualFeedbackSeverity::ERROR)
            );
            $validInput = false;
        }
        return $validInput;
    }
}
