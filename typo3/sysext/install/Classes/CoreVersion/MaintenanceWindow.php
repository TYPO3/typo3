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

namespace TYPO3\CMS\Install\CoreVersion;

class MaintenanceWindow
{
    protected ?\DateTimeInterface $communitySupport = null;
    protected ?\DateTimeInterface $eltsSupport = null;

    public function __construct(?\DateTimeInterface $communitySupport, ?\DateTimeInterface $eltsSupport)
    {
        $this->communitySupport = $communitySupport;
        $this->eltsSupport = $eltsSupport;
    }

    public static function fromApiResponse(array $response): self
    {
        $maintainedUntil = isset($response['maintained_until']) ? new \DateTimeImmutable($response['maintained_until']) : null;
        $eltsUntil = isset($response['elts_until']) ? new \DateTimeImmutable($response['elts_until']) : null;

        return new self($maintainedUntil, $eltsUntil);
    }

    public function isSupportedByCommunity(): bool
    {
        return $this->isSupported($this->communitySupport);
    }

    public function isSupportedByElts(): bool
    {
        return $this->isSupported($this->eltsSupport);
    }

    protected function isSupported(?\DateTimeInterface $supportedUntil): bool
    {
        return $supportedUntil !== null
            && (
                $supportedUntil >=
                new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
            );
    }
}
