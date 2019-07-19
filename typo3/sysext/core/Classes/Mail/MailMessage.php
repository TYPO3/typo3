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

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\NamedAddress;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adapter for Symfony Mime to be used by TYPO3 extensions, also provides
 * some backwards-compatibility for previous TYPO3 installations where
 * send() was baked into the MailMessage object.
 */
class MailMessage extends Email
{
    /**
     * @var Mailer
     */
    protected $mailer;

    /**
     * @var string This will be added as X-Mailer to all outgoing mails
     */
    protected $mailerHeader = 'TYPO3';

    /**
     * TRUE if the message has been sent.
     *
     * @var bool
     */
    protected $sent = false;

    private function initializeMailer()
    {
        $this->mailer = GeneralUtility::makeInstance(Mailer::class);
    }

    /**
     * Sends the message.
     *
     * This is a short-hand method. It is however more useful to create
     * a Mailer instance which can be used via Mailer->send($message);
     *
     * @return bool whether the message was accepted or not
     */
    public function send()
    {
        $this->initializeMailer();
        $this->sent = false;
        $this->mailer->send($this);
        $sentMessage = $this->mailer->getSentMessage();
        if ($sentMessage) {
            $this->sent = true;
        }
        return $this->sent;
    }

    /**
     * Checks whether the message has been sent.
     *
     * @return bool
     */
    public function isSent()
    {
        return $this->sent;
    }

    /**
     * compatibility methods to allow for associative arrays as [name => email address]
     * as it was possible in TYPO3 v9 / SwiftMailer.
     *
     * Also, ensure to switch to NamedAddress objects and the ->subject()/->from() methods directly
     * to directly use the new API.
     */

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject($subject);
    }

    /**
     * Set the origination date of the message as a UNIX timestamp.
     *
     * @param int $date
     */
    public function setDate($date)
    {
        $this->date(new \DateTime('@' . $date));
    }

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     * @return MailMessage
     */
    public function setReturnPath($address)
    {
        return $this->returnPath($address);
    }

    /**
     * Set the sender of this message.
     *
     * This does not override the From field, but it has a higher significance.
     *
     * @param string $address
     * @param string $name optional
     * @return MailMessage
     */
    public function setSender($address, $name = null)
    {
        $address = $this->convertNamedAddress($address, $name);
        return $this->sender($address);
    }

    /**
     * Set the from address of this message.
     *
     * You may pass an array of addresses if this message is from multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setFrom($addresses, $name = null)
    {
        $addresses = $this->convertNamedAddress($addresses, $name);
        return $this->from($addresses, $name);
    }

    /**
     * Set the reply-to address of this message.
     *
     * You may pass an array of addresses if replies will go to multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setReplyTo($addresses, $name = null)
    {
        $addresses = $this->convertNamedAddress($addresses, $name);
        return $this->replyTo($addresses);
    }

    /**
     * Set the to addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setTo($addresses, $name = null)
    {
        $addresses = $this->convertNamedAddress($addresses, $name);
        return $this->to($addresses);
    }

    /**
     * Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setCc($addresses, $name = null)
    {
        $addresses = $this->convertNamedAddress($addresses, $name);
        return $this->cc($addresses);
    }

    /**
     * Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setBcc($addresses, $name = null)
    {
        $addresses = $this->convertNamedAddress($addresses, $name);
        return $this->bcc($addresses);
    }

    /**
     * Ask for a delivery receipt from the recipient to be sent to $addresses.
     *
     * @param array $addresses
     * @return MailMessage
     */
    public function setReadReceiptTo($addresses)
    {
        $addresses = $this->convertNamedAddress($addresses);
        return $this->setReadReceiptTo($addresses);
    }

    /**
     * Converts Adresses into Address/NamedAddress objects.
     *
     * @param string|array $args
     * @return string|array
     */
    protected function convertNamedAddress(...$args)
    {
        if (isset($args[1])) {
            return new NamedAddress($args[0], $args[1]);
        }
        if (is_string($args[0]) || is_array($args[0])) {
            return $this->convertAddresses($args[0]);
        }
        return $this->convertAddresses($args);
    }

    /**
     * Converts Adresses into Address/NamedAddress objects.
     *
     * @param string|array $addresses
     * @return string|array
     */
    protected function convertAddresses($addresses)
    {
        if (!is_array($addresses)) {
            return Address::create($addresses);
        }
        $newAddresses = [];
        foreach ($addresses as $email => $name) {
            if (is_numeric($email) || ctype_digit($email)) {
                $newAddresses[] = Address::create($name);
            } else {
                $newAddresses[] = new NamedAddress($email, $name);
            }
        }

        return $newAddresses;
    }

    /**
     * compatibility methods to allow for associative arrays as [name => email address]
     * as it was possible in TYPO3 v9 / SwiftMailer.
     */

    /**
     * @inheritdoc
     */
    public function addFrom(...$addresses)
    {
        $addresses = $this->convertNamedAddress(...$addresses);
        return parent::addFrom(...$addresses);
    }

    /**
     * @inheritdoc
     */
    public function addReplyTo(...$addresses)
    {
        $addresses = $this->convertNamedAddress(...$addresses);
        return parent::addReplyTo(...$addresses);
    }

    /**
     * @inheritdoc
     */
    public function addTo(...$addresses)
    {
        $addresses = $this->convertNamedAddress(...$addresses);
        return parent::addTo(...$addresses);
    }

    /**
     * @inheritdoc
     */
    public function addCc(...$addresses)
    {
        $addresses = $this->convertNamedAddress(...$addresses);
        return parent::addCc(...$addresses);
    }

    /**
     * @inheritdoc
     */
    public function addBcc(...$addresses)
    {
        $addresses = $this->convertNamedAddress(...$addresses);
        return parent::addBcc(...$addresses);
    }
}
