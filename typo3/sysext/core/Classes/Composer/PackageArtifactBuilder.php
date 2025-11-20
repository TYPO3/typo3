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
 * @template packageMap of array<int, array{PackageInterface, string, non-empty-string}>
 * @template IOMessage of array{severity: 'title'|'info'|'warning', verbosity: int, message: string}
 *
 * @internal This class is an implementation detail and does not represent public API
 */
class PackageArtifactBuilder extends PackageManager implements InstallerScript
{
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
        $io = $event->getIO();
        $this->event = $event;
        $this->config = Config::load($this->event->getComposer(), $io);
        $this->fileSystem = new Filesystem();
        $composer = $this->event->getComposer();
        $basePath = $this->config->get('base-dir');
        $this->packagesBasePath = $basePath . '/';
        foreach ($this->extractPackageMapFromComposer() as [$composerPackage, $path, $extensionKey]) {
            $packagePath = PathUtility::sanitizeTrailingSeparator($path);
            $package = new Package($this, $extensionKey, $packagePath, true);
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
     *
     * @return packageMap
     */
    private function extractPackageMapFromComposer(): array
    {
        $composer = $this->event->getComposer();
        $rootPackage = $composer->getPackage();
        $autoLoadGenerator = $composer->getAutoloadGenerator();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $usedExtensionKeys = [];

        return array_map(
            function (array $packageAndPath) use ($rootPackage, &$usedExtensionKeys): array {
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
                    // The root package's path is the Composer base dir
                    $packagePath = $this->config->get('base-dir');
                }
                // Add extension key to the package map for later reference
                return [$composerPackage, $packagePath, $extensionKey];
            },
            array_filter(
                $autoLoadGenerator->buildPackageMap($composer->getInstallationManager(), $rootPackage, $localRepo->getCanonicalPackages()),
                function (array $packageAndPath): bool {
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
    }
}
