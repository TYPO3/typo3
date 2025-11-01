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

namespace TYPO3\CMS\Extbase\Tests\Functional\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConstraintDecoratingValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestValidators\Domain\Model\AliasedModel;
use TYPO3Tests\TestValidators\Domain\Model\AnotherModel;
use TYPO3Tests\TestValidators\Domain\Model\MixedSymfonyModel;
use TYPO3Tests\TestValidators\Domain\Model\Model;
use TYPO3Tests\TestValidators\Domain\Model\SymfonyModel;
use TYPO3Tests\TestValidators\Validation\Validator\CustomValidator;

final class ValidatorResolverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_validators',
    ];

    #[Test]
    public function createValidatorSetsOptionsAndDependenciesAreInjected(): void
    {
        $subject = $this->get(ValidatorResolver::class);
        $options = ['foo' => 'bar'];
        $validator = $subject->createValidator(CustomValidator::class, $options);
        self::assertInstanceOf(CustomValidator::class, $validator);
        self::assertSame($options, $validator->getOptions());

        // Test that iconFactory is injected (will throw exception if not injected)
        self::assertNotEmpty($validator->iconFactory->getIcon('actions-brand-typo3'));
    }

    #[Test]
    public function ValidatorsUsingAliasedAnnotationNamespaceAreApplied(): void
    {
        $subject = $this->getAccessibleMock(
            ValidatorResolver::class,
            null,
            [$this->get(ReflectionService::class)]
        );

        $subject->getBaseValidatorConjunction(AliasedModel::class);

        $baseValidatorConjunctions = $subject->_get('baseValidatorConjunctions');
        self::assertIsArray($baseValidatorConjunctions);
        self::assertCount(1, $baseValidatorConjunctions);
        self::assertArrayHasKey(AliasedModel::class, $baseValidatorConjunctions);

        $conjunctionValidator = $baseValidatorConjunctions[AliasedModel::class];
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        $baseValidators = $conjunctionValidator->getValidators();
        $baseValidators->rewind();
        self::assertTrue($baseValidators->valid());
        $validator = $baseValidators->current();
        self::assertInstanceOf(GenericObjectValidator::class, $validator);

        $propertyValidators = $validator->getPropertyValidators();
        self::assertCount(1, $propertyValidators);
        self::assertArrayHasKey('foo', $propertyValidators);

        $fooPropertyValidators = $propertyValidators['foo'];
        self::assertCount(3, $fooPropertyValidators);

        $fooPropertyValidators->rewind();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        self::assertSame(
            [
                'minimum' => 1,
                'maximum' => PHP_INT_MAX,
                'betweenMessage' => null,
                'lessMessage' => null,
                'exceedMessage' => null,
            ],
            $propertyValidator->getOptions()
        );

        $fooPropertyValidators->next();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        self::assertSame(
            [
                'minimum' => 0,
                'maximum' => 10,
                'betweenMessage' => null,
                'lessMessage' => null,
                'exceedMessage' => null,
            ],
            $propertyValidator->getOptions()
        );

        $fooPropertyValidators->next();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(NotEmptyValidator::class, $propertyValidator);
    }

    #[Test]
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction(): void
    {
        $subject = $this->getAccessibleMock(
            ValidatorResolver::class,
            null,
            [$this->get(ReflectionService::class)]
        );

        $subject->getBaseValidatorConjunction(Model::class);

        $baseValidatorConjunctions = $subject->_get('baseValidatorConjunctions');
        self::assertIsArray($baseValidatorConjunctions);
        self::assertCount(2, $baseValidatorConjunctions);
        self::assertArrayHasKey(Model::class, $baseValidatorConjunctions);
        self::assertArrayHasKey(AnotherModel::class, $baseValidatorConjunctions);

        $conjunctionValidator = $baseValidatorConjunctions[Model::class];
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        $baseValidators = $conjunctionValidator->getValidators();
        $baseValidators->rewind();
        self::assertTrue($baseValidators->valid());

        $validator = $baseValidators->current();
        self::assertInstanceOf(GenericObjectValidator::class, $validator);

        $propertyValidators = $validator->getPropertyValidators();
        self::assertCount(4, $propertyValidators);
        self::assertArrayHasKey('foo', $propertyValidators);
        self::assertArrayHasKey('bar', $propertyValidators);
        self::assertArrayHasKey('baz', $propertyValidators);
        self::assertArrayHasKey('qux', $propertyValidators);

        $fooPropertyValidators = $propertyValidators['foo'];
        self::assertCount(3, $fooPropertyValidators);

        $fooPropertyValidators->rewind();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        self::assertSame(
            [
                'minimum' => 1,
                'maximum' => PHP_INT_MAX,
                'betweenMessage' => null,
                'lessMessage' => null,
                'exceedMessage' => null,
            ],
            $propertyValidator->getOptions()
        );

        $fooPropertyValidators->next();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        self::assertSame(
            [
                'minimum' => 0,
                'maximum' => 10,
                'betweenMessage' => null,
                'lessMessage' => null,
                'exceedMessage' => null,
            ],
            $propertyValidator->getOptions()
        );

        $fooPropertyValidators->next();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(NotEmptyValidator::class, $propertyValidator);

        $barPropertyValidators = $propertyValidators['bar'];
        self::assertCount(1, $barPropertyValidators);

        $barPropertyValidators->rewind();
        $propertyValidator = $barPropertyValidators->current();
        self::assertInstanceOf(CustomValidator::class, $propertyValidator);

        $bazPropertyValidators = $propertyValidators['baz'];
        self::assertCount(1, $bazPropertyValidators);

        $bazPropertyValidators->rewind();
        $propertyValidator = $bazPropertyValidators->current();
        self::assertInstanceOf(CollectionValidator::class, $propertyValidator);

        $quxPropertyValidators = $propertyValidators['qux'];
        self::assertCount(1, $quxPropertyValidators);

        $quxPropertyValidators->rewind();
        $propertyValidator = $quxPropertyValidators->current();
        self::assertInstanceOf(ConjunctionValidator::class, $propertyValidator);
        self::assertSame(
            $baseValidatorConjunctions[AnotherModel::class],
            $propertyValidator
        );
    }

    #[Test]
    public function SymfonyValidatorsCanBeApplied(): void
    {
        $subject = $this->getAccessibleMock(
            ValidatorResolver::class,
            null,
            [$this->get(ReflectionService::class)]
        );

        $subject->getBaseValidatorConjunction(SymfonyModel::class);

        $baseValidatorConjunctions = $subject->_get('baseValidatorConjunctions');
        self::assertIsArray($baseValidatorConjunctions);
        self::assertCount(1, $baseValidatorConjunctions);
        self::assertArrayHasKey(SymfonyModel::class, $baseValidatorConjunctions);

        $conjunctionValidator = $baseValidatorConjunctions[SymfonyModel::class];
        self::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        $baseValidators = $conjunctionValidator->getValidators();
        $baseValidators->rewind();

        $validator = $baseValidators->current();
        self::assertInstanceOf(GenericObjectValidator::class, $validator);

        $propertyValidators = $validator->getPropertyValidators();
        self::assertCount(1, $propertyValidators);
        self::assertArrayHasKey('foo', $propertyValidators);

        $fooPropertyValidators = $propertyValidators['foo'];
        self::assertCount(1, $fooPropertyValidators);

        $fooPropertyValidators->rewind();
        $propertyValidator = $fooPropertyValidators->current();
        self::assertInstanceOf(ConstraintDecoratingValidator::class, $propertyValidator);
        self::assertSame(
            [],
            $propertyValidator->getOptions()
        );
    }

    #[Test]
    public function SymfonyValidatorsCanBeValidated(): void
    {
        $subject = $this->getAccessibleMock(
            ValidatorResolver::class,
            null,
            [$this->get(ReflectionService::class)]
        );

        $subject->getBaseValidatorConjunction(SymfonyModel::class);
        $baseValidatorConjunctions = $subject->_get('baseValidatorConjunctions');
        $conjunctionValidator = $baseValidatorConjunctions[SymfonyModel::class];
        $baseValidators = $conjunctionValidator->getValidators();
        $baseValidators->rewind();
        $validator = $baseValidators->current();
        $propertyValidators = $validator->getPropertyValidators();
        $fooPropertyValidators = $propertyValidators['foo'];
        $fooPropertyValidators->rewind();
        $propertyValidator = $fooPropertyValidators->current();

        $expectation = new Result();
        $expectation->addError(new Error('Your foo must be at least %2$s characters long', 286244044, ['""', 1, 0]));
        self::assertEquals(
            $propertyValidator->validate(''),
            $expectation,
        );

        $expectation = new Result();
        self::assertEquals(
            $propertyValidator->validate('success'),
            $expectation,
        );

        $expectation = new Result();
        $expectation->addError(new Error('Your foo cannot be longer than %2$s characters', 1497521431, ['"failure because too long"', 10, 24]));
        self::assertEquals(
            $propertyValidator->validate('failure because too long'),
            $expectation,
        );
    }

    public static function SymfonyAndExtbaseValidatorsDataProvider(): \Generator
    {
        yield 'property validates properly' => [
            'input' => 'is valid',
            'expectedErrorResults' => [
                new Result(),
                new Result(),
                new Result(),
            ],
        ];

        $errorResult = new Result();
        $errorResult->addError(new \TYPO3\CMS\Extbase\Validation\Error('The length of the given string exceeded 10 characters.', 1238108069, [10]));
        yield 'property does not validate, is too long (Extbase validator fail)' => [
            'input' => 'is invalid because it is too long',
            'expectedErrorResults' => [
                new Result(),
                new Result(),
                $errorResult,
            ],
        ];

        $errorResult = new Result();
        $errorResult->addError(new Error('Your foo must be at least %2$s characters long', 286244044, ['"i"', 2, 1]));
        yield 'property does not validate, is too short (Symfony validator fail)' => [
            'input' => 'i',
            'expectedErrorResults' => [
                $errorResult,
                new Result(),
                new Result(),
            ],
        ];
    }

    #[DataProvider('SymfonyAndExtbaseValidatorsDataProvider')]
    #[Test]
    public function SymfonyValidatorsCanBeMixedWithExtbaseValidators(string $input, array $expectedErrorResults): void
    {
        $subject = $this->getAccessibleMock(
            ValidatorResolver::class,
            null,
            [$this->get(ReflectionService::class)]
        );

        $subject->getBaseValidatorConjunction(MixedSymfonyModel::class);
        $baseValidatorConjunctions = $subject->_get('baseValidatorConjunctions');
        $conjunctionValidator = $baseValidatorConjunctions[MixedSymfonyModel::class];
        $baseValidators = $conjunctionValidator->getValidators();
        $baseValidators->rewind();
        $validator = $baseValidators->current();
        $propertyValidators = $validator->getPropertyValidators();
        $fooPropertyValidators = $propertyValidators['foo'];
        self::assertCount(3, $fooPropertyValidators);

        $results = [];
        foreach ($fooPropertyValidators as $fooPropertyValidator) {
            $results[] = $fooPropertyValidator->validate($input);
        }
        self::assertEquals($results, $expectedErrorResults);
    }
}
