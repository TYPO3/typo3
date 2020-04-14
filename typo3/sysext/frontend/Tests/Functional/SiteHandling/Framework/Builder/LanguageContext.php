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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class LanguageContext implements Applicable
{
    private $languageId;

    public static function create(int $languageId): self
    {
        return new static($languageId);
    }

    private function __construct(int $languageId)
    {
        $this->languageId = $languageId;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function describe(): string
    {
        return sprintf('lang:%d', $this->languageId);
    }
}
