<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Marcus Krause <marcus#exp2010@t3sec.info>
 *		   Steffen Kamper <info@sk-typo3.de>
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
 * class.tx_em_repository_utility.php
 *
 * Module: Extension manager - Central repository utility functions
 *
 * @author  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author  Steffen Kamper <info@sk-typo3.de>
 */


/**
 * Central utility class for repository handling.
 *
 * @author	  Marcus Krause <marcus#exp2010@t3sec.info>
 * @author	  Steffen Kamper <info@sk-typo3.de>
 *
 * @since	   2010-02-18
 * @package	 TYPO3
 * @subpackage  EM
 */
class tx_em_Repository_Utility implements t3lib_Singleton {


	/**
	 * ##########################################
	 * Problem constants - to be used in bitmasks
	 * ##########################################
	 */
	/**
	 * Type of problem: extension file not existing in file system.
	 *
	 * @var  integer
	 */
	const PROBLEM_EXTENSION_FILE_NOT_EXISTING = 1;

	/**
	 * Type of problem: wrong hash indicates outdated extension file.
	 *
	 * @var  integer
	 */
	const PROBLEM_EXTENSION_HASH_CHANGED = 2;

	/**
	 * Type of problem: no version records in database.
	 *
	 * @var  integer
	 */
	const PROBLEM_NO_VERSIONS_IN_DATABASE = 4;


	/**
	 * Keeps instance of repository class.
	 *
	 * @var em_repository
	 */
	protected $repository = NULL;


	/**
	 * Class constructor.
	 *
	 * @access  public
	 * @param   object  &$repository  (optional) instance of {@link em_repository repository} class
	 * @return  void
	 */
	function __construct(&$repository = NULL) {
		if ($repository !== NULL && is_object($repository)
				&& $repository instanceof tx_em_Repository) {
			$this->setRepository($repository);
		}
	}

	/**
	 * Method provides a wrapper for throwing an exception.
	 *
	 * @access  protected
	 * @see	 tx_em_ConnectionException
	 * @param   string	 $message  the exception message to throw.
	 * @param   integer	$code  the exception code.
	 * @return  void
	 */
	protected function throwConnectionException($message = "", $code = 0) {
		throw new tx_em_ConnectionException(get_class($this) . ': ' . $message, $code);
	}

	/**
	 * Method registers required repository instance to work with.
	 *
	 * Repository instance is passed by reference.
	 *
	 * @access  public
	 * @param   em_repository  &$repository  instance of {@link em_repository repository} class
	 * @return  void
	 * @see	 $repository
	 */
	public function setRepository(tx_em_Repository &$repository) {
		$this->repository = $repository;
	}

	/**
	 * Method fetches extension list file from remote server.
	 *
	 * Delegates to {@link fetchFile()}.
	 *
	 * @access  public
	 * @return  void
	 * @see	 fetchFile()
	 */
	public function fetchExtListFile() {
		$this->fetchFile($this->getRemoteExtListFile(), $this->getLocalExtListFile());
	}

	/**
	 * Method fetches mirror list file from remote server.
	 *
	 * Delegates to {@link fetchFile()}.
	 *
	 * @access  public
	 * @return  void
	 * @see	 fetchFile()
	 */
	public function fetchMirrorListFile() {
		$this->fetchFile($this->getRemoteMirrorListFile(), $this->getLocalMirrorListFile());
	}

	/**
	 * Method fetches contents from remote server and
	 * writes them into a file in the local file system.
	 *
	 * @access  protected
	 * @param   string  $remoteRessource  remote ressource to read contents from
	 * @param   string  $localRessource   local ressource (absolute file path) to store retrieved contents to
	 * @return  void
	 * @see	 t3lib_div::getURL(), t3lib_div::writeFile()
	 * @throws  tx_em_ConnectionException
	 */
	protected function fetchFile($remoteRessource, $localRessource) {
		if (is_string($remoteRessource) && is_string($localRessource)
				&& !empty($remoteRessource) && !empty($localRessource)) {
			$fileContent = t3lib_div::getURL($remoteRessource, 0, array(TYPO3_user_agent));
			if ($fileContent !== false) {
				t3lib_div::writeFile($localRessource, $fileContent) || $this->throwConnectionException(sprintf('Could not write to file %s.', htmlspecialchars($localRessource)));
			} else {
				$this->throwConnectionException(sprintf('Could not access remote ressource %s.', htmlspecialchars($remoteRessource)));
			}
		}
	}

