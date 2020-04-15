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

use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Tests\Fixture\Entity;
use TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CollectionValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = CollectionValidator::class;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
     */
    protected $mockValidatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @param array $options
     * @param array $mockedMethods
     * @return \PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected function getValidator(array $options = [], array $mockedMethods = ['translateErrorMessage'])
    {
        return $this->getAccessibleMock($this->validatorClassName, $mockedMethods, [$options], '', true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockValidatorResolver = $this->getAccessibleMock(
            ValidatorResolver::class,
            ['createValidator', 'buildBaseValidatorConjunction', 'getBaseValidatorConjunction']
        );
        $this->validator = $this->getValidator();
        $this->validator->_set('validatorResolver', $this->mockValidatorResolver);
    }

    /**
     * @test
     */
    public function collectionValidatorReturnsNoErrorsForANullValue()
    {
        self::assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorFailsForAValueNotBeingACollection()
    {
        self::assertTrue($this->validator->validate(new \stdClass())->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator()
    {
        // todo: this test is rather complex, consider making it a functional test with fixtures

        $this->validator->_set('options', ['elementValidator' => 'EmailAddress']);
        $this->mockValidatorResolver->expects(self::exactly(4))
            ->method('createValidator')
            ->with('EmailAddress')
            ->willReturn(
                $this->getMockBuilder(EmailAddressValidator::class)
                    ->setMethods(['translateErrorMessage'])
                    ->getMock()
            );
        $this->validator->_set('validatorResolver', $this->mockValidatorResolver);
        $arrayOfEmailAddresses = [
            'foo@bar.de',
            'not a valid address',
            'dummy@typo3.org',
            'also not valid'
        ];

        $result = $this->validator->validate($arrayOfEmailAddresses);

        self::assertTrue($result->hasErrors());
        self::assertCount(2, $result->getFlattenedErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping()
    {
        // todo: this test is rather complex, consider making it a functional test with fixtures

        $A = new class() {
            public $b = [];
            public $integer = 5;
        };

        $B = new class() {
            public $a;
            public $c;
            public $integer = 'Not an integer';
        };

        $A->b = [$B];
        $B->a = $A;
        $B->c = [$A];

        // Create validators
        $aValidator = $this->getMockBuilder(GenericObjectValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([[]])
            ->getMock();
        $this->validator->_set('options', ['elementValidator' => 'Integer']);
        $integerValidator = $this->getMockBuilder(IntegerValidator::class)
            ->setMethods(['translateErrorMessage'])
            ->setConstructorArgs([[]])
            ->getMock();

        $this->mockValidatorResolver->expects(self::any())
            ->method('createValidator')
            ->with('Integer')
            ->willReturn($integerValidator);
        $this->mockValidatorResolver->expects(self::any())
            ->method('buildBaseValidatorConjunction')
            ->willReturn($aValidator);

        // Add validators to properties
        $aValidator->addPropertyValidator('b', $this->validator);
        $aValidator->addPropertyValidator('integer', $integerValidator);

        $result = $aValidator->validate($A)->getFlattenedErrors();
        self::assertEquals(1221560494, $result['b.0'][0]->getCode());
    }

    /**
     * @test
     */
    public function collectionValidatorIsValidEarlyReturnsOnUninitializedLazyObjectStorages()
    {
        // todo: this test is rather complex, consider making it a functional test with fixtures

        $parentObject  = new Entity('Foo');
        $elementType = Entity::class;
        $lazyObjectStorage = new LazyObjectStorage(
            $parentObject,
            'someProperty',
            ['someNotEmptyValue']
        );
        // only in this test case we want to mock the isValid method
        $validator = $this->getValidator(['elementType' => $elementType], ['isValid']);
        $validator->expects(self::never())->method('isValid');
        $this->mockValidatorResolver->expects(self::never())->method('createValidator');
        $validator->validate($lazyObjectStorage);
    }

    /**
     * @test
     */
    public function collectionValidatorCallsCollectionElementValidatorWhenValidatingObjectStorages()
    {
        // todo: this test is rather complex, consider making it a functional test with fixtures

        $entity = new Entity('Foo');
        $elementType = Entity::class;
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity);
        $aValidator = new GenericObjectValidator([]);

        $this->mockValidatorResolver->expects(self::never())->method('createValidator');
        $this->mockValidatorResolver->expects(self::once())
            ->method('getBaseValidatorConjunction')
            ->with($elementType)
            ->willReturn($aValidator);

        $this->validator->_set('options', ['elementType' => $elementType]);

        $this->validator->validate($objectStorage);
    }
}
