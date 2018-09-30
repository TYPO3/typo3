<?php
namespace TYPO3\CMS\Extensionmanager\Command;

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

use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * CommandController for working with extension management through CLI/scheduler
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use Symfony Command alternatives instead.
 */
class ExtensionCommandController extends CommandController
{
    /**
     * @var bool
     */
    protected $requestAdminPermissions = true;

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @param \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
     */
    public function injectSignalSlotDispatcher(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher;
    }

    /**
     * Installs an extension by key
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param string $extensionKey
     * @cli
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use the equivalent Symfony Command instead.
     */
    public function installCommand($extensionKey)
    {
        trigger_error('Calling ExtensionCommandController->installCommand() will be removed in TYPO3 v10.0. Use the Symfony command "extension:activate" instead, to be called via the "typo3" CLI entrypoint.', E_USER_DEPRECATED);
        $this->emitPackagesMayHaveChangedSignal();

        /** @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $service */
        $service = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
        $service->install($extensionKey);
    }

    /**
     * Uninstalls an extension by key
     *
     * The extension files must be present in one of the
     * recognised extension folder paths in TYPO3.
     *
     * @param string $extensionKey
     * @cli
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use the equivalent Symfony Command instead.
     */
    public function uninstallCommand($extensionKey)
    {
        trigger_error('Calling ExtensionCommandController->uninstallCommand() will be removed in TYPO3 v10.0. Use the Symfony command "extension:deactivate" instead, to be called via the "typo3" CLI entrypoint.', E_USER_DEPRECATED);
        /** @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $service */
        $service = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class);
        $service->uninstall($extensionKey);
    }

    /**
     * Updates class loading information.
     *
     * This command is only needed during development. The extension manager takes care
     * creating or updating this info properly during extension (de-)activation.
     *
     * @cli
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use the equivalent Symfony Command instead.
     */
    public function dumpClassLoadingInformationCommand()
    {
        trigger_error('Calling ExtensionCommandController->dumpClassLoadingInformationCommand() will be removed in TYPO3 v10.0. Use the Symfony command "dumpautoload" instead, to be called via the "typo3" CLI entrypoint.', E_USER_DEPRECATED);
        if (Environment::isComposerMode()) {
            $this->output->outputLine('<error>Class loading information is managed by composer. Use "composer dump-autoload" command to update the information.</error>');
            $this->quit(1);
        } else {
            ClassLoadingInformation::dumpClassLoadingInformation();
            $this->output->outputLine('Class Loading information has been updated.');
        }
    }

    /**
     * Emits packages may have changed signal
     */
    protected function emitPackagesMayHaveChangedSignal()
    {
        $this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
    }
}
