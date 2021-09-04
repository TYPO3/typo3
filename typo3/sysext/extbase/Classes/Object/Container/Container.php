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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * Internal TYPO3 Dependency Injection container
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 * @template T
 */
class Container implements \TYPO3\CMS\Core\SingletonInterface
{
    const SCOPE_PROTOTYPE = 1;
    const SCOPE_SINGLETON = 2;

    /**
     * registered alternative implementations of a class
     * e.g. used to know the class for an AbstractClass or a Dependency
     *
     * @var array
     */
    private $alternativeImplementation;

    /**
     * @var \Doctrine\Instantiator\InstantiatorInterface
     */
    protected $instantiator;

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
     * @var ReflectionService
     */
    private $reflectionService;

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
     * @param string|class-string<T> $className
     * @param array $givenConstructorArguments the list of constructor arguments as array
     * @return object&T the built object
     * @internal
     */
    public function getInstance($className, $givenConstructorArguments = [])
    {
        $this->prototypeObjectsWhichAreCurrentlyInstanciated = [];
        return $this->getInstanceInternal($className, ...$givenConstructorArguments);
    }

    /**
     * Create an instance of $className without calling its constructor
     *
     * @param string|class-string<T> $className
     * @return object&T
     */
    public function getEmptyObject($className)
    {
        $className = $this->getImplementationClassName($className);
        $classSchema = $this->getReflectionService()->getClassSchema($className);
        $object = $this->getInstantiator()->instantiate($className);
        $this->injectDependencies($object, $classSchema);
        $this->initializeObject($object);
        return $object;
    }

