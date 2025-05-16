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

namespace TYPO3\CMS\Core\Mail;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\DelayedEnvelope;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Inspired by SwiftMailer, adapted for TYPO3 and Symfony/Mailer
 *
 * @internal This class is experimental and subject to change!
 */
class FileSpool extends AbstractTransport implements DelayedTransportInterface
{
    /**
     * File WriteRetry Limit.
     */
    protected int $retryLimit = 10;

    /**
     * The maximum number of messages to send per flush
     */
    protected int $messageLimit;

    /**
     * The time limit per flush
     */
    protected int $timeLimit;

    /**
     * Create a new FileSpool, storing messages in $path.
     */
    public function __construct(
        protected string $path,
        ?EventDispatcherInterface $dispatcher = null,
        protected readonly ?LoggerInterface $logger = null
    ) {
        parent::__construct($dispatcher, $logger);

        if (!file_exists($this->path)) {
            GeneralUtility::mkdir_deep($this->path);
        }
    }

    /**
     * Stores a message in the queue.
     */
    protected function doSend(SentMessage $message): void
    {
        $fileName = $this->path . '/' . $this->getRandomString(9);
        $i = 0;

        // We try an exclusive creation of the file. This is an atomic
        // operation, it avoids a locking mechanism
        do {
            $fileName .= $this->getRandomString(1);
            $filePointer = @fopen($fileName . '.message', 'x');
        } while ($filePointer === false && ++$i < $this->retryLimit);

        if ($filePointer === false) {
            throw new TransportException('Could not create file for spooling', 1602615347);
        }

        try {
            $ser = serialize($message);
            if (fwrite($filePointer, $ser) === false) {
                throw new TransportException('Could not write file for spooling', 1602615348);
            }
        } finally {
            fclose($filePointer);
        }
    }

    /**
     * Allow to manage the enqueuing retry limit.
     *
     * Default is ten and allows over 64^20 different fileNames
     */
    public function setRetryLimit(int $limit): void
    {
        $this->retryLimit = $limit;
    }

    /**
     * Execute a recovery if for any reason a process is sending for too long.
     *
     * @param int $timeout in second Defaults is for very slow smtp responses
     */
    public function recover(int $timeout = 900): void
    {
        foreach (new \DirectoryIterator($this->path) as $file) {
            $file = (string)$file->getRealPath();

            if (str_ends_with($file, '.message.sending')) {
                $lockedtime = filectime($file);
                if ((time() - $lockedtime) > $timeout) {
                    rename($file, substr($file, 0, -8));
                }
            }
        }
    }

    public function flushQueue(TransportInterface $transport): int
    {
        $directoryIterator = new \DirectoryIterator($this->path);

        $count = 0;
        $time = time();
        foreach ($directoryIterator as $file) {
            $file = (string)$file->getRealPath();

            if (!str_ends_with($file, '.message')) {
                continue;
            }

            /* We try a rename, it's an atomic operation, and avoid locking the file */
            if (rename($file, $file . '.sending')) {
                $message = unserialize((string)file_get_contents($file . '.sending'), [
                    'allowedClasses' => [
                        RawMessage::class,
                        Message::class,
                        Email::class,
                        DelayedEnvelope::class,
                        Envelope::class,
                    ],
                ]);

                $transport->send($message->getMessage(), $message->getEnvelope());
                $count++;

                unlink($file . '.sending');
            } else {
                /* This message has just been caught by another process */
                continue;
            }

            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }

            if ($this->getTimeLimit() && ($GLOBALS['EXEC_TIME'] - $time) >= $this->getTimeLimit()) {
                break;
            }
        }
        return $count;
    }

    /**
     * Returns a random string needed to generate a fileName for the queue.
     */
    protected function getRandomString(int $count): string
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
        $ret = '';
        $strlen = strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[random_int(0, $strlen - 1)];
        }

        return $ret;
    }

    /**
     * Sets the maximum number of messages to send per flush.
     */
    public function setMessageLimit(int $limit): void
    {
        $this->messageLimit = $limit;
    }

    /**
     * Gets the maximum number of messages to send per flush.
     */
    public function getMessageLimit(): int
    {
        return $this->messageLimit;
    }

    /**
     * Sets the time limit (in seconds) per flush.
     */
    public function setTimeLimit(int $limit): void
    {
        $this->timeLimit = $limit;
    }

    /**
     * Gets the time limit (in seconds) per flush.
     */
    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function __toString(): string
    {
        return 'FileSpool:' . $this->path;
    }
}
