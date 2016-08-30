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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Form\Domain\Filter\TitleCaseFilter;

/**
 * Test case
 */
class TitleCaseFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var TitleCaseFilter
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new TitleCaseFilter();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->csConvObj = new CharsetConverter();
        $GLOBALS['TSFE']->renderCharset = 'utf-8';
    }

    /**
     * @return array
     */
    public function stringProvider()
    {
        return [
            'some text' => ['some text', 'Some Text'],
            'some Text' => ['some Text', 'Some Text'],
            'Ein Maß' => ['Ein Maß', 'Ein Maß'],
            '¿por que?' => ['¿por que?', '¿por Que?'],
        ];
    }

    /**
     * @test
     * @dataProvider stringProvider
     */
    public function filterForStringReturnsStringWithUppercasedWords($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
