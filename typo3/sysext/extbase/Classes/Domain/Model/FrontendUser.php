<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A Frontend User
 *
 * @package Extbase
 * @subpackage Domain\Model
 * @version $Id: FrontendUser.php 1949 2010-03-04 06:40:56Z jocrau $
 * @scope prototype
 * @entity
 * @api
 */
class Tx_Extbase_Domain_Model_FrontendUser extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FrontendUserGroup>
	 */
	protected $usergroup;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $firstName;

	/**
	 * @var string
	 */
	protected $middleName;

	/**
	 * @var string
	 */
	protected $lastName;

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
	 * @var string
	 */
	protected $image = '';

	/**
	 * @var DateTime
	 */
	protected $lastlogin = '';

	/**
	 * @var DateTime
	 */
	protected $isOnline = '';

	/**
	 * Constructs a new Front-End User
	 *
	 * @api
	 */
	public function __construct($username = '', $password = '') {
		$this->username = $username;
		$this->password = $password;
		$this->usergroup = new Tx_Extbase_Persistence_ObjectStorage();
	}

	/**
	 * Sets the username value
	 *
	 * @param string $username
	 * @return void
	 * @api
	 */
	public function setUsername($username) {
		$this->username = $username;
	}

	/**
	 * Returns the username value
	 *
	 * @return string
	 * @api
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Sets the password value
	 *
	 * @param string $password
	 * @return void
	 * @api
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Returns the password value
	 *
	 * @return string
	 * @api
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Sets the usergroups. Keep in mind that the property is called "usergroup"
	 * although it can hold several usergroups.
	 *
	 * @param Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Domain_Model_FrontendUserGroup> $usergroup An object storage containing the usergroups to add
	 * @return void
	 * @api
	 */
	public function setUsergroup(Tx_Extbase_Persistence_ObjectStorage $usergroup) {
		$this->usergroup = $usergroup;
	}

	/**
	 * Adds a usergroup to the frontend user
	 *
	 * @param Tx_Extbase_Domain_Model_FrontendUserGroup $usergroup
	 * @return void
	 * @api
	 */
	public function addUsergroup(Tx_Extbase_Domain_Model_FrontendUserGroup $usergroup) {
		$this->usergroup->attach($usergroup);
	}

	/**
	 * Removes a usergroup from the frontend user
	 *
	 * @param Tx_Extbase_Domain_Model_FrontendUserGroup $usergroup
	 * @return void
	 * @api
	 */
	public function removeUsergroup(Tx_Extbase_Domain_Model_FrontendUserGroup $usergroup) {
		$this->usergroup->detach($usergroup);
	}

	/**
	 * Returns the usergroups. Keep in mind that the property is called "usergroup"
	 * although it can hold several usergroups.
	 *
	 * @return Tx_Extbase_Persistence_ObjectStorage An object storage containing the usergroup
	 * @api
	 */
	public function getUsergroups() {
		return $this->usergroup;
	}

	/**
	 * Sets the name value
	 *
	 * @param string $name
	 * @return void
	 * @api
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name value
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the firstName value
	 *
	 * @param string $firstName
	 * @return void
	 * @api
	 */
	public function setFirstName($firstName) {
		$this->firstName = $firstName;
	}

	/**
	 * Returns the firstName value
	 *
	 * @return string
	 * @api
	 */
	public function getFirstName() {
		return $this->firstName;
	}

	/**
	 * Sets the middleName value
	 *
	 * @param string $middleName
	 * @return void
	 * @api
	 */
	public function setMiddleName($middleName) {
		$this->middleName = $middleName;
	}

	/**
	 * Returns the middleName value
	 *
	 * @return string
	 * @api
	 */
	public function getMiddleName() {
		return $this->middleName;
	}

	/**
	 * Sets the lastName value
	 *
	 * @param string $lastName
	 * @return void
	 * @api
	 */
	public function setLastName($lastName) {
		$this->lastName = $lastName;
	}

	/**
	 * Returns the lastName value
	 *
	 * @return string
	 * @api
	 */
	public function getLastName() {
		return $this->lastName;
	}

	/**
	 * Sets the address value
	 *
	 * @param string $address
	 * @return void
	 * @api
	 */
	public function setAddress($address) {
		$this->address = $address;
	}

	/**
	 * Returns the address value
	 *
	 * @return string
	 * @api
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * Sets the telephone value
	 *
	 * @param string $telephone
	 * @return void
	 * @api
	 */
	public function setTelephone($telephone) {
		$this->telephone = $telephone;
	}

	/**
	 * Returns the telephone value
	 *
	 * @return string
	 * @api
	 */
	public function getTelephone() {
		return $this->telephone;
	}

	/**
	 * Sets the fax value
	 *
	 * @param string $fax
	 * @return void
	 * @api
	 */
	public function setFax($fax) {
		$this->fax = $fax;
	}

	/**
	 * Returns the fax value
	 *
	 * @return string
	 * @api
	 */
	public function getFax() {
		return $this->fax;
	}

	/**
	 * Sets the email value
	 *
	 * @param string $email
	 * @return void
	 * @api
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Returns the email value
	 *
	 * @return string
	 * @api
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Sets the lockToDomain value
	 *
	 * @param string $lockToDomain
	 * @return void
	 * @api
	 */
	public function setLockToDomain($lockToDomain) {
		$this->lockToDomain = $lockToDomain;
	}

	/**
	 * Returns the lockToDomain value
	 *
	 * @return string
	 * @api
	 */
	public function getLockToDomain() {
		return $this->lockToDomain;
	}

	/**
	 * Sets the title value
	 *
	 * @param string $title
	 * @return void
	 * @api
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the title value
	 *
	 * @return string
	 * @api
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the zip value
	 *
	 * @param string $zip
	 * @return void
	 * @api
	 */
	public function setZip($zip) {
		$this->zip = $zip;
	}

	/**
	 * Returns the zip value
	 *
	 * @return string
	 * @api
	 */
	public function getZip() {
		return $this->zip;
	}

	/**
	 * Sets the city value
	 *
	 * @param string $city
	 * @return void
	 * @api
	 */
	public function setCity($city) {
		$this->city = $city;
	}

	/**
	 * Returns the city value
	 *
	 * @return string
	 * @api
	 */
	public function getCity() {
		return $this->city;
	}

	/**
	 * Sets the country value
	 *
	 * @param string $country
	 * @return void
	 * @api
	 */
	public function setCountry($country) {
		$this->country = $country;
	}

	/**
	 * Returns the country value
	 *
	 * @return string
	 * @api
	 */
	public function getCountry() {
		return $this->country;
	}

	/**
	 * Sets the www value
	 *
	 * @param string $www
	 * @return void
	 * @api
	 */
	public function setWww($www) {
		$this->www = $www;
	}

	/**
	 * Returns the www value
	 *
	 * @return string
	 * @api
	 */
	public function getWww() {
		return $this->www;
	}

	/**
	 * Sets the company value
	 *
	 * @param string $company
	 * @return void
	 * @api
	 */
	public function setCompany($company) {
		$this->company = $company;
	}

	/**
	 * Returns the company value
	 *
	 * @return string
	 * @api
	 */
	public function getCompany() {
		return $this->company;
	}

	/**
	 * Sets the image value
	 *
	 * @param string $image
	 * @return void
	 * @api
	 */
	public function setImage($image) {
		$this->image = $image;
	}

	/**
	 * Returns the image value
	 *
	 * @return string
	 * @api
	 */
	public function getImage() {
		return $this->image;
	}

	/**
	 * Sets the lastLogin value
	 *
	 * @param DateTime $lastLogin
	 * @return void
	 * @api
	 */
	public function setLastLogin(DateTime $lastLogin) {
		$this->lastLogin = $lastLogin;
	}

	/**
	 * Returns the lastLogin value
	 *
	 * @return DateTime
	 * @api
	 */
	public function getLastLogin() {
		return $this->lastLogin;
	}

	/**
	 * Sets the isOnline value
	 *
	 * @param DateTime $isOnline
	 * @return void
	 * @api
	 */
	public function setIsOnline($isOnline) {
		$this->isOnline = $isOnline;
	}

	/**
	 * Returns the isOnline value
	 *
	 * @return DateTime
	 * @api
	 */
	public function getIsOnline() {
		return $this->isOnline;
	}

}
?>