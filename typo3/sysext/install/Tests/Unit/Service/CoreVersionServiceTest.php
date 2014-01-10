<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
 */
class CoreVersionServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function updateVersionMatrixStoresVersionMatrixInRegistry() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('fetchVersionMatrixFromRemote'), array(), '', FALSE);
		$registry = $this->getMock('TYPO3\CMS\Core\Registry');
		$versionArray = array(uniqId());
		$registry->expects($this->once())->method('set')->with('TYPO3.CMS.Install', 'coreVersionMatrix', $versionArray);
		$instance->_set('registry', $registry);
		$instance->expects($this->once())->method('fetchVersionMatrixFromRemote')->will($this->returnValue($versionArray));
		$instance->updateVersionMatrix();
	}

	/**
	 * @test
	 */
	public function updateVersionMatrixRemovesOldReleasesFromMatrix() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('fetchVersionMatrixFromRemote'), array(), '', FALSE);
		$registry = $this->getMock('TYPO3\CMS\Core\Registry');
		$versionArray = array(
			'6.2' => array(),
			'6.1' => array(),
		);
		$registry
			->expects($this->once())
			->method('set')
			->with('TYPO3.CMS.Install', 'coreVersionMatrix', $this->logicalNot($this->arrayHasKey('6.1')));
		$instance->_set('registry', $registry);
		$instance->expects($this->once())->method('fetchVersionMatrixFromRemote')->will($this->returnValue($versionArray));
		$instance->updateVersionMatrix();
	}

	/**
	 * @test
	 */
	public function isInstalledVersionAReleasedVersionReturnsTrueForNonDevelopmentVersion() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('getInstalledVersion'), array(), '', FALSE);
		$instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('6.2.0'));
		$this->assertTrue($instance->isInstalledVersionAReleasedVersion());
	}

	/**
	 * @test
	 */
	public function isInstalledVersionAReleasedVersionReturnsFalseForDevelopmentVersion() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('getInstalledVersion'), array(), '', FALSE);
		$instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('6.2-dev'));
		$this->assertFalse($instance->isInstalledVersionAReleasedVersion());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
	 */
	public function getTarGzSha1OfVersionThrowsExceptionIfSha1DoesNotExistInMatrix() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion', 'ensureVersionExistsInMatrix'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(),
				),
			),
		);
		$instance->expects($this->once())->method('getMinorVersion')->will($this->returnValue('6.2'));
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$this->assertTrue($instance->getTarGzSha1OfVersion('6.2.42'));
	}

	/**
	 * @test
	 */
	public function getTarGzSha1OfVersionReturnsSha1OfSpecifiedVersion() {
		$versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion', 'ensureVersionExistsInMatrix'),
			array(),
			'',
			FALSE
		);
		$instance->expects($this->any())->method('getMinorVersion')->will($this->returnValue('6.2'));
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
		$this->assertSame('3dc156eed4b99577232f537d798a8691493f8a83', $instance->getTarGzSha1OfVersion('6.2.0alpha3'));
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isYoungerPatchReleaseAvailableReturnsTrueIfYoungerReleaseIsAvailable() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'regular',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.41'));
		$this->assertTrue($instance->isYoungerPatchReleaseAvailable());
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isYoungerReleaseAvailableReturnsFalseIfNoYoungerReleaseExists() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'regular',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.42'));
		$this->assertFalse($instance->isYoungerPatchReleaseAvailable());
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isYoungerReleaseAvailableReturnsFalseIfOnlyADevelopmentReleaseIsYounger() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'development',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.41'));
		$this->assertFalse($instance->isYoungerPatchReleaseAvailable());
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isYoungerDevelopmentReleaseAvailableReturnsTrueIfADevelopmentReleaseIsYounger() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'development',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.41'));
		$this->assertTrue($instance->isYoungerPatchDevelopmentReleaseAvailable());
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isUpdateSecurityRelevantReturnsTrueIfAnUpdateIsSecurityRelevant() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'security',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.41'));
		$this->assertTrue($instance->isUpdateSecurityRelevant());
	}

	/**
	 * Whitebox test of API method: This tests multiple methods, only 'current version' and 'version matrix' are mocked.
	 *
	 * @test
	 */
	public function isUpdateSecurityRelevantReturnsFalseIfUpdateIsNotSecurityRelevant() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getInstalledVersion'),
			array(),
			'',
			FALSE
		);
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array(
						'type' => 'regular',
						'date' => '2013-12-01 18:24:25 UTC',
					),
					'6.2.41' => array(
						'type' => 'regular',
						'date' => '2013-11-01 18:24:25 UTC',
					),
				),
			),
		);
		$instance->expects($this->any())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->expects($this->any())->method('getInstalledVersion')->will($this->returnValue('6.2.41'));
		$this->assertFalse($instance->isUpdateSecurityRelevant());
	}

	/**
	 * @test
	 */
	public function getInstalledMinorVersionFetchesInstalledVersionNumber() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('getInstalledVersion'), array(), '', FALSE);
		$instance->expects($this->once())->method('getInstalledVersion')->will($this->returnValue('6.2.0'));
		$this->assertSame('6.2', $instance->_call('getInstalledMinorVersion'));
	}

	/**
	 * Data provider
	 */
	public function getMinorVersionDataProvider() {
		return array(
			'6.2.0' => array(
				'6.2.0',
				'6.2',
			),
			'6.2-dev' => array(
				'6.2-dev',
				'6.2',
			),
			'6.2.0alpha2' => array(
				'6.2.0alpha2',
				'6.2',
			),
			'4.5.25' => array(
				'4.5.25',
				'4.5',
			),
		);
	}

	/**
	 * @test
	 * @dataProvider getMinorVersionDataProvider
	 */
	public function getMinorVersionReturnsCorrectMinorVersion($version, $expectedMajor) {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('dummy'), array(), '', FALSE);
		$this->assertSame($expectedMajor, $instance->_call('getMinorVersion', $version));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
	 */
	public function getVersionMatrixThrowsExceptionIfVersionMatrixIsNotYetSetInRegistry() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('fetchVersionMatrixFromRemote'), array(), '', FALSE);
		$registry = $this->getMock('TYPO3\CMS\Core\Registry');
		$registry->expects($this->once())->method('get')->will($this->returnValue(NULL));
		$instance->_set('registry', $registry);
		$instance->_call('getVersionMatrix');
	}

	/**
	 * @test
	 */
	public function getVersionMatrixReturnsMatrixFromRegistry() {
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock('TYPO3\\CMS\\Install\\Service\\CoreVersionService', array('fetchVersionMatrixFromRemote'), array(), '', FALSE);
		$registry = $this->getMock('TYPO3\CMS\Core\Registry');
		$versionArray = array(uniqId());
		$registry->expects($this->once())->method('get')->will($this->returnValue($versionArray));
		$instance->_set('registry', $registry);
		$this->assertSame($versionArray, $instance->_call('getVersionMatrix'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
	 */
	public function getReleaseTimestampOfVersionThrowsExceptionIfReleaseDateIsNotDefined() {
		$versionMatrix = array(
			'6.2' => array(
				'releases' => array(
					'6.2.42' => array()
				),
			),
		);
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion', 'ensureVersionExistsInMatrix'),
			array(),
			'',
			FALSE
		);
		$instance->expects($this->once())->method('getMinorVersion')->will($this->returnValue('6.2'));
		$instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue($versionMatrix));
		$instance->_call('getReleaseTimestampOfVersion', '6.2.42');
	}

	/**
	 * @test
	 */
	public function getReleaseTimestampOfVersionReturnsTimestamp() {
		$versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion', 'ensureVersionExistsInMatrix'),
			array(),
			'',
			FALSE
		);
		$instance->expects($this->once())->method('getMinorVersion')->will($this->returnValue('6.2'));
		$instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
		$this->assertSame(1380651865, $instance->_call('getReleaseTimestampOfVersion', '6.2.0alpha3'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
	 */
	public function ensureVersionExistsInMatrixThrowsExceptionIfMinorVersionDoesNotExist() {
		$versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion'),
			array(),
			'',
			FALSE
		);
		$instance->expects($this->once())->method('getMinorVersion')->will($this->returnValue('2.0'));
		$instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
		$instance->_call('ensureVersionExistsInMatrix', '2.0.42');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Install\Service\Exception\CoreVersionServiceException
	 */
	public function ensureVersionExistsInMatrixThrowsExceptionIfPatchLevelDoesNotExist() {
		$versionMatrixFixtureFile = __DIR__ . '/Fixtures/VersionMatrixFixture.php';
		/** @var $instance \TYPO3\CMS\Install\Service\CoreVersionService|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$instance = $this->getAccessibleMock(
			'TYPO3\\CMS\\Install\\Service\\CoreVersionService',
			array('getVersionMatrix', 'getMinorVersion'),
			array(),
			'',
			FALSE
		);
		$instance->expects($this->once())->method('getMinorVersion')->will($this->returnValue('6.2'));
		$instance->expects($this->once())->method('getVersionMatrix')->will($this->returnValue(require($versionMatrixFixtureFile)));
		$instance->_call('ensureVersionExistsInMatrix', '6.2.5');
	}
}
