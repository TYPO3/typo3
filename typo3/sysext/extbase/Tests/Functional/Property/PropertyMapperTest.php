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

namespace TYPO3\CMS\Extbase\Tests\Functional\Property;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter;
use TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter;
use TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures\Animal;
use TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures\Cat;
use TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures\ExtendedCountableInterface;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PropertyMapperTest extends FunctionalTestCase
{
    // @todo: Switch to a simple test extension that contains a test model, instead.
    protected array $coreExtensionsToLoad = ['beuser'];

    /**
     * @test
     */
    public function initializeObjectThrowsDuplicateTypeConverterException(): void
    {
        $this->expectExceptionCode(1297951378);

        $class = new class() extends ArrayConverter {
        };
        ExtensionUtility::registerTypeConverter(get_class($class));
        $this->getContainer()->set(get_class($class), $class);
        $this->getContainer()->get(PropertyMapper::class);
    }

    /**
     * @test
     */
    public function convertCreatesAPropertyMappingConfigurationIfNotGiven(): void
    {
        // This test just increases the test coverage
        $this->getContainer()->get(PropertyMapper::class)
            ->convert('string', 'string');
    }

    /**
     * @test
     */
    public function convertReturnsNullIfDoMappingReturnsAnError(): void
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);

        self::assertNull($propertyMapper->convert('string', 'integer'));
        self::assertNotEmpty($propertyMapper->getMessages());
    }

    /**
     * @test
     */
    public function convertThrowsATargetNotFoundException(): void
    {
        $this->expectException(TargetNotFoundException::class);
        $this->expectExceptionCode(1297933823);

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(9999, BackendUser::class);
    }

    /**
     * @test
     */
    public function convertThrowsAnExceptionIfNoTypeConverterCanBeFoundForTheConversionOfSimpleTypes(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": No converter found which can be used to convert from "integer" to "boolean"');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(9999, 'boolean');
    }

    /**
     * @test
     */
    public function convertThrowsAnExceptionIfTargetTypeIsNotAString(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": The target type was no string, but of type "NULL"');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(9999, null);
    }

    /**
     * @test
     */
    public function convertInternallyConvertsANullSourceToAnEmptyString(): void
    {
        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        self::assertSame('', $propertyMapper->convert(null, 'string'));
    }

    /**
     * @test
     */
    public function convertThrowsAnExceptionIfTargetTypeIsANonExistingClass(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Could not find a suitable type converter for "NonExistingClass" because no such class or interface exists.');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(1, 'NonExistingClass');
    }

    /**
     * @test
     */
    public function convertThrowsAnExceptionIfAtLeastTwoConvertersAreRegisteredThatHandleTheConversionToTheSameInterface(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('There exist at least two converters which handle the conversion to an interface with priority "10"');

        $converterOne = new class() extends AbstractTypeConverter {
            protected $priority = 10;
            protected $sourceTypes = ['integer'];
            protected $targetType = \Countable::class;

            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): bool
            {
                return true;
            }
        };

        $converterTwo = new class() extends AbstractTypeConverter {
            protected $priority = 10;
            protected $sourceTypes = ['integer'];
            protected $targetType = ExtendedCountableInterface::class;

            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): bool
            {
                return true;
            }
        };

        $counter = new class() implements ExtendedCountableInterface {
            public function count(): int
            {
                return 1;
            }
        };

        ExtensionUtility::registerTypeConverter(get_class($converterOne));
        $this->getContainer()->set(get_class($converterOne), $converterOne);
        ExtensionUtility::registerTypeConverter(get_class($converterTwo));
        $this->getContainer()->set(get_class($converterTwo), $converterTwo);

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(1, get_class($counter));
    }

    /**
     * @test
     */
    public function doMappingReturnsTheSourceIfItIsAlreadyTheDesiredTypeWithoutCallingAConverter(): void
    {
        $objectStorage = new ObjectStorage();

        $result = $this->getContainer()->get(PropertyMapper::class)->convert(
            $objectStorage,
            '\TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>'
        );

        self::assertSame($objectStorage, $result);
    }

    /**
     * @test
     */
    public function findTypeConverterReturnsTheConverterFromThePropertyMappingConfiguration(): void
    {
        $class = new class() extends IntegerConverter {
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): int
            {
                return 1575648246;
            }
        };

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($class);

        $result = $this->getContainer()->get(PropertyMapper::class)->convert(
            1,
            'integer',
            $propertyMappingConfiguration
        );

        self::assertSame(1575648246, $result);
    }

    /**
     * @test
     */
    public function determineSourceTypeThrowsInvalidSourceExceptionForNonSupportedTypes(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('The source is not of type string, array, float, integer or boolean, but of type "object"');

        $generator = static function () {
            return 'string';
        };

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert($generator, 'string');
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsNullIfNoTypeConvertersExistForTheSourceType(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": No converter found which can be used to convert from "integer" to "TYPO3\CMS\Extbase\Tests\Functional\Property\Fixtures\Cat"');

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] = [];

        $result = $this->getContainer()->get(PropertyMapper::class)->convert(1, Cat::class);
        self::assertNull($result);
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyFindsConverterFromStringToObject(): void
    {
        $converter = new class() extends AbstractTypeConverter {
            protected $priority = 10;
            protected $sourceTypes = ['string'];
            protected $targetType = Cat::class;

            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): Cat
            {
                return new Cat();
            }
        };

        ExtensionUtility::registerTypeConverter(get_class($converter));
        $this->getContainer()->set(get_class($converter), $converter);

        $result = $this->getContainer()->get(PropertyMapper::class)->convert('tigger', Cat::class);
        self::assertInstanceOf(Cat::class, $result);
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsConverterForParentClass(): void
    {
        $converter = new class() extends AbstractTypeConverter {
            protected $priority = 10;
            protected $sourceTypes = ['string'];
            protected $targetType = Animal::class;

            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): Animal
            {
                return new Animal();
            }
        };

        ExtensionUtility::registerTypeConverter(get_class($converter));
        $this->getContainer()->set(get_class($converter), $converter);

        $result = $this->getContainer()->get(PropertyMapper::class)->convert('tigger', Cat::class);
        self::assertInstanceOf(Animal::class, $result);
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsConverterForInterfaces(): void
    {
        $converter = new class() extends AbstractTypeConverter {
            protected $priority = 10;
            protected $sourceTypes = ['integer'];
            protected $targetType = \Countable::class;

            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): array
            {
                return [];
            }
        };

        $counter = new class() implements \Countable {
            public function count(): int
            {
                return 1;
            }
        };

        ExtensionUtility::registerTypeConverter(get_class($converter));
        $this->getContainer()->set(get_class($converter), $converter);

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $result = $propertyMapper->convert(1, get_class($counter));

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function defaultPropertyMappingConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        /** @var Cat $result */
        $result = $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );

        self::assertInstanceOf(Cat::class, $result);
        self::assertSame('black', $result->getColor());
    }

    /**
     * @test
     */
    public function skipPropertiesConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->skipProperties('color');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        /** @var Cat $result */
        $result = $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );

        self::assertInstanceOf(Cat::class, $result);
        self::assertNull($result->getColor());
    }

    /**
     * @test
     */
    public function allowAllPropertiesExceptConfiguration(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('It is not allowed to map property "color". You need to use $propertyMappingConfiguration->allowProperties(\'color\') to enable mapping of this property.');

        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllPropertiesExcept('color');

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );
    }

    /**
     * @test
     */
    public function allowAllPropertiesExceptWithSkipUnknownPropertiesConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllPropertiesExcept('color');
        $propertyMappingConfiguration->skipUnknownProperties();

        $propertyMapper = $this->getContainer()->get(PropertyMapper::class);
        /** @var Cat $result */
        $result = $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );

        self::assertInstanceOf(Cat::class, $result);
        self::assertNull($result->getColor());
    }
}
