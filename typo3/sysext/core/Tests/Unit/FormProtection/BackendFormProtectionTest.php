<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\BackendFormProtection;
use TYPO3\CMS\Core\Registry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendFormProtectionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected BackendFormProtection $subject;
    protected BackendUserAuthentication&MockObject $backendUserMock;
    protected HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backendUserMock = $this->createMock(BackendUserAuthentication::class);
        $this->backendUserMock->user['uid'] = 1;
        $this->subject = new BackendFormProtection(
            $this->backendUserMock,
            $this->createMock(Registry::class),
            static function () {
                throw new \Exception('Closure called', 1442592030);
            }
        );
        $this->hashService = new HashService();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
    }

    #[Test]
    public function generateTokenReadsTokenFromSessionData(): void
    {
        $this->backendUserMock
            ->expects($this->once())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->willReturn([]);
        $this->subject->generateToken('foo');
    }

    #[Test]
    public function tokenFromSessionDataIsAvailableForValidateToken(): void
    {
        $sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = '42';

        $tokenId = $this->hashService->hmac(
            $formName . $action . $formInstanceName . $sessionToken,
            AbstractFormProtection::class
        );

        $this->backendUserMock
            ->expects($this->atLeastOnce())
            ->method('getSessionData')
            ->with('formProtectionSessionToken')
            ->willReturn($sessionToken);

        self::assertTrue(
            $this->subject->validateToken($tokenId, $formName, $action, $formInstanceName)
        );
    }

    #[Test]
    public function restoreSessionTokenFromRegistryThrowsExceptionIfSessionTokenIsEmpty(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1301827270);

        $this->subject->setSessionTokenFromRegistry();
    }

    #[Test]
    public function persistSessionTokenWritesTokenToSession(): void
    {
        $this->backendUserMock
            ->expects($this->once())
            ->method('setAndSaveSessionData');
        $this->subject->persistSessionToken();
    }

    #[Test]
    public function failingTokenValidationInvokesFailingTokenClosure(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1442592030);

        $this->subject->validateToken('foo', 'bar');
    }
}
