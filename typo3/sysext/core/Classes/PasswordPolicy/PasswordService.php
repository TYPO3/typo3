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
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class PasswordService
{
    /**
     * @return array<string, string>
     */
    public function getValidationErrorsForInstallToolUpdate(#[\SensitiveParameter] string $password): array
    {
        return $this->getValidationErrorsForPolicyAction(
            $password,
            'installTool',
            PasswordPolicyAction::UPDATE_INSTALL_TOOL_PASSWORD,
        );
    }

    /**
     * @param string $password The password to validate
     * @param string $passwordPolicyUsageContext Refers to a section in $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'][$passwordPolicyUsage]['validators'][...]
     * @param PasswordPolicyAction $passwordPolicyAction Policy action to perform (to indicate the action like "update Install Tool password")
     * @param ContextData|null $contextData Optional context data (for example, previous/current password(s)) used within validators
     */
    public function getValidationErrorsForPolicyAction(
        #[\SensitiveParameter]
        string $password,
        string $passwordPolicyUsageContext,
        PasswordPolicyAction $passwordPolicyAction,
        ?ContextData $contextData = null,
    ): array {
        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            $passwordPolicyAction,
            $passwordPolicyUsageContext,
        );
        $passwordPolicyValidator->isValidPassword($password, $contextData);
        return $passwordPolicyValidator->getValidationErrors();
    }
}
