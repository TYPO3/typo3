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
 * Test case for class \TYPO3\CMS\Form\Validation\AlphanumericValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class AlphanumericValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\AlphanumericValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\AlphanumericValidator(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
	}

	public function validDataProviderWithoutWhitespace() {
		return array(
			'ascii without spaces' => array('thisismyinput4711'),
			'accents without spaces' => array('éóéàèò4711'),
			'umlauts without spaces' => array('üöä4711'),
			'empty string' => array('')
		);
	}

	public function validDataProviderWithWhitespace() {
		return array(
			'ascii with spaces' => array('This is my input 4711'),
			'accents with spaces' => array('Sigur Rós 4711'),
			'umlauts with spaces' => array('Hürriyet Daily News 4711'),
			'space' => array(' '),
			'empty string' => array('')
		);
	}

	public function invalidDataProviderWithoutWhitespace() {
		return array(
			'ascii with dash' => array('my-name-4711'),
			'accents with underscore' => array('Sigur_Rós_4711'),
			'umlauts with periods' => array('Hürriyet.Daily.News.4711'),
			'space' => array(' '),
		);
	}

	public function invalidDataProviderWithWhitespace() {
		return array(
			'ascii with spaces and dashes' => array('This is my-name 4711'),
			'accents with spaces and underscores' => array('Listen to Sigur_Rós_Band 4711'),
			'umlauts with spaces and periods' => array('Go get the Hürriyet.Daily.News 4711')
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProviderWithoutWhitespace
	 */
	public function isValidForValidInputWithoutAllowedWhitespaceReturnsTrue($input) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProviderWithWhitespace
	 */
	public function isValidForValidInputWithAllowedWhitespaceReturnsTrue($input) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProviderWithoutWhitespace
	 */
	public function isValidForInvalidInputWithoutAllowedWhitespaceReturnsFalse($input) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProviderWithWhitespace
	 */
	public function isValidForInvalidInputWithAllowedWhitespaceReturnsFalse($input) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>