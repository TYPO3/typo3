<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\Imaging;

use TYPO3\CMS\Core\Imaging\Dimension;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Imaging\Dimension
 */
class DimensionTest extends UnitTestCase
{
    protected ?Dimension $subject;
    protected int $width = 32;
    protected int $height = 32;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Dimension(Icon::SIZE_DEFAULT);
    }

    /**
     * @test
     */
    public function getWidthReturnsValidInteger(): void
    {
        $value = $this->subject->getWidth();
        self::assertEquals($this->width, $value);
        self::assertIsInt($value);
    }

    /**
     * @test
     */
    public function getHeightReturnsValidInteger(): void
    {
        $value = $this->subject->getHeight();
        self::assertEquals($this->height, $value);
        self::assertIsInt($value);
    }
}
