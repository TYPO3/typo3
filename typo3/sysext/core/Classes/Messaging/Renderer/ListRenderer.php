<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Messaging\Renderer;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * A class representing a html flash message as unordered markup list.
 * It is used in frontend context per default.
 * The created output contains css classes which can be used to style
 * the output individual. Any message contains the message and an
 * optional title which is rendered as <h4> tag if it is set in
 * the FlashMessage object.
 */
class ListRenderer implements FlashMessageRendererInterface
{
    /**
     * @var string The message severity class names
     */
    protected static $classes = [
        FlashMessage::NOTICE => 'notice',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'success',
        FlashMessage::WARNING => 'warning',
        FlashMessage::ERROR => 'danger'
    ];

    /**
     * @var string The message severity icon names
     */
    protected static $icons = [
        FlashMessage::NOTICE => 'lightbulb-o',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'check',
        FlashMessage::WARNING => 'exclamation',
        FlashMessage::ERROR => 'times'
    ];

    /**
     * Render method
     *
     * @param FlashMessage[] $flashMessages
     * @return string Representation of the flash message
     */
    public function render(array $flashMessages): string
    {
        return $this->getMessageAsMarkup($flashMessages);
    }

    /**
     * Gets the message severity class name
     *
     * @param FlashMessage $flashMessage
     *
     * @return string The message severity class name
     */
    protected function getClass(FlashMessage $flashMessage): string
    {
        return 'alert-' . self::$classes[$flashMessage->getSeverity()];
    }

    /**
     * Gets the message severity icon name
     *
     * @param FlashMessage $flashMessage
     *
     * @return string The message severity icon name
     */
    protected function getIconName(FlashMessage $flashMessage): string
    {
        return self::$icons[$flashMessage->getSeverity()];
    }

    /**
     * Gets the message rendered as clean and secure markup
     *
     * @param FlashMessage[] $flashMessages
     * @return string
     */
    protected function getMessageAsMarkup(array $flashMessages): string
    {
        $markup = [];
        $markup[] = '<ul class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<li class="alert ' . htmlspecialchars($this->getClass($flashMessage)) . '">';
            if ($messageTitle !== '') {
                $markup[] = '<h4 class="alert-title">' . htmlspecialchars($messageTitle) . '</h4>';
            }
            $markup[] = '<p class="alert-message">' . htmlspecialchars($flashMessage->getMessage()) . '</p>';
            $markup[] = '</li>';
        }
        $markup[] = '</ul>';
        return implode('', $markup);
    }
}
