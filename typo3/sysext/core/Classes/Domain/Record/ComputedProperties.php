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

use TYPO3\CMS\Core\Domain\Page;

/**
 * Contains all information about computed properties for the current context.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class ComputedProperties
{
    public function __construct(
        protected ?int $versionedUid = null,
        protected ?int $localizedUid = null,
        protected ?int $requestedOverlayLanguageId = null,
        protected ?Page $translationSource = null,
    ) {}

    public function getVersionedUid(): ?int
    {
        return $this->versionedUid;
    }

    public function getLocalizedUid(): ?int
    {
        return $this->localizedUid;
    }

    public function getRequestedOverlayLanguageId(): ?int
    {
        return $this->requestedOverlayLanguageId;
    }

    public function getTranslationSource(): ?Page
    {
        return $this->translationSource;
    }

    public function toArray(): array
    {
        return [
            'versionedUid' => $this->versionedUid,
            'localizedUid' => $this->localizedUid,
            'requestedOverlayLanguageId' => $this->requestedOverlayLanguageId,
            'translationSource' => $this->translationSource,
        ];
    }
}
