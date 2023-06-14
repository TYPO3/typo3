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
}
