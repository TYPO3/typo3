<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Report;

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

use TYPO3\CMS\Core\Localization\LanguageService;
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
    protected function setUp()
    {
        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        /** @var $mockRepositoryRepository RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
        $this->mockRepositoryRepository = $this->getMockBuilder(RepositoryRepository::class)
            ->setConstructorArgs([$this->mockObjectManager])
            ->getMock();
        $this->mockLanguageService = $this->createMock(LanguageService::class);
    }

    /**
     * @test
     */
    public function extensionStatusImplementsStatusProviderInterface()
    {
        $reportMock = $this->createMock(ExtensionStatus::class);
        $this->assertInstanceOf(StatusProviderInterface::class, $reportMock);
    }

    /**
     * @test
     */
    public function getStatusReturnsArray()
    {
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->setMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertInternalType('array', $report->getStatus());
    }

    /**
     * @test
     */
    public function getStatusReturnArrayContainsFiveEntries()
    {
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->setMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertSame(5, \count($report->getStatus()));
    }

    /**
     * @test
     */
    public function getStatusReturnArrayContainsInstancesOfReportsStatusStatus()
    {
        $statusObject = $this->getMockBuilder(Status::class)
            ->setConstructorArgs(['title', 'value'])
            ->getMock();
        /** @var ExtensionStatus $report */
        $report = $this->getMockBuilder(ExtensionStatus::class)
            ->setMethods(['getSecurityStatusOfExtensions', 'getMainRepositoryStatus'])
            ->disableOriginalConstructor()
            ->getMock();
        $report->expects($this->any())->method('getMainRepositoryStatus')->will($this->returnValue($statusObject));
        $resultStatuses = $report->getStatus();
        foreach ($resultStatuses as $status) {
            if ($status) {
                $this->assertInstanceOf(Status::class, $status);
            }
        }
    }

    /**
     * @test
     */
    public function getStatusCallsGetMainRepositoryStatusForMainRepositoryStatusResult()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(0));
        $mockExtensionList = [
            'enetcache' => [
                'installed' => true,
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['getMainRepositoryStatus'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);
        $mockReport
            ->expects($this->once())
            ->method('getMainRepositoryStatus')
            ->will($this->returnValue('foo'));

        $result = $mockReport->getStatus();
        $this->assertSame('foo', $result['mainRepositoryStatus']);
    }

    /**
     * @test
     */
    public function getMainRepositoryStatusReturnsErrorStatusIfRepositoryIsNotFound()
    {
        $this->mockRepositoryRepository
            ->expects($this->once())
            ->method('findOneTypo3OrgRepository')
            ->will($this->returnValue(null));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::ERROR)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('repositoryRepository', $this->mockRepositoryRepository);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getMainRepositoryStatus');
        $this->assertSame($statusMock, $result);
    }

    /**
     * @test
     */
    public function getMainRepositoryStatusReturnsNoticeIfRepositoryUpdateIsLongerThanSevenDaysAgo()
    {
        /** @var $mockRepositoryRepository Repository|\PHPUnit_Framework_MockObject_MockObject */
        $mockRepository = $this->getMockBuilder(Repository::class)->getMock();
        $mockRepository
            ->expects($this->once())
            ->method('getLastUpdate')
            ->will($this->returnValue(new \DateTime('-8 days')));

        $this->mockRepositoryRepository
            ->expects($this->once())
            ->method('findOneTypo3OrgRepository')
            ->will($this->returnValue($mockRepository));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::NOTICE)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('repositoryRepository', $this->mockRepositoryRepository);
        $mockReport->_set('languageService', $this->mockLanguageService);

        /** @var $result Status */
        $result = $mockReport->_call('getMainRepositoryStatus');
        $this->assertSame($statusMock, $result);
    }

    /**
     * @test
     */
    public function getMainRepositoryStatusReturnsOkIfUpdatedLessThanSevenDaysAgo()
    {
        /** @var $mockRepositoryRepository Repository|\PHPUnit_Framework_MockObject_MockObject */
        $mockRepository = $this->getMockBuilder(Repository::class)->getMock();
        $mockRepository
            ->expects($this->once())
            ->method('getLastUpdate')
            ->will($this->returnValue(new \DateTime('-6 days')));

        $this->mockRepositoryRepository
            ->expects($this->once())
            ->method('findOneTypo3OrgRepository')
            ->will($this->returnValue($mockRepository));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->once())
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::OK)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('repositoryRepository', $this->mockRepositoryRepository);
        $mockReport->_set('languageService', $this->mockLanguageService);

        /** @var $result Status */
        $result = $mockReport->_call('getMainRepositoryStatus');
        $this->assertSame($statusMock, $result);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsOkForLoadedExtensionIfNoInsecureExtensionIsLoaded()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(0));
        $mockExtensionList = [
            'enetcache' => [
                'installed' => true,
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(0))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::OK)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->loaded;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsErrorForLoadedExtensionIfInsecureExtensionIsLoaded()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(-1));
        $mockExtensionList = [
            'enetcache' => [
                'installed' => true,
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(0))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::ERROR)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->loaded;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsOkForExistingExtensionIfNoInsecureExtensionExists()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(0));
        $mockExtensionList = [
            'enetcache' => [
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(1))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::OK)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->existing;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsErrorForExistingExtensionIfInsecureExtensionExists()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(-1));
        $mockExtensionList = [
            'enetcache' => [
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(1))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::WARNING)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->existing;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsOkForLoadedExtensionIfNoOutdatedExtensionIsLoaded()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(0));
        $mockExtensionList = [
            'enetcache' => [
                'installed' => true,
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(2))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::OK)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->loadedoutdated;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsErrorForLoadedExtensionIfOutdatedExtensionIsLoaded()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(-2));
        $mockExtensionList = [
            'enetcache' => [
                'installed' => true,
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(2))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::WARNING)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->loadedoutdated;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsOkForExistingExtensionIfNoOutdatedExtensionExists()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(0));
        $mockExtensionList = [
            'enetcache' => [
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(3))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::OK)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->existingoutdated;
        $this->assertSame($statusMock, $loadedResult);
    }

    /**
     * @test
     */
    public function getSecurityStatusOfExtensionsReturnsErrorForExistingExtensionIfOutdatedExtensionExists()
    {
        /** @var $mockTerObject Extension|\PHPUnit_Framework_MockObject_MockObject */
        $mockTerObject = $this->getMockBuilder(Extension::class)->getMock();
        $mockTerObject
            ->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue('1.0.6'));
        $mockTerObject
            ->expects($this->atLeastOnce())
            ->method('getReviewState')
            ->will($this->returnValue(-2));
        $mockExtensionList = [
            'enetcache' => [
                'terObject' => $mockTerObject
            ],
        ];
        /** @var $mockListUtility ListUtility|\PHPUnit_Framework_MockObject_MockObject */
        $mockListUtility = $this->getMockBuilder(ListUtility::class)->getMock();
        $mockListUtility
            ->expects($this->once())
            ->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
            ->will($this->returnValue($mockExtensionList));

        /** @var $mockReport ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface */
        $mockReport = $this->getAccessibleMock(ExtensionStatus::class, ['dummy'], [], '', false);
        $mockReport->_set('objectManager', $this->mockObjectManager);
        $statusMock = $this->createMock(Status::class);
        $this->mockObjectManager
            ->expects($this->at(3))
            ->method('get')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), Status::WARNING)
            ->will($this->returnValue($statusMock));
        $mockReport->_set('listUtility', $mockListUtility);
        $mockReport->_set('languageService', $this->mockLanguageService);

        $result = $mockReport->_call('getSecurityStatusOfExtensions');
        /** @var $loadedResult Status */
        $loadedResult = $result->existingoutdated;
        $this->assertSame($statusMock, $loadedResult);
    }
}
