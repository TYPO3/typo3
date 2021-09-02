<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Object\Container;

use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * Internal TYPO3 Dependency Injection container
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 * @deprecated since v11, will be removed in v12. Use symfony DI and GeneralUtility::makeInstance() instead.
 *             See TYPO3 explained documentation for more information.
 *             Does not trigger_error since the ObjectManager->get() call does that.
 * @template T
 */
class Container implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ContainerInterface
     */
    private $psrContainer;

    /**
     * registered alternative implementations of a class
     * e.g. used to know the class for an AbstractClass or a Dependency
     *
     * @var array
     */
    private $alternativeImplementation;

    /**
     * @var InstantiatorInterface
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
     * @param ContainerInterface $psrContainer
     */
    public function __construct(ContainerInterface $psrContainer)
    {
        $this->psrContainer = $psrContainer;
    }

    /**
     * Internal method to create the class instantiator, extracted to be mockable
     *
     * @return InstantiatorInterface
     */
    protected function getInstantiator(): InstantiatorInterface
    {
        if ($this->instantiator == null) {
            $this->instantiator = new Instantiator();
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
    public function getInstance(string $className, array $givenConstructorArguments = []): object
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
    public function getEmptyObject(string $className): object
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
     * @param array<int,mixed> $givenConstructorArguments the list of constructor arguments as array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception\CannotBuildObjectException
     * @return object&T the built object
     */
    protected function getInstanceInternal(string $className, ...$givenConstructorArguments): object
    {
        if ($className === ContainerInterface::class) {
            return $this->psrContainer;
        }

        $className = $this->getImplementationClassName($className);

        if ($givenConstructorArguments === [] && $this->psrContainer->has($className)) {
            $instance = $this->psrContainer->get($className);
            if (!is_object($instance)) {
                throw new Exception('PSR-11 container returned non object for class name "' . $className . '".', 1562240407);
            }
            return $instance;
        }

        $className = ClassLoadingInformation::getClassNameForAlias($className);
        if (isset($this->singletonInstances[$className])) {
            if (!empty($givenConstructorArguments)) {
                throw new Exception('Object "' . $className . '" fetched from singleton cache, thus, explicit constructor arguments are not allowed.', 1292857934);
            }
            return $this->singletonInstances[$className];
        }

        $classSchema = $this->getReflectionService()->getClassSchema($className);
        $classIsSingleton = $classSchema->isSingleton();
        if (!$classIsSingleton) {
            if (array_key_exists($className, $this->prototypeObjectsWhichAreCurrentlyInstanciated) !== false) {
                throw new CannotBuildObjectException('Cyclic dependency in prototype object, for class "' . $className . '".', 1295611406);
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
     * Instantiates an object, possibly setting the constructor dependencies.
     * Additionally, directly registers all singletons in the singleton registry,
     * such that circular references of singletons are correctly instantiated.
     *
     * @param ClassSchema $classSchema
     * @param array<int,mixed> $givenConstructorArguments
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @return object the new instance
     */
    protected function instanciateObject(ClassSchema $classSchema, ...$givenConstructorArguments): object
    {
        $className = $classSchema->getClassName();
        $classIsSingleton = $classSchema->isSingleton();
        if ($classIsSingleton && !empty($givenConstructorArguments)) {
            throw new Exception('Object "' . $className . '" has explicit constructor arguments but is a singleton; this is not allowed.', 1292858051);
        }
        $constructorArguments = $this->getConstructorArguments($classSchema, $givenConstructorArguments);
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
    protected function injectDependencies(object $instance, ClassSchema $classSchema): void
    {
        if (!$classSchema->hasInjectMethods() && !$classSchema->hasInjectProperties()) {
            return;
        }
        foreach ($classSchema->getInjectMethods() as $injectMethodName => $injectMethod) {
            if (($classNameToInject = $injectMethod->getFirstParameter()->getDependency()) === null) {
                continue;
            }
            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classSchema->isSingleton() && !$instanceToInject instanceof SingletonInterface) {
                $this->logger->notice('The singleton "{class}" needs a prototype in "{method}". This is often a bad code smell; often you rather want to inject a singleton.', [
                    'class' => $classSchema->getClassName(),
                    'method' => $injectMethodName,
                ]);
            }
            if (is_callable([$instance, $injectMethodName])) {
                $instance->{$injectMethodName}($instanceToInject);
            }
        }
        foreach ($classSchema->getInjectProperties() as $injectPropertyName => $injectProperty) {
            if (($classNameToInject = $injectProperty->getType()) === null) {
                continue;
            }

            $instanceToInject = $this->getInstanceInternal($classNameToInject);
            if ($classSchema->isSingleton() && !$instanceToInject instanceof SingletonInterface) {
                $this->logger->notice('The singleton "{class}" needs a prototype in "{method}". This is often a bad code smell; often you rather want to inject a singleton.', [
                    'class' => $classSchema->getClassName(),
                    'method' => $injectPropertyName,
                ]);
            }

            $instance->{$injectPropertyName} = $instanceToInject;
        }
    }

    /**
     * Call object initializer if present in object
     *
     * @param object $instance
     */
    protected function initializeObject(object $instance): void
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
     * @todo deprecate in favor of core DI configuration (aliases/overrides)
     */
    public function registerImplementation(string $className, string $alternativeClassName): void
    {
        $this->alternativeImplementation[$className] = $alternativeClassName;
    }

    /**
     * gets array of parameter that can be used to call a constructor
     *
     * @param ClassSchema $classSchema
     * @param array $givenConstructorArguments
     * @throws \InvalidArgumentException
     * @return array
     */
    private function getConstructorArguments(ClassSchema $classSchema, array $givenConstructorArguments): array
    {
        // @todo: -> private function getConstructorArguments(Method $constructor, array $givenConstructorArguments)

        if (!$classSchema->hasConstructor()) {
            // todo: this check needs to take place outside this method
            // todo: Instead of passing a ClassSchema object in here, all we need is a Method object instead
            return [];
        }

        $arguments = [];
        foreach ($classSchema->getMethod('__construct')->getParameters() as $methodParameter) {
            $index = $methodParameter->getPosition();

            // Constructor argument given AND argument is a simple type OR instance of argument type
            if (array_key_exists($index, $givenConstructorArguments) &&
                ($methodParameter->getDependency() === null || is_a($givenConstructorArguments[$index], $methodParameter->getDependency()))
            ) {
                $argument = $givenConstructorArguments[$index];
            } else {
                if ($methodParameter->getDependency() !== null && !$methodParameter->hasDefaultValue()) {
                    $argument = $this->getInstanceInternal($methodParameter->getDependency());
                    if ($classSchema->isSingleton() && !$argument instanceof SingletonInterface) {
                        $this->logger->notice('The singleton "{class_name}" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', ['class_name' => $classSchema->getClassName()]);
                        // todo: the whole injection is flawed anyway, why would we care about injecting prototypes? so, wayne?
                        // todo: btw: if this is important, we can already detect this case in the class schema.
                    }
                } elseif ($methodParameter->hasDefaultValue() === true) {
                    $argument = $methodParameter->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException('not a correct info array of constructor dependencies was passed!', 1476107941);
                }
            }
            $arguments[] = $argument;
        }
        return $arguments;
    }

    /**
     * Returns the class name for a new instance, taking into account the
     * class-extension API.
     *
     * @param string $className Base class name to evaluate
     * @return string Final class name to instantiate with "new [classname]
     */
    public function getImplementationClassName(string $className): string
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
     * Lazy load ReflectionService.
     *
     * Required as this class is being loaded in ext_localconf.php and we MUST not
     * create caches in ext_localconf.php (which ReflectionService needs to do).
     *
     * @return ReflectionService
     */
    protected function getReflectionService(): ReflectionService
    {
        return $this->reflectionService ?? ($this->reflectionService = $this->psrContainer->get(ReflectionService::class));
    }
}
