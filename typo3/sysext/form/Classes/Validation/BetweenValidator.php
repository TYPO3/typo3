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
 * Between rule
 * Value must be between the min and max. inclusively optional
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class BetweenValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator implements \TYPO3\CMS\Form\Validation\ValidatorInterface {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_between';

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
	 * If TRUE, minimum and maximum values are included in comparison
	 *
	 * @var boolean
	 */
	protected $inclusive;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setMinimum($arguments['minimum'])->setMaximum($arguments['maximum'])->setInclusive($arguments['inclusive']);
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);
			if ($this->inclusive) {
				if ($value < $this->minimum || $value > $this->maximum) {
					return FALSE;
				}
			} else {
				if ($value <= $this->minimum || $value >= $this->maximum) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * Set the minimum value
	 *
	 * @param mixed $minimum Minimum value
	 * @return object Rule object
	 */
	public function setMinimum($minimum) {
		$this->minimum = $minimum;
		return $this;
	}

	/**
	 * Set the maximum value
	 *
	 * @param mixed $maximum Maximum value
	 * @return object Rule object
	 */
	public function setMaximum($maximum) {
		$this->maximum = $maximum;
		return $this;
	}

	/**
	 * Set boolean to make minimum and maximum value inclusive in comparison
	 *
	 * @param boolean $inclusive True is inclusive
	 * @return object Rule object
	 */
	public function setInclusive($inclusive) {
		if ($inclusive === NULL) {
			$this->inclusive = FALSE;
		} else {
			$this->inclusive = (bool) $inclusive;
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
		$label = static::LOCALISATION_OBJECT_NAME . '.' . $type;
		$messages[] = $this->localizationHandler->getLocalLanguageLabel($label);
		if ($this->inclusive) {
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
