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

/**
 * Event which is fired after ContentContentObject has pulled records from database.
 *
 * Therefore, allows listeners to completely manipulate the fetched
 * records, prior to being further processed by the content object.
 *
 * Additionally, the event also allows to manipulate the configuration
 * and options, such as the "value" or "slide".
 */
final class ModifyRecordsAfterFetchingContentEvent
{
    public function __construct(
        private array $records,
        private string $finalContent,
        private int $slide,
        private int $slideCollect,
        private bool $slideCollectReverse,
        private bool $slideCollectFuzzy,
        private array $configuration,
    ) {}

    public function getRecords(): array
    {
        return $this->records;
    }

    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    public function getFinalContent(): string
    {
        return $this->finalContent;
    }

    public function setFinalContent(string $finalContent): void
    {
        $this->finalContent = $finalContent;
    }

    public function getSlide(): int
    {
        return $this->slide;
    }

    public function setSlide(int $slide): void
    {
        $this->slide = $slide;
    }

    public function getSlideCollect(): int
    {
        return $this->slideCollect;
    }

    public function setSlideCollect(int $slideCollect): void
    {
        $this->slideCollect = $slideCollect;
    }

    public function getSlideCollectReverse(): bool
    {
        return $this->slideCollectReverse;
    }

    public function setSlideCollectReverse(bool $slideCollectReverse): void
    {
        $this->slideCollectReverse = $slideCollectReverse;
    }

    public function getSlideCollectFuzzy(): bool
    {
        return $this->slideCollectFuzzy;
    }

    public function setSlideCollectFuzzy(bool $slideCollectFuzzy): void
    {
        $this->slideCollectFuzzy = $slideCollectFuzzy;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
