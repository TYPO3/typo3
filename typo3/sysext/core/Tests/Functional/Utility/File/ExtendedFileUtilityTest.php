<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility\File;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Fabien Udriot <fabien.udriot@ecodev.ch>
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
 * ************************************************************* */
/**
 * Testcase for TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
 *
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 */
class ExtendedFileUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var array A backup of registered singleton instances
	 */
	protected $singletonInstances = array();

	/**
	 * @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
	 */
	protected $fileProcessor;

	/**
	 * @var \TYPO3\CMS\Core\Resource\StorageRepository
	 */
	protected $storageRepository;

	/**
	 * @var string
	 */
	protected $newFileNameInput = '_unitTestedFile.txt';

	/**
	 * @var string
	 */
	protected $newFolderNameInput = '_unitTestedFolder';

	/**
	 * @var string
	 */
	protected $renameFileNameInput = '_unitTestedFileRenamed.txt';

	/**
	 * @var string
	 */
	protected $renameFolderNameInput = '_unitTestedFolderRenamed';

	/**
	 * @var string
	 */
	protected $copyFolderNameInput = '_unitTestedFolderCopied';

	/**
	 * @var string
	 */
	protected $moveFolderNameInput = '_unitTestedFolderMoved';

	/**
	 * @var array
	 */
	protected $objectsToTearDown = array();

	/**
	 * Sets up this testcase
	 */
	public function setUp() {
		$this->storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		// Initializing file processor
		$GLOBALS['BE_USER'] = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication', array('getSessionData', 'setAndSaveSessionData'));
		$GLOBALS['BE_USER']->user['uid'] = 1;
		$GLOBALS['FILEMOUNTS'] = array();
		// Initializing:
		$this->fileProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		$this->fileProcessor->dontCheckForUnique = 1;
		$this->singletonInstances = \TYPO3\CMS\Core\Utility\GeneralUtility::getSingletonInstances();
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
	}

	/**
	 * Tears down this testcase
	 */
	public function tearDown() {
		foreach ($this->objectsToTearDown as $object) {
			if ($object instanceof \TYPO3\CMS\Core\Resource\File || $object instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$object->delete();
			}
		}
		$this->objectsToTearDown = array();
		\TYPO3\CMS\Core\Utility\GeneralUtility::resetSingletonInstances($this->singletonInstances);
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected function getDefaultStorage() {
		// Get the first storage available.
		// Notice if no storage is found, a storage is created on the fly.
		$storages = $this->storageRepository->findAll();
		// Makes sure to return a storage having a local driver
		foreach ($storages as $storage) {

		}
		/** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
		return $storages[0];
	}

	/**
	 * @return string
	 */
	protected function getRootFolderIdentifier() {
		$storage = $this->getDefaultStorage();
		$folderIdentifier = '/';
		// the root of the storage
		$folderCombinedIdentifier = $storage->getUid() . ':' . $folderIdentifier;
		return $folderCombinedIdentifier;
	}

	/*********************************
	 * CREATE
	 ********************************/
	/**
	 * @test
	 */
	public function createNewFileInLocalStorage() {
		// Defines values
		$fileValues = array(
			'newfile' => array(
				array(
					'data' => $this->newFileNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$fileObject = NULL;
		if (!empty($results['newfile'][0])) {
			$fileObject = $results['newfile'][0];
		}
		$this->objectsToTearDown[] = $fileObject;
		$this->assertEquals(TRUE, $fileObject instanceof \TYPO3\CMS\Core\Resource\File);
	}

	/**
	 * @test
	 */
	public function createNewFolderInLocalStorage() {
		// Defines values
		$fileValues = array(
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$folderObject = NULL;
		if (!empty($results['newfolder'][0])) {
			$folderObject = $results['newfolder'][0];
		}
		$this->objectsToTearDown[] = $folderObject;
		$this->assertEquals(TRUE, $folderObject instanceof \TYPO3\CMS\Core\Resource\Folder);
	}

	/*********************************
	 * DELETE
	 ********************************/
	/**
	 * @test
	 */
	public function deleteFileInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$fileIdentifier = $storage->getUid() . ':/' . $this->newFileNameInput;
		// Defines values
		$fileValues = array(
			'newfile' => array(
				array(
					'data' => $this->newFileNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'delete' => array(
				array(
					'data' => $fileIdentifier
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$this->assertEquals(TRUE, empty($results['delete'][1]));
	}

	/**
	 * @test
	 */
	public function deleteFolderInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$folderIdentifier = $storage->getUid() . ':/' . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'delete' => array(
				array(
					'data' => $folderIdentifier
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$this->assertEquals(TRUE, $results['delete'][0]);
	}

	/*********************************
	 * RENAME
	 ********************************/
	/**
	 * @test
	 */
	public function renameFileInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$fileIdentifier = $storage->getUid() . ':/' . $this->newFileNameInput;
		// Defines values
		$fileValues = array(
			'newfile' => array(
				array(
					'data' => $this->newFileNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'rename' => array(
				array(
					'data' => $fileIdentifier,
					'target' => $this->renameFileNameInput
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$fileObject = NULL;
		if (!empty($results['rename'][0])) {
			$fileObject = $results['rename'][0];
		}
		$this->objectsToTearDown[] = $fileObject;
		$this->assertEquals(TRUE, $fileObject instanceof \TYPO3\CMS\Core\Resource\File);
	}

	/**
	 * @test
	 */
	public function renameFolderInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$folderIdentifier = $storage->getUid() . ':/' . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'rename' => array(
				array(
					'data' => $folderIdentifier,
					'target' => $this->renameFolderNameInput
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$folderObject = NULL;
		if (!empty($results['rename'][0])) {
			$folderObject = $results['rename'][0];
		}
		$this->objectsToTearDown[] = $folderObject;
		$this->assertEquals(TRUE, $folderObject instanceof \TYPO3\CMS\Core\Resource\Folder);
	}

	/*********************************
	 * MOVE
	 ********************************/
	/**
	 * @test
	 */
	public function moveFileInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$fileIdentifier = $storage->getUid() . ':/' . $this->newFileNameInput;
		$targetFolder = $this->getRootFolderIdentifier() . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfile' => array(
				array(
					'data' => $this->newFileNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'move' => array(
				array(
					'data' => $fileIdentifier,
					'target' => $targetFolder
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$fileObject = NULL;
		if (!empty($results['move'][0])) {
			$fileObject = $results['move'][0];
		}
		// remove parent folder
		if (!empty($results['newfolder'][0])) {
			$this->objectsToTearDown[] = $results['newfolder'][0];
		}
		$this->assertEquals(TRUE, $fileObject instanceof \TYPO3\CMS\Core\Resource\File);
	}

	/**
	 * @test
	 */
	public function moveFolderInLocalStorage() {
		// Computes a $folderIdentifier which looks like 8:/folderName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$folderIdentifier = $storage->getUid() . ':/' . $this->moveFolderNameInput;
		$targetFolder = $this->getRootFolderIdentifier() . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				),
				array(
					'data' => $this->moveFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'move' => array(
				array(
					'data' => $folderIdentifier,
					'target' => $targetFolder
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$folderObject = NULL;
		if (!empty($results['move'][0])) {
			$folderObject = $results['move'][0];
		}
		// remove parent folder
		if (!empty($results['newfolder'][0])) {
			$this->objectsToTearDown[] = $results['newfolder'][0];
		}
		$this->assertEquals(TRUE, $folderObject instanceof \TYPO3\CMS\Core\Resource\Folder);
	}

	/*********************************
	 * COPY
	 ********************************/
	/**
	 * @test
	 */
	public function copyFileInLocalStorage() {
		// Computes a $fileIdentifier which looks like 8:/fileName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$fileIdentifier = $storage->getUid() . ':/' . $this->newFileNameInput;
		$targetFolder = $this->getRootFolderIdentifier() . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfile' => array(
				array(
					'data' => $this->newFileNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'copy' => array(
				array(
					'data' => $fileIdentifier,
					'target' => $targetFolder
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$fileObject = NULL;
		if (!empty($results['copy'][0])) {
			$fileObject = $results['copy'][0];
		}
		// remove parent folder
		if (!empty($results['newfolder'][0])) {
			$this->objectsToTearDown[] = $results['newfolder'][0];
		}
		if (!empty($results['newfile'][0])) {
			$this->objectsToTearDown[] = $results['newfile'][0];
		}
		$this->assertEquals(TRUE, $fileObject instanceof \TYPO3\CMS\Core\Resource\File);
	}

	/**
	 * @test
	 */
	public function copyFolderInLocalStorage() {
		// Computes a $folderIdentifier which looks like 8:/folderName.txt where 8 is the storage Uid
		$storage = $this->getDefaultStorage();
		$folderIdentifier = $storage->getUid() . ':/' . $this->copyFolderNameInput;
		$targetFolder = $this->getRootFolderIdentifier() . $this->newFolderNameInput;
		// Defines values
		$fileValues = array(
			'newfolder' => array(
				array(
					'data' => $this->newFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				),
				array(
					'data' => $this->copyFolderNameInput,
					'target' => $this->getRootFolderIdentifier()
				)
			),
			'copy' => array(
				array(
					'data' => $folderIdentifier,
					'target' => $targetFolder
				)
			)
		);
		$this->fileProcessor->start($fileValues);
		$results = $this->fileProcessor->processData();
		$folderObject = NULL;
		if (!empty($results['copy'][0])) {
			$folderObject = $results['copy'][0];
		}
		// remove parent folder
		if (!empty($results['newfolder'][0])) {
			$this->objectsToTearDown[] = $results['newfolder'][0];
		}
		if (!empty($results['newfolder'][1])) {
			$this->objectsToTearDown[] = $results['newfolder'][1];
		}
		$this->assertEquals(TRUE, $folderObject instanceof \TYPO3\CMS\Core\Resource\Folder);
	}

}

?>