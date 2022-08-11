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

class DummyEntityWithoutTypeDeclarations extends AbstractEntity
{
    // Single Type Properties

    public $nullableMixedProperty;

    /** @var int */
    public $int;

    /** @var float */
    public $float;

    /** @var bool */
    public $bool;

    /** @var object */
    public $object;

    /** @var array */
    public $array;

    /** @var mixed */
    public $mixed;

    /** @var int|null */
    public $nullableInt;

    // Collection Type Properties

    /** @var string[] */
    public $listWithSquareBracketsSyntax;

    /** @var array<string> */
    public $listWithArraySyntaxWithoutKeyValueType;

    /** @var array<int,string> */
    public $listWithArraySyntaxWithKeyValueType;

    /** @var ObjectStorage<DummyEntityWithoutTypeDeclarations> */
    public $objectStorageWithArraySyntaxWithoutKeyValueType;

    // Union Type Properties (as of PHP 8.0)

    /** @var int|string */
    public $intOrString;

    /** @var int|string|null */
    public $nullableIntOrString;

    /** @var LazyLoadingProxy|DummyEntityWithoutTypeDeclarations */
    public $concreteEntityOrLazyLoadingProxy;

    /** @var ObjectStorage<DummyEntityWithoutTypeDeclarations> */
    public $objectStorage;

    /** @var LazyObjectStorage<DummyEntityWithoutTypeDeclarations> */
    public $lazyObjectStorage;

    // Intersection Type Properties (as of PHP 8.1)

    /** @var \ArrayAccess&\Traversable */
    public $arrayAccessAndTraversable;
}
