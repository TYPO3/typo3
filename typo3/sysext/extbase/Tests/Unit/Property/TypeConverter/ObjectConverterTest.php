<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

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
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;

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
    protected function setUp()
    {
        $this->mockReflectionService = $this->getMock(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $this->mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $this->mockContainer = $this->getMock(\TYPO3\CMS\Extbase\Object\Container\Container::class);

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
        $this->assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
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
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(false));
        $this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue([
            'thePropertyName' => [
                'type' => 'TheTypeOfSubObject',
                'elementType' => null
            ]
        ]));
        $this->mockContainer->expects($this->any())->method('getImplementationClassName')->will($this->returnValue('TheTargetType'));

        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, []);
        $this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }
}
