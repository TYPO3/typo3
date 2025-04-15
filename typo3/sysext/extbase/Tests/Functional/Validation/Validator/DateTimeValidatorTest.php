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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\DateTimeValidator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DateTimeValidatorTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function acceptsDateTimeValuesDataProvider(): array
    {
        return [
            \DateTime::class => [
                new \DateTime(),
            ],
            'Extended ' . \DateTime::class => [
                new class () extends \DateTime {},
            ],
            \DateTimeImmutable::class => [
                new \DateTimeImmutable(),
            ],
            'Extended ' . \DateTimeImmutable::class => [
                new class () extends \DateTimeImmutable {},
            ],
        ];
    }

    #[DataProvider('acceptsDateTimeValuesDataProvider')]
    #[Test]
    public function acceptsDateTimeValues($value): void
    {
        $validator = new DateTimeValidator();
        $result = $validator->validate($value);
        self::assertFalse($result->hasErrors());
    }

    #[Test]
    public function addsErrorForInvalidValue(): void
    {
        $validator = new DateTimeValidator();
        $validator->setOptions([]);
        $result = $validator->validate(false);
        self::assertTrue($result->hasErrors());
    }
}
