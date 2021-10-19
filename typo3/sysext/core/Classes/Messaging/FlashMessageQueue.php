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

namespace TYPO3\CMS\Core\Messaging;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A class which collects and renders flash messages.
 */
class FlashMessageQueue extends \SplQueue implements \JsonSerializable
{
    /**
     * A unique identifier for this queue
     *
     * @var string
     */
    protected $identifier;

    /**
     * @param string $identifier The unique identifier for this queue
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
     * or it enqueues the message.
     *
     * @param FlashMessage $message Instance of \TYPO3\CMS\Core\Messaging\FlashMessage, representing a message
     * @throws \TYPO3\CMS\Core\Exception
     * @return FlashMessageQueue Self to allow chaining
     * @todo: Set return type to void in v12 as breaking patch and drop #[\ReturnTypeWillChange]
     */
    #[\ReturnTypeWillChange]
    public function enqueue($message): FlashMessageQueue
    {
        if (!($message instanceof FlashMessage)) {
            throw new Exception(
                'FlashMessageQueue::enqueue() expects an object of type \TYPO3\CMS\Core\Messaging\FlashMessage but got type "' . (is_object($message) ? get_class($message) : gettype($message)) . '"',
                1376833554
            );
        }
        if ($message->isSessionMessage()) {
            $this->addFlashMessageToSession($message);
        } else {
            parent::enqueue($message);
        }
        return $this;
    }

    /**
     * @param FlashMessage $message
     */
    public function addMessage(FlashMessage $message)
    {
        $this->enqueue($message);
    }

    /**
     * This method is empty, as it will not move any flash message (e.g. from the session)
     *
     * @todo: Set return type to mixed when PHP >= 8.0 is required and drop #[\ReturnTypeWillChange]
     */
    #[\ReturnTypeWillChange]
    public function dequeue()
    {
        // deliberately empty
    }

    /**
     * Adds the given flash message to the array of
     * flash messages that will be stored in the session.
     *
     * @param FlashMessage $message
     */
    protected function addFlashMessageToSession(FlashMessage $message)
    {
        $queuedFlashMessages = $this->getFlashMessagesFromSession();
        $queuedFlashMessages[] = $message;
        $this->storeFlashMessagesInSession($queuedFlashMessages);
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     *
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\AbstractMessage constants
     * @return FlashMessage[]
     */
    public function getAllMessages($severity = null)
    {
        // Get messages from user session
        $queuedFlashMessagesFromSession = $this->getFlashMessagesFromSession();
        $queuedFlashMessages = array_merge($queuedFlashMessagesFromSession, $this->toArray());
        if ($severity !== null) {
            $filteredFlashMessages = [];
            foreach ($queuedFlashMessages as $message) {
                if ($message->getSeverity() === $severity) {
                    $filteredFlashMessages[] = $message;
                }
            }
            return $filteredFlashMessages;
        }

        return $queuedFlashMessages;
    }

    /**
     * Returns all messages from the current PHP session and from the current request.
     * After fetching the messages the internal queue and the message queue in the session
     * will be emptied.
     *
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\AbstractMessage constants
     * @return FlashMessage[]
     */
    public function getAllMessagesAndFlush($severity = null)
    {
        $queuedFlashMessages = $this->getAllMessages($severity);
        // Reset messages in user session
        $this->removeAllFlashMessagesFromSession($severity);
        // Reset internal messages
        $this->clear($severity);
        return $queuedFlashMessages;
    }

    /**
     * Stores given flash messages in the session
     *
     * @param FlashMessage[] $flashMessages
     */
    protected function storeFlashMessagesInSession(array $flashMessages = null)
    {
        if (is_array($flashMessages)) {
            $flashMessages = array_map('json_encode', $flashMessages);
        }
        $user = $this->getUserByContext();
        if ($user instanceof AbstractUserAuthentication) {
            $user->setAndSaveSessionData($this->identifier, $flashMessages);
        }
    }

    /**
     * Removes all flash messages from the session
     *
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\AbstractMessage constants
     */
    protected function removeAllFlashMessagesFromSession($severity = null)
    {
        if (!$this->getUserByContext() instanceof AbstractUserAuthentication) {
            return;
        }
        if ($severity === null) {
            $this->storeFlashMessagesInSession(null);
        } else {
            $messages = $this->getFlashMessagesFromSession();
            foreach ($messages as $index => $message) {
                if ($message->getSeverity() === $severity) {
                    unset($messages[$index]);
                }
            }
            $this->storeFlashMessagesInSession($messages);
        }
    }

    /**
     * Returns current flash messages from the session, making sure to always
     * return an array.
     *
     * @return FlashMessage[]
     */
    protected function getFlashMessagesFromSession(): array
    {
        $sessionMessages = [];
        $user = $this->getUserByContext();
        if ($user instanceof AbstractUserAuthentication) {
            $messagesFromSession = $user->getSessionData($this->identifier);
            $messagesFromSession = is_array($messagesFromSession) ? $messagesFromSession : [];
            foreach ($messagesFromSession as $messageData) {
                $sessionMessages[] = FlashMessage::createFromArray(json_decode($messageData, true));
            }
        }
        return $sessionMessages;
    }

    /**
     * Gets user object by context.
     * This class is also used in install tool, where $GLOBALS['BE_USER'] is not set and can be null.
     *
     * @return AbstractUserAuthentication|null
     */
    protected function getUserByContext(): ?AbstractUserAuthentication
    {
        if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication) {
            return $GLOBALS['TSFE']->fe_user;
        }
        return $GLOBALS['BE_USER'] ?? null;
    }

    /**
     * Fetches and renders all available flash messages from the queue.
     *
     * @param FlashMessageRendererInterface|null $flashMessageRenderer
     * @return string All flash messages in the queue rendered by context based FlashMessageRendererResolver.
     */
    public function renderFlashMessages(FlashMessageRendererInterface $flashMessageRenderer = null)
    {
        $content = '';
        $flashMessages = $this->getAllMessagesAndFlush();

        if (!empty($flashMessages)) {
            if ($flashMessageRenderer === null) {
                $flashMessageRenderer = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve();
            }
            $content = $flashMessageRenderer->render($flashMessages);
        }

        return $content;
    }

    /**
     * Returns all items of the queue as array
     *
     * @return FlashMessage[]
     */
    public function toArray()
    {
        $array = [];
        $this->rewind();
        while ($this->valid()) {
            $array[] = $this->current();
            $this->next();
        }
        return $array;
    }

    /**
     * Removes all items from the queue
     *
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\AbstractMessage constants
     */
    public function clear($severity = null)
    {
        $this->rewind();
        if ($severity === null) {
            while (!$this->isEmpty()) {
                parent::dequeue();
            }
        } else {
            $keysToRemove = [];
            while ($cur = $this->current()) {
                if ($cur->getSeverity() === $severity) {
                    $keysToRemove[] = $this->key();
                }
                $this->next();
            }
            // keys are renumbered when unsetting elements
            // so unset them from last to first
            $keysToRemove = array_reverse($keysToRemove);
            foreach ($keysToRemove as $key) {
                $this->offsetUnset($key);
            }
        }
    }

    /**
     * @return array Data which can be serialized by json_encode()
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
