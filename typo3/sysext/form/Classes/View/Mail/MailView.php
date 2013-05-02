<?php
namespace TYPO3\CMS\Form\View\Mail;

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
 * Main view layer for Forms.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class MailView {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_view_mail';

	/**
	 * The mail message
	 *
	 * @var \TYPO3\CMS\Core\Mail\MailMessage
	 */
	protected $mailMessage;

	/**
	 * The TypoScript settings for the confirmation
	 *
	 * @var array
	 */
	protected $typoScript = array();

	/**
	 * The localization handler
	 *
	 * @var \TYPO3\CMS\Form\Localization
	 */
	protected $localizationHandler;

	/**
	 * The content object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $localCobj;

	/**
	 * Constructor
	 */
	public function __construct(\TYPO3\CMS\Core\Mail\MailMessage $mailMessage, array $typoScript) {
		$this->localCobj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->localizationHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Localization');
		$this->mailMessage = $mailMessage;
		$this->typoScript = $typoScript;
	}

	public function render() {
		if ($this->mailMessage->isSent()) {
			$output = $this->success();
		} else {
			$output = $this->error();
		}
		return $output;
	}

	/**
	 * Success
	 *
	 * @return string
	 */
	protected function success() {
		return $this->makeContentObject('success');
	}

	/**
	 * Error
	 *
	 * @return string
	 */
	protected function error() {
		return $this->makeContentObject('error');
	}

	/**
	 * Make content object
	 *
	 * @return string
	 */
	protected function makeContentObject($isSent) {
		$message = NULL;
		$type = NULL;
		if ($this->typoScript['messages.'][$isSent]) {
			$type = $this->typoScript['messages.'][$isSent];
		}
		if ($this->typoScript['messages.'][$isSent . '.']) {
			$message = $this->typoScript['messages.'][$isSent . '.'];
		}
		if (empty($message)) {
			if (!empty($type)) {
				$message = $type;
				$type = 'TEXT';
			} else {
				$type = 'TEXT';
				$message = $this->getLocalLanguageLabel($isSent);
			}
			$value['value'] = $message;
			$value['wrap'] = '<p>|</p>';
		} elseif (!is_array($message)) {
			$value['value'] = $message;
			$value['wrap'] = '<p>|</p>';
		} else {
			$value = $message;
		}
		return $this->localCobj->cObjGetSingle($type, $value);
	}

	/**
	 * Get the local language label(s) for the message
	 * In some cases this method will be override by rule class
	 *
	 * @param string $type The type
	 * @return string The local language message label
	 */
	protected function getLocalLanguageLabel($type) {
		$label = self::LOCALISATION_OBJECT_NAME . '.' . $type;
		$message = $this->localizationHandler->getLocalLanguageLabel($label);
		return $message;
	}

}
