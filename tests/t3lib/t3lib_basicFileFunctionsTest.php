<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Andreas Kiessling (kiessling@pluspol.info)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for the t3lib_basicFileFunctions class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author 2010 Andreas Kiessling <kiessling@pluspol.info>
 */
class t3lib_basicFileFunctionsTest extends tx_phpunit_testcase {
	/**
	 * @var t3lib_basicFileFunctions
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new t3lib_basicFileFunctions();
	}

	public function tearDown() {
		unset($this->fixture);
	}


	///////////////////////////////////////
	// Tests concerning is_allowed
	///////////////////////////////////////

	/**
	 * Data provider for checkIsAllowed
	 *
	 * @return array Data sets
	 */
	public function functionTestIsAllowedDataProvider() {
		$testAllow = 'pdf,doc,jpg,jpeg,gif,png';
		$testDeny  = PHP_EXTENSIONS_DEFAULT;

		return array(
			'deny for all' => array(FALSE, 'pdf', $testAllow, '*'),
			'allow for configured extension' => array(TRUE, 'pdf', $testAllow, $testDeny),
			'deny for not configured extension' => array(FALSE, 'docx', $testAllow, $testDeny),
			'allow for all' => array(TRUE, 'pdf', '*', $testDeny),
			'allow for all but try php' => array(FALSE, 'php', '*', $testDeny),
			'no fileextension is set and all allowed' => array(TRUE, '', '*', $testDeny),
			'no fileextension is set and only specifiy types are allowed' => array(FALSE, '', $testAllow, $testDeny),
			'no fileextension is set and no allow pattern isset' => array(FALSE, '', '', $testDeny),
			'no fileextension is set and all extensions are allowed' => array(TRUE, '', '*', $testDeny),
			'no fileextension is set and deny for all extensions is set' => array(FALSE, '', $testAllow, '*'),
		);
	}

	/**
	 * @test
	 * @dataProvider functionTestIsAllowedDataProvider
	 */
	public function checkIsAllowed($expected, $fileExtension, $allowed, $denied) {
		$this->fixture->f_ext['webspace']['allow'] = $allowed;
		$this->fixture->f_ext['webspace']['deny'] = $denied;
		$this->assertEquals(
			$expected,
			$this->fixture->is_allowed(
				$fileExtension, 'webspace'
			)
		);
	}


}
?>