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

use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Validator\Constraint;
use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\CMS\Extbase\Attribute;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchPropertyException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Method;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Property;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\PropertyCharacteristics;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;
use TYPO3\CMS\Extbase\Validation\ValidatorClassNameResolver;

/**
 * A class schema
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ClassSchema
{
    /**
     * Change this whenever the result of __serialize() changes, so that cache
     * entries written in a previous format are not read anymore.
     */
    public const string SERIALIZATION_FORMAT = '1';

    private const string SERIALIZATION_KEY_NAME = 'n';
    private const string SERIALIZATION_KEY_BITSET = 'b';
    private const string SERIALIZATION_KEY_PROPERTIES = 'p';
    private const string SERIALIZATION_KEY_METHODS = 'm';
    private const string SERIALIZATION_KEY_CASCADE = 'c';
    private const string SERIALIZATION_KEY_FILE_UPLOAD = 'f';
    private const string SERIALIZATION_KEY_TYPE = 't';
    private const string SERIALIZATION_KEY_VALIDATORS = 'v';

    private const int BIT_CLASS_IS_CONTROLLER = 1 << 3;
    private BitSet $bitSet;
    private static array $methodObjects = [];
    /**
     * @var array<string, Property>
     */
    private array $properties = [];
    private array $methods = [];
    private static ?PropertyInfoExtractor $propertyInfoExtractor = null;

    /**
     * Constructs this class schema
     *
     * @param class-string $className Name of the class this schema is referring to
     * @throws InvalidTypeHintException
     * @throws InvalidValidationConfigurationException
     * @throws \ReflectionException
     */
    public function __construct(private readonly string $className)
    {
        $this->bitSet = new BitSet();

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->implementsInterface(ControllerInterface::class)) {
            $this->bitSet->set(self::BIT_CLASS_IS_CONTROLLER);
        }

        if (self::$propertyInfoExtractor === null) {
            self::$propertyInfoExtractor = new PropertyInfoExtractor(
                [],
                [new PhpStanExtractor(), new ReflectionExtractor()]
            );
        }

        $this->reflectProperties($reflectionClass);
        $this->reflectMethods($reflectionClass);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    protected function reflectProperties(\ReflectionClass $reflectionClass): void
    {
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->isStatic()) {
                continue;
            }

            $propertyName = $reflectionProperty->getName();

            $propertyCharacteristicsBit = 0;
            $propertyCharacteristicsBit += $reflectionProperty->isPrivate() ? PropertyCharacteristics::VISIBILITY_PRIVATE : 0;
            $propertyCharacteristicsBit += $reflectionProperty->isProtected() ? PropertyCharacteristics::VISIBILITY_PROTECTED : 0;
            $propertyCharacteristicsBit += $reflectionProperty->isPublic() ? PropertyCharacteristics::VISIBILITY_PUBLIC : 0;

            $propertyCascadeValue = null;
            $propertyFileUpload = null;
            $propertyValidators = [];

            $validateAttributes = [];
            $fileUploadAttributes = [];
            foreach ($reflectionProperty->getAttributes() as $attribute) {
                match ($attribute->getName()) {
                    Attribute\Validate::class => $validateAttributes[] = $attribute,
                    Attribute\FileUpload::class => $fileUploadAttributes[] = $attribute,
                    Attribute\ORM\Lazy::class => $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_LAZY,
                    Attribute\ORM\Transient::class => $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_TRANSIENT,
                    Attribute\IgnoreValidation::class => $propertyCharacteristicsBit += PropertyCharacteristics::ANNOTATED_IGNORE_VALIDATION,
                    Attribute\ORM\Cascade::class => $propertyCascadeValue = $attribute->newInstance()->value,
                    default => '' // non-extbase attributes
                };

                if (is_a($attribute->getName(), Constraint::class, true)) {
                    $validateAttributes[] = $attribute;
                }
            }
            foreach ($validateAttributes as $attribute) {
                $validator = $attribute->newInstance();

                if ($validator instanceof Constraint) {
                    $property = [
                        'constraint' => $validator,
                        'className' => $validator::class,
                    ];
                } else {
                    $property = [
                        'name' => $validator->validator,
                        'options' => $validator->options,
                        'className' => ValidatorClassNameResolver::resolve($validator->validator),
                    ];
                }

                $propertyValidators[] = $property;
            }

            foreach ($fileUploadAttributes as $attribute) {
                $fileUpload = $attribute->newInstance();

                $propertyFileUpload = [
                    'validation' => $fileUpload->validation,
                    'uploadFolder' => $fileUpload->uploadFolder,
                    'addRandomSuffix' => $fileUpload->addRandomSuffix,
                    'duplicationBehavior' => $fileUpload->duplicationBehavior,
                    'createUploadFolderIfNotExist' => $fileUpload->createUploadFolderIfNotExist,
                ];
            }

            $this->properties[$propertyName] = new Property(
                $propertyName,
                $propertyCascadeValue,
                $propertyFileUpload,
                new PropertyCharacteristics($propertyCharacteristicsBit),
                self::$propertyInfoExtractor->getType($this->className, $propertyName, ['reflectionProperty' => $reflectionProperty]),
                $propertyValidators,
            );
        }
    }

    /**
     * @throws InvalidTypeHintException
     * @throws InvalidValidationConfigurationException
     * @throws \ReflectionException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    protected function reflectMethods(\ReflectionClass $reflectionClass): void
    {
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isStatic()) {
                continue;
            }

            $methodName = $reflectionMethod->getName();

            $this->methods[$methodName] = [];
            $this->methods[$methodName]['private'] = $reflectionMethod->isPrivate();
            $this->methods[$methodName]['protected'] = $reflectionMethod->isProtected();
            $this->methods[$methodName]['public'] = $reflectionMethod->isPublic();
            $this->methods[$methodName]['params'] = [];
            $isAction = $this->bitSet->get(self::BIT_CLASS_IS_CONTROLLER) && str_ends_with($methodName, 'Action');

            /** @var array<string, list<Attribute\Validate>> $validateAttributes */
            $validateAttributes = [];
            /** @var array<string, list<Attribute\IgnoreValidation>> $validateAttributes */
            $ignoreValidationAttributes = [];

            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                $parameterName = $reflectionParameter->getName();
                $parameterAttributes = $reflectionParameter->getAttributes();

                $validateAttributes[$parameterName] ??= [];
                $ignoreValidationAttributes[$parameterName] ??= [];

                if ($isAction) {
                    foreach ($parameterAttributes as $parameterAttribute) {
                        match ($parameterAttribute->getName()) {
                            Attribute\Validate::class => $validateAttributes[$parameterName][] = $parameterAttribute->newInstance(),
                            Attribute\IgnoreValidation::class => $ignoreValidationAttributes[$parameterName][] = $parameterAttribute->newInstance(),
                            default => '' // non-extbase attributes
                        };
                    }
                }

                $reflectionType = $reflectionParameter->getType();

                $this->methods[$methodName]['params'][$parameterName] = [];
                $this->methods[$methodName]['params'][$parameterName]['array'] = false; // compat
                $this->methods[$methodName]['params'][$parameterName]['optional'] = $reflectionParameter->isOptional();
                $this->methods[$methodName]['params'][$parameterName]['allowsNull'] = $reflectionParameter->allowsNull();
                $this->methods[$methodName]['params'][$parameterName]['type'] = null;
                $this->methods[$methodName]['params'][$parameterName]['hasDefaultValue'] = $reflectionParameter->isDefaultValueAvailable();
                $this->methods[$methodName]['params'][$parameterName]['defaultValue'] = null;
                $this->methods[$methodName]['params'][$parameterName]['ignoreValidation'] = $ignoreValidationAttributes[$parameterName] !== [];
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
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($reflectionType->getName(), '\\');
                    } elseif ($reflectionType->getName() === 'self') {
                        // In addition, self cannot be resolved by "new \ReflectionClass('self')",
                        // so treat this as a reference to the current class
                        $this->methods[$methodName]['params'][$parameterName]['type'] = ltrim($reflectionClass->getName(), '\\');
                    } else {
                        // This is mainly to confirm that the class exists. If it doesn't, a ReflectionException
                        // will be thrown. It's not the ideal way of doing so, but it maintains the existing API
                        // so that the exception can get caught and recast to a TYPO3-specific exception.
                        /** @var class-string<mixed> $classname */
                        $classname = $reflectionType->getName();

                        // Test if the class can be reflected
                        /** @noinspection PhpUnusedLocalVariableInspection */
                        $reflection = new \ReflectionClass($classname);
                        // There's a single type declaration that is a class.
                        $this->methods[$methodName]['params'][$parameterName]['type'] = $reflectionType->getName();
                    }
                }

                // Extbase Validation
                if ($validateAttributes[$parameterName] !== []) {
                    if ($this->methods[$methodName]['params'][$parameterName]['type'] === null) {
                        throw new InvalidTypeHintException(
                            'Missing type information for parameter "$' . $parameterName . '" in ' . $this->className . '->' . $methodName . '(): Use a type hint.',
                            1515075192
                        );
                    }

                    $this->methods[$methodName]['params'][$parameterName]['validators'] = array_map(
                        static fn(Attribute\Validate $validator) => [
                            'name' => $validator->validator,
                            'options' => $validator->options,
                            'className' => ValidatorClassNameResolver::resolve($validator->validator),
                        ],
                        $validateAttributes[$parameterName],
                    );
                    unset($validateAttributes[$parameterName]);
                }
            }

            // Extbase Validation
            foreach ($validateAttributes as $parameterName => $validators) {
                if ($validators !== []) {
                    $validatorNames = array_map(
                        static fn(Attribute\Validate $validate) => $validate->validator,
                        $validators,
                    );

                    throw new InvalidValidationConfigurationException(
                        'Invalid #[Validate] attribute in ' . $this->className . '->' . $methodName . '(): The following validators have been defined for missing param "$' . $parameterName . '": ' . implode(', ', $validatorNames),
                        1515073585
                    );
                }
            }
        }
    }

    /**
     * @throws NoSuchPropertyException
     */
    public function getProperty(string $propertyName): Property
    {
        if (!isset($this->properties[$propertyName])) {
            throw NoSuchPropertyException::create($this->className, $propertyName);
        }

        return $this->properties[$propertyName];
    }

    /**
     * @return array<string, Property>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Returns all properties that do not start with an underscore like $_localizedUid
     *
     * @return array<string, Property>
     * @internal
     */
    public function getDomainObjectProperties(): array
    {
        return array_filter(
            $this->getProperties(),
            static fn(Property $property): bool => !str_starts_with($property->name, '_')
        );
    }

    /**
     * If the class schema has a certain property.
     *
     * @param string $propertyName Name of the property
     */
    public function hasProperty(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * @throws NoSuchMethodException
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

    public function hasMethod(string $methodName): bool
    {
        return isset($this->methods[$methodName]);
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

    public function __serialize(): array
    {
        $properties = [];
        foreach ($this->properties as $name => $property) {
            $data = [self::SERIALIZATION_KEY_BITSET => $property->characteristics->__toInt()];
            if ($property->cascade !== null) {
                $data[self::SERIALIZATION_KEY_CASCADE] = $property->cascade;
            }
            if ($property->fileUpload !== null) {
                $data[self::SERIALIZATION_KEY_FILE_UPLOAD] = $property->fileUpload;
            }
            if ($property->type !== null) {
                $data[self::SERIALIZATION_KEY_TYPE] = $property->type;
            }
            if ($property->validators !== []) {
                $data[self::SERIALIZATION_KEY_VALIDATORS] = $property->validators;
            }
            $properties[$name] = $data;
        }

        return [
            self::SERIALIZATION_KEY_NAME => $this->className,
            self::SERIALIZATION_KEY_BITSET => $this->bitSet->__toInt(),
            self::SERIALIZATION_KEY_PROPERTIES => $properties,
            self::SERIALIZATION_KEY_METHODS => $this->methods,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->className = $data[self::SERIALIZATION_KEY_NAME];
        $this->bitSet = new BitSet($data[self::SERIALIZATION_KEY_BITSET]);
        $this->methods = $data[self::SERIALIZATION_KEY_METHODS];

        foreach ($data[self::SERIALIZATION_KEY_PROPERTIES] as $name => $propertyData) {
            $this->properties[$name] = new Property(
                (string)$name,
                $propertyData[self::SERIALIZATION_KEY_CASCADE] ?? null,
                $propertyData[self::SERIALIZATION_KEY_FILE_UPLOAD] ?? null,
                new PropertyCharacteristics($propertyData[self::SERIALIZATION_KEY_BITSET]),
                $propertyData[self::SERIALIZATION_KEY_TYPE] ?? null,
                $propertyData[self::SERIALIZATION_KEY_VALIDATORS] ?? [],
            );
        }
    }
}
