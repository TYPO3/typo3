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

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IntegerValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * Data provider with valid integers
     */
    public static function validIntegers(): array
    {
        return [
            [1029437],
            ['12345'],
            ['+12345'],
            ['-12345'],
        ];
    }

    /**
     * @test
     * @dataProvider validIntegers
     */
    public function integerValidatorReturnsNoErrorsForAValidInteger(int|string $integer): void
    {
        $validator = new IntegerValidator();
        $validator->setOptions([]);
        self::assertFalse($validator->validate($integer)->hasErrors());
    }

    /**
     * Data provider with invalid integers
     */
    public static function invalidIntegers(): array
    {
        return [
            ['not a number'],
            [3.1415],
            ['12345.987'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidIntegers
     */
    public function integerValidatorReturnsErrorForAnInvalidInteger(float|string $invalidInteger): void
    {
        $validator = new IntegerValidator();
        $validator->setOptions([]);
        self::assertTrue($validator->validate($invalidInteger)->hasErrors());
    }

    /**
     * @test
     */
    public function integerValidatorCreatesTheCorrectErrorForAnInvalidSubject(): void
    {
        $validator = new IntegerValidator();
        $validator->setOptions([]);
        self::assertCount(1, $validator->validate('not a number')->getErrors());
    }
}
