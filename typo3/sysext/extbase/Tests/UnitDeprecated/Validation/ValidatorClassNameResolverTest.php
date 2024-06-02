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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Tests\Unit\Validation\Fixtures\Validation\Validator\CustomValidator;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorClassNameResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ValidatorClassNameResolverTest extends UnitTestCase
{
    #[Test]
    public function resolveResolvesExtensionShorthandIdentifiers(): void
    {
        self::assertSame(IntegerValidator::class, ValidatorClassNameResolver::resolve('TYPO3.CMS.Extbase:Integer'));
    }

    public static function namespacedShorthandValidatorNamesDataProvider(): array
    {
        return [
            'TYPO3.CMS.Extbase:NotEmpty' => [
                'TYPO3.CMS.Extbase:NotEmpty',
                NotEmptyValidator::class,
            ],
            'TYPO3.CMS.Extbase.Tests.Unit.Validation.Fixtures:Custom' => [
                'TYPO3.CMS.Extbase.Tests.Unit.Validation.Fixtures:Custom',
                CustomValidator::class,
            ],
        ];
    }

    #[DataProvider('namespacedShorthandValidatorNamesDataProvider')]
    #[Test]
    public function resolveWithShortHandNotationReturnsValidatorNameIfClassExists(string $validatorName, string $expectedClassName): void
    {
        self::assertSame(
            $expectedClassName,
            ValidatorClassNameResolver::resolve($validatorName)
        );
    }

    #[Test]
    public function resolveWithShortHandNotationThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365799920);

        $validatorName = 'TYPO3.CMS.Extbase.Tests.Unit.Validation.Fixtures:NonExistentValidator';
        ValidatorClassNameResolver::resolve($validatorName);
    }
}
