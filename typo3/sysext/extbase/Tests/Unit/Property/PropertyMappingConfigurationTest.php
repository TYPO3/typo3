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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PropertyMappingConfigurationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration(): void
    {
        $subject = new PropertyMappingConfiguration();
        self::assertEquals('someSourceProperty', $subject->getTargetPropertyName('someSourceProperty'));
        self::assertEquals('someOtherSourceProperty', $subject->getTargetPropertyName('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function shouldMapReturnsFalseByDefault(): void
    {
        $subject = new PropertyMappingConfiguration();
        self::assertFalse($subject->shouldMap('someSourceProperty'));
        self::assertFalse($subject->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function shouldMapReturnsTrueIfConfigured(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->allowAllProperties();
        self::assertTrue($subject->shouldMap('someSourceProperty'));
        self::assertTrue($subject->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function shouldMapReturnsTrueForAllowedProperties(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->allowProperties('someSourceProperty', 'someOtherProperty');
        self::assertTrue($subject->shouldMap('someSourceProperty'));
        self::assertTrue($subject->shouldMap('someOtherProperty'));
    }

    /**
     * @test
     */
    public function shouldMapReturnsFalseForBlacklistedProperties(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->allowAllPropertiesExcept('someSourceProperty', 'someOtherProperty');
        self::assertFalse($subject->shouldMap('someSourceProperty'));
        self::assertFalse($subject->shouldMap('someOtherProperty'));
        self::assertTrue($subject->shouldMap('someOtherPropertyWhichHasNotBeenConfigured'));
    }

    /**
     * @test
     */
    public function shouldSkipReturnsFalseByDefault(): void
    {
        $subject = new PropertyMappingConfiguration();
        self::assertFalse($subject->shouldSkip('someSourceProperty'));
        self::assertFalse($subject->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function shouldSkipReturnsTrueIfConfigured(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->skipProperties('someSourceProperty', 'someOtherSourceProperty');
        self::assertTrue($subject->shouldSkip('someSourceProperty'));
        self::assertTrue($subject->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsCanBeRetrievedAgain(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));
        $subject = new PropertyMappingConfiguration();
        $subject->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        self::assertEquals('v1', $subject->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $subject->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function nonexistentTypeConverterOptionsReturnNull(): void
    {
        self::assertNull((new PropertyMappingConfiguration())->getConfigurationValue('foo', 'bar'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsShouldOverrideAlreadySetOptions(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));
        $subject = new PropertyMappingConfiguration();
        $subject->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $subject->setTypeConverterOptions($mockTypeConverterClass, ['k3' => 'v3']);
        self::assertEquals('v3', $subject->getConfigurationValue($mockTypeConverterClass, 'k3'));
        self::assertNull($subject->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionShouldOverrideAlreadySetOptions(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));
        $subject = new PropertyMappingConfiguration();
        $subject->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $subject->setTypeConverterOption($mockTypeConverterClass, 'k1', 'v3');
        self::assertEquals('v3', $subject->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $subject->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsNullIfNoTypeConverterSet(): void
    {
        self::assertNull((new PropertyMappingConfiguration())->getTypeConverter());
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsTypeConverterIfItHasBeenSet(): void
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $subject = new PropertyMappingConfiguration();
        $subject->setTypeConverter($mockTypeConverter);
        self::assertSame($mockTypeConverter, $subject->getTypeConverter());
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldRespectMapping(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->setMapping('k1', 'k1a');
        self::assertEquals('k1a', $subject->getTargetPropertyName('k1'));
        self::assertEquals('k2', $subject->getTargetPropertyName('k2'));
    }

    public static function fluentInterfaceMethodsDataProvider(): array
    {
        return [
            ['allowAllProperties'],
            ['allowProperties'],
            ['allowAllPropertiesExcept'],
            ['setMapping', ['k1', 'k1a']],
        ];
    }

    /**
     * @test
     * @dataProvider fluentInterfaceMethodsDataProvider
     */
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = []): void
    {
        $subject = new PropertyMappingConfiguration();
        self::assertSame($subject, $subject->$methodToTestForFluentInterface(...$argumentsForMethod));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionReturnsThis(): void
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $mockTypeConverterClass = get_class($mockTypeConverter);
        $subject = new PropertyMappingConfiguration();
        self::assertSame($subject, $subject->setTypeConverterOption($mockTypeConverterClass, 'key', 'value'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsReturnsThis(): void
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $mockTypeConverterClass = get_class($mockTypeConverter);
        $subject = new PropertyMappingConfiguration();
        self::assertSame($subject, $subject->setTypeConverterOptions($mockTypeConverterClass, []));
    }

    /**
     * @test
     */
    public function setTypeConverterReturnsThis(): void
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $subject = new PropertyMappingConfiguration();
        self::assertSame($subject, $subject->setTypeConverter($mockTypeConverter));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithGetConfigurationFor(): void
    {
        $subject = new PropertyMappingConfiguration();
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $subject->forProperty('items.*')->setTypeConverterOptions(\stdClass::class, ['k1' => 'v1']);
        $configuration = $subject->getConfigurationFor('items')->getConfigurationFor('6');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithForProperty(): void
    {
        $subject = new PropertyMappingConfiguration();
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $subject->forProperty('items.*.foo')->setTypeConverterOptions(\stdClass::class, ['k1' => 'v1']);
        $configuration = $subject->forProperty('items.6.foo');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithShouldMap(): void
    {
        $subject = new PropertyMappingConfiguration();
        $subject->forProperty('items.*')->setTypeConverterOptions(\stdClass::class, ['k1' => 'v1']);
        $configuration = $subject->forProperty('items');
        self::assertTrue($configuration->shouldMap('6'));
    }
}
