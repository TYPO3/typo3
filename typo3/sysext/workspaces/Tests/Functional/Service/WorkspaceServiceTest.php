<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Workspace service test
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class WorkspacesServiceTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	protected $coreExtensionsToLoad = array('version', 'workspaces');

	/**
	 * Set up
	 */
	public function setUp() {
		parent::setUp();
		$this->setUpBackendUserFromFixture(1);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
		$this->importDataSet(__DIR__ . '/../Fixtures/sys_workspace.xml');
	}

	/**
	 * @test
	 */
	public function emptyWorkspaceReturnsEmptyArray() {
		$this->markTestSkipped("This test need a review. It is green even if all fixtures are commented out");
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(90);
		$this->assertTrue(empty($result), 'The workspace 90 contains no changes and the result was supposed to be empty');
		$this->assertTrue(is_array($result), 'Even the empty result from workspace 90 is supposed to be an array');
	}

	/**
	 * @test
	 */
	public function versionsFromSpecificWorkspaceCanBeFound() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 2);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(1, sizeof($result['pages']), 'The result is supposed to contain one version for this page in workspace 91');
		$this->assertEquals(102, $result['pages'][0]['uid'], 'Wrong workspace overlay record picked');
		$this->assertEquals(1, $result['pages'][0]['livepid'], 'Real pid wasn\'t resolved correctly');
	}

	/**
	 * @test
	 */
	public function versionsFromAllWorkspaceCanBeFound() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(\TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES, 0, -99, 2);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(2, sizeof($result['pages']), 'The result is supposed to contain one version for this page in workspace 91');
	}

	/**
	 * @test
	 */
	public function versionsCanBeFoundRecursive() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 1, 99);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(4, sizeof($result['pages']), 'The result is supposed to contain four versions for this page in workspace 91');
	}

	/**
	 * @test
	 */
	public function versionsCanBeFilteredToSpecificStage() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		// testing stage 1
		$result = $service->selectVersionsInWorkspace(91, 0, 1, 1, 99);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(2, sizeof($result['pages']), 'The result is supposed to contain two versions for this page in workspace 91');
		$this->assertEquals(102, $result['pages'][0]['uid'], 'First records is supposed to have the uid 102');
		$this->assertEquals(105, $result['pages'][1]['uid'], 'First records is supposed to have the uid 105');
		// testing stage 2
		$result = $service->selectVersionsInWorkspace(91, 0, 2, 1, 99);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(2, sizeof($result['pages']), 'The result is supposed to contain two versions for this page in workspace 91');
		$this->assertEquals(104, $result['pages'][0]['uid'], 'First records is supposed to have the uid 106');
		$this->assertEquals(106, $result['pages'][1]['uid'], 'First records is supposed to have the uid 106');
	}

	/**
	 * @test
	 */
	public function versionsCanBeFilteredToSpecificLifecycleStep() {
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		// testing all "draft" records
		$result = $service->selectVersionsInWorkspace(91, 1, -99, 1, 99);
		$this->assertTrue(is_array($result), 'The result from workspace 91 is supposed to be an array');
		$this->assertEquals(2, sizeof($result['pages']), 'The result is supposed to contain three versions for this page in workspace 91');
		// testing all "archive" records
		$result = $service->selectVersionsInWorkspace(91, 2, -99, 1, 99);
		$this->assertEquals(2, sizeof($result['pages']), 'The result is supposed to contain two versions for this page in workspace 91');
		// testing both types records
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 1, 99);
		$this->assertEquals(4, sizeof($result['pages']), 'The result is supposed to contain two versions for this page in workspace 91');
	}

	/**
	 * The only change which we could find here actually moved away from this
	 * branch of the tree - therefore we're not supposed to find anything here
	 *
	 * @test
	 */
	public function movedElementsCanNotBeFoundAtTheirOrigin() {
		$this->markTestSkipped("This test need a review. It is green even if all fixtures are commented out");
		$this->importDataSet(__DIR__ . '/Fixtures/WorkspaceServiceTestMovedContent.xml');
		// Test if the placeholder can be found when we ask using recursion (same result)
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 2, 99);
		$this->assertEquals(0, sizeof($result['pages']), 'Changes should not show up in this branch of the tree within workspace 91');
		$this->assertEquals(0, sizeof($result['tt_content']), 'Changes should not show up in this branch of the tree within workspace 91');
	}

	/**
	 * @test
	 */
	public function movedElementsCanBeFoundAtTheirDestination() {
		$this->importDataSet(__DIR__ . '/Fixtures/WorkspaceServiceTestMovedContent.xml');
		// Test if the placeholder can be found when we ask using recursion (same result)
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 5, 99);
		$this->assertEquals(1, sizeof($result['pages']), 'Wrong amount of page versions found within workspace 91');
		$this->assertEquals(103, $result['pages'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
		$this->assertEquals(5, $result['pages'][0]['wspid'], 'Wrong workspace-pointer found for page 3 in workspace 91');
		$this->assertEquals(2, $result['pages'][0]['livepid'], 'Wrong live-pointer found for page 3 in workspace 91');
		$this->assertEquals(1, sizeof($result['tt_content']), 'Wrong amount of tt_content versions found within workspace 91');
		$this->assertEquals(106, $result['tt_content'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
		$this->assertEquals(7, $result['tt_content'][0]['wspid'], 'Wrong workspace-pointer found for page 3 in workspace 91');
		$this->assertEquals(2, $result['tt_content'][0]['livepid'], 'Wrong live-pointer found for page 3 in workspace 91');
	}

	/**
	 * @test
	 */
	public function movedElementsCanBeFoundUsingTheirLiveUid() {
		$this->importDataSet(__DIR__ . '/Fixtures/WorkspaceServiceTestMovedContent.xml');
		// Test if the placeholder can be found when we ask using recursion (same result)
		$service = new \TYPO3\CMS\Workspaces\Service\WorkspaceService();
		$result = $service->selectVersionsInWorkspace(91, 0, -99, 3, 99);
		$this->assertEquals(1, sizeof($result), 'Wrong amount of versions found within workspace 91');
		$this->assertEquals(1, sizeof($result['pages']), 'Wrong amount of page versions found within workspace 91');
		$this->assertEquals(103, $result['pages'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
	}
}
