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

namespace TYPO3\CMS\Core\Package;

use Composer\Util\Filesystem;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;
use TYPO3\CMS\Core\Package\Resource\ResourceCollection;
use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;

/**
 * A Package representing the details of an extension and/or a composer package
 */
class Package implements PackageInterface
{
    /**
     * If this package is part of factory default, it will be activated
     * during first installation.
     */
    protected bool $partOfFactoryDefault = false;

    /**
     * If this package is part of minimal usable system, it will be
     * activated if PackageStates is created from scratch.
     */
    protected bool $partOfMinimalUsableSystem = false;

    /**
     * ServiceProvider class name. This property and the corresponding
     * composer.json setting is internal and therefore no api (yet).
     *
     * @internal
     */
    protected ?string $serviceProvider;

    /**
     * Composer Packages this package provides in classic mode
     * The composer.json property is public, the implementation
     * here is private
     *
     * @internal
     */
    protected array $providesPackages = [];

    /**
     * Unique key of this package.
     */
    protected string $packageKey;

    /**
     * Full path to this package's main directory
     */
    protected string $packagePath;

    protected bool $isRelativePackagePath = false;

    /**
     * If this package is protected and therefore cannot be deactivated or deleted
     */
    protected bool $protected = false;

    protected ?\stdClass $composerManifest;

    /**
     * Meta information about this package
     */
    protected MetaData $packageMetaData;

    protected ResourceCollectionInterface $resources;

    /**
     * @param PackageManager $packageManager the package manager which knows this package
     * @param string $packageKey Key of this package
     * @param string $packagePath Absolute path to the location of the package's composer manifest
     * @param bool $isBuildingPackageArtifact When set we are in Composer mode and building the package artifact
     * @throws Exception\InvalidPackageManifestException if no composer manifest file could be found
     * @throws InvalidPackageKeyException if an invalid package key was passed
     * @throws InvalidPackagePathException if an invalid package path was passed
     */
    public function __construct(PackageManager $packageManager, string $packageKey, string $packagePath, bool $isBuildingPackageArtifact = false)
    {
        if (!$packageManager->isPackageKeyValid($packageKey)) {
            throw new InvalidPackageKeyException('"' . $packageKey . '" is not a valid package key.', 1217959511);
        }
        if (!(@is_dir($packagePath) || (is_link($packagePath) && is_dir($packagePath)))) {
            throw new InvalidPackagePathException(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631890);
        }
        if (!str_ends_with($packagePath, '/')) {
            throw new InvalidPackagePathException(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633722);
        }
        $this->packageKey = $packageKey;
        $this->packagePath = $packagePath;
        $this->composerManifest = $packageManager->getComposerManifest($this->packagePath, $isBuildingPackageArtifact);
        $this->loadFlagsFromComposerManifest($isBuildingPackageArtifact);
        $this->createPackageMetaData($packageManager, $isBuildingPackageArtifact);
        $this->createResources();
    }

    /**
     * Loads package management related flags from the "extra:typo3/cms:Package" section
     * of extensions composer.json files into local properties
     */
    protected function loadFlagsFromComposerManifest(bool $ignoreProvidesPackages = false): void
    {
        $extraFlags = $this->getValueFromComposerManifest('extra');
        if ($extraFlags !== null && isset($extraFlags->{'typo3/cms'}->{'Package'})) {
            foreach ($extraFlags->{'typo3/cms'}->{'Package'} as $flagName => $flagValue) {
                if ($flagName === 'providesPackages') {
                    if ($ignoreProvidesPackages) {
                        continue;
                    }
                    $flagValue = (array)$flagValue;
                }
                if (property_exists($this, $flagName)) {
                    $this->{$flagName} = $flagValue;
                }
            }
        }
    }

    /**
     * Creates the package meta data object of this package.
     */
    protected function createPackageMetaData(PackageManager $packageManager, bool $isBuildingPackageArtifact = false): void
    {
        $this->packageMetaData = new MetaData($this->getPackageKey());
        $description = (string)$this->getValueFromComposerManifest('description');
        $descriptionParts = explode(' - ', $description, 2);
        if (count($descriptionParts) === 2) {
            $title = $descriptionParts[0];
            $description = $descriptionParts[1];
        } else {
            $title = $description;
        }
        $manifest = $this->getValueFromComposerManifest();
        $this->packageMetaData->setTitle($title);
        $this->packageMetaData->setDescription($description);
        $this->packageMetaData->setPackageType($manifest->type ?? '');
        $isFrameworkPackage = $this->packageMetaData->isFrameworkType();
        $version = $manifest->extra->{'typo3/cms'}->{'version'} ?? $manifest->version ?? '1.0.0+no-version-set';
        if ($isFrameworkPackage) {
            $version = str_replace('-dev', '', (new Typo3Version())->getVersion());
        }
        $this->packageMetaData->setVersion($version);
        $requirements = $manifest->require ?? null;
        if ($requirements !== null) {
            foreach ($requirements as $packageName => $versionConstraints) {
                if ($this->ignoreDependencyInPackageConstraint($packageName, $packageManager, $isBuildingPackageArtifact)) {
                    continue;
                }
                $this->packageMetaData->addConstraint(
                    new PackageConstraint(
                        constraintType: MetaData::CONSTRAINT_TYPE_DEPENDS,
                        value: $packageName,
                        versionConstraints: $versionConstraints,
                    )
                );
            }
        }
        $suggestions = $manifest->suggest ?? null;
        if ($suggestions !== null) {
            foreach ($suggestions as $packageName => $description) {
                if ($this->ignoreDependencyInPackageConstraint($packageName, $packageManager, $isBuildingPackageArtifact)) {
                    continue;
                }
                $constraint = new PackageConstraint(MetaData::CONSTRAINT_TYPE_SUGGESTS, $packageName);
                $this->packageMetaData->addConstraint($constraint);
            }
        }
        $conflicts = $manifest->conflict ?? null;
        if ($conflicts !== null) {
            foreach ($conflicts as $packageName => $versionConstraints) {
                if ($this->ignoreDependencyInPackageConstraint($packageName, $packageManager, $isBuildingPackageArtifact)) {
                    continue;
                }
                $this->packageMetaData->addConstraint(
                    new PackageConstraint(
                        constraintType: MetaData::CONSTRAINT_TYPE_CONFLICTS,
                        value: $packageName,
                        versionConstraints: $versionConstraints,
                    )
                );
            }
        }
    }

