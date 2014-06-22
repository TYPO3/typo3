<?php
namespace TYPO3\CMS\Core\Log\Processor;

/**
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
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @author Steffen Müller <typo3@t3node.com>
 */
abstract class AbstractMemoryProcessor extends \TYPO3\CMS\Core\Log\Processor\AbstractProcessor {

	/**
	 * Allocated memory usage type to use
	 * If set, the real size of memory allocated from system is used.
	 * Otherwise the memory used by emalloc() is used.
	 *
	 * @var boolean
	 * @see memory_get_usage()
	 * @see memory_get_peak_usage()
	 */
	protected $realMemoryUsage = TRUE;

	/**
	 * Whether the size is formatted, e.g. in megabytes
	 *
	 * @var boolean
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize()
	 */
	protected $formatSize = TRUE;

	/**
	 * Sets the allocated memory usage type
	 *
	 * @param boolean $realMemoryUsage Which allocated memory type to use
	 * @return void
	 */
	public function setRealMemoryUsage($realMemoryUsage) {
		$this->realMemoryUsage = (bool) $realMemoryUsage;
	}

	/**
	 * Returns the allocated memory usage type
	 *
	 * @return boolean
	 */
	public function getRealMemoryUsage() {
		return $this->realMemoryUsage;
	}

	/**
	 * Sets whether size should be formatted
	 *
	 * @param boolean $formatSize
	 * @return void
	 */
	public function setFormatSize($formatSize) {
		$this->formatSize = (bool) $formatSize;
	}

	/**
	 * Returns whether size should be formatted
	 *
	 * @return boolean
	 */
	public function getFormatSize() {
		return $this->formatSize;
	}

}
