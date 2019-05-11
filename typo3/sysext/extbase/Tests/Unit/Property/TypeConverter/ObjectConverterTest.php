<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

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

use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;
use TYPO3\CMS\Extbase\Reflection\ClassSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ObjectConverterTest extends UnitTestCase
{
    /**
     * @var ObjectConverter
     */
    protected $converter;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockReflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContainer;

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockReflectionService = $this->createMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $this->mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockContainer = $this->createMock(\TYPO3\CMS\Extbase\Object\Container\Container::class);

        $this->converter = new ObjectConverter();
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
        $this->inject($this->converter, 'objectContainer', $this->mockContainer);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return array
     */
    public function dataProviderForCanConvert()
    {
        return [
            // Is entity => cannot convert
            [\TYPO3\CMS\Extbase\Tests\Fixture\Entity::class, false],
            // Is valueobject => cannot convert
            [\TYPO3\CMS\Extbase\Tests\Fixture\ValueObject::class, false],
            // Is no entity and no value object => can convert
            ['stdClass', true]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForCanConvert
     * @param $className
     * @param $expected
     */
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($className, $expected)
    {
        $this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', $className));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType(): void
    {
        $classSchemaMock = $this->createMock(ClassSchema::class);
        $classSchemaMock->expects($this->any())->method('getMethod')->with('__construct')->willReturn(new ClassSchema\Method(
            '__construct',
            [
                'params' => [
                    'thePropertyName' => [
                        'type' => 'TheTypeOfSubObject',
                        'elementType' => null
                    ]
                ]
            ],
            get_class($classSchemaMock)
        ));

        $this->mockReflectionService
            ->expects($this->any())
            ->method('getClassSchema')
            ->with('TheTargetType')
            ->willReturn($classSchemaMock);

        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue('TheTargetType'));

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, []);
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
