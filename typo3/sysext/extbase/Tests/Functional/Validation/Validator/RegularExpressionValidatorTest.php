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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RegularExpressionValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function regularExpressionValidatorMatchesABasicExpressionCorrectly(): void
    {
        $options = ['regularExpression' => '/^simple[0-9]expression$/'];
        $validator = new RegularExpressionValidator();
        $validator->setOptions($options);
        self::assertFalse($validator->validate('simple1expression')->hasErrors());
        self::assertTrue($validator->validate('simple1expressions')->hasErrors());
    }

    #[Test]
    public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch(): void
    {
        $options = ['regularExpression' => '/^simple[0-9]expression$/'];
        $validator = new RegularExpressionValidator();
        $validator->setOptions($options);
        $errors = $validator->validate('some subject that will not match')->getErrors();
        self::assertEquals([new Error('The given subject did not match the pattern.', 1221565130)], $errors);
    }

    #[Test]
    public function customErrorMessageIsRespected(): void
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'message' => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:onlineDocumentation',
        ];

        $subject = new RegularExpressionValidator();
        $subject->setOptions($options);
        $result = $subject->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('TYPO3 Online Documentation', 1221565130)], $errors);
    }

    public static function customErrorMessagesDataProvider(): array
    {
        return [
            'no message' => [
                '',
                'The given subject did not match the pattern.',
            ],
            'translation key' => [
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordInformation',
                'Record information',
            ],
            'static message' => [
                'some static custom message',
                'some static custom message',
            ],
        ];
    }

    #[DataProvider('customErrorMessagesDataProvider')]
    #[Test]
    public function translatableErrorMessageContainsDefaultValue(string $input, string $expected): void
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'message' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordInformation',
        ];

        $subject = new RegularExpressionValidator();
        $subject->setOptions($options);
        $result = $subject->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('Record information', 1221565130)], $errors);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function customErrorMessageIsRespectedDeprecated(): void
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => 'custom message',
        ];
        $validator = new RegularExpressionValidator();
        $validator->setOptions($options);
        $result = $validator->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('custom message', 1221565130)], $errors);
    }

    #[Test]
    public function emptyErrorMessageFallsBackToDefaultValue(): void
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => '',
        ];

        $subject = new RegularExpressionValidator();
        $subject->setOptions($options);
        $result = $subject->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('The given subject did not match the pattern.', 1221565130)], $errors);
    }

    #[Test]
    #[IgnoreDeprecations]
    public function lllLanguageKeyIsTranslated(): void
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordInformation',
        ];

        $subject = new RegularExpressionValidator();
        $subject->setOptions($options);
        $result = $subject->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('Record information', 1221565130)], $errors);
    }
}
