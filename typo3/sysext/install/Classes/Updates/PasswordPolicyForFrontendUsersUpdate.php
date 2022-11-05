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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('passwordPolicyForFrontendUsersUpdate')]
class PasswordPolicyForFrontendUsersUpdate implements UpgradeWizardInterface, ConfirmableInterface
{
    protected ConfigurationManager $configurationManager;
    protected Confirmation $confirmation;

    public function __construct()
    {
        $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

        $this->confirmation = new Confirmation(
            'Do you want to use the global password policy for frontend users?',
            'If you did not configure own validators in plugin.tx_felogin_login.settings.passwordValidators' .
            ' TypoScript and comply with the TYPO3 default password policy password requirements:' . "\n\n" .
            '* At least 8 chars' . "\n" .
            '* At least one number' . "\n" .
            '* At least one upper case char' . "\n" .
            '* At least one special char' . "\n" .
            '* Must be different than current password (if available)' . "\n\n" .
            'it is recommended to use the global policy for frontend users. By choosing "Yes, use password policy" ' .
            'the feature toggle "security.usePasswordPolicyForFrontendUsers" will be activated.',
            true,
            'Yes, use the password policy',
            'No, use deprecated TypoScript validation'
        );
    }

    public function getConfirmation(): Confirmation
    {
        return $this->confirmation;
    }

    public function getTitle(): string
    {
        return 'Use global password policies for frontend user passwords';
    }

    public function getDescription(): string
    {
        return 'The TYPO3 frontend login extension allows to define validators in TypoScript for a password set by the'
            . ' password recovery function. Those validators have been deprecated with TYPO3 12. This update will ask,'
            . ' if the new global password policy should be used for TYPO3 frontend user passwords.';
    }

    public function updateNecessary(): bool
    {
        return true;
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    public function executeUpdate(): bool
    {
        $this->configurationManager->setLocalConfigurationValueByPath(
            'SYS/features/security.usePasswordPolicyForFrontendUsers',
            true
        );
        return true;
    }
}
