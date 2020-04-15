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

use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class UrlValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = UrlValidator::class;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\UrlValidator
     */
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->getMockBuilder($this->validatorClassName)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
    }

    /**
     * @return array
     */
    public function urlDataProvider(): array
    {
        return [
            'Regular URL' => [
                'value' => 'https://typo3.org/',
                'isValid' => true,
            ],
            'Regular URL with subdomain' => [
                'value' => 'https://testify.typo3.org/',
                'isValid' => true,
            ],
            'Valid URL with trailing slash and path segment' => [
                'value' => 'https://testify.typo3.org/testify/',
                'isValid' => true,
            ],
            'Valid URL without trailing slash and path segment' => [
                'value' => 'https://testify.typo3.org/testify',
                'isValid' => true,
            ],
            'mailto' => [
                'value' => 'mailto:foobar@example.com',
                'isValid' => true,
            ],
            'mailto with subject' => [
                'value' => 'mailto:foobar@example.com?subject=Unit+test+results',
                'isValid' => true,
            ],
            'ftp' => [
                'value' => 'ftp://remotestorage.org',
                'isValid' => true,
            ],
            'tel' => [
                'value' => 'tel:01189998819991197253',
                'isValid' => true,
            ],
            'Some scheme that most likely does not exist' => [
                'value' => 'monk://convert.wololo',
                'isValid' => true,
            ],
            'Umlauts in domain' => [
                'value' => 'https://bürgerkarte.at',
                'isValid' => true,
            ],
            'Domain without protocol' => [
                'value' => 'typo3.org',
                'isValid' => false,
            ],
            'Empty value' => [
                'value' => '',
                'isValid' => true,
            ],
            'Null value' => [
                'value' => null,
                'isValid' => true,
            ],
            'Invalid value is only a string' => [
                'value' => 'testify',
                'isValid' => false,
            ],
            'Invalid value is integer' => [
                'value' => 1,
                'isValid' => false,
            ],
            'Invalid value is object' => [
                'value' => new \stdClass(),
                'isValid' => false,
            ],
            'Invalid value is closure' => [
                'value' => function () {
                },
                'isValid' => false,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider urlDataProvider
     */
    public function urlValidatorDetectsUrlsCorrectly($value, $expected)
    {
        self::assertSame($expected, !$this->validator->validate($value)->hasErrors());
    }
}
