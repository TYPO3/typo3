<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Redirects\UserFunctions;

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

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Display condition evaluating the feature toggle "redirects.hitCount"
 * @internal This class is a specific TYPO3 display condition implementation and is not part of the Public TYPO3 API.
 */
class HitCountDisplayCondition
{
    /**
     * Check whether the redirects hit count is globally enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('redirects.hitCount');
    }
}
