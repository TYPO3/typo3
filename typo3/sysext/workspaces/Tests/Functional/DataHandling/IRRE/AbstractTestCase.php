<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\IRRE;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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

require_once(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/DataHandling/IRRE/AbstractTestCase.php');

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic test helpers.
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE\AbstractTestCase {
	const VALUE_TimeStamp = 1250000000;
	const VALUE_Pid = 1;
	const VALUE_PidAlternative = 7;
	const VALUE_WorkspaceId = 90;
	const VALUE_WorkspaceIdIgnore = -1;

	const COMMAND_Version = 'version';
	const COMMAND_Version_New = 'new';
	const COMMAND_Version_Swap = 'swap';
	const COMMAND_Version_Flush = 'flush';
	const COMMAND_Version_Clear = 'clearWSID';

	protected $coreExtensionsToLoad = array(
		'fluid',
		'version',
		'workspaces'
	);

	/**
	 * @var integer
	 */
	private $modifiedTimeStamp;

	/**
	 * @var \TYPO3\CMS\Version\Hook\DataHandlerHook|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $versionTceMainHookMock;

	/**
	 * @var \TYPO3\CMS\Version\DataHandler\CommandMap
	 */
	protected $versionTceMainCommandMap;

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->importDataSet(__DIR__ . '/../../Fixtures/sys_workspace.xml');

		$this->getBackendUser()->workspace = self::VALUE_WorkspaceId;
		$this->setWorkspacesConsiderReferences(FALSE);
		$this->setWorkspaceChangeStageMode('');
		$this->setWorkspaceSwapMode('');
	}


	/**
	 * Gets a modified timestamp to ensure that a record is changed.
	 *
	 * @return integer
	 */
	protected function getModifiedTimeStamp() {
		if (!isset($this->modifiedTimeStamp)) {
			$this->modifiedTimeStamp = self::VALUE_TimeStamp + 100;
		}

		return $this->modifiedTimeStamp;
	}

	/**
	 * Gets an element structure of tables and ids used to simulate editing with TCEmain.
	 *
	 * @param array $tables Table names with list of ids to be edited
	 * @return array
	 */
	protected function getElementStructureForEditing(array $tables) {
		$editStructure = array();

		foreach ($tables as $tableName => $idList) {
			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$editStructure[$tableName][$id] = array(
					'tstamp' => $this->getModifiedTimeStamp(),
				);
			}
		}

		return $editStructure;
	}

	/**
	 * @param  array $tables Table names with list of ids to be edited
	 * @return DataHandler
	 */
	protected function simulateEditing(array $tables) {
		return $this->simulateEditingByStructure($this->getElementStructureForEditing($tables));
	}

	/**
	 * Simulates editing by using DataHandler.
	 *
	 * @param  array $elements The datamap to be delivered to DataHandler
	 * @return DataHandler
	 */
	protected function simulateEditingByStructure(array $elements) {
		$tceMain = $this->getTceMain();
		$tceMain->start($elements, array());
		$tceMain->process_datamap();

		return $tceMain;
	}

	/**
	 * @param array $commands
	 * @param array $tables
	 * @return DataHandler
	 */
	protected function simulateVersionCommand(array $commands, array $tables) {
		return $this->simulateCommand(
			self::COMMAND_Version,
			$commands,
			$tables
		);
	}

	/**
	 * Simulates editing and command by structure.
	 *
	 * @param array $editingElements
	 * @param array $commandElements
	 * @return DataHandler
	 */
	protected function simulateByStructure(array $editingElements, array $commandElements) {
		$tceMain = $this->getTceMain();
		$tceMain->start($editingElements, $commandElements);
		$tceMain->process_datamap();
		$tceMain->process_cmdmap();

		return $tceMain;
	}

	/**
	 * Asserts that accordant workspace version exist for live versions.
	 *
	 * @param array $tables Table names with list of ids to be edited
	 * @param integer $workspaceId Workspace to be used
	 * @param boolean $expected The expected value
	 * @return void
	 */
	protected function assertWorkspaceVersions(array $tables, $workspaceId = self::VALUE_WorkspaceId, $expected = TRUE) {
		foreach ($tables as $tableName => $idList) {
			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($workspaceId, $tableName, $id);
				$this->assertTrue(
					($expected ? $workspaceVersion !== FALSE : $workspaceVersion === FALSE),
					'Workspace version for ' . $tableName . ':' . $id . ($expected ? ' not' : '') . ' available'
				);
			}
		}
	}

	/**
	 * Gets a \TYPO3\CMS\Version\Hook\DataHandlerHook mock.
	 *
	 * @param integer $expectsGetCommandMap (optional) Expects number of invocations to getCommandMap method
	 * @return \TYPO3\CMS\Version\Hook\DataHandlerHook
	 */
	protected function getVersionTceMainHookMock($expectsGetCommandMap = NULL) {
		$this->versionTceMainHookMock = $this->getMock('TYPO3\\CMS\\Version\\Hook\\DataHandlerHook', array('getCommandMap'));

		if (is_integer($expectsGetCommandMap) && $expectsGetCommandMap >= 0) {
			$this->versionTceMainHookMock->expects($this->exactly($expectsGetCommandMap))->method('getCommandMap')
				->will($this->returnCallback(array($this, 'getVersionTceMainCommandMapCallback')));
		} elseif (!is_null($expectsGetCommandMap)) {
			$this->fail('Expected invocation of getCommandMap must be integer >= 0.');
		}

		return $this->versionTceMainHookMock;
	}

	/**
	 * Gets access to the command map.
	 *
	 * @param integer $expectsGetCommandMap Expects number of invocations to getCommandMap method
	 * @return void
	 */
	protected function getCommandMapAccess($expectsGetCommandMap) {
		$hookReferenceString = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['version'];
		$GLOBALS['T3_VAR']['getUserObj'][$hookReferenceString] = $this->getVersionTceMainHookMock($expectsGetCommandMap);
	}

	/**
	 * @param string $tableName
	 * @param integer $id
	 * @param integer $workspaceId
	 * @param boolean $directLookup
	 * @return boolean
	 */
	protected function getWorkspaceVersionId($tableName, $id, $workspaceId = self::VALUE_WorkspaceId, $directLookup = FALSE) {
		if ($directLookup) {
			$records = $this->getAllRecords($tableName);
			foreach ($records as $record) {
				if (($workspaceId === self::VALUE_WorkspaceIdIgnore || $record['t3ver_wsid'] == $workspaceId) && $record['t3ver_oid'] == $id) {
					return $record['uid'];
				}
			}
		} else {
			$workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($workspaceId, $tableName, $id);
			if ($workspaceVersion !== FALSE) {
				return $workspaceVersion['uid'];
			}
		}

		return FALSE;
	}

	/**
	 * Asserts the existence of a delete placeholder record.
	 *
	 * @param array $tables
	 * @return void
	 */
	protected function assertHasDeletePlaceholder(array $tables) {
		foreach ($tables as $tableName => $idList) {
			$records = $this->getAllRecords($tableName);

			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$failureMessage = 'Delete placeholder of "' . $tableName . ':' . $id . '"';
				$versionizedId = $this->getWorkspaceVersionId($tableName, $id);
				$this->assertTrue(isset($records[$versionizedId]), $failureMessage . ' does not exist');
				$this->assertEquals($id, $records[$versionizedId]['t3_origuid'], $failureMessage . ' has wrong relation to live workspace');
				$this->assertEquals($id, $records[$versionizedId]['t3ver_oid'], $failureMessage . ' has wrong relation to live workspace');
				$this->assertEquals(2, $records[$versionizedId]['t3ver_state'], $failureMessage . ' is not marked as DELETED');
				$this->assertEquals('DELETED!', $records[$versionizedId]['t3ver_label'], $failureMessage . ' is not marked as DELETED');
			}
		}
	}

	/**
	 * @param array $tables
	 * @return void
	 */
	protected function assertIsDeleted(array $tables) {
		foreach ($tables as $tableName => $idList) {
			$records = $this->getAllRecords($tableName);

			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$failureMessage = 'Workspace version "' . $tableName . ':' . $id . '"';
				$this->assertTrue(isset($records[$id]), $failureMessage . ' does not exist');
				$this->assertEquals(0, $records[$id]['t3ver_state']);
				$this->assertEquals(1, $records[$id]['deleted']);
			}
		}
	}

	/**
	 * @param array $tables
	 * @return void
	 */
	protected function assertIsCleared(array $tables) {
		foreach ($tables as $tableName => $idList) {
			$records = $this->getAllRecords($tableName);

			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$failureMessage = 'Workspace version "' . $tableName . ':' . $id . '"';
				$this->assertTrue(isset($records[$id]), $failureMessage . ' does not exist');
				$this->assertEquals(0, $records[$id]['t3ver_state'], $failureMessage . ' has wrong state value');
				$this->assertEquals(0, $records[$id]['t3ver_wsid'], $failureMessage . ' is still in offline workspace');
				$this->assertEquals(-1, $records[$id]['pid'], $failureMessage . ' has wrong pid value');
			}
		}
	}

	/**
	 * @param array $assertions
	 * @param integer $workspaceId
	 */
	protected function assertRecords(array $assertions, $workspaceId = NULL) {
		foreach ($assertions as $table => $elements) {
			$records = $this->getAllRecords($table);

			foreach ($elements as $uid => $data) {
				$intersection = array_intersect_assoc($data, $records[$uid]);
				$differences = array_intersect_key($records[$uid], array_diff_assoc($data, $records[$uid]));

				$this->assertTrue(
					count($data) === count($intersection),
					'Expected ' . $this->elementToString($data) . ' got differences in ' . $this->elementToString($differences) . ' for table ' . $table
				);

				if (is_integer($workspaceId)) {
					$workspaceVersionId = $this->getWorkspaceVersionId($table, $uid, $workspaceId, TRUE);
					$intersection = array_intersect_assoc($data, $records[$workspaceVersionId]);
					$differences = array_intersect_key($records[$workspaceVersionId], array_diff_assoc($data, $records[$workspaceVersionId]));

					$this->assertTrue(
						count($data) === count($intersection),
						'Expected ' . $this->elementToString($data) . ' got differences in ' . $this->elementToString($differences) . ' for table ' . $table
					);
				}
			}
		}
	}

	/**
	 * Sets the User TSconfig property options.workspaces.considerReferences.
	 *
	 * @param boolean $workspacesConsiderReferences
	 * @return void
	 */
	protected function setWorkspacesConsiderReferences($workspacesConsiderReferences = TRUE) {
		$this->getBackendUser()->userTS['options.']['workspaces.']['considerReferences'] = ($workspacesConsiderReferences ? 1 : 0);
	}

	/**
	 * Sets the User TSconfig property options.workspaces.swapMode.
	 *
	 * @param string $workspaceSwapMode
	 * @return void
	 */
	protected function setWorkspaceSwapMode($workspaceSwapMode = 'any') {
		$this->getBackendUser()->userTS['options.']['workspaces.']['swapMode'] = $workspaceSwapMode;
	}

	/**
	 * Sets the User TSconfig property options.workspaces.changeStageMode.
	 *
	 * @param string $workspaceChangeStateMode
	 * @return void
	 */
	protected function setWorkspaceChangeStageMode($workspaceChangeStateMode = 'any') {
		$this->getBackendUser()->userTS['options.']['workspaces.']['changeStageMode'] = $workspaceChangeStateMode;
	}

	public function getVersionTceMainCommandMapCallback(DataHandler $tceMain, array $commandMap) {
		$this->versionTceMainCommandMap = GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\DataHandler\\CommandMap', $this->versionTceMainHookMock, $tceMain, $commandMap, self::VALUE_WorkspaceId);
		return $this->versionTceMainCommandMap;
	}

	/**
	 * @return \TYPO3\CMS\Version\DataHandler\CommandMap
	 */
	protected function getCommandMap() {
		return $this->versionTceMainCommandMap;
	}
}
