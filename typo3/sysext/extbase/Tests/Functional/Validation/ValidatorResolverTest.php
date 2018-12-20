<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Functional\Validation;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * Test case
 */
class ValidatorResolverTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    private $validatorResolver;

    protected function setUp()
    {
        parent::setUp();

        $this->validatorResolver = $this->getAccessibleMock(
            ValidatorResolver::class,
            ['dummy']
        );
        $this->validatorResolver->injectObjectManager(GeneralUtility::makeInstance(ObjectManager::class));
        $this->validatorResolver->injectReflectionService(GeneralUtility::makeInstance(ReflectionService::class));
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
                Fixture\Validation\Validator\CustomValidator::class
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
    public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExists(string $validatorName, string $expectedClassName)
    {
        static::assertEquals(
            $expectedClassName,
            $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName)
        );
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameWithShortHandNotationThrowsExceptionIfClassDoesNotExist()
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365799920);

        $validatorName = 'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:NonExistentValidator';
        $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameWithShortHandNotationReturnsValidatorNameIfClassExistsButDoesNotImplementValidatorInterface()
    {
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365776838);

        $validatorName = 'TYPO3.CMS.Extbase.Tests.Functional.Validation.Fixture:CustomValidatorThatDoesNotImplementValidatorInterface';
        $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName);
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorNameForFullQualifiedValidatorName()
    {
        $validatorName = Fixture\Validation\Validator\CustomValidator::class;
        $className = Fixture\Validation\Validator\CustomValidator::class;

        static::assertEquals(
            $className,
            $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName)
        );
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameReturnsValidatorNameForFullQualifiedValidatorNameWithLeadingBackslash()
    {
        $validatorName = '\\' . Fixture\Validation\Validator\CustomValidator::class;
        $className = Fixture\Validation\Validator\CustomValidator::class;

        static::assertEquals(
            $className,
            $this->validatorResolver->_call('resolveValidatorObjectName', $validatorName)
        );
    }

    /**
     * @test
     */
    public function resolveValidatorObjectNameThrowsNoSuchValidatorExceptionIfClassDoesNotExist()
    {
        $className = $this->getUniqueId('Foo\\Bar');
        $this->expectException(NoSuchValidatorException::class);
        $this->expectExceptionCode(1365799920);
        $this->validatorResolver->_call('resolveValidatorObjectName', $className);
    }

    /**
     * @test
     */
    public function buildBaseValidatorConjunctionAddsValidatorsDefinedByAnnotationsInTheClassToTheReturnedConjunction()
    {
        $this->validatorResolver->_call(
            'buildBaseValidatorConjunction',
            \TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\Model::class,
            \TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\Model::class
        );

        /** @var array $baseValidatorConjunctions */
        $baseValidatorConjunctions = $this->validatorResolver->_get('baseValidatorConjunctions');
        static::assertTrue(is_array($baseValidatorConjunctions));
        static::assertCount(2, $baseValidatorConjunctions);
        static::assertArrayHasKey(\TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\Model::class, $baseValidatorConjunctions);
        static::assertArrayHasKey(\TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\AnotherModel::class, $baseValidatorConjunctions);

        /** @var ConjunctionValidator $conjunctionValidator */
        $conjunctionValidator = $baseValidatorConjunctions[\TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\Model::class];
        static::assertInstanceOf(ConjunctionValidator::class, $conjunctionValidator);

        $baseValidators = $conjunctionValidator->getValidators();
        static::assertInstanceOf(\SplObjectStorage::class, $baseValidators);
        $baseValidators->rewind();

        /** @var GenericObjectValidator $validator */
        $validator = $baseValidators->current();
        static::assertInstanceOf(GenericObjectValidator::class, $validator);

        $propertyValidators = $validator->getPropertyValidators();
        static::assertCount(4, $propertyValidators);
        static::assertArrayHasKey('foo', $propertyValidators);
        static::assertArrayHasKey('bar', $propertyValidators);
        static::assertArrayHasKey('baz', $propertyValidators);
        static::assertArrayHasKey('qux', $propertyValidators);

        /** @var \SplObjectStorage $fooPropertyValidators */
        $fooPropertyValidators = $propertyValidators['foo'];
        static::assertInstanceOf(\SplObjectStorage::class, $fooPropertyValidators);
        static::assertCount(3, $fooPropertyValidators);

        $fooPropertyValidators->rewind();
        /** @var StringLengthValidator $propertyValidator */
        $propertyValidator = $fooPropertyValidators->current();
        static::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        static::assertSame(['minimum' => 1, 'maximum' => PHP_INT_MAX], $propertyValidator->getOptions());

        $fooPropertyValidators->next();
        /** @var StringLengthValidator $propertyValidator */
        $propertyValidator = $fooPropertyValidators->current();
        static::assertInstanceOf(StringLengthValidator::class, $propertyValidator);
        static::assertSame(['minimum' => 0, 'maximum' => 10], $propertyValidator->getOptions());

        $fooPropertyValidators->next();
        $propertyValidator = $fooPropertyValidators->current();
        static::assertInstanceOf(NotEmptyValidator::class, $propertyValidator);

        /** @var \SplObjectStorage $barPropertyValidators */
        $barPropertyValidators = $propertyValidators['bar'];
        static::assertInstanceOf(\SplObjectStorage::class, $barPropertyValidators);
        static::assertCount(1, $barPropertyValidators);

        $barPropertyValidators->rewind();
        $propertyValidator = $barPropertyValidators->current();
        static::assertInstanceOf(Fixture\Validation\Validator\CustomValidator::class, $propertyValidator);

        /** @var \SplObjectStorage $bazPropertyValidators */
        $bazPropertyValidators = $propertyValidators['baz'];
        static::assertInstanceOf(\SplObjectStorage::class, $bazPropertyValidators);
        static::assertCount(1, $bazPropertyValidators);

        $bazPropertyValidators->rewind();
        $propertyValidator = $bazPropertyValidators->current();
        static::assertInstanceOf(CollectionValidator::class, $propertyValidator);

        /** @var \SplObjectStorage $quxPropertyValidators */
        $quxPropertyValidators = $propertyValidators['qux'];
        static::assertInstanceOf(\SplObjectStorage::class, $quxPropertyValidators);
        static::assertCount(1, $quxPropertyValidators);

        $quxPropertyValidators->rewind();
        $propertyValidator = $quxPropertyValidators->current();
        static::assertInstanceOf(ConjunctionValidator::class, $propertyValidator);
        static::assertSame(
            $baseValidatorConjunctions[\TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixture\Domain\Model\AnotherModel::class],
            $propertyValidator
        );

        $baseValidators->next();
        $validator = $baseValidators->current();
        static::assertInstanceOf(Fixture\Domain\Validator\ModelValidator::class, $validator);
    }
}
