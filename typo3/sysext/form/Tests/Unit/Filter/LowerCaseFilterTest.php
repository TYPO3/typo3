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
class LowerCaseFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\LowerCaseFilter
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\LowerCaseFilter();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->csConvObj = new \TYPO3\CMS\Core\Charset\CharsetConverter();
        $GLOBALS['TSFE']->renderCharset = 'utf-8';
    }

    public function dataProvider()
    {
        return [
            'a -> a' => ['a', 'a'],
            'A -> a' => ['A', 'a'],
            'AaA -> aaa' => ['AaA', 'aaa'],
            'ÜßbÉØ -> üßbéø' => ['ÜßbÉØ', 'üßbéø'],
            '01A23b -> 01a23b' => ['01A23b', '01a23b'],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function filterForVariousInputReturnsLowercasedInput($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
