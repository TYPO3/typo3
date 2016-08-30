<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * Test case
 * @firsttest test for reflection
 * @anothertest second test for reflection
 * @anothertest second test for reflection with second value
 */
class ReflectionServiceTest extends UnitTestCase
{
    /**
     * @param array $foo The foo parameter
     * @return void
     */
    public function fixtureMethodForMethodTagsValues(array $foo)
    {
    }

    /**
     * @param bool $dummy
     * @param int $foo
     * @return void
     */
    public function fixtureMethodForMethodTagsValuesWithShortTypes($dummy, $foo)
    {
    }

    /**
     * @test
     */
    public function getClassTagsValues()
    {
        $service = new ReflectionService();
        $classValues = $service->getClassTagsValues(get_class($this));
        $this->assertEquals([
            'firsttest' => ['test for reflection'],
            'anothertest' => ['second test for reflection', 'second test for reflection with second value']
        ], $classValues);
    }

    /**
     * @test
     */
    public function getClassTagValues()
    {
        $service = new ReflectionService();
        $classValues = $service->getClassTagValues(get_class($this), 'firsttest');
        $this->assertEquals([
            'test for reflection',
        ], $classValues);
    }

    /**
     * @test
     */
    public function hasMethod()
    {
        $service = new ReflectionService();
        $this->assertTrue($service->hasMethod(get_class($this), 'fixtureMethodForMethodTagsValues'));
        $this->assertFalse($service->hasMethod(get_class($this), 'notExistentMethod'));
    }

    /**
     * @test
     */
    public function getMethodTagsValues()
    {
        $service = new ReflectionService();
        $tagsValues = $service->getMethodTagsValues(get_class($this), 'fixtureMethodForMethodTagsValues');
        $this->assertEquals([
            'param' => ['array $foo The foo parameter'],
            'return' => ['void']
        ], $tagsValues);
    }

    /**
     * @test
     */
    public function getMethodParameters()
    {
        $service = new ReflectionService();
        $parameters = $service->getMethodParameters(get_class($this), 'fixtureMethodForMethodTagsValues');
        $this->assertSame([
            'foo' => [
                'position' => 0,
                'byReference' => false,
                'array' => true,
                'optional' => false,
                'allowsNull' => false,
                'class' => null,
                'type' => 'array'
            ]
        ], $parameters);
    }

    /**
     * @test
     */
    public function getMethodParametersWithShortTypeNames()
    {
        $service = new ReflectionService();
        $parameters = $service->getMethodParameters(get_class($this), 'fixtureMethodForMethodTagsValuesWithShortTypes');
        $this->assertSame([
            'dummy' => [
                'position' => 0,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true,
                'class' => null,
                'type' => 'boolean'
            ],
            'foo' => [
                'position' => 1,
                'byReference' => false,
                'array' => false,
                'optional' => false,
                'allowsNull' => true,
                'class' => null,
                'type' => 'integer'
            ]
        ], $parameters);
    }
}
