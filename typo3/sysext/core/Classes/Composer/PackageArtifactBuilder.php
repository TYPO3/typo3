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

namespace TYPO3\CMS\Core\Composer;

use Composer\Package\PackageInterface;
use Composer\Repository\PlatformRepository;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Composer\Plugin\Util\ExtensionKeyResolver;
use TYPO3\CMS\Core\Package\Cache\ComposerPackageArtifact;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageManifestException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageStateException;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * The builder is a subclass of PackageManager as it shares much of its functionality.
 * It evaluates the installed Composer packages for applicable TYPO3 extensions.
 * All Composer packages will be discovered, that have an extra.typo3/cms definition in their composer.json.
 * All ext_emconf.php files will be completely ignored in this context, which means all extensions
 * are required to have a composer.json file, which works out naturally with a Composer setup.
 *
 * @internal This class is an implementation detail and does not represent public API
 */
class PackageArtifactBuilder extends PackageManager implements InstallerScript
{
    private const LEGACY_EXTENSION_INSTALL_PATH = '/typo3conf/ext';

    /**
     * @var Event $event
     */
    private $event;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var Filesystem $fileSystem
     */
    private $fileSystem;

    /**
     * Array of Composer package names (as array key) that are installed by Composer but have no relation to TYPO3 extension API
     * @var array
     */
    private $availableComposerPackageKeys = [];

    public function __construct()
    {
        // Disable path determination with Environment class, which is not initialized here
        parent::__construct(new DependencyOrderingService(), '', '');
    }

    protected function isComposerDependency(string $packageName): bool
    {
        return PlatformRepository::isPlatformPackage($packageName) || ($this->availableComposerPackageKeys[$packageName] ?? false);
    }

    /**
     * Entry method called in Composer post-dump-autoload hook
     *
     * @throws InvalidPackageKeyException
     * @throws InvalidPackageManifestException
     * @throws InvalidPackagePathException
     * @throws InvalidPackageStateException
     */
    public function run(Event $event): bool
    {
        $this->event = $event;
        $this->config = Config::load($this->event->getComposer(), $this->event->getIO());
        $this->fileSystem = new Filesystem();
        $composer = $this->event->getComposer();
        $basePath = $this->config->get('base-dir');
        $this->packagesBasePath = $basePath . '/';
        foreach ($this->extractPackageMapFromComposer() as [$composerPackage, $path, $extensionKey]) {
            $packagePath = PathUtility::sanitizeTrailingSeparator($path);
            $package = new Package($this, $extensionKey, $packagePath, true);
            $this->setTitleFromExtEmConf($package);
            $package->makePathRelative($this->fileSystem, $basePath);
            $package->getPackageMetaData()->setVersion($composerPackage->getPrettyVersion());
            $this->registerPackage($package);
        }
        $this->sortPackagesAndConfiguration();
        $cacheIdentifier = md5(serialize($composer->getLocker()->getLockData()) . $this->event->isDevMode());
        $this->setPackageCache(new ComposerPackageArtifact($composer->getConfig()->get('vendor-dir') . '/typo3', $this->fileSystem, $cacheIdentifier));
        $this->saveToPackageCache();

        return true;
    }

    /**
     * Sets a title for the package from ext_emconf.php in case this file exists
     * @todo deprecate or remove in TYPO3 v12
     */
    private function setTitleFromExtEmConf(Package $package): void
    {
        $emConfPath = $package->getPackagePath() . '/ext_emconf.php';
        if (file_exists($emConfPath)) {
            $_EXTKEY = $package->getPackageKey();
            $EM_CONF = null;
            include $emConfPath;
            if (!empty($EM_CONF[$_EXTKEY]['title'])) {
                $package->getPackageMetaData()->setTitle($EM_CONF[$_EXTKEY]['title']);
            }
        }
    }

