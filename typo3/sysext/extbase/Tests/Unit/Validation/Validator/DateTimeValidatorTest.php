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

use TYPO3\CMS\Extbase\Validation\Validator\DateTimeValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DateTimeValidatorTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider dateTimeValues
     */
    public function acceptsDateTimeValues($value)
    {
        $validator = new DateTimeValidator();
        $result = $validator->validate($value);

        self::assertFalse($result->hasErrors());
    }

    /**
     * @return array
     */
    public function dateTimeValues(): array
    {
        return [
            \DateTime::class => [
                new \DateTime(),
            ],
            'Extended ' . \DateTime::class => [
                new class() extends \DateTime {
                },
            ],
            \DateTimeImmutable::class => [
                new \DateTimeImmutable(),
            ],
            'Extended ' . \DateTimeImmutable::class => [
                new class() extends \DateTimeImmutable {
                },
            ],
        ];
    }

    /**
     * @test
     */
    public function addsErrorForInvalidValue()
    {
        $validator = $this->getMockBuilder(DateTimeValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->getMock();
        $result = $validator->validate(false);

        self::assertTrue($result->hasErrors());
    }
}
