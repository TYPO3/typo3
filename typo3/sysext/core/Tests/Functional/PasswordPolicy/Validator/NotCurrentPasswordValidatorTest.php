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

namespace TYPO3\CMS\Core\Tests\Functional\PasswordPolicy\Validator;

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\PasswordPolicy\Validator\NotCurrentPasswordValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NotCurrentPasswordValidatorTest extends FunctionalTestCase
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
    public function validatorReturnsFalseIfPasswordIsEqualToCurrentPasswordForBackendUser(): void
    {
        $knownPasswordHash = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('BE')
            ->getHashedPassword('password');

        $validator = new NotCurrentPasswordValidator();

        $contextData = new ContextData(loginMode: 'BE', currentPasswordHash: $knownPasswordHash);
        self::assertFalse($validator->validate('password', $contextData));
    }

    /**
     * @test
     */
    public function validatorThrowsExpectedExceptionIfNoUnsupportedLoginMode(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1649846004);

        $validator = new NotCurrentPasswordValidator();
        $contextData = new ContextData(loginMode: 'INVALID');
        $validator->validate('password', $contextData);
    }

    /**
     * @test
     */
    public function validatorReturnsFalseIfPasswordIsEqualToCurrentPasswordForFrontendUser(): void
    {
        $knownPasswordHash = GeneralUtility::makeInstance(PasswordHashFactory::class)
            ->getDefaultHashInstance('FE')
            ->getHashedPassword('password');

        $validator = new NotCurrentPasswordValidator();
        $contextData = new ContextData(loginMode: 'FE', currentPasswordHash: $knownPasswordHash);

        self::assertFalse($validator->validate('password', $contextData));
    }
}
