<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Steffen Kamper <info@sk-typo3.de>
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
 * Testcase for comparing regular expressions in the TYPO3 core, eg. while
 * replacing ereg* to preg*.
 *
 * @package TYPO3
 * @subpackage t3lib
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 */
class regularexpression_testcase extends tx_phpunit_testcase {
	public function setUp() {
	}

	public function tearDown() {
	}


	//////////////////////////////////
	// Tests for regular expressions
	//////////////////////////////////

	/**
	 * @test
	 */
	public function removeLineFeeds() {
		$thisLine = 'test
		test
		test';
		$test = (ereg_replace("[\n\r]*", '', $thisLine) == preg_replace('/[\n\r]*/', '', $thisLine));
		$this->assertTrue(
			$test
		);
	}

	/**
	 * @test
	 */
	public function removeNoneAscii() {
		$string = 'this is a teststring with Umlauts äöü';
		$test = (substr(ereg_replace('[^a-zA-Z0-9_]','',str_replace(' ','_',trim($string))),0,30) == substr(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', trim($string))), 0, 30));
		$this->assertTrue(
			$test
		);
	}

	/**
	 * @test
	 */
	public function clearPath() {
		$string = './thisDir/subDir/';
		$test = (ereg_replace('^\./', '', $string) == preg_replace('/^\.\//', '', $string));
		$this->assertTrue(
			$test
		);
	}

	/**
	 * @test
	 */
	public function removeTrailingSign() {
		$string = 'label:';
		$test = (ereg_replace(':$', '', $string) == preg_replace('/:$/', '', $string));
		$this->assertTrue(
			$test
		);
	}



	/**
	 * @test
	 */
	public function split1() {
		$string = 'test1, test2|test3;test4';
		$array1 = split(',|;|'.chr(10),$string);
		$array2 = preg_split('/[,;'.chr(10).']/',$string);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	/**
	 * @test
	 */
	public function split2() {
		$string = 'test1, test2=test3; test4';
		$array1 = split('[[:space:]=]',$string,2);
		$array2 = preg_split('/[[:space:]=]/',$string,2);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	/**
	 * @test
	 */
	public function split3() {
		$string = 'test1:test2=test3; test4=test5|test6';
		$array1 = split('=|:',$string,3);
		$array2 = preg_split('/[=:]/',$string,3);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	/**
	 * @test
	 */
	public function split4() {
		$string = 'key => value';
		$array1 = split("[[:space:]=>]",$string,2);
		$array2 = preg_split('/[[:space:]=>]/',$string,2);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	/**
	 * @test
	 */
	public function split5() {
		$string = 'test[1][2][3][4] test[5] test[6]';
		$array1 = split('\[|\]',$string);
		$array2 = preg_split('/\[|\]/',$string);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}
}
?>
