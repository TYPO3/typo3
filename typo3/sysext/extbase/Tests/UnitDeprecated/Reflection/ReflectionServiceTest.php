<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection;

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

use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 * @see test for reflection
 * @link second test for reflection
 * @link second test for reflection with second value
 */
class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @param array $foo The foo parameter
     * @return string
     */
    public function fixtureMethodForMethodTagsValues(array $foo): string
    {
        return '';
    }

    /**
     * @param bool $dummy
     * @param int $foo
     */
    public function fixtureMethodForMethodTagsValuesWithShortTypes($dummy, $foo): void
    {
    }

    /**
     * @test
     */
    public function getClassTagsValues(): void
    {
        $service = new ReflectionService();
        $classValues = $service->getClassTagsValues(static::class);
        $this->assertEquals([
            'see' => ['test for reflection'],
            'link' => ['second test for reflection', 'second test for reflection with second value']
        ], $classValues);

        $this->assertEquals(
            [],
            $service->getClassTagsValues('NonExistantNamespace\\NonExistantClass')
        );
    }

    /**
     * @test
     */
    public function getClassTagValues(): void
    {
        $service = new ReflectionService();
        $classValues = $service->getClassTagValues(static::class, 'see');
        $this->assertEquals([
            'test for reflection',
        ], $classValues);

        $this->assertEquals(
            [],
            $service->getClassTagValues(static::class, 'nonExistantTag')
        );

        $this->assertEquals(
            [],
            $service->getClassTagValues('NonExistantNamespace\\NonExistantClass', 'nonExistantTag')
        );
    }

    /**
     * @test
     */
    public function hasMethod(): void
    {
        $service = new ReflectionService();
        $this->assertTrue($service->hasMethod(static::class, 'fixtureMethodForMethodTagsValues'));
        $this->assertFalse($service->hasMethod(static::class, 'notExistentMethod'));
        $this->assertFalse($service->hasMethod('NonExistantNamespace\\NonExistantClass', 'notExistentMethod'));
    }

    /**
     * @test
     */
    public function getMethodTagsValues(): void
    {
        $service = new ReflectionService();
        $tagsValues = $service->getMethodTagsValues(static::class, 'fixtureMethodForMethodTagsValues');
        $this->assertEquals([
            'param' => ['array $foo The foo parameter'],
            'return' => ['string']
        ], $tagsValues);

        $this->assertEquals(
            [],
            $service->getMethodTagsValues(static::class, 'notExistentMethod')
        );

        $this->assertEquals(
            [],
            $service->getMethodTagsValues('NonExistantNamespace\\NonExistantClass', 'notExistentMethod')
        );
    }

    /**
     * @test
     */
    public function getMethodParameters(): void
    {
        $service = new ReflectionService();
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
                'validators' => [],
            ]
        ], $parameters);

        $this->assertSame(
            [],
            $service->getMethodParameters(static::class, 'notExistentMethod')
        );

        $this->assertSame(
            [],
            $service->getMethodParameters('NonExistantNamespace\\NonExistantClass', 'notExistentMethod')
        );
    }

    /**
     * @test
     */
    public function getMethodParametersWithShortTypeNames(): void
    {
        $service = new ReflectionService();
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
                'validators' => [],
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
                'validators' => [],
            ]
        ], $parameters);
    }

    public function testIsClassTaggedWith(): void
    {
        $service = new ReflectionService();
        $this->assertTrue($service->isClassTaggedWith(
            Fixture\DummyClassWithTags::class,
            'see'
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

    public function testIsPropertyTaggedWith(): void
    {
        $service = new ReflectionService();
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

    public function testgetPropertyTagValues(): void
    {
        $service = new ReflectionService();
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

    public function testGetPropertyTagsValues(): void
    {
        $service = new ReflectionService();
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

    public function testGetClassPropertyNames(): void
    {
        $service = new ReflectionService();
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
