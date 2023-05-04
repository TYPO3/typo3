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
use TYPO3\CMS\Core\Domain\EqualityInterface;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Bridge to UriInterface to be used in Content-Security-Policy models,
 * which e.g. supports wildcard domains, like `*.typo3.org` or `https://*.typo3.org`.
 */
final class UriValue extends Uri implements \Stringable, EqualityInterface, CoveringInterface, SourceInterface
{
    private string $domainName = '';
    private bool $entireWildcard = false;
    private bool $domainWildcard = false;

    public static function fromUri(UriInterface $other): self
    {
        return new self((string)$other);
    }

    public function __toString(): string
    {
        if ($this->entireWildcard) {
            return '*';
        }
        if ($this->domainName !== '') {
            return ($this->domainWildcard ? '*.' : '') . $this->domainName;
        }
        return parent::__toString();
    }

    public function equals(EqualityInterface $other): bool
    {
        return $other instanceof self && (string)$other === (string)$this;
    }

    public function covers(CoveringInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        // `*` matches anything
        if ($this->entireWildcard) {
            return true;
        }
        // `*.example.com` or `example.com`
        if ($this->domainName !== '') {
            if ($this->domainWildcard) {
                if (($other->domainName !== '' && str_ends_with($other->domainName, '.' . $this->domainName))
                    || ($other->host !== '' && str_ends_with($other->host, '.' . $this->domainName))
                ) {
                    return true;
                }
            } else {
                if (($other->domainName !== '' && $other->domainName === $this->domainName)
                    || ($other->host !== '' && $other->host === $this->domainName)
                ) {
                    return true;
                }
            }
        }
        // `https://*.example.com`
        if ($other->host !== ''
            && $this->scheme === $other->scheme
            && str_starts_with($this->host, '*.')
            && str_ends_with($other->host, substr($this->host, 1))
        ) {
            return true;
        }
        return str_starts_with((string)$other, (string)$this);
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    protected function parseUri(string $uri): void
    {
        if ($uri === '*') {
            $this->entireWildcard = true;
            return;
        }
        parent::parseUri($uri);
        // ignore fragments per default
        $this->fragment = '';
        // handle domain names that were recognized as paths
        if ($this->canBeParsedAsWildcardDomainName()) {
            $this->domainName = substr($this->path, 4);
            $this->domainWildcard = true;
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
            || !str_starts_with($this->path, '%2A')
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
