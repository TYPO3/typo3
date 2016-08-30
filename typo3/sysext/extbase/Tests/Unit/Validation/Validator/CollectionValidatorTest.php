<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test case
 */
class CollectionValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator::class;

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
     * @return \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function getValidator(array $options = [], array $mockedMethods = ['translateErrorMessage'])
    {
        return $this->getAccessibleMock($this->validatorClassName, $mockedMethods, [$options], '', true);
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->mockValidatorResolver = $this->getAccessibleMock(
            \TYPO3\CMS\Extbase\Validation\ValidatorResolver::class,
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
        $this->assertFalse($this->validator->validate(null)->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorFailsForAValueNotBeingACollection()
    {
        $this->assertTrue($this->validator->validate(new \StdClass())->hasErrors());
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesEveryElementOfACollectionWithTheGivenElementValidator()
    {
        $this->validator->_set('options', ['elementValidator' => 'EmailAddress']);
        $this->mockValidatorResolver->expects($this->exactly(4))
            ->method('createValidator')
            ->with('EmailAddress')
            ->will($this->returnValue($this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator::class, ['translateErrorMessage'])));
        $this->validator->_set('validatorResolver', $this->mockValidatorResolver);
        $arrayOfEmailAddresses = [
            'foo@bar.de',
            'not a valid address',
            'dummy@typo3.org',
            'also not valid'
        ];

        $result = $this->validator->validate($arrayOfEmailAddresses);

        $this->assertTrue($result->hasErrors());
        $this->assertSame(2, count($result->getFlattenedErrors()));
    }

    /**
     * @test
     */
    public function collectionValidatorValidatesNestedObjectStructuresWithoutEndlessLooping()
    {
        $classNameA = $this->getUniqueId('A');
        eval('class ' . $classNameA . '{ public $b = array(); public $integer = 5; }');
        $classNameB = $this->getUniqueId('B');
        eval('class ' . $classNameB . '{ public $a; public $c; public $integer = "Not an integer"; }');
        $A = new $classNameA();
        $B = new $classNameB();
        $A->b = [$B];
        $B->a = $A;
        $B->c = [$A];

        // Create validators
        $aValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, ['translateErrorMessage'], [[]]);
        $this->validator->_set('options', ['elementValidator' => 'Integer']);
        $integerValidator = $this->getMock(\TYPO3\CMS\Extbase\Validation\Validator\IntegerValidator::class, ['translateErrorMessage'], [[]]);

        $this->mockValidatorResolver->expects($this->any())
            ->method('createValidator')
            ->with('Integer')
            ->will($this->returnValue($integerValidator));
        $this->mockValidatorResolver->expects($this->any())
            ->method('buildBaseValidatorConjunction')
            ->will($this->returnValue($aValidator));

            // Add validators to properties
        $aValidator->addPropertyValidator('b', $this->validator);
        $aValidator->addPropertyValidator('integer', $integerValidator);

        $result = $aValidator->validate($A)->getFlattenedErrors();
        $this->assertEquals(1221560494, $result['b.0'][0]->getCode());
    }

    /**
     * @test
     */
    public function collectionValidatorIsValidEarlyReturnsOnUnitializedLazyObjectStorages()
    {
        $parentObject  = new \TYPO3\CMS\Extbase\Tests\Fixture\Entity('Foo');
        $elementType = \TYPO3\CMS\Extbase\Tests\Fixture\Entity::class;
        $lazyObjectStorage = new \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage(
            $parentObject,
            'someProperty',
            ['someNotEmptyValue']
        );
        \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($lazyObjectStorage, 'isInitialized', false, true);
            // only in this test case we want to mock the isValid method
        $validator = $this->getValidator(['elementType' => $elementType], ['isValid']);
        $validator->expects($this->never())->method('isValid');
        $this->mockValidatorResolver->expects($this->never())->method('createValidator');
        $validator->validate($lazyObjectStorage);
    }

    /**
     * @test
     */
    public function collectionValidatorCallsCollectionElementValidatorWhenValidatingObjectStorages()
    {
        $entity = new \TYPO3\CMS\Extbase\Tests\Fixture\Entity('Foo');
        $elementType = \TYPO3\CMS\Extbase\Tests\Fixture\Entity::class;
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $objectStorage->attach($entity);
        $aValidator = new \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator([]);

        $this->mockValidatorResolver->expects($this->never())->method('createValidator');
        $this->mockValidatorResolver->expects($this->once())
            ->method('getBaseValidatorConjunction')
            ->with($elementType)
            ->will($this->returnValue($aValidator));

        $this->validator->_set('options', ['elementType' => $elementType]);

        $this->validator->validate($objectStorage);
    }
}
