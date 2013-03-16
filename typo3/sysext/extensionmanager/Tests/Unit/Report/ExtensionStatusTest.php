<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Report;
use TYPO3\CMS\Extensionmanager\Report;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ExtensionStatusTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function extensionStatusImplementsStatusProviderInterface() {
		$reportMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus');
		$this->assertInstanceOf('TYPO3\\CMS\\Reports\\StatusProviderInterface', $reportMock);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsArray() {
		$report = new Report\ExtensionStatus();
		$this->assertInternalType('array', $report->getStatus());
	}

	/**
	 * @test
	 */
	public function getStatusReturnArrayContainsThreeEntries() {
		$report = new Report\ExtensionStatus();
		$this->assertSame(3, count($report->getStatus()));
	}

	/**
	 * @test
	 */
	public function getStatusReturnArrayContainsInstancesOfReportsStatusStatus() {
		$report = new Report\ExtensionStatus();
		$resultStatuses = $report->getStatus();
		foreach($resultStatuses as $status) {
			$this->assertInstanceOf('TYPO3\\CMS\\Reports\\Status', $status);
		}
	}

	/**
	 * @test
	 */
	public function getStatusCallsGetMainRepositoryStatusForMainRepositoryStatusResult() {
		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject */
		$mockReport = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('getMainRepositoryStatus'));
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
	public function getMainRepositoryStatusReturnsErrorStatusIfRepositoryIsNotFound() {
		/** @var $mockRepositoryRepository \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
		$mockRepositoryRepository = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository');
		$mockRepositoryRepository
			->expects($this->once())
			->method('findOneTypo3OrgRepository')
			->will($this->returnValue(NULL));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('repositoryRepository', $mockRepositoryRepository);

		/** @var $result \TYPO3\CMS\Reports\Status */
		$result = $mockReport->_call('getMainRepositoryStatus');
		$this->assertSame(\TYPO3\CMS\Reports\Status::ERROR, $result->getSeverity());
	}

	/**
	 * @test
	 */
	public function getMainRepositoryStatusReturnsNoticeIfRepositoryUpdateIsLongerThanSevenDaysAgo() {
		/** @var $mockRepositoryRepository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository|\PHPUnit_Framework_MockObject_MockObject */
		$mockRepository = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository');
		$mockRepository
			->expects($this->once())
			->method('getLastUpdate')
			->will($this->returnValue(new \DateTime('-8 days')));

		/** @var $mockRepositoryRepository \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
		$mockRepositoryRepository = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository');
		$mockRepositoryRepository
			->expects($this->once())
			->method('findOneTypo3OrgRepository')
			->will($this->returnValue($mockRepository));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('repositoryRepository', $mockRepositoryRepository);

		/** @var $result \TYPO3\CMS\Reports\Status */
		$result = $mockReport->_call('getMainRepositoryStatus');
		$this->assertSame(\TYPO3\CMS\Reports\Status::NOTICE, $result->getSeverity());
	}

	/**
	 * @test
	 */
	public function getMainRepositoryStatusReturnsOkIfUpdatedLessThanSevenDaysAgo() {
		/** @var $mockRepositoryRepository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository|\PHPUnit_Framework_MockObject_MockObject */
		$mockRepository = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Repository');
		$mockRepository
			->expects($this->once())
			->method('getLastUpdate')
			->will($this->returnValue(new \DateTime('-6 days')));

		/** @var $mockRepositoryRepository \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject */
		$mockRepositoryRepository = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository');
		$mockRepositoryRepository
			->expects($this->once())
			->method('findOneTypo3OrgRepository')
			->will($this->returnValue($mockRepository));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('repositoryRepository', $mockRepositoryRepository);

		/** @var $result \TYPO3\CMS\Reports\Status */
		$result = $mockReport->_call('getMainRepositoryStatus');
		$this->assertSame(\TYPO3\CMS\Reports\Status::OK, $result->getSeverity());
	}

	/**
	 * @test
	 */
	public function getSecurityStatusOfExtensionsReturnsOkForLoadedExtensionIfNoInsecureExtensionIsLoaded() {
		/** @var $mockTerObject \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|\PHPUnit_Framework_MockObject_MockObject */
		$mockTerObject = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$mockTerObject
			->expects($this->any())
			->method('getVersion')
			->will($this->returnValue('1.0.6'));
		$mockTerObject
			->expects($this->atLeastOnce())
			->method('getReviewState')
			->will($this->returnValue(0));
		$mockExtensionList = array(
			'enetcache' => array(
				'installed' => TRUE,
				'terObject' => $mockTerObject
			),
		);
		/** @var $mockListUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility|\PHPUnit_Framework_MockObject_MockObject */
		$mockListUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$mockListUtility
			->expects($this->once())
			->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
			->will($this->returnValue($mockExtensionList));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('listUtility', $mockListUtility);

		$result = $mockReport->_call('getSecurityStatusOfExtensions');
		/** @var $loadedResult \TYPO3\CMS\Reports\Status */
		$loadedResult = $result->loaded;
		$this->assertSame(\TYPO3\CMS\Reports\Status::OK, $loadedResult->getSeverity());
	}

	/**
	 * @test
	 */
	public function getSecurityStatusOfExtensionsReturnsErrorForLoadedExtensionIfInsecureExtensionIsLoaded() {
		/** @var $mockTerObject \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|\PHPUnit_Framework_MockObject_MockObject */
		$mockTerObject = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$mockTerObject
			->expects($this->any())
			->method('getVersion')
			->will($this->returnValue('1.0.6'));
		$mockTerObject
			->expects($this->atLeastOnce())
			->method('getReviewState')
			->will($this->returnValue(-1));
		$mockExtensionList = array(
			'enetcache' => array(
				'installed' => TRUE,
				'terObject' => $mockTerObject
			),
		);
		/** @var $mockListUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility|\PHPUnit_Framework_MockObject_MockObject */
		$mockListUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$mockListUtility
			->expects($this->once())
			->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
			->will($this->returnValue($mockExtensionList));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('listUtility', $mockListUtility);

		$result = $mockReport->_call('getSecurityStatusOfExtensions');
		/** @var $loadedResult \TYPO3\CMS\Reports\Status */
		$loadedResult = $result->loaded;
		$this->assertSame(\TYPO3\CMS\Reports\Status::ERROR, $loadedResult->getSeverity());
	}

	/**
	 * @test
	 */
	public function getSecurityStatusOfExtensionsReturnsOkForExistingExtensionIfNoInsecureExtensionExists() {
		/** @var $mockTerObject \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|\PHPUnit_Framework_MockObject_MockObject */
		$mockTerObject = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$mockTerObject
			->expects($this->any())
			->method('getVersion')
			->will($this->returnValue('1.0.6'));
		$mockTerObject
			->expects($this->atLeastOnce())
			->method('getReviewState')
			->will($this->returnValue(0));
		$mockExtensionList = array(
			'enetcache' => array(
				'terObject' => $mockTerObject
			),
		);
		/** @var $mockListUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility|\PHPUnit_Framework_MockObject_MockObject */
		$mockListUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$mockListUtility
			->expects($this->once())
			->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
			->will($this->returnValue($mockExtensionList));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('listUtility', $mockListUtility);

		$result = $mockReport->_call('getSecurityStatusOfExtensions');
		/** @var $existingResult \TYPO3\CMS\Reports\Status */
		$existingResult = $result->existing;
		$this->assertSame(\TYPO3\CMS\Reports\Status::OK, $existingResult->getSeverity());
	}

	/**
	 * @test
	 */
	public function getSecurityStatusOfExtensionsReturnsErrorForExistingExtensionIfInsecureExtensionExists() {
		/** @var $mockTerObject \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|\PHPUnit_Framework_MockObject_MockObject */
		$mockTerObject = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension');
		$mockTerObject
			->expects($this->any())
			->method('getVersion')
			->will($this->returnValue('1.0.6'));
		$mockTerObject
			->expects($this->atLeastOnce())
			->method('getReviewState')
			->will($this->returnValue(-1));
		$mockExtensionList = array(
			'enetcache' => array(
				'terObject' => $mockTerObject
			),
		);
		/** @var $mockListUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility|\PHPUnit_Framework_MockObject_MockObject */
		$mockListUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$mockListUtility
			->expects($this->once())
			->method('getAvailableAndInstalledExtensionsWithAdditionalInformation')
			->will($this->returnValue($mockExtensionList));

		/** @var $mockReport \TYPO3\CMS\Extensionmanager\Report\ExtensionStatus|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$mockReport = $this->getAccessibleMock('TYPO3\\CMS\\Extensionmanager\\Report\\ExtensionStatus', array('dummy'));
		$mockReport->_set('listUtility', $mockListUtility);

		$result = $mockReport->_call('getSecurityStatusOfExtensions');
		/** @var $existingResult \TYPO3\CMS\Reports\Status */
		$existingResult = $result->existing;
		$this->assertSame(\TYPO3\CMS\Reports\Status::WARNING, $existingResult->getSeverity());
	}
}
?>