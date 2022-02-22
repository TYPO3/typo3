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
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\NumberValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumberValidatorTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    /**
     * @test
     */
    public function numberValidatorReturnsTrueForASimpleInteger(): void
    {
        $subject = new NumberValidator();
        $subject->setOptions([]);
        self::assertFalse($subject->validate(1029437)->hasErrors());
    }

    /**
     * @test
     */
    public function numberValidatorReturnsFalseForAString(): void
    {
        $subject = new NumberValidator();
        $subject->setOptions([]);
        $expectedResult = new Result();
        // we only test for the error code, after the message translation method is mocked
        $expectedResult->addError(new Error('The given subject was not a valid number.', 1221563685));
        self::assertEquals($expectedResult, $subject->validate('not a number'));
    }
}
