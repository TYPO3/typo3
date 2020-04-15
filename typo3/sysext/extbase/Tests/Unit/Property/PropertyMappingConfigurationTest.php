<?php

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

/**
 * Test case
 */
class PropertyMappingConfigurationTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * Initialization
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyMappingConfiguration = new PropertyMappingConfiguration();
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::getTargetPropertyName
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        self::assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
        self::assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->allowAllProperties();
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsTrueForAllowedProperties()
    {
        $this->propertyMappingConfiguration->allowProperties('someSourceProperty', 'someOtherProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsFalseForBlacklistedProperties()
    {
        $this->propertyMappingConfiguration->allowAllPropertiesExcept('someSourceProperty', 'someOtherProperty');
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));

        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherPropertyWhichHasNotBeenConfigured'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldSkip
     */
    public function shouldSkipReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration::shouldSkip
     */
    public function shouldSkipReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->skipProperties('someSourceProperty', 'someOtherSourceProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsCanBeRetrievedAgain()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);

        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        self::assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function nonexistentTypeConverterOptionsReturnNull()
    {
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsShouldOverrideAlreadySetOptions()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k3' => 'v3']);

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k3'));
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionShouldOverrideAlreadySetOptions()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOption($mockTypeConverterClass, 'k1', 'v3');

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsNullIfNoTypeConverterSet()
    {
        self::assertNull($this->propertyMappingConfiguration->getTypeConverter());
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsTypeConverterIfItHasBeenSet()
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverter($mockTypeConverter);
        self::assertSame($mockTypeConverter, $this->propertyMappingConfiguration->getTypeConverter());
    }

    /**
     * @return \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     */
    protected function buildChildConfigurationForSingleProperty()
    {
        $childConfiguration = $this->propertyMappingConfiguration->forProperty('key1.key2');
        $childConfiguration->setTypeConverterOption('someConverter', 'foo', 'specialChildConverter');

        return $childConfiguration;
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldRespectMapping()
    {
        $this->propertyMappingConfiguration->setMapping('k1', 'k1a');
        self::assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
        self::assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
    }

    /**
     * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
     */
    public function fluentInterfaceMethodsDataProvider()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);

        return [
            ['allowAllProperties'],
            ['allowProperties'],
            ['allowAllPropertiesExcept'],
            ['setMapping', ['k1', 'k1a']],
            ['setTypeConverterOptions', [$mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']]],
            ['setTypeConverterOption', [$mockTypeConverterClass, 'k1', 'v3']],
            ['setTypeConverter', [$this->createMock(TypeConverterInterface::class)]],
        ];
    }

    /**
     * @test
     * @dataProvider fluentInterfaceMethodsDataProvider
     */
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = [])
    {
        $actualResult = call_user_func_array([$this->propertyMappingConfiguration, $methodToTestForFluentInterface], $argumentsForMethod);
        self::assertSame($this->propertyMappingConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithGetConfigurationFor()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->getConfigurationFor('items')->getConfigurationFor('6');
        self::assertSame('v1', $configuration->getConfigurationValue('stdClass', 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithForProperty()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*.foo')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items.6.foo');
        self::assertSame('v1', $configuration->getConfigurationValue('stdClass', 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithShouldMap()
    {
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items');
        self::assertTrue($configuration->shouldMap(6));
    }
}
