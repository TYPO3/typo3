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
    protected $mockLanguageService;

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
        $this->mockLanguageService = $this->createMock(LanguageService::class);
        $this->resetSingletonInstances = true;
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
        [$repositoryRepositoryProphecy] = $this->setUpRepositoryStatusTests();
        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorStatusIfRepositoryIsNotFound(): void
    {
        [$repositoryRepositoryProphecy, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(0, true, false);

        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn(null);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::ERROR)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsNoticeIfRepositoryUpdateIsLongerThanSevenDaysAgo(): void
    {
        [$repositoryRepositoryProphecy, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(0, true, false);

        $repository = new Repository();
        $repository->setLastUpdate(new \DateTime('-14days'));
        $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn($repository);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::NOTICE)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsOkIfUpdatedLessThanSevenDaysAgo(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests();

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::OK)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsOkForLoadedExtensionIfNoInsecureExtensionIsLoaded(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests();

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::OK)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorForLoadedExtensionIfInsecureExtensionIsLoaded(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(-1);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::ERROR)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsOkForExistingExtensionIfNoInsecureExtensionExists(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(0, false);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::OK)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsWarningForExistingExtensionIfInsecureExtensionExistsButIsNotLoaded(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(-1, false);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::WARNING)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsWarningForLoadedExtensionIfOutdatedExtensionIsLoaded(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(-2, true);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::WARNING)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getStatusReturnsErrorForExistingExtensionIfOutdatedExtensionExists(): void
    {
        [, $objectManagerProphecy] = $this->setUpRepositoryStatusTests(-2, false);

        $extensionStatus = new ExtensionStatus();
        $extensionStatus->getStatus();

        $objectManagerProphecy->get(Status::class, Argument::any(), Argument::any(), Argument::any(), Status::WARNING)->shouldHaveBeenCalled();
    }

    /**
     * @param int $reviewState
     * @param bool $installed
     * @param bool $setupRepositoryStatusOk
     * @return array
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    protected function setUpRepositoryStatusTests(int $reviewState = 0, bool $installed = true, bool $setupRepositoryStatusOk = true): array
    {
        $mockTerObject = new Extension();
        $mockTerObject->setVersion('1.0.6');
        $mockTerObject->setReviewState($reviewState);

        $mockExtensionList = [
            'enetcache' => [
                'installed' => $installed,
                'terObject' => $mockTerObject,
            ],
        ];
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        /** @var $mockListUtility ListUtility|\PHPUnit\Framework\MockObject\MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility->injectEventDispatcher($eventDispatcher);
        $mockListUtility
            ->expects(self::once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->willReturn($mockExtensionList);

        $repositoryRepositoryProphecy = $this->prophesize(RepositoryRepository::class);
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->get(RepositoryRepository::class)->willReturn($repositoryRepositoryProphecy->reveal());
        $objectManagerProphecy->get(ListUtility::class)->willReturn($mockListUtility);
        $objectManagerProphecy->get(LanguageService::class)->willReturn($this->mockLanguageService);
        $objectManagerProphecy->get(Status::class, Argument::cetera())->willReturn(new Status('test status', 'test status value'));
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());
        if ($setupRepositoryStatusOk) {
            $repository = new Repository();
            $repository->setLastUpdate(new \DateTime('-4days'));
            $repositoryRepositoryProphecy->findOneTypo3OrgRepository()->willReturn($repository);
        }
        return [$repositoryRepositoryProphecy, $objectManagerProphecy];
    }
}
