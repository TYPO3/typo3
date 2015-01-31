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
class StringConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
	 */
	protected $converter;

	protected function setUp() {
		$this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'integer'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('string', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromShouldReturnSourceString() {
		$this->assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
	}

	/**
	 * @test
	 */
	public function canConvertFromShouldReturnTrue() {
		$this->assertTrue($this->converter->canConvertFrom('myString', 'string'));
	}

	/**
	 * @test
	 */
	public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray() {
		$this->assertEquals(array(), $this->converter->getSourceChildPropertiesToBeConverted('myString'));
	}

}
