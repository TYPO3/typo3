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
class FrontendUserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUser();
    }

    /**
     * @test
     */
    public function getUsernameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function setUsernameSetsUsername()
    {
        $username = 'don.juan';
        $this->subject->setUsername($username);
        $this->assertSame($username, $this->subject->getUsername());
    }

    /**
     * @test
     */
    public function getPasswordInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setPasswordSetsPassword()
    {
        $password = 'f00Bar';
        $this->subject->setPassword($password);
        $this->assertSame($password, $this->subject->getPassword());
    }

    /**
     * @test
     */
    public function setUsergroupSetsUsergroup()
    {
        $usergroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $usergroup->attach(new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo'));
        $this->subject->setUsergroup($usergroup);
        $this->assertSame($usergroup, $this->subject->getUsergroup());
    }

    /**
     * @test
     */
    public function addUsergroupAddsUserGroup()
    {
        $usergroup = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
        $this->assertEquals(count($this->subject->getUsergroup()), 0);
        $this->subject->addUsergroup($usergroup);
        $this->assertEquals(count($this->subject->getUsergroup()), 1);
    }

    /**
     * @test
     */
    public function removeUsergroupRemovesUsergroup()
    {
        $usergroup = new \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup('foo');
        $this->subject->addUsergroup($usergroup);
        $this->assertEquals(count($this->subject->getUsergroup()), 1);
        $this->subject->removeUsergroup($usergroup);
        $this->assertEquals(count($this->subject->getUsergroup()), 0);
    }

    /**
     * @test
     */
    public function getNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getName());
    }

    /**
     * @test
     */
    public function setNameSetsName()
    {
        $name = 'don juan';
        $this->subject->setName($name);
        $this->assertSame($name, $this->subject->getName());
    }

    /**
     * @test
     */
    public function getFirstNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function setFirstNameSetsFirstName()
    {
        $firstName = 'don';
        $this->subject->setFirstName($firstName);
        $this->assertSame($firstName, $this->subject->getFirstName());
    }

    /**
     * @test
     */
    public function getMiddleNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function setMiddleNameSetsMiddleName()
    {
        $middleName = 'miguel';
        $this->subject->setMiddleName($middleName);
        $this->assertSame($middleName, $this->subject->getMiddleName());
    }

    /**
     * @test
     */
    public function getLastNameInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function setLastNameSetsLastName()
    {
        $lastName = 'juan';
        $this->subject->setLastName($lastName);
        $this->assertSame($lastName, $this->subject->getLastName());
    }

    /**
     * @test
     */
    public function getAddressInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function setAddressSetsAddress()
    {
        $address = 'foobar 42, foo';
        $this->subject->setAddress($address);
        $this->assertSame($address, $this->subject->getAddress());
    }

    /**
     * @test
     */
    public function getTelephoneInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function setTelephoneSetsTelephone()
    {
        $telephone = '42';
        $this->subject->setTelephone($telephone);
        $this->assertSame($telephone, $this->subject->getTelephone());
    }

    /**
     * @test
     */
    public function getFaxInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getFax());
    }

    /**
     * @test
     */
    public function setFaxSetsFax()
    {
        $fax = '42';
        $this->subject->setFax($fax);
        $this->assertSame($fax, $this->subject->getFax());
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
    public function getLockToDomainInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function setLockToDomainSetsLockToDomain()
    {
        $lockToDomain = 'foo.bar';
        $this->subject->setLockToDomain($lockToDomain);
        $this->assertSame($lockToDomain, $this->subject->getLockToDomain());
    }

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $title = 'foobar';
        $this->subject->setTitle($title);
        $this->assertSame($title, $this->subject->getTitle());
    }

    /**
     * @test
     */
    public function getZipInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getZip());
    }

    /**
     * @test
     */
    public function setZipSetsZip()
    {
        $zip = '42';
        $this->subject->setZip($zip);
        $this->assertSame($zip, $this->subject->getZip());
    }

    /**
     * @test
     */
    public function getCityInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getCity());
    }

    /**
     * @test
     */
    public function setCitySetsCity()
    {
        $city = 'foo';
        $this->subject->setCity($city);
        $this->assertSame($city, $this->subject->getCity());
    }

    /**
     * @test
     */
    public function getCountryInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function setCountrySetsCountry()
    {
        $country = 'foo';
        $this->subject->setCountry($country);
        $this->assertSame($country, $this->subject->getCountry());
    }

    /**
     * @test
     */
    public function getWwwInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getWww());
    }

    /**
     * @test
     */
    public function setWwwSetsWww()
    {
        $www = 'foo.bar';
        $this->subject->setWww($www);
        $this->assertSame($www, $this->subject->getWww());
    }

    /**
     * @test
     */
    public function getCompanyInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function setCompanySetsCompany()
    {
        $company = 'foo bar';
        $this->subject->setCompany($company);
        $this->assertSame($company, $this->subject->getCompany());
    }

    /**
     * @test
     */
    public function getImageInitiallyReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->getImage());
    }

    /**
     * @test
     */
    public function setImageSetsImage()
    {
        $image = 'foobar.gif';
        $this->subject->setImage($image);
        $this->assertSame($image, $this->subject->getImage());
    }

    /**
     * @test
     */
    public function getLastloginInitiallyReturnsNull()
    {
        $this->assertNull($this->subject->getLastlogin());
    }

    /**
     * @test
     */
    public function setLastloginSetsLastlogin()
    {
        $date = new \DateTime();
        $this->subject->setLastlogin($date);
        $this->assertSame($date, $this->subject->getLastlogin());
    }
}
