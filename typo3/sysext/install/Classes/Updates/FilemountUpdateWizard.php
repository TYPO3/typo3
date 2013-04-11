<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Tolleiv Nietsch <typo3@tolleiv.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Upgrade wizard which checks all existing filemounts
 * and upgrades this them in case we have:
 * a) absolute filemounts (base = 0) without related storage
 * b) relative filemounts (base = 1) which aren't related to a storage
 * further we assume that all other filemounts (base > 1) are already related to a storage
 *
 * @author 	  Tolleiv Nietsch <typo3@tolleiv.de>
 * @license 	 http://www.gnu.org/copyleft/gpl.html
 */
class FilemountUpdateWizard extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Migrate existing filemounts to be file abstraction layer compatible.';

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $sqlQueries = array();

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage
	 */
	protected $storage;

	/**
	 * @var \TYPO3\CMS\Core\Resource\StorageRepository
	 */
	protected $storageRepository;

	/**
	 * Creates this object
	 */
	public function __construct() {
		$this->db = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Initialize the storage repository.
	 */
	public function init() {
		$this->storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$storages = $this->storageRepository->findAll();
		$this->storage = $storages[0];
	}

	/**
	 * Checks if an update is needed.
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Migrate all filemounts to be based on file abstraction layer storages.';
		$filemountCount = $this->db->exec_SELECTcountRows('*', 'sys_filemounts', 'base IN (0,1) ' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_filemounts'));
		return $filemountCount > 0 && !$this->isWizardDone();
	}

	/**
	 * Performs the database update.
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$this->init();
		$this->migrateAbsoluteFilemounts();
		$this->migrateRelativeFilemounts();
		if (is_array($this->sqlQueries) && is_array($dbQueries)) {
			$dbQueries = array_merge($dbQueries, $this->sqlQueries);
		}
		$this->markWizardAsDone();
		return TRUE;
	}

	/**
	 * Takes the existing absolute filemounts (base=0) and migrates them to use
	 * the existing fileadmin/ storage or a new storage.
	 */
	protected function migrateAbsoluteFilemounts() {
		$description = 'This is the local %s directory. This storage mount has been created by the TYPO3 upgrade wizards.';
		$fileadminDir = PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
		$absoluteFilemounts = $this->db->exec_SELECTgetRows('*', 'sys_filemounts', 'base = 0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_filemounts'));
		foreach ($absoluteFilemounts as $filemount) {
			if (stristr($filemount['path'], $fileadminDir)) {
				$storageId = $this->storage->getUid();
				$storagePath = str_replace($fileadminDir, '', $filemount['path']);
			} else {
				$storageId = $this->storageRepository->createLocalStorage($filemount['title'] . ' (auto-created)', $filemount['path'], 'absolute', sprintf($description, $filemount['path']));
				$storagePath = '/';
				$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
			}
			$this->db->exec_UPDATEquery('sys_filemounts', 'uid=' . intval($filemount['uid']), array('base' => $storageId, 'path' => $storagePath));
			$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		}
	}

	/**
	 * Relative filemounts are transformed to relate to our fileadmin/ storage
	 * and their path is modified to be a valid resource location
	 */
	protected function migrateRelativeFilemounts() {
		$relativeFilemounts = $this->db->exec_SELECTgetRows('*', 'sys_filemounts', 'base = 1' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_filemounts'));
		foreach ($relativeFilemounts as $filemount) {
			$this->db->exec_UPDATEquery('sys_filemounts', 'uid=' . intval($filemount['uid']), array('base' => $this->storage->getUid(), 'path' => '/' . ltrim($filemount['path'], '/')));
			$this->sqlQueries[] = $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery;
		}
	}

}


?>