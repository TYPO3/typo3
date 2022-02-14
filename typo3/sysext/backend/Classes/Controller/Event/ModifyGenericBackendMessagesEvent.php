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

namespace TYPO3\CMS\Backend\Controller\Event;

use TYPO3\CMS\Core\Messaging\AbstractMessage;

/**
 * Listeners to this event are able to add or change messages for the "Help > About" module.
 */
final class ModifyGenericBackendMessagesEvent
{
    private array $messages = [];

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(AbstractMessage $message): void
    {
        $this->messages[] = $message;
    }

    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }
}
