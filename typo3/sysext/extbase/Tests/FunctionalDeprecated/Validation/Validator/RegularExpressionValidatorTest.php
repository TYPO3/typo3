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

namespace TYPO3\CMS\Extbase\Tests\FunctionalDeprecated\Validation\Validator;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RegularExpressionValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function customErrorMessageIsRespected(): void
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
