<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log\Processor;

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
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $processor->setRealMemoryUsage(false);
        $this->assertFalse($processor->getRealMemoryUsage());
    }

    /**
     * @test
     */
    public function setFormatSizeSetsFormatSize()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $processor->setFormatSize(false);
        $this->assertFalse($processor->getFormatSize());
    }
}
