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

namespace TYPO3\CMS\Extbase\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
readonly class RateLimit
{
    public function __construct(
        public int $limit = 5,
        public string $interval = '15 minutes',
        public string $policy = 'sliding_window',
        public string $message = ''
    ) {
        if ($this->limit < 1) {
            throw new \RuntimeException('Invalid "limit" property for rate limit. Ensure, that the value is greater than 0.', 1771074438);
        }
        if ($this->interval === '') {
            throw new \RuntimeException('Invalid "interval" property for rate limit.', 1771074439);
        }
        if ($this->policy === '') {
            throw new \RuntimeException('Invalid "policy" property for rate limit.', 1771074440);
        }
    }

    public function getConfiguration(string $identifier): array
    {
        return [
            'id' => 'extbase-' . $identifier,
            'policy' => $this->policy,
            'limit' => $this->limit,
            'interval' => $this->interval,
        ];
    }
}
