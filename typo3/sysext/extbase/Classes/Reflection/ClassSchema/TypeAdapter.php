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
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

/**
 * Adapter providing the old Symfony PropertyInfo\Type API on top of the new TypeInfo\Type.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class TypeAdapter
{
    public function __construct(private Type $type, private bool $forceNullable = false) {}

    public function getBuiltinType(): string
    {
        return $this->resolveBuiltinType($this->type);
    }

    public function getClassName(): ?string
    {
        return $this->resolveClassName($this->type);
    }

    public function isCollection(): bool
    {
        $type = $this->type instanceof NullableType ? $this->type->getWrappedType() : $this->type;
        return $type instanceof CollectionType;
    }

    /**
     * @return list<TypeAdapter>
     */
    public function getCollectionKeyTypes(): array
    {
        $type = $this->type instanceof NullableType ? $this->type->getWrappedType() : $this->type;
        if ($type instanceof CollectionType) {
            return $this->decomposeType($type->getCollectionKeyType());
        }
        return [];
    }

    /**
     * @return list<TypeAdapter>
     */
    public function getCollectionValueTypes(): array
    {
        $type = $this->type instanceof NullableType ? $this->type->getWrappedType() : $this->type;
        if ($type instanceof CollectionType) {
            return $this->decomposeType($type->getCollectionValueType());
        }
        return [];
    }

    public function isNullable(): bool
    {
        return $this->forceNullable || $this->type->isNullable();
    }

    /**
     * @return list<TypeAdapter>
     */
    private function decomposeType(Type $type): array
    {
        if ($type instanceof UnionType) {
            return array_map(
                static fn(Type $t) => new self($t),
                $type->getTypes()
            );
        }
        return [new self($type)];
    }

    private function resolveBuiltinType(Type $type): string
    {
        if ($type instanceof BuiltinType) {
            return $type->getTypeIdentifier()->value;
        }
        if ($type instanceof ObjectType) {
            return 'object';
        }
        if ($type instanceof WrappingTypeInterface) {
            return $this->resolveBuiltinType($type->getWrappedType());
        }
        return 'object';
    }

    private function resolveClassName(Type $type): ?string
    {
        if ($type instanceof ObjectType) {
            return $type->getClassName();
        }
        if ($type instanceof WrappingTypeInterface) {
            return $this->resolveClassName($type->getWrappedType());
        }
        return null;
    }
}
