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

use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\AbstractPreset;

/**
 * Preset for password hashing method "bcrypt"
 * @internal only to be used within EXT:install
 */
class BcryptPreset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Bcrypt';

    /**
     * @var int Priority of preset
     */
    protected $priority = 60;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/passwordHashing/className' => BcryptPasswordHash::class,
        'BE/passwordHashing/options' => [],
        'FE/passwordHashing/className' => BcryptPasswordHash::class,
        'FE/passwordHashing/options' => [],
    ];

    /**
     * Find out if bcrypt is available on this system
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return GeneralUtility::makeInstance(BcryptPasswordHash::class)->isAvailable();
    }
}
