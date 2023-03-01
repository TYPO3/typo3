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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Bridge to UriInterface to be used in Content-Security-Policy models,
 * which e.g. supports wildcard domains, like `*.typo3.org` or `https://*.typo3.org`.
 */
final class UriValue extends Uri implements \Stringable
{
    private string $domainName = '';

    public static function fromUri(UriInterface $other): self
    {
        return new self((string)$other);
    }

    public function __toString(): string
    {
        if ($this->domainName !== '') {
            return $this->domainName;
        }
        return parent::__toString();
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    protected function parseUri($uri): void
    {
        parent::parseUri($uri);
        // ignore fragments per default
        $this->fragment = '';
        // handle domain names that were recognized as paths
        if ($this->canBeParsedAsWildcardDomainName()) {
            $this->domainName = '*.' . substr($this->path, 4);
        } elseif ($this->canBeParsedAsDomainName()) {
            $this->domainName = $this->path;
        }
    }

    private function canBeParsedAsDomainName(): bool
    {
        return $this->path !== ''
            && $this->scheme === ''
            && $this->host === ''
            && $this->query === ''
            && $this->userInfo === ''
            && $this->validateDomainName($this->path);
    }

    private function canBeParsedAsWildcardDomainName(): bool
    {
        if ($this->path === ''
            || $this->scheme !== ''
            || $this->host !== ''
            || $this->query !== ''
            || $this->userInfo !== ''
            || stripos($this->path, '%2A') !== 0
        ) {
            return false;
        }
        $possibleDomainName = substr($this->path, 4);
        return $this->validateDomainName($possibleDomainName);
    }

    private function validateDomainName(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_DOMAIN) !== false;
    }
}
