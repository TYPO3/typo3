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
 * File Maximum size rule
 * The file size must be smaller or equal than the maximum
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_validate_filemaximumsize extends tx_form_system_validate_abstract implements tx_form_system_validate_interface {

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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments) {
		$this->setMaximum($arguments['maximum']);

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
		if($this->requestHandler->has($this->fieldName)) {
			$fileValue = $this->requestHandler->getByMethod($this->fieldName);
			$value = $fileValue['size'];

			if($value > $this->maximum) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the maximum value
	 *
	 * @param integer $maximum Maximum value
	 * @return object Rule object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setMaximum($maximum) {
		$this->maximum = (integer) $maximum;

		return $this;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	protected function substituteValues($message) {
		$message = str_replace(
			'%maximum',
			t3lib_div::formatSize($this->maximum),
			$message
		);

		return $message;
	}
}
?>