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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema;

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @phpstan-type PropertyDefinitionSpec array{
 *     'c': null|string,
 *     'f': null|array<string, mixed>,
 *     't': null|Type,
 *     'v': list<array>,
 *     'propertyCharacteristicsBit'?: int
 * }
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class Property
{
    /**
     * @var PropertyDefinitionSpec
     */
    private array $definition;
    private PropertyCharacteristics $characteristics;

    /**
     * @param PropertyDefinitionSpec $definition
     */
    public function __construct(
        private readonly string $name,
        array $definition
    ) {
        $this->characteristics = new PropertyCharacteristics($definition['propertyCharacteristicsBit']);
        unset($definition['propertyCharacteristicsBit']);

        $defaults = [
            'c' => null, // cascade
            'f' => null, // file upload
            't' => null, // type
            'v' => [], // validators
        ];

        foreach ($defaults as $key => $defaultValue) {
            if (!isset($definition[$key])) {
                $definition[$key] = $defaultValue;
            }
        }

        $this->definition = $definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the types (string, integer, ...) set by the `@var` doc comment and php property type declarations
     *
     * Returns empty array if types could not be evaluated
     *
     * @return list<TypeAdapter>
     */
    public function getTypes(): array
    {
        $type = $this->getType();
        if ($type === null) {
            return [];
        }
        if ($type instanceof BuiltinType && $type->getTypeIdentifier() === TypeIdentifier::MIXED) {
            return [];
        }
        // NullableType extends UnionType, check first
        if ($type instanceof NullableType) {
            $inner = $type->getWrappedType();
            if ($inner instanceof UnionType) {
                return array_map(
                    static fn(Type $t) => new TypeAdapter($t, forceNullable: true),
                    $inner->getTypes()
                );
            }
            if ($inner instanceof IntersectionType) {
                return array_map(
                    static fn(Type $t) => new TypeAdapter($t, forceNullable: true),
                    $inner->getTypes()
                );
            }
            return [new TypeAdapter($inner, forceNullable: true)];
        }
        if ($type instanceof UnionType) {
            $members = array_filter(
                $type->getTypes(),
                static fn(Type $t) => !($t instanceof BuiltinType && $t->getTypeIdentifier() === TypeIdentifier::NULL)
            );
            return array_values(array_map(
                static fn(Type $t) => new TypeAdapter($t),
                $members
            ));
        }
        if ($type instanceof IntersectionType) {
            return array_map(
                static fn(Type $t) => new TypeAdapter($t),
                $type->getTypes()
            );
        }
        return [new TypeAdapter($type)];
    }

    /**
     * Gets the native `symfony/type-info` type.
     */
    public function getType(): ?Type
    {
        return $this->definition['t'];
    }

    /**
     * Returns the primary type found in a list of types except LazyLoadingProxy
     */
    public function getPrimaryType(): ?TypeAdapter
    {
        $types = $this->getTypes();
        $filtered = array_values(array_filter(
            $types,
            static fn(TypeAdapter $t) => $t->getClassName() !== LazyLoadingProxy::class
        ));
        return $filtered[0] ?? null;
    }

    public function getPrimaryCollectionValueType(): ?TypeAdapter
    {
        $primaryType = $this->getPrimaryType();
        if ($primaryType === null || !$primaryType->isCollection()) {
            return null;
        }
        return $primaryType->getCollectionValueTypes()[0] ?? null;
    }

    /**
     * @return list<TypeAdapter>
     */
    public function getFilteredTypes(callable $callback): array
    {
        return array_values(array_filter($this->getTypes(), $callback));
    }

    public function filterLazyLoadingProxyAndLazyObjectStorage(TypeAdapter $type): bool
    {
        return !in_array((string)$type->getClassName(), [LazyLoadingProxy::class, LazyObjectStorage::class], true);
    }

    public function isObjectStorageType(): bool
    {
        $filteredTypes = $this->getFilteredTypes(
            static fn(TypeAdapter $type) => in_array((string)$type->getClassName(), [ObjectStorage::class, LazyObjectStorage::class], true)
        );

        return $filteredTypes !== [];
    }

    public function isPublic(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PUBLIC);
    }

    public function isProtected(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PROTECTED);
    }

    public function isPrivate(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::VISIBILITY_PRIVATE);
    }

    public function isLazy(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_LAZY);
    }

    public function isTransient(): bool
    {
        return $this->characteristics->get(PropertyCharacteristics::ANNOTATED_TRANSIENT);
    }

    public function isNullable(): bool
    {
        $primaryType = $this->getPrimaryType();
        return $primaryType === null || $primaryType->isNullable();
    }

    public function getValidators(): array
    {
        return $this->definition['v'];
    }

    public function getFileUpload(): ?array
    {
        return $this->definition['f'];
    }

    public function getCascadeValue(): ?string
    {
        return $this->definition['c'];
    }
}
