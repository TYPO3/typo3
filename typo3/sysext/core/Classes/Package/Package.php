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

namespace TYPO3\CMS\Core\Package;

use Composer\Util\Filesystem;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageKeyException;
use TYPO3\CMS\Core\Package\Exception\InvalidPackagePathException;
use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;

/**
 * A Package representing the details of an extension and/or a composer package
 */
class Package implements PackageInterface
{
    /**
     * If this package is part of factory default, it will be activated
     * during first installation.
     *
     * @var bool
     */
    protected $partOfFactoryDefault = false;

    /**
     * If this package is part of minimal usable system, it will be
     * activated if PackageStates is created from scratch.
     *
     * @var bool
     */
    protected $partOfMinimalUsableSystem = false;

    /**
     * ServiceProvider class name. This property and the corresponding
     * composer.json setting is internal and therefore no api (yet).
     *
     * @var string|null
     * @internal
     */
    protected $serviceProvider;

    /**
     * Unique key of this package.
     * @var string
     */
    protected $packageKey;

    /**
     * Full path to this package's main directory
     * @var string
     */
    protected $packagePath;

    /**
     * @var bool
     */
    protected $isRelativePackagePath = false;

    /**
     * If this package is protected and therefore cannot be deactivated or deleted
     * @var bool
     */
    protected $protected = false;

    /**
     * @var \stdClass
     */
    protected $composerManifest;

    /**
     * Meta information about this package
     * @var MetaData
     */
    protected $packageMetaData;

    /**
     * Constructor
     *
     * @param PackageManager $packageManager the package manager which knows this package
     * @param string $packageKey Key of this package
     * @param string $packagePath Absolute path to the location of the package's composer manifest
     * @param bool $ignoreExtEmConf When set ext_emconf.php is ignored when building composer manifest
     * @throws Exception\InvalidPackageManifestException if no composer manifest file could be found
     * @throws InvalidPackageKeyException if an invalid package key was passed
     * @throws InvalidPackagePathException if an invalid package path was passed
     */
    public function __construct(PackageManager $packageManager, string $packageKey, string $packagePath, bool $ignoreExtEmConf = false)
    {
        if (!$packageManager->isPackageKeyValid($packageKey)) {
            throw new InvalidPackageKeyException('"' . $packageKey . '" is not a valid package key.', 1217959511);
        }
        if (!(@is_dir($packagePath) || (is_link($packagePath) && is_dir($packagePath)))) {
            throw new InvalidPackagePathException(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631890);
        }
        if (substr($packagePath, -1, 1) !== '/') {
            throw new InvalidPackagePathException(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633722);
        }
        $this->packageKey = $packageKey;
        $this->packagePath = $packagePath;
        $this->composerManifest = $packageManager->getComposerManifest($this->packagePath, $ignoreExtEmConf);
        $this->loadFlagsFromComposerManifest();
        $this->createPackageMetaData($packageManager);
    }

    /**
     * Loads package management related flags from the "extra:typo3/cms:Package" section
     * of extensions composer.json files into local properties
     */
    protected function loadFlagsFromComposerManifest()
    {
        $extraFlags = $this->getValueFromComposerManifest('extra');
        if ($extraFlags !== null && isset($extraFlags->{'typo3/cms'}->{'Package'})) {
            foreach ($extraFlags->{'typo3/cms'}->{'Package'} as $flagName => $flagValue) {
                if (property_exists($this, $flagName)) {
                    $this->{$flagName} = $flagValue;
                }
            }
        }
    }

    /**
     * Creates the package meta data object of this package.
     *
     * @param PackageManager $packageManager
     */
    protected function createPackageMetaData(PackageManager $packageManager)
    {
        $this->packageMetaData = new MetaData($this->getPackageKey());
        $description = (string)$this->getValueFromComposerManifest('description');
        $this->packageMetaData->setDescription($description);
        $this->packageMetaData->setTitle($this->getValueFromComposerManifest('title') ?? $description);
        $this->packageMetaData->setVersion((string)$this->getValueFromComposerManifest('version'));
        $this->packageMetaData->setPackageType((string)$this->getValueFromComposerManifest('type'));
        $requirements = $this->getValueFromComposerManifest('require');
        if ($requirements !== null) {
            foreach ($requirements as $requirement => $version) {
                $packageKey = $packageManager->getPackageKeyFromComposerName($requirement);
                $constraint = new PackageConstraint(MetaData::CONSTRAINT_TYPE_DEPENDS, $packageKey);
                $this->packageMetaData->addConstraint($constraint);
            }
        }
        $suggestions = $this->getValueFromComposerManifest('suggest');
        if ($suggestions !== null) {
            foreach ($suggestions as $suggestion => $version) {
                $packageKey = $packageManager->getPackageKeyFromComposerName($suggestion);
                $constraint = new PackageConstraint(MetaData::CONSTRAINT_TYPE_SUGGESTS, $packageKey);
                $this->packageMetaData->addConstraint($constraint);
            }
        }
    }

    /**
     * Get the Service Provider class name
     *
     * @return string
     * @internal
     */
    public function getServiceProvider(): string
    {
        return $this->serviceProvider ?? PseudoServiceProvider::class;
    }

    /**
     * @return bool
     * @internal
     */
    public function isPartOfFactoryDefault()
    {
        return $this->partOfFactoryDefault;
    }

    /**
     * @return bool
     * @internal
     */
    public function isPartOfMinimalUsableSystem()
    {
        return $this->partOfMinimalUsableSystem;
    }

    /**
     * Returns the package key of this package.
     *
     * @return string
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->protected;
    }

    /**
     * Sets the protection flag of the package
     *
     * @param bool $protected TRUE if the package should be protected, otherwise FALSE
     */
    public function setProtected($protected)
    {
        $this->protected = (bool)$protected;
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     */
    public function getPackagePath()
    {
        if (!$this->isRelativePackagePath) {
            return $this->packagePath;
        }
        $this->isRelativePackagePath = false;

        return $this->packagePath = Environment::getComposerRootPath() . '/' . $this->packagePath;
    }

    /**
     * Used by PackageArtifactBuilder to make package path relative
     *
     * @param Filesystem $filesystem
     * @param string $composerRootPath
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
     * @return MetaData
     * @internal
     */
    public function getPackageMetaData()
    {
        return $this->packageMetaData;
    }

    /**
     * Returns an array of packages this package replaces
     *
     * @return array
     * @internal
     */
    public function getPackageReplacementKeys()
    {
        // The cast to array is required since the manifest returns data with type mixed
        return (array)$this->getValueFromComposerManifest('replace') ?: [];
    }

    /**
     * Returns contents of Composer manifest - or part there of if a key is given.
     *
     * @param string $key Optional. Only return the part of the manifest indexed by 'key'
     * @return mixed|null
     * @see json_decode for return values
     * @internal
     */
    public function getValueFromComposerManifest($key = null)
    {
        if ($key === null) {
            return $this->composerManifest;
        }

        if (isset($this->composerManifest->{$key})) {
            $value = $this->composerManifest->{$key};
        } else {
            $value = null;
        }
        return $value;
    }
}
