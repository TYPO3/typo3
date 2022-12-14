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

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

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
        $storagePids = explode(',', $this->settings['pages'] ?? '');
        $storagePids = array_map('intval', $storagePids);

        $recursionDepth = (int)($this->settings['recursive'] ?? 0);
        if ($recursionDepth > 0) {
            $recursiveStoragePids = $storagePids;
            foreach ($storagePids as $startPid) {
                $pids = $this->configurationManager->getContentObject()->getTreeList($startPid, $recursionDepth);
                foreach (GeneralUtility::intExplode(',', $pids, true) as $pid) {
                    $recursiveStoragePids[] = $pid;
                }
            }
            $storagePids = $recursiveStoragePids;
        }

        return array_unique($storagePids);
    }

    protected function getSignedStorageFolders(): string
    {
        $pidList = implode(',', $this->getStorageFolders());
        return sprintf(
            '%s@%s',
            $pidList,
            GeneralUtility::hmac($pidList, FrontendUserAuthentication::class)
        );
    }

    protected function shallEnforceLoginSigning(): bool
    {
        return GeneralUtility::makeInstance(Features::class)
            ->isFeatureEnabled('security.frontend.enforceLoginSigning');
    }
}
