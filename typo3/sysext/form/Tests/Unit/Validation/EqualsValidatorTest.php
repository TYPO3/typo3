<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
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
 * Test case
 */
class EqualsValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\EqualsValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\EqualsValidator', array('dummy'), array(), '', FALSE);
	}

	public function invalidPairProvider() {
		return array(
			'somethingElse !== something' => array(array('somethingElse', 'something')),
			'4 !== 3'                     => array(array(4, 3))
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPairProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myField');
		$this->subject->setField($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myField' => $input[1],
			$input[0] => TRUE
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
