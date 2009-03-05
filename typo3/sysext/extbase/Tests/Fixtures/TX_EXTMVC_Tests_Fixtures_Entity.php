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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/DomainObject/TX_EXTMVC_DomainObject_Entity.php');

/**
 * An entity
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the entity's name
	 *
	 * @return string The entity's name
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getName() {
		return $this->name;
	}
	
	// /**
	//  * Mock method
	//  *
	//  * @return void
	//  * @author Jochen Rau <jochen.rau@typoplanet.de>
	//  */
	// public function _memorizeCleanState() {
	// }

}
?>