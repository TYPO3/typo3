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

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The sender and reply-to addresses used for emails which do not define them.
 *
 * Without a valid "defaultMailFromAddress", a no-reply address of the host name is used,
 * and "no-reply@example.com" if the host name does not form a valid address either.
 */
final readonly class SenderDefaults
{
    public function __construct(
        public Address $from,
        public ?Address $replyTo = null,
    ) {}

    /**
     * @param array $mailSettings the global MAIL configuration or the settings of a mail profile
     */
    public static function fromMailSettings(array $mailSettings): self
    {
        $fromName = $mailSettings['defaultMailFromName'] ?? '';
        $replyToAddress = $mailSettings['defaultMailReplyToAddress'] ?? '';
        $replyTo = null;
        if (is_string($replyToAddress) && $replyToAddress !== '' && GeneralUtility::validEmail($replyToAddress)) {
            $replyToName = $mailSettings['defaultMailReplyToName'] ?? '';
            $replyTo = new Address($replyToAddress, is_string($replyToName) ? $replyToName : '');
        }
        return new self(
            new Address(self::resolveFromAddress($mailSettings), is_string($fromName) ? $fromName : ''),
            $replyTo
        );
    }

    private static function resolveFromAddress(array $mailSettings): string
    {
        $address = $mailSettings['defaultMailFromAddress'] ?? null;
        if (is_string($address) && GeneralUtility::validEmail($address)) {
            return $address;
        }
        $address = 'no-reply@' . php_uname('n');
        return GeneralUtility::validEmail($address) ? $address : 'no-reply@example.com';
    }
}
