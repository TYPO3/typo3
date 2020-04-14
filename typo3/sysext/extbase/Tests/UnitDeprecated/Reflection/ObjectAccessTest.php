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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Reflection;

use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture\DummyClassWithGettersAndSetters;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ObjectAccessTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var DummyClassWithGettersAndSetters
     */
    protected $dummyObject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dummyObject = new DummyClassWithGettersAndSetters();
        $this->dummyObject->setProperty('string1');
        $this->dummyObject->setAnotherProperty(42);
        $this->dummyObject->shouldNotBePickedUp = true;
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnexposedPropertyIfForceDirectAccessIsTrue()
    {
        $property = ObjectAccess::getProperty($this->dummyObject, 'unexposedProperty', true);
        self::assertEquals($property, 'unexposed', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyReturnsExpectedValueForUnknownPropertyIfForceDirectAccessIsTrue()
    {
        $this->dummyObject->unknownProperty = 'unknown';
        $property = ObjectAccess::getProperty($this->dummyObject, 'unknownProperty', true);
        self::assertEquals($property, 'unknown', 'A property of a given object was not returned correctly.');
    }

    /**
     * @test
     */
    public function getPropertyThrowsPropertyNotAccessibleExceptionForNotExistingPropertyIfForceDirectAccessIsTrue()
    {
        $this->expectException(PropertyNotAccessibleException::class);
        $this->expectExceptionCode(1302855001);
        ObjectAccess::getProperty($this->dummyObject, 'notExistingProperty', true);
    }
}
