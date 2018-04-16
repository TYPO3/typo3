<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Validation;

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

use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException;

/**
 * Test case
 */
class ValidatorResolverTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsReturnsEmptyArrayIfMethodHasNoArguments()
    {
        $mockController = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\ActionController::class, ['fooAction'], [], '', false);
        $methodParameters = [];
        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockController), 'fooAction');
        $this->assertSame([], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsBuildsAConjunctionFromValidateAnnotationsOfTheSpecifiedMethod()
    {
        $mockObject = $this->getMockBuilder('stdClass')
            ->setMethods(['fooMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ],
            'arg2' => [
                'type' => 'array'
            ]
        ];
        $methodTagsValues = [
            'param' => [
                'string $arg1',
                'array $arg2'
            ],
            'validate' => [
                '$arg1 Foo(bar = baz), Bar',
                '$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
            ]
        ];
        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockStringValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockArrayValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockFooValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockBarValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $conjunction1 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $conjunction1->expects($this->at(1))->method('addValidator')->with($mockFooValidator);
        $conjunction1->expects($this->at(2))->method('addValidator')->with($mockBarValidator);
        $conjunction2 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction2->expects($this->at(0))->method('addValidator')->with($mockArrayValidator);
        $conjunction2->expects($this->at(1))->method('addValidator')->with($mockQuuxValidator);
        $mockArguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg1', 'dummyValue'));
        $mockArguments->addArgument(new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('arg2', 'dummyValue'));
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction2));
        $validatorResolver->expects($this->at(3))->method('createValidator')->with('array')->will($this->returnValue($mockArrayValidator));
        $validatorResolver->expects($this->at(4))->method('createValidator')->with('Foo', ['bar' => 'baz'])->will($this->returnValue($mockFooValidator));
        $validatorResolver->expects($this->at(5))->method('createValidator')->with('Bar')->will($this->returnValue($mockBarValidator));
        $validatorResolver->expects($this->at(6))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $result = $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
        $this->assertEquals(['arg1' => $conjunction1, 'arg2' => $conjunction2], $result);
    }

    /**
     * @test
     */
    public function buildMethodArgumentsValidatorConjunctionsThrowsExceptionIfValidationAnnotationForNonExistingArgumentExists()
    {
        $this->expectException(InvalidValidationConfigurationException::class);
        $this->expectExceptionCode(1253172726);
        $mockObject = $this->getMockBuilder('stdClass')
            ->setMethods(['fooMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $methodParameters = [
            'arg1' => [
                'type' => 'string'
            ]
        ];
        $methodTagsValues = [
            'param' => [
                'string $arg1'
            ],
            'validate' => [
                '$arg2 VENDOR\\ModelCollection\\Domain\\Model\\Model'
            ]
        ];
        $mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodTagsValues));
        $mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockObject), 'fooAction')->will($this->returnValue($methodParameters));
        $mockStringValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $mockQuuxValidator = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class);
        $conjunction1 = $this->createMock(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
        $conjunction1->expects($this->at(0))->method('addValidator')->with($mockStringValidator);
        $validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['createValidator']);
        $validatorResolver->expects($this->at(0))->method('createValidator')->with(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class)->will($this->returnValue($conjunction1));
        $validatorResolver->expects($this->at(1))->method('createValidator')->with('string')->will($this->returnValue($mockStringValidator));
        $validatorResolver->expects($this->at(2))->method('createValidator')->with('VENDOR\\ModelCollection\\Domain\\Model\\Model')->will($this->returnValue($mockQuuxValidator));
        $validatorResolver->_set('reflectionService', $mockReflectionService);
        $validatorResolver->buildMethodArgumentsValidatorConjunctions(get_class($mockObject), 'fooAction');
    }
}
