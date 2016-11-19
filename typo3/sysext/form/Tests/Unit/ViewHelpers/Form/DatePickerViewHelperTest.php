<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Tests\Unit\ViewHelpers\Form;

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
use TYPO3\CMS\Form\ViewHelpers\Form\DatePickerViewHelper;

/**
 * Test case
 */
class DatePickerViewHelperTest extends UnitTestCase
{

    /**
     * @var \TYPO3\CMS\Form\ViewHelpers\Form\DatePickerViewHelper
     */
    protected $subject = null;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = $this->getAccessibleMock(DatePickerViewHelper::class, [
            'dummy'
        ], [], '', false);
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat01()
    {
        $input = 'd';
        $expected = 'dd';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat02()
    {
        $input = 'D';
        $expected = 'D';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat03()
    {
        $input = 'j';
        $expected = 'o';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat04()
    {
        $input = 'l';
        $expected = 'DD';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat05()
    {
        $input = 'F';
        $expected = 'MM';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat06()
    {
        $input = 'm';
        $expected = 'mm';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat07()
    {
        $input = 'M';
        $expected = 'M';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat08()
    {
        $input = 'n';
        $expected = 'm';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat09()
    {
        $input = 'Y';
        $expected = 'yy';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }

    /**
     * @test
     */
    public function convertDateFormatToDatePickerFormatReturnsTransformedFormat10()
    {
        $input = 'y';
        $expected = 'y';
        $this->assertSame($expected, $this->subject->_call('convertDateFormatToDatePickerFormat', $input));
    }
}
