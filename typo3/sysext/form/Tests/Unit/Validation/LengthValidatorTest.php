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
class LengthValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\LengthValidator
	 */
	protected $subject;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$charsetConverterMock = $this->getMock('TYPO3\\CMS\\Core\\Charset\\CharsetConverter', array(), array(), '', FALSE);
		$charsetConverterMock->expects($this->any())->method('strlen')
			->will($this->returnCallback(function ($charset, $value) {
				return mb_strlen($value, $charset);
			}));
		$this->subject = $this->getAccessibleMock('TYPO3\\CMS\\Form\\Validation\\LengthValidator', array('dummy'), array(), '', FALSE);
		$this->subject->_set('charsetConverter', $charsetConverterMock);
	}

	/**
	 * @return array
	 */
	public function validLengthProvider() {
		return array(
			'4 ≤ length(myString) ≤ 8' => array(
				array(4, 8, 'mäString')
			),
			'8 ≤ length(myString) ≤ 8' => array(
				array(8, 8, 'möString')
			),
			'4 ≤ length(myString)' => array(
				array(4, NULL, 'myString')
			),
			'4 ≤ length(asdf) ≤ 4' => array(
				array(4, 4, 'asdf')
			),
		);
	}

	/**
	 * @test
	 * @dataProvider validLengthProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myLength');
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @return array
	 */
	public function invalidLengthProvider() {
		return array(
			'4 ≤ length(my) ≤ 12' => array(
				array(4, 12, 'my')
			),
			'4 ≤ length(my long string) ≤ 12' => array(
				array(4, 12, 'my long string')
			),
		);
	}

	/**
	 * @test
	 * @dataProvider invalidLengthProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myLength');
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
