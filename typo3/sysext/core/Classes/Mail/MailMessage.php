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

use TYPO3\CMS\Core\Utility\MailUtility;

/**
 * Adapter for Swift_Mailer to be used by TYPO3 extensions
 */
class MailMessage extends \Swift_Message
{
    /**
     * @var \TYPO3\CMS\Core\Mail\Mailer
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

    /**
     * Holds the failed recipients after the message has been sent
     *
     * @var array
     */
    protected $failedRecipients = [];

    /**
     */
    private function initializeMailer()
    {
        $this->mailer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\Mailer::class);
    }

    /**
     * Sends the message.
     *
     * @return int the number of recipients who were accepted for delivery
     */
    public function send()
    {
        // Ensure to always have a From: header set
        if (empty($this->getFrom())) {
            $this->setFrom(MailUtility::getSystemFrom());
        }
        $this->initializeMailer();
        $this->sent = true;
        $this->getHeaders()->addTextHeader('X-Mailer', $this->mailerHeader);
        return $this->mailer->send($this, $this->failedRecipients);
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
     * Returns the recipients for which the mail was not accepted for delivery.
     *
     * @return array the recipients who were not accepted for delivery
     */
    public function getFailedRecipients()
    {
        return $this->failedRecipients;
    }

    /**
     * Set the return-path (the bounce address) of this message.
     *
     * @param string $address
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setReturnPath($address)
    {
        $address = $this->idnaEncodeAddresses($address);
        return parent::setReturnPath($address);
    }

    /**
     * Set the sender of this message.
     *
     * This does not override the From field, but it has a higher significance.
     *
     * @param string $address
     * @param string $name optional
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setSender($address, $name = null)
    {
        $address = $this->idnaEncodeAddresses($address);
        return parent::setSender($address, $name);
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
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setFrom($addresses, $name = null)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setFrom($addresses, $name);
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
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setReplyTo($addresses, $name = null)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setReplyTo($addresses, $name);
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
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setTo($addresses, $name = null)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setTo($addresses, $name);
    }

    /**
     * Set the Cc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setCc($addresses, $name = null)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setCc($addresses, $name);
    }

    /**
     * Set the Bcc addresses of this message.
     *
     * If $name is passed and the first parameter is a string, this name will be
     * associated with the address.
     *
     * @param string|array $addresses
     * @param string $name optional
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setBcc($addresses, $name = null)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setBcc($addresses, $name);
    }

    /**
     * Ask for a delivery receipt from the recipient to be sent to $addresses.
     *
     * @param array $addresses
     * @return \TYPO3\CMS\Core\Mail\MailMessage
     */
    public function setReadReceiptTo($addresses)
    {
        $addresses = $this->idnaEncodeAddresses($addresses);
        return parent::setReadReceiptTo($addresses);
    }

    /**
     * IDNA encode email addresses. Accepts addresses in all formats that SwiftMailer supports
     *
     * @param string|array $addresses
     * @return string|array
     */
    protected function idnaEncodeAddresses($addresses)
    {
        if (!is_array($addresses)) {
            return $this->idnaEncodeAddress($addresses);
        }
        $newAddresses = [];
        foreach ($addresses as $email => $name) {
            if (ctype_digit($email)) {
                $newAddresses[] = $this->idnaEncodeAddress($name);
            } else {
                $newAddresses[$this->idnaEncodeAddress($email)] = $name;
            }
        }

        return $newAddresses;
    }

    /**
     * IDNA encode the domain part of an email address if it contains non ASCII characters
     *
     * @param mixed $email
     * @return mixed
     * @see \TYPO3\CMS\Core\Utility\GeneralUtility::validEmail
     */
    protected function idnaEncodeAddress($email)
    {
        // Early return in case input is not a string
        if (!is_string($email)) {
            return $email;
        }
        // Split on the last "@" since adresses like "foo@bar"@example.org are valid
        $atPosition = strrpos($email, '@');
        if (!$atPosition || $atPosition + 1 === strlen($email)) {
            // Return if no @ found or it is placed at the very beginning or end of the email
            return $email;
        }
        $domain = substr($email, $atPosition + 1);
        $local = substr($email, 0, $atPosition);
        $domain = \TYPO3\CMS\Core\Utility\GeneralUtility::idnaEncode($domain);

        return $local . '@' . $domain;
    }
}
