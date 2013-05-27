<?php
namespace TYPO3\CMS\Extbase\Tests\Unit;

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
 * Base testcase for the Extbase extension.
 */
abstract class BaseTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
	 */
	protected $objectManager;

	/**
	 * Injects an untainted clone of the object manager and all its referencing
	 * objects for every test.
	 *
	 * @return void
	 */
	public function runBare() {
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->objectManager = clone $objectManager;
		parent::runBare();
	}

	/**
	 * Injects $dependency into property $name of $target
	 *
	 * This is a convenience method for setting a protected or private property in
	 * a test subject for the purpose of injecting a dependency.
	 *
	 * @param object $target The instance which needs the dependency
	 * @param string $name Name of the property to be injected
	 * @param object $dependency The dependency to inject – usually an object but can also be any other type
	 * @return void
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	protected function inject($target, $name, $dependency) {
		if (!is_object($target)) {
			throw new \InvalidArgumentException('Wrong type for argument $target, must be object.');
		}

		$objectReflection = new \ReflectionObject($target);
		$methodNamePart = strtoupper($name[0]) . substr($name, 1);
		if ($objectReflection->hasMethod('set' . $methodNamePart)) {
			$methodName = 'set' . $methodNamePart;
			$target->$methodName($dependency);
		} elseif ($objectReflection->hasMethod('inject' . $methodNamePart)) {
			$methodName = 'inject' . $methodNamePart;
			$target->$methodName($dependency);
		} elseif ($objectReflection->hasProperty($name)) {
			$property = $objectReflection->getProperty($name);
			$property->setAccessible(TRUE);
			$property->setValue($target, $dependency);
		} else {
			throw new \RuntimeException('Could not inject ' . $name . ' into object of type ' . get_class($target));
		}
	}
}

?>