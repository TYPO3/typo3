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
class AlphanumericFilterTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Filter\AlphanumericFilter
     */
    protected $subject = null;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Form\Domain\Filter\AlphanumericFilter();
    }

    /**
     * @test
     */
    public function filterForStringWithUnicodeCharactersAndSpacesReturnsInputString()
    {
        $input = 'My name contains äøüößØœ';
        // This is default, but let's be explicit:
        $this->subject->setAllowWhiteSpace(true);
        $this->assertSame($input, $this->subject->filter($input));
    }

    /**
     * @test
     */
    public function filterForStringWithUnicodeCharactersAndSpacesWithAllowWhitespaceSetToFalseReturnsInputStringWithoutSpaces()
    {
        $input = 'My name contains äøüößØœ';
        $expected = 'MynamecontainsäøüößØœ';
        $this->subject->setAllowWhiteSpace(false);
        $this->assertSame($expected, $this->subject->filter($input));
    }

    /**
     * @test
     */
    public function filterAllowsNumericCharacters()
    {
        $this->assertSame('foo23bar', $this->subject->filter('foo23bar'));
    }
}
