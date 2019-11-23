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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility;
use TYPO3\CMS\Extensionmanager\Utility\Repository\Helper;

/**
 * Utility for Downloading Extensions
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class DownloadUtility implements SingletonInterface
{
    /**
     * @var TerUtility
     */
    protected $terUtility;

    /**
     * @var Helper
     */
    protected $repositoryHelper;

    /**
     * @var string
     */
    protected $downloadPath = 'Local';

    /**
     * @var FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @param TerUtility $terUtility
     */
    public function injectTerUtility(TerUtility $terUtility)
    {
        $this->terUtility = $terUtility;
    }

    /**
     * @param Helper $repositoryHelper
     */
    public function injectRepositoryHelper(Helper $repositoryHelper)
    {
        $this->repositoryHelper = $repositoryHelper;
    }

    /**
     * @param FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * Download an extension
     *
     * @param Extension $extension
     */
    public function download(Extension $extension)
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
     * @throws ExtensionManagerException
     */
    public function setDownloadPath($downloadPath)
    {
        if (!in_array($downloadPath, Extension::returnAllowedInstallTypes())) {
            throw new ExtensionManagerException($downloadPath . ' not in allowed download paths', 1344766387);
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
