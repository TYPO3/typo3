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

namespace TYPO3\CMS\Core\Log\Writer;

use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;

/**
 * Abstract implementation of a log writer
 */
abstract class AbstractWriter implements WriterInterface
{
    use BlockSerializationTrait;

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
                throw new InvalidLogWriterConfigurationException('Invalid LogWriter configuration option "' . $optionKey . '" for log writer of type "' . static::class . '"', 1321696152);
            }
        }
    }

    /**
     * Interpolates context values into the message placeholders.
     */
    protected function interpolate(string $message, array $context = []): string
    {
        // Build a replacement array with braces around the context keys.
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && !is_null($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $this->formatContextValue($val);
            }
        }

        // Interpolate replacement values into the message and return.
        return strtr($message, $replace);
    }

    /**
     * Escape or quote a value from the context appropriate for the output.
     *
     * Note: In some output cases, escaping should not be done here but later on output,
     * such as if it's being written to a database for later display.
     *
     * @param string $value
     * @return string
     */
    protected function formatContextValue(string $value): string
    {
        return $value;
    }

    /**
     * Formats an exception into a string.
     *
     * The format here is nearly the same as just casting an exception to a string,
     * but omits the full class namespace and stack trace, as those get very long.
     */
    protected function formatException(\Throwable $ex): string
    {
        $classname = get_class($ex);
        if ($pos = strrpos($classname, '\\')) {
            $classname = substr($classname, $pos + 1);
        }

        return sprintf(
            '- %s: %s, in file %s:%s',
            $classname,
            $ex->getMessage(),
            $ex->getFile(),
            $ex->getLine(),
        );
    }
}
