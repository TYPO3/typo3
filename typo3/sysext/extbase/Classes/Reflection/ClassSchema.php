<?php
namespace TYPO3\CMS\Extbase\Reflection;

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

use Doctrine\Common\Annotations\AnnotationReader;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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
use TYPO3\CMS\Extbase\Reflection\DocBlock\Tags\Null_;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * A class schema
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ClassSchema
{
    /**
     * Available model types
     */
    private const MODELTYPE_ENTITY = 1;
    private const MODELTYPE_VALUEOBJECT = 2;

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
     * Model type of the class this schema is referring to
     *
     * @var int
     */
    protected $modelType = self::MODELTYPE_ENTITY;

    /**
     * Whether a repository exists for the class this schema is referring to
     *
     * @var bool
     */
    protected $aggregateRoot = false;

    /**
     * Properties of the class which need to be persisted
     *
     * @var array
     */
    protected $properties = [];

    /**
     * Indicates if the class is a singleton or not.
     *
     * @var bool
     */
    private $isSingleton;

    /**
     * @var bool
     */
    private $isController;

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var array
     */
    private $injectProperties = [];

    /**
     * @var array
     */
    private $injectMethods = [];

    /**
     * @var PropertyInfoExtractor
     */
    private static $propertyInfoExtractor;

    /**
     * @var
     */
    private static $docBlockFactory;

    /**
     * Constructs this class schema
     *
     * @param string $className Name of the class this schema is referring to
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException
     * @throws \ReflectionException
     */
    public function __construct($className)
    {
        $this->className = $className;

        $reflectionClass = new \ReflectionClass($className);

        $this->isSingleton = $reflectionClass->implementsInterface(SingletonInterface::class);
        $this->isController = $reflectionClass->implementsInterface(ControllerInterface::class);

        if ($reflectionClass->isSubclassOf(AbstractEntity::class)) {
            $this->modelType = static::MODELTYPE_ENTITY;

            $possibleRepositoryClassName = ClassNamingUtility::translateModelNameToRepositoryName($className);
            if (class_exists($possibleRepositoryClassName)) {
                $this->setAggregateRoot(true);
            }
        }

        if ($reflectionClass->isSubclassOf(AbstractValueObject::class)) {
            $this->modelType = static::MODELTYPE_VALUEOBJECT;
        }

        if (self::$propertyInfoExtractor === null) {
            $docBlockFactory = DocBlockFactory::createInstance();
            $docBlockFactory->registerTagHandler('var', DocBlock\Tags\Var_::class);

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
     */
    protected function reflectProperties(\ReflectionClass $reflectionClass)
    {
        $annotationReader = new AnnotationReader();

        $defaultProperties = $reflectionClass->getDefaultProperties();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();

            $this->properties[$propertyName] = [
                'default'      => $reflectionProperty->isDefault(),
                'defaultValue' => $defaultProperties[$propertyName] ?? null,
                'private'      => $reflectionProperty->isPrivate(),
                'protected'    => $reflectionProperty->isProtected(),
                'public'       => $reflectionProperty->isPublic(),
                'static'       => $reflectionProperty->isStatic(),
                'type'         => null, // Extbase
                'elementType'  => null, // Extbase
                'annotations'  => [],
                'tags'         => [],
                'validators'   => []
            ];

            $this->properties[$propertyName]['annotations']['inject'] = false;
            $this->properties[$propertyName]['annotations']['lazy'] = false;
            $this->properties[$propertyName]['annotations']['transient'] = false;
            $this->properties[$propertyName]['annotations']['type'] = null;
            $this->properties[$propertyName]['annotations']['cascade'] = null;
            $this->properties[$propertyName]['annotations']['dependency'] = null;

            $annotations = $annotationReader->getPropertyAnnotations($reflectionProperty);

            /** @var array|Validate[] $validateAnnotations */
            $validateAnnotations = array_filter($annotations, function ($annotation) {
                return $annotation instanceof Validate;
            });

            if (count($validateAnnotations) > 0) {
                $validatorResolver = GeneralUtility::makeInstance(ValidatorResolver::class);

                foreach ($validateAnnotations as $validateAnnotation) {
                    $validatorObjectName = $validatorResolver->resolveValidatorObjectName($validateAnnotation->validator);

                    $this->properties[$propertyName]['validators'][] = [
                        'name' => $validateAnnotation->validator,
                        'options' => $validateAnnotation->options,
                        'className' => $validatorObjectName,
                    ];
                }
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Lazy::class) instanceof Lazy) {
                $this->properties[$propertyName]['annotations']['lazy'] = true;
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Transient::class) instanceof Transient) {
                $this->properties[$propertyName]['annotations']['transient'] = true;
            }

            $isInjectProperty = $propertyName !== 'settings'
                && ($annotationReader->getPropertyAnnotation($reflectionProperty, Inject::class) instanceof Inject);

            $isPossibleCollectionProperty = $annotationReader->getPropertyAnnotation($reflectionProperty, Transient::class) === null
                && $this->isModel();

            $types = [];
            $typesCount = 0;
            if ($isInjectProperty || $isPossibleCollectionProperty) {
                /** @var Type[] $types */
                $types = (array)self::$propertyInfoExtractor->getTypes($this->className, $propertyName);
                $typesCount = count($types);
            }

            if ($typesCount > 0
                && ($annotation = $annotationReader->getPropertyAnnotation($reflectionProperty, Cascade::class)) instanceof Cascade
            ) {
                /** @var Cascade $annotation */
                $this->properties[$propertyName]['annotations']['cascade'] = $annotation->value;
            }

            if ($isInjectProperty && ($type = $types[0]) instanceof Type) {
                $this->properties[$propertyName]['annotations']['inject'] = true;
                $this->properties[$propertyName]['annotations']['type'] = $type->getClassName();
                $this->properties[$propertyName]['annotations']['dependency'] = $type->getClassName();

                $this->injectProperties[] = $propertyName;
            }

            if ($isPossibleCollectionProperty) {
                if ($typesCount === 1) {
                    $this->properties[$propertyName]['type'] = $types[0]->getClassName() ?? $types[0]->getBuiltinType();
                } elseif ($typesCount === 2) {
                    [$type, $elementType] = $types;
                    $actualType = $type->getClassName() ?? $type->getBuiltinType();

                    if (TypeHandlingUtility::isCollectionType($actualType)
                        && $elementType->getBuiltinType() === 'array'
                        && $elementType->getCollectionValueType() instanceof Type
                        && $elementType->getCollectionValueType()->getClassName() !== null
                    ) {
                        $this->properties[$propertyName]['type'] = ltrim($actualType, '\\');
                        $this->properties[$propertyName]['elementType'] = ltrim($elementType->getCollectionValueType()->getClassName(), '\\');
                    }
                }
            }
        }
    }

    /**
     * @param \ReflectionClass $reflectionClass
     */
    protected function reflectMethods(\ReflectionClass $reflectionClass)
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
            $this->methods[$methodName]['isAction']     = StringUtility::endsWith($methodName, 'Action');

            $argumentValidators = [];

            $annotations = $annotationReader->getMethodAnnotations($reflectionMethod);

            /** @var array|Validate[] $validateAnnotations */
            $validateAnnotations = array_filter($annotations, function ($annotation) {
                return $annotation instanceof Validate;
            });

            if ($this->isController && $this->methods[$methodName]['isAction'] && count($validateAnnotations) > 0) {
                $validatorResolver = GeneralUtility::makeInstance(ValidatorResolver::class);

                foreach ($validateAnnotations as $validateAnnotation) {
                    $validatorName = $validateAnnotation->validator;
                    $validatorObjectName = $validatorResolver->resolveValidatorObjectName($validatorName);

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

                $ignoreValidationParameters = array_filter($annotations, function ($annotation) use ($parameterName) {
                    return $annotation instanceof IgnoreValidation && $annotation->argumentName === $parameterName;
                });

                $this->methods[$methodName]['params'][$parameterName] = [];
                $this->methods[$methodName]['params'][$parameterName]['position'] = $parameterPosition; // compat
                $this->methods[$methodName]['params'][$parameterName]['byReference'] = $reflectionParameter->isPassedByReference(); // compat
                $this->methods[$methodName]['params'][$parameterName]['array'] = $reflectionParameter->isArray(); // compat
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

                if (($reflectionType = $reflectionParameter->getType()) instanceof \ReflectionType) {
                    $this->methods[$methodName]['params'][$parameterName]['type'] = (string)$reflectionType;
                    $this->methods[$methodName]['params'][$parameterName]['allowsNull'] = $reflectionType->allowsNull();
                }

                if (($parameterClass = $reflectionParameter->getClass()) instanceof \ReflectionClass) {
                    $this->methods[$methodName]['params'][$parameterName]['class'] = $parameterClass->getName();
                    $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($parameterClass->getName(), '\\');
                }

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
                     * Fetching the data type via doc blocks will also be deprecated and removed
                     * in the near future.
                     */
                    $params = self::$docBlockFactory->create($docComment)
                        ->getTagsByName('param');

                    if (isset($params[$parameterPosition])) {
                        /** @var Param $param */
                        $param = $params[$parameterPosition];
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($param->getType(), '\\');
                    }
                }

                // Extbase DI
                if ($reflectionParameter->getClass() instanceof \ReflectionClass
                    && ($reflectionMethod->isConstructor() || $this->hasInjectMethodName($reflectionMethod))
                ) {
                    $this->methods[$methodName]['params'][$parameterName]['dependency'] = $reflectionParameter->getClass()->getName();
                }

                // Extbase Validation
                if (isset($argumentValidators[$parameterName])) {
                    if ($this->methods[$methodName]['params'][$parameterName]['type'] === null) {
                        throw new InvalidTypeHintException(
                            'Missing type information for parameter "$' . $parameterName . '" in ' . $this->className . '->' . $methodName . '(): Either use an @param annotation or use a type hint.',
                            1515075192
                        );
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
     * Marks the class if it is root of an aggregate and therefore accessible
     * through a repository - or not.
     *
     * @param bool $isRoot TRUE if it is the root of an aggregate
     */
    public function setAggregateRoot($isRoot)
    {
        $this->aggregateRoot = $isRoot;
    }

    /**
     * Whether the class is an aggregate root and therefore accessible through
     * a repository.
     *
     * @return bool TRUE if it is managed
     */
    public function isAggregateRoot(): bool
    {
        return $this->aggregateRoot;
    }

    /**
     * If the class schema has a certain property.
     *
     * @param string $propertyName Name of the property
     * @return bool
     */
    public function hasProperty($propertyName): bool
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return isset($this->methods['__construct']);
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
     * @return array
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
        return $this->modelType === static::MODELTYPE_ENTITY;
    }

    /**
     * @return bool
     * @internal
     */
    public function isValueObject(): bool
    {
        return $this->modelType === static::MODELTYPE_VALUEOBJECT;
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->isSingleton;
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
        return count($this->injectProperties) > 0;
    }

    /**
     * @return bool
     */
    public function hasInjectMethods(): bool
    {
        return count($this->injectMethods) > 0;
    }

    /**
     * @return array|Method[]
     */
    public function getInjectMethods(): array
    {
        return array_filter($this->buildMethodObjects(), function ($method) {
            /** @var Method $method */
            return $method->isInjectMethod();
        });
    }

    /**
     * @return array
     */
    public function getInjectProperties(): array
    {
        $injectProperties = [];
        foreach ($this->injectProperties as $injectPropertyName) {
            $injectProperties[$injectPropertyName] = $this->properties[$injectPropertyName]['annotations']['dependency'];
        }

        return $injectProperties;
    }

    /**
     * @return array
     */
    private function buildPropertyObjects(): array
    {
        if (!isset(static::$propertyObjects[$this->className])) {
            static::$propertyObjects[$this->className] = [];
            foreach ($this->properties as $propertyName => $propertyDefinition) {
                static::$propertyObjects[$this->className][$propertyName] = new Property($propertyName, $propertyDefinition);
            }
        }

        return static::$propertyObjects[$this->className];
    }

    /**
     * @return array|Method[]
     */
    private function buildMethodObjects(): array
    {
        if (!isset(static::$methodObjects[$this->className])) {
            static::$methodObjects[$this->className] = [];
            foreach ($this->methods as $methodName => $methodDefinition) {
                static::$methodObjects[$this->className][$methodName] = new Method($methodName, $methodDefinition, $this->className);
            }
        }

        return static::$methodObjects[$this->className];
    }
}
