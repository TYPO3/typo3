<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Test case for class \TYPO3\CMS\Form\Validation\FileAllowedTypesValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class FileAllowedTypesValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\FileAllowedTypesValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\FileAllowedTypesValidator(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
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
		$this->fixture->setFieldName('myFile');
		$this->fixture->setAllowedTypes($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('type' => $input[1])
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidTypesProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->fixture->setFieldName('myFile');
		$this->fixture->setAllowedTypes($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('type' => $input[1])
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>