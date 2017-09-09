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

use TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException;

/**
 * Abstract implementation of a log processor
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    /**
     * Constructs this log processor
     *
     * @param array $options Configuration options - depends on the actual processor
     * @throws \TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $optionKey => $optionValue) {
            $methodName = 'set' . ucfirst($optionKey);
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($optionValue);
            } else {
                throw new InvalidLogProcessorConfigurationException('Invalid LogProcessor configuration option "' . $optionKey . '" for log processor of type "' . static::class . '"', 1321696151);
            }
        }
    }
}
