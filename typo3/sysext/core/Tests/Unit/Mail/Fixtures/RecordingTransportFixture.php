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

namespace TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

final class RecordingTransportFixture implements TransportInterface
{
    /**
     * @var list<RawMessage>
     */
    public static array $sentMessages = [];

    public function __construct(public readonly array $mailSettings = []) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        self::$sentMessages[] = $message;
        return null;
    }

    public function __toString(): string
    {
        return 'recording';
    }
}
