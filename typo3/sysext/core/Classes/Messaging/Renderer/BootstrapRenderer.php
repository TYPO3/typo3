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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * A class representing a bootstrap flash messages.
 * This class renders flash messages as markup, based on the
 * bootstrap HTML/CSS framework. It is used in backend context.
 * The created output contains all classes which are required for
 * the TYPO3 backend. Any kind of message contains also a nice icon.
 */
#[Autoconfigure(public: true)]
readonly class BootstrapRenderer implements FlashMessageRendererInterface
{
    public function __construct(
        private IconFactory $iconFactory,
    ) {}

    /**
     * Gets the message rendered as clean and secure markup
     *
     * @param FlashMessage[] $flashMessages
     * @return string Representation of the flash message
     */
    public function render(array $flashMessages): string
    {
        $markup = [];
        $markup[] = '<div class="typo3-messages">';
        foreach ($flashMessages as $flashMessage) {
            $messageTitle = $flashMessage->getTitle();
            $markup[] = '<div class="alert alert-' . htmlspecialchars($flashMessage->getSeverity()->getCssClass()) . '">';
            $markup[] = '  <div class="alert-inner">';
            $markup[] = '    <div class="alert-icon">';
            $markup[] = '      <span class="icon-emphasized">';
            $markup[] =            $this->iconFactory->getIcon($flashMessage->getSeverity()->getIconIdentifier(), IconSize::SMALL)->render();
            $markup[] = '      </span>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="alert-content">';
            if ($messageTitle !== '') {
                $markup[] = '      <div class="alert-title">' . htmlspecialchars($messageTitle) . '</div>';
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
