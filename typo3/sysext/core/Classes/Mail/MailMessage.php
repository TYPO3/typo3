<?php
namespace TYPO3\CMS\Core\Mail;

// Make sure Swift's auto-loader is registered
require_once PATH_typo3 . 'contrib/swiftmailer/swift_required.php';

/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions
 *
 * @author Ernesto Baschny <ernst@cron-it.de>
 */
class MailMessage extends \Swift_Message {

	/**
	 * @var \TYPO3\CMS\Core\Mail\Mailer
	 */
	protected $mailer;

	/**
	 * @var string This will be added as X-Mailer to all outgoing mails
	 */
	protected $mailerHeader = 'TYPO3';

	/**
	 * TRUE if the message has been sent.
	 *
	 * @var boolean
	 */
	protected $sent = FALSE;

	/**
	 * Holds the failed recipients after the message has been sent
	 *
	 * @var array
	 */
	protected $failedRecipients = array();

	/**
	 * @return void
	 */
	private function initializeMailer() {
		$this->mailer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\Mailer');
	}

	/**
	 * Sends the message.
	 *
	 * @return integer the number of recipients who were accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function send() {
		$this->initializeMailer();
		$this->sent = TRUE;
		$this->getHeaders()->addTextHeader('X-Mailer', $this->mailerHeader);
		return $this->mailer->send($this, $this->failedRecipients);
	}

	/**
	 * Checks whether the message has been sent.
	 *
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isSent() {
		return $this->sent;
	}

	/**
	 * Returns the recipients for which the mail was not accepted for delivery.
	 *
	 * @return array the recipients who were not accepted for delivery
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getFailedRecipients() {
		return $this->failedRecipients;
	}

}


?>