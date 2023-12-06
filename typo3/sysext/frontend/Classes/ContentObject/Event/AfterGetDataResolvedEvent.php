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

namespace TYPO3\CMS\Frontend\ContentObject\Event;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Listeners are able to modify the resolved ContentObjectRenderer->getData() result
 */
final class AfterGetDataResolvedEvent
{
    public function __construct(
        private readonly string $parameterString,
        private readonly array $alternativeFieldArray,
        private mixed $result,
        private readonly ContentObjectRenderer $contentObjectRenderer
    ) {}

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    public function getParameterString(): string
    {
        return $this->parameterString;
    }

    public function getAlternativeFieldArray(): array
    {
        return $this->alternativeFieldArray;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }
}
