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

namespace TYPO3\CMS\Core\Core;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;

/**
 * @internal
 */
final class RequestId
{
    public readonly string $long;
    public readonly string $short;
    public readonly int $microtime;
    public readonly ConsumableNonce $nonce;

    public function __construct()
    {
        $this->long = bin2hex(random_bytes(20));
        $this->short = substr($this->long, 0, 13);
        $this->microtime = (int)(microtime(true) * 1000000);
        $this->nonce = new ConsumableNonce();
    }

    public function __toString(): string
    {
        return $this->short;
    }
}
