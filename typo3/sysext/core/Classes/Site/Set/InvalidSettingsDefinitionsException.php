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

namespace TYPO3\CMS\Core\Site\Set;

/**
 * @internal Only to be used by internal site settings functionality
 */
final class InvalidSettingsDefinitionsException extends \RuntimeException
{
    private readonly string $setName;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        string $setName = '',
    ) {
        parent::__construct($message, $code, $previous);
        $this->setName = $setName;
    }

    public function getSetName(): string
    {
        return $this->setName;
    }
}
