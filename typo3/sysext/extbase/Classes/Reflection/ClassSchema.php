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

namespace TYPO3\CMS\Extbase\Reflection;

use Doctrine\Common\Annotations\AnnotationReader;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;
use TYPO3\CMS\Extbase\Annotation\ORM\Transient;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Method;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Property;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\PropertyCharacteristics;
use TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Null_;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\CMS\Extbase\Validation\ValidatorClassNameResolver;

/**
 * A class schema
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ClassSchema
{
    private const BIT_CLASS_IS_ENTITY = 1 << 0;
    private const BIT_CLASS_IS_VALUE_OBJECT = 1 << 1;
    private const BIT_CLASS_IS_AGGREGATE_ROOT = 1 << 2;
    private const BIT_CLASS_IS_CONTROLLER = 1 << 3;
    private const BIT_CLASS_IS_SINGLETON = 1 << 4;
    private const BIT_CLASS_HAS_CONSTRUCTOR = 1 << 5;
    private const BIT_CLASS_HAS_INJECT_METHODS = 1 << 6;
    private const BIT_CLASS_HAS_INJECT_PROPERTIES = 1 << 7;

    /**
     * @var BitSet
     */
    private $bitSet;

    /**
     * @var array
     */
    private static $propertyObjects = [];

    /**
     * @var array
     */
    private static $methodObjects = [];

    /**
     * Name of the class this schema is referring to
     *
     * @var string
     */
    protected $className;

    /**
     * Properties of the class which need to be persisted
     *
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var array
     */
    private $injectMethods = [];

    /**
     * @var PropertyInfoExtractor
     */
    private static $propertyInfoExtractor;

    /**
     * @var DocBlockFactory
     */
    private static $docBlockFactory;

    /**
     * Constructs this class schema
     *
     * @param string $className Name of the class this schema is referring to
     * @throws InvalidTypeHintException
     * @throws InvalidValidationConfigurationException
     * @throws \ReflectionException
     */
    public function __construct(string $className)
    {
        /** @var class-string $className */
        $this->className = $className;
        $this->bitSet = new BitSet();

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->implementsInterface(SingletonInterface::class)) {
            $this->bitSet->set(self::BIT_CLASS_IS_SINGLETON);
        }

        if ($reflectionClass->implementsInterface(ControllerInterface::class)) {
            $this->bitSet->set(self::BIT_CLASS_IS_CONTROLLER);
        }

        if ($reflectionClass->isSubclassOf(AbstractEntity::class)) {
            $this->bitSet->set(self::BIT_CLASS_IS_ENTITY);

            $possibleRepositoryClassName = ClassNamingUtility::translateModelNameToRepositoryName($className);
            if (class_exists($possibleRepositoryClassName)) {
                $this->bitSet->set(self::BIT_CLASS_IS_AGGREGATE_ROOT);
            }
        }

        if ($reflectionClass->isSubclassOf(AbstractValueObject::class)) {
            $this->bitSet->set(self::BIT_CLASS_IS_VALUE_OBJECT);
        }

        if (self::$propertyInfoExtractor === null) {
            $docBlockFactory = DocBlockFactory::createInstance();
            $phpDocExtractor = new PhpDocExtractor($docBlockFactory);

            $reflectionExtractor = new ReflectionExtractor();

            self::$propertyInfoExtractor = new PropertyInfoExtractor(
                [],
                [$phpDocExtractor, $reflectionExtractor]
            );
        }

        if (self::$docBlockFactory === null) {
            self::$docBlockFactory = DocBlockFactory::createInstance();
            self::$docBlockFactory->registerTagHandler('author', Null_::class);
            self::$docBlockFactory->registerTagHandler('covers', Null_::class);
            self::$docBlockFactory->registerTagHandler('deprecated', Null_::class);
            self::$docBlockFactory->registerTagHandler('link', Null_::class);
            self::$docBlockFactory->registerTagHandler('method', Null_::class);
            self::$docBlockFactory->registerTagHandler('property-read', Null_::class);
            self::$docBlockFactory->registerTagHandler('property', Null_::class);
            self::$docBlockFactory->registerTagHandler('property-write', Null_::class);
            self::$docBlockFactory->registerTagHandler('return', Null_::class);
            self::$docBlockFactory->registerTagHandler('see', Null_::class);
            self::$docBlockFactory->registerTagHandler('since', Null_::class);
            self::$docBlockFactory->registerTagHandler('source', Null_::class);
            self::$docBlockFactory->registerTagHandler('throw', Null_::class);
            self::$docBlockFactory->registerTagHandler('throws', Null_::class);
            self::$docBlockFactory->registerTagHandler('uses', Null_::class);
            self::$docBlockFactory->registerTagHandler('var', Null_::class);
            self::$docBlockFactory->registerTagHandler('version', Null_::class);
        }

        $this->reflectProperties($reflectionClass);
        $this->reflectMethods($reflectionClass);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    protected function reflectProperties(\ReflectionClass $reflectionClass): void
    {
        $annotationReader = new AnnotationReader();

        $classHasInjectProperties = false;
        $defaultProperties = $reflectionClass->getDefaultProperties();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            // according to https://www.php.net/manual/en/reflectionclass.getdefaultproperties.php
            // > This method only works for static properties when used on internal classes. The default
            // > value of a static class property can not be tracked when using this method on user defined classes.
            $defaultPropertyValue = $reflectionProperty->isStatic() ? null : $defaultProperties[$propertyName] ?? null;

            $propertyCharacteristicsBit = 0;
            $propertyCharacteristicsBit += $reflectionProperty->isPrivate() ? PropertyCharacteristics::VISIBILITY_PRIVATE : 0;
            $propertyCharacteristicsBit += $reflectionProperty->isProtected() ? PropertyCharacteristics::VISIBILITY_PROTECTED : 0;
            $propertyCharacteristicsBit += $reflectionProperty->isPublic() ? PropertyCharacteristics::VISIBILITY_PUBLIC : 0;
            $propertyCharacteristicsBit += $reflectionProperty->isStatic() ? PropertyCharacteristics::IS_STATIC : 0;

            $this->properties[$propertyName] = [
                'c' => null, // cascade
                'd' => $defaultPropertyValue, // defaultValue
                'e' => null, // elementType
                't' => null, // type
                'v' => [], // validators
            ];

            $annotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            /** @var array|Validate[] $validateAnnotations */
            $validateAnnotations = array_filter($annotations, static function ($annotation) {
                return $annotation instanceof Validate;
            });

            if (count($validateAnnotations) > 0) {
                foreach ($validateAnnotations as $validateAnnotation) {
                    $validatorObjectName = ValidatorClassNameResolver::resolve($validateAnnotation->validator);

                    $this->properties[$propertyName]['v'][] = [
                        'name' => $validateAnnotation->validator,
                        'options' => $validateAnnotation->options,
                        'className' => $validatorObjectName,
                    ];
                }
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Lazy::class) instanceof Lazy) {
                $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_LAZY;
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Transient::class) instanceof Transient) {
                $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_TRANSIENT;
            }

            $isInjectProperty = $propertyName !== 'settings' && $reflectionProperty->isPublic()
                && ($annotationReader->getPropertyAnnotation($reflectionProperty, Inject::class) instanceof Inject);

            if ($isInjectProperty) {
                trigger_error(
                    sprintf('Property "%s" of class "%s" uses Extbase\' property injection which is deprecated.', $propertyName, $reflectionClass->getName()),
                    E_USER_DEPRECATED
                );
                $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_INJECT;
                $classHasInjectProperties = true;
            }

            $this->properties[$propertyName]['propertyCharacteristicsBit'] = $propertyCharacteristicsBit;

            /** @var Type[] $types */
            $types = (array)self::$propertyInfoExtractor->getTypes($this->className, $propertyName, ['reflectionProperty' => $reflectionProperty]);
            $typesCount = count($types);

            if ($typesCount !== 1) {
                continue;
            }

            if (($annotation = $annotationReader->getPropertyAnnotation($reflectionProperty, Cascade::class)) instanceof Cascade) {
                /** @var Cascade $annotation */
                $this->properties[$propertyName]['c'] = $annotation->value;
            }

            /** @var Type $type */
            $type = current($types);

            if ($type->isCollection()) {
                $this->properties[$propertyName]['t'] = ltrim($type->getClassName() ?? $type->getBuiltinType(), '\\');

                if (($collectionValueType = ($type->getCollectionValueTypes()[0] ?? null)) instanceof Type) {
                    $this->properties[$propertyName]['e'] = ltrim($collectionValueType->getClassName() ?? $type->getBuiltinType(), '\\');
                }
            } else {
                $this->properties[$propertyName]['t'] = $types[0]->getClassName() ?? $types[0]->getBuiltinType();
            }
        }

        if ($classHasInjectProperties) {
            $this->bitSet->set(self::BIT_CLASS_HAS_INJECT_PROPERTIES);
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @throws InvalidTypeHintException
     * @throws InvalidValidationConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    protected function reflectMethods(\ReflectionClass $reflectionClass): void
    {
        $annotationReader = new AnnotationReader();

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $methodName = $reflectionMethod->getName();

            $this->methods[$methodName] = [];
            $this->methods[$methodName]['private']      = $reflectionMethod->isPrivate();
            $this->methods[$methodName]['protected']    = $reflectionMethod->isProtected();
            $this->methods[$methodName]['public']       = $reflectionMethod->isPublic();
            $this->methods[$methodName]['static']       = $reflectionMethod->isStatic();
            $this->methods[$methodName]['abstract']     = $reflectionMethod->isAbstract();
            $this->methods[$methodName]['params']       = [];
            $this->methods[$methodName]['tags']         = [];
            $this->methods[$methodName]['annotations']  = [];
            $this->methods[$methodName]['isAction']     = str_ends_with($methodName, 'Action');

            $argumentValidators = [];

            $annotations = $annotationReader->getMethodAnnotations($reflectionMethod);

            /** @var array|Validate[] $validateAnnotations */
            $validateAnnotations = array_filter($annotations, static function ($annotation) {
                return $annotation instanceof Validate;
            });

            if ($this->methods[$methodName]['isAction']
                && $this->bitSet->get(self::BIT_CLASS_IS_CONTROLLER)
                && count($validateAnnotations) > 0
            ) {
                foreach ($validateAnnotations as $validateAnnotation) {
                    $validatorName = $validateAnnotation->validator;
                    $validatorObjectName = ValidatorClassNameResolver::resolve($validatorName);

                    $argumentValidators[$validateAnnotation->param][] = [
                        'name' => $validatorName,
                        'options' => $validateAnnotation->options,
                        'className' => $validatorObjectName,
                    ];
                }
            }

            foreach ($annotations as $annotation) {
                if ($annotation instanceof IgnoreValidation) {
                    $this->methods[$methodName]['tags']['ignorevalidation'][] = $annotation->argumentName;
                }
            }

            $docComment = $reflectionMethod->getDocComment();
            $docComment = is_string($docComment) ? $docComment : '';

            foreach ($reflectionMethod->getParameters() as $parameterPosition => $reflectionParameter) {
                /* @var \ReflectionParameter $reflectionParameter */

                $parameterName = $reflectionParameter->getName();

                $ignoreValidationParameters = array_filter($annotations, static function ($annotation) use ($parameterName) {
                    return $annotation instanceof IgnoreValidation && $annotation->argumentName === $parameterName;
                });

                $reflectionType = $reflectionParameter->getType();

                $this->methods[$methodName]['params'][$parameterName] = [];
                $this->methods[$methodName]['params'][$parameterName]['position'] = $parameterPosition; // compat
                $this->methods[$methodName]['params'][$parameterName]['byReference'] = $reflectionParameter->isPassedByReference(); // compat
                $this->methods[$methodName]['params'][$parameterName]['array'] = false; // compat
                $this->methods[$methodName]['params'][$parameterName]['optional'] = $reflectionParameter->isOptional();
                $this->methods[$methodName]['params'][$parameterName]['allowsNull'] = $reflectionParameter->allowsNull();
                $this->methods[$methodName]['params'][$parameterName]['class'] = null; // compat
                $this->methods[$methodName]['params'][$parameterName]['type'] = null;
                $this->methods[$methodName]['params'][$parameterName]['hasDefaultValue'] = $reflectionParameter->isDefaultValueAvailable();
                $this->methods[$methodName]['params'][$parameterName]['defaultValue'] = null;
                $this->methods[$methodName]['params'][$parameterName]['dependency'] = null; // Extbase DI
                $this->methods[$methodName]['params'][$parameterName]['ignoreValidation'] = count($ignoreValidationParameters) === 1;
                $this->methods[$methodName]['params'][$parameterName]['validators'] = [];

                if ($reflectionParameter->isDefaultValueAvailable()) {
                    $this->methods[$methodName]['params'][$parameterName]['defaultValue'] = $reflectionParameter->getDefaultValue();
                }

                // A ReflectionNamedType means "there is a type specified, and it's not a union type."
                // (Union types are not handled, currently.)
                if ($reflectionType instanceof \ReflectionNamedType) {
                    $this->methods[$methodName]['params'][$parameterName]['allowsNull'] = $reflectionType->allowsNull();
                    // A built-in type effectively means "not a class".
                    if ($reflectionType->isBuiltin()) {
                        $this->methods[$methodName]['params'][$parameterName]['array'] = $reflectionType->getName() === 'array'; // compat
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($reflectionType->getName(), '\\');
                    } elseif ($reflectionType->getName() === 'self') {
                        // In addition, self cannot be resolved by "new \ReflectionClass('self')",
                        // so treat this as a reference to the current class
                        $this->methods[$methodName]['params'][$parameterName]['class'] = $reflectionClass->getName();
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($reflectionClass->getName(), '\\');
                    } else {
                        // This is mainly to confirm that the class exists. If it doesn't, a ReflectionException
                        // will be thrown. It's not the ideal way of doing so, but it maintains the existing API
                        // so that the exception can get caught and recast to a TYPO3-specific exception.
                        /** @var class-string<mixed> $classname */
                        $classname = $reflectionType->getName();
                        $reflection = new \ReflectionClass($classname);
                        // There's a single type declaration that is a class.
                        $this->methods[$methodName]['params'][$parameterName]['class'] = $reflectionType->getName();
                        $this->methods[$methodName]['params'][$parameterName]['type'] = $reflectionType->getName();
                    }
                }

                $typeDetectedViaDocBlock = false;
                if ($docComment !== '' && $this->methods[$methodName]['params'][$parameterName]['type'] === null) {
                    /*
                     * We create (redundant) instances here in this loop due to the fact that
                     * we do not want to analyse all doc blocks of all available methods. We
                     * use this technique only if we couldn't grasp all necessary data via
                     * reflection.
                     *
                     * Also, if we analyze all method doc blocks, we will trigger numerous errors
                     * due to non PSR-5 compatible tags in the core and in user land code.
                     *
                     * Fetching the data type via doc blocks is deprecated and will be removed in the near future.
                     */
                    $params = self::$docBlockFactory->create($docComment)
                        ->getTagsByName('param');

                    if (isset($params[$parameterPosition])) {
                        /** @var Param $param */
                        $param = $params[$parameterPosition];
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim((string)$param->getType(), '\\');
                        $typeDetectedViaDocBlock = true;
                    }
                }

                // Extbase DI
                if ($reflectionType instanceof \ReflectionNamedType && !$reflectionType->isBuiltin()
                    && ($reflectionMethod->isConstructor() || $this->hasInjectMethodName($reflectionMethod))
                ) {
                    if ($typeDetectedViaDocBlock) {
                        $parameterType = $this->methods[$methodName]['params'][$parameterName]['type'];
                        $errorMessage = <<<MESSAGE
The type ($parameterType) of parameter \$$parameterName of method $this->className::$methodName() is defined via php DocBlock, which is deprecated and will be removed in TYPO3 12.0. Use a proper parameter type hint instead:
[private|protected|public] function $methodName($parameterType \$$parameterName)
MESSAGE;
                        trigger_error($errorMessage, E_USER_DEPRECATED);
                    }
                    $this->methods[$methodName]['params'][$parameterName]['dependency'] = $reflectionType->getName();
                }

                // Extbase Validation
                if (isset($argumentValidators[$parameterName])) {
                    if ($this->methods[$methodName]['params'][$parameterName]['type'] === null) {
                        throw new InvalidTypeHintException(
                            'Missing type information for parameter "$' . $parameterName . '" in ' . $this->className . '->' . $methodName . '(): Use a type hint.',
                            1515075192
                        );
                    }
                    if ($typeDetectedViaDocBlock) {
                        $parameterType = $this->methods[$methodName]['params'][$parameterName]['type'];
                        $errorMessage = <<<MESSAGE
The type ($parameterType) of parameter \$$parameterName of method $this->className::$methodName() is defined via php DocBlock, which is deprecated and will be removed in TYPO3 12.0. Use a proper parameter type hint instead:
[private|protected|public] function $methodName($parameterType \$$parameterName)
MESSAGE;

                        trigger_error($errorMessage, E_USER_DEPRECATED);
                    }

                    $this->methods[$methodName]['params'][$parameterName]['validators'] = $argumentValidators[$parameterName];
                    unset($argumentValidators[$parameterName]);
                }
            }

            // Extbase Validation
            foreach ($argumentValidators as $parameterName => $validators) {
                $validatorNames = array_column($validators, 'name');

                throw new InvalidValidationConfigurationException(
                    'Invalid validate annotation in ' . $this->className . '->' . $methodName . '(): The following validators have been defined for missing param "$' . $parameterName . '": ' . implode(', ', $validatorNames),
                    1515073585
                );
            }

            // Extbase
            $this->methods[$methodName]['injectMethod'] = false;
            if ($this->hasInjectMethodName($reflectionMethod)
                && count($this->methods[$methodName]['params']) === 1
                && reset($this->methods[$methodName]['params'])['dependency'] !== null
            ) {
                $this->methods[$methodName]['injectMethod'] = true;
                $this->injectMethods[] = $methodName;
            }
        }

        if (isset($this->methods['__construct'])) {
            $this->bitSet->set(self::BIT_CLASS_HAS_CONSTRUCTOR);
        }

        if (count($this->injectMethods) > 0) {
            $this->bitSet->set(self::BIT_CLASS_HAS_INJECT_METHODS);
        }
    }

    /**
     * Returns the class name this schema is referring to
     *
     * @return string The class name
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @throws NoSuchPropertyException
     *
     * @param string $propertyName
     * @return Property
     */
    public function getProperty(string $propertyName): Property
    {
        $properties = $this->buildPropertyObjects();

        if (!isset($properties[$propertyName])) {
            throw NoSuchPropertyException::create($this->className, $propertyName);
        }

        return $properties[$propertyName];
    }

    /**
     * @return array|Property[]
     */
    public function getProperties(): array
    {
        return $this->buildPropertyObjects();
    }

    /**
     * Whether the class is an aggregate root and therefore accessible through
     * a repository.
     *
     * @return bool TRUE if it is managed
     */
    public function isAggregateRoot(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_IS_AGGREGATE_ROOT);
    }

    /**
     * If the class schema has a certain property.
     *
     * @param string $propertyName Name of the property
     * @return bool
     */
    public function hasProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_HAS_CONSTRUCTOR);
    }

    /**
     * @throws NoSuchMethodException
     *
     * @param string $methodName
     * @return Method
     */
    public function getMethod(string $methodName): Method
    {
        $methods = $this->buildMethodObjects();

        if (!isset($methods[$methodName])) {
            throw NoSuchMethodException::create($this->className, $methodName);
        }

        return $methods[$methodName];
    }

    /**
     * @return array|Method[]
     */
    public function getMethods(): array
    {
        return $this->buildMethodObjects();
    }

    /**
     * @param \ReflectionMethod $reflectionMethod
     * @return bool
     */
    protected function hasInjectMethodName(\ReflectionMethod $reflectionMethod): bool
    {
        $methodName = $reflectionMethod->getName();
        if ($methodName === 'injectSettings' || !$reflectionMethod->isPublic()) {
            return false;
        }

        if (
            strpos($reflectionMethod->getName(), 'inject') === 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @internal
     */
    public function isModel(): bool
    {
        return $this->isEntity() || $this->isValueObject();
    }

    /**
     * @return bool
     * @internal
     */
    public function isEntity(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_IS_ENTITY);
    }

    /**
     * @return bool
     * @internal
     */
    public function isValueObject(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_IS_VALUE_OBJECT);
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_IS_SINGLETON);
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function hasMethod(string $methodName): bool
    {
        return isset($this->methods[$methodName]);
    }

    /**
     * @return bool
     */
    public function hasInjectProperties(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_HAS_INJECT_PROPERTIES);
    }

    /**
     * @return bool
     */
    public function hasInjectMethods(): bool
    {
        return $this->bitSet->get(self::BIT_CLASS_HAS_INJECT_METHODS);
    }

    /**
     * @return array|Method[]
     */
    public function getInjectMethods(): array
    {
        return array_filter($this->buildMethodObjects(), static function ($method) {
            /** @var Method $method */
            return $method->isInjectMethod();
        });
    }

    /**
     * @return array|Property[]
     */
    public function getInjectProperties(): array
    {
        return array_filter($this->buildPropertyObjects(), static function ($property) {
            /** @var Property $property */
            return $property->isInjectProperty();
        });
    }

    /**
     * @return array|Property[]
     */
    private function buildPropertyObjects(): array
    {
        if (!isset(self::$propertyObjects[$this->className])) {
            self::$propertyObjects[$this->className] = [];
            foreach ($this->properties as $propertyName => $propertyDefinition) {
                self::$propertyObjects[$this->className][$propertyName] = new Property($propertyName, $propertyDefinition);
            }
        }

        return self::$propertyObjects[$this->className];
    }

    /**
     * @return array|Method[]
     */
    private function buildMethodObjects(): array
    {
        if (!isset(self::$methodObjects[$this->className])) {
            self::$methodObjects[$this->className] = [];
            foreach ($this->methods as $methodName => $methodDefinition) {
                self::$methodObjects[$this->className][$methodName] = new Method($methodName, $methodDefinition, $this->className);
            }
        }

        return self::$methodObjects[$this->className];
    }
}
