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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\Archive\ExtractException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Utility for dealing with files and folders
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class FileHandlingUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EmConfUtility
     */
    protected $emConfUtility;

    /**
     * @var InstallUtility
     */
    protected $installUtility;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @param EmConfUtility $emConfUtility
     */
    public function injectEmConfUtility(EmConfUtility $emConfUtility)
    {
        $this->emConfUtility = $emConfUtility;
    }

    /**
     * @param InstallUtility $installUtility
     */
    public function injectInstallUtility(InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    /**
     * @param LanguageService $languageService
     */
    public function injectLanguageService(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Initialize method - loads language file
     */
    public function initializeObject()
    {
        $this->languageService->includeLLFile('EXT:extensionmanager/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Unpack an extension in t3x data format and write files
     *
     * @param array $extensionData
     * @param Extension $extension
     * @param string $pathType
     */
    public function unpackExtensionFromExtensionDataArray(array $extensionData, Extension $extension = null, $pathType = 'Local')
    {
        $extensionDir = $this->makeAndClearExtensionDir($extensionData['extKey'], $pathType);
        $files = $this->extractFilesArrayFromExtensionData($extensionData);
        $directories = $this->extractDirectoriesFromExtensionData($files);
        $files = array_diff_key($files, array_flip($directories));
        $this->createDirectoriesForExtensionFiles($directories, $extensionDir);
        $this->writeExtensionFiles($files, $extensionDir);
        $this->writeEmConfToFile($extensionData, $extensionDir, $extension);
        $this->reloadPackageInformation($extensionData['extKey']);
    }

    /**
     * Extract needed directories from given extensionDataFilesArray
     *
     * @param array $files
     * @return array
     */
    protected function extractDirectoriesFromExtensionData(array $files)
    {
        $directories = [];
        foreach ($files as $filePath => $file) {
            preg_match('/(.*)\\//', $filePath, $matches);
            if (!empty($matches[0])) {
                $directories[] = $matches[0];
            }
        }
        return array_unique($directories);
    }

    /**
     * Returns the "FILES" part from the data array
     *
     * @param array $extensionData
     * @return mixed
     */
    protected function extractFilesArrayFromExtensionData(array $extensionData)
    {
        return $extensionData['FILES'];
    }

    /**
     * Loops over an array of directories and creates them in the given root path
     * It also creates nested directory structures
     *
     * @param array $directories
     * @param string $rootPath
     */
    protected function createDirectoriesForExtensionFiles(array $directories, $rootPath)
    {
        foreach ($directories as $directory) {
            $this->createNestedDirectory($rootPath . $directory);
        }
    }

    /**
     * Wrapper for utility method to create directory recursively
     *
     * @param string $directory Absolute path
     * @throws ExtensionManagerException
     */
    protected function createNestedDirectory($directory)
    {
        try {
            GeneralUtility::mkdir_deep($directory);
        } catch (\RuntimeException $exception) {
            throw new ExtensionManagerException(
                sprintf($this->languageService->getLL('fileHandling.couldNotCreateDirectory'), $this->getRelativePath($directory)),
                1337280416
            );
        }
    }

    /**
     * Loops over an array of files and writes them to the given rootPath
     *
     * @param array $files
     * @param string $rootPath
     */
    protected function writeExtensionFiles(array $files, $rootPath)
    {
        foreach ($files as $file) {
            GeneralUtility::writeFile($rootPath . $file['name'], $file['content']);
        }
    }

    /**
     * Removes the current extension of $type and creates the base folder for
     * the new one (which is going to be imported)
     *
     * @param string $extensionKey
     * @param string $pathType Extension installation scope (Local,Global,System)
     * @throws ExtensionManagerException
     * @return string
     */
    protected function makeAndClearExtensionDir($extensionKey, $pathType = 'Local')
    {
        $extDirPath = $this->getExtensionDir($extensionKey, $pathType);
        if (is_dir($extDirPath)) {
            $this->removeDirectory($extDirPath);
        }
        $this->addDirectory($extDirPath);

        return $extDirPath;
    }

    /**
     * Returns the installation directory for an extension depending on the installation scope
     *
     * @param string $extensionKey
     * @param string $pathType Extension installation scope (Local,Global,System)
     * @return string
     * @throws ExtensionManagerException
     */
    public function getExtensionDir($extensionKey, $pathType = 'Local')
    {
        $paths = Extension::returnInstallPaths();
        $path = $paths[$pathType] ?? '';
        if (!$path || !is_dir($path) || !$extensionKey) {
            throw new ExtensionManagerException(
                sprintf($this->languageService->getLL('fileHandling.installPathWasNoDirectory'), $this->getRelativePath($path)),
                1337280417
            );
        }

        return $path . $extensionKey . '/';
    }

    /**
     * Add specified directory
     *
     * @param string $extDirPath
     * @throws ExtensionManagerException
     */
    protected function addDirectory($extDirPath)
    {
        GeneralUtility::mkdir($extDirPath);
        if (!is_dir($extDirPath)) {
            throw new ExtensionManagerException(
                sprintf($this->languageService->getLL('fileHandling.couldNotCreateDirectory'), $this->getRelativePath($extDirPath)),
                1337280418
            );
        }
    }

    /**
     * Remove specified directory
     *
     * @param string $extDirPath
     * @throws ExtensionManagerException
     */
    public function removeDirectory($extDirPath)
    {
        $extDirPath = GeneralUtility::fixWindowsFilePath($extDirPath);
        $extensionPathWithoutTrailingSlash = rtrim($extDirPath, '/');
        if (is_link($extensionPathWithoutTrailingSlash) && !Environment::isWindows()) {
            $result = unlink($extensionPathWithoutTrailingSlash);
        } else {
            $result = GeneralUtility::rmdir($extDirPath, true);
        }
        if ($result === false) {
            throw new ExtensionManagerException(
                sprintf($this->languageService->getLL('fileHandling.couldNotRemoveDirectory'), $this->getRelativePath($extDirPath)),
                1337280415
            );
        }
    }

    /**
     * Constructs emConf and writes it to corresponding file
     * In case the file has been extracted already, the properties of the meta data take precedence but are merged with the present ext_emconf.php
     *
     * @param array $extensionData
     * @param string $rootPath
     * @param Extension $extension
     */
    protected function writeEmConfToFile(array $extensionData, $rootPath, Extension $extension = null)
    {
        $emConfFileData = [];
        if (file_exists($rootPath . 'ext_emconf.php')) {
            $emConfFileData = $this->emConfUtility->includeEmConf(
                $extensionData['extKey'],
                [
                    'packagePath' => $rootPath,
                    'siteRelPath' => PathUtility::stripPathSitePrefix($rootPath)
                ]
            );
        }
        $extensionData['EM_CONF'] = array_replace_recursive($emConfFileData, $extensionData['EM_CONF']);
        $emConfContent = $this->emConfUtility->constructEmConf($extensionData, $extension);
        GeneralUtility::writeFile($rootPath . 'ext_emconf.php', $emConfContent);
    }

    /**
     * Is the given path a valid path for extension installation
     *
     * @param string $path the absolute (!) path in question
     * @return bool
     */
    public function isValidExtensionPath($path)
    {
        $allowedPaths = Extension::returnAllowedInstallPaths();
        foreach ($allowedPaths as $allowedPath) {
            if (GeneralUtility::isFirstPartOfStr($path, $allowedPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns absolute path
     *
     * @param string $relativePath
     * @throws ExtensionManagerException
     * @return string
     */
    protected function getAbsolutePath($relativePath)
    {
        $absolutePath = GeneralUtility::getFileAbsFileName(GeneralUtility::resolveBackPath(Environment::getPublicPath() . '/' . $relativePath));
        if (empty($absolutePath)) {
            throw new ExtensionManagerException('Illegal relative path given', 1350742864);
        }
        return $absolutePath;
    }

    /**
     * Returns relative path
     *
     * @param string $absolutePath
     * @return string
     */
    protected function getRelativePath(string $absolutePath): string
    {
        return PathUtility::stripPathSitePrefix($absolutePath);
    }

    /**
     * Get extension path for an available or installed extension
     *
     * @param string $extensionKey
     * @return string
     */
    public function getAbsoluteExtensionPath(string $extensionKey): string
    {
        $extension = $this->installUtility->enrichExtensionWithDetails($extensionKey);
        return $this->getAbsolutePath($extension['siteRelPath']);
    }

    /**
     * Get version of an available or installed extension
     *
     * @param string $extensionKey
     * @return string
     */
    protected function getExtensionVersion(string $extensionKey): string
    {
        $extensionData = $this->installUtility->enrichExtensionWithDetails($extensionKey);
        return (string)$extensionData['version'];
    }

    /**
     * Create a zip file from an extension
     *
     * @param string $extensionKey
     * @return string Name and path of create zip file
     */
    public function createZipFileFromExtension($extensionKey): string
    {
        $extensionPath = $this->getAbsoluteExtensionPath($extensionKey);

        // Add trailing slash to the extension path, getAllFilesAndFoldersInPath explicitly requires that.
        $extensionPath = PathUtility::sanitizeTrailingSeparator($extensionPath);

        $version = $this->getExtensionVersion($extensionKey);
        if (empty($version)) {
            $version = '0.0.0';
        }

        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (!@is_dir($temporaryPath)) {
            GeneralUtility::mkdir($temporaryPath);
        }
        $fileName = $temporaryPath . $extensionKey . '_' . $version . '_' . date('YmdHi', $GLOBALS['EXEC_TIME']) . '.zip';

        $zip = new \ZipArchive();
        $zip->open($fileName, \ZipArchive::CREATE);

        $excludePattern = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

        // Get all the files of the extension, but exclude the ones specified in the excludePattern
        $files = GeneralUtility::getAllFilesAndFoldersInPath(
            [], // No files pre-added
            $extensionPath, // Start from here
            '', // Do not filter files by extension
            true, // Include subdirectories
            PHP_INT_MAX, // Recursion level
            $excludePattern        // Files and directories to exclude.
        );

        // Make paths relative to extension root directory.
        $files = GeneralUtility::removePrefixPathFromList($files, $extensionPath);

        // Remove the one empty path that is the extension dir itself.
        $files = array_filter($files);

        foreach ($files as $file) {
            $fullPath = $extensionPath . $file;
            // Distinguish between files and directories, as creation of the archive
            // fails on Windows when trying to add a directory with "addFile".
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($file);
            } else {
                $zip->addFile($fullPath, $file);
            }
        }

        $zip->close();
        return $fileName;
    }

    /**
     * Unzip an extension.zip.
     *
     * @param string $file path to zip file
     * @param string $fileName file name
     * @param string $pathType path type (Local, Global, System)
     * @throws ExtensionManagerException
     */
    public function unzipExtensionFromFile($file, $fileName, $pathType = 'Local')
    {
        $extensionDir = $this->makeAndClearExtensionDir($fileName, $pathType);

        try {
            $zipService = GeneralUtility::makeInstance(ZipService::class);
            if ($zipService->verify($file)) {
                $zipService->extract($file, $extensionDir);
            }
        } catch (ExtractException $e) {
            $this->logger->error('Extracting the extension archive failed', ['exception' => $e]);
            throw new ExtensionManagerException('Extracting the extension archive failed: ' . $e->getMessage(), 1565777179, $e);
        }

        GeneralUtility::fixPermissions($extensionDir, true);
    }

    /**
     * @param string $extensionKey
     */
    protected function reloadPackageInformation($extensionKey)
    {
        $this->installUtility->reloadPackageInformation($extensionKey);
    }
}
