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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
final class DispatchNotificationHook
{
    /**
     * Called as a hook in \TYPO3\CMS\Backend\Utility\BackendUtility::getUpdateSignalDetails
     * calls a JS function to send the slug change notification
     *
     * @param array $params
     */
    public function dispatchNotification(&$params)
    {
        $javaScriptRenderer = GeneralUtility::makeInstance(PageRenderer::class)->getJavaScriptRenderer();
        // @todo refactor to directly invoke the redirects slugChanged() method
        // instead of dispatching an event that is only catched by the event dispatcher itself
        $instruction = JavaScriptModuleInstruction::create('@typo3/redirects/event-handler.js')
            ->addFlags(JavaScriptModuleInstruction::FLAG_USE_TOP_WINDOW);
        // Fall back to a single notification, which might still be stored in the session
        $notifications = $params['parameter']['notifications'] ?? [$params['parameter']];
        foreach ($notifications as $notification) {
            $instruction->invoke('dispatchCustomEvent', 'typo3:redirects:slugChanged', $notification);
        }
        $javaScriptRenderer->addJavaScriptModuleInstruction($instruction);
        // not modifying `$params`, since instruction is added to global `PageRenderer`
    }
}
