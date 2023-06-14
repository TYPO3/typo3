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

namespace TYPO3\CMS\Core\Messaging;

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * A class used for any kind of messages.
 */
abstract class AbstractMessage implements \JsonSerializable
{
    protected string $title = '';
    protected string $message = '';
    protected ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @internal
     */
    public function getSeverity(): ContextualFeedbackSeverity
    {
        return $this->severity;
    }

    /**
     * Sets the message' severity
     *
     * @param ContextualFeedbackSeverity $severity
     */
    public function setSeverity(ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $this->severity = $severity;
    }

    /**
     * Creates a string representation of the message. Useful for command
     * line use.
     *
     * @return string A string representation of the message.
     */
    public function __toString()
    {
        $title = '';
        if ($this->title !== '') {
            $title = ' - ' . $this->title;
        }
        return $this->severity->name . $title . ': ' . $this->message;
    }

    /**
     * @return array Data which can be serialized by json_encode()
     */
    public function jsonSerialize(): array
    {
        return [
            'severity' => $this->getSeverity()->value,
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
        ];
    }
}
