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

namespace TYPO3\CMS\Core\Domain\Record;

/**
 * Contains all information about language for a language-aware record.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class LanguageInfo
{
    public function __construct(
        protected int $languageId,
        protected ?int $translationParent,
        protected ?int $translationSource
    ) {}

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getTranslationParent(): ?int
    {
        return $this->translationParent;
    }

    public function getTranslationSource(): ?int
    {
        return $this->translationSource;
    }
}
