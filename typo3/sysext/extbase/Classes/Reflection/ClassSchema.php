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
    const MODELTYPE_ENTITY = 1;
    const MODELTYPE_VALUEOBJECT = 2;

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
    protected $modelType = 0;

    /**
     * Whether a repository exists for the class this schema is referring to
     *
     * @var bool
     */
    protected $aggregateRoot = false;

    /**
     * The name of the property holding the uuid of an entity, if any.
     *
     * @var string
     */
    protected $uuidPropertyName;

    /**
     * Properties of the class which need to be persisted
     *
     * @var array
     */
    protected $properties = [];

    /**
     * The properties forming the identity of an object
     *
     * @var array
     */
    protected $identityProperties = [];

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
    private $methods;

    /**
     * @var array
     */
    private $tags;

    /**
     * @var array
     */
    private $injectProperties = [];

    /**
     * @var array
     */
    private $injectMethods = [];

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

        $docCommentParser = new DocCommentParser(true);
        $docCommentParser->parseDocComment($reflectionClass->getDocComment());
        $this->tags = $docCommentParser->getTagsValues();

        $this->reflectProperties($reflectionClass);
        $this->reflectMethods($reflectionClass);
    }

    /**
     * @param \ReflectionClass $reflectionClass
     */
    protected function reflectProperties(\ReflectionClass $reflectionClass)
    {
        $annotationReader = new AnnotationReader();

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();

            $this->properties[$propertyName] = [
                'default'     => $reflectionProperty->isDefault(),
                'private'     => $reflectionProperty->isPrivate(),
                'protected'   => $reflectionProperty->isProtected(),
                'public'      => $reflectionProperty->isPublic(),
                'static'      => $reflectionProperty->isStatic(),
                'type'        => null, // Extbase
                'elementType' => null, // Extbase
                'annotations' => [],
                'tags'        => [],
                'validators'  => []
            ];

            $docCommentParser = new DocCommentParser(true);
            $docCommentParser->parseDocComment($reflectionProperty->getDocComment());
            foreach ($docCommentParser->getTagsValues() as $tag => $values) {
                $this->properties[$propertyName]['tags'][strtolower($tag)] = $values;
            }

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

            if ($docCommentParser->isTaggedWith('validate')) {
                trigger_error(
                    sprintf(
                        'Property %s::%s is tagged with @validate which is deprecated and will be removed in TYPO3 v10.0.',
                        $reflectionClass->getName(),
                        $reflectionProperty->getName()
                    ),
                    E_USER_DEPRECATED
                );

                $validatorResolver = GeneralUtility::makeInstance(ValidatorResolver::class);

                $validateValues = $docCommentParser->getTagValues('validate');
                foreach ($validateValues as $validateValue) {
                    $validatorConfiguration = $validatorResolver->parseValidatorAnnotation($validateValue);

                    foreach ($validatorConfiguration['validators'] ?? [] as $validator) {
                        $validatorObjectName = $validatorResolver->resolveValidatorObjectName($validator['validatorName']);

                        $this->properties[$propertyName]['validators'][] = [
                            'name' => $validator['validatorName'],
                            'options' => $validator['validatorOptions'],
                            'className' => $validatorObjectName,
                        ];
                    }
                }
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Lazy::class) instanceof Lazy) {
                $this->properties[$propertyName]['annotations']['lazy'] = true;
            }

            if ($docCommentParser->isTaggedWith('lazy')) {
                $this->properties[$propertyName]['annotations']['lazy'] = true;
                trigger_error(
                    sprintf(
                        'Property %s::%s is tagged with @lazy which is deprecated and will be removed in TYPO3 v10.0.',
                        $reflectionClass->getName(),
                        $reflectionProperty->getName()
                    ),
                    E_USER_DEPRECATED
                );
            }

            if ($annotationReader->getPropertyAnnotation($reflectionProperty, Transient::class) instanceof Transient) {
                $this->properties[$propertyName]['annotations']['transient'] = true;
            }

            if ($docCommentParser->isTaggedWith('transient')) {
                $this->properties[$propertyName]['annotations']['transient'] = true;
                trigger_error(
                    sprintf(
                        'Property %s::%s is tagged with @transient which is deprecated and will be removed in TYPO3 v10.0.',
                        $reflectionClass->getName(),
                        $reflectionProperty->getName()
                    ),
                    E_USER_DEPRECATED
                );
            }

            if ($propertyName !== 'settings'
                && ($annotationReader->getPropertyAnnotation($reflectionProperty, Inject::class) instanceof Inject)
            ) {
                try {
                    $varValue = ltrim($docCommentParser->getTagValues('var')[0], '\\');
                    $this->properties[$propertyName]['annotations']['inject'] = true;
                    $this->properties[$propertyName]['annotations']['type'] = $varValue;
                    $this->properties[$propertyName]['annotations']['dependency'] = $varValue;

                    $this->injectProperties[] = $propertyName;
                } catch (\Exception $e) {
                }
            }

            if ($propertyName !== 'settings' && $docCommentParser->isTaggedWith('inject')) {
                trigger_error(
                    sprintf(
                        'Property %s::%s is tagged with @inject which is deprecated and will be removed in TYPO3 v10.0.',
                        $reflectionClass->getName(),
                        $reflectionProperty->getName()
                    ),
                    E_USER_DEPRECATED
                );
                try {
                    $varValues = $docCommentParser->getTagValues('var');
                    $this->properties[$propertyName]['annotations']['inject'] = true;
                    $this->properties[$propertyName]['annotations']['type'] = ltrim($varValues[0], '\\');
                    $this->properties[$propertyName]['annotations']['dependency'] = ltrim($varValues[0], '\\');

                    if (!$reflectionProperty->isPublic()) {
                        trigger_error(
                            sprintf(
                                'Property %s::%s is not public and tagged with @inject which is deprecated and will stop working in TYPO3 v10.0.',
                                $reflectionClass->getName(),
                                $reflectionProperty->getName()
                            ),
                            E_USER_DEPRECATED
                        );
                    }

                    $this->injectProperties[] = $propertyName;
                } catch (\Exception $e) {
                }
            }

            if ($docCommentParser->isTaggedWith('var') && !$docCommentParser->isTaggedWith('transient')) {
                try {
                    $cascadeAnnotationValues = $docCommentParser->getTagValues('cascade');
                    $this->properties[$propertyName]['annotations']['cascade'] = $cascadeAnnotationValues[0];
                } catch (\Exception $e) {
                }

                if ($this->properties[$propertyName]['annotations']['cascade'] !== null) {
                    trigger_error(
                        sprintf(
                            'Property %s::%s is tagged with @cascade which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionProperty->getName()
                        ),
                        E_USER_DEPRECATED
                    );
                }

                if (($annotation = $annotationReader->getPropertyAnnotation($reflectionProperty, Cascade::class)) instanceof Cascade) {
                    /** @var Cascade $annotation */
                    $this->properties[$propertyName]['annotations']['cascade'] = $annotation->value;
                }

                try {
                    $type = TypeHandlingUtility::parseType(implode(' ', $docCommentParser->getTagValues('var')));
                } catch (\Exception $e) {
                    $type = [
                        'type' => null,
                        'elementType' => null
                    ];
                }

                $this->properties[$propertyName]['type'] = $type['type'] ? ltrim($type['type'], '\\') : null;
                $this->properties[$propertyName]['elementType'] = $type['elementType'] ? ltrim($type['elementType'], '\\') : null;
            }

            if ($docCommentParser->isTaggedWith('uuid')) {
                $this->setUuidPropertyName($propertyName);
            }

            if ($docCommentParser->isTaggedWith('identity')) {
                $this->markAsIdentityProperty($propertyName);
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

            $docCommentParser = new DocCommentParser(true);
            $docCommentParser->parseDocComment($reflectionMethod->getDocComment());

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

            foreach ($docCommentParser->getTagsValues() as $tag => $values) {
                if ($tag === 'cli') {
                    trigger_error(
                        sprintf(
                            'Method %s::%s is tagged with @cli which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionMethod->getName()
                        ),
                        E_USER_DEPRECATED
                    );
                }
                if ($tag === 'internal' && $reflectionClass->isSubclassOf(\TYPO3\CMS\Extbase\Mvc\Controller\CommandController::class)) {
                    trigger_error(
                        sprintf(
                            'Command method %s::%s is tagged with @internal which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionMethod->getName()
                        ),
                        E_USER_DEPRECATED
                    );
                }
                if ($tag === 'ignorevalidation') {
                    trigger_error(
                        sprintf(
                            'Method %s::%s is tagged with @ignorevalidation which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionMethod->getName()
                        ),
                        E_USER_DEPRECATED
                    );
                }
                if ($tag === 'flushesCaches') {
                    trigger_error(
                        sprintf(
                            'Method %s::%s is tagged with @flushesCaches which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionMethod->getName()
                        ),
                        E_USER_DEPRECATED
                    );
                }
                if ($tag === 'validate' && $this->isController && $this->methods[$methodName]['isAction']) {
                    trigger_error(
                        sprintf(
                            'Method %s::%s is tagged with @validate which is deprecated and will be removed in TYPO3 v10.0.',
                            $reflectionClass->getName(),
                            $reflectionMethod->getName()
                        ),
                        E_USER_DEPRECATED
                    );

                    $validatorResolver = GeneralUtility::makeInstance(ValidatorResolver::class);

                    foreach ($values as $validate) {
                        $methodValidatorDefinition = $validatorResolver->parseValidatorAnnotation($validate);

                        foreach ($methodValidatorDefinition['validators'] as $validator) {
                            $validatorObjectName = $validatorResolver->resolveValidatorObjectName($validator['validatorName']);

                            $argumentValidators[$methodValidatorDefinition['argumentName']][] = [
                                'name' => $validator['validatorName'],
                                'options' => $validator['validatorOptions'],
                                'className' => $validatorObjectName,
                            ];
                        }
                    }
                }
                $this->methods[$methodName]['tags'][$tag] = array_map(function ($value) use ($tag) {
                    // not stripping the dollar sign for @validate annotations is just
                    // a quick fix for a regression introduced in 9.0.0.
                    // This exception to the rules will vanish once the resolving of
                    // validators will take place inside this class and not in the
                    // controller during runtime.
                    return $tag === 'validate' ? $value : ltrim($value, '$');
                }, $values);
            }
            unset($methodValidatorDefinition);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof IgnoreValidation) {
                    $this->methods[$methodName]['tags']['ignorevalidation'][] = $annotation->argumentName;
                }
            }

            $this->methods[$methodName]['description'] = $docCommentParser->getDescription();

            foreach ($reflectionMethod->getParameters() as $parameterPosition => $reflectionParameter) {
                /* @var \ReflectionParameter $reflectionParameter */

                $parameterName = $reflectionParameter->getName();

                $this->methods[$methodName]['params'][$parameterName] = [];
                $this->methods[$methodName]['params'][$parameterName]['position'] = $parameterPosition; // compat
                $this->methods[$methodName]['params'][$parameterName]['byReference'] = $reflectionParameter->isPassedByReference(); // compat
                $this->methods[$methodName]['params'][$parameterName]['array'] = $reflectionParameter->isArray(); // compat
                $this->methods[$methodName]['params'][$parameterName]['optional'] = $reflectionParameter->isOptional();
                $this->methods[$methodName]['params'][$parameterName]['allowsNull'] = $reflectionParameter->allowsNull(); // compat
                $this->methods[$methodName]['params'][$parameterName]['class'] = null; // compat
                $this->methods[$methodName]['params'][$parameterName]['type'] = null;
                $this->methods[$methodName]['params'][$parameterName]['nullable'] = $reflectionParameter->allowsNull();
                $this->methods[$methodName]['params'][$parameterName]['default'] = null;
                $this->methods[$methodName]['params'][$parameterName]['hasDefaultValue'] = $reflectionParameter->isDefaultValueAvailable();
                $this->methods[$methodName]['params'][$parameterName]['defaultValue'] = null; // compat
                $this->methods[$methodName]['params'][$parameterName]['dependency'] = null; // Extbase DI
                $this->methods[$methodName]['params'][$parameterName]['validators'] = [];

                if ($reflectionParameter->isDefaultValueAvailable()) {
                    $this->methods[$methodName]['params'][$parameterName]['default'] = $reflectionParameter->getDefaultValue();
                    $this->methods[$methodName]['params'][$parameterName]['defaultValue'] = $reflectionParameter->getDefaultValue(); // compat
                }

                if (($reflectionType = $reflectionParameter->getType()) instanceof \ReflectionNamedType) {
                    $this->methods[$methodName]['params'][$parameterName]['type'] = $reflectionType->getName();
                    $this->methods[$methodName]['params'][$parameterName]['nullable'] = $reflectionType->allowsNull();
                }

                if (($parameterClass = $reflectionParameter->getClass()) instanceof \ReflectionClass) {
                    $this->methods[$methodName]['params'][$parameterName]['class'] = $parameterClass->getName();
                    $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($parameterClass->getName(), '\\');
                } else {
                    $methodTagsAndValues = $this->methods[$methodName]['tags'];
                    if (isset($methodTagsAndValues['param'][$parameterPosition])) {
                        $explodedParameters = explode(' ', $methodTagsAndValues['param'][$parameterPosition]);
                        if (count($explodedParameters) >= 2) {
                            if (TypeHandlingUtility::isSimpleType($explodedParameters[0])) {
                                // ensure that short names of simple types are resolved correctly to the long form
                                // this is important for all kinds of type checks later on
                                $typeInfo = TypeHandlingUtility::parseType($explodedParameters[0]);

                                $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($typeInfo['type'], '\\');
                            } else {
                                $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($explodedParameters[0], '\\');
                            }
                        }
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
     * Adds (defines) a specific property and its type.
     *
     * @param string $name Name of the property
     * @param string $type Type of the property
     * @param bool $lazy Whether the property should be lazy-loaded when reconstituting
     * @param string $cascade Strategy to cascade the object graph.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function addProperty($name, $type, $lazy = false, $cascade = '')
    {
        trigger_error(
            'This method will be removed in TYPO3 v10.0, properties will be automatically added on ClassSchema construction.',
            E_USER_DEPRECATED
        );
        $type = TypeHandlingUtility::parseType($type);
        $this->properties[$name] = [
            'type' => $type['type'],
            'elementType' => $type['elementType'],
            'lazy' => $lazy,
            'cascade' => $cascade
        ];
    }

    /**
     * Returns the given property defined in this schema. Check with
     * hasProperty($propertyName) before!
     *
     * @param string $propertyName
     * @return array
     */
    public function getProperty($propertyName)
    {
        return isset($this->properties[$propertyName]) && is_array($this->properties[$propertyName])
            ? $this->properties[$propertyName]
            : [];
    }

    /**
     * Returns all properties defined in this schema
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the model type of the class this schema is referring to.
     *
     * @param int $modelType The model type, one of the MODELTYPE_* constants.
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function setModelType($modelType)
    {
        trigger_error(
            'This method will be removed in TYPO3 v10.0, modelType will be automatically set on ClassSchema construction.',
            E_USER_DEPRECATED
        );
        if ($modelType < self::MODELTYPE_ENTITY || $modelType > self::MODELTYPE_VALUEOBJECT) {
            throw new \InvalidArgumentException('"' . $modelType . '" is an invalid model type.', 1212519195);
        }
        $this->modelType = $modelType;
    }

    /**
     * Returns the model type of the class this schema is referring to.
     *
     * @return int The model type, one of the MODELTYPE_* constants.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getModelType()
    {
        trigger_error(
            'This method will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        return $this->modelType;
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
     * Sets the property marked as uuid of an object with @uuid
     *
     * @param string $propertyName
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function setUuidPropertyName($propertyName)
    {
        trigger_error(
            'Tagging properties with @uuid is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        if (!array_key_exists($propertyName, $this->properties)) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must be added to the class schema before it can be marked as UUID property.', 1233863842);
        }
        $this->uuidPropertyName = $propertyName;
    }

    /**
     * Gets the name of the property marked as uuid of an object
     *
     * @return string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getUuidPropertyName()
    {
        trigger_error(
            'Tagging properties with @uuid is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        return $this->uuidPropertyName;
    }

    /**
     * Marks the given property as one of properties forming the identity
     * of an object. The property must already be registered in the class
     * schema.
     *
     * @param string $propertyName
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function markAsIdentityProperty($propertyName)
    {
        trigger_error(
            'Tagging properties with @identity is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        if (!array_key_exists($propertyName, $this->properties)) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must be added to the class schema before it can be marked as identity property.', 1233775407);
        }
        if ($this->properties[$propertyName]['annotations']['lazy'] === true) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must not be makred for lazy loading to be marked as identity property.', 1239896904);
        }
        $this->identityProperties[$propertyName] = $this->properties[$propertyName]['type'];
    }

    /**
     * Gets the properties (names and types) forming the identity of an object.
     *
     * @return array
     * @see markAsIdentityProperty()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getIdentityProperties()
    {
        trigger_error(
            'Tagging properties with @identity is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );
        return $this->identityProperties;
    }

    /**
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return isset($this->methods['__construct']);
    }

    /**
     * @param string $name
     * @return array
     */
    public function getMethod(string $name): array
    {
        return $this->methods[$name] ?? [];
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
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
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
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
     * @return array
     */
    public function getInjectMethods(): array
    {
        $injectMethods = [];
        foreach ($this->injectMethods as $injectMethodName) {
            $injectMethods[$injectMethodName] = reset($this->methods[$injectMethodName]['params'])['dependency'];
        }

        return $injectMethods;
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
    public function getConstructorArguments(): array
    {
        if (!$this->hasConstructor()) {
            return [];
        }

        return $this->methods['__construct']['params'];
    }
}
