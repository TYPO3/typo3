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

namespace TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use TYPO3\CMS\Core\Mail\DelayedTransportInterface;

/**
 * Fixture fake valid spool
 */
class FakeValidSpoolFixture implements DelayedTransportInterface
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        // dont do anything
    }

    public function flushQueue(TransportInterface $transport): int
    {
        return 1;
    }

    public function __toString(): string
    {
        return '';
    }
}
