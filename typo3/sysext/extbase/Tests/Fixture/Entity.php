<?php
namespace TYPO3\CMS\Extbase\Tests\Fixture;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class Entity extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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