<?php
namespace TYPO3\CMS\Form\Validation;

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
 * Length rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class LengthValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Minimum value
	 *
	 * @var mixed
	 */
	protected $minimum;

	/**
	 * Maximum value
	 *
	 * @var mixed
	 */
	protected $maximum;

	/**
	 * TYPO3 charset encoding object
	 *
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 */
	protected $charsetConverter = NULL;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->charsetConverter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
		$this->setMinimum($arguments['minimum'])->setMaximum($arguments['maximum']);
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return bool
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);
			$length = $this->charsetConverter->strlen('utf-8', $value);
			if ($length < $this->minimum) {
				return FALSE;
			}
			if ($this->maximum !== NULL && $length > $this->maximum) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the minimum value
	 *
	 * @param int $minimum Minimum value
	 * @return LengthValidator Rule object
	 */
	public function setMinimum($minimum) {
		$this->minimum = (int)$minimum;
		return $this;
	}

	/**
	 * Set the maximum value
	 *
	 * @param int $maximum Maximum value
	 * @return LengthValidator Rule object
	 */
	public function setMaximum($maximum) {
		if (empty($maximum)) {
			$this->maximum = NULL;
		} else {
			$this->maximum = (int)$maximum;
		}
		return $this;
	}

	/**
	 * Get the local language label(s) for the message
	 * Overrides the abstract
	 *
	 * @param string $type The type
	 * @return string The local language message label
	 * @see \TYPO3\CMS\Form\Validation\AbstractValidator::_getLocalLanguageLabel()
	 */
	protected function getLocalLanguageLabel($type) {
		$label = strtolower(get_class($this)) . '.' . $type;
		$messages[] = $this->localizationHandler->getLocalLanguageLabel($label);
		if ($this->maximum !== NULL) {
			$messages[] = $this->localizationHandler->getLocalLanguageLabel($label . '2');
		}
		$message = implode(', ', $messages);
		return $message;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	protected function substituteValues($message) {
		return str_replace(
			array('%minimum', '%maximum'),
			array($this->minimum, $this->maximum),
			$message
		);
	}

}
