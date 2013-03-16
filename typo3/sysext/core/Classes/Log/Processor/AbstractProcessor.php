<?php
namespace TYPO3\CMS\Core\Log\Processor;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Steffen Müller (typo3@t3node.com)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
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


?>