<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Ingo Renner <ingo@typo3.org>
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
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_tasks_SystemStatusUpdateTask extends tx_scheduler_Task {

	/**
	 * Email address to send email notification to in case we find problems with
	 * the system.
	 *
	 * @var	string
	 */
	protected $notificationEmail = NULL;

	/**
	 * Executes the System Status Update task, determing the highest severity of
	 * status reports and saving that to the registry to be displayed at login
	 * if necessary.
	 *
	 * @see typo3/sysext/scheduler/tx_scheduler_Task::execute()
	 */
	public function execute() {
		$registry     = t3lib_div::makeInstance('t3lib_Registry');
		$statusReport = t3lib_div::makeInstance('tx_reports_reports_Status');

		$systemStatus    = $statusReport->getSystemStatus();
		$highestSeverity = $statusReport->getHighestSeverity($systemStatus);

		$registry->set('tx_reports', 'status.highestSeverity', $highestSeverity);

		if ($highestSeverity > tx_reports_reports_status_Status::OK) {
			$this->sendNotificationEmail($systemStatus);
		}

		return true;
	}

	/**
	 * Gets the notification email address.
	 *
	 * @return	string	Notification email address.
	 */
	public function getNotificationEmail() {
		return $this->notificationEmail;
	}

	/**
	 * Sets the notification email address.
	 *
	 * @param	string	$notificationEmail Notification email address.
	 */
	public function setNotificationEmail($notificationEmail) {
		$this->notificationEmail = $notificationEmail;
	}

	/**
	 * Sends a notification email, reporting system issues.
	 *
	 * @param	array	$systemStatus Array of statuses
	 */
	protected function sendNotificationEmail(array $systemStatus) {
		$systemIssues = array();

		foreach ($systemStatus as $statusProvider) {
			foreach ($statusProvider as $status) {
				if ($status->getSeverity() > tx_reports_reports_status_Status::OK) {
					$systemIssues[] = (string) $status;
				}
			}
		}

		$fromEmail = $this->getFromAddress();

		$subject = sprintf(
			$GLOBALS['LANG']->getLL('status_updateTask_email_subject'),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
		);

		$message = sprintf(
			$GLOBALS['LANG']->getLL('status_problemNotification'),
			'',
			''
		);
		$message .= CRLF . CRLF;
		$message .= $GLOBALS['LANG']->getLL('status_updateTask_email_site')
			. ': ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		$message .= CRLF . CRLF;
		$message .= $GLOBALS['LANG']->getLL('status_updateTask_email_issues')
			. ': ' .CRLF;
		$message .= implode(CRLF, $systemIssues);
		$message .= CRLF . CRLF;

		$mail = t3lib_div::makeInstance('t3lib_mail_Message');
		$mail->setFrom(array($fromEmail => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']));
		$mail->setTo($this->notificationEmail);
		$mail->setSubject($subject);
		$mail->setBody($message);

		$mail->send();
	}

	/**
	 * Tries to find an email address to use for the From email header.
	 *
	 * Uses a fall back chain:
	 *     Install Tool ->
	 *     no-reply@FirstDomainRecordFound ->
	 *     no-reply@php_uname('n')
	 *
	 * @return	string	email address
	 */
	protected function getFromAddress() {
		$email = '';
		$user  = 'no-reply';

			// default
		$email = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];

			// find domain record
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				// just get us a domain record we can use
			$domainRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'domainName',
				'sys_domain',
				'hidden = 0',
				'',
				'pid ASC, sorting ASC'
			);

			if (!empty($domainRecord['domainName'])) {
				$tempUrl = $domainRecord['domainName'];

				if (!t3lib_div::isFirstPartOfStr($tempUrl, 'http')) {
						// shouldn't be the case anyways, but you never know
						// ... there're crazy people out there
					$tempUrl = 'http://' .$tempUrl;
				}

				$host = parse_url($tempUrl, PHP_URL_HOST);
			}

			$email = $user . '@' . $host;
		}

			// uname
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$host  = php_uname('n');
			$email = $user . '@' . $host;
		}

		return $email;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/tasks/class.tx_reports_tasks_systemstatusupdatetask.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/tasks/class.tx_reports_tasks_systemstatusupdatetask.php']);
}

?>