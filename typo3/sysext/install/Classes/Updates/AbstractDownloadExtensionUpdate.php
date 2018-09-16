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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Download extension from TER
 */
abstract class AbstractDownloadExtensionUpdate implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    /**
     * @var string
     */
    protected $repositoryUrl = 'https://typo3.org/fileadmin/ter/@filename';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var \TYPO3\CMS\Install\Updates\ExtensionModel
     */
    protected $extension;

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        return $this->installExtension($this->extension);
    }

    /**
     * This method can be called to install an extension following all proper processes
     * (e.g. installing in extList, respecting priority, etc.)
     *
     * @param \TYPO3\CMS\Install\Updates\ExtensionModel $extension
     * @return bool whether the installation worked or not
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    protected function installExtension(ExtensionModel $extension): bool
    {
        $updateSuccessful = true;
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ListUtility $extensionListUtility */
        $extensionListUtility = $objectManager->get(ListUtility::class);
        $availableExtensions = $extensionListUtility->getAvailableExtensions();

        $extensionKey = $extension->getKey();
        $isExtensionAvailable = !empty($availableExtensions[$extensionKey]);
        $isComposerMode = Environment::isComposerMode();

        if (!$isComposerMode && !$isExtensionAvailable) {
            /** @var TerUtility $extensionTerUtility */
            $extensionTerUtility = $objectManager->get(TerUtility::class);
            $t3xContent = $this->fetchExtension($extensionKey, $extension->getVersionString());
            if (empty($t3xContent)) {
                $updateSuccessful = false;
                $this->output->writeln('<error>The extension ' . $extensionKey . ' could not be downloaded.</error>');
            }
            $t3xExtracted = $extensionTerUtility->decodeExchangeData($t3xContent);
            if (empty($t3xExtracted) || !is_array($t3xExtracted) || empty($t3xExtracted['extKey'])) {
                $updateSuccessful = false;
                $this->output->writeln('<error>The extension ' . $extensionKey . ' could not be extracted.</error>');
            }

            /** @var FileHandlingUtility $extensionFileHandlingUtility */
            $extensionFileHandlingUtility = $objectManager->get(FileHandlingUtility::class);
            $extensionFileHandlingUtility->unpackExtensionFromExtensionDataArray($t3xExtracted);

            // The listUtility now needs to have the regenerated list of packages
            $extensionListUtility->reloadAvailableExtensions();
        }

        if ($isComposerMode && !$isExtensionAvailable) {
            $updateSuccessful = false;
            $this->output->writeln(
                '<warning>The extension ' . $extensionKey
                . ' can not be downloaded since Composer is used for package management. Please require this '
                . 'extension as package via Composer: "composer require ' . $extension->getComposerName()
                . ':^' . $extension->getVersionString() . '"</warning>'
            );
        }

        if ($updateSuccessful) {
            /** @var InstallUtility $extensionInstallUtility */
            $extensionInstallUtility = $objectManager->get(InstallUtility::class);
            $extensionInstallUtility->install($extensionKey);
        }

        return $updateSuccessful;
    }

    /**
     * Fetch extension from repository
     *
     * @param string $extensionKey The extension key to fetch
     * @param string $version The version to fetch
     * @throws \InvalidArgumentException
     * @return string T3X file content
     */
    protected function fetchExtension($extensionKey, $version): string
    {
        if (empty($extensionKey) || empty($version)) {
            throw new \InvalidArgumentException(
                'No extension key for fetching an extension was given.',
                1344687432
            );
        }

        $filename = $extensionKey[0] . '/' . $extensionKey[1] . '/' . $extensionKey . '_' . $version . '.t3x';
        $url = str_replace('@filename', $filename, $this->repositoryUrl);

        return $this->fetchUrl($url);
    }

    /**
     * Open an URL and return the response
     * This wrapper method is required to try several download methods if
     * the configuration is not valid or initially written by the installer.
     *
     * @param string $url The URL to file
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @return string File content
     */
    protected function fetchUrl($url): string
    {
        if (empty($url)) {
            throw new \InvalidArgumentException(
                'No URL for downloading an extension given.',
                1344687436
            );
        }

        $fileContent = GeneralUtility::getUrl($url);

        // Can not fetch url, throw an exception
        if ($fileContent === false) {
            throw new \RuntimeException(
                'Can not fetch URL "' . $url . '". Possible reasons are network problems or misconfiguration.',
                1344685036
            );
        }

        return $fileContent;
    }
}
