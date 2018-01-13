<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation;

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

/**
 * Test case
 */
class ValidatorResolverTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver | \PHPUnit_Framework_MockObject_MockObject | \TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $validatorResolver;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    protected function setUp()
    {
        $this->validatorResolver = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class, ['dummy']);
    }

    /**
     * @test
     */
    public function getValidatorTypeCorrectlyRenamesPhpDataTypes()
    {
        static::assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'integer'));
        static::assertEquals('Integer', $this->validatorResolver->_call('getValidatorType', 'int'));
        static::assertEquals('String', $this->validatorResolver->_call('getValidatorType', 'string'));
        static::assertEquals('Array', $this->validatorResolver->_call('getValidatorType', 'array'));
        static::assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'float'));
        static::assertEquals('Float', $this->validatorResolver->_call('getValidatorType', 'double'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'boolean'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        static::assertEquals('Boolean', $this->validatorResolver->_call('getValidatorType', 'bool'));
        static::assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'number'));
        static::assertEquals('Number', $this->validatorResolver->_call('getValidatorType', 'numeric'));
    }

    /**
     * @test
     */
    public function getValidatorTypeRenamesMixedToRaw()
    {
        static::assertEquals('Raw', $this->validatorResolver->_call('getValidatorType', 'mixed'));
    }

    /**
     * dataProvider for parseValidatorAnnotationCanParseAnnotations
     * @return array
     */
    public function validatorAnnotations(): array
    {
        return [
            [
                '$var Bar',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Bar', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Bar, Foo',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Bar', 'validatorOptions' => []],
                        ['validatorName' => 'Foo', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Baz (Foo=Bar)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => 'Bar']]
                    ]
                ]
            ],
            [
                '$var Buzz (Foo="B=a, r", Baz=1)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Buzz', 'validatorOptions' => ['Foo' => 'B=a, r', 'Baz' => '1']]
                    ]
                ]
            ],
            [
                '$var Foo(Baz=1, Bar=Quux)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Foo', 'validatorOptions' => ['Baz' => '1', 'Bar' => 'Quux']]
                    ]
                ]
            ],
            [
                '$var Pax, Foo(Baz = \'1\', Bar = Quux)',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Pax', 'validatorOptions' => []],
                        ['validatorName' => 'Foo', 'validatorOptions' => ['Baz' => '1', 'Bar' => 'Quux']]
                    ]
                ]
            ],
            [
                '$var Reg (P="[at]*(h|g)"), Quux',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Reg', 'validatorOptions' => ['P' => '[at]*(h|g)']],
                        ['validatorName' => 'Quux', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Baz (Foo="B\\"ar")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => 'B"ar']]
                    ]
                ]
            ],
            [
                '$var F3_TestPackage_Quux',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'F3_TestPackage_Quux', 'validatorOptions' => []]
                    ]
                ]
            ],
            [
                '$var Baz(Foo="5"), Bar(Quux="123")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => '5']],
                        ['validatorName' => 'Bar', 'validatorOptions' => ['Quux' => '123']]
                    ]
                ]
            ],
            [
                '$var Baz(Foo="2"), Bar(Quux=123, Pax="a weird \\"string\\" with *freaky* \\stuff")',
                [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Baz', 'validatorOptions' => ['Foo' => '2']],
                        ['validatorName' => 'Bar', 'validatorOptions' => ['Quux' => '123', 'Pax' => 'a weird "string" with *freaky* \\stuff']]
                    ]
                ]
            ],
            'namespaced validator class name' => [
                'annotation' => '$var F3\TestPackage\Quux',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'F3\TestPackage\Quux', 'validatorOptions' => []]
                    ]
                ]
            ],
            'shorthand notation for system validator' => [
                'annotation' => '$var TYPO3.CMS.Mypkg:MySecond',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'TYPO3.CMS.Mypkg:MySecond', 'validatorOptions' => []]
                    ]
                ]
            ],
            'shorthand notation for custom validator with parameter' => [
                'annotation' => '$var Acme.Mypkg:MyThird(Foo="2")',
                'expected' => [
                    'argumentName' => 'var',
                    'validators' => [
                        ['validatorName' => 'Acme.Mypkg:MyThird', 'validatorOptions' => ['Foo' => '2']]
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validatorAnnotations
     *
     * @param mixed $annotation
     * @param mixed $expectedResult
     */
    public function parseValidatorAnnotationCanParseAnnotations($annotation, $expectedResult)
    {
        $result = $this->validatorResolver->_call('parseValidatorAnnotation', $annotation);
        static::assertEquals($result, $expectedResult);
    }
}
