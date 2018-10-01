<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Informational upgrade wizard to remind upgrading instances
 * may have to verify argon2i is available on the live servers
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Argon2iPasswordHashes implements UpgradeWizardInterface, ConfirmableInterface
{
    protected $confirmation;

    public function __construct()
    {
        $this->confirmation = new Confirmation(
            'Please make sure to read the following carefully:',
            $this->getDescription(),
            false,
            'Yes, I understand!',
            '',
            true
        );
    }

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'argon2iPasswordHashes';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Reminder to verify live system supports argon2i';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'TYPO3 uses the modern hash mechanism "argon2i" on this system. Existing passwords'
               . ' will be automatically upgraded to this mechanism upon user login. If this instance'
               . ' is later deployed to a different system, make sure the system does support argon2i'
               . ' too, otherwise logins will fail. If that is not possible, select a different hash'
               . ' algorithm in Setting > Presets > Password hashing settings and make sure no user'
               . ' has been upgraded yet. This upgrade wizard exists only to inform you, it does not'
               . ' change the system';
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        $feHash = $passwordHashFactory->getDefaultHashInstance('BE');
        $beHash = $passwordHashFactory->getDefaultHashInstance('FE');
        return $feHash instanceof Argon2iPasswordHash || $beHash instanceof Argon2iPasswordHash;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * This upgrade wizard has informational character only, it does not perform actions.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        return true;
    }

    /**
     * Return a confirmation message instance
     *
     * @return \TYPO3\CMS\Install\Updates\Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }
}
