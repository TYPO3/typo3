<?php
namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

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

use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\Dimension
 */
class DimensionTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Imaging\Dimension
     */
    protected $subject = null;

    /**
     * @var int
     */
    protected $width = 32;

    /**
     * @var int
     */
    protected $height = 32;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Core\Imaging\Dimension(Icon::SIZE_DEFAULT);
    }

    /**
     * @test
     */
    public function getWidthReturnsValidInteger()
    {
        $value = $this->subject->getWidth();
        $this->assertEquals($this->width, $value);
        $this->assertInternalType('int', $value);
    }

    /**
     * @test
     */
    public function getHeightReturnsValidInteger()
    {
        $value = $this->subject->getHeight();
        $this->assertEquals($this->height, $value);
        $this->assertInternalType('int', $value);
    }
}
