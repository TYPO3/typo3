<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

use TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractMemoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setRealMemoryUsageSetsRealMemoryUsage()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(AbstractMemoryProcessor::class);
        $processor->setRealMemoryUsage(false);
        self::assertFalse($processor->getRealMemoryUsage());
    }

    /**
     * @test
     */
    public function setFormatSizeSetsFormatSize()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(AbstractMemoryProcessor::class);
        $processor->setFormatSize(false);
        self::assertFalse($processor->getFormatSize());
    }
}
