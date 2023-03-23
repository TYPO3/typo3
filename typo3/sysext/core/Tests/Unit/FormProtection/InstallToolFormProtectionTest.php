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

use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class InstallToolFormProtectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function tokenFromSessionDataIsAvailableForValidateToken(): void
    {
        $sessionToken = '881ffea2159ac72182557b79dc0c723f5a8d20136f9fab56cdd4f8b3a1dbcfcd';
        $formName = 'foo';
        $action = 'edit';
        $formInstanceName = '42';
        $tokenId = GeneralUtility::hmac($formName . $action . $formInstanceName . $sessionToken);
        $_SESSION['installToolFormToken'] = $sessionToken;
        $subject = $this->getAccessibleMock(InstallToolFormProtection::class, null);
        $subject->_call('retrieveSessionToken');
        self::assertTrue($subject->validateToken($tokenId, $formName, $action, $formInstanceName));
    }

    /**
     * @test
     */
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
