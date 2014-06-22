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
class FileMaximumSizeValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\FileMaximumSizeValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\FileMaximumSizeValidator', array('dummy'), array(), '', FALSE);
	}

	public function validSizesProvider() {
		return array(
			'11B for max. 12B' => array(array(12, 11)),
			'12B for max. 12B' => array(array(12, 12))
		);
	}

	public function invalidSizesProvider() {
		return array(
			'12B for max. 11B' => array(array(11, 12))
		);
	}

	/**
	 * @test
	 * @dataProvider validSizesProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setMaximum($input[0]);
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
	 * @dataProvider inValidSizesProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setMaximum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('size' => $input[1])
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
