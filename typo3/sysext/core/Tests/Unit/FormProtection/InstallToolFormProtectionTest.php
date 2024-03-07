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
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InstallToolFormProtectionTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected HashService $hashService;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $this->hashService = new HashService();
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
        $_SESSION['installToolFormToken'] = $sessionToken;
        $subject = $this->getAccessibleMock(InstallToolFormProtection::class, null);
        $subject->_call('retrieveSessionToken');
        self::assertTrue($subject->validateToken($tokenId, $formName, $action, $formInstanceName));
    }

    #[Test]
    public function persistSessionTokenWritesTokensToSession(): void
    {
        $_SESSION['installToolFormToken'] = 'foo';
        $subject = $this->getAccessibleMock(InstallToolFormProtection::class, null);
        $subject->_set('sessionToken', '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd');
        $subject->persistSessionToken();
        self::assertEquals(
            '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd',
            $_SESSION['installToolFormToken']
        );
    }
}
