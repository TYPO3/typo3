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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StringConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new StringConverter();
    }

    #[Test]
    public function convertFromShouldReturnSourceString(): void
    {
        self::assertEquals('myString', $this->converter->convertFrom('myString', 'string'));
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray(): void
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
