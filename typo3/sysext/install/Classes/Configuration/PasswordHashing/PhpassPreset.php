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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\AbstractPreset;
use TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt;

/**
 * Preset for password hashing method "phpass"
 */
class PhpassPreset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Phpass';

    /**
     * @var int Priority of preset
     */
    protected $priority = 40;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/passwordHashing/className' => PhpassSalt::class,
        'BE/passwordHashing/options' => [],
        'FE/passwordHashing/className' => PhpassSalt::class,
        'FE/passwordHashing/options' => [],
    ];

    /**
     * Find out if Phpass is available on this system
     *
     * @return bool
     */
    public function isAvailable()
    {
        return GeneralUtility::makeInstance(PhpassSalt::class)->isAvailable();
    }
}
