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
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Regex;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Validator\ConstraintDecoratingValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ConstraintDecoratingValidatorTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_validators',
    ];

    protected bool $initializeDatabase = false;

    private ConstraintDecoratingValidator $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new ConstraintDecoratingValidator(
            new Blank(message: 'The value {{ value }} is not blank.'),
        );
    }

    #[Test]
    public function validateReturnsEmptyResultIfConstraintWasNotViolated(): void
    {
        self::assertEquals(
            new Result(),
            $this->subject->validate(''),
        );
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function validateReturnsResultWithConstraintViolationsAsErrorsDataProvider(): \Generator
    {
        yield 'message without placeholders' => [
            'The value does not match the expected pattern.',
            'The value does not match the expected pattern.',
        ];
        yield 'message with named placeholders' => [
            'The value {{ value }} does not match the expected pattern {{ pattern }}.',
            'The value %1$s does not match the expected pattern %2$s.',
        ];
        yield 'message with short sprintf placeholders' => [
            'The value %s does not match the expected pattern %s.',
            'The value %s does not match the expected pattern %s.',
        ];
        yield 'message with long sprintf placeholders' => [
            'The value %1$s does not match the expected pattern %2$s.',
            'The value %1$s does not match the expected pattern %2$s.',
        ];
        yield 'message with LLL: syntax' => [
            'LLL:EXT:test_validators/Resources/Private/Language/locallang.xlf:validator.regex.message',
            'The value "foo" does not match the expected pattern /^[A-Z]+$/.',
        ];
    }

    #[DataProvider('validateReturnsResultWithConstraintViolationsAsErrorsDataProvider')]
    #[Test]
    public function validateReturnsResultWithConstraintViolationsAsErrors(string $message, string $expectedError): void
    {
        $subject = new ConstraintDecoratingValidator(new Regex('/^[A-Z]+$/', $message));

        $expected = new Result();
        $expected->addError(new Error($expectedError, 3851940642, ['"foo"', '/^[A-Z]+$/']));

        self::assertEquals($expected, $subject->validate('foo'));
    }
}
