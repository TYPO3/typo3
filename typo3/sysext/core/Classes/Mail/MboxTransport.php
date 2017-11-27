<?php
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

use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 */
class MboxTransport implements \Swift_Transport
{
    /**
     * @var string The file to write our mails into
     */
    private $debugFile;

    /**
     * Create a new MailTransport
     *
     * @param string $debugFile
     */
    public function __construct($debugFile)
    {
        $this->debugFile = $debugFile;
    }

    /**
     * Not used.
     */
    public function isStarted()
    {
        return false;
    }

    /**
     * Not used.
     */
    public function start()
    {
    }

    /**
     * Not used.
     */
    public function stop()
    {
    }

    /**
     * Outputs the mail to a text file according to RFC 4155.
     *
     * @param \Swift_Mime_Message $message The message to send
     * @param string[] &$failedRecipients To collect failures by-reference, nothing will fail in our debugging case
     * @return int
     * @throws \RuntimeException
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $message->generateId();
        // Create a mbox-like header
        $mboxFrom = $this->getReversePath($message);
        $mboxDate = strftime('%c', $message->getDate());
        $messageStr = sprintf('From %s  %s', $mboxFrom, $mboxDate) . LF;
        // Add the complete mail inclusive headers
        $messageStr .= $message->toString();
        $messageStr .= LF . LF;
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $lockObject = $lockFactory->createLocker('mbox');
        $lockObject->acquire();
        // Write the mbox file
        $file = @fopen($this->debugFile, 'a');
        if (!$file) {
            $lockObject->release();
            throw new \RuntimeException(sprintf('Could not write to file "%s" when sending an email to debug transport', $this->debugFile), 1291064151);
        }
        @fwrite($file, $messageStr);
        @fclose($file);
        GeneralUtility::fixPermissions($this->debugFile);
        $lockObject->release();
        // Return every recipient as "delivered"
        $count = count((array)$message->getTo()) + count((array)$message->getCc()) + count((array)$message->getBcc());
        return $count;
    }

    /**
     * Determine the best-use reverse path for this message
     *
     * @param \Swift_Mime_Message $message
     * @return mixed|null
     */
    private function getReversePath(\Swift_Mime_Message $message)
    {
        $return = $message->getReturnPath();
        $sender = $message->getSender();
        $from = $message->getFrom();
        $path = null;
        if (!empty($return)) {
            $path = $return;
        } elseif (!empty($sender)) {
            $keys = array_keys($sender);
            $path = array_shift($keys);
        } elseif (!empty($from)) {
            $keys = array_keys($from);
            $path = array_shift($keys);
        }
        return $path;
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param \Swift_Events_EventListener $plugin
     * @return bool
     */
    public function registerPlugin(\Swift_Events_EventListener $plugin)
    {
        return true;
    }
}
