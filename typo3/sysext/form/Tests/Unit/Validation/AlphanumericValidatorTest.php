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
class AlphanumericValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\AlphanumericValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\AlphanumericValidator', array('dummy'), array(), '', FALSE);
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
	public function isValidForValidInputWithAllowedWhitespaceReturnsTrue($input) {
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
	public function isValidForInvalidInputWithAllowedWhitespaceReturnsFalse($input) {
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
