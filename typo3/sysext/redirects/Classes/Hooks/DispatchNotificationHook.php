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

namespace TYPO3\CMS\Redirects\Hooks;

/**
 * @internal
 */
final class DispatchNotificationHook
{
    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalCode
     * calls a JS function to send the slug change notification
     *
     * @param array $params
     */
    public function dispatchNotification(&$params)
    {
        $code = '
            // Ensure the event handler is ready and listening to events
            top.window.require(["TYPO3/CMS/Redirects/EventHandler"], function() {
                top.document.dispatchEvent(new CustomEvent("typo3:redirects:slugChanged", { detail: %s }));
            });
        ';
        $payload = json_encode($params['parameter']);
        $params['JScode'] = sprintf($code, $payload);
    }
}