    /**
     * In Composer mode, $packageManager->isComposerDependency() will always be true already for composer dependencies,
     * since all packages are known.
     * In Classic mode providesPackages is evaluated for third party extensions
     * while for framework packages only dependencies to other framework packages are tracked
     */
    private function ignoreDependencyInPackageConstraint(string $packageName, PackageManager $packageManager, bool $isBuildingPackageArtifact): bool
    {
        $isKnownComposerDependency = $packageManager->isComposerDependency($packageName);
        if ($isBuildingPackageArtifact) {
            return $isKnownComposerDependency;
        }
        return $isKnownComposerDependency
            // provided Composer packages as specified by third party extensions (loaded on demand in classic mode)
            || isset($this->providesPackages[$packageName])
            || ($this->packageMetaData->isFrameworkType() && !$packageManager->isFrameworkPackage($packageName))
        ;
    }

    protected function createResources(): void
    {
        $relativeIconPath = $this->getPackageIcon();
        $iconIdentifier = $relativeIconPath !== null ? sprintf(
            'PKG:%s:%s',
            $this->getValueFromComposerManifest('name') ?? $this->getPackageKey(),
            $relativeIconPath,
        ) : null;
        $resourceDefinitionClosure = $this->getResourceDefinitions(
            __DIR__ . '/../../Configuration/DefaultPackageResources.php'
        );
        $customResourceDefinitionClosure = $this->getResourceDefinitions(
            $this->getPackagePath() . 'Configuration/Resources.php'
        );
        $resourceDefinitions = array_merge(
            $resourceDefinitionClosure($this),
            $customResourceDefinitionClosure === null ? [] : $customResourceDefinitionClosure($this),
        );
        $this->resources = new ResourceCollection(
            $resourceDefinitions,
            $iconIdentifier,
        );
    }

    protected function getResourceDefinitions(string $configPath): ?\Closure
    {
        if (!file_exists($configPath)) {
            return null;
        }
        return (static function ($configPath) {
            return require $configPath;
        })($configPath);
    }

    public function getResources(): ResourceCollectionInterface
    {
        return $this->resources;
    }

    /**
     * Get the Service Provider class name
     *
     * @internal
     */
    public function getServiceProvider(): string
    {
        return $this->serviceProvider ?? PseudoServiceProvider::class;
    }

    /**
     * @internal
     */
    public function isPartOfFactoryDefault(): bool
    {
        return $this->partOfFactoryDefault;
    }

    /**
     * @internal
     */
    public function isPartOfMinimalUsableSystem(): bool
    {
        return $this->partOfMinimalUsableSystem;
    }

    /**
     * Returns the package key of this package.
     */
    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     */
    public function isProtected(): bool
    {
        return $this->protected;
    }

    /**
     * Sets the protection flag of the package
     *
     * @param bool $protected TRUE if the package should be protected, otherwise FALSE
     */
    public function setProtected(bool $protected): void
    {
        $this->protected = (bool)$protected;
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     */
    public function getPackagePath(): string
    {
        if (!$this->isRelativePackagePath) {
            return $this->packagePath;
        }
        $this->isRelativePackagePath = false;

        return $this->packagePath = Environment::getProjectPath() . '/' . $this->packagePath;
    }

    /**
     * Used by PackageArtifactBuilder to make package path relative
     *
     * @internal
     */
    public function makePathRelative(Filesystem $filesystem, string $composerRootPath): void
    {
        $this->isRelativePackagePath = true;
        $this->packagePath = ($composerRootPath . '/') === $this->packagePath ? '' : $filesystem->findShortestPath($composerRootPath, $this->packagePath, true) . '/';
    }

    /**
     * Returns the package meta data object of this package.
     *
     * @internal
     */
    public function getPackageMetaData(): MetaData
    {
        return $this->packageMetaData;
    }

    /**
     * Returns an array of packages this package replaces
     * @internal
     */
    public function getPackageReplacementKeys(): array
    {
        // The cast to array is required since the manifest returns data with type mixed
        return (array)$this->getValueFromComposerManifest('replace') ?: [];
    }

    /**
     * Returns contents of Composer manifest - or part there of if a key is given.
     *
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @see json_decode for return values
     * @internal
     */
    public function getValueFromComposerManifest($key = null): mixed
    {
        if ($key === null) {
            return $this->composerManifest;
        }
        return $this->composerManifest->{$key} ?? null;
    }

    /**
     * Find package icon location relative to the package path
     */
    public function getPackageIcon(): ?string
    {
        $resourcePath = 'Resources/Public/Icons/Extension.';
        foreach (['svg', 'png', 'gif'] as $fileExtension) {
            if (file_exists($this->getPackagePath() . $resourcePath . $fileExtension)) {
                return $resourcePath . $fileExtension;
            }
        }
        return null;
    }
}
