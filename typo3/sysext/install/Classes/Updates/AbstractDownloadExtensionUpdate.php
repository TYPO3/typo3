<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Download extension from TER
 */
abstract class AbstractDownloadExtensionUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Install an Extension from the Extension Repository';

    /**
     * See subclasses for more information
     * @var array
     */
    protected $extensionDetails = [];

    /**
     * @var string
     */
    protected $repositoryUrl = 'https://typo3.org/fileadmin/ter/@filename';

    /**
     * This method can be called to install an extension following all proper processes
     * (e.g. installing in extList, respecting priority, etc.)
     *
     * @param string $extensionKey
     * @param mixed $customMessages
     * @return bool whether the installation worked or not
     */
    protected function installExtension($extensionKey, &$customMessages)
    {
        $updateSuccessful = true;
        /** @var $objectManager ObjectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var $extensionListUtility ListUtility */
        $extensionListUtility = $objectManager->get(ListUtility::class);

        $availableExtensions = $extensionListUtility->getAvailableExtensions();
        $availableAndInstalledExtensions = $extensionListUtility->getAvailableAndInstalledExtensions($availableExtensions);

        // Extension is not downloaded yet.
        if (!is_array($availableAndInstalledExtensions[$extensionKey])) {
            /** @var $extensionTerUtility TerUtility */
            $extensionTerUtility = $objectManager->get(TerUtility::class);
            $extensionDetails = $this->getExtensionDetails($extensionKey);
            if (empty($extensionDetails)) {
                $updateSuccessful = false;
                $customMessages .= 'No version information for extension ' . $extensionKey . ' found. Can not install the extension.';
            }
            $t3xContent = $this->fetchExtension($extensionKey, $extensionDetails['versionString']);
            if (empty($t3xContent)) {
                $updateSuccessful = false;
                $customMessages .= 'The extension ' . $extensionKey . ' could not be downloaded.';
            }
            $t3xExtracted = $extensionTerUtility->decodeExchangeData($t3xContent);
            if (empty($t3xExtracted) || !is_array($t3xExtracted) || empty($t3xExtracted['extKey'])) {
                $updateSuccessful = false;
                $customMessages .= 'The extension ' . $extensionKey . ' could not be extracted.';
            }

            /** @var $extensionFileHandlingUtility FileHandlingUtility */
            $extensionFileHandlingUtility = $objectManager->get(FileHandlingUtility::class);
            $extensionFileHandlingUtility->unpackExtensionFromExtensionDataArray($t3xExtracted);

            // The listUtility now needs to have the regenerated list of packages
            $extensionListUtility->reloadAvailableExtensions();
        }

        if ($updateSuccessful !== false) {
            /** @var $extensionInstallUtility InstallUtility */
            $extensionInstallUtility = $objectManager->get(InstallUtility::class);
            $extensionInstallUtility->install($extensionKey);
        }
        return $updateSuccessful;
    }

    /**
     * Returns the details of a local or external extension
     *
     * @param string $extensionKey Key of the extension to check
     *
     * @return array Extension details
     */
    protected function getExtensionDetails($extensionKey)
    {
        if (array_key_exists($extensionKey, $this->extensionDetails)) {
            return $this->extensionDetails[$extensionKey];
        }

        return [];
    }

    /**
     * Fetch extension from repository
     *
     * @param string $extensionKey The extension key to fetch
     * @param string $version The version to fetch
     *
     * @throws \InvalidArgumentException
     * @return string T3X file content
     */
    protected function fetchExtension($extensionKey, $version)
    {
        if (empty($extensionKey) || empty($version)) {
            throw new \InvalidArgumentException('No extension key for fetching an extension was given.',
                1344687432);
        }

        $filename = $extensionKey[0] . '/' . $extensionKey[1] . '/' . $extensionKey . '_' . $version . '.t3x';
        $url = str_replace('@filename', $filename, $this->repositoryUrl);

        return $this->fetchUrl($url);
    }

    /**
     * Open an URL and return the response
     *
     * This wrapper method is required to try several download methods if
     * the configuration is not valid or initially written by the installer.
     *
     * @param string $url The URL to file
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return string File content
     */
    protected function fetchUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('No URL for downloading an extension given.',
                1344687436);
        }

        $fileContent = GeneralUtility::getUrl($url, 0, [TYPO3_user_agent]);

        // Can not fetch url, throw an exception
        if ($fileContent === false) {
            throw new \RuntimeException('Can not fetch URL "' . $url . '". Possible reasons are network problems or misconfiguration.',
                1344685036);
        }

        return $fileContent;
    }
}
