<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * Test case
 * @firsttest test for reflection
 * @anothertest second test for reflection
 * @anothertest second test for reflection with second value
 */
class ReflectionServiceTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @param array $foo The foo parameter
     * @return string
     */
    public function fixtureMethodForMethodTagsValues(array $foo)
    {
    }

    /**
     * @param bool $dummy
     * @param int $foo
     */
    public function fixtureMethodForMethodTagsValuesWithShortTypes($dummy, $foo)
    {
    }

    /**
     * @test
     */
    public function getClassTagsValues()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $classValues = $service->getClassTagsValues(static::class);
        $this->assertEquals([
            'firsttest' => ['test for reflection'],
            'anothertest' => ['second test for reflection', 'second test for reflection with second value']
        ], $classValues);

        $this->assertEquals(
            [],
            $service->getClassTagsValues('NonExistantNamespace\\NonExistantClass')
        );
    }

    /**
     * @test
     */
    public function getClassTagValues()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $classValues = $service->getClassTagValues(static::class, 'firsttest');
        $this->assertEquals([
            'test for reflection',
        ], $classValues);

        $this->assertEquals(
            [],
            $service->getClassTagValues('NonExistantNamespace\\NonExistantClass', 'nonExistantTag')
        );
    }

    /**
     * @test
     */
    public function hasMethod()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $this->assertTrue($service->hasMethod(static::class, 'fixtureMethodForMethodTagsValues'));
        $this->assertFalse($service->hasMethod(static::class, 'notExistentMethod'));
        $this->assertFalse($service->hasMethod('NonExistantNamespace\\NonExistantClass', 'notExistentMethod'));
    }

    /**
     * @test
     */
    public function getMethodTagsValues()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $tagsValues = $service->getMethodTagsValues(static::class, 'fixtureMethodForMethodTagsValues');
        $this->assertEquals([
            'param' => ['array $foo The foo parameter'],
            'return' => ['string']
        ], $tagsValues);

        $this->assertEquals(
            [],
            $service->getMethodTagsValues('NonExistantNamespace\\NonExistantClass', 'notExistentMethod')
        );
    }

    /**
     * @test
     */
    public function getMethodParameters()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $parameters = $service->getMethodParameters(static::class, 'fixtureMethodForMethodTagsValues');
        $this->assertSame([
            'foo' => [
                'position' => 0,
                'byReference' => false,
                'array' => true,
                'optional' => false,
                'allowsNull' => false,
                'class' => null,
                'type' => 'array',
                'nullable' => false,
                'default' =>  null,
                'hasDefaultValue' =>  false,
                'defaultValue' =>  null,
                'dependency' =>  null,
            ]
        ], $parameters);

        $this->assertSame(
            [],
            $service->getMethodParameters('NonExistantNamespace\\NonExistantClass', 'notExistentMethod')
        );
    }

    /**
     * @test
     */
    public function getMethodParametersWithShortTypeNames()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $parameters = $service->getMethodParameters(static::class, 'fixtureMethodForMethodTagsValuesWithShortTypes');
        $this->assertSame([
            'dummy' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true,
                'class' => null,
                'type' => 'boolean',
                'nullable' => true,
                'default' =>  null,
                'hasDefaultValue' =>  false,
                'defaultValue' =>  null,
                'dependency' =>  null,
            ],
            'foo' => [
                'position' => 1,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true,
                'class' => null,
                'type' => 'integer',
                'nullable' => true,
                'default' =>  null,
                'hasDefaultValue' =>  false,
                'defaultValue' =>  null,
                'dependency' =>  null,
            ]
        ], $parameters);
    }

    public function testIsClassTaggedWith()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $this->assertTrue($service->isClassTaggedWith(
            Fixture\DummyClassWithTags::class,
            'foo'
        ));

        $this->assertFalse($service->isClassTaggedWith(
            Fixture\DummyClassWithAllTypesOfProperties::class,
            'bar'
        ));

        $this->assertFalse($service->isClassTaggedWith(
            'NonExistantNamespace\\NonExistantClass',
            'foo'
        ));
    }

    public function testIsPropertyTaggedWith()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);
        $this->assertTrue($service->isPropertyTaggedWith(
            Fixture\DummyClassWithAllTypesOfProperties::class,
            'propertyWithInjectAnnotation',
            'inject'
        ));

        $this->assertFalse($service->isPropertyTaggedWith(
            Fixture\DummyClassWithAllTypesOfProperties::class,
            'propertyWithInjectAnnotation',
            'foo'
        ));

        $this->assertFalse($service->isPropertyTaggedWith(
            Fixture\DummyClassWithAllTypesOfProperties::class,
            'nonExistantProperty',
            'foo'
        ));

        $this->assertFalse($service->isPropertyTaggedWith(
            'NonExistantNamespace\\NonExistantClass',
            'propertyWithInjectAnnotation',
            'inject'
        ));
    }

    public function testgetPropertyTagValues()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);

        $this->assertSame(
            [],
            $service->getPropertyTagValues(
                Fixture\DummyClassWithAllTypesOfProperties::class,
                'propertyWithInjectAnnotation',
                'foo'
            )
        );

        $this->assertSame(
            [],
            $service->getPropertyTagValues(
            Fixture\DummyClassWithAllTypesOfProperties::class,
            'propertyWithInjectAnnotation',
            'inject'
            )
        );
    }

    public function testGetPropertyTagsValues()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);

        $this->assertSame(
            [
                'inject' => [],
                'var' => [
                    'DummyClassWithAllTypesOfProperties'
                ]
            ],
            $service->getPropertyTagsValues(
                Fixture\DummyClassWithAllTypesOfProperties::class,
                'propertyWithInjectAnnotation'
            )
        );

        $this->assertSame(
            [],
            $service->getPropertyTagsValues(
                Fixture\DummyClassWithAllTypesOfProperties::class,
                'nonExistantProperty'
            )
        );

        $this->assertSame(
            [],
            $service->getPropertyTagsValues(
                'NonExistantNamespace\\NonExistantClass',
                'nonExistantProperty'
            )
        );
    }

    public function testGetClassPropertyNames()
    {
        $service = GeneralUtility::makeInstance(ReflectionService::class);

        $this->assertSame(
            [
                'publicProperty',
                'protectedProperty',
                'privateProperty',
                'publicStaticProperty',
                'protectedStaticProperty',
                'privateStaticProperty',
                'propertyWithIgnoredTags',
                'propertyWithInjectAnnotation',
                'propertyWithTransientAnnotation',
                'propertyWithCascadeAnnotation',
                'propertyWithCascadeAnnotationWithoutVarAnnotation',
                'propertyWithObjectStorageAnnotation'
            ],
            $service->getClassPropertyNames(Fixture\DummyClassWithAllTypesOfProperties::class)
        );

        $this->assertSame(
            [],
            $service->getClassPropertyNames('NonExistantNamespace\\NonExistantClass')
        );
    }
}
