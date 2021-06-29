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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Log\LogManagerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TransportFactory
 */
class TransportFactory implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const SPOOL_MEMORY = 'memory';
    public const SPOOL_FILE = 'file';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LogManagerInterface
     */
    protected $logManager;

    public function __construct(EventDispatcherInterface $dispatcher, LogManagerInterface $logManager)
    {
        $this->dispatcher = $dispatcher;
        $this->logManager = $logManager;
    }

    /**
     * Gets a transport from settings.
     *
     * @param array $mailSettings from $GLOBALS['TYPO3_CONF_VARS']['MAIL']
     * @return TransportInterface
     * @throws Exception
     * @throws \RuntimeException
     */
    public function get(array $mailSettings): TransportInterface
    {
        if (!isset($mailSettings['transport'])) {
            throw new \InvalidArgumentException('Key "transport" must be set in the mail settings', 1469363365);
        }
        if ($mailSettings['transport'] === 'spool') {
            throw new \InvalidArgumentException('Mail transport can not be set to "spool"', 1469363238);
        }

        $transportType = isset($mailSettings['transport_spool_type'])
            && !empty($mailSettings['transport_spool_type'])
            ? 'spool' : $mailSettings['transport'];

        switch ($transportType) {
            case 'spool':
                $transport = $this->createSpool($mailSettings);
                break;
            case 'smtp':
                // Get settings to be used when constructing the transport object
                if (
                    isset($mailSettings['transport_smtp_server'])
                    && strpos($mailSettings['transport_smtp_server'], ':') > 0
                ) {
                    $parts = GeneralUtility::trimExplode(':', $mailSettings['transport_smtp_server'], true);
                    $host = $parts[0];
                    $port = $parts[1] ?? null;
                } else {
                    $host = (string)($mailSettings['transport_smtp_server'] ?? '');
                    $port = null;
                }

                if ($host === '') {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set to "smtp".', 1291068606);
                }
                if ($port === null || $port === '') {
                    $port = 25;
                } else {
                    $port = (int)$port;
                }
                $useEncryption = (bool)($mailSettings['transport_smtp_encrypt'] ?? false) ?: null;
                // Create transport
                $transport = new EsmtpTransport(
                    $host,
                    $port,
                    $useEncryption,
                    $this->dispatcher,
                    $this->logManager->getLogger(EsmtpTransport::class)
                );
                // Need authentication?
                $username = (string)($mailSettings['transport_smtp_username'] ?? '');
                if ($username !== '') {
                    $transport->setUsername($username);
                }
                $password = (string)($mailSettings['transport_smtp_password'] ?? '');
                if ($password !== '') {
                    $transport->setPassword($password);
                }
                $mailDomain = (string)($mailSettings['transport_smtp_domain'] ?? '');
                if ($mailDomain !== '') {
                    $transport->setLocalDomain($mailDomain);
                }
                break;
            case 'sendmail':
                $sendmailCommand = $mailSettings['transport_sendmail_command'] ?? @ini_get('sendmail_path');
                if (empty($sendmailCommand)) {
                    $sendmailCommand = '/usr/sbin/sendmail -bs';
                    $this->logger->warning('Mailer transport "sendmail" was chosen without a specific command, using "{command}"', ['command' => $sendmailCommand]);
                }
                // Create transport
                $transport = new SendmailTransport(
                    $sendmailCommand,
                    $this->dispatcher,
                    $this->logManager->getLogger(SendmailTransport::class)
                );
                break;
            case 'mbox':
                $mboxFile = $mailSettings['transport_mbox_file'];
                if ($mboxFile == '') {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'transport_mbox_file\'] needs to be set when transport is set to "mbox".', 1294586645);
                }
                // Create our transport
                $transport = GeneralUtility::makeInstance(
                    MboxTransport::class,
                    $mboxFile,
                    $this->dispatcher,
                    $this->logManager->getLogger(MboxTransport::class)
                );
                break;
            // Used for testing purposes
            case 'null':
            case NullTransport::class:
                $transport = new NullTransport(
                    $this->dispatcher,
                    $this->logManager->getLogger(NullTransport::class)
                );
                break;
            // Used by Symfony's Transport Factory
            case !empty($mailSettings['dsn']):
            case 'dsn':
                if (empty($mailSettings['dsn'])) {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'dsn\'] needs to be set when transport is set to "dsn".', 1615021869);
                }
                $transport = Transport::fromDsn(
                    $mailSettings['dsn'],
                    $this->dispatcher,
                    null,
                    $this->logManager->getLogger(Transport::class)
                );
                break;
            default:
                // Custom mail transport
                $transport = GeneralUtility::makeInstance($mailSettings['transport'], $mailSettings);
                if (!$transport instanceof TransportInterface) {
                    throw new \RuntimeException($mailSettings['transport'] . ' is not an implementation of Symfony\Mailer\TransportInterface,
                            but must implement that interface to be used as a mail transport.', 1323006478);
                }
        }
        return $transport;
    }

    /**
     * Creates a spool from mail settings.
     *
     * @param array $mailSettings
     * @return DelayedTransportInterface
     * @throws \RuntimeException
     */
    protected function createSpool(array $mailSettings): DelayedTransportInterface
    {
        switch ($mailSettings['transport_spool_type']) {
            case self::SPOOL_FILE:
                $path = GeneralUtility::getFileAbsFileName($mailSettings['transport_spool_filepath']);
                if (empty($path)) {
                    throw new \RuntimeException('The Spool Type filepath must be configured for TYPO3 in order to be used. Be sure that it\'s not accessible via the web.', 1518558797);
                }
                $spool = GeneralUtility::makeInstance(
                    FileSpool::class,
                    $path,
                    $this->dispatcher,
                    $this->logManager->getLogger(FileSpool::class)
                );
                break;
            case self::SPOOL_MEMORY:
                $spool = GeneralUtility::makeInstance(
                    MemorySpool::class,
                    $this->dispatcher,
                    $this->logManager->getLogger(MemorySpool::class)
                );
                break;
            default:
                $spool = GeneralUtility::makeInstance($mailSettings['transport_spool_type'], $mailSettings);
                if (!($spool instanceof DelayedTransportInterface)) {
                    throw new \RuntimeException(
                        $mailSettings['transport_spool_type'] . ' is not an implementation of DelayedTransportInterface, but must implement that interface to be used as a mail spool.',
                        1466799482
                    );
                }
                break;
        }
        return $spool;
    }
}
