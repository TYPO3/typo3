<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

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
 * A Frontend User
 *
 * @api
 */
class FrontendUser extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $username = '';

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup>
     */
    protected $usergroup;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $firstName = '';

    /**
     * @var string
     */
    protected $middleName = '';

    /**
     * @var string
     */
    protected $lastName = '';

    /**
     * @var string
     */
    protected $address = '';

    /**
     * @var string
     */
    protected $telephone = '';

    /**
     * @var string
     */
    protected $fax = '';

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var string
     */
    protected $lockToDomain = '';

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $zip = '';

    /**
     * @var string
     */
    protected $city = '';

    /**
     * @var string
     */
    protected $country = '';

    /**
     * @var string
     */
    protected $www = '';

    /**
     * @var string
     */
    protected $company = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Domain\Model\FileReference>
     */
    protected $image = null;

    /**
     * @var \DateTime|null
     */
    protected $lastlogin = null;

    /**
     * Constructs a new Front-End User
     *
     * @param string $username
     * @param string $password
     * @api
     */
    public function __construct($username = '', $password = '')
    {
        $this->username = $username;
        $this->password = $password;
        $this->usergroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Sets the username value
     *
     * @param string $username
     * @api
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Returns the username value
     *
     * @return string
     * @api
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the password value
     *
     * @param string $password
     * @api
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the password value
     *
     * @return string
     * @api
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the usergroups. Keep in mind that the property is called "usergroup"
     * although it can hold several usergroups.
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $usergroup
     * @api
     */
    public function setUsergroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $usergroup)
    {
        $this->usergroup = $usergroup;
    }

    /**
     * Adds a usergroup to the frontend user
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $usergroup
     * @api
     */
    public function addUsergroup(\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $usergroup)
    {
        $this->usergroup->attach($usergroup);
    }

    /**
     * Removes a usergroup from the frontend user
     *
     * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $usergroup
     * @api
     */
    public function removeUsergroup(\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup $usergroup)
    {
        $this->usergroup->detach($usergroup);
    }

    /**
     * Returns the usergroups. Keep in mind that the property is called "usergroup"
     * although it can hold several usergroups.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage An object storage containing the usergroup
     * @api
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    /**
     * Sets the name value
     *
     * @param string $name
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the name value
     *
     * @return string
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the firstName value
     *
     * @param string $firstName
     * @api
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Returns the firstName value
     *
     * @return string
     * @api
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Sets the middleName value
     *
     * @param string $middleName
     * @api
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
    }

    /**
     * Returns the middleName value
     *
     * @return string
     * @api
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Sets the lastName value
     *
     * @param string $lastName
     * @api
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Returns the lastName value
     *
     * @return string
     * @api
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Sets the address value
     *
     * @param string $address
     * @api
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Returns the address value
     *
     * @return string
     * @api
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Sets the telephone value
     *
     * @param string $telephone
     * @api
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * Returns the telephone value
     *
     * @return string
     * @api
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Sets the fax value
     *
     * @param string $fax
     * @api
     */
    public function setFax($fax)
    {
        $this->fax = $fax;
    }

    /**
     * Returns the fax value
     *
     * @return string
     * @api
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * Sets the email value
     *
     * @param string $email
     * @api
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the email value
     *
     * @return string
     * @api
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the lockToDomain value
     *
     * @param string $lockToDomain
     * @api
     */
    public function setLockToDomain($lockToDomain)
    {
        $this->lockToDomain = $lockToDomain;
    }

    /**
     * Returns the lockToDomain value
     *
     * @return string
     * @api
     */
    public function getLockToDomain()
    {
        return $this->lockToDomain;
    }

    /**
     * Sets the title value
     *
     * @param string $title
     * @api
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the title value
     *
     * @return string
     * @api
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the zip value
     *
     * @param string $zip
     * @api
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Returns the zip value
     *
     * @return string
     * @api
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Sets the city value
     *
     * @param string $city
     * @api
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Returns the city value
     *
     * @return string
     * @api
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Sets the country value
     *
     * @param string $country
     * @api
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country value
     *
     * @return string
     * @api
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the www value
     *
     * @param string $www
     * @api
     */
    public function setWww($www)
    {
        $this->www = $www;
    }

    /**
     * Returns the www value
     *
     * @return string
     * @api
     */
    public function getWww()
    {
        return $this->www;
    }

    /**
     * Sets the company value
     *
     * @param string $company
     * @api
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Returns the company value
     *
     * @return string
     * @api
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Sets the image value
     *
     * @api
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $image
     */
    public function setImage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $image)
    {
        $this->image = $image;
    }

    /**
     * Gets the image value
     *
     * @api
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Sets the lastlogin value
     *
     * @param \DateTime $lastlogin
     * @api
     */
    public function setLastlogin(\DateTime $lastlogin)
    {
        $this->lastlogin = $lastlogin;
    }

    /**
     * Returns the lastlogin value
     *
     * @return \DateTime
     * @api
     */
    public function getLastlogin()
    {
        return $this->lastlogin;
    }
}
