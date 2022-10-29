<?php

declare(strict_types=1);

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

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class IntegerConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new IntegerConverter();
    }

    /**
     * @test
     */
    public function convertFromShouldCastTheStringToInteger(): void
    {
        self::assertSame(15, $this->converter->convertFrom('15', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyIntegers(): void
    {
        $source = 123;
        self::assertSame($source, $this->converter->convertFrom($source, 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified(): void
    {
        self::assertNull($this->converter->convertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric(): void
    {
        self::assertInstanceOf(Error::class, $this->converter->convertFrom('not numeric', 'integer'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray(): void
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
