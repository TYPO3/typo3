<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extensionmanager\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Remote\DownloadFailedException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Remote\VerificationFailedException;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Download extension from TER
 */
abstract class AbstractDownloadExtensionUpdate implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    protected OutputInterface $output;
    protected ExtensionModel $extension;

    protected FileHandlingUtility $fileHandlingUtility;
    protected ListUtility $listUtility;
    protected InstallUtility $installUtility;
    protected RemoteRegistry $remoteRegistry;

    public function injectFileHandlingUtility(FileHandlingUtility $fileHandlingUtility): void
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    public function injectListUtility(ListUtility $listUtility): void
    {
        $this->listUtility = $listUtility;
    }

    public function injectInstallUtility(InstallUtility $installUtility): void
    {
        $this->installUtility = $installUtility;
    }

    public function injectRemoteRegistry(RemoteRegistry $remoteRegistry): void
    {
        $this->remoteRegistry = $remoteRegistry;
    }

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     */
    public function executeUpdate(): bool
    {
        return $this->installExtension($this->extension);
    }

    /**
     * This method can be called to install an extension following all proper processes
     * (e.g. installing in extList, respecting priority, etc.)
     *
     * @return bool whether the installation worked or not
     * @throws ExtensionManagerException
     */
    protected function installExtension(ExtensionModel $extension): bool
    {
        $updateSuccessful = true;

        $availableExtensions = $this->listUtility->getAvailableExtensions();

        $extensionKey = $extension->getKey();
        $isExtensionAvailable = !empty($availableExtensions[$extensionKey]);
        $isComposerMode = Environment::isComposerMode();

        if (!$isComposerMode && !$isExtensionAvailable) {
            if ($this->remoteRegistry->hasDefaultRemote()) {
                $terRemote = $this->remoteRegistry->getDefaultRemote();
                try {
                    $terRemote->downloadExtension($extensionKey, $extension->getVersionString(), $this->fileHandlingUtility);
                } catch (DownloadFailedException) {
                    $updateSuccessful = false;
                    $this->output->writeln('<error>The extension ' . $extensionKey . ' could not be downloaded.</error>');
                } catch (VerificationFailedException) {
                    $updateSuccessful = false;
                    $this->output->writeln('<error>The extension ' . $extensionKey . ' could not be extracted.</error>');
                }
            } else {
                $updateSuccessful = false;
                $this->output->writeln(
                    '<error>The extension ' . $extensionKey . ' could not be downloaded because no remote is available.</error>'
                );
            }
            // The listUtility now needs to have the regenerated list of packages
            $this->listUtility->reloadAvailableExtensions();
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
            $this->installUtility->install($extensionKey);
        }

        return $updateSuccessful;
    }
}
