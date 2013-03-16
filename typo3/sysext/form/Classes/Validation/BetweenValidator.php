<?php
namespace TYPO3\CMS\Form\Validation;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 * Between rule
 * Value must be between the min and max. inclusively optional
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class BetweenValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator implements \TYPO3\CMS\Form\Validation\ValidatorInterface {

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
		$label = strtolower(get_class($this)) . '.' . $type;
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
		$message = str_replace('%minimum', $this->minimum, $message);
		$message = str_replace('%maximum', $this->maximum, $message);
		return $message;
	}

}

?>