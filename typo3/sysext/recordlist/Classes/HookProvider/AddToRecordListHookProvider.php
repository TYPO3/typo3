<?php

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

namespace TYPO3\CMS\Recordlist\HookProvider;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Event\RenderAdditionalContentToRecordListEvent;

/**
 * This class provides a replacement for the existing hooks of the RecordListController.
 *
 * @internal Please note that this class will likely be removed in TYPO3 v12 and Extension Authors should
 * switch to PSR-14 event listeners.
 */
class AddToRecordListHookProvider
{
    public function __invoke(RenderAdditionalContentToRecordListEvent $event)
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']) && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']) > 0) {
            trigger_error('The hook "recordlist/Modules/Recordlist/index.php" "drawHeaderHook" has been marked as deprecated. Use PSR-14 event RenderAdditionalContentToRecordListEvent insted. ', E_USER_DEPRECATED);
        }
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']) && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook']) > 0) {
            trigger_error('The hook "recordlist/Modules/Recordlist/index.php" "drawFooterHook" has been marked as deprecated. Use PSR-14 event RenderAdditionalContentToRecordListEvent insted. ', E_USER_DEPRECATED);
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook'] ?? [] as $hook) {
            $params = [
                'request' => $event->getRequest(),
            ];
            $null = null;
            $event->addContentAbove(GeneralUtility::callUserFunction($hook, $params, $null));
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawFooterHook'] ?? [] as $hook) {
            $params = [
                'request' => $event->getRequest(),
            ];
            $null = null;
            $event->addContentBelow(GeneralUtility::callUserFunction($hook, $params, $null));
        }
    }
}
