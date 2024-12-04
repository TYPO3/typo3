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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Remote\TerExtensionRemote;
use TYPO3\CMS\Extensionmanager\Report\ExtensionStatus;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Reports\Status;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ExtensionStatusTest extends UnitTestCase
{
    #[Test]
    public function getStatusReturnArrayContainsFiveEntries(): void
    {
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->onlyMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        self::assertCount(5, $report->getStatus());
    }

    #[Test]
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

    #[Test]
    public function getStatusCallsMainRepositoryForMainRepositoryStatusResult(): void
    {
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())
            ->willReturn($this->createMock(LanguageService::class));

        $listUtilityMock = $this->setUpRegistryStatusTests();
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $remoteRegistryMock->expects(self::atLeastOnce())->method('hasDefaultRemote');

        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $subject->getStatus();
    }

    #[Test]
    public function getStatusReturnsErrorStatusIfRepositoryIsNotFound(): void
    {
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())
            ->willReturn($this->createMock(LanguageService::class));

        $listUtilityMock = $this->setUpRegistryStatusTests(0, true);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock(false);
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::ERROR, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsNoticeIfRepositoryUpdateIsLongerThanSevenDaysAgo(): void
    {
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())
            ->willReturn($this->createMock(LanguageService::class));

        $remote = new class () extends TerExtensionRemote {
            public function __construct() {}

            public function getLastUpdate(): \DateTimeInterface
            {
                return new \DateTimeImmutable('-14days');
            }

            protected function isDownloadedExtensionListUpToDate(): bool
            {
                return true;
            }
        };

        $listUtilityMock = $this->setUpRegistryStatusTests();
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($remote);
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::NOTICE, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsOkForLoadedExtensionIfNoInsecureExtensionIsLoaded(): void
    {
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())
            ->willReturn($this->createMock(LanguageService::class));

        $listUtilityMock = $this->setUpRegistryStatusTests();
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['mainRepositoryStatus'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::OK, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsErrorForLoadedExtensionIfInsecureExtensionIsLoaded(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);

        $listUtilityMock = $this->setUpRegistryStatusTests(-1);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['extensionsSecurityStatusInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::ERROR, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsOkForExistingExtensionIfNoInsecureExtensionExists(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);

        $listUtilityMock = $this->setUpRegistryStatusTests(0, false);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        foreach ($status as $statusObject) {
            self::assertInstanceOf(Status::class, $statusObject);
            self::assertEquals(ContextualFeedbackSeverity::OK, $statusObject->getSeverity());
        }
    }

    #[Test]
    public function getStatusReturnsWarningForExistingExtensionIfInsecureExtensionExistsButIsNotLoaded(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);

        $listUtilityMock = $this->setUpRegistryStatusTests(-1, false);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['extensionsSecurityStatusNotInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::WARNING, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsWarningForLoadedExtensionIfOutdatedExtensionIsLoaded(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);

        $listUtilityMock = $this->setUpRegistryStatusTests(-2, true);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['extensionsOutdatedStatusInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::WARNING, $statusObject->getSeverity());
    }

    #[Test]
    public function getStatusReturnsErrorForExistingExtensionIfOutdatedExtensionExists(): void
    {
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactoryMock = $this->createMock(LanguageServiceFactory::class);
        $languageServiceFactoryMock->method('createFromUserPreferences')->with(self::anything())->willReturn($languageServiceMock);

        $listUtilityMock = $this->setUpRegistryStatusTests(-2, false);
        $remoteRegistryMock = $this->setUpRemoteRegistryMock();
        $remoteRegistryMock->method('getDefaultRemote')->willReturn($this->getDefaultTerExtensionRemote());
        $subject = new ExtensionStatus(
            $remoteRegistryMock,
            $listUtilityMock,
            $languageServiceFactoryMock
        );
        $status = $subject->getStatus();
        $statusObject = $status['extensionsOutdatedStatusNotInstalled'];
        self::assertInstanceOf(Status::class, $statusObject);
        self::assertEquals(ContextualFeedbackSeverity::WARNING, $statusObject->getSeverity());
    }

    protected function setUpRegistryStatusTests(
        int $reviewState = 0,
        bool $installed = true
    ): ListUtility&MockObject {
        $mockTerObject = new Extension();
        $mockTerObject->setVersion('1.0.6');
        $mockTerObject->setReviewState($reviewState);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
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
        return $mockListUtility;
    }

    protected function setUpRemoteRegistryMock(bool $setupRepositoryStatusOk = true): RemoteRegistry&MockObject
    {
        $remoteRegistryMock = $this->createMock(RemoteRegistry::class);
        if ($setupRepositoryStatusOk) {
            $remoteRegistryMock->method('hasRemote')->with(self::anything())->willReturn(true);
            $remoteRegistryMock->method('hasDefaultRemote')->willReturn(true);
        } else {
            $remoteRegistryMock->method('hasDefaultRemote')->willReturn(false);
        }
        return $remoteRegistryMock;
    }

    private function getDefaultTerExtensionRemote(): TerExtensionRemote
    {
        return new class () extends TerExtensionRemote {
            public function __construct() {}

            public function getLastUpdate(): \DateTimeInterface
            {
                return new \DateTimeImmutable('-4days');
            }

            protected function isDownloadedExtensionListUpToDate(): bool
            {
                return true;
            }
        };
    }
}
