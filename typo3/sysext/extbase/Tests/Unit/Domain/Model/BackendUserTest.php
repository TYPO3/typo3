<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Model;

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
 * Test case
 */
class BackendUserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\BackendUser();
    }

    /**
     * @test
     */
    public function getUserNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function setUserNameSetsUserName()
    {
        $userName = 'don.juan';
        $this->subject->setUserName($userName);
        $this->assertSame($userName, $this->subject->getUserName());
    }

    /**
     * @test
     */
    public function getIsAdministratorInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function setIsAdministratorCanSetIsAdministratorToTrue()
    {
        $this->subject->setIsAdministrator(true);
        $this->assertTrue($this->subject->getIsAdministrator());
    }

    /**
     * @test
     */
    public function getIsDisabledInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function setIsDisabledCanSetIsDisabledToTrue()
    {
        $this->subject->setIsDisabled(true);
        $this->assertTrue($this->subject->getIsDisabled());
    }

    /**
     * @test
     */
    public function getStartDateAndTimeInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function setStartDateAndTimeSetsStartDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setStartDateAndTime($date);
        $this->assertSame($date, $this->subject->getStartDateAndTime());
    }

    /**
     * @test
     */
    public function getEndDateAndTimeInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function setEndDateAndTimeSetsEndDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setEndDateAndTime($date);
        $this->assertSame($date, $this->subject->getEndDateAndTime());
    }

    /**
     * @test
     */
    public function isActivatedInitiallyReturnsTrue()
    {
        $this->assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForDisabledReturnsFalse()
    {
        $this->subject->setIsDisabled(true);
        $this->assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureReturnsFalse()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        $this->assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastReturnsTrue()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        $this->assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInFutureReturnsTrue()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setEndDateAndTime($tomorrow);
        $this->assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForEndDateAndTimeInPastReturnsFalse()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setEndDateAndTime($yesterday);
        $this->assertFalse($this->subject->isActivated());
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
        $this->assertTrue($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInPastEndDateAndTimeInPastReturnsFalse()
    {
        $yesterday = new \DateTime('yesterday');
        $this->subject->setStartDateAndTime($yesterday);
        $this->subject->setEndDateAndTime($yesterday);
        $this->assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function isActivatedForStartDateAndTimeInFutureEndDateAndTimeInFutureReturnsFalse()
    {
        $tomorrow = new \DateTime('tomorrow');
        $this->subject->setStartDateAndTime($tomorrow);
        $this->subject->setEndDateAndTime($tomorrow);
        $this->assertFalse($this->subject->isActivated());
    }

    /**
     * @test
     */
    public function getEmailInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function setEmailSetsEmail()
    {
        $email = 'don.juan@example.com';
        $this->subject->setEmail($email);
        $this->assertSame($email, $this->subject->getEmail());
    }

    /**
     * @test
     */
    public function getRealNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function setRealNameSetsRealName()
    {
        $realName = 'Don Juan';
        $this->subject->setRealName($realName);
        $this->assertSame($realName, $this->subject->getRealName());
    }

    /**
     * @test
     */
    public function getIpLockIsDisabledInitiallyReturnsFalse()
    {
        $this->assertFalse($this->subject->getIpLockIsDisabled());
    }

    /**
     * @test
     */
    public function setIpLockIsDisabledCanSetIpLockIsDisabledToTrue()
    {
        $this->subject->setIpLockIsDisabled(true);
        $this->assertTrue($this->subject->getIpLockIsDisabled());
    }

    /**
     * @test
     */
    public function getLastLoginDateAndTimeInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getLastLoginDateAndTime());
    }

    /**
     * @test
     */
    public function setLastLoginDateAndTimeSetsLastLoginDateAndTime()
    {
        $date = new \DateTime();
        $this->subject->setLastLoginDateAndTime($date);
        $this->assertSame($date, $this->subject->getLastLoginDateAndTime());
    }
}
