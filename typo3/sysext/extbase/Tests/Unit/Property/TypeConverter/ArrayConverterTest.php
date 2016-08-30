<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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
class ArrayConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['array', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheSourceArray()
    {
        $sourceArray = ['Foo' => 'Bar', 'Baz'];
        $this->assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
    }

    /**
     * @return array
     */
    public function stringToArrayDataProvider()
    {
        return [
            'Empty string to empty array' => ['', []],
        ];
    }

    /**
     * @test
     * @dataProvider stringToArrayDataProvider
     *
     * @param string $source
     * @param array $expectedResult
     */
    public function canConvertFromEmptyString($source, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array'));
    }

    /**
     * @return array
     */
    public function canConvertFromDataProvider()
    {
        return [
            'Can convert empty string' => ['', true],
            'Can not convert not empty string' => ['foo', false],
            'Can convert array' => [['foo'], true],
        ];
    }

    /**
     * @test
     * @dataProvider canConvertFromDataProvider
     *
     * @param mixed $source
     * @param bool $expectedResult
     */
    public function canConvertFromReturnsCorrectBooleans($source, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->converter->canConvertFrom($source, 'array'));
    }
}
