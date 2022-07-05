<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Type;

/**
 * Enum that contains message severities. It is backed by integer values to keep backwards compatibility to the previous
 * AbstractMessage constants.
 */
enum ContextualFeedbackSeverity: int
{
    case NOTICE = -2;
    case INFO = -1;
    case OK = 0;
    case WARNING = 1;
    case ERROR = 2;

    /**
     * @return non-empty-string
     */
    public function getCssClass(): string
    {
        return match ($this) {
            self::NOTICE => 'notice',
            self::INFO => 'info',
            self::OK => 'success',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
        };
    }

    /**
     * @return non-empty-string
     */
    public function getIconIdentifier(): string
    {
        return match ($this) {
            self::NOTICE => 'actions-lightbulb',
            self::INFO => 'actions-info',
            self::OK => 'actions-check',
            self::WARNING => 'actions-exclamation',
            self::ERROR => 'actions-close',
        };
    }

    /**
     * Internal helper method to convert integer based severities into their enum counterparts with logging deprecations.
     *
     * @internal
     * @deprecated Will be removed with TYPO3 13.0
     */
    public static function transform(int $originalSeverity): ?self
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = end($backtrace);
        if (isset($caller['class'])) {
            $callerName = $caller['class'] . $caller['type'] . $caller['function'];
        } else {
            $callerName = $caller['function'];
        }
        $callerLocation = sprintf('file %s, line %d', $caller['file'], $caller['line']);

        $severity = ContextualFeedbackSeverity::tryFrom($originalSeverity);
        if ($severity !== null) {
            trigger_error(sprintf(
                'Calling %s (%s) with an integer severity "%d" will be removed with TYPO3 v13. Consider using %s instead.',
                $callerName,
                $callerLocation,
                $originalSeverity,
                __CLASS__ . '::' . $severity->name
            ), E_USER_DEPRECATED);

            return $severity;
        }

        trigger_error(sprintf(
            'Calling %s (%s) with an invalid severity "%d" will be unsupported with TYPO3 v13.',
            $callerName,
            $callerLocation,
            $originalSeverity
        ), E_USER_DEPRECATED);
        return null;
    }
}
