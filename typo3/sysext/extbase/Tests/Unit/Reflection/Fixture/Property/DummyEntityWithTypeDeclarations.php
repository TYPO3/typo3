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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\Property;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class DummyEntityWithTypeDeclarations extends AbstractEntity
{
    // Single Type Properties

    public int $int;

    public float $float;

    public bool $bool;

    public object $object;

    public array $array;

    public mixed $mixed;

    public ?int $nullableInt;

    // Collection Type Properties

    /** @var string[] */
    public array $listWithSquareBracketsSyntax;

    /** @var array<string> */
    public array $listWithArraySyntaxWithoutKeyValueType;

    /** @var array<int,string> */
    public array $listWithArraySyntaxWithKeyValueType;

    /** @var ObjectStorage<DummyEntityWithTypeDeclarations> */
    public ObjectStorage $objectStorageWithArraySyntaxWithoutKeyValueType;

    // Union Type Properties (as of PHP 8.0)

    public int|string $intOrString;

    public int|string|null $nullableIntOrString;

    public LazyLoadingProxy|DummyEntityWithTypeDeclarations $concreteEntityOrLazyLoadingProxy;

    /** @var ObjectStorage<DummyEntityWithTypeDeclarations> */
    public ObjectStorage $objectStorage;

    /** @var LazyObjectStorage<DummyEntityWithTypeDeclarations> */
    public LazyObjectStorage $lazyObjectStorage;

    // Intersection Type Properties (as of PHP 8.1)

    public \ArrayAccess&\Traversable $arrayAccessAndTraversable;
}
