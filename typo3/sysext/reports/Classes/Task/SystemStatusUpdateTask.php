<?php
namespace TYPO3\CMS\Reports\Task;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ingo Renner <ingo@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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
	 * Executes the System Status Update task, determing the highest severity of
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

?>