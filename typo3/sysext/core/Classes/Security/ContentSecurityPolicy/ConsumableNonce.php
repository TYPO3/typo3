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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Core\Utility\StringUtility;

final class ConsumableNonce extends ConsumableString
{
    private const MIN_BYTES = 40;

    /**
     * @internal backward compatibility for Nonce::$b64
     * @deprecated will be removed in TYPO3 v13.0, use ConsumableNonce::consume() or ConsumableNonce::$value instead
     */
    public readonly string $b64;

    public function __construct(string $nonce = null)
    {
        if ($nonce === null || strlen($nonce) < self::MIN_BYTES) {
            $nonce = random_bytes(self::MIN_BYTES);
            $nonce = StringUtility::base64urlEncode($nonce);
        }
        $this->b64 = $nonce;
        parent::__construct($this->b64);
    }
}
