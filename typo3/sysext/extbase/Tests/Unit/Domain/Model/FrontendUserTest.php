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

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FrontendUserTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new FrontendUser();
    }

    /**
     * @test
     */
    public function getUsernameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function setUsernameSetsUsername()
    {
        $username = 'don.juan';
        $this->subject->setUsername($username);
        self::assertSame($username, $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function getPasswordInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setPasswordSetsPassword()
    {
        $password = 'f00Bar';
        $this->subject->setPassword($password);
        self::assertSame($password, $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setUsergroupSetsUsergroup()
    {
        $usergroup = new ObjectStorage();
        $usergroup->attach(new FrontendUserGroup('foo'));
        $this->subject->setUsergroup($usergroup);
        self::assertSame($usergroup, $this->subject->getUsergroup());
    }

    /**
     * @test
     */
    public function addUsergroupAddsUserGroup()
    {
        $usergroup = new FrontendUserGroup('foo');
        self::assertEquals(count($this->subject->getUsergroup()), 0);
        $this->subject->addUsergroup($usergroup);
        self::assertEquals(count($this->subject->getUsergroup()), 1);
    }

    /**
     * @test
     */
    public function removeUsergroupRemovesUsergroup()
    {
        $usergroup = new FrontendUserGroup('foo');
        $this->subject->addUsergroup($usergroup);
        self::assertEquals(count($this->subject->getUsergroup()), 1);
        $this->subject->removeUsergroup($usergroup);
        self::assertEquals(count($this->subject->getUsergroup()), 0);
    }

    /**
     * @test
     */
    public function getNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getName());
    }

    /**
     * @test
     */
    public function setNameSetsName()
    {
        $name = 'don juan';
        $this->subject->setName($name);
        self::assertSame($name, $this->subject->getName());
    }

    /**
     * @test
     */
    public function getFirstNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function setFirstNameSetsFirstName()
    {
        $firstName = 'don';
        $this->subject->setFirstName($firstName);
        self::assertSame($firstName, $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function getMiddleNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function setMiddleNameSetsMiddleName()
    {
        $middleName = 'miguel';
        $this->subject->setMiddleName($middleName);
        self::assertSame($middleName, $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function getLastNameInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function setLastNameSetsLastName()
    {
        $lastName = 'juan';
        $this->subject->setLastName($lastName);
        self::assertSame($lastName, $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function getAddressInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function setAddressSetsAddress()
    {
        $address = 'foobar 42, foo';
        $this->subject->setAddress($address);
        self::assertSame($address, $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function getTelephoneInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function setTelephoneSetsTelephone()
    {
        $telephone = '42';
        $this->subject->setTelephone($telephone);
        self::assertSame($telephone, $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function getFaxInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getFax());
    }

    /**
     * @test
     */
    public function setFaxSetsFax()
    {
        $fax = '42';
        $this->subject->setFax($fax);
        self::assertSame($fax, $this->subject->getFax());
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
    public function getLockToDomainInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function setLockToDomainSetsLockToDomain()
    {
        $lockToDomain = 'foo.bar';
        $this->subject->setLockToDomain($lockToDomain);
        self::assertSame($lockToDomain, $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foobar';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getZipInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getZip());
    }

    /**
     * @test
     */
    public function setZipSetsZip()
    {
        $zip = '42';
        $this->subject->setZip($zip);
        self::assertSame($zip, $this->subject->getZip());
    }

    /**
     * @test
     */
    public function getCityInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getCity());
    }

    /**
     * @test
     */
    public function setCitySetsCity()
    {
        $city = 'foo';
        $this->subject->setCity($city);
        self::assertSame($city, $this->subject->getCity());
    }

    /**
     * @test
     */
    public function getCountryInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function setCountrySetsCountry()
    {
        $country = 'foo';
        $this->subject->setCountry($country);
        self::assertSame($country, $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function getWwwInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getWww());
    }

    /**
     * @test
     */
    public function setWwwSetsWww()
    {
        $www = 'foo.bar';
        $this->subject->setWww($www);
        self::assertSame($www, $this->subject->getWww());
    }

    /**
     * @test
     */
    public function getCompanyInitiallyReturnsEmptyString()
    {
        self::assertSame('', $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function setCompanySetsCompany()
    {
        $company = 'foo bar';
        $this->subject->setCompany($company);
        self::assertSame($company, $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function getImageInitiallyReturnsObjectStorage()
    {
        self::assertInstanceOf(ObjectStorage::class, $this->subject->getImage());
    }

    /**
     * @test
     */
    public function setImageSetsImage()
    {
        $images = new ObjectStorage();
        $reference = new FileReference();
        $reference->setPid(123);
        $images->attach($reference);

        $this->subject->setImage($images);
        self::assertSame($images, $this->subject->getImage());
    }

    /**
     * @test
     */
    public function getLastloginInitiallyReturnsNull()
    {
        self::assertNull($this->subject->getLastlogin());
    }

    /**
     * @test
     */
    public function setLastloginSetsLastlogin()
    {
        $date = new \DateTime();
        $this->subject->setLastlogin($date);
        self::assertSame($date, $this->subject->getLastlogin());
    }
}
