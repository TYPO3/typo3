<?php
namespace TYPO3\CMS\Core\Log\Writer;

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
 * Abstract implementation of a log writer
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
abstract class AbstractWriter implements \TYPO3\CMS\Core\Log\Writer\WriterInterface {

	/**
	 * Constructs this log writer
	 *
	 * @param array $options Configuration options - depends on the actual log writer
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $options = array()) {
		foreach ($options as $optionKey => $optionValue) {
			$methodName = 'set' . ucfirst($optionKey);
			if (method_exists($this, $methodName)) {
				$this->{$methodName}($optionValue);
			} else {
				throw new \InvalidArgumentException('Invalid log writer option "' . $optionKey . '" for log writer of type "' . get_class($this) . '"', 1321696152);
			}
		}
	}

}
