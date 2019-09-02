<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Notification;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The NotificationService is responsible for generating notifications (not FlashMessages) in the
 * backend. This PHP API provide methods to create JavaScript notifications popups in the top
 * right corner of the TYPO3 backend.
 * The scope of this API is backend only! If you need something similar for the frontend
 * or in CLI context, the FlashMessage API is your friend or you have to implement your own logic.
 */
final class NotificationService
{
    private const TYPE_NOTICE = 'notice';
    private const TYPE_INFO = 'info';
    private const TYPE_SUCCESS = 'success';
    private const TYPE_WARNING = 'warning';
    private const TYPE_ERROR = 'error';

    /**
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    public function notice(string $title, string $message, int $duration = 5, array $actions = []): void
    {
        $this->createNotification(static::TYPE_NOTICE, $title, $message, $duration, $actions);
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    public function info(string $title, string $message, int $duration = 5, array $actions = []): void
    {
        $this->createNotification(static::TYPE_INFO, $title, $message, $duration, $actions);
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    public function success(string $title, string $message, int $duration = 5, array $actions = []): void
    {
        $this->createNotification(static::TYPE_SUCCESS, $title, $message, $duration, $actions);
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    public function warning(string $title, string $message, int $duration = 5, array $actions = []): void
    {
        $this->createNotification(static::TYPE_WARNING, $title, $message, $duration, $actions);
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    public function error(string $title, string $message, int $duration = 0, array $actions = []): void
    {
        $this->createNotification(static::TYPE_ERROR, $title, $message, $duration, $actions);
    }

    /**
     * @param string $type
     * @param string $title
     * @param string $message
     * @param int $duration
     * @param Action[] $actions
     */
    private function createNotification(string $type, string $title, string $message, int $duration, array $actions = []): void
    {
        $actionFunctionTemplate = 'const %s = new %s(function() { %s });';
        $actionDefinitionTemplate = '{label: %s, action: %s}';
        $actionItemFunctions = [];
        $actionItemDefinitions = [];
        $i = 1;
        foreach ($actions as $action) {
            $actionName = 'action' . $i++;
            $actionItemFunctions[] = sprintf($actionFunctionTemplate, $actionName, $action->getType(), $action->getCallbackCode());
            $actionItemDefinitions[] = sprintf($actionDefinitionTemplate, GeneralUtility::quoteJSvalue($action->getLabel()), $actionName);
        }
        GeneralUtility::makeInstance(PageRenderer::class)
            ->loadRequireJsModule('TYPO3/CMS/Backend/Notification', sprintf('function(Notification) {
                require([\'TYPO3/CMS/Backend/ActionButton/DeferredAction\', \'TYPO3/CMS/Backend/ActionButton/ImmediateAction\'], function(DeferredAction, ImmediateAction) {
                    %s
                    Notification.%s(%s, %s, %d, [%s]);
                });
            }', implode(LF, $actionItemFunctions), $type, GeneralUtility::quoteJSvalue($title), GeneralUtility::quoteJSvalue($message), $duration, implode(',', $actionItemDefinitions)));
    }
}
