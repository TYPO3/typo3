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

namespace TYPO3\CMS\Core\Html\Event;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Event that is fired to validate if a link is valid or not.
 */
final class BrokenLinkAnalysisEvent implements StoppableEventInterface
{
    private bool $isBroken = false;
    private bool $linkWasChecked = false;

    /**
     * Message why a link was broken (used in e.g. RteHtmlParser as info)
     */
    private string $reason = '';

    public function __construct(private readonly string $linkType, private readonly array $linkData) {}

    public function isPropagationStopped(): bool
    {
        // prevent other listeners from being called if link has been checked
        return $this->linkWasChecked;
    }

    /**
     * Returns the link type as string
     * @see LinkService types
     */
    public function getLinkType(): string
    {
        return $this->linkType;
    }

    /**
     * Returns resolved LinkService data, depending on the type
     */
    public function getLinkData(): array
    {
        return $this->linkData;
    }

    public function markAsCheckedLink(): void
    {
        $this->linkWasChecked = true;
    }

    public function markAsBrokenLink(string $reason = ''): void
    {
        $this->isBroken = true;
        $this->reason = $reason;
    }

    public function isBrokenLink(): bool
    {
        return $this->isBroken;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
