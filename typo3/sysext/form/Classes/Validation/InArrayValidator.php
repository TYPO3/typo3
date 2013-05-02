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
 * In array rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class InArrayValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_inarray';

	/**
	 * Haystack to search in
	 *
	 * @var array
	 */
	protected $array;

	/**
	 * Search strict
	 *
	 * @var boolean
	 */
	protected $strict;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setArray($arguments['array.'])->setStrict($arguments['strict']);
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
			if (!in_array($value, $this->array, $this->strict)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the array as haystack
	 *
	 * @param array $array The haystack
	 * @return object Rule object
	 */
	public function setArray($array) {
		$this->array = (array) $array;
		return $this;
	}

	/**
	 * Set the strict mode for the search
	 *
	 * @param boolean $strict True if strict
	 * @return object Rule object
	 */
	public function setStrict($strict) {
		$this->strict = (bool) $strict;
		return $this;
	}

}