    /**
     * Internal implementation for getting a class.
     *
     * @param string|class-string<T> $className
     * @param array $givenConstructorArguments the list of constructor arguments as array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException
     * @return object&T the built object
     */
    protected function getInstanceInternal($className, ...$givenConstructorArguments)
    {
        $className = $this->getImplementationClassName($className);
        if ($className === \TYPO3\CMS\Extbase\Object\Container\Container::class) {
            return $this;
        }
        if ($className === \TYPO3\CMS\Core\Cache\CacheManager::class) {
            return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
        }
        if ($className === \TYPO3\CMS\Core\Package\PackageManager::class) {
            return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
        }
        $className = \TYPO3\CMS\Core\Core\ClassLoadingInformation::getClassNameForAlias($className);
        if (isset($this->singletonInstances[$className])) {
            if (!empty($givenConstructorArguments)) {
                throw new \TYPO3\CMS\Extbase\Object\Exception('Object "' . $className . '" fetched from singleton cache, thus, explicit constructor arguments are not allowed.', 1292857934);
            }
            return $this->singletonInstances[$className];
        }

        $classSchema = $this->getReflectionService()->getClassSchema($className);
        $classIsSingleton = $classSchema->isSingleton();
        if (!$classIsSingleton) {
            if (array_key_exists($className, $this->prototypeObjectsWhichAreCurrentlyInstanciated) !== false) {
                throw new \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException('Cyclic dependency in prototype object, for class "' . $className . '".', 1295611406);
            }
            $this->prototypeObjectsWhichAreCurrentlyInstanciated[$className] = true;
        }
        $instance = $this->instanciateObject($classSchema, ...$givenConstructorArguments);
        $this->injectDependencies($instance, $classSchema);
        $this->initializeObject($instance);
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
     * @param ClassSchema $classSchema
     * @param array $givenConstructorArguments
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @return object the new instance
     */
    protected function instanciateObject(ClassSchema $classSchema, ...$givenConstructorArguments)
    {
        $className = $classSchema->getClassName();
        $classIsSingleton = $classSchema->isSingleton();
        if ($classIsSingleton && !empty($givenConstructorArguments)) {
            throw new \TYPO3\CMS\Extbase\Object\Exception('Object "' . $className . '" has explicit constructor arguments but is a singleton; this is not allowed.', 1292858051);
        }
        $constructorArguments = $this->getConstructorArguments($className, $classSchema, $givenConstructorArguments);
        $instance = GeneralUtility::makeInstance($className, ...$constructorArguments);
        if ($classIsSingleton) {
            $this->singletonInstances[$className] = $instance;
        }
        return $instance;
    }

    /**
     * Inject setter-dependencies into $instance
     *
     * @param object $instance
     * @param ClassSchema $classSchema
     */
    protected function injectDependencies($instance, ClassSchema $classSchema)
    {
        if (!$classSchema->hasInjectMethods() && !$classSchema->hasInjectProperties()) {
            return;
        }
        foreach ($classSchema->getInjectMethods() as $injectMethodName => $classNameToInject) {
            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classSchema->isSingleton() && !$instanceToInject instanceof \TYPO3\CMS\Core\SingletonInterface) {
                $this->getLogger()->notice('The singleton "' . $classSchema->getClassName() . '" needs a prototype in "' . $injectMethodName . '". This is often a bad code smell; often you rather want to inject a singleton.');
            }
            if (is_callable([$instance, $injectMethodName])) {
                $instance->{$injectMethodName}($instanceToInject);
            }
        }
        foreach ($classSchema->getInjectProperties() as $injectPropertyName => $classNameToInject) {
            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classSchema->isSingleton() && !$instanceToInject instanceof \TYPO3\CMS\Core\SingletonInterface) {
                $this->getLogger()->notice('The singleton "' . $classSchema->getClassName() . '" needs a prototype in "' . $injectPropertyName . '". This is often a bad code smell; often you rather want to inject a singleton.');
            }

            if ($classSchema->getProperty($injectPropertyName)['public']) {
                $instance->{$injectPropertyName} = $instanceToInject;
            } else {
                $propertyReflection = new \ReflectionProperty($instance, $injectPropertyName);
                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($instance, $instanceToInject);
            }
        }
    }

    /**
     * Call object initializer if present in object
     *
     * @param object $instance
     */
    protected function initializeObject($instance)
    {
        if (method_exists($instance, 'initializeObject') && is_callable([$instance, 'initializeObject'])) {
            $instance->initializeObject();
        }
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
     * @param ClassSchema $classSchema
     * @param array $givenConstructorArguments
     * @throws \InvalidArgumentException
     * @return array
     */
    private function getConstructorArguments($className, ClassSchema $classSchema, array $givenConstructorArguments)
    {
        $parameters = [];
        $constructorArgumentInformation = $classSchema->getConstructorArguments();
        foreach ($constructorArgumentInformation as $constructorArgumentName => $argumentInformation) {
            $index = $argumentInformation['position'];

            // Constructor argument given AND argument is a simple type OR instance of argument type
            if (array_key_exists($index, $givenConstructorArguments) && (!isset($argumentInformation['dependency']) || is_a($givenConstructorArguments[$index], $argumentInformation['dependency']))) {
                $parameter = $givenConstructorArguments[$index];
            } else {
                if (isset($argumentInformation['dependency']) && $argumentInformation['hasDefaultValue'] === false) {
                    $parameter = $this->getInstanceInternal($argumentInformation['dependency']);
                    if ($classSchema->isSingleton() && !$parameter instanceof \TYPO3\CMS\Core\SingletonInterface) {
                        $this->getLogger()->notice('The singleton "' . $className . '" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.');
                    }
                } elseif ($argumentInformation['hasDefaultValue'] === true) {
                    $parameter = $argumentInformation['defaultValue'];
                } else {
                    throw new \InvalidArgumentException('not a correct info array of constructor dependencies was passed!', 1476107941);
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
     * @param string $className
     *
     * @return bool
     */
    public function isSingleton($className)
    {
        return $this->getReflectionService()->getClassSchema($className)->isSingleton();
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

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    /**
     * Lazy load ReflectionService.
     *
     * Required as this class is being loaded in ext_localconf.php and we MUST not
     * create caches in ext_localconf.php (which ReflectionService needs to do).
     *
     * @return ReflectionService
     */
    protected function getReflectionService(): ReflectionService
    {
        return $this->reflectionService ?? ($this->reflectionService = GeneralUtility::makeInstance(ReflectionService::class, GeneralUtility::makeInstance(CacheManager::class)));
    }
}
