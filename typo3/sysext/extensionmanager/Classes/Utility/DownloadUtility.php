<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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

/**
 * Utility for Downloading Extensions
 */
class DownloadUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility
     */
    protected $terUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
     */
    protected $repositoryHelper;

    /**
     * @var string
     */
    protected $downloadPath = 'Local';

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility
     */
    public function injectTerUtility(\TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility)
    {
        $this->terUtility = $terUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
     */
    public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper)
    {
        $this->repositoryHelper = $repositoryHelper;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * Download an extension
     *
     * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
     */
    public function download(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension)
    {
        $mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
        $fetchedExtension = $this->terUtility->fetchExtension($extension->getExtensionKey(), $extension->getVersion(), $extension->getMd5hash(), $mirrorUrl);
        if (isset($fetchedExtension['extKey']) && !empty($fetchedExtension['extKey']) && is_string($fetchedExtension['extKey'])) {
            $this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($fetchedExtension, $extension, $this->getDownloadPath());
        }
    }

    /**
     * Set the download path
     *
     * @param string $downloadPath
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function setDownloadPath($downloadPath)
    {
        if (!in_array($downloadPath, \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallTypes())) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($downloadPath . ' not in allowed download paths', 1344766387);
        }
        $this->downloadPath = $downloadPath;
    }

    /**
     * Get the download path
     *
     * @return string
     */
    public function getDownloadPath()
    {
        return $this->downloadPath;
    }
}
