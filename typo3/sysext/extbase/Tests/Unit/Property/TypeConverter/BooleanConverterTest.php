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
class BooleanConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter
	 */
	protected $converter;

	protected function setUp() {
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
