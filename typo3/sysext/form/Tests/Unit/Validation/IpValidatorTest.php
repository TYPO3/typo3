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
 * Test case for class \TYPO3\CMS\Form\Validation\IpValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class IpValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\IpValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\IpValidator(array());
	}

	public function tearDown() {
		unset($this->helper);
		unset($this->fixture);
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
		$this->fixture->setFieldName('myIp');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myIp' => $input
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidIpv4Provider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->fixture->setFieldName('myIp');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myIp' => $input
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>