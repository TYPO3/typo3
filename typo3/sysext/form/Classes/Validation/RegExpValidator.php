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
 * Regexp rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class RegExpValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_regexp';

	/**
	 * Regular expression for rule
	 *
	 * @var string
	 */
	protected $regularExpression;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setRegularExpression($arguments['expression']);
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
			if (!preg_match($this->regularExpression, $value)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the regular expression
	 *
	 * @param string $expression The regular expression
	 * @return object Rule object
	 */
	public function setRegularExpression($expression) {
		$this->regularExpression = (string) $expression;
		return $this;
	}

}
