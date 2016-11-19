<?php
namespace TYPO3\CMS\Form\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Utility\ArrayUtility;

/**
 * Test case
 */
class ArrayUtilityTest extends UnitTestCase
{

    /**
     * @test
     */
    public function assertAllArrayKeysAreValidThrowsExceptionOnNotAllowedArrayKeys()
    {
        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1325697085);

        $arrayToTest = [
            'roger' => '',
            'francine' => '',
            'stan' => '',
        ];

        $allowedArrayKeys = [
            'roger',
            'francine',
        ];

        ArrayUtility::assertAllArrayKeysAreValid($arrayToTest, $allowedArrayKeys);
    }

    /**
     * @test
     */
    public function assertAllArrayKeysAreValidReturnsNullOnAllowedArrayKeys()
    {
        $arrayToTest = [
            'roger' => '',
            'francine' => '',
            'stan' => '',
        ];

        $allowedArrayKeys = [
            'roger',
            'francine',
            'stan',
        ];

        $this->assertNull(ArrayUtility::assertAllArrayKeysAreValid($arrayToTest, $allowedArrayKeys));
    }

    /**
     * @test
     */
    public function sortNumericArrayKeysRecursiveExpectSorting()
    {
        $input = [
            20 => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            10 => 'a',
            20 => 'b',
            30 => 'c',
            40 => 'd',
            50 => [
                10 => 'b',
                20 => 'a',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::sortNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function sortNumericArrayKeysRecursiveExpectNoSorting()
    {
        $input = [
            'b' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
        ];

        $expected = [
            'b' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
        ];

        $this->assertSame($expected, ArrayUtility::sortNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function reIndexNumericArrayKeysRecursiveExpectReindexing()
    {
        $input = [
            20 => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            0 => 'b',
            1 => 'a',
            2 => 'd',
            3 => 'c',
            4 => [
                0 => 'a',
                1 => 'b',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::reIndexNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function reIndexNumericArrayKeysRecursiveExpectNoReindexing()
    {
        $input = [
            'a' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                20 => 'a',
                10 => 'b',
            ],
        ];

        $expected = [
            'a' => 'b',
            10 => 'a',
            40 => 'd',
            30 => 'c',
            50 => [
                0 => 'a',
                1 => 'b',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::reIndexNumericArrayKeysRecursive($input));
    }

    /**
     * @test
     */
    public function removeNullValuesRecursiveExpectRemoval()
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => null,
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'd' => 'd',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::removeNullValuesRecursive($input));
    }

    /**
     * @test
     */
    public function stripTagsFromValuesRecursiveExpectRemoval()
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => '<b>i am evil</b>',
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'c' => 'i am evil',
                'd' => 'd',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::stripTagsFromValuesRecursive($input));
    }

    /**
     * @test
     */
    public function convertBooleanStringsToBooleanRecursiveExpectConverting()
    {
        $input = [
            'a' => 'a',
            'b' => [
                'c' => 'true',
                'd' => 'd',
            ],
        ];

        $expected = [
            'a' => 'a',
            'b' => [
                'c' => true,
                'd' => 'd',
            ],
        ];

        $this->assertSame($expected, ArrayUtility::convertBooleanStringsToBooleanRecursive($input));
    }
}
