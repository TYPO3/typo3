<?php
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
 * Length rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_Length extends tx_form_System_Validate_Abstract {

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
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 * @return void
	 */
	public function __construct($arguments) {
		$this->setMinimum($arguments['minimum'])
			->setMaximum($arguments['maximum']);

		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see tx_form_System_Validate_Interface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);

			$length = iconv_strlen($value);

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
	 * @param integer $minimum Minimum value
	 * @return object Rule object
	 */
	public function setMinimum($minimum) {
		$this->minimum = (integer) $minimum;

		return $this;
	}

	/**
	 * Set the maximum value
	 *
	 * @param integer $maximum Maximum value
	 * @return object Rule object
	 */
	public function setMaximum($maximum) {
		if (empty($maximum)) {
			$this->maximum = NULL;
		} else {
			$this->maximum = (integer) $maximum;
		}

		return $this;
	}

	/**
	 * Get the local language label(s) for the message
	 * Overrides the abstract
	 *
	 * @param string $type The type
	 * @return string The local language message label
	 * @see tx_form_System_Validate_Abstract::_getLocalLanguageLabel()
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
		$message = str_replace('%minimum', $this->minimum, $message);
		$message = str_replace('%maximum', $this->maximum, $message);

		return $message;
	}
}
?>