    /**
     * Sorts all TYPO3 extension packages by dependency defined in composer.json file
     */
    private function sortPackagesAndConfiguration(): void
    {
        $packagesWithDependencies = $this->resolvePackageDependencies($this->packages);
        // Sort the packages by key at first, so we get a stable sorting of "equivalent" packages afterwards
        ksort($packagesWithDependencies);
        $sortedPackageKeys = $this->sortPackageStatesConfigurationByDependency($packagesWithDependencies);
        $this->packageStatesConfiguration = [];
        $sortedPackages = [];
        foreach ($sortedPackageKeys as $packageKey) {
            $sortedPackages[$packageKey] = $this->packages[$packageKey];
            // The artifact does not need path information, so it is kept empty
            // The keys must be present, though because the PackageManager implies than a
            // package is active by this configuration array
            $this->packageStatesConfiguration['packages'][$packageKey] = [];
        }
        $this->packages = $sortedPackages;
        $this->packageStatesConfiguration['version'] = 5;
    }

    /**
     * Fetch a map of all installed packages and filter them, when they apply
     * for TYPO3.
     */
    private function extractPackageMapFromComposer(): array
    {
        $composer = $this->event->getComposer();
        $rootPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $usedExtensionKeys = [];

        $installedTypo3Packages = array_map(
            function (array $packageAndPath) use ($rootPackage, &$usedExtensionKeys) {
                [$composerPackage, $packagePath] = $packageAndPath;
                $packageName = $composerPackage->getName();
                $packagePath = GeneralUtility::fixWindowsFilePath($packagePath);
                try {
                    $extensionKey = ExtensionKeyResolver::resolve($composerPackage);
                } catch (\Throwable $e) {
                    if (str_starts_with($composerPackage->getType(), 'typo3-cms-')) {
                        // This means we have a package of type extension, and it does not have the extension key set
                        // This only happens since version > 4.0 of the installer and must be propagated to become user facing
                        throw $e;
                    }
                    // In case we can not otherwise determine the extension key, we take the composer name
                    $extensionKey = $packageName;
                }
                if (isset($usedExtensionKeys[$extensionKey])) {
                    throw new \UnexpectedValueException(
                        sprintf(
                            'Package with the name "%s" registered extension key "%s", but this key was already set by package with the name "%s"',
                            $packageName,
                            $extensionKey,
                            $usedExtensionKeys[$extensionKey]
                        ),
                        1638880941
                    );
                }
                $usedExtensionKeys[$extensionKey] = $packageName;
                unset($this->availableComposerPackageKeys[$packageName]);
                $this->composerNameToPackageKeyMap[$packageName] = $extensionKey;
                if ($composerPackage === $rootPackage) {
                    return $this->handleRootPackage($rootPackage, $extensionKey);
                }
                // Add extension key to the package map for later reference
                return [$composerPackage, $packagePath, $extensionKey];
            },
            array_filter(
                $autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $rootPackage, $localRepo->getCanonicalPackages()),
                function (array $packageAndPath) {
                    /** @var PackageInterface $composerPackage */
                    [$composerPackage] = $packageAndPath;
                    // Filter all Composer packages without typo3/cms definition, but keep all
                    // package names, to be able to ignore Composer only dependencies when ordering the packages
                    $this->availableComposerPackageKeys[$composerPackage->getName()] = true;
                    foreach ($composerPackage->getReplaces() as $link) {
                        $this->availableComposerPackageKeys[$link->getTarget()] = true;
                    }
                    return isset($composerPackage->getExtra()['typo3/cms']);
                }
            )
        );

        $this->publishResources($installedTypo3Packages);

