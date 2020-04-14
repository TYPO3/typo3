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

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Used to implement backwards-compatible spooling
 */
interface DelayedTransportInterface extends TransportInterface
{
    /**
     * Sends messages using the given transport instance
     *
     * @param TransportInterface $transport
     * @return int the number of messages sent
     */
    public function flushQueue(TransportInterface $transport): int;
}
