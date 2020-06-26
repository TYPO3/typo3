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

namespace TYPO3\CMS\Redirects\Configuration;

class RedirectCleanupConfiguration
{
    /**
     * @var int
     */
    protected $hitCount;

    /**
     * @var int
     */
    protected $days = 90;

    /**
     * @var string[]
     */
    protected $domains = [];

    /**
     * @var int[]
     */
    protected $statusCodes = [];

    /**
     * @var string
     */
    protected $path;

    public function getHitCount(): ?int
    {
        return $this->hitCount;
    }

    public function setHitCount(?int $hitCount): self
    {
        $this->hitCount = $hitCount;
        return $this;
    }

    public function getDays(): ?int
    {
        return $this->days;
    }

    public function setDays(int $days): self
    {
        $this->days = $days;
        return $this;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function setDomains(array $domains): self
    {
        $this->domains = $domains;
        return $this;
    }

    public function getStatusCodes(): array
    {
        return $this->statusCodes;
    }

    public function setStatusCodes(array $statusCodes): self
    {
        $this->statusCodes = $statusCodes;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }
}
