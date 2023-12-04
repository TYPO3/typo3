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

namespace TYPO3\CMS\IndexedSearch\Event;

use Psr\Http\Message\ServerRequestInterface;

final class EnableIndexingEvent
{
    private bool $enableIndexing = false;

    public function __construct(
        private readonly ServerRequestInterface $request,
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function enableIndexing(): void
    {
        $this->enableIndexing = true;
    }

    public function isIndexingEnabled(): bool
    {
        return $this->enableIndexing;
    }
}
