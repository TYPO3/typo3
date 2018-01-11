<?php
declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Unit\Core\Charset;

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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CharsetConverterTest extends UnitTestCase
{
    /**
     * Data provider for specialCharactersToAsciiConvertsUmlautsToAscii()
     *
     * @return string[][]
     */
    public function validInputForSpecCharsToAscii(): array
    {
        return [
            'scandinavian input' => [
                'Näe ja koe',
                // See issue #20612 - this is actually a wrong transition, but the way the method currently works
                'Naee ja koe',
            ],
            'german input' => [
                'Größere Änderungswünsche Weißräm',
                'Groessere AEnderungswuensche Weissraem',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider validInputForSpecCharsToAscii
     * @param string $input
     * @param string $expectedString
     */
    public function specCharsToAsciiConvertsUmlautsToAscii(
        string $input,
        string $expectedString
    ) {
        $subject = new CharsetConverter();
        $this->assertSame($expectedString, $subject->specCharsToASCII('utf-8', $input));
    }

    /**
     * Data provider for specialCharactersToAsciiConvertsInvalidInputToEmptyString()
     *
     * @return array[]
     */
    public function invalidInputForSpecCharsToAscii(): array
    {
        return [
            'integer input' => [
                1,
            ],
            'null input' => [
                null,
            ],
            'boolean input' => [
                true,
            ],
            'floating point input' => [
                3.14,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidInputForSpecCharsToAscii
     * @param mixed $input
     */
    public function specCharsToAsciiConvertsInvalidInputToEmptyString($input)
    {
        $subject = new CharsetConverter();
        $this->assertSame('', $subject->specCharsToASCII('utf-8', $input));
    }
}
