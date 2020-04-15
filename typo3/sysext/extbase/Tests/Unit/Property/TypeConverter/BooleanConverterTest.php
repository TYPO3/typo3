<?php

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

namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

use TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BooleanConverterTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new BooleanConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['boolean', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheBooleanSource()
    {
        $source = true;
        self::assertEquals($source, $this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsSourceStringToBoolean()
    {
        $source = 'true';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsNumericSourceStringToBoolean()
    {
        $source = '1';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }
}
