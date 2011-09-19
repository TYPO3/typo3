<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * Main view layer for Forms.
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_View_Mail {

	/**
	 * The mail message
	 *
	 * @var t3lib_mail_Message
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
	 * @var tx_form_System_Localization
	 */
	protected $localizationHandler;

	/**
	 * The content object
	 *
	 * @var tslib_cObj
	 */
	protected $localCobj;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct(t3lib_mail_Message $mailMessage, array $typoScript) {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->localizationHandler = t3lib_div::makeInstance(
			'tx_form_System_Localization'
		);
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

	protected function success() {
		return $this->makeContentObject('success');
	}

	protected function error() {
		return $this->makeContentObject('error');
	}

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
		$label = strtolower(get_class($this)) . '.' . $type;
		$message = $this->localizationHandler->getLocalLanguageLabel($label);
		return $message;
	}
}
?>