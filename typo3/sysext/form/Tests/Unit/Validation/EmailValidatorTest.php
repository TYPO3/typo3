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
class EmailValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\EmailValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\EmailValidator', array('dummy'), array(), '', FALSE);
	}

	public function validEmailProvider() {
		return array(
			'a@b.de' => array('a@b.de'),
			'somebody@mymac.local' => array('somebody@mymac.local')
		);
	}

	public function invalidEmailProvider() {
		return array(
			'myemail@' => array('myemail@'),
			'myemail' => array('myemail'),
			'somebody@localhost' => array('somebody@localhost'),
		);
	}

	/**
	 * @test
	 * @dataProvider validEmailProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myEmail');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myEmail' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidEmailProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myEmail');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myEmail' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
