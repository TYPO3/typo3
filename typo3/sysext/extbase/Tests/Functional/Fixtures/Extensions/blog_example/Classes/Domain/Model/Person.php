<?php
namespace ExtbaseTeam\BlogExample\Domain\Model;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  (c) 2011 Bastian Waidelich <bastian@typo3.org>
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
 * A person - acting as author
 */
class Person extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 */
	protected $firstname = '';

	/**
	 * @var string
	 */
	protected $lastname = '';

	/**
	 * @var string
	 */
	protected $email = '';

	/**
	 * Constructs a new Person
	 *
	 */
	public function __construct($firstname, $lastname, $email) {
		$this->setFirstname($firstname);
		$this->setLastname($lastname);
		$this->setEmail($email);
	}

	/**
	 * Sets this persons's firstname
	 *
	 * @param string $firstname The person's firstname
	 * @return void
	 */
	public function setFirstname($firstname) {
		$this->firstname = $firstname;
	}

	/**
	 * Returns the person's firstname
	 *
	 * @return string The persons's firstname
	 */
	public function getFirstname() {
		return $this->firstname;
	}

	/**
	 * Sets this persons's lastname
	 *
	 * @param string $lastname The person's lastname
	 * @return void
	 */
	public function setLastname($lastname) {
		$this->lastname = $lastname;
	}

	/**
	 * Returns the person's lastname
	 *
	 * @return string The persons's lastname
	 */
	public function getLastname() {
		return $this->lastname;
	}

	/**
	 * Returns the person's full name
	 *
	 * @return string The persons's lastname
	 */
	public function getFullName() {
		return $this->firstname . ' ' . $this->lastname;
	}

	/**
	 * Sets this persons's email adress
	 *
	 * @param string $email The person's email adress
	 * @return void
	 */
	public function setEmail($email) {
		$this->email = $email;
	}

	/**
	 * Returns the person's email address
	 *
	 * @return string The persons's email address
	 */
	public function getEmail() {
		return $this->email;
	}

}
?>