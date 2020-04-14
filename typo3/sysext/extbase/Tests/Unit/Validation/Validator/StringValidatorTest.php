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

use TYPO3\CMS\Extbase\Validation\Validator\StringValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the string length validator
 */
class StringValidatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function stringValidatorShouldValidateString()
    {
        self::assertFalse((new StringValidator())->validate('Hello World')->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfNumberIsGiven()
    {
        /** @var StringValidator $validator */
        $validator = $this->getMockBuilder(StringValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        self::assertTrue($validator->validate(42)->hasErrors());
    }

    /**
     * @test
     */
    public function stringValidatorShouldReturnErrorIfObjectWithToStringMethodStringIsGiven()
    {
        /** @var StringValidator $validator */
        $validator = $this->getMockBuilder(StringValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();

        $object = new class() {
            /** @return string */
            public function __toString()
            {
                return 'ASDF';
            }
        };

        self::assertTrue($validator->validate($object)->hasErrors());
    }
}
