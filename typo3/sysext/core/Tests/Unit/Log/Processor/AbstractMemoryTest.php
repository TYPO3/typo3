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

/**
 * Test case
 */
class AbstractMemoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getRealMemoryUsageGetsRealMemoryUsage()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $this->assertAttributeEquals($processor->getRealMemoryUsage(), 'realMemoryUsage', $processor);
    }

    /**
     * @test
     */
    public function setRealMemoryUsageSetsRealMemoryUsage()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $processor->setRealMemoryUsage(false);
        $this->assertAttributeEquals(false, 'realMemoryUsage', $processor);
    }

    /**
     * @test
     */
    public function getFormatSizeGetsFormatSize()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $this->assertAttributeEquals($processor->getFormatSize(), 'formatSize', $processor);
    }

    /**
     * @test
     */
    public function setFormatSizeSetsFormatSize()
    {
        /** @var $processor \TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor */
        $processor = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Log\Processor\AbstractMemoryProcessor::class);
        $processor->setFormatSize(false);
        $this->assertAttributeEquals(false, 'formatSize', $processor);
    }
}
