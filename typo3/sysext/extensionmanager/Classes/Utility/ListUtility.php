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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Event\PackagesMayHaveChangedEvent;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Enum\ExtensionType;

/**
 * Utility for dealing with extension list related functions
 *
 * @TODO: Refactor this API class:
 * - The methods depend on each other, they take each others result, that could be done internally
 * - There is no good wording to distinguish existing and loaded extensions
 * - The name 'listUtility' is not good, the methods could be moved to some 'extensionInformationUtility', or a repository?
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
class ListUtility implements SingletonInterface
{
    /**
     * @var EmConfUtility
     */
    protected $emConfUtility;

    /**
     * @var ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var ?array<string, array<string, scalar>>
     */
    protected $availableExtensions;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DependencyUtility
     */
    protected $dependencyUtility;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectEmConfUtility(EmConfUtility $emConfUtility)
    {
        $this->emConfUtility = $emConfUtility;
    }

    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    public function injectDependencyUtility(DependencyUtility $dependencyUtility)
    {
        $this->dependencyUtility = $dependencyUtility;
    }

    /**
     * Returns the list of available, but not necessarily loaded extensions
     *
     * @return array[] All extensions with info
     */
    public function getAvailableExtensions(string $filter = ''): array
    {
        if ($this->availableExtensions === null) {
            $this->availableExtensions = [];
            $this->eventDispatcher->dispatch(new PackagesMayHaveChangedEvent());
            foreach ($this->packageManager->getAvailablePackages() as $package) {
                if (!$package->getPackageMetaData()->isExtensionType()) {
                    continue;
                }
                $installationType = $this->getInstallTypeForPackage($package);
                if ($filter === '' || $filter === $installationType) {
                    $version = $package->getPackageMetaData()->getVersion();
                    $icon = $package->getPackageIcon();
                    $extensionData = [
                        'packagePath' => $package->getPackagePath(),
                        'type' => $installationType,
                        'key' => $package->getPackageKey(),
                        'version' => $version,
                        'state' => str_starts_with($version, 'dev-') ? 'alpha' : 'stable',
                        'icon' => $icon ? PathUtility::getAbsoluteWebPath($package->getPackagePath() . $icon) : '',
                        'title' => $package->getPackageMetaData()->getTitle(),
                    ];
                    $this->availableExtensions[$package->getPackageKey()] = $extensionData;
                }
            }
        }

        return $this->availableExtensions;
    }

    /**
     * Reset and reload the available extensions
     */
    public function reloadAvailableExtensions(): void
    {
        $this->availableExtensions = null;
        $this->packageManager->scanAvailablePackages();
        $this->getAvailableExtensions();
    }

    public function getExtension(string $extensionKey): PackageInterface
    {
        return $this->packageManager->getPackage($extensionKey);
    }

    /**
     * Returns "System" or "Local" based on extension position in filesystem.
     */
    protected function getInstallTypeForPackage(PackageInterface $package): string
    {
        if (Environment::isComposerMode()) {
            return $package->getPackageMetaData()->isFrameworkType()
                ? ExtensionType::System->value
                : ExtensionType::Local->value;
        }
        if (str_starts_with($package->getPackagePath(), Environment::getFrameworkBasePath())) {
            return ExtensionType::System->value;
        }
        if (str_starts_with($package->getPackagePath(), Environment::getExtensionsPath())) {
            return ExtensionType::Local->value;
        }
        return '';
    }

    /**
     * Enrich the output of getAvailableExtensions() with an array key installed = 1 if an extension is loaded.
     */
    public function getAvailableAndInstalledExtensions(array $availableExtensions): array
    {
        foreach ($this->packageManager->getActivePackages() as $extKey => $_) {
            if (isset($availableExtensions[$extKey])) {
                $availableExtensions[$extKey]['installed'] = true;
            }
        }
        return $availableExtensions;
    }

    /**
     * Adds the information from the emconf array to the extension information
     */
    public function enrichExtensionsWithEmConfInformation(array $extensions): array
    {
        foreach ($extensions as $extensionKey => $properties) {
            $emConf = $this->emConfUtility->includeEmConf($extensionKey, $properties['packagePath'] ?? '');
            if (!is_array($emConf)) {
                continue;
            }
            $extensions[$extensionKey] = array_merge($emConf, $properties);
            $extensions[$extensionKey]['state'] = $emConf['state'] ?? $extensions[$extensionKey]['state'] ?? 'stable';
        }
        return $extensions;
    }

    /**
     * Adds the information from the emconf array and TER to the extension information
     */
    public function enrichExtensionsWithEmConfAndTerInformation(array $extensions): array
    {
        $extensions = $this->enrichExtensionsWithEmConfInformation($extensions);
        foreach ($extensions as $extensionKey => $properties) {
            $terObject = $this->getExtensionTerData($extensionKey, $properties['version'] ?? '');
            if ($terObject === null) {
                continue;
            }
            $extensions[$extensionKey]['terObject'] = $terObject;
            $extensions[$extensionKey]['remote'] = $terObject->getRemoteIdentifier();
            $extensions[$extensionKey]['updateAvailable'] = false;
            $extensions[$extensionKey]['updateToVersion'] = null;

            $extensionToUpdate = $this->getUpdateableVersion($terObject);
            if ($extensionToUpdate === null) {
                continue;
            }
            $extensions[$extensionKey]['updateAvailable'] = true;
            $extensions[$extensionKey]['updateToVersion'] = $extensionToUpdate;
        }
        return $extensions;
    }

    /**
     * Tries to find given extension with given version in TER data.
     * If extension is found but not the given version, we return TER data from highest version with version data set to
     * given one.
     *
     * @param string $extensionKey Key of the extension
     * @param string $version String representation of version number
     * @return Extension|null Extension TER object or NULL if nothing found
     */
    protected function getExtensionTerData(string $extensionKey, string $version): ?Extension
    {
        $terObject = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionKey, $version);
        if (!$terObject instanceof Extension) {
            // Version unknown in TER data, try to find extension
            $terObject = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
            if ($terObject instanceof Extension) {
                // Found in TER now, set version information to the known ones, so we can look if there is a newer one
                // Use a cloned object, otherwise wrong information is stored in persistenceManager
                $terObject = clone $terObject;
                $terObject->setVersion($version);
                $terObject->setIntegerVersion(
                    VersionNumberUtility::convertVersionNumberToInteger($terObject->getVersion())
                );
            } else {
                $terObject = null;
            }
        }

        return $terObject;
    }

    /**
     * Gets all available and installed extension with additional information
     * from em_conf and TER (if available)
     */
    public function getAvailableAndInstalledExtensionsWithAdditionalInformation(string $filter = ''): array
    {
        $availableExtensions = $this->getAvailableExtensions($filter);
        $availableAndInstalledExtensions = $this->getAvailableAndInstalledExtensions($availableExtensions);
        return $this->enrichExtensionsWithEmConfAndTerInformation($availableAndInstalledExtensions);
    }

    /**
     * Returns the updateable version for an extension which also resolves dependencies.
     *
     * @return Extension|null null if no update available otherwise latest possible update
     */
    protected function getUpdateableVersion(Extension $extensionData): ?Extension
    {
        // Only check for update for TER extensions
        $version = $extensionData->getIntegerVersion();
        $extensionUpdates = $this->extensionRepository->findByVersionRangeAndExtensionKeyOrderedByVersion(
            $extensionData->getExtensionKey(),
            $version,
            0,
            false
        );
        if ($extensionUpdates->count() > 0) {
            foreach ($extensionUpdates as $extensionUpdate) {
                /** @var Extension $extensionUpdate */
                $this->dependencyUtility->checkDependencies($extensionUpdate);
                if (!$this->dependencyUtility->hasDependencyErrors()) {
                    return $extensionUpdate;
                }
            }
        }
        return null;
    }
}
