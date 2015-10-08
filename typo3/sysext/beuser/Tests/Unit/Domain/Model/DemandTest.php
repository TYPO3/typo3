<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Model;

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
 * Test case for class \TYPO3\CMS\Beuser\Domain\Model\Demand
 */
class DemandTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Beuser\Domain\Model\Demand
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Beuser\Domain\Model\Demand();
    }

    /**
     * @test
     */
    public function setUserTypeAllExpectedValueForInt()
    {
        $userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
        $this->subject->setUserType($userType);
        $this->assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setUserTypeAdminOnlyExpectedValueForInt()
    {
        $userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::USERTYPE_ADMINONLY;
        $this->subject->setUserType($userType);
        $this->assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setUserTypeUserOnlyExpectedValueForInt()
    {
        $userType = \TYPO3\CMS\Beuser\Domain\Model\Demand::USERTYPE_USERONLY;
        $this->subject->setUserType($userType);
        $this->assertSame($this->subject->getUserType(), $userType);
    }

    /**
     * @test
     */
    public function setStatusAllExpectedValueForInt()
    {
        $status = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
        $this->subject->setStatus($status);
        $this->assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setStatusActiveExpectedValueForInt()
    {
        $status = \TYPO3\CMS\Beuser\Domain\Model\Demand::STATUS_ACTIVE;
        $this->subject->setStatus($status);
        $this->assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setStatusInactiveExpectedValueForInt()
    {
        $status = \TYPO3\CMS\Beuser\Domain\Model\Demand::STATUS_INACTIVE;
        $this->subject->setStatus($status);
        $this->assertSame($this->subject->getStatus(), $status);
    }

    /**
     * @test
     */
    public function setLoginAllExpectedValueForInt()
    {
        $login = \TYPO3\CMS\Beuser\Domain\Model\Demand::ALL;
        $this->subject->setLogins($login);
        $this->assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function setLoginNoneExpectedValueForInt()
    {
        $login = \TYPO3\CMS\Beuser\Domain\Model\Demand::LOGIN_NONE;
        $this->subject->setLogins($login);
        $this->assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function setLoginxSameExpectedValueForInt()
    {
        $login = \TYPO3\CMS\Beuser\Domain\Model\Demand::LOGIN_SOME;
        $this->subject->setLogins($login);
        $this->assertSame($this->subject->getLogins(), $login);
    }

    /**
     * @test
     */
    public function getUserNameInitialValueForString()
    {
        $this->assertSame($this->subject->getUserName(), '', 'UserName must be empty string.');
    }

    /**
     * @test
     */
    public function setUserNameReturnExpectedValueForString()
    {
        $newUserName = 'User#ää*%^name';
        $this->subject->setUserName($newUserName);
        $this->assertSame($this->subject->getUserName(), $newUserName, 'UserName is not as set before.');
    }
}
