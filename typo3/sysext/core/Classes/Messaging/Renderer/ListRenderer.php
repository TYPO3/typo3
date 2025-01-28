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
 * A class representing a html flash message as unordered markup list.
 * It is used in frontend context by default.
 * The created output contains css classes which can be used to style
 * the output individual. Any message contains the message and an
 * optional title which is rendered as <h4> tag if it is set in
 * the FlashMessage object.
 */
readonly class ListRenderer implements FlashMessageRendererInterface
{
    /**
     * Gets the message rendered as clean and secure markup
     *
     * @param FlashMessage[] $flashMessages
     * @return string Representation of the flash message
     */
    public function render(array $flashMessages): string
    {
        $markup = [];
        $markup[] = '<ul class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<li class="alert alert-' . htmlspecialchars($flashMessage->getSeverity()->getCssClass()) . '">';
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
