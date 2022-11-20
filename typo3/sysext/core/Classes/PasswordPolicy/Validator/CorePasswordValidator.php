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

use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;

/**
 * Configurable TYPO3 core password validator which can validate, that a password has:
 *
 * - A minimum length
 * - At least one upper case char
 * - At least one lower case char
 * - At least one digit
 * - At least one special char
 *
 * @internal only to be used within ext:core, not part of TYPO3 Core API.
 */
class CorePasswordValidator extends AbstractPasswordValidator
{
    public function validate(string $password, ?ContextData $contextData = null): bool
    {
        $isValid = true;
        $lang = $this->getLanguageService();

        if (strlen($password) < $this->getMinLength()) {
            $this->addErrorMessage(
                'minimumLength',
                sprintf(
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.minimumLength'),
                    $this->getMinLength()
                )
            );
            $isValid = false;
        }

        if ($this->isCheckEnabled('upperCaseCharacterRequired') &&
            !$this->evaluatePasswordRequirement($password, 'upperCaseCharacterRequired')
        ) {
            $this->addErrorMessage(
                'upperCaseCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.upperCaseCharacterRequired')
            );
            $isValid = false;
        }

        if ($this->isCheckEnabled('lowerCaseCharacterRequired') &&
            !$this->evaluatePasswordRequirement($password, 'lowerCaseCharacterRequired')
        ) {
            $this->addErrorMessage(
                'lowerCaseCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.lowerCaseCharacterRequired')
            );
            $isValid = false;
        }

        if ($this->isCheckEnabled('digitCharacterRequired') &&
            !$this->evaluatePasswordRequirement($password, 'digitCharacterRequired')
        ) {
            $this->addErrorMessage(
                'digitCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.digitCharacterRequired')
            );
            $isValid = false;
        }

        if ($this->isCheckEnabled('specialCharacterRequired') &&
            !$this->evaluatePasswordRequirement($password, 'specialCharacterRequired')
        ) {
            $this->addErrorMessage(
                'specialCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:error.specialCharacterRequired')
            );
            $isValid = false;
        }

        return $isValid;
    }

    public function initializeRequirements(): void
    {
        $lang = $this->getLanguageService();
        $this->addRequirement(
            'minimumLength',
            sprintf(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.minimumLength'),
                $this->getMinLength()
            ),
        );

        if ($this->isCheckEnabled('upperCaseCharacterRequired')) {
            $this->addRequirement(
                'upperCaseCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.upperCaseCharacterRequired')
            );
        }

        if ($this->isCheckEnabled('lowerCaseCharacterRequired')) {
            $this->addRequirement(
                'lowerCaseCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.lowerCaseCharacterRequired')
            );
        }

        if ($this->isCheckEnabled('digitCharacterRequired')) {
            $this->addRequirement(
                'digitCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.digitCharacterRequired')
            );
        }

        if ($this->isCheckEnabled('specialCharacterRequired')) {
            $this->addRequirement(
                'specialCharacterRequired',
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:requirement.specialCharacterRequired')
            );
        }
    }

    private function getMinLength(): int
    {
        return (int)($this->options['minimumLength'] ?? 8);
    }

    private function isCheckEnabled(string $checkIdentifier): bool
    {
        return $this->options[$checkIdentifier] ?? false;
    }

    /**
     * Evaluates the password complexity for the given check
     */
    private function evaluatePasswordRequirement(string $password, string $requirement): bool
    {
        $result = true;

        $patterns = [
            'upperCaseCharacterRequired' => '/[A-Z]/',
            'lowerCaseCharacterRequired' => '/[a-z]/',
            'digitCharacterRequired' => '/[0-9]/',
            'specialCharacterRequired' => '/[^0-9a-z]/i',
        ];

        if (isset($patterns[$requirement]) && !preg_match($patterns[$requirement], $password) > 0) {
            $result = false;
        }

        return $result;
    }
}
