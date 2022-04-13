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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Event;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * A PSR-14 event fired when sys_template rows have been fetched.
 *
 * This event is intended to add own rows based on given rows or site resolution.
 */
final class AfterTemplatesHaveBeenDeterminedEvent
{
    public function __construct(
        private readonly array $rootline,
        private readonly SiteInterface $site,
        private array $templateRows,
    ) {
    }

    public function getRootline(): array
    {
        return $this->rootline;
    }

    public function getSite(): SiteInterface
    {
        return $this->site;
    }

    public function getTemplateRows(): array
    {
        return $this->templateRows;
    }

    public function setTemplateRows(array $templateRows): void
    {
        $this->templateRows = $templateRows;
    }
}
