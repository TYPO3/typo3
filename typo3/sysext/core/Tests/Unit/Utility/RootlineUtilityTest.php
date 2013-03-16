<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 * Testcase for class \TYPO3\CMS\Core\Utility\RootlineUtility
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 */
class RootlineUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Utility\RootlineUtility|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $pageContextMock;

	public function setUp() {
		$this->pageContextMock = $this->getMock('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$this->fixture = $this->getAccessibleMock('\TYPO3\CMS\Core\Utility\RootlineUtility', array('enrichWithRelationFields'), array(1, '', $this->pageContextMock));
	}


	/***
	 *
	 * 		UTILITY FUNCTIONS
	 *
	 */
	/**
	 * Tests that $subsetCandidate is completely part of $superset
	 * and keys match.
	 *
	 * @see (A ^ B) = A <=> A c B
	 * @param array $subsetCandidate
	 * @param array $superset
	 */
	protected function assertIsSubset(array $subsetCandidate, array $superset) {
		$this->assertSame($subsetCandidate, array_intersect_assoc($subsetCandidate, $superset));
	}

	/***
	 *
	 * 		>TEST CASES
	 *
	 */
	/**
	 * @test
	 */
	public function isMountedPageWithoutMountPointsReturnsFalse() {
		$this->fixture->__construct(1);
		$this->assertFalse($this->fixture->isMountedPage());
	}

	/**
	 * @test
	 */
	public function isMountedPageWithMatchingMountPointParameterReturnsTrue() {
		$this->fixture->__construct(1, '1-99');
		$this->assertTrue($this->fixture->isMountedPage());
	}

	/**
	 * @test
	 */
	public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse() {
		$this->fixture->__construct(1, '99-99');
		$this->assertFalse($this->fixture->isMountedPage());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function processMountedPageWithNonMountedPageThrowsException() {
		$this->fixture->__construct(1, '1-99');
		$this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT));
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageNotThrowsException() {
		$this->fixture->__construct(1, '1-99');
		$this->assertNotEmpty($this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1)));
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountedFromParameter() {
		$this->fixture->__construct(1, '1-99');
		$result = $this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1));
		$this->assertTrue(isset($result['_MOUNTED_FROM']));
		$this->assertSame(1, $result['_MOUNTED_FROM']);
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue() {
		$this->fixture->__construct(1, '1-99');
		$result = $this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1));
		$this->assertTrue(isset($result['_MP_PARAM']));
		$this->assertSame('1-99', $result['_MP_PARAM']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsMountOLParameter() {
		$this->fixture->__construct(1, '1-99');
		$result = $this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1));
		$this->assertTrue(isset($result['_MOUNT_OL']));
		$this->assertSame(TRUE, $result['_MOUNT_OL']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage() {
		$this->fixture->__construct(1, '1-99');
		$result = $this->fixture->_call('processMountedPage', array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1, 'pid' => 5, 'title' => 'TestCase'));
		$this->assertTrue(isset($result['_MOUNT_PAGE']));
		$this->assertSame(array('uid' => 99, 'pid' => 5, 'title' => 'TestCase'), $result['_MOUNT_PAGE']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage() {
		$mountPointPageData = array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 0);
		$this->fixture->__construct(1, '1-99');
		$result = $this->fixture->_call('processMountedPage', array('uid' => 1), $mountPointPageData);
		$this->assertIsSubset($mountPointPageData, $result);
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldAsLocal() {
		$this->assertFalse($this->fixture->_call('columnHasRelationToResolve', array(
			'type' => 'group'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote2() {
		$this->assertTrue($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'group',
				'MM' => 'tx_xyz'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldAsLocal() {
		$this->assertFalse($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'inline'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote() {
		$this->assertTrue($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'inline',
				'foreign_field' => 'xyz'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldWithFMMAsRemote() {
		$this->assertTrue($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'inline',
				'MM' => 'xyz'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsSelectFieldAsLocal() {
		$this->assertFalse($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'select'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote() {
		$this->assertTrue($this->fixture->_call('columnHasRelationToResolve', array(
			'config' => array(
				'type' => 'select',
				'MM' => 'xyz'
			)
		)));
	}

	/**
	 * @test
	 */
	public function getCacheIdentifierContainsAllContextParameters() {
		$this->pageContextMock->sys_language_uid = 8;
		$this->pageContextMock->versioningWorkspaceId = 15;
		$this->pageContextMock->versioningPreview = TRUE;
		$this->fixture->__construct(42, '47-11', $this->pageContextMock);
		$this->assertSame('42_47-11_8_15_1', $this->fixture->getCacheIdentifier());
		$this->pageContextMock->versioningPreview = FALSE;
		$this->fixture->__construct(42, '47-11', $this->pageContextMock);
		$this->assertSame('42_47-11_8_15_0', $this->fixture->getCacheIdentifier());
		$this->pageContextMock->versioningWorkspaceId = 0;
		$this->fixture->__construct(42, '47-11', $this->pageContextMock);
		$this->assertSame('42_47-11_8_0_0', $this->fixture->getCacheIdentifier());
	}

	/**
	 * @test
	 */
	public function getRecordArrayFetchesTranslationWhenLanguageIdIsSet() {
		$pageData = array(
			'uid' => 1,
			'title' => 'Original',
		);
		$pageDataTranslated = array(
			'uid' => 1,
			'title' => 'Translated',
			'_PAGES_OVERLAY_UID' => '2',
		);

		$this->fixture
			->expects($this->any())
			->method('enrichWithRelationFields')
			->with(2, $pageDataTranslated)
			->will($this->returnArgument(1));

		$databaseConnectionMock = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$databaseConnectionMock
			->expects($this->once())
			->method('exec_SELECTgetSingleRow')
			->will(
				$this->returnValue($pageData)
			);
		$this->fixture->_set('databaseConnection',
			$databaseConnectionMock
		);

		$this->pageContextMock
				->expects($this->any())
				->method('getPageOverlay')
				->will($this->returnValue($pageDataTranslated));

		$this->fixture->_set('languageUid', 1);
		$this->assertSame($pageDataTranslated, $this->fixture->_call('getRecordArray', 1));
	}
}

?>