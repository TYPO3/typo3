<?php
namespace TYPO3\CMS\Extbase\Object\Container;

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

/**
 * Internal TYPO3 Dependency Injection container
 */
class Container implements \TYPO3\CMS\Core\SingletonInterface
{
    const SCOPE_PROTOTYPE = 1;
    const SCOPE_SINGLETON = 2;

    /**
     * internal cache for classinfos
     *
     * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfoCache
     */
    private $cache = null;

    /**
     * registered alternative implementations of a class
     * e.g. used to know the class for an AbstractClass or a Dependency
     *
     * @var array
     */
    private $alternativeImplementation;

    /**
     * reference to the classinfofactory, that analyses dependencys
     *
     * @var \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory
     */
    private $classInfoFactory = null;

    /**
     * @var \Doctrine\Instantiator\InstantiatorInterface
     */
    protected $instantiator = null;

    /**
     * holds references of singletons
     *
     * @var array
     */
    private $singletonInstances = [];

    /**
     * Array of prototype objects currently being built, to prevent recursion.
     *
     * @var array
     */
    private $prototypeObjectsWhichAreCurrentlyInstanciated;

    /**
     * Constructor is protected since container should
     * be a singleton.
     *
     * @see getContainer()
     */
    public function __construct()
    {
    }

    /**
     * Internal method to create the classInfoFactory, extracted to be mockable.
     *
     * @return \TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory
     */
    protected function getClassInfoFactory()
    {
        if ($this->classInfoFactory == null) {
            $this->classInfoFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\ClassInfoFactory::class);
        }
        return $this->classInfoFactory;
    }

    /**
     * Internal method to create the classInfoCache, extracted to be mockable.
     *
     * @return \TYPO3\CMS\Extbase\Object\Container\ClassInfoCache
     */
    protected function getClassInfoCache()
    {
        if ($this->cache == null) {
            $this->cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\ClassInfoCache::class);
        }
        return $this->cache;
    }

    /**
     * Internal method to create the class instantiator, extracted to be mockable
     *
     * @return \Doctrine\Instantiator\InstantiatorInterface
     */
    protected function getInstantiator()
    {
        if ($this->instantiator == null) {
            $this->instantiator = new \Doctrine\Instantiator\Instantiator();
        }
        return $this->instantiator;
    }

    /**
     * Main method which should be used to get an instance of the wished class
     * specified by $className.
     *
     * @param string $className
     * @param array $givenConstructorArguments the list of constructor arguments as array
     * @return object the built object
     */
    public function getInstance($className, $givenConstructorArguments = [])
    {
        $this->prototypeObjectsWhichAreCurrentlyInstanciated = [];
        return $this->getInstanceInternal($className, $givenConstructorArguments);
    }

    /**
     * Create an instance of $className without calling its constructor
     *
     * @param string $className
     * @return object
     */
    public function getEmptyObject($className)
    {
        $className = $this->getImplementationClassName($className);
        $classInfo = $this->getClassInfo($className);
        $object = $this->getInstantiator()->instantiate($className);
        $this->injectDependencies($object, $classInfo);
        $this->initializeObject($object, $classInfo);
        return $object;
    }

    /**
     * Internal implementation for getting a class.
     *
     * @param string $className
     * @param array $givenConstructorArguments the list of constructor arguments as array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException
     * @return object the built object
     */
    protected function getInstanceInternal($className, $givenConstructorArguments = [])
    {
        $className = $this->getImplementationClassName($className);
        if ($className === \TYPO3\CMS\Extbase\Object\Container\Container::class) {
            return $this;
        }
        if ($className === \TYPO3\CMS\Core\Cache\CacheManager::class) {
            return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        }
        if ($className === \TYPO3\CMS\Core\Package\PackageManager::class) {
            return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        }
        $className = \TYPO3\CMS\Core\Core\ClassLoadingInformation::getClassNameForAlias($className);
        if (isset($this->singletonInstances[$className])) {
            if (!empty($givenConstructorArguments)) {
                throw new \TYPO3\CMS\Extbase\Object\Exception('Object "' . $className . '" fetched from singleton cache, thus, explicit constructor arguments are not allowed.', 1292857934);
            }
            return $this->singletonInstances[$className];
        }
        $classInfo = $this->getClassInfo($className);
        $classIsSingleton = $classInfo->getIsSingleton();
        if (!$classIsSingleton) {
            if (array_key_exists($className, $this->prototypeObjectsWhichAreCurrentlyInstanciated) !== false) {
                throw new \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException('Cyclic dependency in prototype object, for class "' . $className . '".', 1295611406);
            }
            $this->prototypeObjectsWhichAreCurrentlyInstanciated[$className] = true;
        }
        $instance = $this->instanciateObject($classInfo, $givenConstructorArguments);
        $this->injectDependencies($instance, $classInfo);
        $this->initializeObject($instance, $classInfo);
        if (!$classIsSingleton) {
            unset($this->prototypeObjectsWhichAreCurrentlyInstanciated[$className]);
        }
        return $instance;
    }

    /**
     * Instanciates an object, possibly setting the constructor dependencies.
     * Additionally, directly registers all singletons in the singleton registry,
     * such that circular references of singletons are correctly instanciated.
     *
     * @param \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo
     * @param array $givenConstructorArguments
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @return object the new instance
     */
    protected function instanciateObject(\TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo, array $givenConstructorArguments)
    {
        $className = $classInfo->getClassName();
        $classIsSingleton = $classInfo->getIsSingleton();
        if ($classIsSingleton && !empty($givenConstructorArguments)) {
            throw new \TYPO3\CMS\Extbase\Object\Exception('Object "' . $className . '" has explicit constructor arguments but is a singleton; this is not allowed.', 1292858051);
        }
        $constructorArguments = $this->getConstructorArguments($className, $classInfo, $givenConstructorArguments);
        array_unshift($constructorArguments, $className);
        $instance = call_user_func_array([\TYPO3\CMS\Core\Utility\GeneralUtility::class, 'makeInstance'], $constructorArguments);
        if ($classIsSingleton) {
            $this->singletonInstances[$className] = $instance;
        }
        return $instance;
    }

    /**
     * Inject setter-dependencies into $instance
     *
     * @param object $instance
     * @param \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo
     * @return void
     */
    protected function injectDependencies($instance, \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo)
    {
        if (!$classInfo->hasInjectMethods() && !$classInfo->hasInjectProperties()) {
            return;
        }
        foreach ($classInfo->getInjectMethods() as $injectMethodName => $classNameToInject) {
            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classInfo->getIsSingleton() && !$instanceToInject instanceof \TYPO3\CMS\Core\SingletonInterface) {
                $this->log('The singleton "' . $classInfo->getClassName() . '" needs a prototype in "' . $injectMethodName . '". This is often a bad code smell; often you rather want to inject a singleton.', 1);
            }
            if (is_callable([$instance, $injectMethodName])) {
                $instance->{$injectMethodName}($instanceToInject);
            }
        }
        foreach ($classInfo->getInjectProperties() as $injectPropertyName => $classNameToInject) {
            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classInfo->getIsSingleton() && !$instanceToInject instanceof \TYPO3\CMS\Core\SingletonInterface) {
                $this->log('The singleton "' . $classInfo->getClassName() . '" needs a prototype in "' . $injectPropertyName . '". This is often a bad code smell; often you rather want to inject a singleton.', 1);
            }
            $propertyReflection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Reflection\PropertyReflection::class, $instance, $injectPropertyName);

            $propertyReflection->setAccessible(true);
            $propertyReflection->setValue($instance, $instanceToInject);
        }
    }

    /**
     * Call object initializer if present in object
     *
     * @param object $instance
     * @param \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo
     */
    protected function initializeObject($instance, \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo)
    {
        if ($classInfo->getIsInitializeable() && is_callable([$instance, 'initializeObject'])) {
            $instance->initializeObject();
        }
    }

    /**
     * Wrapper for dev log, in order to ease testing
     *
     * @param string $message Message (in english).
     * @param int $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     * @return void
     */
    protected function log($message, $severity)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($message, 'extbase', $severity);
    }

    /**
     * register a classname that should be used if a dependency is required.
     * e.g. used to define default class for an interface
     *
     * @param string $className
     * @param string $alternativeClassName
     */
    public function registerImplementation($className, $alternativeClassName)
    {
        $this->alternativeImplementation[$className] = $alternativeClassName;
    }

    /**
     * gets array of parameter that can be used to call a constructor
     *
     * @param string $className
     * @param \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo
     * @param array $givenConstructorArguments
     * @throws \InvalidArgumentException
     * @return array
     */
    private function getConstructorArguments($className, \TYPO3\CMS\Extbase\Object\Container\ClassInfo $classInfo, array $givenConstructorArguments)
    {
        $parameters = [];
        $constructorArgumentInformation = $classInfo->getConstructorArguments();
        foreach ($constructorArgumentInformation as $index => $argumentInformation) {
            // Constructor argument given AND argument is a simple type OR instance of argument type
            if (array_key_exists($index, $givenConstructorArguments) && (!isset($argumentInformation['dependency']) || is_a($givenConstructorArguments[$index], $argumentInformation['dependency']))) {
                $parameter = $givenConstructorArguments[$index];
            } else {
                if (isset($argumentInformation['dependency']) && !array_key_exists('defaultValue', $argumentInformation)) {
                    $parameter = $this->getInstanceInternal($argumentInformation['dependency']);
                    if ($classInfo->getIsSingleton() && !$parameter instanceof \TYPO3\CMS\Core\SingletonInterface) {
                        $this->log('The singleton "' . $className . '" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', 1);
                    }
                } elseif (array_key_exists('defaultValue', $argumentInformation)) {
                    $parameter = $argumentInformation['defaultValue'];
                } else {
                    throw new \InvalidArgumentException('not a correct info array of constructor dependencies was passed!');
                }
            }
            $parameters[] = $parameter;
        }
        return $parameters;
    }

    /**
     * Returns the class name for a new instance, taking into account the
     * class-extension API.
     *
     * @param string $className Base class name to evaluate
     * @return string Final class name to instantiate with "new [classname]
     */
    public function getImplementationClassName($className)
    {
        if (isset($this->alternativeImplementation[$className])) {
            $className = $this->alternativeImplementation[$className];
        }
        if (substr($className, -9) === 'Interface') {
            $className = substr($className, 0, -9);
        }
        return $className;
    }

    /**
     * Gets Classinfos for the className - using the cache and the factory
     *
     * @param string $className
     * @return \TYPO3\CMS\Extbase\Object\Container\ClassInfo
     */
    private function getClassInfo($className)
    {
        $classNameHash = md5($className);
        $classInfo = $this->getClassInfoCache()->get($classNameHash);
        if (!$classInfo instanceof \TYPO3\CMS\Extbase\Object\Container\ClassInfo) {
            $classInfo = $this->getClassInfoFactory()->buildClassInfoFromClassName($className);
            $this->getClassInfoCache()->set($classNameHash, $classInfo);
        }
        return $classInfo;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function isSingleton($className)
    {
        return $this->getClassInfo($className)->getIsSingleton();
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function isPrototype($className)
    {
        return !$this->isSingleton($className);
    }
}
