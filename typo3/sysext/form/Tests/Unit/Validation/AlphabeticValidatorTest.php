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
class AlphabeticValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\AlphabeticValidator
	 */
	protected $subject;

	/**
	 * Set up
	 */
	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\AlphabeticValidator', array('dummy'), array(), '', FALSE);
	}

	/**
	 * @return array
	 */
	public function validDataProviderWithoutWhitespace() {
		return array(
			'ascii without spaces' => array('thisismyinput'),
			'accents without spaces' => array('éóéàèò'),
			'umlauts without spaces' => array('üöä'),
			'empty string' => array('')
		);
	}

	/**
	 * @return array
	 */
	public function validDataProviderWithWhitespace() {
		return array(
			'ascii with spaces' => array('This is my input'),
			'accents with spaces' => array('Sigur Rós'),
			'umlauts with spaces' => array('Hürriyet Daily News'),
			'space' => array(' '),
			'empty string' => array('')
		);
	}

	/**
	 * @return array
	 */
	public function invalidDataProviderWithoutWhitespace() {
		return array(
			'ascii with dash' => array('my-name'),
			'accents with underscore' => array('Sigur_Rós'),
			'umlauts with periods' => array('Hürriyet.Daily.News'),
			'space' => array(' '),
		);
	}

	/**
	 * @return array
	 */
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

		$this->subject->setFieldName('name');
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
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

		$this->subject->setAllowWhiteSpace(TRUE);
		$this->subject->setFieldName('name');
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
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

		$this->subject->setFieldName('name');
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
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

		$this->subject->setAllowWhiteSpace(TRUE);
		$this->subject->setFieldName('name');
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
