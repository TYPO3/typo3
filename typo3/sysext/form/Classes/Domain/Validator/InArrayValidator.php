<?php
namespace TYPO3\CMS\Form\Domain\Validator;

/*
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

class InArrayValidator extends AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'element' => array('', 'The name of the element', 'string', TRUE),
		'errorMessage' => array('', 'The error message', 'array', TRUE),
		'array.' => array('', 'The array value', 'array', TRUE),
		'strict' => array('', 'Compare types', 'boolean', FALSE),
	);

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_inarray';

	/**
	 * Check if $value is valid. If it is not valid, needs to add an error
	 * to result.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function isValid($value) {
		if (!in_array($value, (array)$this->options['array.'], !empty($this->options['strict']))) {
			$this->addError(
				$this->renderMessage(
					$this->options['errorMessage'][0],
					$this->options['errorMessage'][1],
					'error'
				),
				1442002594
			);
		}
	}
}
