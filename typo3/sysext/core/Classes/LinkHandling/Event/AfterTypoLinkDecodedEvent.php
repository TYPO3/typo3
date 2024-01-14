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

namespace TYPO3\CMS\Core\LinkHandling\Event;

/**
 * Listeners are able to modify the decoded link parts of a TypoLink
 */
final class AfterTypoLinkDecodedEvent
{
    public function __construct(
        private array $typoLinkParts,
        private readonly string $typoLink,
        private readonly string $delimiter,
        private readonly string $emptyValueSymbol
    ) {}

    public function getTypoLinkParts(): array
    {
        return $this->typoLinkParts;
    }

    public function setTypoLinkParts(array $typoLinkParts): void
    {
        $this->typoLinkParts = $typoLinkParts;
    }

    public function getTypoLink(): string
    {
        return $this->typoLink;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function getEmptyValueSymbol(): string
    {
        return $this->emptyValueSymbol;
    }
}
