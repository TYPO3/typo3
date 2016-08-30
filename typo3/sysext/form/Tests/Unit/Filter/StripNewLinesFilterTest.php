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
class StripNewLinesFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\StripNewLinesFilter
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\StripNewLinesFilter();
    }

    public function dataProviderWithNewlines()
    {
        return [
            'some\rtext' => ["some\rtext", 'some text'],
            'some\ntext' => ["some\ntext", 'some text'],
            'some\r\ntext' => ["some\r\ntext", 'some text'],
            'somechr(13)text' => ['some' . chr(13) . 'text', 'some text'],
            'somechr(10)text' => ['some' . chr(10) . 'text', 'some text'],
            'somechr(13)chr(10)text' => ['some' . chr(13) . chr(10) . 'text', 'some text'],
            'someCRtext' => ['some' . CR . 'text', 'some text'],
            'someLFtext' => ['some' . LF . 'text', 'some text'],
            'someCRLFtext' => ['some' . CRLF . 'text', 'some text'],
            'some^Mtext' => ['some
text', 'some text'],
            'trailing newline\r' => ["trailing newline\n", 'trailing newline '],
            'trailing newline\n' => ["trailing newline\r", 'trailing newline '],
            'trailing newline\r\n' => ["trailing newline\r\n", 'trailing newline '],
            'trailing newlinechr(13)' => ['trailing newline' . chr(13), 'trailing newline '],
            'trailing newlinechr(10)' => ['trailing newline' . chr(10), 'trailing newline '],
            'trailing newlinechr(13)chr(10)' => ['trailing newline' . chr(13) . chr(10), 'trailing newline '],
            'trailing newlineCR' => ['trailing newline' . CR, 'trailing newline '],
            'trailing newlineLF' => ['trailing newline' . LF, 'trailing newline '],
            'trailing newlineCRLF' => ['trailing newline' . CRLF, 'trailing newline '],
            'trailing newline^M' => ['trailing newline
', 'trailing newline ']
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderWithNewlines
     */
    public function filterForStringWithNewlineReturnsStringWithoutNewline($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
