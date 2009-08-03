<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @version $Id$
 */
/**
 * Example domain class which can be used to test different view helpers, e.g. the "select" view helper.
 *
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
