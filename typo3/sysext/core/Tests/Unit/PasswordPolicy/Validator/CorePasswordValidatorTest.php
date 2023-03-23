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

namespace TYPO3\CMS\Core\Tests\Unit\PasswordPolicy\Validator;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CorePasswordValidatorTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getMockBuilder(LanguageService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sL'])
            ->getMock();
    }

    public static function validatorReturnsExpectedResultsDataProvider(): array
    {
        return [
            'empty password' => [
                [
                    'minimumLength' => 8,
                ],
                '',
                false,
            ],
            'password too short' => [
                [
                    'minimumLength' => 8,
                ],
                'pass',
                false,
            ],
            'no upper case char' => [
                [
                    'upperCaseCharacterRequired' => true,
                ],
                'pass',
                false,
            ],
            'no lower case char' => [
                [
                    'lowerCaseCharacterRequired' => true,
                ],
                'PASS',
                false,
            ],
            'no digit' => [
                [
                    'digitCharacterRequired' => true,
                ],
                'pass',
                false,
            ],
            'no special char' => [
                [
                    'specialCharacterRequired' => true,
                ],
                'pass',
                false,
            ],
            'password with all requirements' => [
                [
                    'minimumLength' => 8,
                    'upperCaseCharacterRequired' => true,
                    'lowerCaseCharacterRequired' => true,
                    'digitCharacterRequired' => true,
                    'specialCharacterRequired' => true,
                ],
                'Pa$$w0rd!',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validatorReturnsExpectedResultsDataProvider
     */
    public function validatorReturnsExpectedResults($options, $password, $expected)
    {
        $validator = new CorePasswordValidator($options);
        self::assertEquals($expected, $validator->validate($password));
    }
}
