<?php

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
 * Testcase for the DateTime converter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers Tx_Extbase_Property_TypeConverter_DateTimeConverter<extended>
 */
class Tx_Extbase_Tests_Unit_Property_TypeConverter_DateTimeConverterTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Property_TypeConverter_DateTimeConverter
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new Tx_Extbase_Property_TypeConverter_DateTimeConverter();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('DateTime', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}


	/** String to DateTime testcases  **/

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function canConvertFromReturnsFalseIfTargetTypeIsNotDateTime() {
		$this->assertFalse($this->converter->canConvertFrom('Foo', 'SomeOtherType'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function canConvertFromReturnsTrueIfSourceTypeIsAString() {
		$this->assertTrue($this->converter->canConvertFrom('Foo', 'DateTime'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function canConvertFromReturnsFalseIfSourceTypeIsAnEmptyString() {
		$this->assertFalse($this->converter->canConvertFrom('', 'DateTime'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Property_Exception_TypeConverterException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromThrowsExceptionIfGivenStringCantBeConverted() {
		$this->converter->convertFrom('1980-12-13', 'DateTime');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromProperlyConvertsStringWithDefaultDateFormat() {
		$expectedResult = '1980-12-13T20:15:07+01:23';
		$date = $this->converter->convertFrom($expectedResult, 'DateTime');
		$actualResult = $date->format('Y-m-d\TH:i:sP');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromUsesDefaultDateFormatIfItIsNotConfigured() {
		$expectedResult = '1980-12-13T20:15:07+01:23';
		$mockMappingConfiguration = $this->getMock('Tx_Extbase_Property_PropertyMappingConfigurationInterface');
		$mockMappingConfiguration
				->expects($this->atLeastOnce())
				->method('getConfigurationValue')
				->with('Tx_Extbase_Property_TypeConverter_DateTimeConverter', Tx_Extbase_Property_TypeConverter_DateTimeConverter::CONFIGURATION_DATE_FORMAT)
				->will($this->returnValue(NULL));

		$date = $this->converter->convertFrom($expectedResult, 'DateTime', array(), $mockMappingConfiguration);
		$actualResult = $date->format(Tx_Extbase_Property_TypeConverter_DateTimeConverter::DEFAULT_DATE_FORMAT);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @return array
	 * @see convertFromStringTests()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromStringDataProvider() {
		return array(
			array('', '', FALSE),
			array('1308174051', '', FALSE),
			array('13-12-1980', 'd.m.Y', FALSE),
			array('1308174051', 'Y-m-d', FALSE),
			array('12:13', 'H:i', TRUE),
			array('13.12.1980', 'd.m.Y', TRUE),
			array('2005-08-15T15:52:01+00:00', NULL, TRUE),
			array('2005-08-15T15:52:01+0000', DateTime::ISO8601, TRUE),
			array('1308174051', 'U', TRUE),
		);
	}

	/**
	 * @param string $source the string to be converted
	 * @param string $dateFormat the expected date format
	 * @param boolean $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
	 * @test
	 * @dataProvider convertFromStringDataProvider
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromStringTests($source, $dateFormat, $isValid) {
		if ($isValid !== TRUE) {
			$this->setExpectedException('Tx_Extbase_Property_Exception_TypeConverterException');
		}
		if ($dateFormat !== NULL) {
			$mockMappingConfiguration = $this->getMock('Tx_Extbase_Property_PropertyMappingConfigurationInterface');
			$mockMappingConfiguration
					->expects($this->atLeastOnce())
					->method('getConfigurationValue')
					->with('Tx_Extbase_Property_TypeConverter_DateTimeConverter', Tx_Extbase_Property_TypeConverter_DateTimeConverter::CONFIGURATION_DATE_FORMAT)
					->will($this->returnValue($dateFormat));
		} else {
			$mockMappingConfiguration = NULL;
		}
		$date = $this->converter->convertFrom($source, 'DateTime', array(), $mockMappingConfiguration);
		$this->assertInstanceOf('DateTime', $date);
		if ($dateFormat === NULL) {
			$dateFormat = Tx_Extbase_Property_TypeConverter_DateTimeConverter::DEFAULT_DATE_FORMAT;
		}
		$this->assertSame($source, $date->format($dateFormat));
	}

	/** Array to DateTime testcases  **/

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function canConvertFromReturnsTrueIfSourceTypeIsAnArray() {
		$this->assertTrue($this->converter->canConvertFrom(array(), 'DateTime'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Property_Exception_TypeConverterException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromThrowsExceptionIfGivenArrayCantBeConverted() {
		$this->converter->convertFrom(array('date' => '1980-12-13'), 'DateTime');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Property_Exception_TypeConverterException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromThrowsExceptionIfGivenArrayDoesNotSpecifyTheDate() {
		$this->converter->convertFrom(array('hour' => '12', 'minute' => '30'), 'DateTime');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromProperlyConvertsArrayWithDefaultDateFormat() {
		$expectedResult = '1980-12-13T20:15:07+01:23';
		$date = $this->converter->convertFrom(array('date' => $expectedResult), 'DateTime');
		$actualResult = $date->format('Y-m-d\TH:i:sP');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromAllowsToOverrideTheTime() {
		$source = array(
			'date' => '2011-06-16',
			'dateFormat' => 'Y-m-d',
			'hour' => '12',
			'minute' => '30',
			'second' => '59',
		);
		$date = $this->converter->convertFrom($source, 'DateTime');
		$this->assertSame('2011-06-16', $date->format('Y-m-d'));
		$this->assertSame('12', $date->format('H'));
		$this->assertSame('30', $date->format('i'));
		$this->assertSame('59', $date->format('s'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromAllowsToOverrideTheTimezone() {
		$source = array(
			'date' => '2011-06-16',
			'dateFormat' => 'Y-m-d',
			'timezone' => 'Atlantic/Reykjavik',
		);
		$date = $this->converter->convertFrom($source, 'DateTime');
		$this->assertSame('2011-06-16', $date->format('Y-m-d'));
		$this->assertSame('Atlantic/Reykjavik', $date->getTimezone()->getName());
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Property_Exception_TypeConverterException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromThrowsExceptionIfSpecifiedTimezoneIsInvalid() {
		$source = array(
			'date' => '2011-06-16',
			'dateFormat' => 'Y-m-d',
			'timezone' => 'Invalid/Timezone',
		);
		$this->converter->convertFrom($source, 'DateTime');
	}

	/**
	 * @return array
	 * @see convertFromArrayTests()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromArrayDataProvider() {
		return array(
			array(array(), FALSE),
			array(array('date' => '1308174051'), FALSE),
			array(array('date' => '2005-08-15T15:52:01+01:00'), TRUE),
			array(array('date' => '1308174051', 'dateFormat' => ''), FALSE),
			array(array('date' => '13-12-1980', 'dateFormat' => 'd.m.Y'), FALSE),
			array(array('date' => '1308174051', 'dateFormat' => 'Y-m-d'), FALSE),
			array(array('date' => '12:13', 'dateFormat' => 'H:i'), TRUE),
			array(array('date' => '13.12.1980', 'dateFormat' => 'd.m.Y'), TRUE),
			array(array('date' => '2005-08-15T15:52:01+00:00', 'dateFormat' => ''), TRUE),
			array(array('date' => '2005-08-15T15:52:01+0000', 'dateFormat' => DateTime::ISO8601), TRUE),
			array(array('date' => '1308174051', 'dateFormat' => 'U'), TRUE),
		);
	}

	/**
	 * @param array $source the array to be converted
	 * @param boolean $isValid TRUE if the conversion is expected to be successful, otherwise FALSE
	 * @test
	 * @dataProvider convertFromArrayDataProvider
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFromArrayTests(array $source, $isValid) {
		if ($isValid !== TRUE) {
			$this->setExpectedException('Tx_Extbase_Property_Exception_TypeConverterException');
		}
		$dateFormat = isset($source['dateFormat']) && strlen($source['dateFormat']) > 0 ? $source['dateFormat'] : NULL;
		if ($dateFormat !== NULL) {
			$mockMappingConfiguration = $this->getMock('Tx_Extbase_Property_PropertyMappingConfigurationInterface');
			$mockMappingConfiguration
					->expects($this->atLeastOnce())
					->method('getConfigurationValue')
					->with('Tx_Extbase_Property_TypeConverter_DateTimeConverter', Tx_Extbase_Property_TypeConverter_DateTimeConverter::CONFIGURATION_DATE_FORMAT)
					->will($this->returnValue($dateFormat));
		} else {
			$mockMappingConfiguration = NULL;
		}
		$date = $this->converter->convertFrom($source, 'DateTime', array(), $mockMappingConfiguration);
		$this->assertInstanceOf('DateTime', $date);
		if ($dateFormat === NULL) {
			$dateFormat = Tx_Extbase_Property_TypeConverter_DateTimeConverter::DEFAULT_DATE_FORMAT;
		}
		$dateAsString = isset($source['date']) ? $source['date'] : '';
		$this->assertSame($dateAsString, $date->format($dateFormat));
	}

}
?>