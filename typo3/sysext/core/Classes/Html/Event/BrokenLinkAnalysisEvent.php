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
    /**
     * see LinkService types
     * @var string
     */
    private $linkType;

    /**
     * Resolved LinkService data, depending on the type
     * @var array
     */
    private $linkData;

    /**
     * @var bool
     */
    private $isBroken = false;

    /**
     * @var bool
     */
    private $linkWasChecked = false;

    /**
     * Message why a link was broken (used in e.g. RteHtmlParser as info)
     * @var string
     */
    private $reason = '';

    public function __construct(string $linkType, array $linkData)
    {
        $this->linkType = $linkType;
        $this->linkData = $linkData;
    }

    public function isPropagationStopped(): bool
    {
        // prevent other listeners from being called if link has been checked
        return $this->linkWasChecked;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

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
