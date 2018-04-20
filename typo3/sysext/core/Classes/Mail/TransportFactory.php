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

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TransportFactory
 */
class TransportFactory implements SingletonInterface
{
    const SPOOL_MEMORY = 'memory';
    const SPOOL_FILE = 'file';

    /**
     * Gets a transport from settings.
     *
     * @param array $mailSettings from $GLOBALS['TYPO3_CONF_VARS']['MAIL']
     * @return \Swift_Transport
     * @throws Exception
     * @throws \RuntimeException
     */
    public function get(array $mailSettings): \Swift_Transport
    {
        if (!isset($mailSettings['transport'])) {
            throw new \InvalidArgumentException('Key "transport" must be set in the mail settings', 1469363365);
        }
        if ($mailSettings['transport'] === 'spool') {
            throw new \InvalidArgumentException('Mail transport can not be set to "spool"', 1469363238);
        }

        $transport = null;
        $transportType = isset($mailSettings['transport_spool_type']) && !empty($mailSettings['transport_spool_type']) ? 'spool' : $mailSettings['transport'];

        switch ($transportType) {
            case 'spool':
                $transport = \Swift_SpoolTransport::newInstance($this->createSpool($mailSettings));
                break;
            case 'smtp':
                // Get settings to be used when constructing the transport object
                if (isset($mailSettings['transport_smtp_server']) && strpos($mailSettings['transport_smtp_server'], ':') > 0) {
                    $parts = GeneralUtility::trimExplode(':', $mailSettings['transport_smtp_server'], true);
                    $host = $parts[0];
                    $port = $parts[1] ?? null;
                } else {
                    $host = (string)$mailSettings['transport_smtp_server'] ?? '';
                    $port = null;
                }

                if ($host === '') {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set to "smtp".', 1291068606);
                }
                if ($port === null || $port === '') {
                    $port = 25;
                }
                $useEncryption = $mailSettings['transport_smtp_encrypt'] ?? null;
                // Create our transport
                $transport = \Swift_SmtpTransport::newInstance($host, $port, $useEncryption);
                // Need authentication?
                $username = (string)($mailSettings['transport_smtp_username'] ?? '');
                if ($username !== '') {
                    $transport->setUsername($username);
                }
                $password = (string)($mailSettings['transport_smtp_password'] ?? '');
                if ($password !== '') {
                    $transport->setPassword($password);
                }
                break;
            case 'sendmail':
                $sendmailCommand = $mailSettings['transport_sendmail_command'];
                if (empty($sendmailCommand)) {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'transport_sendmail_command\'] needs to be set when transport is set to "sendmail".', 1291068620);
                }
                // Create our transport
                $transport = \Swift_SendmailTransport::newInstance($sendmailCommand);
                break;
            case 'mbox':
                $mboxFile = $mailSettings['transport_mbox_file'];
                if ($mboxFile == '') {
                    throw new Exception('$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'transport_mbox_file\'] needs to be set when transport is set to "mbox".', 1294586645);
                }
                // Create our transport
                $transport = GeneralUtility::makeInstance(MboxTransport::class, $mboxFile);
                break;
            case 'mail':
                // Create the transport, no configuration required
                $transport = \Swift_MailTransport::newInstance();
                break;
            default:
                // Custom mail transport
                $customTransport = GeneralUtility::makeInstance($mailSettings['transport'], $mailSettings);
                if ($customTransport instanceof \Swift_Transport) {
                    $transport = $customTransport;
                } else {
                    throw new \RuntimeException($mailSettings['transport'] . ' is not an implementation of \\Swift_Transport,
                            but must implement that interface to be used as a mail transport.', 1323006478);
                }
        }
        return $transport;
    }

    /**
     * Creates a spool from mail settings.
     *
     * @param array $mailSettings
     * @return \Swift_Spool
     * @throws \RuntimeException
     */
    protected function createSpool(array $mailSettings): \Swift_Spool
    {
        $spool = null;
        switch ($mailSettings['transport_spool_type']) {
            case self::SPOOL_FILE:
                $path = GeneralUtility::getFileAbsFileName($mailSettings['transport_spool_filepath']);
                if (empty($path) || !file_exists($path) || !is_writable($path)) {
                    throw new \RuntimeException('The Spool Type filepath must be available and writeable for TYPO3 in order to be used. Be sure that it\'s not accessible via the web.', 1518558797);
                }
                $spool = GeneralUtility::makeInstance(\Swift_FileSpool::class, $path);
                break;
            case self::SPOOL_MEMORY:
                $spool = GeneralUtility::makeInstance(MemorySpool::class);
                break;
            default:
                $spool = GeneralUtility::makeInstance($mailSettings['transport_spool_type'], $mailSettings);
                if (!$spool instanceof \Swift_Spool) {
                    throw new \RuntimeException(
                        $mailSettings['transport_spool_type'] . ' is not an implementation of \\Swift_Spool,
                            but must implement that interface to be used as a mail spool.',
                        1466799482
                    );
                }
                break;
        }
        return $spool;
    }
}
