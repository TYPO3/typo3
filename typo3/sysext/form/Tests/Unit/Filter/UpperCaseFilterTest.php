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
class UpperCaseFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\UpperCaseFilter
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\UpperCaseFilter();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->csConvObj = new \TYPO3\CMS\Core\Charset\CharsetConverter();
        $GLOBALS['TSFE']->renderCharset = 'utf-8';
    }

    public function stringProvider()
    {
        return [
            'asdf' => ['asdf', 'ASDF'],
            'as?df' => ['as?df', 'AS?DF'],
        ];
    }

    /**
     * @test
     * @dataProvider stringProvider
     */
    public function filterForStringReturnsUppercasedString($input, $expected)
    {
        $this->assertSame(
            $expected,
            $this->subject->filter($input)
        );
    }
}
