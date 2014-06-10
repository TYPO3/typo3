<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Model of frontend response
 */
class Parser implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $paths = array();

	/**
	 * @var array
	 */
	protected $records = array();

	/**
	 * @return array
	 */
	public function getPaths() {
		return $this->paths;
	}

	/**
	 * @return array
	 */
	public function getRecords() {
		return $this->records;
	}

	/**
	 * @param array $structure
	 * @param array $path
	 */
	public function parse(array $structure, array $path = array()) {
		$this->process($structure);
	}

	/**
	 * @param array $iterator
	 * @param array $path
	 */
	protected function process(array $iterator, array $path = array()) {
		foreach ($iterator as $identifier => $properties) {
			$this->addRecord($identifier, $properties);
			$this->addPath($identifier, $path);
			foreach ($properties as $propertyName => $propertyValue) {
				if (!is_array($propertyValue)) {
					continue;
				}
				$nestedPath = array_merge($path, array($identifier, $propertyName));
				$this->process($propertyValue, $nestedPath);
			}
		}
	}

	/**
	 * @param string $identifier
	 * @param array $properties
	 */
	protected function addRecord($identifier, array $properties) {
		if (isset($this->records[$identifier])) {
			return;
		}

		foreach ($properties as $propertyName => $propertyValue) {
			if (is_array($propertyValue)) {
				unset($properties[$propertyName]);
			}
		}

		$this->records[$identifier] = $properties;
	}

	/**
	 * @param string $identifier
	 * @param array $path
	 */
	protected function addPath($identifier, array $path) {
		if (!isset($this->paths[$identifier])) {
			$this->paths[$identifier] = array();
		}

		$this->paths[$identifier][] = $path;
	}

}
