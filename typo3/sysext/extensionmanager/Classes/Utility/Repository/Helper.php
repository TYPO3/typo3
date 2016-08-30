<?php
namespace TYPO3\CMS\Extensionmanager\Utility\Repository;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility;

/**
 * Central utility class for repository handling.
 */
class Helper implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * ##########################################
     * Problem constants - to be used in bitmasks
     * ##########################################
     */
    /**
     * Type of problem: extension file not existing in file system.
     *
     * @var int
     */
    const PROBLEM_EXTENSION_FILE_NOT_EXISTING = 1;
    /**
     * Type of problem: wrong hash indicates outdated extension file.
     *
     * @var int
     */
    const PROBLEM_EXTENSION_HASH_CHANGED = 2;
    /**
     * Type of problem: no version records in database.
     *
     * @var int
     */
    const PROBLEM_NO_VERSIONS_IN_DATABASE = 4;
    /**
     * Keeps instance of repository class.
     *
     * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Repository
     */
    protected $repository = null;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository
     */
    protected $repositoryRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility
     */
    protected $configurationUtility;

    /**
     * Class constructor.
     *
     * @access public
     */
    public function __construct()
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
        $this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository */
        $repositoryRepository = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository::class);
        $this->extensionRepository = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository::class);
        $this->configurationUtility = $this->objectManager->get(ConfigurationUtility::class);
        /** @var \TYPO3\CMS\Extensionmanager\Domain\Model\Repository $repository */
        $repository = $repositoryRepository->findByUid(1);
        if (is_object($repository)) {
            $this->setRepository($repository);
        }
    }

    /**
     * Method registers required repository instance to work with.
     *
     * Repository instance is passed by reference.
     *
     * @access public
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Repository $repository
     * @return void
     * @see $repository
     */
    public function setRepository(\TYPO3\CMS\Extensionmanager\Domain\Model\Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Method fetches extension list file from remote server.
     *
     * Delegates to {@link fetchFile()}.
     *
     * @access public
     * @return void
     * @throws ExtensionManagerException
     * @see fetchFile()
     */
    public function fetchExtListFile()
    {
        $this->fetchFile($this->getRemoteExtListFile(), $this->getLocalExtListFile());
    }

    /**
     * Method fetches mirror list file from remote server.
     *
     * Delegates to {@link fetchFile()}.
     *
     * @access public
     * @return void
     * @throws ExtensionManagerException
     * @see fetchFile()
     */
    public function fetchMirrorListFile()
    {
        $this->fetchFile($this->getRemoteMirrorListFile(), $this->getLocalMirrorListFile());
    }

    /**
     * Method fetches contents from remote server and
     * writes them into a file in the local file system.
     *
     * @param string $remoteResource remote resource to read contents from
     * @param string $localResource local resource (absolute file path) to store retrieved contents to (must be within typo3temp/)
     * @return void
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(), \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile()
     * @throws ExtensionManagerException
     */
    protected function fetchFile($remoteResource, $localResource)
    {
        if ($this->configurationUtility->getCurrentConfiguration('extensionmanager')['offlineMode']['value']) {
            throw new ExtensionManagerException('Extension Manager is in offline mode. No TER connection available.', 1437078780);
        }
        if (is_string($remoteResource) && is_string($localResource) && !empty($remoteResource) && !empty($localResource)) {
            $fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($remoteResource, 0, [TYPO3_user_agent]);
            if ($fileContent !== false) {
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($localResource, $fileContent) !== null) {
                    throw new ExtensionManagerException(sprintf('Could not write to file %s.', $localResource), 1342635378);
                }
            } else {
                throw new ExtensionManagerException(sprintf('Could not access remote resource %s.', $remoteResource), 1342635425);
            }
        }
    }

    /**
     * Method returns location of local extension list file.
     *
     * @access public
     * @return string local location of file
     * @see getRemoteExtListFile()
     */
    public function getLocalExtListFile()
    {
        $absFilePath = PATH_site . 'typo3temp/ExtensionManager/' . (int)$this->repository->getUid() . '.extensions.xml.gz';
        return $absFilePath;
    }

    /**
     * Method returns location of remote extension list file.
     *
     * @access public
     * @return string remote location of file
     * @see getLocalExtListFile()
     */
    public function getRemoteExtListFile()
    {
        $mirror = $this->getMirrors(true)->getMirror();
        $filePath = 'https://' . $mirror['host'] . $mirror['path'] . 'extensions.xml.gz';
        return $filePath;
    }

    /**
     * Method returns location of remote file containing
     * the extension checksum hash.
     *
     * @access public
     * @return string remote location of file
     */
    public function getRemoteExtHashFile()
    {
        $mirror = $this->getMirrors(true)->getMirror();
        $filePath = 'https://' . $mirror['host'] . $mirror['path'] . 'extensions.md5';
        return $filePath;
    }

    /**
     * Method returns location of local mirror list file.
     *
     * @access public
     * @return string local location of file
     * @see getRemoteMirrorListFile()
     */
    public function getLocalMirrorListFile()
    {
        $absFilePath = PATH_site . 'typo3temp/ExtensionManager/' . (int)$this->repository->getUid() . '.mirrors.xml.gz';
        return $absFilePath;
    }

    /**
     * Method returns location of remote mirror list file.
     *
     * @access public
     * @return string remote location of file
     * @see getLocalMirrorListFile()
     */
    public function getRemoteMirrorListFile()
    {
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
     * @access public
     * @param bool $forcedUpdateFromRemote if boolean TRUE, mirror configuration will always retrieved from remote server
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors instance of repository mirrors class
     * @throws ExtensionManagerException
     */
    public function getMirrors($forcedUpdateFromRemote = true)
    {
        $assignedMirror = $this->repository->getMirrors();
        if ($forcedUpdateFromRemote || is_null($assignedMirror) || !is_object($assignedMirror)) {
            if ($forcedUpdateFromRemote || !is_file($this->getLocalMirrorListFile())) {
                $this->fetchMirrorListFile();
            }
            /** @var $objMirrorListImporter \TYPO3\CMS\Extensionmanager\Utility\Importer\MirrorListUtility */
            $objMirrorListImporter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extensionmanager\Utility\Importer\MirrorListUtility::class);
            $this->repository->addMirrors($objMirrorListImporter->getMirrors($this->getLocalMirrorListFile()));
        }
        return $this->repository->getMirrors();
    }

    /**
     * Method returns information if currently available
     * extension list might be outdated.
     *
     * @access public
     * @see \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper::PROBLEM_NO_VERSIONS_IN_DATABASE,
     * @throws ExtensionManagerException
     * @return int "0" if everything is perfect, otherwise bitmask with problems
     */
    public function isExtListUpdateNecessary()
    {
        $updateNecessity = 0;
        if ($this->extensionRepository->countByRepository($this->repository->getUid()) <= 0) {
            $updateNecessity |= self::PROBLEM_NO_VERSIONS_IN_DATABASE;
        }
        if (!is_file($this->getLocalExtListFile())) {
            $updateNecessity |= self::PROBLEM_EXTENSION_FILE_NOT_EXISTING;
        } else {
            $remotemd5 = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($this->getRemoteExtHashFile(), 0, [TYPO3_user_agent]);
            if ($remotemd5 !== false) {
                $localmd5 = md5_file($this->getLocalExtListFile());
                if ($remotemd5 !== $localmd5) {
                    $updateNecessity |= self::PROBLEM_EXTENSION_HASH_CHANGED;
                }
            } else {
                throw new ExtensionManagerException('Could not retrieve extension hash file from remote server.', 1342635016);
            }
        }
        return $updateNecessity;
    }

    /**
     * Method updates TYPO3 database with up-to-date
     * extension version records.
     *
     * @return bool TRUE if the extension list was successfully update, FALSE if no update necessary
     * @throws ExtensionManagerException
     * @see isExtListUpdateNecessary()
     */
    public function updateExtList()
    {
        $updated = false;
        $updateNecessity = $this->isExtListUpdateNecessary();
        if ($updateNecessity !== 0) {
            // retrieval of file necessary
            $tmpBitmask = self::PROBLEM_EXTENSION_FILE_NOT_EXISTING | self::PROBLEM_EXTENSION_HASH_CHANGED;
            if (($tmpBitmask & $updateNecessity) > 0) {
                $this->fetchExtListFile();
                $updateNecessity &= ~$tmpBitmask;
            }
            // database table cleanup
            if ($updateNecessity & self::PROBLEM_NO_VERSIONS_IN_DATABASE) {
                $updateNecessity &= ~self::PROBLEM_NO_VERSIONS_IN_DATABASE;
            } else {
                // Use straight query as extbase "remove" is too slow here
                // This truncates the whole table. It would be more correct to remove only rows of a specific
                // repository, but multiple repository handling is not implemented, and truncate is quicker.
                $this->getDatabaseConnection()->exec_TRUNCATEquery('tx_extensionmanager_domain_model_extension');
            }
            // no further problems - start of import process
            if ($updateNecessity === 0) {
                $uid = $this->repository->getUid();
                /* @var $objExtListImporter \TYPO3\CMS\Extensionmanager\Utility\Importer\ExtensionListUtility */
                $objExtListImporter = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\Importer\ExtensionListUtility::class);
                $objExtListImporter->import($this->getLocalExtListFile(), $uid);
                $updated = true;
            }
        }
        return $updated;
    }

    /**
     * Get database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
