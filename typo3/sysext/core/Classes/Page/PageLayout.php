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

namespace TYPO3\CMS\Core\Page;

/**
 * Contains information about the layout of a page,
 * mainly which content areas (colPos=0, colPos=1, ...) are used and filled.
 *
 * @internal This is not part of TYPO3 Core API.
 */
class PageLayout
{
    public function __construct(
        protected string $identifier,
        protected string $title,
        protected array $contentAreas,
    ) {}

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContentAreas(): array
    {
        return $this->contentAreas;
    }
}
