<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Collection of backend layouts.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class BackendLayoutCollection {

	/**
	 * @var string
	 */
	protected $identifier;

	/**
	 * @var array|BackendLayout[]
	 */
	protected $backendLayouts = array();

	/**
	 * @param string $identifier
	 */
	public function __construct($identifier) {
		$this->setIdentifier($identifier);
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $identifier
	 * @throws \UnexpectedValueException
	 */
	public function setIdentifier($identifier) {
		if (strpos($identifier, '__') !== FALSE) {
			throw new \UnexpectedValueException(
				'Identifier "' . $identifier . '" must not contain "__"',
				1381597631
			);
		}

		$this->identifier = $identifier;
	}

	/**
	 * Adds a backend layout to this collection.
	 *
	 * @param BackendLayout $backendLayout
	 * @throws \LogicException
	 */
	public function add(BackendLayout $backendLayout) {
		$identifier = $backendLayout->getIdentifier();

		if (strpos($identifier, '__') !== FALSE) {
			throw new \UnexpectedValueException(
				'BackendLayout Identifier "' . $identifier . '" must not contain "__"',
				1381597628
			);
		}

		if (isset($this->backendLayouts[$identifier])) {
			throw new \LogicException(
				'Backend Layout ' . $identifier . ' is already defined',
				1381559376
			);
		}

		$this->backendLayouts[$identifier] = $backendLayout;
	}

	/**
	 * Gets a backend layout by (regular) identifier.
	 *
	 * @param string $identifier
	 * @return NULL|BackendLayout
	 */
	public function get($identifier) {
		$backendLayout = NULL;

		if (isset($this->backendLayouts[$identifier])) {
			$backendLayout = $this->backendLayouts[$identifier];
		}

		return $backendLayout;
	}

	/**
	 * Gets all backend layouts in this collection.
	 *
	 * @return array|BackendLayout[]
	 */
	public function getAll() {
		return $this->backendLayouts;
	}

}