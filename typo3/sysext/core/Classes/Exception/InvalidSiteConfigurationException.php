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

namespace TYPO3\CMS\Core\Exception;

/**
 * Exception thrown if a site configuration contains an invalid value.
 *
 * The configuration path points to the offending option in dot notation,
 * for example "base" or "languages.1.base".
 */
final class InvalidSiteConfigurationException extends \InvalidArgumentException
{
    public function __construct(
        string $message,
        int $code,
        public readonly string $configurationPath,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
