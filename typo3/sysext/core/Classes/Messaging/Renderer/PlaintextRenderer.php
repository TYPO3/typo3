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

namespace TYPO3\CMS\Core\Messaging\Renderer;

use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * A class representing a html flash message as plain text.
 * It is used in CLI context per default.
 * The created output contains at least the severity and the message
 * in the following format:
 * [SEVERITY] <message>
 *
 * Example:
 * [ERROR] No record found
 *
 * In case the FlashMessage object contains also a title, the
 * following format is used:
 * [SEVERITY] <title>: <message>
 *
 * Example:
 * [ERROR] An error occurred: No record found
 *
 * Multiple messages are separated by a new line (LF).
 */
class PlaintextRenderer implements FlashMessageRendererInterface
{
    /**
     * Message types
     * @var array
     */
    protected static $type = [
        FlashMessage::NOTICE => 'NOTICE',
        FlashMessage::INFO => 'INFO',
        FlashMessage::OK => 'SUCCESS',
        FlashMessage::WARNING => 'WARNING',
        FlashMessage::ERROR => 'DANGER',
    ];

    /**
     * Render method
     *
     * @param FlashMessage[] $flashMessages
     * @return string Representation of the flash message as plain text
     */
    public function render(array $flashMessages): string
    {
        $messages = [];

        foreach ($flashMessages as $flashMessage) {
            $message = $flashMessage->getMessage();
            if ($flashMessage->getTitle() !== '') {
                $message = $flashMessage->getTitle() . ': ' . $message;
            }
            $messages[] = '[' . self::$type[$flashMessage->getSeverity()] . '] ' . $message;
        }
        return implode(LF, $messages);
    }
}
