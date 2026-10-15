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

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Driver\FileStorageDriverFactoryInterface;
use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;
use TYPO3\CMS\Core\Resource\Driver\FlysystemDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Tests\Unit\Resource\Driver\Fixtures\TestingDriver;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class DriverRegistryTest extends UnitTestCase
{
    #[Test]
    public function registeredDriverClassesCanBeRetrieved(): void
    {
        $className = get_class(self::createStub(DriverInterface::class));
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        $returnedClassName = $subject->getDriverClass('foobar');
        self::assertEquals($className, $returnedClassName);
    }

    #[Test]
    public function registerDriverClassThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979197);
        $subject = new DriverRegistry();
        $subject->registerDriverClass(StringUtility::getUniqueId('class_'));
    }

    #[Test]
    public function registerDriverClassThrowsExceptionIfShortnameIsAlreadyTakenByAnotherDriverClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314979451);
        $className = get_class(self::createStub(DriverInterface::class));
        $className2 = TestingDriver::class;
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        $subject->registerDriverClass($className2, 'foobar');
    }

    #[Test]
    public function getDriverClassThrowsExceptionIfClassIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1314085990);
        $subject = new DriverRegistry();
        $subject->getDriverClass(StringUtility::getUniqueId('class_'));
    }

    #[Test]
    public function getDriverClassAcceptsClassNameIfClassIsRegistered(): void
    {
        $className = get_class(self::createStub(DriverInterface::class));
        $subject = new DriverRegistry();
        $subject->registerDriverClass($className, 'foobar');
        self::assertEquals($className, $subject->getDriverClass($className));
    }

    #[Test]
    public function driverRegistryIsInitializedWithPreconfiguredDrivers(): void
    {
        $className = get_class(self::createStub(DriverInterface::class));
        $shortName = StringUtility::getUniqueId('class_');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className,
            ],
        ];
        $subject = new DriverRegistry();
        self::assertEquals($className, $subject->getDriverClass($shortName));
    }

    #[Test]
    #[IgnoreDeprecations]
    public function registeredFlexFormDataStructureTriggersDeprecation(): void
    {
        $className = get_class(self::createStub(DriverInterface::class));
        $shortName = StringUtility::getUniqueId('class_');
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            $shortName => [
                'class' => $className,
                'flexFormDS' => 'FILE:EXT:my_driver/Configuration/FlexForm/Driver.xml',
            ],
        ];
        $this->expectUserDeprecationMessageMatches('/registers a "flexFormDS", which is not evaluated anymore/');
        $subject = new DriverRegistry();
        self::assertEquals($className, $subject->getDriverClass($shortName));
    }

    #[Test]
    public function driverExistsReturnsTrueForAllExistingDrivers(): void
    {
        $className = get_class(self::createStub(DriverInterface::class));
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

    #[Test]
    public function driverExistsReturnsFalseIfDriverDoesNotExist(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $subject = new DriverRegistry();
        self::assertFalse($subject->driverExists(StringUtility::getUniqueId('class_')));
    }

    #[Test]
    public function driverClassesRegisteredWithAttributeAreRegistered(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $subject = new DriverRegistry(['Testing' => TestingDriver::class]);
        self::assertTrue($subject->driverExists('Testing'));
        self::assertSame(TestingDriver::class, $subject->getDriverClass('Testing'));
        self::assertInstanceOf(TestingDriver::class, $subject->createDriver('Testing', []));
    }

    #[Test]
    public function driverRegisteredWithAttributeTakesPrecedenceOverDriverRegisteredInConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            'Testing' => ['class' => LocalDriver::class],
            'Factory' => ['class' => LocalDriver::class],
        ];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('warning');
        $driverFactory = self::createStub(FileStorageDriverFactoryInterface::class);
        $subject = new DriverRegistry(
            ['Testing' => TestingDriver::class],
            new ServiceLocator(['Factory' => static fn(): FileStorageDriverFactoryInterface => $driverFactory]),
            $logger
        );
        self::assertSame(TestingDriver::class, $subject->getDriverClass('Testing'));
        self::assertTrue($subject->driverExists('Factory'));
    }

    #[Test]
    public function driverRegisteredWithAttributeAndInConfigurationWithSameClassIsNotReported(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [
            'Testing' => ['class' => TestingDriver::class],
        ];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $subject = new DriverRegistry(['Testing' => TestingDriver::class], null, $logger);
        self::assertSame(TestingDriver::class, $subject->getDriverClass('Testing'));
    }

    #[Test]
    public function driverFactoryCreatesTheDriver(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $driver = new TestingDriver();
        $driverFactory = self::createMock(FileStorageDriverFactoryInterface::class);
        $driverFactory->expects($this->once())->method('createDriver')->with(['option' => 'value'])->willReturn($driver);
        $subject = new DriverRegistry([], new ServiceLocator(['Factory' => static fn(): FileStorageDriverFactoryInterface => $driverFactory]));
        self::assertTrue($subject->driverExists('Factory'));
        self::assertSame($driver, $subject->createDriver('Factory', ['option' => 'value']));
    }

    #[Test]
    public function getDriverClassThrowsForDriverCreatedByFactory(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $driverFactory = self::createStub(FileStorageDriverFactoryInterface::class);
        $subject = new DriverRegistry([], new ServiceLocator(['Factory' => static fn(): FileStorageDriverFactoryInterface => $driverFactory]));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792161904);
        $subject->getDriverClass('Factory');
    }

    #[Test]
    public function adapterFactoriesProvideFlysystemDrivers(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $adapterFactory = self::createStub(FilesystemAdapterFactoryInterface::class);
        $subject = new DriverRegistry([], new ServiceLocator(['S3' => static fn(): FilesystemAdapterFactoryInterface => $adapterFactory]));
        self::assertTrue($subject->driverExists('S3'));
        self::assertSame(FlysystemDriver::class, $subject->getDriverClass('S3'));
        self::assertInstanceOf(FlysystemDriver::class, $subject->createDriver('S3', []));
    }

    #[Test]
    public function registeringDriverClassWithIdentifierOfFactoryThrows(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'] = [];
        $adapterFactory = self::createStub(FilesystemAdapterFactoryInterface::class);
        $subject = new DriverRegistry([], new ServiceLocator(['S3' => static fn(): FilesystemAdapterFactoryInterface => $adapterFactory]));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1794408871);
        $subject->registerDriverClass(TestingDriver::class, 'S3');
    }
}
