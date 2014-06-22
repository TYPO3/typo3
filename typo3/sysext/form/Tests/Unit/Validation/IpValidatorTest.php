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
class IpValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\IpValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\IpValidator', array('dummy'), array(), '', FALSE);
	}

	public function validIpv4Provider() {
		return array(
			'127.0.0.1'   => array('127.0.0.1'),
			'10.0.0.4'    => array('10.0.0.4'),
			'192.168.0.4' => array('192.168.0.4'),
			'0.0.0.0'     => array('0.0.0.0')
		);
	}

	public function invalidIpv4Provider() {
		return array(
			'127.0.0.256' => array('127.0.0.256'),
			'256.0.0.2'   => array('256.0.0.2')
		);
	}

	/**
	 * @test
	 * @dataProvider validIpv4Provider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myIp');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myIp' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidIpv4Provider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myIp');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myIp' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
