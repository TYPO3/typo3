<?php
namespace TYPO3\CMS\Sv\Tests\Unit;

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
