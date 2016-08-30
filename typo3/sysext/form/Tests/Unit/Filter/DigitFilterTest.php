<?php
namespace TYPO3\CMS\Form\Tests\Unit\Filter;

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
class DigitFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\DigitFilter
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\DigitFilter();
    }

    public function validDataProvider()
    {
        return [
            '1,00 -> 100' => ['1,00', '100'],
            '1E+49 -> 149' => ['1E+49', '149'],
            '100 -> 100' => ['100', '100'],
            '00000 -> 00000' => ['00000', '00000'],
            'ABCD -> ""' => ['ABCD', ''],
        ];
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function filterForStringsReturnsStringsFilteredToOnlyContainDigits($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