        return $installedTypo3Packages;
    }

    /**
     * TYPO3 can not handle public resources of extensions, that do not reside in typo3conf/ext
     * Therefore, if the root package is of type typo3-cms-extension and has the folder Resources/Public,
     * we fake the path of this extension to be in typo3conf/ext
     *
     * For root packages of other types or extensions without public resources, no symlink is created
     * and the package path stays to be the composer root path.
     *
     * If extensions are installed into vendor folder, linking is skipped, because public resources
     * are published anyway.
     * Linking could be skipped altogether, but is kept to stay consistent:
     * extensions in typo3conf/ext: root package is linked
     * extensions in vendor: public resources of all packages are published
     * @todo: remove this method in TYPO3 v12
     *
     * @param PackageInterface $rootPackage
     * @param string $extensionKey
     */
    private function handleRootPackage(PackageInterface $rootPackage, string $extensionKey): array
    {
        $baseDir = $this->config->get('base-dir');
        $composer = $this->event->getComposer();
        if ($rootPackage->getType() !== 'typo3-cms-extension'
            || !file_exists($baseDir . '/Resources/Public/')
        ) {
            return [$rootPackage, $baseDir, $extensionKey];
        }
        $typo3ExtensionInstallPath = $composer->getInstallationManager()->getInstaller('typo3-cms-extension')->getInstallPath($rootPackage);
        if (!str_contains($typo3ExtensionInstallPath, self::LEGACY_EXTENSION_INSTALL_PATH)) {
            return [$rootPackage, $baseDir, $extensionKey];
        }
        if (!file_exists($typo3ExtensionInstallPath) && !$this->fileSystem->isSymlinkedDirectory($typo3ExtensionInstallPath)) {
            $this->fileSystem->ensureDirectoryExists(dirname($typo3ExtensionInstallPath));
            $this->fileSystem->relativeSymlink($baseDir, $typo3ExtensionInstallPath);
        }
        if (realpath($baseDir) !== realpath($typo3ExtensionInstallPath)) {
            $this->event->getIO()->warning('The root package is of type "typo3-cms-extension" and has public resources, but could not be linked to "' . self::LEGACY_EXTENSION_INSTALL_PATH . '" directory, because target directory already exits.');
        }

        return [$rootPackage, $typo3ExtensionInstallPath, $extensionKey];
    }

    private function publishResources(array $installedTypo3Packages): void
    {
        $baseDir = $this->config->get('base-dir');
        foreach ($installedTypo3Packages as [$composerPackage, $path, $extensionKey]) {
            $fileSystemResourcesPath = $path . '/Resources/Public';
            // skip non-composer installation extension paths, or if resource paths does not exist.
            if (str_ends_with($path, self::LEGACY_EXTENSION_INSTALL_PATH . '/' . $extensionKey) || !file_exists($fileSystemResourcesPath)) {
                continue;
            }
            $relativePath = substr($fileSystemResourcesPath, strlen($baseDir));
            [$relativePrefix] = explode('Resources/Public', $relativePath);
            $publicResourcesPath = $this->fileSystem->normalizePath($this->config->get('web-dir') . '/_assets/' . md5($relativePrefix));
            $this->fileSystem->ensureDirectoryExists(dirname($publicResourcesPath));
            if (Platform::isWindows()) {
                $this->ensureJunctionExists($fileSystemResourcesPath, $publicResourcesPath);
            } else {
                $this->ensureSymlinkExists($fileSystemResourcesPath, $publicResourcesPath);
            }
        }
    }

    private function ensureJunctionExists(string $target, string $junction): void
    {
        if (!$this->fileSystem->isJunction($junction)) {
            // Cleanup a possible symlink that might have been installed by ourselves prior to #98434
            // Note: Unprivileged deletion of symlinks is allowed, even if they were created by a
            // privileged user
            if (is_link($junction)) {
                $this->fileSystem->unlink($junction);
            }
            $this->fileSystem->junction($target, $junction);
        }
    }

    private function ensureSymlinkExists(string $target, string $link): void
    {
        if (!$this->fileSystem->isSymlinkedDirectory($link)) {
            $this->fileSystem->relativeSymlink($target, $link);
        }
    }
}
