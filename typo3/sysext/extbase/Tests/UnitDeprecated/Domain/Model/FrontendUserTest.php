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
    public function getUsernameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function setUsernameSetsUsername(): void
    {
        $username = 'don.juan';
        $this->subject->setUsername($username);
        self::assertSame($username, $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function getPasswordInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setPasswordSetsPassword(): void
    {
        $password = 'f00Bar';
        $this->subject->setPassword($password);
        self::assertSame($password, $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setUsergroupSetsUsergroup(): void
    {
        $usergroup = new ObjectStorage();
        $usergroup->attach(new FrontendUserGroup('foo'));
        $this->subject->setUsergroup($usergroup);
        self::assertSame($usergroup, $this->subject->getUsergroup());
    }

    /**
     * @test
     */
    public function addUsergroupAddsUserGroup(): void
    {
        $usergroup = new FrontendUserGroup('foo');
        self::assertCount(0, $this->subject->getUsergroup());
        $this->subject->addUsergroup($usergroup);
        self::assertCount(1, $this->subject->getUsergroup());
    }

    /**
     * @test
     */
    public function removeUsergroupRemovesUsergroup(): void
    {
        $usergroup = new FrontendUserGroup('foo');
        $this->subject->addUsergroup($usergroup);
        self::assertCount(1, $this->subject->getUsergroup());
        $this->subject->removeUsergroup($usergroup);
        self::assertCount(0, $this->subject->getUsergroup());
    }

    /**
     * @test
     */
    public function getNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getName());
    }

    /**
     * @test
     */
    public function setNameSetsName(): void
    {
        $name = 'don juan';
        $this->subject->setName($name);
        self::assertSame($name, $this->subject->getName());
    }

    /**
     * @test
     */
    public function getFirstNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function setFirstNameSetsFirstName(): void
    {
        $firstName = 'don';
        $this->subject->setFirstName($firstName);
        self::assertSame($firstName, $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function getMiddleNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function setMiddleNameSetsMiddleName(): void
    {
        $middleName = 'miguel';
        $this->subject->setMiddleName($middleName);
        self::assertSame($middleName, $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function getLastNameInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function setLastNameSetsLastName(): void
    {
        $lastName = 'juan';
        $this->subject->setLastName($lastName);
        self::assertSame($lastName, $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function getAddressInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function setAddressSetsAddress(): void
    {
        $address = 'foobar 42, foo';
        $this->subject->setAddress($address);
        self::assertSame($address, $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function getTelephoneInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function setTelephoneSetsTelephone(): void
    {
        $telephone = '42';
        $this->subject->setTelephone($telephone);
        self::assertSame($telephone, $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function getFaxInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getFax());
    }

    /**
     * @test
     */
    public function setFaxSetsFax(): void
    {
        $fax = '42';
        $this->subject->setFax($fax);
        self::assertSame($fax, $this->subject->getFax());
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
    public function getTitleInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle(): void
    {
        $title = 'foobar';
        $this->subject->setTitle($title);
        self::assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getZipInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getZip());
    }

    /**
     * @test
     */
    public function setZipSetsZip(): void
    {
        $zip = '42';
        $this->subject->setZip($zip);
        self::assertSame($zip, $this->subject->getZip());
    }

    /**
     * @test
     */
    public function getCityInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getCity());
    }

    /**
     * @test
     */
    public function setCitySetsCity(): void
    {
        $city = 'foo';
        $this->subject->setCity($city);
        self::assertSame($city, $this->subject->getCity());
    }

    /**
     * @test
     */
    public function getCountryInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function setCountrySetsCountry(): void
    {
        $country = 'foo';
        $this->subject->setCountry($country);
        self::assertSame($country, $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function getWwwInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getWww());
    }

    /**
     * @test
     */
    public function setWwwSetsWww(): void
    {
        $www = 'foo.bar';
        $this->subject->setWww($www);
        self::assertSame($www, $this->subject->getWww());
    }

    /**
     * @test
     */
    public function getCompanyInitiallyReturnsEmptyString(): void
    {
        self::assertSame('', $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function setCompanySetsCompany(): void
    {
        $company = 'foo bar';
        $this->subject->setCompany($company);
        self::assertSame($company, $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function getImageInitiallyReturnsObjectStorage(): void
    {
        self::assertInstanceOf(ObjectStorage::class, $this->subject->getImage());
    }

    /**
     * @test
     */
    public function setImageSetsImage(): void
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
    public function getLastloginInitiallyReturnsNull(): void
    {
        self::assertNull($this->subject->getLastlogin());
    }

    /**
     * @test
     */
    public function setLastloginSetsLastlogin(): void
    {
        $date = new \DateTime();
        $this->subject->setLastlogin($date);
        self::assertSame($date, $this->subject->getLastlogin());
    }
}
