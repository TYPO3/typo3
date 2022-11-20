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

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Additional Mbox Transport option
 */
class MboxTransport extends AbstractTransport
{
    /**
     * Create a new MailTransport
     *
     * @param string $mboxFile The file into which to write mail.
     */
    public function __construct(
        private readonly string $mboxFile,
        ?EventDispatcherInterface $dispatcher = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);
        $this->setMaxPerSecond(0);
    }

    /**
     * Outputs the mail to a text file according to RFC 4155.
     *
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     * @throws LockCreateException
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
