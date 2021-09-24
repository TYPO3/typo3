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

/**
 * Used to check if a referrer or a redirect URL is valid to be used as within Frontend Logins
 * for redirects.
 *
 * @internal for now as it might get adopted for further streamlining against other validation paradigms
 */
class RedirectUrlValidator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * @param SiteFinder|null $siteFinder
     */
    public function __construct(?SiteFinder $siteFinder)
    {
        $this->siteFinder = $siteFinder ?? GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * Checks if a given URL is valid / properly sanitized and/or the domain is known to TYPO3.
     *
     * @param string $value
     * @return bool
     */
    public function isValid(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        // Validate the URL
        if ($this->isRelativeUrl($value) || $this->isInCurrentDomain($value) || $this->isInLocalDomain($value)) {
            return true;
        }
        // URL is not allowed
        $this->logger->warning('Url "{url}" was not accepted.', ['url' => $value]);
        return false;
    }

    /**
     * Determines whether the URL is on the current host and belongs to the
     * current TYPO3 installation. The scheme part is ignored in the comparison.
     *
     * @param string $url URL to be checked
     * @return bool Whether the URL belongs to the current TYPO3 installation
     */
    protected function isInCurrentDomain(string $url): bool
    {
        $urlWithoutSchema = preg_replace('#^https?://#', '', $url) ?? '';
        $siteUrlWithoutSchema = preg_replace('#^https?://#', '', $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl()) ?? '';
        // this condition only exists to satisfy phpstan, which complains that this could be an array, too.
        if (is_array($siteUrlWithoutSchema)) {
            $siteUrlWithoutSchema = $siteUrlWithoutSchema[0];
        }
        return strpos($urlWithoutSchema . '/', $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getHttpHost() . '/') === 0
            && strpos($urlWithoutSchema, $siteUrlWithoutSchema) === 0;
    }

    /**
     * Determines whether the URL matches a domain known to TYPO3.
     *
     * @param string $url Absolute URL which needs to be checked
     * @return bool Whether the URL is considered to be local
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
     *
     * @param string $url URL which needs to be checked
     * @return bool Whether the URL is considered to be relative
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
