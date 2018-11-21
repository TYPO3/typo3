<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Annotation\ORM\Transient;

/**
 * Fixture class with getters and setters
 */
class DummyClassWithAllTypesOfProperties
{
    public $publicProperty;

    protected $protectedProperty;

    private $privateProperty;

    public static $publicStaticProperty;

    protected static $protectedStaticProperty;

    private static $privateStaticProperty;

    public $publicPropertyWithDefaultValue = 'foo';

    /**
     * @license
     * @copyright
     * @author
     * @version
     */
    public $propertyWithIgnoredTags;

    /**
     * @Extbase\Inject
     * @var DummyClassWithAllTypesOfProperties
     */
    public $propertyWithInjectAnnotation;

    /**
     * @Transient
     */
    public $propertyWithTransientAnnotation;

    /**
     * @var DummyClassWithAllTypesOfProperties
     * @Extbase\ORM\Cascade("remove")
     */
    public $propertyWithCascadeAnnotation;

    /**
     * @Extbase\ORM\Cascade("remove")
     */
    public $propertyWithCascadeAnnotationWithoutVarAnnotation;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties>
     */
    public $propertyWithObjectStorageAnnotation;
}
