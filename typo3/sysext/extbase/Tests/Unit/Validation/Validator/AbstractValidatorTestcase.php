<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

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

/**
 * Test case for the Abstract Validator
 */
abstract class AbstractValidatorTestcase extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected $validatorClassName;

	/**
	 * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
	 */
	protected $validator;

	protected function setUp() {
		$this->validator = $this->getValidator();
	}

	/**
	 * @param array $options
	 * @return mixed
	 */
	protected function getValidator($options = array()) {
		$validator = new $this->validatorClassName($options);
		return $validator;
	}

	/**
	 * @param array $options
	 */
	protected function validatorOptions($options) {
		$this->validator = $this->getValidator($options);
	}

}
