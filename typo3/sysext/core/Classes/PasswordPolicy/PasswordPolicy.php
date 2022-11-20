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

use TYPO3\CMS\Core\PasswordPolicy\Validator\AbstractPasswordValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Password policy class which holds information about configured password validators and password requirements
 *
 * @internal
 */
class PasswordPolicy
{
    /**
     * @var AbstractPasswordValidator[]
     */
    protected array $validators = [];

    /**
     * @param array<class-string<AbstractPasswordValidator>, array<string, mixed>> $validators
     */
    public function __construct(array $validators, protected PasswordPolicyAction $action)
    {
        foreach ($validators as $validatorClassName => $validatorSettings) {
            // Exclude validator if current action is defined as excludeAction
            if (in_array($action, $validatorSettings['excludeActions'] ?? [], true)) {
                continue;
            }

            $this->validators[] =  GeneralUtility::makeInstance(
                $validatorClassName,
                $validatorSettings['options'] ?? []
            );
        }
    }

    public function getAction(): PasswordPolicyAction
    {
        return $this->action;
    }

    public function hasValidators(): bool
    {
        return !empty($this->validators);
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Returns an array with requirements (e.g. ["Password must at least contain one char"]) for all
     * configured password validators. The structure of the array is as following:
     *
     * ['classId.validatorId' => 'Requirement text']
     */
    public function getRequirements(): array
    {
        $requirements = [];
        foreach ($this->validators as $validator) {
            $requirements = array_merge($requirements, $validator->getRequirements());
        }

        return $requirements;
    }
}
