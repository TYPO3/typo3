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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Report;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionRemote;
use TYPO3\CMS\Extensionmanager\Report\ExtensionStatus;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ExtensionStatusTest extends UnitTestCase
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->languageService = $this->prophesize(LanguageService::class)->reveal();
    }

    /**
     * @test
     */
    public function extensionStatusImplementsStatusProviderInterface(): void
    {
        $reportMock = $this->createMock(ExtensionStatus::class);
        self::assertInstanceOf(StatusProviderInterface::class, $reportMock);
    }

    /**
     * @test
     */
    public function getStatusReturnsArray(): void
    {
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->onlyMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        self::assertIsArray($report->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnArrayContainsFiveEntries(): void
    {
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->onlyMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        self::assertCount(5, $report->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnArrayContainsInstancesOfReportsStatusStatus(): void
    {
        $statusObject = $this->getMockBuilder(Status::class)
            ->setConstructorArgs(['title', 'value'])
            ->getMock();
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->onlyMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $report->method('getMainRepositoryStatus')->willReturn($statusObject);

        foreach ($report->getStatus() as $status) {
            if ($status) {
                self::assertInstanceOf(Status::class, $status);
            }
        }
    }

    /**
     * @test
     */
    public function getStatusCallsMainRepositoryForMainRepositoryStatusResult(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests();
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $subject->getStatus();

        $remoteRegistryProphecy->hasDefaultRemote()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfRepositoryIsNotFound(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(0, true, false);

        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::ERROR);
    }

    /**
     * @test
     */
    public function getStatusReturnsNoticeIfRepositoryUpdateIsLongerThanSevenDaysAgo(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests();
        $remote = new class() extends TerExtensionRemote {
            public function __construct()
            {
            }

            public function getLastUpdate(): \DateTimeInterface
            {
                return new \DateTimeImmutable('-14days');
            }

            protected function isDownloadedExtensionListUpToDate(): bool
            {
                return true;
            }
        };
        $remoteRegistryProphecy->getDefaultRemote()->willReturn($remote);

        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::NOTICE);
    }

    /**
     * @test
     */
    public function getStatusReturnsOkForLoadedExtensionIfNoInsecureExtensionIsLoaded(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests();
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::OK);
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorForLoadedExtensionIfInsecureExtensionIsLoaded(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(-1);
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['extensionsSecurityStatusInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::ERROR);
    }

    /**
     * @test
     */
    public function getStatusReturnsOkForExistingExtensionIfNoInsecureExtensionExists(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(0, false);
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        foreach ($status as $statusObject) {
            self::assertInstanceOf(Status::class, $statusObject);
            self::assertEquals($statusObject->getSeverity(), Status::OK);
        }
    }

    /**
     * @test
     */
    public function getStatusReturnsWarningForExistingExtensionIfInsecureExtensionExistsButIsNotLoaded(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(-1, false);
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['extensionsSecurityStatusNotInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::WARNING);
    }

    /**
     * @test
     */
    public function getStatusReturnsWarningForLoadedExtensionIfOutdatedExtensionIsLoaded(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(-2, true);
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['extensionsOutdatedStatusInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::WARNING);
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorForExistingExtensionIfOutdatedExtensionExists(): void
    {
        $remoteRegistryProphecy = $this->setUpRegistryStatusTests(-2, false);
        $subject = new ExtensionStatus($remoteRegistryProphecy->reveal(), $this->languageService);
        $status = $subject->getStatus();
        $statusObject = $status['extensionsOutdatedStatusNotInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals($statusObject->getSeverity(), Status::WARNING);
    }

    /**
     * @param int $reviewState
     * @param bool $installed
     * @param bool $setupRepositoryStatusOk
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    protected function setUpRegistryStatusTests(int $reviewState = 0, bool $installed = true, bool $setupRepositoryStatusOk = true)
    {
        $mockTerObject = new Extension();
        $mockTerObject->setVersion('1.0.6');
        $mockTerObject->setReviewState($reviewState);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        /** @var $mockListUtility ListUtility|\PHPUnit\Framework\MockObject\MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility->injectEventDispatcher($eventDispatcher);
        $mockListUtility
            ->expects(self::once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->willReturn([
                'enetcache' => [
                    'installed' => $installed,
                    'terObject' => $mockTerObject,
                ],
            ]);

        GeneralUtility::setSingletonInstance(ListUtility::class, $mockListUtility);
        $remoteRegistryProphecy = $this->prophesize(RemoteRegistry::class);
        if ($setupRepositoryStatusOk) {
            $remote = new class() extends TerExtensionRemote {
                public function __construct()
                {
                }
                public function getLastUpdate(): \DateTimeInterface
                {
                    return new \DateTimeImmutable('-4days');
                }
                protected function isDownloadedExtensionListUpToDate(): bool
                {
                    return true;
                }
            };
            $remoteRegistryProphecy->hasRemote(Argument::cetera())->willReturn(true);
            $remoteRegistryProphecy->hasDefaultRemote()->willReturn(true);
            $remoteRegistryProphecy->getDefaultRemote()->willReturn($remote);
        } else {
            $remoteRegistryProphecy->hasDefaultRemote()->willReturn(false);
        }
        return $remoteRegistryProphecy;
    }
}
