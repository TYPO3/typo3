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
class FileAllowedTypesValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\FileAllowedTypesValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\FileAllowedTypesValidator', array('dummy'), array(), '', FALSE);
	}

	public function validTypesProvider() {
		return array(
			'pdf in (pdf)'       => array(array('application/pdf', 'application/pdf')),
			'pdf in (pdf, json)' => array(array('application/pdf, application/json', 'application/pdf'))

		);
	}
	public function invalidTypesProvider() {
		return array(
			'xml in (pdf, json)' => array(array('application/pdf, application/json', 'application/xml')),
			'xml in (pdf)'       => array(array('application/pdf, application/json', 'application/xml'))
		);
	}

	/**
	 * @test
	 * @dataProvider validTypesProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setAllowedTypes($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('type' => $input[1])
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidTypesProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myFile');
		$this->subject->setAllowedTypes($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('type' => $input[1])
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
