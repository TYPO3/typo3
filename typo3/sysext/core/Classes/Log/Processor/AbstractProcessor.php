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
 * Abstract implementation of a log processor
 *
 * @author Steffen Müller <typo3@t3node.com>
 */
abstract class AbstractProcessor implements \TYPO3\CMS\Core\Log\Processor\ProcessorInterface {

	/**
	 * Constructs this log processor
	 *
	 * @param array $options Configuration options - depends on the actual processor
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $options = array()) {
		foreach ($options as $optionKey => $optionValue) {
			$methodName = 'set' . ucfirst($optionKey);
			if (method_exists($this, $methodName)) {
				$this->{$methodName}($optionValue);
			} else {
				throw new \InvalidArgumentException('Invalid log processor option "' . $optionKey . '" for log processor of type "' . get_class($this) . '"', 1321696151);
			}
		}
	}

}
