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
 * A class representing a bootstrap flash messages.
 * This class renders flash messages as markup, based on the
 * bootstrap HTML/CSS framework. It is used in backend context.
 * The created output contains all classes which are required for
 * the TYPO3 backend. Any kind of message contains also a nice icon.
 */
class BootstrapRenderer implements FlashMessageRendererInterface
{
    /**
     * @var string[] The message severity class names
     */
    protected static $classes = [
        FlashMessage::NOTICE => 'notice',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'success',
        FlashMessage::WARNING => 'warning',
        FlashMessage::ERROR => 'danger',
    ];

    /**
     * @var string[] The message severity icon names
     */
    protected static $icons = [
        FlashMessage::NOTICE => 'lightbulb-o',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'check',
        FlashMessage::WARNING => 'exclamation',
        FlashMessage::ERROR => 'times',
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
        $markup[] = '<div class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<div class="alert ' . htmlspecialchars($this->getClass($flashMessage)) . '">';
            $markup[] = '  <div class="media">';
            $markup[] = '    <div class="media-left">';
            $markup[] = '      <span class="fa-stack fa-lg">';
            $markup[] = '        <i class="fa fa-circle fa-stack-2x"></i>';
            $markup[] = '        <i class="fa fa-' . htmlspecialchars($this->getIconName($flashMessage)) . ' fa-stack-1x"></i>';
            $markup[] = '      </span>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="media-body">';
            if ($messageTitle !== '') {
                $markup[] = '      <h4 class="alert-title">' . htmlspecialchars($messageTitle) . '</h4>';
            }
            $markup[] = '      <p class="alert-message">' . htmlspecialchars($flashMessage->getMessage()) . '</p>';
            $markup[] = '    </div>';
            $markup[] = '  </div>';
            $markup[] = '</div>';
        }
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
