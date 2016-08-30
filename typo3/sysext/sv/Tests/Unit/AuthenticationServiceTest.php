<?php
namespace TYPO3\CMS\Sv\Tests\Unit;

/*
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
class AuthenticationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Date provider for processLoginReturnsCorrectData
     *
     * @return array
     */
    public function processLoginDataProvider()
    {
        return [
            'Backend login with securityLevel "normal"' => [
                'normal',
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                ],
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                    'uident_text' => 'password',
                ]
            ],
            'Frontend login with securityLevel "normal"' => [
                'normal',
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                ],
                [
                    'status' => 'login',
                    'uname' => 'admin',
                    'uident' => 'password',
                    'uident_text' => 'password',
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider processLoginDataProvider
     */
    public function processLoginReturnsCorrectData($passwordSubmissionStrategy, $loginData, $expectedProcessedData)
    {
        /** @var $authenticationService \TYPO3\CMS\Sv\AuthenticationService */
        $authenticationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Sv\AuthenticationService::class);
        // Login data is modified by reference
        $authenticationService->processLoginData($loginData, $passwordSubmissionStrategy);
        $this->assertEquals($expectedProcessedData, $loginData);
    }
}
