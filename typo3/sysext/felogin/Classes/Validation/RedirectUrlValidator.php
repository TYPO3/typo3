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

namespace TYPO3\CMS\FrontendLogin\Validation;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Used to check if a referrer or a redirect URL is valid to be used as within Frontend Logins
 * for redirects.
 *
 * @internal for now as it might get adopted for further streamlining against other validation paradigms
 */
class RedirectUrlValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(protected SiteFinder $siteFinder) {}

    /**
     * Checks if a given URL is valid / properly sanitized and/or the domain is known to TYPO3.
     */
    public function isValid(RequestInterface $request, string $value): bool
    {
        if ($value === '') {
            return false;
        }
        // Validate the URL
        if ($this->isRelativeUrl($value) || $this->isInCurrentDomain($request, $value) || $this->isInLocalDomain($value)) {
            return true;
        }
        // URL is not allowed
        $this->logger->debug('Url "{url}" was not accepted.', ['url' => $value]);
        return false;
    }

    /**
     * Determines whether the URL is on the current host and belongs to the
     * current TYPO3 installation. The scheme part is ignored in the comparison.
     */
    protected function isInCurrentDomain(RequestInterface $request, string $url): bool
    {
        $urlWithoutSchema = preg_replace('#^https?://#', '', $url) ?? '';
        $siteUrlWithoutSchema = preg_replace('#^https?://#', '', $request->getAttribute('normalizedParams')->getSiteUrl()) ?? '';
        // this condition only exists to satisfy phpstan, which complains that this could be an array, too.
        if (is_array($siteUrlWithoutSchema)) {
            $siteUrlWithoutSchema = $siteUrlWithoutSchema[0];
        }
        return str_starts_with($urlWithoutSchema . '/', $request->getAttribute('normalizedParams')->getHttpHost() . '/')
            && str_starts_with($urlWithoutSchema, $siteUrlWithoutSchema);
    }

    /**
     * Determines whether the URL matches a domain known to TYPO3.
     */
    protected function isInLocalDomain(string $url): bool
    {
        if (!GeneralUtility::isValidUrl($url)) {
            return false;
        }
        $parsedUrl = parse_url($url);
        if ($parsedUrl['scheme'] === 'http' || $parsedUrl['scheme'] === 'https') {
            $host = $parsedUrl['host'];
            foreach ($this->siteFinder->getAllSites() as $site) {
                if ($site->getBase()->getHost() === $host) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Determines whether the URL is relative to the current TYPO3 installation.
     */
    protected function isRelativeUrl(string $url): bool
    {
        $url = GeneralUtility::sanitizeLocalUrl($url);
        if (!empty($url)) {
            $parsedUrl = @parse_url($url);
            if ($parsedUrl !== false && !isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
                // If the relative URL starts with a slash, we need to check if it's within the current site path
                return $parsedUrl['path'][0] !== '/' || str_starts_with($parsedUrl['path'], GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
            }
        }
        return false;
    }
}
