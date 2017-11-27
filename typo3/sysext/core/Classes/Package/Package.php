<?php
namespace TYPO3\CMS\Core\Package;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A Package representing the details of an extension and/or a composer package
 * Adapted from FLOW for TYPO3 CMS
 */
class Package implements PackageInterface
{
    /**
     * @var array
     */
    protected $extensionManagerConfiguration = [];

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
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Constructor
     *
     * @param PackageManager $packageManager the package manager which knows this package
     * @param string $packageKey Key of this package
     * @param string $packagePath Absolute path to the location of the package's composer manifest
     * @throws Exception\InvalidPackageKeyException if an invalid package key was passed
     * @throws Exception\InvalidPackagePathException if an invalid package path was passed
     * @throws Exception\InvalidPackageManifestException if no composer manifest file could be found
     */
    public function __construct(PackageManager $packageManager, $packageKey, $packagePath)
    {
        if (!$packageManager->isPackageKeyValid($packageKey)) {
            throw new Exception\InvalidPackageKeyException('"' . $packageKey . '" is not a valid package key.', 1217959511);
        }
        if (!(@is_dir($packagePath) || (is_link($packagePath) && is_dir($packagePath)))) {
            throw new Exception\InvalidPackagePathException(sprintf('Tried to instantiate a package object for package "%s" with a non-existing package path "%s". Either the package does not exist anymore, or the code creating this object contains an error.', $packageKey, $packagePath), 1166631890);
        }
        if (substr($packagePath, -1, 1) !== '/') {
            throw new Exception\InvalidPackagePathException(sprintf('The package path "%s" provided for package "%s" has no trailing forward slash.', $packagePath, $packageKey), 1166633722);
        }
        $this->packageManager = $packageManager;
        $this->packageKey = $packageKey;
        $this->packagePath = $packagePath;
        $this->composerManifest = $packageManager->getComposerManifest($this->packagePath);
        $this->loadFlagsFromComposerManifest();
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
     * @return bool
     */
    public function isPartOfFactoryDefault()
    {
        return $this->partOfFactoryDefault;
    }

    /**
     * @return bool
     */
    public function isPartOfMinimalUsableSystem()
    {
        return $this->partOfMinimalUsableSystem;
    }

    /**
     * Returns the package key of this package.
     *
     * @return string
     * @api
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return bool
     * @api
     */
    public function isProtected()
    {
        return $this->protected;
    }

    /**
     * Sets the protection flag of the package
     *
     * @param bool $protected TRUE if the package should be protected, otherwise FALSE
     * @api
     */
    public function setProtected($protected)
    {
        $this->protected = (bool)$protected;
    }

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     * @api
     */
    public function getPackagePath()
    {
        return $this->packagePath;
    }

    /**
     * Returns the package meta data object of this package.
     *
     * @return MetaData
     */
    public function getPackageMetaData()
    {
        if ($this->packageMetaData === null) {
            $this->packageMetaData = new MetaData($this->getPackageKey());
            $this->packageMetaData->setDescription($this->getValueFromComposerManifest('description'));
            $this->packageMetaData->setVersion($this->getValueFromComposerManifest('version'));
            $requirements = $this->getValueFromComposerManifest('require');
            if ($requirements !== null) {
                foreach ($requirements as $requirement => $version) {
                    $packageKey = $this->packageManager->getPackageKeyFromComposerName($requirement);
                    // dynamically migrate 'cms' dependency to 'core' dependency
                    // see also \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility::convertDependenciesToObjects
                    if ($packageKey === 'cms') {
                        GeneralUtility::deprecationLog('Extension "' . $this->packageKey . '" defines a dependency on ext:cms, which has been removed. Please remove the dependency.');
                        $packageKey = 'core';
                    }
                    $constraint = new MetaData\PackageConstraint(MetaData::CONSTRAINT_TYPE_DEPENDS, $packageKey);
                    $this->packageMetaData->addConstraint($constraint);
                }
            }
            $suggestions = $this->getValueFromComposerManifest('suggest');
            if ($suggestions !== null) {
                foreach ($suggestions as $suggestion => $version) {
                    $packageKey = $this->packageManager->getPackageKeyFromComposerName($suggestion);
                    $constraint = new MetaData\PackageConstraint(MetaData::CONSTRAINT_TYPE_SUGGESTS, $packageKey);
                    $this->packageMetaData->addConstraint($constraint);
                }
            }
        }
        return $this->packageMetaData;
    }

    /**
     * Returns an array of packages this package replaces
     *
     * @return array
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

    /**
     * Added by TYPO3 CMS
     *
     * The package caching serializes package objects.
     * The package manager instance may not be serialized
     * as a fresh instance is created upon every request.
     *
     * This method will be removed once the package is
     * released of the package manager dependency.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = get_class_vars(get_class($this));
        unset($properties['packageManager']);
        return array_keys($properties);
    }

    /**
     * Added by TYPO3 CMS
     *
     * The package caching deserializes package objects.
     * A fresh package manager instance has to be set
     * during bootstrapping.
     *
     * This method will be removed once the package is
     * released of the package manager dependency.
     */
    public function __wakeup()
    {
        if (isset($GLOBALS['TYPO3_currentPackageManager'])) {
            $this->packageManager = $GLOBALS['TYPO3_currentPackageManager'];
        }
    }
}
