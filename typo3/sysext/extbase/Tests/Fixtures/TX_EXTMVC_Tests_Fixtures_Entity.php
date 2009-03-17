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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/DomainObject/TX_EXTMVC_DomainObject_Entity.php');

/**
 * An entity
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 * @entity
 */
class TX_EXTMVC_Tests_Fixtures_Entity extends TX_EXTMVC_DomainObject_Entity {

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
	
	// /**
	//  * Mock method
	//  *
	//  * @return void
	//  */
	// public function _memorizeCleanState() {
	// }

}
?>