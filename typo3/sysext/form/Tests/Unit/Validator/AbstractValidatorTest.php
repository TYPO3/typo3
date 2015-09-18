<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validator;

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

use TYPO3\CMS\Form\Domain\Validator\AbstractValidator;

/**
 * Test case
 */
abstract class AbstractValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Must be filled with subject class name
	 * in specific test implementation.
	 *
	 * @var string
	 */
	protected $subjectClassName = NULL;

	/**
	 * @param array $options
	 * @return AbstractValidator|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function createSubject(array $options) {
		$subject = $this->getAccessibleMock($this->subjectClassName, array('renderMessage'), array($options));
		$subject->method('renderMessage')->will($this->returnValue('error'));
		return $subject;
	}

}
