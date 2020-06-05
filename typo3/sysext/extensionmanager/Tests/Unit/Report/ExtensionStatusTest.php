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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Model\Repository;
use TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository;
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
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var RepositoryRepository
     */
    protected $mockRepositoryRepository;

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
        $this->mockObjectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        /** @var $mockRepositoryRepository RepositoryRepository|\PHPUnit\Framework\MockObject\MockObject */
        $this->mockRepositoryRepository = $this->getMockBuilder(RepositoryRepository::class)
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
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
        $repositoryRepositoryProphecy = $this->setUpRepositoryStatusTests();
        $subject = new ExtensionStatus($this->languageService);
        $subject->getStatus();

        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfRepositoryIsNotFound(): void
    {
        $repositoryRepositoryProphecy = $this->setUpRepositoryStatusTests(0, true, false);
        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn(null);

        $subject = new ExtensionStatus($this->languageService);
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
        $repositoryRepositoryProphecy = $this->setUpRepositoryStatusTests(0, true, false);

        $repository = new Repository();
        $repository->setLastUpdate(new \DateTime('-14days'));
        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn($repository);

        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests();
        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests(-1);
        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests(0, false);
        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests(-1, false);
        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests(-2, true);
        $subject = new ExtensionStatus($this->languageService);
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
        $this->setUpRepositoryStatusTests(-2, false);
        $subject = new ExtensionStatus($this->languageService);
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
    protected function setUpRepositoryStatusTests(int $reviewState = 0, bool $installed = true, bool $setupRepositoryStatusOk = true)
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

        $repositoryRepositoryProphecy = $this->prophesize(RepositoryRepository::class);
        GeneralUtility::setSingletonInstance(RepositoryRepository::class, $repositoryRepositoryProphecy->reveal());
        GeneralUtility::setSingletonInstance(ListUtility::class, $mockListUtility);
        if ($setupRepositoryStatusOk) {
            $repository = new Repository();
            $repository->setLastUpdate(new \DateTime('-4days'));
            $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn($repository);
        }
        return $repositoryRepositoryProphecy;
    }
}
