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

use Swift_Transport;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions.
 *
 * This will use the setting in TYPO3_CONF_VARS to choose the correct transport
 * for it to work out-of-the-box.
 */
class Mailer extends \Swift_Mailer
{
    /**
     * @var \Swift_Transport
     */
    protected $transport;

    /**
     * @var array
     */
    protected $mailSettings = [];

    /**
     * When constructing, also initializes the \Swift_Transport like configured
     *
     * @param \Swift_Transport|null $transport optionally pass a transport to the constructor.
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function __construct(\Swift_Transport $transport = null)
    {
        if ($transport !== null) {
            $this->transport = $transport;
        } else {
            if (empty($this->mailSettings)) {
                $this->injectMailSettings();
            }
            try {
                $this->initializeTransport();
            } catch (\Exception $e) {
                throw new \TYPO3\CMS\Core\Exception($e->getMessage(), 1291068569);
            }
        }
        parent::__construct($this->transport);

        $this->emitPostInitializeMailerSignal();
    }

    /**
     * Prepares a transport using the TYPO3_CONF_VARS configuration
     *
     * Used options:
     * $TYPO3_CONF_VARS['MAIL']['transport'] = 'smtp' | 'sendmail' | 'mail' | 'mbox'
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_server'] = 'smtp.example.org';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_port'] = '25';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_encrypt'] = FALSE; # requires openssl in PHP
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'] = 'username';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_password'] = 'password';
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_sendmail_command'] = '/usr/sbin/sendmail -bs'
     *
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \RuntimeException
     */
    private function initializeTransport()
    {
        $this->transport = $this->getTransportFactory()->get($this->mailSettings);
    }

    /**
     * This method is only used in unit tests
     *
     * @param array $mailSettings
     * @internal
     */
    public function injectMailSettings(array $mailSettings = null)
    {
        if (is_array($mailSettings)) {
            $this->mailSettings = $mailSettings;
        } else {
            $this->mailSettings = (array)$GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        }
    }

    /**
     * Returns the real transport (not a spool).
     *
     * @return \Swift_Transport
     */
    public function getRealTransport(): Swift_Transport
    {
        $mailSettings = !empty($this->mailSettings) ? $this->mailSettings : (array)$GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        unset($mailSettings['transport_spool_type']);
        return $this->getTransportFactory()->get($mailSettings);
    }

    /**
     * @return TransportFactory
     */
    protected function getTransportFactory(): TransportFactory
    {
        return GeneralUtility::makeInstance(TransportFactory::class);
    }

    /**
     * Get the object manager
     *
     * @return \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected function getObjectManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return $this->getObjectManager()->get(Dispatcher::class);
    }

    /**
     * Emits a signal after mailer initialization
     */
    protected function emitPostInitializeMailerSignal()
    {
        $this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Core\\Mail\\Mailer', 'postInitializeMailer', [$this]);
    }
}
