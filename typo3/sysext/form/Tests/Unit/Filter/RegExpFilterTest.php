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
class RegExpFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\RegExpFilter
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\RegExpFilter();
    }

    public function dataProvider()
    {
        return [
            'a-a -> aa for /-/' => [
                'a-a',
                '/-/',
                'aa'
            ],
            'aaa -> "" for /.+/' => [
                'aaa',
                '/.+/',
                ''
            ],
            'aAa -> aa for /[^a]+/' => [
                'aAa',
                '/[^a]+/',
                'aa'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function filterForStringReturnsInputWithoutCharactersMatchedByRegularExpression($input, $regularExpression, $expected)
    {
        $this->subject->setRegularExpression($regularExpression);
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
