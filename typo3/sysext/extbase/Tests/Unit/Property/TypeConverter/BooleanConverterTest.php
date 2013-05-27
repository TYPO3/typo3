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
 * Testcase for the Boolean converter
 *
 */
class BooleanConverterTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('boolean', 'string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromDoesNotModifyTheBooleanSource() {
		$source = TRUE;
		$this->assertEquals($source, $this->converter->convertFrom($source, 'boolean'));
	}

	/**
	 * @test
	 */
	public function convertFromCastsSourceStringToBoolean() {
		$source = 'true';
		$this->assertSame(TRUE, $this->converter->convertFrom($source, 'boolean'));
	}

	/**
	 * @test
	 */
	public function convertFromCastsNumericSourceStringToBoolean() {
		$source = '1';
		$this->assertSame(TRUE, $this->converter->convertFrom($source, 'boolean'));
	}
}
?>