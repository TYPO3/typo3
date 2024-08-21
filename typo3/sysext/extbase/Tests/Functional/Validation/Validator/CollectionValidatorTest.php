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

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\Functional\Validation\Fixtures\Entity;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractGenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CollectionValidatorTest extends FunctionalTestCase
{
    #[Test]
    public function collectionValidatorReturnsNoErrorsForANullValue(): void
    {
        $subject = $this->get(CollectionValidator::class);
        $subject->setOptions([]);
        self::assertFalse($subject->validate(null)->hasErrors());
    }

    #[Test]
    public function collectionValidatorFailsForAValueNotBeingACollection(): void
    {
        $subject = $this->get(CollectionValidator::class);
        $subject->setOptions([]);
        self::assertTrue($subject->validate(new \stdClass())->hasErrors());
    }

    #[Test]
    public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator(): void
    {
        $mockValidatorResolver = $this->getAccessibleMock(
            ValidatorResolver::class,
            ['createValidator', 'buildBaseValidatorConjunction', 'getBaseValidatorConjunction'],
            [$this->get(ReflectionService::class)]
        );
        /** @var ContainerBuilder $container */
        $container = $this->get('service_container');
        $container->set(ValidatorResolver::class, $mockValidatorResolver);
        $subject = $container->get(CollectionValidator::class);
        $subject->setOptions(['elementValidator' => 'EmailAddress']);
        $emailAddressValidator = new EmailAddressValidator();
        $emailAddressValidator->setOptions([]);
        $mockValidatorResolver->expects(self::exactly(4))
            ->method('createValidator')
            ->with('EmailAddress')
            ->willReturn($emailAddressValidator);
        $arrayOfEmailAddresses = [
            'foo@bar.de',
            'not a valid address',
            'dummy@typo3.org',
            'also not valid',
        ];
        $result = $subject->validate($arrayOfEmailAddresses);
        self::assertTrue($result->hasErrors());
        self::assertCount(2, $result->getFlattenedErrors());
    }

    #[Test]
    public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping(): void
    {
        $A = new class () {
            public $b = [];
            public $integer = 5;
        };
        $B = new class () {
            public $a;
            public $c;
            public $integer = 'Not an integer';
        };
        $A->b = [$B];
        $B->a = $A;
        $B->c = [$A];

        // Create validators
        $aValidator = $this->getMockBuilder(AbstractGenericObjectValidator::class)
            ->onlyMethods(['translateErrorMessage'])
            ->getMock();
        $aValidator->setOptions([]);

        $subject = $this->get(CollectionValidator::class);
        $subject->setOptions(['elementValidator' => 'Integer']);
        $integerValidator = new IntegerValidator();
        $integerValidator->setOptions([]);
        $mockValidatorResolver = $this->getAccessibleMock(
            ValidatorResolver::class,
            ['createValidator', 'buildBaseValidatorConjunction', 'getBaseValidatorConjunction'],
            [$this->get(ReflectionService::class)]
        );
        $mockValidatorResolver
            ->method('createValidator')
            ->with('Integer')
            ->willReturn($integerValidator);
        // Add validators to properties
        $aValidator->addPropertyValidator('b', $subject);
        $aValidator->addPropertyValidator('integer', $integerValidator);

        $result = $aValidator->validate($A)->getFlattenedErrors();
        self::assertEquals(1221560494, $result['b.0'][0]->getCode());
    }

    #[Test]
    public function collectionValidatorCallsCollectionElementValidatorWhenValidatingObjectStorages(): void
    {
        $entity = new Entity('Foo');
        $elementType = Entity::class;
        $objectStorage = new ObjectStorage();
        $objectStorage->attach($entity);
        $aValidator = new GenericObjectValidator();
        $aValidator->setOptions([]);
        $mockValidatorResolver = $this->getAccessibleMock(
            ValidatorResolver::class,
            ['createValidator', 'buildBaseValidatorConjunction', 'getBaseValidatorConjunction'],
            [$this->get(ReflectionService::class)]
        );
        $mockValidatorResolver->expects(self::never())->method('createValidator');
        $subject = $this->get(CollectionValidator::class);
        $subject->setOptions(['elementType' => $elementType]);
        $subject->validate($objectStorage);
    }
}
