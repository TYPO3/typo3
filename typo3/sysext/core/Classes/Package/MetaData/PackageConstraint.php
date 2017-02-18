<?php
namespace TYPO3\CMS\Core\Package\MetaData;

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

/**
 * Package constraint meta model
 * Adapted from FLOW for TYPO3 CMS
 */
class PackageConstraint
{
    /**
     * One of depends, conflicts or suggests
     * @var string
     */
    protected $constraintType;

    /**
     * The constraint name or value
     * @var string
     */
    protected $value;

    /**
     * Minimum version for the constraint
     * @var string
     */
    protected $minVersion;

    /**
     * Maximum version for the constraint
     * @var string
     */
    protected $maxVersion;

    /**
     * Meta data constraint constructor
     *
     * @param string $constraintType
     * @param string $value
     * @param string $minVersion
     * @param string $maxVersion
     */
    public function __construct($constraintType, $value, $minVersion = null, $maxVersion = null)
    {
        $this->constraintType = $constraintType;
        $this->value = $value;
        $this->minVersion = $minVersion;
        $this->maxVersion = $maxVersion;
    }

    /**
     * @return string The constraint name or value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string The constraint type (depends, conflicts, suggests)
     */
    public function getConstraintType()
    {
        return $this->constraintType;
    }
}
