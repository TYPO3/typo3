<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection\Fixture;

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

    /**
     * @license
     * @copyright
     * @author
     * @version
     */
    public $propertyWithIgnoredTags;

    /**
     * @inject
     * @var DummyClassWithAllTypesOfProperties
     */
    public $propertyWithInjectAnnotation;

    /**
     * @transient
     */
    public $propertyWithTransientAnnotation;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithAllTypesOfProperties>
     */
    public $propertyWithObjectStorageAnnotation;
}
