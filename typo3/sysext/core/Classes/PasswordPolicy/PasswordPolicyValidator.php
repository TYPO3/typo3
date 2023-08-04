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

namespace TYPO3\CMS\Core\PasswordPolicy;

use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;

/**
 * Validates a password using validators configured in $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'].
 * The class must be instantiated with an action (see PasswordPolicyAction) and a password policy name.
 */
class PasswordPolicyValidator
{
    protected ?PasswordPolicy $passwordPolicy = null;
    protected array $validationErrors = [];

    public function __construct(PasswordPolicyAction $action, string $passwordPolicy = 'default')
    {
        $passwordPolicies = $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'] ?? [];
        if (isset($passwordPolicies[$passwordPolicy])) {
            $this->passwordPolicy = new PasswordPolicy(
                $passwordPolicies[$passwordPolicy]['validators'] ?? [],
                $action,
            );
        }
    }

    /**
     * Returns, if the given password meets all requirements defined by configured password policy validators.
     * If no password policy is set or the password policy has no validators, the given password is considered
     * as valid.
     *
     * @param string $password The password to validate
     * @param ContextData|null $contextData ContextData for usage in additional checks (e.g. password must not contain users firstname).
     */
    public function isValidPassword(string $password, ?ContextData $contextData = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $isValid = true;
        foreach ($this->passwordPolicy->getValidators() as $validator) {
            if (!$validator->validate($password, $contextData)) {
                $this->validationErrors = array_merge($this->validationErrors, $validator->getErrorMessages());
                $isValid = false;
            }
        }

        return $isValid;
    }

    public function isEnabled(): bool
    {
        return $this->passwordPolicy !== null && $this->passwordPolicy->hasValidators();
    }

    public function hasRequirements(): bool
    {
        return !empty($this->getRequirements());
    }

    public function getRequirements(): array
    {
        return $this->passwordPolicy ? $this->passwordPolicy->getRequirements() : [];
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
