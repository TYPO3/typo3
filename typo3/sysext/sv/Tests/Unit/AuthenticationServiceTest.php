<?php
namespace TYPO3\CMS\Sv\Tests\Unit;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Helmut Hummel <helmut.hummel@typo3.org>
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
 * Testcase for class \TYPO3\CMS\Sv\AuthenticationService
 *
 */
class AuthenticationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Date provider for processLoginReturnsCorrectData
	 *
	 * @return array
	 */
	public function processLoginDataProvider() {
		return array(
			'Backend login with securityLevel "normal"' => array(
				'normal',
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => 'password',
					'chalvalue' => NULL
				),
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => 'password',
					'chalvalue' => NULL,
					'uident_text' => 'password',
					'uident_challenged' => '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' => '651219fccfbe0c9004c7196515d780ce'
				)
			),
			'Backend login with securityLevel "superchallenged"' => array(
				'superchallenged',
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => '651219fccfbe0c9004c7196515d780ce',
					'chalvalue' => NULL
				),
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => '651219fccfbe0c9004c7196515d780ce',
					'chalvalue' => NULL,
					'uident_text' => '',
					'uident_challenged' => '',
					'uident_superchallenged' => '651219fccfbe0c9004c7196515d780ce'
				)
			),
			'Frontend login with securityLevel "normal"' => array(
				'normal',
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => 'password',
					'chalvalue' => NULL
				),
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => 'password',
					'chalvalue' => NULL,
					'uident_text' => 'password',
					'uident_challenged' => '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' => '651219fccfbe0c9004c7196515d780ce'
				)
			),
			'Frontend login with securityLevel "challenged"' => array(
				'challenged',
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => '458203772635d38f05ca9e62d8237974',
					'chalvalue' => NULL
				),
				array(
					'status' => 'login',
					'uname' => 'admin',
					'uident' => '458203772635d38f05ca9e62d8237974',
					'chalvalue' => NULL,
					'uident_text' => '',
					'uident_challenged' => '458203772635d38f05ca9e62d8237974',
					'uident_superchallenged' => ''
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider processLoginDataProvider
	 */
	public function processLoginReturnsCorrectData($passwordSubmissionStrategy, $loginData, $expectedProcessedData) {
		/** @var $authenticationService \TYPO3\CMS\Sv\AuthenticationService */
		$authenticationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Sv\\AuthenticationService');
		// Login data is modified by reference
		$authenticationService->processLoginData($loginData, $passwordSubmissionStrategy);
		$this->assertEquals($expectedProcessedData, $loginData);
	}

}
