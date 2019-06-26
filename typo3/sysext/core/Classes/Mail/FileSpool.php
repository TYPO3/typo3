<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Mail;

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

use DirectoryIterator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mailer\DelayedSmtpEnvelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\SmtpEnvelope;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Inspired by SwiftMailer, adapted for TYPO3 and Symfony/Mailer
 *
 * @internal This class is experimental and subject to change!
 */
class FileSpool extends AbstractTransport implements DelayedTransportInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The spool directory
     * @var string
     */
    protected $path;

    /**
     * File WriteRetry Limit.
     *
     * @var int
     */
    protected $_retryLimit = 10;

    /**
     * The maximum number of messages to send per flush
     * @var int
     */
    protected $messageLimit;

    /**
     * The time limit per flush
     * @var int
     */
    protected $timeLimit;

    /**
     * Create a new FileSpool.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        parent::__construct();
        $this->path = $path;

        if (!file_exists($this->path)) {
            GeneralUtility::mkdir_deep($this->path);
        }
    }

    /**
     * Stores a message in the queue.
     * @param SentMessage $message
     */
    protected function doSend(SentMessage $message): void
    {
        $ser = serialize($message);
        $fileName = $this->path . '/' . $this->getRandomString(10);
        for ($i = 0; $i < $this->_retryLimit; ++$i) {
            // We try an exclusive creation of the file. This is an atomic operation, it avoid locking mechanism
            $fp = @fopen($fileName . '.message', 'x');
            if (false !== $fp) {
                if (false === fwrite($fp, $ser)) {
                    throw new TransportException('Could not create file for spooling', 1561618885);
                }
                fclose($fp);
            } else {
                // The file already exists, we try a longer fileName
                $fileName .= $this->getRandomString(1);
            }
        }
    }

    /**
     * Allow to manage the enqueuing retry limit.
     *
     * Default, is ten and allows over 64^20 different fileNames
     *
     * @param int $limit
     */
    public function setRetryLimit(int $limit): void
    {
        $this->_retryLimit = $limit;
    }

    /**
     * Execute a recovery if for any reason a process is sending for too long.
     *
     * @param int $timeout in second Defaults is for very slow smtp responses
     */
    public function recover(int $timeout = 900): void
    {
        foreach (new DirectoryIterator($this->path) as $file) {
            $file = $file->getRealPath();

            if (substr($file, -16) == '.message.sending') {
                $lockedtime = filectime($file);
                if ((time() - $lockedtime) > $timeout) {
                    rename($file, substr($file, 0, -8));
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function flushQueue(TransportInterface $transport): int
    {
        $directoryIterator = new DirectoryIterator($this->path);

        $count = 0;
        $time = time();
        foreach ($directoryIterator as $file) {
            $file = $file->getRealPath();

            if (substr($file, -8) != '.message') {
                continue;
            }

            /* We try a rename, it's an atomic operation, and avoid locking the file */
            if (rename($file, $file . '.sending')) {
                $message = unserialize(file_get_contents($file . '.sending'), [
                    'allowedClasses' => [
                        RawMessage::class,
                        Message::class,
                        Email::class,
                        DelayedSmtpEnvelope::class,
                        SmtpEnvelope::class,
                    ],
                ]);

                $transport->send($message);
                $count++;

                unlink($file . '.sending');
            } else {
                /* This message has just been catched by another process */
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
     *
     * @param int $count
     *
     * @return string
     */
    protected function getRandomString(int $count): string
    {
        // This string MUST stay FS safe, avoid special chars
        $base = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';
        $ret = '';
        $strlen = strlen($base);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= $base[((int)rand(0, $strlen - 1))];
        }

        return $ret;
    }

    /**
     * Sets the maximum number of messages to send per flush.
     *
     * @param int $limit
     */
    public function setMessageLimit(int $limit): void
    {
        $this->messageLimit = (int)$limit;
    }

    /**
     * Gets the maximum number of messages to send per flush.
     *
     * @return int The limit
     */
    public function getMessageLimit(): int
    {
        return $this->messageLimit;
    }

    /**
     * Sets the time limit (in seconds) per flush.
     *
     * @param int $limit The limit
     */
    public function setTimeLimit(int $limit): void
    {
        $this->timeLimit = (int)$limit;
    }

    /**
     * Gets the time limit (in seconds) per flush.
     *
     * @return int The limit
     */
    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }
}
