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
class StringConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
     */
    protected $converter;

    protected function setUp()
    {
        $this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['string', 'integer'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('string', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldReturnSourceString()
    {
        $this->assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrue()
    {
        $this->assertTrue($this->converter->canConvertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
