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

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
     * TRUE if the message has been sent.
     *
     * @var bool
     */
    protected $sent = false;

    private function initializeMailer(): void
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
    public function send(): bool
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
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * compatibility methods to allow for associative arrays as [name => email address]
     * as it was possible in TYPO3 v9 / SwiftMailer.
     *
     * Also, ensure to switch to Address objects and the ->subject()/->from() methods directly
     * to directly use the new API.
     */

    /**
     * Set the subject of the message.
     *
     * @param string $subject
     * @return MailMessage
     */
    public function setSubject($subject): self
    {
        return $this->subject($subject);
    }

    /**
     * Set the origination date of the message as a UNIX timestamp.
     *
     * @param int $date
     * @return MailMessage
     */
    public function setDate($date): self
    {
        return $this->date(new \DateTime('@' . $date));
    }

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     * @return MailMessage
     */
    public function setReturnPath($address): self
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
    public function setSender($address, $name = null): self
    {
        return $this->sender(...$this->convertNamedAddress($address, $name));
    }

    /**
     * Set the from address of this message.
     *
     * You may pass an array of addresses if this message is from multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     * If $name is passed and the first parameter is not a string, an exception is thrown.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setFrom($addresses, $name = null): self
    {
        $this->checkArguments($addresses, $name);
        return $this->from(...$this->convertNamedAddress($addresses, $name));
    }

    /**
     * Set the reply-to address of this message.
     *
     * You may pass an array of addresses if replies will go to multiple people.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     * If $name is passed and the first parameter is not a string, an exception is thrown.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setReplyTo($addresses, $name = null): self
    {
        $this->checkArguments($addresses, $name);
        return $this->replyTo(...$this->convertNamedAddress($addresses, $name));
    }

    /**
     * Set the to addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     * If $name is passed and the first parameter is not a string, an exception is thrown.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setTo($addresses, $name = null): self
    {
        $this->checkArguments($addresses, $name);
        return $this->to(...$this->convertNamedAddress($addresses, $name));
    }

    /**
     * Set the Cc addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     * If $name is passed and the first parameter is not a string, an exception is thrown.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setCc($addresses, $name = null): self
    {
        $this->checkArguments($addresses, $name);
        return $this->cc(...$this->convertNamedAddress($addresses, $name));
    }

    /**
     * Set the Bcc addresses of this message.
     *
     * If multiple recipients will receive the message an array should be used.
     * Example: array('receiver@domain.org', 'other@domain.org' => 'A name')
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     * If $name is passed and the first parameter is not a string, an exception is thrown.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return MailMessage
     */
    public function setBcc($addresses, $name = null): self
    {
        $this->checkArguments($addresses, $name);
        return $this->bcc(...$this->convertNamedAddress($addresses, $name));
    }

    /**
     * Ask for a delivery receipt from the recipient to be sent to $addresses.
     *
     * @param string $address
     * @return MailMessage
     */
    public function setReadReceiptTo(string $address): self
    {
        $this->getHeaders()->addMailboxHeader('Disposition-Notification-To', $address);
        return $this;
    }

    /**
     * Converts address from [email, name] into Address objects.
     *
     * @param mixed ...$args
     * @return Address[]
     */
    protected function convertNamedAddress(...$args): array
    {
        if (isset($args[1])) {
            return [Address::create(sprintf('%s <%s>', $args[1], $args[0]))];
        }
        if (is_string($args[0]) || is_array($args[0])) {
            return $this->convertAddresses($args[0]);
        }
        return $this->convertAddresses($args);
    }

    /**
     * Converts Addresses into Address/NamedAddress objects.
     *
     * @param string|array $addresses
     * @return Address[]
     */
    protected function convertAddresses($addresses): array
    {
        if (!is_array($addresses)) {
            return [Address::create($addresses)];
        }
        $newAddresses = [];
        foreach ($addresses as $email => $name) {
            if (is_numeric($email) || ctype_digit($email)) {
                $newAddresses[] = Address::create($name);
            } else {
                $newAddresses[] = Address::create(sprintf('%s <%s>', $name, $email));
            }
        }

        return $newAddresses;
    }

    //
    // Compatibility methods, as it was possible in TYPO3 v9 / SwiftMailer.
    //

    public function addFrom(...$addresses): Email
    {
        return parent::addFrom(...$this->convertNamedAddress(...$addresses));
    }

    public function addReplyTo(...$addresses): Email
    {
        return parent::addReplyTo(...$this->convertNamedAddress(...$addresses));
    }

    public function addTo(...$addresses): Email
    {
        return parent::addTo(...$this->convertNamedAddress(...$addresses));
    }

    public function addCc(...$addresses): Email
    {
        return parent::addCc(...$this->convertNamedAddress(...$addresses));
    }

    public function addBcc(...$addresses): Email
    {
        return parent::addBcc(...$this->convertNamedAddress(...$addresses));
    }

    protected function checkArguments($addresses, string $name = null): void
    {
        if ($name !== null && !is_string($addresses)) {
            throw new \InvalidArgumentException('The combination of a name and an array of addresses is invalid.', 1570543657);
        }
    }
}
