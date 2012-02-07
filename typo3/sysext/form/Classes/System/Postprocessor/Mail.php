<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Patrick Broens <patrick@patrickbroens.nl>
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
 * The mail post processor
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Postprocessor_Mail {
	/**
	 * @var tx_form_Domain_Model_Form
	 */
	protected $form;

	/**
	 * @var array
	 */
	protected $typoScript;

	/**
	 * @var t3lib_mail_Message
	 */
	protected $mailMessage;

	/**
	 * @var tx_form_System_Request
	 */
	protected $requestHandler;

	/**
	 * @var array
	 */
	protected $dirtyHeaders = array();

	/**
	 * Constructor
	 *
	 * @param $form tx_form_Domain_Model_Form Form domain model
	 * @param $typoscript array Post processor TypoScript settings
	 * @return void
	 */
	public function __construct(tx_form_Domain_Model_Form $form, array $typoScript) {
		$this->form = $form;
		$this->typoScript = $typoScript;
		$this->mailMessage = t3lib_div::makeInstance('t3lib_mail_Message');
		$this->requestHandler = t3lib_div::makeInstance('tx_form_System_Request');
	}

	/**
	 * The main method called by the post processor
	 *
	 * Configures the mail message
	 *
	 * @return string HTML message from this processor
	 */
	public function process() {
		$this->setSubject();
		$this->setFrom();
		$this->setTo();
		$this->setCc();
		$this->setPriority();
		$this->setOrganization();

		// @todo The whole content rendering seems to be missing here!

		$this->setHtmlContent();
		$this->setPlainContent();
		$this->addAttachmentsFromForm();
		$this->send();

		return $this->render();
	}

	/**
	 * Sets the subject of the mail message
	 *
	 * If not configured, it will use a default setting
	 *
	 * @return void
	 */
	protected function setSubject() {
		if (isset($this->typoScript['subject'])) {
			$subject = $this->typoScript['subject'];
		} elseif ($this->requestHandler->has($this->typoScript['subjectField'])) {
			$subject = $this->requestHandler->get($this->typoScript['subjectField']);
		} else {
			$subject = 'Formmail on ' . t3lib_div::getIndpEnv('HTTP_HOST');
		}
		$subject = $this->sanitizeHeaderString($subject);
		$this->mailMessage->setSubject($subject);
	}

	/**
	 * Sets the sender of the mail message
	 *
	 * Mostly the sender is a combination of the name and the email address
	 *
	 * @return void
	 */
	protected function setFrom() {
		$fromEmail = '';
		if ($this->typoScript['senderEmail']) {
			$fromEmail = $this->typoScript['senderEmail'];
		} elseif ($this->requestHandler->has($this->typoScript['senderEmailField'])) {
			$fromEmail = $this->requestHandler->get($this->typoScript['senderEmailField']);
		} else {
			$fromEmail = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
		}
		if (!t3lib_div::validEmail($fromEmail)) {
			$fromEmail = t3lib_utility_Mail::getSystemFromAddress();
		}

		$fromName = '';
		if ($this->typoScript['senderName']) {
			$fromName = $this->typoScript['senderName'];
		} elseif ($this->requestHandler->has($this->typoScript['senderNameField'])) {
			$fromName = $this->requestHandler->get($this->typoScript['senderNameField']);
		} else {
			$fromName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
		}
		$fromName = $this->sanitizeHeaderString($fromName);
		if (preg_match('/\s|,/', $fromName) >= 1) {
			$fromName = '"' . $fromName . '"';
		}

		$from = array($fromEmail => $fromName);

		$this->mailMessage->setFrom($from);
	}

	/**
	 * Adds the receiver of the mail message when configured
	 *
	 * Checks the address if it is a valid email address
	 *
	 * @return void
	 */
	protected function setTo() {
		if (
			$this->typoScript['recipientEmail'] &&
			t3lib_div::validEmail($this->typoScript['recipientEmail'])
		) {
			$this->mailMessage->setTo($this->typoScript['recipientEmail']);
		}
	}

	/**
	 * Adds the carbon copy receiver of the mail message when configured
	 *
	 * Checks the address if it is a valid email address
	 *
	 * @return void
	 */
	protected function setCc() {
		if (
			$this->typoScript['ccEmail'] &&
			t3lib_div::validEmail($this->typoScript['ccEmail'])
		) {
			$this->mailMessage->AddCc(trim($this->typoScript['ccEmail']));
		}
	}

	/**
	 * Set the priority of the mail message
	 *
	 * When not in settings, the value will be 3. If the priority is configured,
	 * but too big, it will be set to 5, which means very low.
	 *
	 * @return void
	 */
	protected function setPriority() {
		$priority = 3;
		if ($this->typoScript['priority']) {
			$priority = t3lib_utility_Math::forceIntegerInRange($this->typoScript['priority'], 1, 5);
		}
		$this->mailMessage->setPriority($priority);
	}

	/**
	 * Add a text header to the mail header of the type Organization
	 *
	 * Sanitizes the header string when necessary
	 *
	 * @return void
	 */
	protected function setOrganization() {
		if ($this->typoScript['organization']) {
			$organization = $this->typoScript['organization'];
			$organization = $this->sanitizeHeaderString($organization);
			$this->mailMessage->getHeaders()->addTextHeader('Organization', $organization);
		}
	}

	/**
	 * Set the default character set used
	 *
	 * Respect formMailCharset if it was set, otherwise use metaCharset for mail
	 * if different from renderCharset
	 *
	 * @return void
	 */
	protected function setCharacterSet() {
		$characterSet = NULL;
		if ($GLOBALS['TSFE']->config['config']['formMailCharset']) {
			$characterSet = $GLOBALS['TSFE']->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['formMailCharset']);
		} elseif ($GLOBALS['TSFE']->metaCharset != $GLOBALS['TSFE']->renderCharset) {
			$characterSet = $GLOBALS['TSFE']->metaCharset;
		}
		if ($characterSet) {
			$this->mailMessage->setCharset($characterSet);
		}
	}

	/**
	 * Add the HTML content
	 *
	 * Add a MimePart of the type text/html to the message.
	 *
	 * @return void
	 */
	protected function setHtmlContent() {
		/** @var $view tx_form_View_Mail_Html */
		$view = t3lib_div::makeInstance(
			'tx_form_View_Mail_Html',
			$this->form,
			$this->typoScript
		);
		$htmlContent = $view->get();
		$this->mailMessage->setBody($htmlContent, 'text/html');
	}

	/**
	 * Add the plain content
	 *
	 * Add a MimePart of the type text/plain to the message.
	 *
	 * @return void
	 */
	protected function setPlainContent() {
		/** @var $view tx_form_View_Mail_Plain */
		$view = t3lib_div::makeInstance(
			'tx_form_View_Mail_Plain',
			$this->form
		);
		$plainContent = $view->render();
		$this->mailMessage->addPart($plainContent, 'text/plain');
	}

	/**
	 * Sends the mail.
	 * Sending the mail requires the recipient and message to be set.
	 *
	 * @return void
	 */
	protected function send() {
		if ($this->mailMessage->getTo() && $this->mailMessage->getBody()) {
			$this->mailMessage->send();
		}
	}

	/**
	 * Render the message after trying to send the mail
	 *
	 * @return string HTML message from the mail view
	 */
	protected function render() {
		/** @var $view tx_form_View_Mail */
		$view = t3lib_div::makeInstance(
			'tx_form_View_Mail',
			$this->mailMessage,
			$this->typoScript
		);

		return $view->render();
	}

	/**
	 * Checks string for suspicious characters
	 *
	 * @param string String to check
	 * @return string Valid or empty string
	 */
	protected function sanitizeHeaderString($string) {
		$pattern = '/[\r\n\f\e]/';
		if (preg_match($pattern, $string) > 0) {
			$this->dirtyHeaders[] = $string;
			$string = '';
		}
		return $string;
	}

	/**
	 * Add attachments when uploaded
	 *
	 * @return void
	 */
	protected function addAttachmentsFromForm() {
		$formElements = $this->form->getElements();
		$values = $this->requestHandler->getByMethod();
		$this->addAttachmentsFromElements($formElements, $values);
	}

	/**
	 * Loop through all elements and attach the file when the element
	 * is a fileupload
	 *
	 * @param array $elements
	 * @param array $submittedValues
	 * @return void
	 */
	protected function addAttachmentsFromElements($elements, $submittedValues) {
		/** @var $element tx_form_Domain_Model_Element_Abstract */
		foreach ($elements as $element) {
			if (is_a($element, 'tx_form_Domain_Model_Element_Container')) {
				$this->addAttachmentsFromElements($element->getElements(), $submittedValues);
				continue;
			}
			if (is_a($element, 'tx_form_Domain_Model_Element_Fileupload')) {
				$elementName = $element->getName();
				if (is_array($submittedValues[$elementName]) && isset($submittedValues[$elementName]['tempFilename'])) {
					$filename = $submittedValues[$elementName]['tempFilename'];
					if (is_file($filename) && t3lib_div::isAllowedAbsPath($filename)) {
						$this->mailMessage->attach(
							Swift_Attachment::fromPath($filename)->setFilename($submittedValues[$elementName]['originalFilename'])
						);
					}
				}
			}
		}
	}
}
?>
