<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Example domain class which can be used to test different view helpers, e.g. the "select" view helper.
 *
 * @version $Id: Fixture_UserDomainClass.php 3350 2009-10-27 12:01:08Z k-fish $
 * @package Fluid
 * @subpackage ViewHelpers\Fixtures
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_ViewHelpers_Fixtures_UserDomainClass {

	protected $id;

	protected $firstName;

	protected $lastName;

	/**
	 * Constructor.
	 *
	 * @param int $id
	 * @param string $firstName
	 * @param string $lastName
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($id, $firstName, $lastName) {
		$this->id = $id;
		$this->firstName = $firstName;
		$this->lastName = $lastName;
	}

	/**
	 * Return the ID
	 *
	 * @return int ID
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Return the first name
	 *
	 * @return string first name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getFirstName() {
		return $this->firstName;
	}

	/**
	 * Return the last name
	 *
	 * @return string lastname
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getLastName() {
		return $this->lastName;
	}
}


?>
