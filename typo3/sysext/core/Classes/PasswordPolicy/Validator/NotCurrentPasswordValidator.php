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

namespace TYPO3\CMS\Core\PasswordPolicy\Validator;

use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This validator checks, if the given password matches the current user password
 *
 * @internal only to be used within ext:core, not part of TYPO3 Core API.
 */
class NotCurrentPasswordValidator extends AbstractPasswordValidator
{
    public function validate(string $password, ?ContextData $contextData = null): bool
    {
        if (!$contextData) {
            throw new \RuntimeException('ContextData must be supplied to validator.', 1662808782);
        }

        if (in_array($contextData->getLoginMode(), ['FE', 'BE'], true)) {
            $isValid = !$this->isCurrentPassword($password, $contextData);
        } else {
            throw new \RuntimeException('Unsupported loginMode provided. Ensure, that loginMode is either "FE" or "BE".', 1649846004);
        }

        return $isValid;
    }

    public function initializeRequirements(): void
    {
        $this->addRequirement(
            'notCurrentPassword',
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.notCurrentPassword')
        );
    }

    /**
     * Returns if the hash of the given password equals the hash of the current password
     */
    protected function isCurrentPassword(string $password, ContextData $contextData): bool
    {
        $result = false;
        $saltFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
        try {
            $hashInstance = $saltFactory->get($contextData->getCurrentPasswordHash(), $contextData->getLoginMode());
            $result = $hashInstance->checkPassword(
                $password,
                $contextData->getCurrentPasswordHash()
            );
        } catch (InvalidPasswordHashException $e) {
            // Since the password will be updated, we silently ignore, if current password hash can not be checked
        }

        if ($result) {
            $this->addErrorMessage(
                'notCurrentPassword',
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.notCurrentPassword')
            );
        }

        return $result;
    }
}