	/**
	 * Method returns location of local extension list file.
	 *
	 * @access  public
	 * @return  string  local location of file
	 * @see	 getRemoteExtListFile()
	 */
	public function getLocalExtListFile() {
		$absFilePath = PATH_site . 'typo3temp/'
				. intval($this->repository->getId())
				. '.extensions.xml.gz';
		return $absFilePath;
	}

	/**
	 * Method returns location of remote extension list file.
	 *
	 * @access  public
	 * @return  string  remote location of file
	 * @see	 getLocalExtListFile()
	 */
	public function getRemoteExtListFile() {
		$mirror = $this->getMirrors(TRUE)->getMirror();
		$filePath = 'http://' . $mirror['host'] . $mirror['path']
				. 'extensions.xml.gz';
		return $filePath;
	}

	/**
	 * Method returns location of remote file containing
	 * the extension checksum hash.
	 *
	 * @access  public
	 * @return  string  remote location of file
	 */
	public function getRemoteExtHashFile() {
		$mirror = $this->getMirrors(TRUE)->getMirror();
		$filePath = 'http://' . $mirror['host'] . $mirror['path']
				. 'extensions.md5';
		return $filePath;
	}

	/**
	 * Method returns location of local mirror list file.
	 *
	 * @access  public
	 * @return  string  local location of file
	 * @see	 getRemoteMirrorListFile()
	 */
	public function getLocalMirrorListFile() {
		$absFilePath = PATH_site . 'typo3temp/'
				. intval($this->repository->getId())
				. '.mirrors.xml.gz';
		return $absFilePath;
	}

	/**
	 * Method returns location of remote mirror list file.
	 *
	 * @access  public
	 * @return  string  remote location of file
	 * @see	 getLocalMirrorListFile()
	 */
	public function getRemoteMirrorListFile() {
		$filePath = $this->repository->getMirrorListUrl();
		return $filePath;
	}

	/**
	 * Method returns available mirrors for registered repository.
	 *
	 * If there are no mirrors registered to the repository,
	 * the method will retrieve them from file system or remote
	 * server.
	 *
	 * @access  public
	 * @param   boolean  $forcedUpdateFromRemote  if boolean true, mirror configuration will always retrieved from remote server
	 * @return  em_repository_mirrors  instance of repository mirrors class
	 */
	public function getMirrors($forcedUpdateFromRemote = TRUE) {
		$assignedMirror = $this->repository->getMirrors();
		if ($forcedUpdateFromRemote || is_null($assignedMirror) || !is_object($assignedMirror)) {
			if ($forcedUpdateFromRemote || !is_file($this->getLocalMirrorListFile())) {
				$this->fetchMirrorListFile();
			}
			$objMirrorListImporter = t3lib_div::makeInstance('tx_em_Import_MirrorListImporter');
			$this->repository->addMirrors($objMirrorListImporter->getMirrors($this->getLocalMirrorListFile()));
		}
		return $this->repository->getMirrors();
	}

