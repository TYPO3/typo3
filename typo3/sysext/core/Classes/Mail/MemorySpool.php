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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Small wrapper for \Swift_MemorySpool
 *
 * Because TYPO3 doesn't offer a terminate signal or hook,
 * and taking in account the risk that extensions do some redirects or even exit,
 * we simply use the destructor of a singleton class which should be pretty much
 * at the end of a request.
 *
 * To have only one memory spool per request seems to be more appropriate anyway.
 *
 * @internal This class is experimental and subject to change!
 */
class MemorySpool extends \Swift_MemorySpool implements SingletonInterface, LoggerAwareInterface
{
    use BlockSerializationTrait;
    use LoggerAwareTrait;

    /**
     * Sends out the messages in the memory
     */
    public function sendMessages()
    {
        $mailer = GeneralUtility::makeInstance(Mailer::class);
        try {
            $this->flushQueue($mailer->getRealTransport());
        } catch (\Swift_TransportException $exception) {
            $this->logger->error('An Exception occurred while flushing email queue: ' . $exception->getMessage());
        }
    }

    public function __destruct()
    {
        $this->sendMessages();
    }
}
