<?php
namespace TYPO3\CMS\Core\Tests\Unit\Charset;

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
 * Testcase for \TYPO3\CMS\Core\Charset\CharsetConverter
 */
class CharsetConverterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Charset\CharsetConverter();
    }

    ////////////////////////////
    // Tests concerning substr
    ////////////////////////////
    /**
     * @test
     * @see http://forge.typo3.org/issues/22334
     */
    public function substrForEmptyStringAndNonZeroLengthReturnsEmptyString()
    {
        $this->assertSame('', $this->subject->substr('utf-8', '', 0, 42));
    }

    /////////////////////////////////
    // Tests concerning utf8_strlen
    /////////////////////////////////
    /**
     * @test
     */
    public function utf8_strlenForNonEmptyAsciiOnlyStringReturnsNumberOfCharacters()
    {
        $this->assertEquals(10, $this->subject->utf8_strlen('good omens'));
    }
}
