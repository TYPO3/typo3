<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Testcase for class t3lib_userauth
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_userauthTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * phpunit still needs some globals that are
	 * reconstructed before $backupGlobals is handled. Those
	 * important globals are handled in tearDown() directly.
	 *
	 * @var array
	 */
	protected $globals = array();

	public function setUp() {
		$this->globals = array(
			'TYPO3_LOADED_EXT' => serialize($GLOBALS['TYPO3_LOADED_EXT']),
		);
	}

	public function tearDown() {
		foreach ($this->globals as $key => $value) {
			$GLOBALS[$key] = unserialize($value);
		}
	}

	////////////////////////////////////
	// Tests concerning processLoginData
	////////////////////////////////////


	public function processLoginDataProvider() {
		return array(

			'Backend login with securityLevel "normal"' => array(
				'BE',
				'normal',
				'superchallenged',
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  'password',
					'chalvalue' =>  NULL,
				),
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  '651219fccfbe0c9004c7196515d780ce',
					'chalvalue' =>  NULL,
					'uident_text' =>  'password',
					'uident_challenged' =>  '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' =>  '651219fccfbe0c9004c7196515d780ce',
				),
			),

			'Backend login with securityLevel "superchallenged"' => array(
				'BE',
				'superchallenged',
				'superchallenged',
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  '651219fccfbe0c9004c7196515d780ce',
					'chalvalue' =>  NULL,
				),
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  '651219fccfbe0c9004c7196515d780ce',
					'chalvalue' =>  NULL,
					'uident_text' =>  '',
					'uident_challenged' =>  '',
					'uident_superchallenged' =>  '651219fccfbe0c9004c7196515d780ce',
				),
			),

			'Frontend login with securityLevel "normal"' => array(
				'FE',
				'normal',
				'normal',
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  'password',
					'chalvalue' =>  NULL,
				),
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  'password',
					'chalvalue' =>  NULL,
					'uident_text' =>  'password',
					'uident_challenged' =>  '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' =>  '651219fccfbe0c9004c7196515d780ce',
				),
			),

			'Frontend login with securityLevel "challenged"' => array(
				'FE',
				'challenged',
				'challenged',
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  '458203772635d38f05ca9e62d8237974',
					'chalvalue' =>  NULL,
				),
				array (
					'status' =>  'login',
					'uname' =>  'admin',
					'uident' =>  '458203772635d38f05ca9e62d8237974',
					'chalvalue' =>  NULL,
					'uident_text' =>  '',
					'uident_challenged' =>  '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' =>  '',
				),
			),

		);
	}

	/**
	 * @test
	 * @dataProvider processLoginDataProvider
	 */
	public function processLoginReturnsCorrectData($loginType, $passwordSubmissionStrategy, $passwordCompareStrategy, $originalData, $processedData) {

		/** @var $mock t3lib_userauth */
		$mock = $this->getMock('t3lib_userauth', array('_dummy'));
		$mock->security_level = $passwordCompareStrategy;
		$mock->loginType = $loginType;

		$this->assertEquals($mock->processLoginData($originalData, $passwordSubmissionStrategy), $processedData);

	}
}




?>