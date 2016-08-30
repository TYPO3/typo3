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
class IntegerFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\IntegerFilter
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\IntegerFilter();
    }

    public function dataProvider()
    {
        return [
            '"1" -> 1' => ['1', 1],
            '1 -> 1' => [1, 1],
            '1.1 -> 1' => [1.1, 1],
            'a -> 0' => ['a', 0],
            'a42 -> 0' => ['a42', 0],
            '-100.00 -> -100' => [-100.00, -100],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function filterForVariousInputReturnsInputCastedToInteger($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
