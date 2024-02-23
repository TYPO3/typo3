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

namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BackendUserTest extends UnitTestCase
{
    protected BackendUser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BackendUser();
    }

    #[Test]
    public function getUidReturnsInitialValueForInt(): void
    {
        self::assertNull($this->subject->getUid(), 'Not uid set after initialization.');
    }

    #[Test]
    public function getUserNameReturnsInitialValueForString(): void
    {
        self::assertSame($this->subject->getUserName(), '', 'Username not empty');
    }

    #[Test]
    public function setUserNameForStringSetsUserName(): void
    {
        $newUserName = 'DonJuan';
        $this->subject->setUserName($newUserName);
        self::assertSame($this->subject->getUserName(), $newUserName);
    }

    #[Test]
    public function getRealNameReturnInitialValueForString(): void
    {
        self::assertSame($this->subject->getRealName(), '', 'Real name not empty');
    }

    #[Test]
    public function setRealNameForStringSetsName(): void
    {
        $realName = 'Conceived at T3CON2018';
        $this->subject->setRealName($realName);
        self::assertSame($this->subject->getRealName(), $realName);
    }

    #[Test]
    public function getAdminReturnInitialValueForBoolean(): void
    {
        self::assertFalse($this->subject->getIsAdministrator(), 'Admin status is correct.');
    }

    #[Test]
    public function setAdminToTrueSetsAdmin(): void
    {
        $this->subject->setIsAdministrator(true);
        self::assertTrue($this->subject->getIsAdministrator(), 'Admin status is not true, after setting to true.');
    }

    #[Test]
    public function setAdminToFalseSetsAdmin(): void
    {
        $this->subject->setIsAdministrator(false);
        self::assertFalse($this->subject->getIsAdministrator(), 'Admin status is not false, after setting to false.');
    }
}
