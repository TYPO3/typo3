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

namespace TYPO3\CMS\Core\Information;

class Typo3Version
{
    protected const VERSION = '11.5.13';
    protected const BRANCH = '11.5';

    /**
     * Define constants used up until TYPO3 v10, this constructor can be removed in TYPO3 v12.
     */
    public function __construct()
    {
        if (!defined('TYPO3_version')) {
            define('TYPO3_version', $this->getVersion());
        }
        if (!defined('TYPO3_branch')) {
            define('TYPO3_branch', $this->getBranch());
        }
    }

    public function getVersion(): string
    {
        return static::VERSION;
    }

    public function getBranch(): string
    {
        return static::BRANCH;
    }

    /**
     * Get 'major version' of version, e.g., '7' from '7.3.0'
     *
     * @return int Major version, e.g., '7'
     */
    public function getMajorVersion(): int
    {
        [$explodedVersion] = explode('.', static::VERSION);
        return (int)$explodedVersion;
    }

    public function __toString(): string
    {
        return $this->getVersion();
    }
}
