<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Configuration\PasswordHashing;

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

use TYPO3\CMS\Install\Configuration\AbstractFeature;
use TYPO3\CMS\Install\Configuration\FeatureInterface;

/**
 * Password hashing feature detects password hashing capabilities of the system
 * @internal only to be used within EXT:install
 */
class PasswordHashingFeature extends AbstractFeature implements FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'PasswordHashing';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        Argon2iPreset::class,
        BcryptPreset::class,
        Pbkdf2Preset::class,
        PhpassPreset::class,
        CustomPreset::class,
    ];
}
