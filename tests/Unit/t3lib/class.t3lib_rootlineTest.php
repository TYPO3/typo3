<?php
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

require_once(__DIR__ . '/fixtures/AccessibleRootline.php');
/**
 * Testcase for class t3lib_rootline
 *
 * @author Steffen Ritter <steffen.ritter@typo3.org>
 *
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_rootlineTest extends tx_phpunit_testcase {

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
	 *
	 * @param array $subsetCandidate
	 * @param array $superset
	 */
	protected function assertIsSubset(array $subsetCandidate, array $superset) {
		$this->assertSame(
			$subsetCandidate,
			array_intersect_assoc($subsetCandidate, $superset)
		);
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
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);

		$this->assertFalse(
			$fixture->isMountedPage()
		);
	}

	/**
	 * @test
	 */
	public function isMountedPageWithMatchingMountPointParameterReturnsTrue() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');

		$this->assertTrue(
			$fixture->isMountedPage()
		);
	}

	/**
	 * @test
	 */
	public function isMountedPageWithNonMatchingMountPointParameterReturnsFalse() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '99-99');

		$this->assertFalse(
			$fixture->isMountedPage()
		);
	}

	/**
	 * @test
	 * @expectedException RuntimeException
	 */
	public function processMountedPageWithNonMountedPageThrowsException() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_DEFAULT)
		);
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageNotThrowsException() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1)
		);
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountedFromParameter() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$result = $fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1)
		);

		$this->assertTrue(isset($result['_MOUNTED_FROM']));
		$this->assertSame(1, $result['_MOUNTED_FROM']);
	}

	/**
	 * @test
	 */
	public function processMountedPageWithMountedPageAddsMountPointParameterToReturnValue() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$result = $fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1)
		);

		$this->assertTrue(isset($result['_MP_PARAM']));
		$this->assertSame(
			'1-99',
			$result['_MP_PARAM']
		);
	}



	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsMountOLParameter() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$result = $fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1)
		);

		$this->assertTrue(isset($result['_MOUNT_OL']));
		$this->assertSame(TRUE, $result['_MOUNT_OL']);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageIsOverlayAddsDataInformationAboutMountPage() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$result = $fixture->processMountedPage(
			array('uid' => 1),
			array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 1, 'pid' => 5, 'title' => 'TestCase')
		);

		$this->assertTrue(isset($result['_MOUNT_PAGE']));
		$this->assertSame(
			array('uid' => 99, 'pid' => 5, 'title' => 'TestCase'),
			$result['_MOUNT_PAGE']
		);
	}

	/**
	 * @test
	 */
	public function processMountedPageForMountPageWithoutOverlayReplacesMountedPageWithMountPage() {
		$a = array('uid' => 99, 'doktype' => t3lib_pageSelect::DOKTYPE_MOUNTPOINT, 'mount_pid' => 1, 'mount_pid_ol' => 0);
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1, '1-99');
		$result = $fixture->processMountedPage(
			array('uid' => 1),
			$a
		);

		$this->assertIsSubset($a, $result);
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldAsLocal() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'type' => 'group'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsGroupFieldWithMMAsRemote() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
			'type' => 'group',
			'MM' => 'tx_xyz'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldAsLocal() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'type' => 'inline'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldWithForeignKeyAsRemote() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
			'type' => 'inline',
			'foreign_key' => 'xyz'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsInlineFieldWithFMMAsRemote() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
			'type' => 'inline',
			'MM' => 'xyz'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsSelectFieldAsLocal() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertFalse($fixture->columnHasRelationToResolve(array(
			'type' => 'select'
		)));
	}

	/**
	 * @test
	 */
	public function columnHasRelationToResolveDetectsSelectFieldWithMMAsRemote() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(1);
		$this->assertTrue($fixture->columnHasRelationToResolve(array(
			'type' => 'select',
			'MM' => 'xyz'
		)));
	}

	/**
	 * @test
	 */
	public function getCacheIdentifierContainsAllConstructorParameters() {
		$fixture = new Tests_unit_t3lib_AccessibleRootline(42, '47-11', 8, 15);
		$this->assertSame(
			'42_47-11_8_15',
			$fixture->getCacheIdentifier()
		);
	}

}
