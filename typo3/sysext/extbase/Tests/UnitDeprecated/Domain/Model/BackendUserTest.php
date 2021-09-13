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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Domain\Model;

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
    public function getUserNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function setUserNameSetsUserName(): void
    {
        $userName = 'don.juan';
        $this->subject->setUserName($userName);
        self::assertSame($userName, $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function getIsAdministratorInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function setIsAdministratorCanSetIsAdministratorToTrue(): void
    {
        $this->subject->setIsAdministrator(true);
        self::assertTrue($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function getIsDisabledInitiallyReturnsFalse(): void
    {
        self::assertFalse($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function setIsDisabledCanSetIsDisabledToTrue(): void
    {
        $this->subject->setIsDisabled(true);
        self::assertTrue($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function getStartDateAndTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function setStartDateAndTimeSetsStartDateAndTime(): void
    {
        $date = new \DateTime();
        $this->subject->setStartDateAndTime($date);
        self::assertSame($date, $this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function getEndDateAndTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function setEndDateAndTimeSetsEndDateAndTime(): void
    {
        $date = new \DateTime();
        $this->subject->setEndDateAndTime($date);
        self::assertSame($date, $this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function isActivatedInitiallyReturnsTrue(): void
    {
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForDisabledReturnsFalse(): void
    {
        $this->subject->setIsDisabled(true);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureReturnsFalse(): void
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastReturnsTrue(): void
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInFutureReturnsTrue(): void
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setEndDateAndTime($tomorrow);
        self::assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInPastReturnsFalse(): void
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setEndDateAndTime($yesterday);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInFutureReturnsTrue(): void
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
    public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInPastReturnsFalse(): void
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        $this->subject->setEndDateAndTime($yesterday);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureEndDateAndTimeInFutureReturnsFalse(): void
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        $this->subject->setEndDateAndTime($tomorrow);
        self::assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function getEmailInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function setEmailSetsEmail(): void
    {
        $email = 'don.juan@example.com';
        $this->subject->setEmail($email);
        self::assertSame($email, $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function getRealNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function setRealNameSetsRealName(): void
    {
        $realName = 'Don Juan';
        $this->subject->setRealName($realName);
        self::assertSame($realName, $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function getLastLoginDateAndTimeInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLastLoginDateAndTime());
    }

    /**
     * @test
     */
    public function setLastLoginDateAndTimeSetsLastLoginDateAndTime(): void
    {
        $date = new \DateTime();
        $this->subject->setLastLoginDateAndTime($date);
        self::assertSame($date, $this->subject->getLastLoginDateAndTime());
    }
}
