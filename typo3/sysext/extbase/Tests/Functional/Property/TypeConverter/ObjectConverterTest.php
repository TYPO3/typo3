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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property\TypeConverter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Property\Exception;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TypeConverterTest\Domain\Model\Animal;
use TYPO3Tests\TypeConverterTest\Domain\Model\Animals;
use TYPO3Tests\TypeConverterTest\Domain\Model\Cat;

final class ObjectConverterTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function convertToObject(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $model = new class () extends AbstractEntity {
            /**
             * @var string
             */
            protected $name;

            public function setName(string $name): void
            {
                $this->name = $name;
            }
        };

        /** @var DomainObjectInterface $object */
        $object = $propertyMapper->convert(['name' => 'John Doe'], get_class($model));

        self::assertInstanceOf(get_class($model), $object);
        self::assertSame('John Doe', $object->_getProperty('name'));
    }

    #[Test]
    public function convertToObjectViaTypeInArray(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->setTypeConverterOption(
            ObjectConverter::class,
            ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED,
            true
        );

        $object = $propertyMapper->convert(
            ['name' => 'John Doe', '__type' => Cat::class],
            Animal::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(Cat::class, $object);
        self::assertSame('John Doe', $object->getName());
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsTypeDefinedByPropertyMappingConfiguration(): void
    {
        $class = new class () {
            public $name;
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration
            ->forProperty('name')
            ->setTypeConverterOption(
                ObjectConverter::class,
                ObjectConverter::CONFIGURATION_TARGET_TYPE,
                'string'
            )
        ;

        $result = $propertyMapper->convert(
            ['name' => 'foo'],
            get_class($class),
            $propertyMapperConfiguration
        );

        self::assertSame('foo', $result->name);
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsTypeDefinedByConstructorArgument(): void
    {
        $class = new class ('') {
            private $name;
            public function __construct(string $name)
            {
                $this->name = $name;
            }
            public function getName(): string
            {
                return $this->name;
            }
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $result = $propertyMapper->convert(
            ['name' => 'foo'],
            get_class($class),
            $propertyMapperConfiguration
        );

        self::assertSame('foo', $result->getName());
    }

    #[Test]
    public function collectionTypesAreConsideredInMapping(): void
    {
        $class = new class () {
            /**
             * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal>
             */
            protected \TYPO3\CMS\Extbase\Persistence\ObjectStorage $collection;

            /**
             * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal>
             */
            public function getCollection(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
            {
                return $this->collection;
            }

            /**
             * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3Tests\TypeConverterTest\Domain\Model\Animal> $collection
             */
            public function setCollection(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $collection): void
            {
                $this->collection = $collection;
            }
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->forProperty('collection.*')->allowAllProperties();

        $result = $propertyMapper->convert(
            ['collection' => [['name' => 'Zebra'], ['name' => 'Lion']]],
            get_class($class),
            $propertyMapperConfiguration
        );

        self::assertSame(2, $result->getCollection()->count());
        self::assertSame('Zebra', $result->getCollection()->current()->getName());
        $result->getCollection()->next();
        self::assertSame('Lion', $result->getCollection()->current()->getName());
    }

    #[Test]
    public function collectionTypesAreConsideredInMappingWithShortObjectStorageNamespaceAndNonAnonymousClass(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->forProperty('collection.*')->allowAllProperties();
        $result = $propertyMapper->convert(
            ['collection' => [['name' => 'Zebra'], ['name' => 'Lion']]],
            Animals::class,
            $propertyMapperConfiguration
        );
        self::assertSame(2, $result->getCollection()->count());
        self::assertSame('Zebra', $result->getCollection()->current()->getName());
        $result->getCollection()->next();
        self::assertSame('Lion', $result->getCollection()->current()->getName());
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsTypeDefinedBySetter(): void
    {
        $class = new class () {
            private $name;
            public function setName(string $name): void
            {
                $this->name = $name;
            }
            public function getName(): string
            {
                return $this->name;
            }
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $result = $propertyMapper->convert(
            ['name' => 'foo'],
            get_class($class),
            $propertyMapperConfiguration
        );

        self::assertSame('foo', $result->getName());
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertyIsNotAccessible(): void
    {
        $class = new class () {};

        $className = get_class($class);
        $propertyName = 'name';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Type of child property "' . $propertyName . '" of class "' . $className . '" could not be derived from constructor arguments as said class does not have a constructor defined.');

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $propertyMapper->convert(
            [$propertyName => 'foo'],
            $className,
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertyTypeCannotBeDerivedFromNonExistingConstructorArgument(): void
    {
        $class = new class () {
            public function __construct() {}
        };

        $className = get_class($class);
        $propertyName = 'name';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Type of child property "' . $propertyName . '" of class "' . $className . '" could not be derived from constructor arguments as the constructor of said class does not have a parameter with property name "' . $propertyName . '".');

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $propertyMapper->convert(
            [$propertyName => 'foo'],
            $className,
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertyTypeCannotBeDerivedFromExistingConstructorArgument(): void
    {
        $class = new class () {
            // @phpstan-ignore-next-line
            public function __construct($name = null) {}
        };

        $className = get_class($class);
        $propertyName = 'name';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Type of child property "' . $propertyName . '" of class "' . $className . '" could not be derived from constructor argument "' . $propertyName . '". This usually happens if the argument misses a type hint.');

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $propertyMapper->convert(
            [$propertyName => 'foo'],
            $className,
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsInvalidTargetExceptionIfPropertySetterDoesNotDefineAType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Setter for property "name" had no type hint or documentation in target object of type "');

        $class = new class () {
            public function setName($name): void {}
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $propertyMapper->convert(
            ['name' => 'foo'],
            get_class($class),
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function convertFromThrowsInvalidTargetExceptionIfPropertiesCannotBeSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Property "name" having a value of type "string" could not be set in target object of type "');

        $class = new class () {
            // @phpstan-ignore-next-line
            private string $name;
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration
            ->forProperty('name')
            ->setTypeConverterOption(
                ObjectConverter::class,
                ObjectConverter::CONFIGURATION_TARGET_TYPE,
                'string'
            )
        ;

        $propertyMapper->convert(
            ['name' => 'foo'],
            get_class($class),
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function buildObjectUsesDefaultValueOfOptionalConstructorArguments(): void
    {
        $class = new class ('', '') {
            public $name;
            public $color;
            public function __construct(string $name, ?string $color = 'red')
            {
                $this->name = $name;
                $this->color = $color;
            }
        };

        $result = $this->get(PropertyMapper::class)->convert(
            ['name' => 'foo'],
            get_class($class)
        );

        self::assertSame('foo', $result->name);
        self::assertSame('red', $result->color);
    }

    #[Test]
    public function buildObjectThrowsInvalidTargetExceptionIfMandatoryConstructorArgumentIsMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Missing constructor argument "color" for object of type "');

        $class = new class ('', '') {
            public $name;
            public $color;
            public function __construct(string $name, string $color)
            {
                $this->name = $name;
                $this->color = $color;
            }
        };

        $this->get(PropertyMapper::class)->convert(
            ['name' => 'foo'],
            get_class($class)
        );
    }

    #[Test]
    public function getTargetTypeForSourceThrowsInvalidPropertyMappingConfigurationExceptionIfTargetTypeOverridingIsNotAllowed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.');

        $class = new class () {};

        $this->get(PropertyMapper::class)->convert(
            ['__type' => Animal::class],
            get_class($class)
        );
    }

    #[Test]
    public function getTargetTypeForSourceThrowsInvalidDataTypeExceptionIfOverriddenTargetTypeIsNotASubtypeOfOriginalTargetType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": The given type "TYPO3Tests\TypeConverterTest\Domain\Model\Animal" is not a subtype of "');

        $class = new class () {};

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();
        $propertyMapperConfiguration->setTypeConverterOption(
            ObjectConverter::class,
            ObjectConverter::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED,
            true
        );

        $this->get(PropertyMapper::class)->convert(
            ['__type' => Animal::class],
            get_class($class),
            $propertyMapperConfiguration
        );
    }

    #[Test]
    public function convertWithRegisteredSubclassReturnsInstanceOfRegisteredSubclass(): void
    {
        // XCLASS the animal class
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Animal::class] = [
            'className' => Cat::class,
        ];

        $propertyMapper = $this->get(PropertyMapper::class);

        $propertyMapperConfiguration = new PropertyMappingConfiguration();
        $propertyMapperConfiguration->allowAllProperties();

        $object = $propertyMapper->convert(
            ['name' => 'John Doe'],
            Animal::class,
            $propertyMapperConfiguration
        );

        self::assertInstanceOf(Cat::class, $object);
    }
}
