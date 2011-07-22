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
 * Captcha model object
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_element_captcha extends tx_form_domain_model_element_abstract {

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accesskey' => '',
		'alt' => '',
		'class' => '',
		'dir' => '',
		'disabled' => '',
		'id' => '',
		'lang' => '',
		'maxlength' => '',
		'name' => '',
		'readonly' => '',
		'size' => '',
		'style' => '',
		'tabindex' => '',
		'title' => '',
		'type' => 'text',
		'value' => '',
	);

	/**
	 * Mandatory attributes for this object
	 *
	 * @var array
	 */
	protected $mandatoryAttributes = array(
		'name',
		'id'
	);

	/**
	 * HTML string for captcha image
	 *
	 * @var string
	 */
	protected $image = '';

	/**
	 * HTML string for text to reload the image
	 *
	 * @var string
	 */
	protected $reload = '';

	/**
	 * HTML string for accessibility feature
	 *
	 * @var string
	 */
	protected $accessibility = '';

	/**
	 * Constructor
	 * Sets the configuration, calls parent constructor and fills the attributes
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
		$this->setCaptcha();
	}

	/**
	 * Returns the HTML string for the captcha image
	 *
	 * @return string HTML for the image
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getImage() {
		return $this->image;
	}

	/**
	 * Returns the HTML string for the text to reload the captcha image
	 *
	 * @return string HTML for the reload text
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getReload() {
		return $this->reload;
	}

	/**
	 * Returns the HTML string for an accessible audio fragment
	 *
	 * @return string HTML for the accessibility feature
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function getAccessibility() {
		return $this->accessibility;
	}

	/**
	 * Read the captcha object
	 * Check which captcha extension is loaded
	 * and make captcha according to this extension
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setCaptcha() {
		switch (TRUE) {
			case t3lib_extMgm::isLoaded('sr_freecap'):
				$this->useExtensionSrfreecap();
				break;
			case t3lib_extMgm::isLoaded('captcha'):
				$this->useExtensionCaptcha();
				break;
			case t3lib_extMgm::isLoaded('simple_captcha'):
				$this->useExtensionSimpleCaptcha();
				break;
			case t3lib_extMgm::isLoaded('wt_calculating_captcha'):
				$this->useExtensionWtCalculatingCaptcha();
				break;
			/*case t3lib_extMgm::isLoaded('securimage'):
				$this->useExtensionSecurimage();
				break;*/
			case t3lib_extMgm::isLoaded('jm_recaptcha'):
				$this->useExtensionJmRecaptcha();
				break;
		}
	}

	/**
	 * Use the extension sr_freecap for captcha validation
	 * Set the label, image, reload text and accessibility feature
	 * Uses the default input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSrfreecap() {
		require_once(t3lib_extMgm::extPath('sr_freecap') . 'pi2/class.tx_srfreecap_pi2.php');
		$captchaObject = t3lib_div::makeInstance('tx_srfreecap_pi2');

		$captchaValues = $captchaObject->makeCaptcha();
		$this->setLabelIfEmpty($captchaValues['###SR_FREECAP_NOTICE###']);
		$this->setImage($captchaValues['###SR_FREECAP_IMAGE###']);
		$this->setReload($captchaValues['###SR_FREECAP_CANT_READ###']);
		$this->setAccessibility($captchaValues['###SR_FREECAP_ACCESSIBLE###']);
	}

	/**
	 * Use the extension captcha for captcha validation
	 * Set the image and label
	 * Uses the default input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionCaptcha() {
		$localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');

		$this->setImage('<img src="' . t3lib_extMgm::siteRelPath('captcha') . 'captcha/captcha.php" alt="" />');
		$this->setLabelIfEmpty($localizationHandler->getLocalLanguageLabel('tx_form_domain_model_element_captcha.captcha'));
	}

	/**
	 * Use the extension simple_captcha for captcha validation
	 * Set the label and images
	 * Makes multiple images with checkboxes,
	 * uses different layout and no default input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSimpleCaptcha() {
		require_once(t3lib_extMgm::extPath('simple_captcha') . 'class.tx_simplecaptcha.php');
		$localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');

		$this->setLayout('<label /><captchaimage />');

		$captchaObject = t3lib_div::makeInstance('tx_simplecaptcha');

		$this->setImage($captchaObject->getCaptcha());
		$this->setLabelIfEmpty($localizationHandler->getLocalLanguageLabel('tx_form_domain_model_element_captcha.simple_captcha'));
	}

	/**
	 * Use the extension wt_calculation_captcha for captcha validation
	 * Set the image and label
	 * Uses the default input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionWtCalculatingCaptcha() {
		require_once(t3lib_extMgm::extPath('wt_calculating_captcha') . 'class.tx_wtcalculatingcaptcha.php');
		$localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');

		$captchaObject = t3lib_div::makeInstance('tx_wtcalculatingcaptcha');

		$this->setImage($captchaObject->generateCaptcha());
		$this->setLabelIfEmpty($localizationHandler->getLocalLanguageLabel('tx_form_domain_model_element_captcha.wt_calculating_captcha'));
	}

	/**
	 * Use the extension securimage for captcha validation
	 * Set the label, image, reload text and accessibility feature
	 * Uses the default input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionSecurimage() {
		require_once(t3lib_extMgm::extPath('securimage') . 'pi1/class.tx_securimage_pi1.php');
		$captchaObject = t3lib_div::makeInstance('tx_securimage_pi1');

		$captchaValues = $captchaObject->getCaptcha();
		$this->setLabelIfEmpty($captchaValues['###CAPCTHA_DESC###']);
		$this->setImage($captchaValues['###CAPCTHA###']);
		$this->setReload($captchaValues['###CAPCTHA_RELOAD###']);
		$this->setAccessibility($captchaValues['###CAPCTHA_AUDIO###']);
	}

	/**
	 * Use the extension jm_recaptcha for captcha validation
	 * Set the label and image
	 * Uses its own input field
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function useExtensionJmRecaptcha() {
		require_once(t3lib_extMgm::extPath('jm_recaptcha') . 'class.tx_jmrecaptcha.php');
		$localizationHandler = t3lib_div::makeInstance('tx_form_system_localization');
		$captchaObject = t3lib_div::makeInstance('tx_jmrecaptcha');

		$this->setLayout('<label /><captchaimage />');

		$this->setImage($captchaObject->getReCaptcha());
		$this->setLabelIfEmpty($localizationHandler->getLocalLanguageLabel('tx_form_domain_model_element_captcha.jm_recaptcha'));
	}

	/**
	 * Use local language label if it ain't set by user
	 * Label will be used from captcha extension if available,
	 * otherwise from FORM local language file
	 *
	 * @param string $text The override label
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setLabelIfEmpty($text) {
		if(!$this->additionalIsSet('label')) {
			$label['value'] = (string) $text;
			$this->setAdditional('label', 'TEXT', $label);
		}
	}

	/**
	 * Set the captcha image
	 *
	 * @param string $image HTML string of the image
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setImage($image) {
		$this->image = (string) $image;
	}

	/**
	 * Set the text for reloading the image
	 *
	 * @param string $reload Reload text
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setReload($reload) {
		$this->reload = (string) $reload;
	}

	/**
	 * Set the HTML for the accessibility feature
	 *
	 * @param string $accessibility HTML string for the feature
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function setAccessibility($accessibility) {
		$this->accessibility = (string) $accessibility;
	}

	/**
	 * Get the local language label(s) for the message
	 *
	 * @return string The local language message label
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function getLocalLanguageLabel($type) {
		$label = get_class($this) . '.' . $type;
		$message = $this->localizationHandler->getLocalLanguageLabel($label);
		return $message;
	}
}
?>