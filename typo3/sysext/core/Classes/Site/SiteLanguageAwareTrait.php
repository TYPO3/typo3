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

namespace TYPO3\CMS\Core\Site;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Helper trait to use a site language within a class.
 *
 * @internal this is not public API yet as this might change, and could be changed within TYPO3 Core at any time.
 */
trait SiteLanguageAwareTrait
{
    /**
     * @var Entity\SiteLanguage
     */
    protected $siteLanguage;

    /**
     * @param Entity\SiteLanguage $siteLanguage
     */
    public function setSiteLanguage(SiteLanguage $siteLanguage)
    {
        $this->siteLanguage = $siteLanguage;
    }

    /**
     * @return Entity\SiteLanguage
     */
    public function getSiteLanguage(): SiteLanguage
    {
        return $this->siteLanguage;
    }
}
