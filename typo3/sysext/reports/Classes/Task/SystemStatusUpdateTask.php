<?php
namespace TYPO3\CMS\Reports\Task;

/**
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
 * A task that should be run regularly to determine the system's status.
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class SystemStatusUpdateTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Email addresses to send email notification to in case we find problems with
	 * the system.
	 *
	 * @var string
	 */
	protected $notificationEmail = NULL;

	/**
	 * Executes the System Status Update task, determining the highest severity of
	 * status reports and saving that to the registry to be displayed at login
	 * if necessary.
	 *
	 * @see \TYPO3\CMS\Scheduler\Task\AbstractTask::execute()
	 */
	public function execute() {
		/** @var $registry \TYPO3\CMS\Core\Registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		/** @var $statusReport \TYPO3\CMS\Reports\Report\Status\Status */
		$statusReport = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Report\\Status\\Status');
		$systemStatus = $statusReport->getSystemStatus();
		$highestSeverity = $statusReport->getHighestSeverity($systemStatus);
		$registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);
		if ($highestSeverity > \TYPO3\CMS\Reports\Status::OK) {
			$this->sendNotificationEmail($systemStatus);
		}
		return TRUE;
	}

	/**
	 * Gets the notification email addresses.
	 *
	 * @return string Notification email addresses.
	 */
	public function getNotificationEmail() {
		return $this->notificationEmail;
	}

	/**
	 * Sets the notification email address.
	 *
	 * @param string $notificationEmail Notification email address.
	 * @return void
	 */
	public function setNotificationEmail($notificationEmail) {
		$this->notificationEmail = $notificationEmail;
	}

	/**
	 * Sends a notification email, reporting system issues.
	 *
	 * @param array $systemStatus Array of statuses
	 * @return void
	 */
	protected function sendNotificationEmail(array $systemStatus) {
		$systemIssues = array();
		foreach ($systemStatus as $statusProvider) {
			foreach ($statusProvider as $status) {
				if ($status->getSeverity() > \TYPO3\CMS\Reports\Status::OK) {
					$systemIssues[] = (string) $status;
				}
			}
		}
		$notificationEmails = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $this->notificationEmail, TRUE);
		$sendEmailsTo = array();
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
		$from = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom();
		/** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
		$mail->setFrom($from);
		$mail->setTo($sendEmailsTo);
		$mail->setSubject($subject);
		$mail->setBody($message);
		$mail->send();
	}

}
