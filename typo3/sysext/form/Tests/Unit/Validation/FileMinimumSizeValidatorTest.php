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
class FileMinimumSizeValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\FileMinimumSizeValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\FileMinimumSizeValidator', array('dummy'), array(), '', FALSE);
	}

	public function validSizesProvider() {
		return array(
			'12B for min. 11B' => array(array(11, 12)),
			'12B for min. 12B' => array(array(12, 12))
		);
	}

	public function invalidSizesProvider() {
		return array(
			'11B for min. 12B' => array(array(12, 11))
		);
	}

	/**
	 * @test
	 * @dataProvider validSizesProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setMinimum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('size' => $input[1])
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidSizesProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setMinimum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('size' => $input[1])
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
