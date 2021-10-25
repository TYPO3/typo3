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

use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\TextValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the text validator
 */
class TextValidatorTest extends UnitTestCase
{
    protected string $validatorClassName = TextValidator::class;

    public function setup(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
    }

    public function isValidDataProvider(): array
    {
        return [
            'a simple string' => [
                false, // expectation: no error
                'this is a very simple string', // test string
            ],
            'allow new line character' => [
                false,
                'Ierd Frot uechter mä get, Kirmesdag' . chr(10) . 'Ke kille Minutt',
            ],
            'allow single quote' => [
                false,
                'foo \' bar',
            ],
            'allow double quote' => [
                false,
                'foo " bar',
            ],
            'slash' => [
                false,
                'foo/bar',
            ],
            'slash with closing angle bracket' => [
                false,
                'foo/>bar',
            ],
            'closing angle bracket without opening angle bracket' => [
                false,
                '>foo',
            ],
            'common special characters' => [
                false,
                '3% of most people tend to use semikolae; we need to check & allow that. And hashes (#) are not evil either, nor is the sign called \'quote\'.',
            ],
            'nul byte' => [
                true,
                'foo' . chr(0) . 'bar',
            ],
            'a string with html' => [
                true,
                '<span style="color: #BBBBBB;">a nice text</span>',
            ],
            'not closed html' => [
                true,
                '<foo>bar',
            ],
            'opening angle bracket' => [
                true,
                '<foo', // @todo: This is odd. It means a simple opening bracket makes this validator fail.
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isValidDataProvider
     */
    public function isValidHasNoError(bool $expectation, string $testString): void
    {
        self::assertSame($expectation, $this->validator->validate($testString)->hasErrors());
    }

    /**
     * @test
     */
    public function textValidatorCreatesTheCorrectErrorIfTheSubjectContainsHtmlEntities(): void
    {
        // we only test for the error code, after the translation Method for message is mocked anyway
        $expected = [new Error('', 1221565786)];
        self::assertEquals($expected, $this->validator->validate('<span style="color: #BBBBBB;">a nice text</span>')->getErrors());
    }
}
