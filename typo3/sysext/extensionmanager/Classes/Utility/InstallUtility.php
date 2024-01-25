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

namespace TYPO3\CMS\Extensionmanager\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Package\Event\AfterPackageDeactivationEvent;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\PackageActivationService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Extension Manager Install Utility
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class InstallUtility implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private LanguageService $languageService;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly FileHandlingUtility $fileHandlingUtility,
        private readonly ListUtility $listUtility,
        private readonly PackageManager $packageManager,
        private readonly CacheManager $cacheManager,
        private readonly OpcodeCacheService $opcodeCacheService,
        private readonly PackageActivationService $packageActivationService,
        LanguageServiceFactory $languageServiceFactory,
    ) {
        $this->languageService = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    /**
     * Wrapper around PackageActivationService to install an extension and processes db updates.
     * The wrapper is used to properly dispatch the AfterPackageActivationEvent passing the current
     * instance to the listeners.
     *
     * @see DispatchAfterPackageActivationEventOnPackageInitialization
     */
    public function install(string ...$extensionKeys): void
    {
        $this->packageActivationService->activate($extensionKeys, $this);
    }

    /**
     * Helper function to uninstall an extension.
     *
     * @throws ExtensionManagerException
     */
    public function uninstall(string $extensionKey): void
    {
        $dependentExtensions = $this->findInstalledExtensionsThatDependOnExtension($extensionKey);
        if (!empty($dependentExtensions)) {
            throw new ExtensionManagerException(
                sprintf(
                    $this->languageService->sL(
                        'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.uninstall.dependencyError'
                    ),
                    $extensionKey,
                    implode(', ', $dependentExtensions)
                ),
                1342554622
            );
        }
        $this->packageManager->deactivatePackage($extensionKey);
        $this->eventDispatcher->dispatch(new AfterPackageDeactivationEvent($extensionKey, 'typo3-cms-extension', $this));
        $this->cacheManager->flushCachesInGroup('system');
    }

    /**
     * Reset and reload the available extensions.
     */
    public function reloadAvailableExtensions(): void
    {
        $this->listUtility->reloadAvailableExtensions();
    }

    /**
     * Checks if an extension is available in the system.
     */
    public function isAvailable(string $extensionKey): bool
    {
        return $this->packageManager->isPackageAvailable($extensionKey);
    }

    /**
     * Reloads the package information, if the package is already registered.
     *
     * @throws InvalidPackageStateException if the package isn't available
     */
    public function reloadPackageInformation(string $extensionKey): void
    {
        if ($this->packageManager->isPackageAvailable($extensionKey)) {
            $this->opcodeCacheService->clearAllActive();
            $this->packageManager->reloadPackageInformation($extensionKey);
        }
    }

    /**
     * Fetch additional information for an extension key.
     *
     * @throws ExtensionManagerException
     */
    public function enrichExtensionWithDetails(string $extensionKey, bool $loadTerInformation = true): array
    {
        $extension = $this->getExtensionArray($extensionKey);
        if (!$loadTerInformation) {
            $availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfInformation([$extensionKey => $extension]);
        } else {
            $availableAndInstalledExtensions = $this->listUtility->enrichExtensionsWithEmConfAndTerInformation([$extensionKey => $extension]);
        }
        if (!isset($availableAndInstalledExtensions[$extensionKey])) {
            throw new ExtensionManagerException(
                'Please check your uploaded extension "' . $extensionKey . '". The configuration file "ext_emconf.php" seems to be invalid.',
                1391432222
            );
        }
        return $availableAndInstalledExtensions[$extensionKey];
    }

    /**
     * Removing an extension deletes the directory.
     */
    public function removeExtension(string $extension): void
    {
        $absolutePath = $this->enrichExtensionWithDetails($extension)['packagePath'];
        if ($this->isValidExtensionPath($absolutePath)) {
            if ($this->packageManager->isPackageAvailable($extension)) {
                // Package manager deletes the extension and removes the entry from PackageStates.php
                $this->packageManager->deletePackage($extension);
            } else {
                // The extension is not listed in PackageStates.php, we can safely remove it
                $this->fileHandlingUtility->removeDirectory($absolutePath);
            }
        } else {
            throw new ExtensionManagerException('No valid extension path given.', 1342875724);
        }
    }

    /**
     * Find installed extensions which depend on the given extension.
     * Used by extension uninstall to stop the process if an installed
     * extension depends on the extension to be uninstalled.
     */
    protected function findInstalledExtensionsThatDependOnExtension(string $extensionKey): array
    {
        $availableAndInstalledExtensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $dependentExtensions = [];
        foreach ($availableAndInstalledExtensions as $availableAndInstalledExtensionKey => $availableAndInstalledExtension) {
            if (isset($availableAndInstalledExtension['installed']) && $availableAndInstalledExtension['installed'] === true) {
                if (is_array($availableAndInstalledExtension['constraints'] ?? false)
                    && is_array($availableAndInstalledExtension['constraints']['depends'])
                    && array_key_exists($extensionKey, $availableAndInstalledExtension['constraints']['depends'])
                ) {
                    $dependentExtensions[] = $availableAndInstalledExtensionKey;
                }
            }
        }
        return $dependentExtensions;
    }

    protected function getExtensionArray(string $extensionKey): array
    {
        $availableExtensions = $this->listUtility->getAvailableExtensions();
        if (isset($availableExtensions[$extensionKey])) {
            return $availableExtensions[$extensionKey];
        }
        throw new ExtensionManagerException('Extension ' . $extensionKey . ' is not available', 1342864081);
    }

    /**
     * Is the given path a valid path for extension installation
     *
     * @param string $path Absolute (!) path in question
     */
    protected function isValidExtensionPath(string $path): bool
    {
        $allowedPaths = Extension::returnInstallPaths();
        foreach ($allowedPaths as $allowedPath) {
            if (str_starts_with($path, $allowedPath)) {
                return true;
            }
        }
        return false;
    }
}
