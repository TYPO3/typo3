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

class DriverRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function registeredDriverClassesCanBeRetrieved(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        $returnedClassName = $subject->getDriverClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    /**
     * @test
     */
    public function registerDriverClassThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979197);
        $subject = new DriverRegistry();
        $subject->registerDriverClass(StringUtility::getUniqueId('class_'));
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
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        $subject->registerDriverClass($className2, 'foobar');
    }

    /**
     * @test
     */
    public function getDriverClassThrowsExceptionIfClassIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314085990);
        $subject = new DriverRegistry();
        $subject->getDriverClass(StringUtility::getUniqueId('class_'));
    }

    /**
     * @test
     */
    public function getDriverClassAcceptsClassNameIfClassIsRegistered(): void
    {
        $className = get_class($this->getMockForAbstractClass(AbstractDriver::class));
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        self::assertEquals($className, $subject->getDriverClass($className));
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
                'class' => $className,
            ],
        ];
        $subject = new DriverRegistry();
        self::assertEquals($className, $subject->getDriverClass($shortName));
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
                'class' => $className,
            ],
        ];
        $subject = new DriverRegistry();
        self::assertTrue($subject->driverExists($shortName));
        self::assertFalse($subject->driverExists(StringUtility::getUniqueId('class')));
    }

    /**
     * @test
     */
    public function driverExistsReturnsFalseIfDriverDoesNotExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $subject = new DriverRegistry();
        self::assertFalse($subject->driverExists(StringUtility::getUniqueId('class_')));
    }
}
