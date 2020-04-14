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

namespace TYPO3\CMS\Core\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Additional Mbox Transport option
 */
class MboxTransport extends AbstractTransport
{
    /**
     * @var string The file to write our mails into
     */
    private $mboxFile;

    /**
     * Create a new MailTransport
     *
     * @param string $mboxFile
     */
    public function __construct($mboxFile)
    {
        parent::__construct();
        $this->mboxFile = $mboxFile;
        $this->setMaxPerSecond(0);
    }

    /**
     * Outputs the mail to a text file according to RFC 4155.
     *
     * @param SentMessage $message
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException
     * @throws \TYPO3\CMS\Core\Locking\Exception\LockCreateException
     */
    protected function doSend(SentMessage $message): void
    {
        // Add the complete mail inclusive headers
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $lockObject = $lockFactory->createLocker('mbox');
        $lockObject->acquire();
        // Write the mbox file
        $file = @fopen($this->mboxFile, 'a');
        if (!$file) {
            $lockObject->release();
            throw new \RuntimeException(sprintf('Could not write to file "%s" when sending an email to debug transport', $this->mboxFile), 1291064151);
        }
        @fwrite($file, $message->toString());
        @fclose($file);
        GeneralUtility::fixPermissions($this->mboxFile);
        $lockObject->release();
    }

    public function __toString(): string
    {
        return $this->mboxFile;
    }
}
