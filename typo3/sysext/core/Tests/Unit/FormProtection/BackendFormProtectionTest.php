<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\FormProtection;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class BackendFormProtectionTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    /**
     * @var BackendUserAuthentication|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $backendUserMock;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registryMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $this->backendUserMock->user['uid'] = 1;
        $this->registryMock = $this->createMock(Registry::class);
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
            ->expects(self::once())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->willReturn([]);
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

        $tokenId = GeneralUtility::hmac(
            $formName . $action . $formInstanceName . $sessionToken
        );

        $this->backendUserMock
            ->expects(self::atLeastOnce())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->willReturn($sessionToken);

        self::assertTrue(
            $this->subject->validateToken($tokenId, $formName, $action, $formInstanceName)
        );
    }

    /**
     * @test
     */
    public function restoreSessionTokenFromRegistryThrowsExceptionIfSessionTokenIsEmpty()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1301827270);

        $this->subject->setSessionTokenFromRegistry();
    }

    /**
     * @test
     */
    public function persistSessionTokenWritesTokenToSession()
    {
        $this->backendUserMock
            ->expects(self::once())
            ->method('setAndSaveSessionData');
        $this->subject->persistSessionToken();
    }

    /**
     * @test
     */
    public function failingTokenValidationInvokesFailingTokenClosure()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1442592030);

        $this->subject->validateToken('foo', 'bar');
    }
}
