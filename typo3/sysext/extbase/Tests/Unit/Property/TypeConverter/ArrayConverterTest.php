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

/**
 * Test case
 */
class ArrayConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter
	 */
	protected $converter;

	protected function setUp() {
		$this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array', 'string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromDoesNotModifyTheSourceArray() {
		$sourceArray = array('Foo' => 'Bar', 'Baz');
		$this->assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
	}

	/**
	 * @return array
	 */
	public function stringToArrayDataProvider() {
		return array(
			'Empty string to empty array' => array('', array()),
		);
	}

	/**
	 * @test
	 * @dataProvider stringToArrayDataProvider
	 *
	 * @param string $source
	 * @param array $expectedResult
	 */
	public function canConvertFromEmptyString($source, $expectedResult) {
		$this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array'));
	}

	/**
	 * @return array
	 */
	public function canConvertFromDataProvider() {
		return array(
			'Can convert empty string' => array('', TRUE),
			'Can not convert not empty string' => array('foo', FALSE),
			'Can convert array' => array(array('foo'), TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider canConvertFromDataProvider
	 *
	 * @param mixed $source
	 * @param bool $expectedResult
	 */
	public function canConvertFromReturnsCorrectBooleans($source, $expectedResult) {
		$this->assertSame($expectedResult, $this->converter->canConvertFrom($source, 'array'));
	}

}
