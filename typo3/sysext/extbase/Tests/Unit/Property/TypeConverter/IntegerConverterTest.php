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
class IntegerConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['integer', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('integer', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldCastTheStringToInteger()
    {
        $this->assertSame(15, $this->converter->convertFrom('15', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyIntegers()
    {
        $source = 123;
        $this->assertSame($source, $this->converter->convertFrom($source, 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified()
    {
        $this->assertNull($this->converter->convertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric()
    {
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Error\Error::class, $this->converter->convertFrom('not numeric', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForANumericStringSource()
    {
        $this->assertTrue($this->converter->canConvertFrom('15', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForAnIntegerSource()
    {
        $this->assertTrue($this->converter->canConvertFrom(123, 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForAnEmptyValue()
    {
        $this->assertTrue($this->converter->canConvertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForANullValue()
    {
        $this->assertTrue($this->converter->canConvertFrom(null, 'integer'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
