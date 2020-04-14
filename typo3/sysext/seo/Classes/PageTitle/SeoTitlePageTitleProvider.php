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

namespace TYPO3\CMS\Seo\PageTitle;

use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

/**
 * This class will take care of the seo title that can be set in the backend
 * @internal this class is not part of TYPO3's Core API.
 */
class SeoTitlePageTitleProvider extends AbstractPageTitleProvider
{
    public function __construct()
    {
        $this->title = (string)$GLOBALS['TSFE']->page['seo_title'];
    }
}
