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
 * Testcase for comparing regular expressions in the TYPO3 core, eg while replacing ereg* to preg*
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


	//////////////////////
	// Utility functions
	//////////////////////

	


	////////////////////////////////////
	// Tests for regular expressions
	////////////////////////////////////

	public function testRemoveLineFeeds() {
	    $thisLine = 'test
	    test
	    test';	
	    $test = (ereg_replace("[\n\r]*", '', $thisLine) == preg_replace('/[\n\r]*/', '', $thisLine));
		$this->assertTrue(
			$test
		);
	}

	public function testRemoveNoneAscii() {
		$string = 'this is a teststring with Umlauts äöü';
	    $test = (substr(ereg_replace('[^a-zA-Z0-9_]','',str_replace(' ','_',trim($string))),0,30) == substr(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', trim($string))), 0, 30));
		$this->assertTrue(
			$test
		);
	}

	public function testClearPath() {
		$string = './thisDir/subDir/';
	    $test = (ereg_replace('^\./', '', $string) == preg_replace('/^\.\//', '', $string));
		$this->assertTrue(
			$test
		);
	}

	public function testRemoveTrailingSign() {
		$string = 'label:';
	    $test = (ereg_replace(':$', '', $string) == preg_replace('/:$/', '', $string));
		$this->assertTrue(
			$test
		);
	}

	
	
	public function testSplit1() {
		$string = 'test1, test2|test3;test4';
		$array1 = split(',|;|'.chr(10),$string);
		$array2 = preg_split('/[,;'.chr(10).']/',$string);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	public function testSplit2() {
		$string = 'test1, test2=test3; test4';
		$array1 = split('[[:space:]=]',$string,2);
		$array2 = preg_split('/[[:space:]=]/',$string,2);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	public function testSplit3() {
		$string = 'test1:test2=test3; test4=test5|test6';
		$array1 = split('=|:',$string,3);
		$array2 = preg_split('/[=:]/',$string,3);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	public function testSplit4() {
		$string = 'key => value';
		$array1 = split("[[:space:]=>]",$string,2);
		$array2 = preg_split('/[[:space:]=>]/',$string,2);
		foreach($array1 as $key => $value) {
			$this->assertTrue(
				($array2[$key] === $value)
			);
		}
	}

	public function testSplit5() {
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