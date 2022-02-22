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

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Validator\FloatValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class FloatValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->create('default');
    }

    public function validFloats(): array
    {
        return [
            [1029437.234726],
            ['123.45'],
            ['+123.45'],
            ['-123.45'],
            ['123.45e3'],
            [123450.0],
        ];
    }

    /**
     * @test
     * @dataProvider validFloats
     */
    public function floatValidatorReturnsNoErrorsForAValidFloat(float|string $float): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertFalse($validator->validate($float)->hasErrors());
    }

    public function invalidFloats(): array
    {
        return [
            [1029437],
            ['1029437'],
            ['not a number'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidFloats
     */
    public function floatValidatorReturnsErrorForAnInvalidFloat(int|string $float): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate($float)->hasErrors());
    }

    /**
     * test
     */
    public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $validator = new FloatValidator();
        $validator->setOptions([]);
        self::assertCount(1, $validator->validate(123456)->getErrors());
    }
}
