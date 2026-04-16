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

use Composer\Semver\VersionParser;
use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;

/**
 * The default TYPO3 Package MetaData implementation
 */
class MetaData
{
    public const CONSTRAINT_TYPE_DEPENDS = 'depends';
    public const CONSTRAINT_TYPE_CONFLICTS = 'conflicts';
    public const CONSTRAINT_TYPE_SUGGESTS = 'suggests';
    private const FRAMEWORK_TYPE = 'typo3-cms-framework';

    /**
     * @var array
     */
    protected static $CONSTRAINT_TYPES = [self::CONSTRAINT_TYPE_DEPENDS, self::CONSTRAINT_TYPE_CONFLICTS, self::CONSTRAINT_TYPE_SUGGESTS];

    /**
     * @var string
     */
    protected $packageKey;

    /**
     * Package type
     *
     * @var string|null
     */
    protected $packageType;

    /**
     * The normalized pretty version number
     */
    protected string $version;

    protected Stability $stability;

    protected ?string $build = null;

    protected bool $excludeFromUpdates = false;

    /**
     * Package title
     * @var string|null
     */
    protected $title;

    /**
     * Package description
     * @var string|null
     */
    protected $description;

    /**
     * constraints by constraint type (depends, conflicts, suggests)
     * @var array
     */
    protected $constraints = [];

    /**
     * Get all available constraint types
     *
     * @return array All constraint types
     */
    public function getConstraintTypes()
    {
        return self::$CONSTRAINT_TYPES;
    }

    /**
     * Package metadata constructor
     *
     * @param string $packageKey The package key
     */
    public function __construct($packageKey)
    {
        $this->packageKey = $packageKey;
    }

    /**
     * @return string The package key
     */
    public function getPackageKey()
    {
        return $this->packageKey;
    }

    public function isExtensionType(): bool
    {
        return is_string($this->packageType) && str_starts_with($this->packageType, 'typo3-cms-');
    }

    public function isFrameworkType(): bool
    {
        return $this->packageType === self::FRAMEWORK_TYPE;
    }

    /**
     * Get package type
     *
     * @return string|null
     */
    public function getPackageType()
    {
        return $this->packageType;
    }

    /**
     * Set package type
     *
     * @param string|null $packageType
     */
    public function setPackageType($packageType)
    {
        $this->packageType = $packageType;
    }

    /**
     * @return string The package version
     */
    public function getVersion()
    {
        return $this->version;
    }

    public function getStability(): Stability
    {
        return $this->stability;
    }

    /**
     * @param string $version The package version to set
     */
    public function setVersion($version)
    {
        $this->stability = Stability::from(VersionParser::parseStability($version));
        [$version, $build] = $this->splitBuildMetadata($version);
        $this->build = $build;
        $normalizedVersion = (new VersionParser())->normalize($version);
        $this->version = $this->normalizedToPrettyVersion($normalizedVersion);
    }

    /**
     * Converts a Composer-normalized version string into a human-friendly TYPO3 version string.
     *
     * Composer normalization typically produces versions in one of these forms:
     *
     * - Numeric versions with four segments:
     *   `1.2.3.0`, `3.0.0.0`, `5.2.32.0-RC2`
     * - Special dev branch form:
     *   `9999999-dev`
     * - Explicit dev branch names:
     *   `dev-main`
     *
     * This method transforms those normalized values into a prettier representation:
     *
     * - drops the fourth numeric segment
     * - removes trailing `.0` parts, while keeping at least `major.minor`
     * - preserves any stability suffix such as `-dev`, `-alpha1`, `-beta2`, `-RC3`
     * - maps Composer's special `9999999-dev` value to plain `dev`
     * - leaves non-matching or branch-like versions unchanged
     *
     * Examples:
     *
     * - `1.2.3.0` -> `1.2.3`
     * - `3.0.0.0` -> `3.0`
     * - `1.2.0.0` -> `1.2`
     * - `5.2.32.0-RC2` -> `5.2.32-RC2`
     * - `1.2.3.0-beta2` -> `1.2.3-beta2`
     * - `1.2.3.0-dev` -> `1.2.3-dev`
     * - `9999999-dev` -> `dev`
     * - `dev-main` -> `dev-main`
     */
    private function normalizedToPrettyVersion(string $normalized): string
    {
        // Common Composer special-case for branches.
        if ($normalized === '9999999-dev') {
            return 'dev';
        }

        // Leave obvious non-numeric/dev branch versions untouched.
        if (str_starts_with($normalized, 'dev-')) {
            return $normalized;
        }

        // Match actual numbers but return if we don't get a match
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)(-.+)?$/', $normalized, $matches)) {
            return $normalized;
        }

        $parts = [$matches[1], $matches[2], $matches[3]];
        $suffix = $matches[5] ?? '';

        return implode('.', $parts) . $suffix;
    }

    /**
     * Splits semver build metadata off the version string.
     *
     * Returns:
     *   [0] => version without "+..."
     *   [1] => metadata after "+" or null if none exists
     *
     * Only the first "+" is treated as the separator.
     *
     * @return array{0: string, 1: string|null}
     */
    private function splitBuildMetadata(string $version): array
    {
        $pos = strpos($version, '+');

        if ($pos === false) {
            return [$version, null];
        }

        $baseVersion = substr($version, 0, $pos);
        $buildMetadata = substr($version, $pos + 1);

        if ($baseVersion === '') {
            throw new \InvalidArgumentException('Version base must not be empty.', 1775558333);
        }

        return [$baseVersion, $buildMetadata];
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null The package description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description The package description to set
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get all constraints
     *
     * @return array Package constraints
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Get the constraints by type
     *
     * @param string $constraintType Type of the constraints to get: CONSTRAINT_TYPE_*
     * @return array Package constraints
     */
    public function getConstraintsByType($constraintType)
    {
        if (!isset($this->constraints[$constraintType])) {
            return [];
        }
        return $this->constraints[$constraintType];
    }

    /**
     * Add a constraint
     *
     * @param MetaData\PackageConstraint $constraint The constraint to add
     */
    public function addConstraint(PackageConstraint $constraint)
    {
        $this->constraints[$constraint->getConstraintType()][] = $constraint;
    }

    public function isExcludedFromUpdates(): bool
    {
        return $this->excludeFromUpdates;
    }

    public function setExcludeFromUpdates(bool $excludeFromUpdates): void
    {
        $this->excludeFromUpdates = $excludeFromUpdates;
    }

    public function getBuild(): ?string
    {
        return $this->build;
    }
}
