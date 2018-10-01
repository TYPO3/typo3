<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site\Entity;

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

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Entity representing a site with legacy configuration (sys_domain) and all available
 * languages in the system (sys_language)
 * @internal this class will likely be removed in TYPO3 v10.0. Please use SiteMatcher and SiteInterface to work with Sites in your own code.
 */
class PseudoSite extends NullSite implements SiteInterface
{
    /**
     * @var string[]
     */
    protected $entryPoints;

    /**
     * attached sys_domain records
     * @var array
     */
    protected $domainRecords = [];

    /**
     * Sets up a pseudo site object, and its languages and error handlers
     *
     * @param int $rootPageId
     * @param array $configuration
     */
    public function __construct(int $rootPageId, array $configuration)
    {
        $this->rootPageId = $rootPageId;
        foreach ($configuration['domains'] ?? [] as $domain) {
            if (empty($domain['domainName'] ?? false)) {
                continue;
            }
            $this->domainRecords[] = $domain;
            $this->entryPoints[] = new Uri($this->sanitizeBaseUrl($domain['domainName'] ?: ''));
        }
        if (empty($this->entryPoints)) {
            $this->entryPoints = [new Uri('/')];
        }
        $baseEntryPoint = reset($this->entryPoints);

        parent::__construct($configuration['languages'], $baseEntryPoint);
    }

    /**
     * Returns a generic identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return '#PSEUDO_' . $this->rootPageId;
    }

    /**
     * Returns the first base URL of this site, falls back to "/"
     */
    public function getBase(): UriInterface
    {
        return $this->entryPoints[0] ?? new Uri('/');
    }

    /**
     * Returns the base URLs of this site, if none given, it's always "/"
     *
     * @return UriInterface[]
     */
    public function getEntryPoints(): array
    {
        return $this->entryPoints;
    }

    /**
     * Returns the root page ID of this site
     *
     * @return int
     */
    public function getRootPageId(): int
    {
        return $this->rootPageId;
    }

    /**
     * If a site base contains "/" or "www.domain.com", it is ensured that
     * parse_url() can handle this kind of configuration properly.
     *
     * @param string $base
     * @return string
     */
    protected function sanitizeBaseUrl(string $base): string
    {
        // no protocol ("//") and the first part is no "/" (path), means that this is a domain like
        // "www.domain.com/blabla", and we want to ensure that this one then gets a "no-scheme agnostic" part
        if (!empty($base) && strpos($base, '//') === false && $base{0} !== '/') {
            // either a scheme is added, or no scheme but with domain, or a path which is not absolute
            // make the base prefixed with a slash, so it is recognized as path, not as domain
            // treat as path
            if (strpos($base, '.') === false) {
                $base = '/' . $base;
            } else {
                // treat as domain name
                $base = '//' . $base;
            }
        }
        return $base;
    }
}
