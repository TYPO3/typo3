<?php
declare(strict_types = 1);

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Validation;

use TYPO3\CMS\Extbase\Tests\Fixture\ValidatorThatDoesNotImplementValidatorInterfaceValidator;
use TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Validation\Validator\CustomValidator;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator;
use TYPO3\CMS\Extbase\Validation\Validator\FloatValidator;
use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NumberValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorClassNameResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Extbase\Tests\Unit\Validation\ValidatorClassNameResolverTest
 */
class ValidatorClassNameResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function resolveResolvesFullyQualifiedClassNames(): void
    {
        $validatorIdentifier = IntegerValidator::class;
        static::assertSame($validatorIdentifier, ValidatorClassNameResolver::resolve($validatorIdentifier));
    }

    /**
     * @test
     */
    public function resolveResolvesCoreShorthandIdentifiers(): void
    {
        static::assertSame(IntegerValidator::class, ValidatorClassNameResolver::resolve('int'));
        static::assertSame(BooleanValidator::class, ValidatorClassNameResolver::resolve('bool'));
        static::assertSame(FloatValidator::class, ValidatorClassNameResolver::resolve('double'));
        static::assertSame(NumberValidator::class, ValidatorClassNameResolver::resolve('numeric'));
        static::assertSame(FloatValidator::class, ValidatorClassNameResolver::resolve('float'));
    }

    /**
     * @test
     */
    public function resolveResolvesExtensionShorthandIdentifiers(): void
    {
        static::assertSame(IntegerValidator::class, ValidatorClassNameResolver::resolve('TYPO3.CMS.Extbase:Integer'));
    }

    /**
     * @test
     */
    public function resolveThrowsNoSuchValidatorExceptionDueToMissingClass(): void
    {
        $this->expectExceptionCode(1365799920);
        $this->expectExceptionMessage('Validator class TYPO3\CMS\Extbase\Validation\Validator\NonExistingValidator does not exist');

        static::assertSame(IntegerValidator::class, ValidatorClassNameResolver::resolve('NonExisting'));
    }

    /**
     * @test
     */
    public function resolveThrowsNoSuchValidatorExceptionDueToClassInheritence(): void
    {
        $this->expectExceptionCode(1365776838);
        $this->expectExceptionMessage(sprintf(
            'Validator class %s must implement %s',
            ValidatorThatDoesNotImplementValidatorInterfaceValidator::class,
            ValidatorInterface::class
        ));

        static::assertSame(
            IntegerValidator::class,
            ValidatorClassNameResolver::resolve(ValidatorThatDoesNotImplementValidatorInterfaceValidator::class)
        );
    }

    /**
     * @return array
     */
    public function namespacedShorthandValidatorNamesDataProvider(): array
    {
        return [
            'TYPO3.CMS.Extbase:NotEmpty' => [
                'TYPO3.CMS.Extbase:NotEmpty',
                NotEmptyValidator::class
            ],
            'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:Custom' => [
                'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:Custom',
                CustomValidator::class
            ]
        ];
    }

    /**
     * @test
     * @dataProvider namespacedShorthandValidatorNamesDataProvider
     *
     * @param string $validatorName
     * @param string $expectedClassName
     */
    public function resolveWithShortHandNotationReturnsValidatorNameIfClassExists(string $validatorName, string $expectedClassName): void
    {
        static::assertSame(
            $expectedClassName,
            ValidatorClassNameResolver::resolve($validatorName)
        );
    }

    /**
     * @test
     */
    public function resolveWithShortHandNotationThrowsExceptionIfClassDoesNotExist(): void
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365799920);

        $validatorName = 'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:NonExistentValidator';
        ValidatorClassNameResolver::resolve($validatorName);
    }

    /**
     * @test
     */
    public function resolveThrowsExceptionWithValidatorThatDoesNotImplementValidatorInterface(): void
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365776838);

        $validatorName = 'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:CustomValidatorThatDoesNotImplementValidatorInterface';
        ValidatorClassNameResolver::resolve($validatorName);
    }

    /**
     * @test
     */
    public function resolveReturnsValidatorNameForFullQualifiedValidatorName(): void
    {
        $validatorName = CustomValidator::class;
        $className = CustomValidator::class;

        static::assertSame(
            $className,
            ValidatorClassNameResolver::resolve($validatorName)
        );
    }

    /**
     * @test
     */
    public function resolveReturnsValidatorNameForFullQualifiedValidatorNameWithLeadingBackslash(): void
    {
        $validatorName = '\\' . CustomValidator::class;
        $className = CustomValidator::class;

        static::assertSame(
            $className,
            ValidatorClassNameResolver::resolve($validatorName)
        );
    }
}
