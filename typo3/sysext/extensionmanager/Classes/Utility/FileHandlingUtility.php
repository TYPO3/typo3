<?php

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

namespace TYPO3\CMS\Extensionmanager\Utility;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Exception\Archive\ExtractException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Utility for dealing with files and folders.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class FileHandlingUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private LanguageService $languageService;

    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly EmConfUtility $emConfUtility,
        private readonly OpcodeCacheService $opcodeCacheService,
        LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    /**
     * Unpack an extension in t3x data format and write files
     */
    public function unpackExtensionFromExtensionDataArray(string $extensionKey, array $extensionData, string $pathType = 'Local'): void
    {
        $files = $extensionData['FILES'] ?? [];
        $emConfData = $extensionData['EM_CONF'] ?? [];
        $extensionDir = $this->makeAndClearExtensionDir($extensionKey, $pathType);
        $directories = $this->extractDirectoriesFromExtensionData($files);
        $files = array_diff_key($files, array_flip($directories));
        $this->createDirectoriesForExtensionFiles($directories, $extensionDir);
        $this->writeExtensionFiles($files, $extensionDir);
        $this->writeEmConfToFile($extensionKey, $emConfData, $extensionDir);
        $this->reloadPackageInformation($extensionKey);
    }

    /**
     * Returns the installation directory for an extension depending on the installation scope
     *
     * @param string $pathType Extension installation scope (Local,Global,System)
     * @throws ExtensionManagerException
     */
    public function getExtensionDir(string $extensionKey, string $pathType = 'Local'): string
    {
        $paths = Extension::returnInstallPaths();
        $path = $paths[$pathType] ?? '';
        if (!$path || !is_dir($path) || !$extensionKey) {
            throw new ExtensionManagerException(
                sprintf(
                    $this->languageService->sL('LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:fileHandling.installPathWasNoDirectory'),
                    $this->getRelativePath($path)
                ),
                1337280417
            );
        }
        return $path . $extensionKey . '/';
    }

    /**
     * Remove specified directory
     *
     * @throws ExtensionManagerException
     */
    public function removeDirectory(string $extDirPath): void
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
                sprintf(
                    $this->languageService->sL(
                        'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:fileHandling.couldNotRemoveDirectory'
                    ),
                    $this->getRelativePath($extDirPath)
                ),
                1337280415
            );
        }
    }

    /**
     * Unzip an extension.zip.
     *
     * @param string $file path to zip file
     * @param string $fileName file name
     * @param string $pathType path type (Local, Global, System)
     * @throws ExtensionManagerException
     */
    public function unzipExtensionFromFile(string $file, string $fileName, string $pathType = 'Local')
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
     * Extract needed directories from given extensionDataFilesArray
     */
    protected function extractDirectoriesFromExtensionData(array $files): array
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
     * Loops over an array of directories and creates them in the given root path
     * It also creates nested directory structures
     *
     * @throws ExtensionManagerException
     */
    protected function createDirectoriesForExtensionFiles(array $directories, string $rootPath): void
    {
        foreach ($directories as $directory) {
            $fullPath = $rootPath . $directory;
            try {
                GeneralUtility::mkdir_deep($fullPath);
            } catch (\RuntimeException) {
                throw new ExtensionManagerException(
                    sprintf(
                        $this->languageService->sL(
                            'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:fileHandling.couldNotCreateDirectory'
                        ),
                        $this->getRelativePath($fullPath)
                    ),
                    1337280416
                );
            }
        }
    }

    /**
     * Loops over an array of files and writes them to the given rootPath
     */
    protected function writeExtensionFiles(array $files, string $rootPath): void
    {
        foreach ($files as $file) {
            GeneralUtility::writeFile($rootPath . $file['name'], $file['content']);
        }
    }

    /**
     * Removes the current extension of $type and creates the base folder for
     * the new one (which is going to be imported)
     *
     * @param string $pathType Extension installation scope (Local,Global,System)
     * @throws ExtensionManagerException
     */
    protected function makeAndClearExtensionDir(string $extensionKey, string $pathType = 'Local'): string
    {
        $extDirPath = $this->getExtensionDir($extensionKey, $pathType);
        if (is_dir($extDirPath)) {
            $this->removeDirectory($extDirPath);
        }
        $this->addDirectory($extDirPath);
        return $extDirPath;
    }

    /**
     * Add specified directory
     *
     * @throws ExtensionManagerException
     */
    protected function addDirectory(string $extDirPath): void
    {
        GeneralUtility::mkdir($extDirPath);
        if (!is_dir($extDirPath)) {
            throw new ExtensionManagerException(
                sprintf(
                    $this->languageService->sL(
                        'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:fileHandling.couldNotCreateDirectory'
                    ),
                    $this->getRelativePath($extDirPath)
                ),
                1337280418
            );
        }
    }

    /**
     * Constructs emConf and writes it to corresponding file
     */
    protected function writeEmConfToFile(string $extensionKey, array $emConfData, string $rootPath): void
    {
        $emConfContent = $this->emConfUtility->constructEmConf($extensionKey, $emConfData);
        GeneralUtility::writeFile($rootPath . 'ext_emconf.php', $emConfContent);
    }

    /**
     * Returns relative path
     */
    protected function getRelativePath(string $absolutePath): string
    {
        return PathUtility::stripPathSitePrefix($absolutePath);
    }

    protected function reloadPackageInformation(string $extensionKey): void
    {
        if ($this->packageManager->isPackageAvailable($extensionKey)) {
            $this->opcodeCacheService->clearAllActive();
            $this->packageManager->reloadPackageInformation($extensionKey);
        }
    }
}
