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
 * Test case for class \TYPO3\CMS\Form\System\Validation\AlphabeticValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class AlphabeticValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\AlphabeticValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\AlphabeticValidator();
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
	}

	public function validDataProviderWithoutWhitespace() {
		return array(
			'ascii without spaces' => array('thisismyinput'),
			'accents without spaces' => array('éóéàèò'),
			'umlauts without spaces' => array('üöä'),
			'empty string' => array('')
		);
	}

	public function validDataProviderWithWhitespace() {
		return array(
			'ascii with spaces' => array('This is my input'),
			'accents with spaces' => array('Sigur Rós'),
			'umlauts with spaces' => array('Hürriyet Daily News'),
			'space' => array(' '),
			'empty string' => array('')
		);
	}

	public function invalidDataProviderWithoutWhitespace() {
		return array(
			'ascii with dash' => array('my-name'),
			'accents with underscore' => array('Sigur_Rós'),
			'umlauts with periods' => array('Hürriyet.Daily.News'),
			'space' => array(' '),
		);
	}

	public function invalidDataProviderWithWhitespace() {
		return array(
			'ascii with spaces and dashes' => array('This is my-name'),
			'accents with spaces and underscores' => array('Listen to Sigur_Rós_Band'),
			'umlauts with spaces and periods' => array('Go get the Hürriyet.Daily.News')
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
	public function isValidForValidInputWithWhitespaceAllowedReturnsTrue($input) {
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
	public function isValidForInvalidInputWithWhitespaceAllowedReturnsFalse($input) {
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