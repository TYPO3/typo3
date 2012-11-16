<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Steffen Ritter <steffen.ritter@typo3.org>
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

require_once __DIR__ . '/Fixtures/RootlineUtilityTestAccessibleFixture.php';

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\RootlineUtility
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 */
class RootlineUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

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
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertFalse($fixture->isMountedPage());
	}

	/**
	 * @test
	 */
	public function isMountedPageWithMatchingMountPointParameterReturnsTrue() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$this->assertTrue($fixture->isMountedPage());
	}

	/**
	 * @test
	 */
	public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '99-99');
		$this->assertFalse($fixture->isMountedPage());
	}

	/**
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function processMountedPageWithNonMountedPageThrowsException() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT));
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageNotThrowsException() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1));
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountedFromParameter() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$result = $fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1));
		$this->assertTrue(isset($result['_MOUNTED_FROM']));
		$this->assertSame(1, $result['_MOUNTED_FROM']);
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$result = $fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1));
		$this->assertTrue(isset($result['_MP_PARAM']));
		$this->assertSame('1-99', $result['_MP_PARAM']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsMountOLParameter() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$result = $fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1));
		$this->assertTrue(isset($result['_MOUNT_OL']));
		$this->assertSame(TRUE, $result['_MOUNT_OL']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$result = $fixture->processMountedPage(array('uid' => 1), array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1, 'pid' => 5, 'title' => 'TestCase'));
		$this->assertTrue(isset($result['_MOUNT_PAGE']));
		$this->assertSame(array('uid' => 99, 'pid' => 5, 'title' => 'TestCase'), $result['_MOUNT_PAGE']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage() {
		$a = array('uid' => 99, 'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 0);
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1, '1-99');
		$result = $fixture->processMountedPage(array('uid' => 1), $a);
		$this->assertIsSubset($a, $result);
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldAsLocal() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'type' => 'group'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
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
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'config' => array(
				'type' => 'inline'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
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
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
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
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'config' => array(
				'type' => 'select'
			)
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote() {
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
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
		$pageContext = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$pageContext->sys_language_uid = 8;
		$pageContext->versioningWorkspaceId = 15;
		$pageContext->versioningPreview = TRUE;
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(42, '47-11', $pageContext);
		$this->assertSame('42_47-11_8_15_1', $fixture->getCacheIdentifier());
		$pageContext->versioningPreview = FALSE;
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(42, '47-11', $pageContext);
		$this->assertSame('42_47-11_8_15_0', $fixture->getCacheIdentifier());
		$pageContext->versioningWorkspaceId = 0;
		$fixture = new Fixtures\RootlineUtilityTestAccessibleFixture(42, '47-11', $pageContext);
		$this->assertSame('42_47-11_8_0_0', $fixture->getCacheIdentifier());
	}

}

?>