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

namespace TYPO3\CMS\Install\Updates;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Remote\DownloadFailedException;
use TYPO3\CMS\Extensionmanager\Remote\RemoteRegistry;
use TYPO3\CMS\Extensionmanager\Remote\VerificationFailedException;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;

/**
 * Download extension from TER
 */
abstract class AbstractDownloadExtensionUpdate implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
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

        $extensionListUtility = GeneralUtility::makeInstance(ListUtility::class);
        $availableExtensions = $extensionListUtility->getAvailableExtensions();

        $extensionKey = $extension->getKey();
        $isExtensionAvailable = !empty($availableExtensions[$extensionKey]);
        $isComposerMode = Environment::isComposerMode();

        if (!$isComposerMode && !$isExtensionAvailable) {
            $extensionFileHandlingUtility = GeneralUtility::makeInstance(FileHandlingUtility::class);
            $remoteRegistry = GeneralUtility::makeInstance(RemoteRegistry::class);
            if ($remoteRegistry->hasDefaultRemote()) {
                $terRemote = $remoteRegistry->getDefaultRemote();
                try {
                    $terRemote->downloadExtension($extensionKey, $extension->getVersionString(), $extensionFileHandlingUtility);
                } catch (DownloadFailedException $e) {
                    $updateSuccessful = false;
                    $this->output->writeln('<error>The extension ' . $extensionKey . ' could not be downloaded.</error>');
                } catch (VerificationFailedException $e) {
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
            $extensionInstallUtility = GeneralUtility::makeInstance(InstallUtility::class);
            $extensionInstallUtility->install($extensionKey);
        }

        return $updateSuccessful;
    }
}
