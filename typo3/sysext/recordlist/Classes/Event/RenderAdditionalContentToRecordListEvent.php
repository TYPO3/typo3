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

namespace TYPO3\CMS\Recordlist\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Class AddToRecordListEvent
 *
 * Add content above or below the main content of the record list
 */
final class RenderAdditionalContentToRecordListEvent
{
    private $contentAbove = '';

    private $contentBelow = '';

    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function addContentAbove(string $contentAbove): void
    {
        $this->contentAbove .= $contentAbove;
    }

    public function addContentBelow(string $contentBelow): void
    {
        $this->contentBelow .= $contentBelow;
    }

    public function getAdditionalContentAbove(): string
    {
        return $this->contentAbove;
    }

    public function getAdditionalContentBelow(): string
    {
        return $this->contentBelow;
    }
}
