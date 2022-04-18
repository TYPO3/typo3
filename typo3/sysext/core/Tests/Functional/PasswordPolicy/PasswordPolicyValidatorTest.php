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

namespace TYPO3\CMS\Core\Tests\Functional\PasswordPolicy;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PasswordPolicyValidatorTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected bool $initializeDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getMockBuilder(LanguageService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sL'])
            ->getMock();
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorIsEnabledWhenPasswordPolicyIsConfigured(): void
    {
        $this->setDefaultPasswordPolicy();
        $passwordPolicy = 'default';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertTrue($passwordPolicyValidator->isEnabled());
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorIsDisabledWhenNoValidatorsInPasswordPolicy(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'] = [];
        $passwordPolicy = 'default';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertFalse($passwordPolicyValidator->isEnabled());
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorIsDisabledForUnknownPasswordPolicyIdentifier(): void
    {
        $this->setDefaultPasswordPolicy();
        $passwordPolicy = 'unknown';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertFalse($passwordPolicyValidator->isEnabled());
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorReturnsExpectedAmountOfPasswordPolicyRequirements(): void
    {
        $this->setDefaultPasswordPolicy();
        $passwordPolicy = 'default';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertCount(5, $passwordPolicyValidator->getRequirements());
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorDoesNotAcceptPasswordNotCompliantToPolicy(): void
    {
        $this->setDefaultPasswordPolicy();
        $passwordPolicy = 'default';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertFalse($passwordPolicyValidator->isValidPassword('123456'));
        self::assertCount(4, $passwordPolicyValidator->getValidationErrors());
    }

    /**
     * @test
     */
    public function passwordPolicyValidatorHasExpectedAmountOfValidationErrorsForInvalidPassword(): void
    {
        $this->setDefaultPasswordPolicy();
        $passwordPolicy = 'default';

        $passwordPolicyValidator = GeneralUtility::makeInstance(
            PasswordPolicyValidator::class,
            PasswordPolicyAction::NEW_USER_PASSWORD,
            $passwordPolicy
        );

        self::assertFalse($passwordPolicyValidator->isValidPassword(''));
        self::assertCount(5, $passwordPolicyValidator->getValidationErrors());
    }

    protected function setDefaultPasswordPolicy(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'] = [
            'default' => [
                'validators' => [
                    \TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator::class => [
                        'options' => [
                            'minimumLength' => 8,
                            'upperCaseCharacterRequired' => true,
                            'lowerCaseCharacterRequired' => true,
                            'digitCharacterRequired' => true,
                            'specialCharacterRequired' => true,
                        ],
                        'excludeActions' => [],
                    ],
                ],
            ],
        ];
    }
}
