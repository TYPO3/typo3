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

namespace TYPO3\CMS\Extbase\Error;

/**
 * An object representation of a generic message. Usually, you will use Error, Warning or Notice instead of this one.
 */
class Message
{
    /**
     * The default (english) error message
     *
     * @var string
     */
    protected $message = 'Unknown message';

    /**
     * The error code
     *
     * @var int
     */
    protected $code;

    /**
     * The message arguments. Will be replaced in the message body.
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * An optional title for the message (used eg. in flashMessages).
     *
     * @var string
     */
    protected $title = '';

    /**
     * Constructs this error
     *
     * @param string $message An english error message which is used if no other error message can be resolved
     * @param int $code A unique error code
     * @param array $arguments Array of arguments to be replaced in message
     * @param string $title optional title for the message
     */
    public function __construct(string $message, int $code, array $arguments = [], string $title = '')
    {
        $this->message = $message;
        $this->code = $code;
        $this->arguments = $arguments;
        $this->title = $title;
    }

    /**
     * Returns the error message
     *
     * @return string The error message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns the error code
     *
     * @return int The error code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Return the rendered message
     *
     * @return string
     */
    public function render(): string
    {
        if (count($this->arguments) > 0) {
            return vsprintf($this->message, $this->arguments);
        }
        return $this->message;
    }

    /**
     * Converts this error into a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
