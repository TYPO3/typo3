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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the FAL driver registry.
 */
class DriverRegistryTest extends UnitTestCase
{
    /**
     * @var DriverRegistry
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeSubject();
    }

    protected function initializeSubject(): void
    {
        $this->subject = new DriverRegistry();
    }

    /**
     * @test
     */
    public function registeredDriverClassesCanBeRetrieved(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $this->subject->registerDriverClass($className, 'foobar');
        $returnedClassName = $this->subject->getDriverClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    /**
     * @test
     */
    public function registerDriverClassThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979197);
        $this->subject->registerDriverClass(StringUtility::getUniqueId('class_'));
    }

    /**
     * @test
     */
    public function registerDriverClassThrowsExceptionIfShortnameIsAlreadyTakenByAnotherDriverClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979451);
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $className2 = get_class($this->getMockForAbstractClass(DriverInterface::class));
        $this->subject->registerDriverClass($className, 'foobar');
        $this->subject->registerDriverClass($className2, 'foobar');
    }

    /**
     * @test
     */
    public function getDriverClassThrowsExceptionIfClassIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314085990);
        $this->subject->getDriverClass(StringUtility::getUniqueId('class_'));
    }

    /**
     * @test
     */
    public function getDriverClassAcceptsClassNameIfClassIsRegistered(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $this->subject->registerDriverClass($className, 'foobar');
        self::assertEquals($className, $this->subject->getDriverClass($className));
    }

    /**
     * @test
     */
    public function driverRegistryIsInitializedWithPreconfiguredDrivers(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $shortName = StringUtility::getUniqueId('class_');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className
            ]
        ];
        $this->initializeSubject();
        self::assertEquals($className, $this->subject->getDriverClass($shortName));
    }

    /**
     * @test
     */
    public function driverExistsReturnsTrueForAllExistingDrivers(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $shortName = StringUtility::getUniqueId('class_');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className
            ]
        ];
        $this->initializeSubject();
        self::assertTrue($this->subject->driverExists($shortName));
        self::assertFalse($this->subject->driverExists(StringUtility::getUniqueId('class')));
    }

    /**
     * @test
     */
    public function driverExistsReturnsFalseIfDriverDoesNotExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $this->initializeSubject();
        self::assertFalse($this->subject->driverExists(StringUtility::getUniqueId('class_')));
    }
}
