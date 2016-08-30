<?php
namespace TYPO3\CMS\Core\Log\Writer;

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

use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;

/**
 * Abstract implementation of a log writer
 */
abstract class AbstractWriter implements WriterInterface
{
    /**
     * Constructs this log writer
     *
     * @param array $options Configuration options - depends on the actual log writer
     * @throws \TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $optionKey => $optionValue) {
            $methodName = 'set' . ucfirst($optionKey);
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($optionValue);
            } else {
                throw new InvalidLogWriterConfigurationException('Invalid LogWriter configuration option "' . $optionKey . '" for log writer of type "' . get_class($this) . '"', 1321696152);
            }
        }
    }
}
