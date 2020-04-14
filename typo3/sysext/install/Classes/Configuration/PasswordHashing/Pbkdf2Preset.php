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

namespace TYPO3\CMS\Install\Configuration\PasswordHashing;

use TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\AbstractPreset;

/**
 * Preset for password hashing method "PBKDF2"
 * @internal only to be used within EXT:install
 */
class Pbkdf2Preset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Pbkdf2';

    /**
     * @var int Priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/passwordHashing/className' => Pbkdf2PasswordHash::class,
        'BE/passwordHashing/options' => [],
        'FE/passwordHashing/className' => Pbkdf2PasswordHash::class,
        'FE/passwordHashing/options' => [],
    ];

    /**
     * Find out if PBKDF2 is available on this system
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return GeneralUtility::makeInstance(Pbkdf2PasswordHash::class)->isAvailable();
    }
}
