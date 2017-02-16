<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

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
 * Testcase for the FAL driver registry.
 */
class DriverRegistryTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\Driver\DriverRegistry
     */
    protected $subject;

    protected function setUp()
    {
        $this->initializeSubject();
    }

    protected function initializeSubject()
    {
        $this->subject = new \TYPO3\CMS\Core\Resource\Driver\DriverRegistry();
    }

    /**
     * @test
     */
    public function registeredDriverClassesCanBeRetrieved()
    {
        $className = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class));
        $this->subject->registerDriverClass($className, 'foobar');
        $returnedClassName = $this->subject->getDriverClass('foobar');
        $this->assertEquals($className, $returnedClassName);
    }

    /**
     * @test
     */
    public function registerDriverClassThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979197);
        $this->subject->registerDriverClass($this->getUniqueId());
    }

    /**
     * @test
     */
    public function registerDriverClassThrowsExceptionIfShortnameIsAlreadyTakenByAnotherDriverClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979451);
        $className = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class));
        $className2 = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\DriverInterface::class));
        $this->subject->registerDriverClass($className, 'foobar');
        $this->subject->registerDriverClass($className2, 'foobar');
    }

    /**
     * @test
     */
    public function getDriverClassThrowsExceptionIfClassIsNotRegistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314085990);
        $this->subject->getDriverClass($this->getUniqueId());
    }

    /**
     * @test
     */
    public function getDriverClassAcceptsClassNameIfClassIsRegistered()
    {
        $className = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class));
        $this->subject->registerDriverClass($className, 'foobar');
        $this->assertEquals($className, $this->subject->getDriverClass($className));
    }

    /**
     * @test
     */
    public function driverRegistryIsInitializedWithPreconfiguredDrivers()
    {
        $className = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class));
        $shortName = $this->getUniqueId();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className
            ]
        ];
        $this->initializeSubject();
        $this->assertEquals($className, $this->subject->getDriverClass($shortName));
    }

    /**
     * @test
     */
    public function driverExistsReturnsTrueForAllExistingDrivers()
    {
        $className = get_class($this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\Driver\AbstractDriver::class));
        $shortName = $this->getUniqueId();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className
            ]
        ];
        $this->initializeSubject();
        $this->assertTrue($this->subject->driverExists($shortName));
        $this->assertFalse($this->subject->driverExists($this->getUniqueId()));
    }

    /**
     * @test
     */
    public function driverExistsReturnsFalseIfDriverDoesNotExist()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
        ];
        $this->initializeSubject();
        $this->assertFalse($this->subject->driverExists($this->getUniqueId()));
    }
}
