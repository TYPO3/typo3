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

namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BackendUser();
    }

    /**
     * @test
     */
    public function getUserNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function setUserNameSetsUserName()
    {
        $userName = 'don.juan';
        $this->subject->setUserName($userName);
        self::assertSame($userName, $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function getIsAdministratorInitiallyReturnsFalse()
    {
        self::assertFalse($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function setIsAdministratorCanSetIsAdministratorToTrue()
    {
        $this->subject->setIsAdministrator(true);
        self::assertTrue($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function getIsDisabledInitiallyReturnsFalse()
    {
        self::assertFalse($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function setIsDisabledCanSetIsDisabledToTrue()
    {
        $this->subject->setIsDisabled(true);
        self::assertTrue($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function getStartDateAndTimeInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function setStartDateAndTimeSetsStartDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setStartDateAndTime($date);
        self::assertSame($date, $this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function getEndDateAndTimeInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function setEndDateAndTimeSetsEndDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setEndDateAndTime($date);
        self::assertSame($date, $this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function isActivatedInitiallyReturnsTrue()
    {
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForDisabledReturnsFalse()
    {
        $this->subject->setIsDisabled(true);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureReturnsFalse()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastReturnsTrue()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInFutureReturnsTrue()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setEndDateAndTime($tomorrow);
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInPastReturnsFalse()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setEndDateAndTime($yesterday);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInFutureReturnsTrue()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setEndDateAndTime($tomorrow);
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInPastReturnsFalse()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        $this->subject->setEndDateAndTime($yesterday);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureEndDateAndTimeInFutureReturnsFalse()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        $this->subject->setEndDateAndTime($tomorrow);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function getEmailInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function setEmailSetsEmail()
    {
        $email = 'don.juan@example.com';
        $this->subject->setEmail($email);
        self::assertSame($email, $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function getRealNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function setRealNameSetsRealName()
    {
        $realName = 'Don Juan';
        $this->subject->setRealName($realName);
        self::assertSame($realName, $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function getIpLockIsDisabledInitiallyReturnsFalse()
    {
        self::assertFalse($this->subject->getIpLockIsDisabled());
    }

    /**
     * @test
     */
    public function setIpLockIsDisabledCanSetIpLockIsDisabledToTrue()
    {
        $this->subject->setIpLockIsDisabled(true);
        self::assertTrue($this->subject->getIpLockIsDisabled());
    }

    /**
     * @test
     */
    public function getLastLoginDateAndTimeInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getLastLoginDateAndTime());
    }

    /**
     * @test
     */
    public function setLastLoginDateAndTimeSetsLastLoginDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setLastLoginDateAndTime($date);
        self::assertSame($date, $this->subject->getLastLoginDateAndTime());
    }
}
