<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\PageTitle;

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
 * Class to handle $GLOBALS['TSFE']->altPageTitle as input for the page title
 *
 * @deprecated since TYPO3 v9.4 and will be removed in TYPO3 v10.0
 */
class AltPageTitleProvider extends AbstractPageTitleProvider
{
    /**
     * @return string
     */
    public function getTitle(): string
    {
        if (!empty($GLOBALS['TSFE']->altPageTitle)) {
            trigger_error('$TSFE->altPageTitle will be removed in TYPO3 v10.0. Please use the TitleTag API to set the title tag.', E_USER_DEPRECATED);

            return $GLOBALS['TSFE']->altPageTitle;
        }

        return '';
    }
}
