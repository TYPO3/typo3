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

use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for class \TYPO3\CMS\Beuser\Domain\Model\Demand
 */
class DemandTest extends UnitTestCase
{
    protected Demand $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Demand();
    }

    /**
     * @test
     */
    public function setUserTypeAllExpectedValueForInt(): void
    {
        $userType = Demand::ALL;
        $this->subject->setUserType($userType);
        self::assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setUserTypeAdminOnlyExpectedValueForInt(): void
    {
        $userType = Demand::USERTYPE_ADMINONLY;
        $this->subject->setUserType($userType);
        self::assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setUserTypeUserOnlyExpectedValueForInt(): void
    {
        $userType = Demand::USERTYPE_USERONLY;
        $this->subject->setUserType($userType);
        self::assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setStatusAllExpectedValueForInt(): void
    {
        $status = Demand::ALL;
        $this->subject->setStatus($status);
        self::assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setStatusActiveExpectedValueForInt(): void
    {
        $status = Demand::STATUS_ACTIVE;
        $this->subject->setStatus($status);
        self::assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setStatusInactiveExpectedValueForInt(): void
    {
        $status = Demand::STATUS_INACTIVE;
        $this->subject->setStatus($status);
        self::assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setLoginAllExpectedValueForInt(): void
    {
        $login = Demand::ALL;
        $this->subject->setLogins($login);
        self::assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function setLoginNoneExpectedValueForInt(): void
    {
        $login = Demand::LOGIN_NONE;
        $this->subject->setLogins($login);
        self::assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function setLoginSameExpectedValueForInt(): void
    {
        $login = Demand::LOGIN_SOME;
        $this->subject->setLogins($login);
        self::assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function getUserNameInitialValueForString(): void
    {
        self::assertSame($this->subject->getUserName(), '', 'UserName must be empty string.');
    }

    /**
     * @test
     */
    public function setUserNameReturnExpectedValueForString(): void
    {
        $newUserName = 'User#ää*%^name';
        $this->subject->setUserName($newUserName);
        self::assertSame($this->subject->getUserName(), $newUserName, 'UserName is not as set before.');
    }
}
