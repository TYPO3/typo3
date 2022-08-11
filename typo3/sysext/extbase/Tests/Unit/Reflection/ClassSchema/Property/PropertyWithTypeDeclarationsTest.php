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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\ClassSchema\Property;

use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\Property\DummyEntityWithTypeDeclarations;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PropertyWithTypeDeclarationsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function intProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('int')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('int', $propertyTypes[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function floatProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('float')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('float', $propertyTypes[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function boolProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('bool')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('bool', $propertyTypes[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function objectProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('object')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('object', $propertyTypes[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function arrayProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('array')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('array', $propertyTypes[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function mixedProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('mixed')->getTypes();

        self::assertCount(0, $propertyTypes);
    }

    /**
     * @test
     */
    public function nullableIntProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('nullableInt')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame('int', $propertyTypes[0]->getBuiltinType());
        self::assertTrue($propertyTypes[0]->isNullable());
    }

    // Collection Type Properties

    /**
     * @test
     */
    public function listWithSquareBracketsSyntaxProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('listWithSquareBracketsSyntax')->getTypes();

        self::assertTrue($propertyTypes[0]->isCollection());
        self::assertSame('array', $propertyTypes[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionKeyTypes());
        self::assertSame('int', $propertyTypes[0]->getCollectionKeyTypes()[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionValueTypes());
        self::assertSame('string', $propertyTypes[0]->getCollectionValueTypes()[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function listWithArraySyntaxWithoutKeyValueTypeProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('listWithArraySyntaxWithoutKeyValueType')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertTrue($propertyTypes[0]->isCollection());
        self::assertSame('array', $propertyTypes[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionKeyTypes());
        self::assertSame('int', $propertyTypes[0]->getCollectionKeyTypes()[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionValueTypes());
        self::assertSame('string', $propertyTypes[0]->getCollectionValueTypes()[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function listWithArraySyntaxWithKeyValueTypeProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('listWithArraySyntaxWithKeyValueType')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertTrue($propertyTypes[0]->isCollection());
        self::assertSame('array', $propertyTypes[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionKeyTypes());
        self::assertSame('int', $propertyTypes[0]->getCollectionKeyTypes()[0]->getBuiltinType());

        self::assertCount(1, $propertyTypes[0]->getCollectionValueTypes());
        self::assertSame('string', $propertyTypes[0]->getCollectionValueTypes()[0]->getBuiltinType());
    }

    /**
     * @test
     */
    public function objectStorageWithArraySyntaxWithoutKeyValueTypeProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('objectStorageWithArraySyntaxWithoutKeyValueType')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertTrue($propertyTypes[0]->isCollection());
        self::assertSame('object', $propertyTypes[0]->getBuiltinType());
        self::assertSame(ObjectStorage::class, $propertyTypes[0]->getClassName());

        self::assertCount(0, $propertyTypes[0]->getCollectionKeyTypes());

        self::assertCount(1, $propertyTypes[0]->getCollectionValueTypes());
        self::assertSame('object', $propertyTypes[0]->getCollectionValueTypes()[0]->getBuiltinType());
        self::assertSame(DummyEntityWithTypeDeclarations::class, $propertyTypes[0]->getCollectionValueTypes()[0]->getClassName());
    }

    // Union Type Properties (as of PHP 8.0)

    /**
     * @test
     */
    public function intOrStringProperty(): void
    {
        $property = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('intOrString');
        $propertyTypes = $property->getTypes();

        self::assertCount(2, $propertyTypes);
        self::assertSame('string', $propertyTypes[0]->getBuiltinType());
        self::assertSame('int', $propertyTypes[1]->getBuiltinType());
        self::assertFalse($propertyTypes[0]->isNullable());
        self::assertFalse($propertyTypes[1]->isNullable());
        self::assertFalse($propertyTypes[1]->isNullable());
        self::assertSame('string', $property->getPrimaryType()?->getBuiltinType());
    }

    /**
     * @test
     */
    public function nullableIntOrStringProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('nullableIntOrString')->getTypes();

        self::assertCount(2, $propertyTypes);
        self::assertSame('string', $propertyTypes[0]->getBuiltinType());
        self::assertSame('int', $propertyTypes[1]->getBuiltinType());
        self::assertTrue($propertyTypes[0]->isNullable());
        self::assertTrue($propertyTypes[1]->isNullable());
    }

    /**
     * @test
     */
    public function concreteEntityOrLazyLoadingProxyProperty(): void
    {
        $property = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('concreteEntityOrLazyLoadingProxy');
        $propertyTypes = $property->getTypes();

        self::assertCount(2, $propertyTypes);
        self::assertSame(LazyLoadingProxy::class, $propertyTypes[0]->getClassName());
        self::assertSame(DummyEntityWithTypeDeclarations::class, $propertyTypes[1]->getClassName());
        self::assertSame(DummyEntityWithTypeDeclarations::class, $property->getPrimaryType()?->getClassName());
    }

    /**
     * @test
     */
    public function objectStorageProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('objectStorage')->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame(ObjectStorage::class, $propertyTypes[0]->getClassName());
        self::assertSame(DummyEntityWithTypeDeclarations::class, $propertyTypes[0]->getCollectionValueTypes()[0]->getClassName());
    }

    /**
     * @test
     */
    public function lazyObjectStorage(): void
    {
        $property = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('lazyObjectStorage');
        $propertyTypes = $property->getTypes();

        self::assertCount(1, $propertyTypes);
        self::assertSame(LazyObjectStorage::class, $propertyTypes[0]->getClassName());
        self::assertSame(DummyEntityWithTypeDeclarations::class, $propertyTypes[0]->getCollectionValueTypes()[0]->getClassName());
        self::assertSame(LazyObjectStorage::class, $property->getPrimaryType()?->getClassName());
    }

    // Intersection Type Properties (as of PHP 8.1)

    /**
     * @test
     */
    public function arrayAccessAndTraversableProperty(): void
    {
        $propertyTypes = (new ClassSchema(DummyEntityWithTypeDeclarations::class))
            ->getProperty('arrayAccessAndTraversable')->getTypes();

        self::assertCount(2, $propertyTypes);
        self::assertSame(\ArrayAccess::class, $propertyTypes[0]->getClassName());
        self::assertSame(\Traversable::class, $propertyTypes[1]->getClassName());
    }
}
