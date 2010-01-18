<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3. 
*  All credits go to the v5 team.
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
 * An entity
 *
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 * @entity
 */
class Tx_Extbase_Tests_Fixtures_Entity extends Tx_Extbase_DomainObject_AbstractEntity {

	/**
	 * The entity's name
	 *
	 * @var string
	 */
	protected $name;


	/**
	 * Constructs this entity
	 *
	 * @param string $name Name of this blog
	 * @return void
	 */
	public function __construct($name) {
		$this->setName($name);
	}
	
	/**
	 * Sets this entity's name
	 *
	 * @param string $name The entity's name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the entity's name
	 *
	 * @return string The entity's name
	 */
	public function getName() {
		return $this->name;
	}
	
}
?>