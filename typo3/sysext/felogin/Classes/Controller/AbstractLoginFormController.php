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

namespace TYPO3\CMS\FrontendLogin\Controller;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
abstract class AbstractLoginFormController extends ActionController
{
    /**
     * Returns the parsed storagePid list including recursions
     */
    protected function getStorageFolders(): array
    {
        if (!($GLOBALS['TYPO3_CONF_VARS']['FE']['checkFeUserPid'] ?? false)) {
            return [];
        }
        $storagePids = GeneralUtility::intExplode(',', (string)($this->settings['pages'] ?? ''), true);
        return GeneralUtility::makeInstance(PageRepository::class)->getPageIdsRecursive($storagePids, (int)($this->settings['recursive'] ?? 0));
    }
}
