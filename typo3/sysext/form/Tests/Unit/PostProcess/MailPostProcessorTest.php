<?php
namespace TYPO3\CMS\Form\Tests\Unit\PostProcess;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
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
 * Testcase for MailPostProcessor
 */
class MailPostProcessorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Form\PostProcess\MailPostProcessor
	 */
	public $mailPostProcessor;

	/**
	 * Set up
	 *
	 * @return void
	 */
	public function setUp() {
		$this->mailPostProcessor = $this->getAccessibleMock('TYPO3\CMS\Form\PostProcess\MailPostProcessor', array('dummy'), array(), '', FALSE);
	}

	/**
	 * Tear down
	 *
	 * @return void
	 */
	protected function tearDown() {
		unset($this->mailPostProcessor);
	}

	/**
	 * Data provider for filterValidEmailsReturnsOnlyValidAddresses
	 * 
	 * @return array string of input, array of expected email-addresses
	 */
	public function filterValidEmailsProvider() {
		return array(
			'list with some invalid email-addresses' => array(
				'someone, someone@example.com, Foo <foo@example.com>, <bar@example.com>, "Foo, bar" <foo.bar@example.com>',
				array(
					'someone@example.com',
					'bar@example.com',
					'foo@example.com' => 'Foo',
					'foo.bar@example.com' => '"Foo, bar"',
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider filterValidEmailsProvider
	 */
	public function filterValidEmailsReturnsOnlyValidAddresses($input, $expected) {
		$this->assertEquals($expected, $this->mailPostProcessor->_call('filterValidEmails', $input));
	}

}

?>