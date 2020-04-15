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

use TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class StringConverterTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Property\TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new StringConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['string', 'integer'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('string', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(10, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromShouldReturnSourceString()
    {
        self::assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrue()
    {
        self::assertTrue($this->converter->canConvertFrom('myString', 'string'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
