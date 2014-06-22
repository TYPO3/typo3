<?php
namespace TYPO3\CMS\Core\Tests;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
