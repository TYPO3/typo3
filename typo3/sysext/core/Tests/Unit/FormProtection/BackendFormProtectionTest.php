<?php
namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Registry;

/**
 * Testcase
 */
class BackendFormProtectionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendUserMock;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->backendUserMock = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
        $this->backendUserMock->user['uid'] = 1;
        $this->registryMock = $this->getMock(Registry::class);
        $this->subject = new BackendFormProtection(
            $this->backendUserMock,
            $this->registryMock,
            function () {
                throw new \Exception('Closure called', 1442592030);
            }
        );
    }

    /**
     * @test
     */
    public function generateTokenReadsTokenFromSessionData()
    {
        $this->backendUserMock
            ->expects($this->once())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->will($this->returnValue([]));
        $this->subject->generateToken('foo');
    }

    /**
     * @test
     */
    public function tokenFromSessionDataIsAvailableForValidateToken()
    {
        $sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = '42';

        $tokenId = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(
            $formName . $action . $formInstanceName . $sessionToken
        );

        $this->backendUserMock
            ->expects($this->atLeastOnce())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->will($this->returnValue($sessionToken));

        $this->assertTrue(
            $this->subject->validateToken($tokenId, $formName, $action, $formInstanceName)
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     * @test
     */
    public function restoreSessionTokenFromRegistryThrowsExceptionIfSessionTokenIsEmpty()
    {
        $this->subject->setSessionTokenFromRegistry();
    }

    /**
     * @test
     */
    public function persistSessionTokenWritesTokenToSession()
    {
        $this->backendUserMock
            ->expects($this->once())
            ->method('setAndSaveSessionData');
        $this->subject->persistSessionToken();
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 1442592030
     */
    public function failingTokenValidationInvokesFailingTokenClosure()
    {
        $this->subject->validateToken('foo', 'bar');
    }
}
