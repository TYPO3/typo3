<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site;

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

/**
 * Interface for SiteAware features of TYPO3
 */
interface SiteAwareInterface
{
    /**
     * @param Entity\Site $site
     */
    public function setSite(Entity\Site $site): void;

    /**
     * @return Entity\Site
     */
    public function getSite(): Entity\Site;
}
