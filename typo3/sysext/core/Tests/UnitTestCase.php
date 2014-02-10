<?php
namespace TYPO3\CMS\Core\Tests;

/***************************************************************
 * Copyright notice
 *
 * (c) 2005-2013 Robert Lemke (robert@typo3.org)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Base test case for unit tests.
 *
 * This class currently only inherits the base test case. However, it is recommended
 * to extend this class for unit test cases instead of the base test case because if,
 * at some point, specific behavior needs to be implemented for unit tests, your test cases
 * will profit from it automatically.
 *
 */
abstract class UnitTestCase extends BaseTestCase {

	/**
	 * TODO: make LoadedExtensionsArray serializable instead
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT');

	/**
	 * Unset all additional properties of test classes to help PHP
	 * garbage collection. This reduces memory footprint with lots
	 * of tests.
	 *
	 * If owerwriting tearDown() in test classes, please call
	 * parent::tearDown() at the end. Unsetting of own properties
	 * is not needed this way.
	 *
	 * @return void
	 */
	protected function tearDown() {
		$reflection = new \ReflectionObject($this);
		foreach ($reflection->getProperties() as $property) {
			$declaringClass = $property->getDeclaringClass()->getName();
			if (
				!$property->isStatic()
				&& $declaringClass !== 'TYPO3\CMS\Core\Tests\UnitTestCase'
				&& $declaringClass !== 'TYPO3\CMS\Core\Tests\BaseTestCase'
				&& strpos($property->getDeclaringClass()->getName(), 'PHPUnit_') !== 0
			) {
				$propertyName = $property->getName();
				unset($this->$propertyName);
			}
		}
		unset($reflection);
	}
}
