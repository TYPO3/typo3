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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

use TYPO3\CMS\Extbase\Attribute as Extbase;
use TYPO3\CMS\Extbase\Attribute\ORM\Transient;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Fixture class with getters and setters
 */
class DummyClassWithAllTypesOfProperties extends AbstractEntity
{
    public $publicProperty;

    protected $protectedProperty;

    private $privateProperty;

    public $publicPropertyWithDefaultValue = 'foo';

    public string $stringTypedProperty = '';

    public ?string $nullableStringTypedProperty = null;

    #[Transient]
    public $propertyWithTransientAttribute;

    #[Extbase\ORM\Cascade(['value' => 'remove'])]
    public DummyClassWithAllTypesOfProperties $propertyWithCascadeAttribute;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties>
     */
    public $propertyWithObjectStorageAnnotation;

    /**
     * @var ObjectStorage<DummyClassWithAllTypesOfProperties>
     */
    public $propertyWithObjectStorageAnnotationWithoutFQCN;

    /**
     * @var DummyClassWithAllTypesOfProperties|LazyLoadingProxy
     */
    public $propertyWithLazyLoadingProxy;

    /**
     * @var ObjectStorage<DummyClassWithAllTypesOfProperties>|LazyObjectStorage<DummyClassWithAllTypesOfProperties>
     */
    public $propertyWithLazyObjectStorageAnnotationWithoutFQCN;
}