	/**
	 * Method returns information if currently available
	 * extension list might be outdated.
	 *
	 * @access  public
	 * @see	 em_repository_utility::PROBLEM_NO_VERSIONS_IN_DATABASE,
	 *		  em_repository_utility::PROBLEM_EXTENSION_FILE_NOT_EXISTING,
	 *		  em_repository_utility::PROBLEM_EXTENSION_HASH_CHANGED
	 * @return  integer  integer "0" if everything is perfect, otherwise bitmask with occured problems
	 * @see	 updateExtList()
	 */
	public function isExtListUpdateNecessary() {
		$updateNecessity = 0;

		if (tx_em_Database::getExtensionCountFromRepository($this->getRepositoryUID(TRUE)) <= 0) {
			$updateNecessity |= self::PROBLEM_NO_VERSIONS_IN_DATABASE;
		}

		if (!is_file($this->getLocalExtListFile())) {
			$updateNecessity |= self::PROBLEM_EXTENSION_FILE_NOT_EXISTING;
		} else {
			$remotemd5 = t3lib_div::getURL($this->getRemoteExtHashFile(), 0, array(TYPO3_user_agent));

			if ($remotemd5 !== false) {
				$localmd5 = md5_file($this->getLocalExtListFile());
				if ($remotemd5 !== $localmd5) {
					$updateNecessity |= self::PROBLEM_EXTENSION_HASH_CHANGED;
				}
			} else {
				$this->throwConnectionException('Could not retrieve extension hash file from remote server.');
			}
		}
		return $updateNecessity;
	}

	/**
	 * Method returns UID of the current repository.
	 *
	 * @access  public
	 * @param   boolean	$insertIfMissing  creates repository record in DB if set to TRUE
	 * @return  integer
	 */
	public function getRepositoryUID($insertIfMissing = FALSE) {
		$uid = $this->repository->getId();
		$repository = tx_em_Database::getRepositoryByUID($uid);
		if (empty($repository) && $insertIfMissing) {
			$uid = tx_em_Database::insertRepository($this->repository);
		} else {
			$uid = intval($repository['uid']);
		}

		return $uid;
	}

	/**
	 * Method updates TYPO3 database with up-to-date
	 * extension version records.
	 *
	 * @access  public
	 * @param   boolean  $renderFlashMessage  if method should return flashmessage or raw integer
	 * @return  mixed	either sum of imported extensions or instance of t3lib_FlashMessage
	 * @see	 isExtListUpdateNecessary()
	 */
	public function updateExtList($renderFlashMessage = FALSE) {

		if ($renderFlashMessage) {
			/* @var $flashMessage t3lib_FlashMessage */
			$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('ext_import_list_unchanged_header'),
				$GLOBALS['LANG']->getLL('ext_import_list_unchanged'),
				t3lib_FlashMessage::INFO
			);
		}
		$sumRecords = 0;

		$updateNecessity = $this->isExtListUpdateNecessary();

		if ($updateNecessity !== 0) {
			// retrieval of file necessary
			$tmpBitmask = (self::PROBLEM_EXTENSION_FILE_NOT_EXISTING | self::PROBLEM_EXTENSION_HASH_CHANGED);
			if (($tmpBitmask & $updateNecessity) > 0) {
				$this->fetchExtListFile();
				$updateNecessity &= ~$tmpBitmask;
			}

				// database table cleanup
			if (($updateNecessity & self::PROBLEM_NO_VERSIONS_IN_DATABASE)) {
				$updateNecessity &= ~self::PROBLEM_NO_VERSIONS_IN_DATABASE;
			} else {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_extensions', 'repository=' . $this->getRepositoryUID());
			}

				// no further problems - start of import process
			if ($updateNecessity === 0) {
				$uid = $this->getRepositoryUID(TRUE);
				/* @var $objExtListImporter tx_em_Import_ExtensionListImporter */
				$objExtListImporter = t3lib_div::makeInstance('tx_em_Import_ExtensionListImporter');
				$objExtListImporter->import($this->getLocalExtListFile(), $uid);
				$sumRecords = tx_em_Database::getExtensionCountFromRepository($uid);
				if ($renderFlashMessage) {
					$flashMessage->setTitle($GLOBALS['LANG']->getLL('ext_import_extlist_updated_header'));
					$flashMessage->setMessage(sprintf($GLOBALS['LANG']->getLL('ext_import_extlist_updated'), tx_em_Database::getExtensionCountFromRepository()));
					$flashMessage->setSeverity(t3lib_FlashMessage::OK);
				}
			}
		}
		return $renderFlashMessage ? $flashMessage : $sumRecords;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/repository/class.tx_em_repository_utility.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/repository/class.tx_em_repository_utility.php']);
}

?>