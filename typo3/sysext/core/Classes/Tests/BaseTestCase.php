<?php
namespace TYPO3\CMS\Core\Tests;

/*
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

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * The mother of all test cases.
 *
 * Don't sub class this test case but rather choose a more specialized base test case,
 * such as UnitTestCase or FunctionalTestCase
 *
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Whether global variables should be backed up
     *
     * @var bool
     */
    protected $backupGlobals = true;

    /**
     * Whether static attributes should be backed up
     *
     * @var bool
     */
    protected $backupStaticAttributes = false;

    /**
     * Creates a mock object which allows for calling protected methods and access of protected properties.
     *
     * @param string $originalClassName name of class to create the mock object of, must not be empty
     * @param string[]|null $methods name of the methods to mock, null for "mock no methods"
     * @param array $arguments arguments to pass to constructor
     * @param string $mockClassName the class name to use for the mock class
     * @param bool $callOriginalConstructor whether to call the constructor
     * @param bool $callOriginalClone whether to call the __clone method
     * @param bool $callAutoload whether to call any autoload function
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     *         a mock of $originalClassName with access methods added
     *
     * @throws \InvalidArgumentException
     */
    protected function getAccessibleMock(
        $originalClassName, $methods = [], array $arguments = [], $mockClassName = '',
        $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true
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
     * Returns a mock object which allows for calling protected methods and access
     * of protected properties. Concrete methods to mock can be specified with
     * the last parameter
     *
     * @param string $originalClassName Full qualified name of the original class
     * @param array $arguments
     * @param string $mockClassName
     * @param bool $callOriginalConstructor
     * @param bool $callOriginalClone
     * @param bool $callAutoload
     * @param array $mockedMethods
     *
     * @throws \InvalidArgumentException
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     *
     */
    protected function getAccessibleMockForAbstractClass(
        $originalClassName, array $arguments = [], $mockClassName = '',
        $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $mockedMethods = []
    ) {
        if ($originalClassName === '') {
            throw new \InvalidArgumentException('$originalClassName must not be empty.', 1384268260);
        }

        return $this->getMockForAbstractClass(
            $this->buildAccessibleProxy($originalClassName),
            $arguments,
            $mockClassName,
            $callOriginalConstructor,
            $callOriginalClone,
            $callAutoload,
            $mockedMethods
        );
    }

    /**
     * Creates a proxy class of the specified class which allows
     * for calling even protected methods and access of protected properties.
     *
     * @param string $className Name of class to make available, must not be empty
     * @return string Fully qualified name of the built class, will not be empty
     */
    protected function buildAccessibleProxy($className)
    {
        $accessibleClassName = $this->getUniqueId('Tx_Phpunit_AccessibleProxy');
        $class = new \ReflectionClass($className);
        $abstractModifier = $class->isAbstract() ? 'abstract ' : '';

        eval(
            $abstractModifier . 'class ' . $accessibleClassName .
                ' extends ' . $className . ' implements ' . \TYPO3\CMS\Core\Tests\AccessibleObjectInterface::class . ' {' .
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
    protected function callInaccessibleMethod($object, $name)
    {
        // Remove first two arguments ($object and $name)
        $arguments = func_get_args();
        array_splice($arguments, 0, 2);

        $reflectionObject = new \ReflectionObject($object);
        $reflectionMethod = $reflectionObject->getMethod($name);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($object, $arguments);
    }

    /**
     * Injects $dependency into property $name of $target
     *
     * This is a convenience method for setting a protected or private property in
     * a test subject for the purpose of injecting a dependency.
     *
     * @param object $target The instance which needs the dependency
     * @param string $name Name of the property to be injected
     * @param mixed $dependency The dependency to inject – usually an object but can also be any other type
     * @return void
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function inject($target, $name, $dependency)
    {
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
            $property->setAccessible(true);
            $property->setValue($target, $dependency);
        } else {
            throw new \RuntimeException('Could not inject ' . $name . ' into object of type ' . get_class($target));
        }
    }

    /**
     * Create and return a unique id optionally prepended by a given string
     *
     * This function is used because on windows and in cygwin environments uniqid() has a resolution of one second which
     * results in identical ids if simply uniqid('Foo'); is called.
     *
     * @param string $prefix
     * @return string
     */
    protected function getUniqueId($prefix = '')
    {
        return $prefix . StringUtility::getUniqueId(mt_rand());
    }
}
