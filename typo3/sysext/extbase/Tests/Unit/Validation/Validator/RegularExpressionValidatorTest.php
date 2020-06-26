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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RegularExpressionValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function regularExpressionValidatorMatchesABasicExpressionCorrectly()
    {
        $options = ['regularExpression' => '/^simple[0-9]expression$/'];
        /** @var MockObject|RegularExpressionValidator $validator */
        $validator = $this->getMockBuilder(RegularExpressionValidator::class)
            ->onlyMethods(['getErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        self::assertFalse($validator->validate('simple1expression')->hasErrors());
        self::assertTrue($validator->validate('simple1expressions')->hasErrors());
    }

    /**
     * @test
     */
    public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch()
    {
        $options = ['regularExpression' => '/^simple[0-9]expression$/'];
        /** @var MockObject|RegularExpressionValidator $validator */
        $validator = $this->getMockBuilder(RegularExpressionValidator::class)
            ->onlyMethods(['getErrorMessage'])
            ->setConstructorArgs([$options])
            ->getMock();
        $errors = $validator->validate('some subject that will not match')->getErrors();
        // we only test for the error code, after the translation Method for message is mocked anyway
        self::assertEquals([new Error('', 1221565130)], $errors);
    }

    /**
     * @test
     */
    public function customErrorMessageIsRespected()
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => 'custom message',
        ];
        $validator = new RegularExpressionValidator($options);
        $result = $validator->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('custom message', 1221565130)], $errors);
    }

    /**
     * @test
     */
    public function getErrorMessageReturnsDefaultLabelIfNoCustomIsDefined()
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
        ];
        /** @var RegularExpressionValidator|MockObject $validator */
        $validator = $this->getMockBuilder(RegularExpressionValidator::class)
                          ->onlyMethods(['translateErrorMessage'])
                          ->setConstructorArgs([$options])
                          ->getMock();
        $validator->expects(self::once())->method('translateErrorMessage')
                                         ->willReturn('default message');
        $result = $validator->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('default message', 1221565130)], $errors);
    }

    /**
     * @test
     */
    public function customErrorMessageIsTranslated()
    {
        $options = [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => 'LLL:demo/Resources/',
        ];
        /** @var RegularExpressionValidator|MockObject $validator */
        $validator = $this->getMockBuilder(RegularExpressionValidator::class)
                          ->onlyMethods(['translateErrorMessage'])
                          ->setConstructorArgs([$options])
                          ->getMock();
        $validator->expects(self::once())->method('translateErrorMessage')
                                         ->willReturn('custom translated message');
        $result = $validator->validate('some subject that will not match');
        self::assertTrue($result->hasErrors());
        $errors = $result->getErrors();
        self::assertEquals([new Error('custom translated message', 1221565130)], $errors);
    }
}
