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

namespace TYPO3\CMS\Backend\Form\Event;

/**
 * Listeners to this Event will be able to modify the link explanation array, used in FormEngine for link fields
 */
final class ModifyLinkExplanationEvent
{
    private array $linkExplanation;

    public function __construct(
        array $linkExplanation,
        private readonly array $linkData,
        private readonly array $linkParts,
        private readonly array $elementData,
    ) {
        $this->linkExplanation = $linkExplanation;
    }

    public function getLinkData(): array
    {
        return $this->linkData;
    }

    public function getLinkParts(): array
    {
        return $this->linkParts;
    }

    public function getElementData(): array
    {
        return $this->elementData;
    }

    public function getLinkExplanation(): array
    {
        return $this->linkExplanation;
    }

    public function setLinkExplanation(array $linkExplanation): void
    {
        $this->linkExplanation = $linkExplanation;
    }

    public function getLinkExplanationValue(string $key, mixed $default = null): mixed
    {
        return $this->linkExplanation[$key] ?? $default;
    }

    public function setLinkExplanationValue(string $key, mixed $value): void
    {
        $this->linkExplanation[$key] = $value;
    }
}
