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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestValidators\Validation\Validator\TranslateErrorMessageWithExtensionNameValidator;
use TYPO3Tests\TestValidators\Validation\Validator\TranslateErrorMessageWithoutExtensionNameValidator;

final class ErrorMessageTranslationTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_validators',
    ];

    #[Test]
    public function validatorReturnsExpectedErrorIfExtensionNameProvidedInTranslateErrorMessage(): void
    {
        $subject = new TranslateErrorMessageWithExtensionNameValidator();
        $validationResult = $subject->validate('value');
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals('Expected translated error message for extensionName scenario.', $validationResult->getFirstError()->getMessage());
    }

    #[Test]
    public function validatorReturnsExpectedDefaultErrorIfNoExtensionNameProvidedInTranslateErrorMessage(): void
    {
        $subject = new TranslateErrorMessageWithoutExtensionNameValidator();
        $validationResult = $subject->validate('value');
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals('Expected translated error message.', $validationResult->getFirstError()->getMessage());
    }

    #[Test]
    public function validatorReturnsExpectedCustomErrorIfNoExtensionNameProvidedInTranslateErrorMessage(): void
    {
        $subject = new TranslateErrorMessageWithoutExtensionNameValidator();
        $subject->setOptions([
            'message' => 'LLL:EXT:test_validators/Resources/Private/Language/locallang.xlf:validator.translateerrormessagewithoutextensionname.custom_message',
        ]);
        $validationResult = $subject->validate('value');
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals('Expected custom translated error message.', $validationResult->getFirstError()->getMessage());
    }

    #[Test]
    public function validatorReturnsCustomMessageAsStringIfNoExtensionNameProvidedInTranslateErrorMessage(): void
    {
        $subject = new TranslateErrorMessageWithoutExtensionNameValidator();
        $subject->setOptions([
            'message' => 'Custom message as string.',
        ]);
        $validationResult = $subject->validate('value');
        self::assertTrue($validationResult->hasErrors());
        self::assertEquals('Custom message as string.', $validationResult->getFirstError()->getMessage());
    }
}
