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
 * The mother of all test cases.
 *
 * Don't sub class this test case but rather choose a more specialized base test case,
 * such as UnitTestCase or FunctionalTestCase
 *
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * Whether global variables should be backed up
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Whether static attributes should be backed up
	 *
	 * @var boolean
	 */
	protected $backupStaticAttributes = FALSE;

	/**
	 * Creates a mock object which allows for calling protected methods and access of protected properties.
	 *
	 * @param string $originalClassName name of class to create the mock object of, must not be empty
	 * @param array<string> $methods name of the methods to mock
	 * @param array $arguments arguments to pass to constructor
	 * @param string $mockClassName the class name to use for the mock class
	 * @param boolean $callOriginalConstructor whether to call the constructor
	 * @param boolean $callOriginalClone whether to call the __clone method
	 * @param boolean $callAutoload whether to call any autoload function
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface
	 *         a mock of $originalClassName with access methods added
	 *
	 * @see \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase::getAccessibleMock
	 */
	protected function getAccessibleMock(
		$originalClassName, array $methods = array(), array $arguments = array(), $mockClassName = '',
		$callOriginalConstructor = TRUE, $callOriginalClone = TRUE, $callAutoload = TRUE
	) {
		if ($originalClassName === '') {
			throw new \InvalidArgumentException('$originalClassName must not be empty.', 1334701880);
		}

		return $this->getMock(
			$this->buildAccessibleProxy($originalClassName),
			$methods,
			$arguments,
			$mockClassName,
			$callOriginalConstructor,
			$callOriginalClone,
			$callAutoload
		);
	}

	/**
	 * Creates a proxy class of the specified class which allows
	 * for calling even protected methods and access of protected properties.
	 *
	 * @param string $className Name of class to make available, must not be empty
	 *
	 * @return string Fully qualified name of the built class, will not be empty
	 *
	 * @see Tx_Extbase_Tests_Unit_BaseTestCase::buildAccessibleProxy
	 */
	protected function buildAccessibleProxy($className) {
		$accessibleClassName = uniqid('Tx_Phpunit_AccessibleProxy');
		$class = new \ReflectionClass($className);
		$abstractModifier = $class->isAbstract() ? 'abstract ' : '';

		eval(
			$abstractModifier . 'class ' . $accessibleClassName .
				' extends ' . $className . ' implements \TYPO3\CMS\Core\Tests\AccessibleObjectInterface {' .
					'public function _call($methodName) {' .
						'if ($methodName === \'\') {' .
							'throw new \InvalidArgumentException(\'$methodName must not be empty.\', 1334663993);' .
						'}' .
						'$args = func_get_args();' .
						'return call_user_func_array(array($this, $methodName), array_slice($args, 1));' .
					'}' .
					'public function _callRef(' .
						'$methodName, &$arg1 = NULL, &$arg2 = NULL, &$arg3 = NULL, &$arg4 = NULL, &$arg5= NULL, &$arg6 = NULL, ' .
						'&$arg7 = NULL, &$arg8 = NULL, &$arg9 = NULL' .
					') {' .
						'if ($methodName === \'\') {' .
							'throw new \InvalidArgumentException(\'$methodName must not be empty.\', 1334664210);' .
						'}' .
						'switch (func_num_args()) {' .
							'case 0:' .
								'throw new RuntimeException(\'The case of 0 arguments is not supposed to happen.\', 1334703124);' .
								'break;' .
							'case 1:' .
								'$returnValue = $this->$methodName();' .
								'break;' .
							'case 2:' .
								'$returnValue = $this->$methodName($arg1);' .
								'break;' .
							'case 3:' .
								'$returnValue = $this->$methodName($arg1, $arg2);' .
								'break;' .
							'case 4:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3);' .
								'break;' .
							'case 5:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3, $arg4);' .
								'break;' .
							'case 6:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5);' .
								'break;' .
							'case 7:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6);' .
								'break;' .
							'case 8:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7);' .
								'break;' .
							'case 9:' .
								'$returnValue = $this->$methodName($arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8);' .
								'break;' .
							'case 10:' .
								'$returnValue = $this->$methodName(' .
									'$arg1, $arg2, $arg3, $arg4, $arg5, $arg6, $arg7, $arg8, $arg9' .
								');' .
								'break;' .
							'default:' .
								'throw new \InvalidArgumentException(' .
									'\'_callRef currently only allows calls to methods with no more than 9 parameters.\'' .
								');' .
						'}' .
						'return $returnValue;' .
					'}' .
					'public function _set($propertyName, $value) {' .
						'if ($propertyName === \'\') {' .
							'throw new \InvalidArgumentException(\'$propertyName must not be empty.\', 1334664355);' .
						'}' .
						'$this->$propertyName = $value;' .
					'}' .
					'public function _setRef($propertyName, &$value) {' .
						'if ($propertyName === \'\') {' .
							'throw new \InvalidArgumentException(\'$propertyName must not be empty.\', 1334664545);' .
						'}' .
						'$this->$propertyName = $value;' .
					'}' .
					'public function _setStatic($propertyName, $value) {' .
						'if ($propertyName === \'\') {' .
							'throw new \InvalidArgumentException(\'$propertyName must not be empty.\', 1344242602);' .
						'}' .
						'self::$$propertyName = $value;' .
					'}' .
					'public function _get($propertyName) {' .
						'if ($propertyName === \'\') {' .
							'throw new \InvalidArgumentException(\'$propertyName must not be empty.\', 1334664967);' .
						'}' .
						'return $this->$propertyName;' .
					'}' .
					'public function _getStatic($propertyName) {' .
						'if ($propertyName === \'\') {' .
							'throw new \InvalidArgumentException(\'$propertyName must not be empty.\', 1344242603);' .
						'}' .
						'return self::$$propertyName;' .
					'}' .
			'}'
		);

		return $accessibleClassName;
	}

	/**
	 * Helper function to call protected or private methods
	 *
	 * @param object $object The object to be invoked
	 * @param string $name the name of the method to call
	 * @return mixed
	 */
	protected function callInaccessibleMethod($object, $name) {
		// Remove first two arguments ($object and $name)
		$arguments = func_get_args();
		array_splice($arguments, 0, 2);

		$reflectionObject = new \ReflectionObject($object);
		$reflectionMethod = $reflectionObject->getMethod($name);
		$reflectionMethod->setAccessible(TRUE);
		return $reflectionMethod->invokeArgs($object, $arguments);
	}
}
?>