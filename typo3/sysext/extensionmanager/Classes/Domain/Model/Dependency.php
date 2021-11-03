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

namespace TYPO3\CMS\Extensionmanager\Domain\Model;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Value Object of a single dependency of an extension
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class Dependency
{
    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var string
     */
    protected $lowestVersion = '';

    /**
     * @var string
     */
    protected $highestVersion = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var array
     */
    protected static $dependencyTypes = [
        'depends',
        'conflicts',
        'suggests',
    ];

    /**
     * @var array
     */
    public static $specialDependencies = [
        'typo3',
        'php',
    ];

    private function __construct(string $identifier, string $type, string $lowestVersion, string $highestVersion)
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->lowestVersion = $lowestVersion;
        $this->highestVersion = $highestVersion;
    }

    /**
     * Use this factory when building dependencies of an extension, like ["depends"]["news"] => '1.0.0-2.6.9'
     *
     * @param string $identifier the extension name or "typo3" or "php" for TYPO3 Core / PHP version constraints
     * @param string $versionConstraint the actual version number. "1.0.0-2.0.0" or "1.0.0" which means "1.0.0 or higher"
     * @param string $dependencyType use "depends", "suggests" or "conflicts".
     * @return self
     * @throws ExtensionManagerException
     */
    public static function createFromEmConf(
        string $identifier,
        string $versionConstraint = '',
        string $dependencyType = 'depends'
    ): self {
        $versionNumbers = VersionNumberUtility::convertVersionsStringToVersionNumbers($versionConstraint);
        $lowest = $versionNumbers[0];
        if (count($versionNumbers) === 2) {
            $highest = $versionNumbers[1];
        } else {
            $highest = '';
        }
        if (!in_array($dependencyType, self::$dependencyTypes, true)) {
            throw new ExtensionManagerException($dependencyType . ' was not a valid dependency type.', 1476122402);
        }
        // dynamically migrate 'cms' dependency to 'core' dependency
        // see also \TYPO3\CMS\Core\Package\Package::getPackageMetaData
        $identifier = strtolower($identifier);
        $identifier = $identifier === 'cms' ? 'core' : $identifier;
        return new self($identifier, $dependencyType, (string)$lowest, (string)$highest);
    }

    public function getHighestVersion(): string
    {
        return $this->highestVersion;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLowestVersion(): string
    {
        return $this->lowestVersion;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Compares the given version number against the lowest and highest
     * possible version number of this dependency (e.g. "typo3") to
     * determine if the given version is "compatible".
     *
     * @param string $version
     * @return bool TRUE if the version number is compatible
     */
    public function isVersionCompatible(string $version): bool
    {
        if ($this->lowestVersion !== '' && version_compare($version, $this->lowestVersion) === -1) {
            return false;
        }
        if ($this->highestVersion !== '' && version_compare($this->highestVersion, $version) === -1) {
            return false;
        }
        return true;
    }

    public function getLowestVersionAsInteger(): int
    {
        if ($this->lowestVersion) {
            return VersionNumberUtility::convertVersionNumberToInteger($this->lowestVersion);
        }
        return 0;
    }

    public function getHighestVersionAsInteger(): int
    {
        if ($this->highestVersion) {
            return VersionNumberUtility::convertVersionNumberToInteger($this->highestVersion);
        }
        return 0;
    }
}
