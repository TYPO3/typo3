<?php
namespace TYPO3\CMS\Lowlevel\Tests\Unit\Utility;

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
 * Testcase for the \TYPO3\CMS\Lowlevel\Utility\ArrayBrowser class in the TYPO3 Core.
 */
class ArrayBrowserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Lowlevel\Utility\ArrayBrowser
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Lowlevel\Utility\ArrayBrowser();
    }

    ///////////////////////////////
    // Tests concerning depthKeys
    ///////////////////////////////
    /**
     * @test
     */
    public function depthKeysWithEmptyFirstParameterAddsNothing()
    {
        $this->assertEquals([], $this->subject->depthKeys([], []));
    }

    /**
     * @test
     */
    public function depthKeysWithNumericKeyAddsOneNumberForKeyFromFirstArray()
    {
        $this->assertEquals([0 => 1], $this->subject->depthKeys(['foo'], []));
    }
}
