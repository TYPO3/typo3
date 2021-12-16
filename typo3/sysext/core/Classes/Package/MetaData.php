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

use TYPO3\CMS\Core\Package\MetaData\PackageConstraint;

/**
 * The default TYPO3 Package MetaData implementation
 */
class MetaData
{
    const CONSTRAINT_TYPE_DEPENDS = 'depends';
    const CONSTRAINT_TYPE_CONFLICTS = 'conflicts';
    const CONSTRAINT_TYPE_SUGGESTS = 'suggests';
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
     * The version number
     * @var string
     */
    protected $version;

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
     * @return string
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

    /**
     * @param string $version The package version to set
     */
    public function setVersion($version)
    {
        $this->version = $version;
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
}
