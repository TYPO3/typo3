<?php
namespace TYPO3\CMS\Core\Log\Processor;

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
 * Common memory processor methods.
 */
abstract class AbstractMemoryProcessor extends AbstractProcessor
{
    /**
     * Allocated memory usage type to use
     * If set, the real size of memory allocated from system is used.
     * Otherwise the memory used by emalloc() is used.
     *
     * @var bool
     * @see memory_get_usage()
     * @see memory_get_peak_usage()
     */
    protected $realMemoryUsage = true;

    /**
     * Whether the size is formatted, e.g. in megabytes
     *
     * @var bool
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize()
     */
    protected $formatSize = true;

    /**
     * Sets the allocated memory usage type
     *
     * @param bool $realMemoryUsage Which allocated memory type to use
     */
    public function setRealMemoryUsage($realMemoryUsage)
    {
        $this->realMemoryUsage = (bool)$realMemoryUsage;
    }

    /**
     * Returns the allocated memory usage type
     *
     * @return bool
     */
    public function getRealMemoryUsage()
    {
        return $this->realMemoryUsage;
    }

    /**
     * Sets whether size should be formatted
     *
     * @param bool $formatSize
     */
    public function setFormatSize($formatSize)
    {
        $this->formatSize = (bool)$formatSize;
    }

    /**
     * Returns whether size should be formatted
     *
     * @return bool
     */
    public function getFormatSize()
    {
        return $this->formatSize;
    }
}
