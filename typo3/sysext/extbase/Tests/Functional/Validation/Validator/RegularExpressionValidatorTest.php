<?php

declare(strict_types=1);

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation\Validator;

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RegularExpressionValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
    }

    public function customErrorMessagesDataProvider(): array
    {
        return [
            'no message' => [
                '',
                'The given subject did not match the pattern.'
            ],
            'translation key' => [
                'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.recordInformation',
                'Record information'
            ],
            'static message' => [
                'some static custom message',
                'some static custom message'
            ],
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     * @throws InvalidValidationOptionsException
     * @test
     * @dataProvider customErrorMessagesDataProvider
     */
    public function translatableErrorMessageContainsDefaultValue(string $input, string $expected)
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
        ];
        if ($input) {
            $options['errorMessage'] = $input;
        }
        $subject = new RegularExpressionValidator($options);
        $result = $subject->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error($expected, 1221565130)], $errors);
    }
}
