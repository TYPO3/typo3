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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\TypeConverterTest\Domain\Model\Animal;
use TYPO3Tests\TypeConverterTest\Domain\Model\Cat;
use TYPO3Tests\TypeConverterTest\Domain\Model\Countable;
use TYPO3Tests\TypeConverterTest\Domain\Model\Dog;
use TYPO3Tests\TypeConverterTest\Domain\Model\ExtendedCountableInterface;

final class PropertyMapperTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['extbase'];
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example/',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/type_converter_test/',
    ];

    #[Test]
    public function convertCreatesAPropertyMappingConfigurationIfNotGiven(): void
    {
        // This test just increases the test coverage
        $this->get(PropertyMapper::class)
            ->convert('string', 'string');
    }

    #[Test]
    public function convertReturnsNullIfDoMappingReturnsAnError(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);

        self::assertNull($propertyMapper->convert('string', 'integer'));
        self::assertNotEmpty($propertyMapper->getMessages());
    }

    #[Test]
    public function convertThrowsATargetNotFoundException(): void
    {
        $this->expectException(TargetNotFoundException::class);
        $this->expectExceptionCode(1297933823);
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $configurationManager = $this->get(ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);
        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert(9999, Blog::class);
    }

    #[Test]
    public function convertThrowsAnExceptionIfNoTypeConverterCanBeFoundForTheConversionOfSimpleTypes(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": No converter found which can be used to convert from "integer" to "boolean"');

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert(9999, 'boolean');
    }

    #[Test]
    public function convertInternallyConvertsANullSourceToAnEmptyString(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);
        self::assertSame('', $propertyMapper->convert(null, 'string'));
    }

    #[Test]
    public function convertThrowsAnExceptionIfTargetTypeIsANonExistingClass(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": Could not find a suitable type converter for "NonExistingClass" because no such class or interface exists.');

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert(1, 'NonExistingClass');
    }

    #[Test]
    public function convertThrowsAnExceptionIfAtLeastTwoConvertersAreRegisteredThatHandleTheConversionToTheSameInterface(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('There exist at least two converters which handle the conversion to an interface with priority "10"');

        $counter = new class () implements ExtendedCountableInterface {
            public function count(): int
            {
                return 1;
            }
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert(1, get_class($counter));
    }

    #[Test]
    public function doMappingReturnsTheSourceIfItIsAlreadyTheDesiredTypeWithoutCallingAConverter(): void
    {
        $objectStorage = new ObjectStorage();

        $result = $this->get(PropertyMapper::class)->convert(
            $objectStorage,
            '\TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>'
        );

        self::assertSame($objectStorage, $result);
    }

    #[Test]
    public function findTypeConverterReturnsTheConverterFromThePropertyMappingConfiguration(): void
    {
        $class = new class () extends IntegerConverter {
            public function convertFrom($source, string $targetType, array $convertedChildProperties = [], ?PropertyMappingConfigurationInterface $configuration = null): int
            {
                return 1575648246;
            }
        };

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($class);

        $result = $this->get(PropertyMapper::class)->convert(
            1,
            'integer',
            $propertyMappingConfiguration
        );

        self::assertSame(1575648246, $result);
    }

    #[Test]
    public function determineSourceTypeThrowsInvalidSourceExceptionForNonSupportedTypes(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('The source is not of type string, array, float, integer or boolean, but of type "object"');

        $generator = static function () {
            return 'string';
        };

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert($generator, 'string');
    }

    #[Test]
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsNullIfNoTypeConvertersExistForTheSourceType(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(1297759968);
        $this->expectExceptionMessage('Exception while property mapping at property path "": No converter found which can be used to convert from "boolean" to "TYPO3Tests\TypeConverterTest\Domain\Model\Cat"');

        $result = $this->get(PropertyMapper::class)->convert(false, Cat::class);
        self::assertNull($result);
    }

    #[Test]
    public function findFirstEligibleTypeConverterInObjectHierarchyFindsConverterFromStringToObject(): void
    {
        $result = $this->get(PropertyMapper::class)->convert('tigger', Cat::class);
        self::assertInstanceOf(Cat::class, $result);
    }

    #[Test]
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsConverterForParentClass(): void
    {
        $result = $this->get(PropertyMapper::class)->convert('fluffy', Dog::class);
        self::assertInstanceOf(Animal::class, $result);
    }

    #[Test]
    public function findFirstEligibleTypeConverterInObjectHierarchyReturnsConverterForInterfaces(): void
    {
        $propertyMapper = $this->get(PropertyMapper::class);
        $result = $propertyMapper->convert(1, Countable::class);

        self::assertSame([], $result);
    }

    #[Test]
    public function defaultPropertyMappingConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();

        $propertyMapper = $this->get(PropertyMapper::class);
        /** @var Cat $result */
        $result = $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );

        self::assertInstanceOf(Cat::class, $result);
        self::assertSame('black', $result->getColor());
    }

    #[Test]
    public function skipPropertiesConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->skipProperties('color');

        $propertyMapper = $this->get(PropertyMapper::class);
        /** @var Cat $result */
        $result = $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );

        self::assertInstanceOf(Cat::class, $result);
        self::assertNull($result->getColor());
    }

    #[Test]
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

        $propertyMapper = $this->get(PropertyMapper::class);
        $propertyMapper->convert(
            $source,
            Cat::class,
            $propertyMappingConfiguration
        );
    }

    #[Test]
    public function allowAllPropertiesExceptWithSkipUnknownPropertiesConfiguration(): void
    {
        $source = [
            'color' => 'black',
        ];

        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllPropertiesExcept('color');
        $propertyMappingConfiguration->skipUnknownProperties();

        $propertyMapper = $this->get(PropertyMapper::class);
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
