<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Captcha rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_validate_captcha extends tx_form_system_validate_abstract {

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments) {
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see typo3/sysext/form/interfaces/tx_form_system_validate_interface#isValid()
	 */
	public function isValid() {
		switch (TRUE) {
			case t3lib_extMgm::isLoaded('sr_freecap'):
				return $this->useExtensionSrFreecap();
				break;
			case t3lib_extMgm::isLoaded('captcha'):
				return $this->useExtensionCaptcha();
				break;
			case t3lib_extMgm::isLoaded('simple_captcha'):
				return $this->useExtensionSimpleCaptcha();
				break;
			case t3lib_extMgm::isLoaded('wt_calculating_captcha'):
				return $this->useExtensionWtCalculatingCaptcha();
				break;
			/*case t3lib_extMgm::isLoaded('securimage'):
				return $this->useExtensionWtCalculatingCaptcha();
				break;*/
			case t3lib_extMgm::isLoaded('jm_recaptcha'):
				return $this->useExtensionJmRecaptcha();
				break;
			default:
				return TRUE;
		}
	}

	/**
	 * Use the extension sr_freecap for captcha validation
	 * Checks if the input matches the characters of the captcha image
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSrFreecap() {
		$validated = FALSE;

		require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
		$captchaObject = t3lib_div::makeInstance('tx_srfreecap_pi2');

		$value = $this->requestHandler->getByMethod($this->fieldName);

		if (is_object($captchaObject) && $captchaObject->checkWord($value)) {
			$validated = TRUE;
		}

		return $validated;
	}

	/**
	 * Use the extension captcha for captcha validation
	 * Checks if the input matches the characters of the captcha image
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionCaptcha() {
		$validated = FALSE;

		session_start();
		$captchaSessionString = $_SESSION['tx_captcha_string'];
		$SESSION['tx_captcha_string'] = '';

		$value = $this->requestHandler->getByMethod($this->fieldName);

		if ($captchaSessionString && $value === $captchaSessionString) {
			$validated = TRUE;
		}

		return $validated;
	}

	/**
	 * Use the extension simple_captcha for captcha validation
	 * Checks if the checkboxes of true images are checked
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSimpleCaptcha() {
		$validated = FALSE;

		require_once(t3lib_extMgm::extPath('simple_captcha') . 'class.tx_simplecaptcha.php');
		$captchaObject = t3lib_div::makeInstance('tx_simplecaptcha');

		if (is_object($captchaObject) && $captchaObject->checkCaptcha()) {
			$validated = TRUE;
		}

		return $validated;
	}

	/**
	 * Use the extension wt_calculation_captcha for captcha validation
	 * Checks if the input matches calculation on the image
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionWtCalculatingCaptcha() {
		$validated = FALSE;

		require_once(t3lib_extMgm::extPath('wt_calculating_captcha') . 'class.tx_wtcalculatingcaptcha.php');
		$captchaObject = t3lib_div::makeInstance('tx_wtcalculatingcaptcha');

		$value = $this->requestHandler->getByMethod($this->fieldName);

		if (is_object($captchaObject) && $captchaObject->correctCode($value)) {
			$validated = TRUE;
		}

		return $validated;
	}

	/**
	 * Use the extension securimage for captcha validation
	 * Checks if the input matches characters on the image
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSecurimage() {
		$validated = FALSE;

		require_once(t3lib_extMgm::extPath('securimage') . 'pi1/class.tx_securimage_pi1.php');
		$captchaObject = t3lib_div::makeInstance('tx_securimage_pi1');

		$value = $this->requestHandler->getByMethod($this->fieldName);

		if (is_object($captchaObject) && $captchaObject->validate($value)) {
			$validated = TRUE;
		}

		return $validated;
	}

	/**
	 * Use the extension jm_recaptcha for captcha validation
	 * Checks if the input matches characters on the image
	 *
	 * @return boolean True if valid
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionJmRecaptcha() {
		$validated = FALSE;

		require_once(t3lib_extMgm::extPath('jm_recaptcha') . 'class.tx_jmrecaptcha.php');
		$captchaObject = t3lib_div::makeInstance('tx_jmrecaptcha');

		if (is_object($captchaObject)) {
			$status = $captchaObject->validateReCaptcha();
			if ($status['verified']) {
				$validated = TRUE;
			}
		}

		return $validated;
	}
}
?>