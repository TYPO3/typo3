<?php

namespace TYPO3\CMS\Core\Mail;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 3 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TransportFactory
 */
class TransportFactory implements \TYPO3\CMS\Core\SingletonInterface
{
    const SPOOL_MEMORY = 'memory';
    const SPOOL_FILE = 'file';

    /**
     * Gets a transport from settings.
     *
     * @param  array   $mailSettings from $GLOBALS['TYPO3_CONF_VARS']['MAIL']
     * @return \Swift_Transport
     * @throws \TYPO3\CMS\Core\Exception
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
        $transportType = (isset($mailSettings['transport_spool_type']) && !empty($mailSettings['transport_spool_type'])) ? 'spool': $mailSettings['transport'];

        switch ($transportType) {
            case 'spool':
                $transport = \Swift_SpoolTransport::newInstance($this->createSpool($mailSettings));
                break;
            case 'smtp':
                // Get settings to be used when constructing the transport object
                list($host, $port) = preg_split('/:/', $mailSettings['transport_smtp_server']);
                if ($host === '') {
                    throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set to "smtp"', 1291068606);
                }
                if ($port === null || $port === '') {
                    $port = '25';
                }
                $useEncryption = $mailSettings['transport_smtp_encrypt'] ?: null;
                // Create our transport
                $transport = \Swift_SmtpTransport::newInstance($host, $port, $useEncryption);
                // Need authentication?
                $username = $mailSettings['transport_smtp_username'];
                if ($username !== '') {
                    $transport->setUsername($username);
                }
                $password = $mailSettings['transport_smtp_password'];
                if ($password !== '') {
                    $transport->setPassword($password);
                }
                break;
            case 'sendmail':
                $sendmailCommand = $mailSettings['transport_sendmail_command'];
                if (empty($sendmailCommand)) {
                    throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_sendmail_command\'] needs to be set when transport is set to "sendmail"', 1291068620);
                }
                // Create our transport
                $transport = \Swift_SendmailTransport::newInstance($sendmailCommand);
                break;
            case 'mbox':
                $mboxFile = $mailSettings['transport_mbox_file'];
                if ($mboxFile == '') {
                    throw new \TYPO3\CMS\Core\Exception('$TYPO3_CONF_VARS[\'MAIL\'][\'transport_mbox_file\'] needs to be set when transport is set to "mbox"', 1294586645);
                }
                // Create our transport
                $transport = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MboxTransport::class, $mboxFile);
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
     * @param  array  $mailSettings
     * @return \Swift_Spool
     */
    protected function createSpool(array $mailSettings): \Swift_Spool
    {
        $spool = null;
        switch ($mailSettings['transport_spool_type']) {
            case self::SPOOL_FILE:
                $path = GeneralUtility::getFileAbsFileName($mailSettings['transport_spool_filepath']);
                $spool = GeneralUtility::makeInstance(\Swift_FileSpool::class, $path);
                break;
            case self::SPOOL_MEMORY:
                $spool = GeneralUtility::makeInstance(MemorySpool::class);
                break;
            default:
                $spool = GeneralUtility::makeInstance($mailSettings['transport_spool_type'], $mailSettings);
                if (!($spool instanceof \Swift_Spool)) {
                    throw new \RuntimeException($mailSettings['spool'] . ' is not an implementation of \\Swift_Spool,
                            but must implement that interface to be used as a mail spool.', 1466799482);
                }
                break;
        }
        return $spool;
    }
}
