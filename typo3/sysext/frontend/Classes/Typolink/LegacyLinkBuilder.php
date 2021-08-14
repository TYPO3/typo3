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

namespace TYPO3\CMS\Frontend\Typolink;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;

/**
 * Builds a TypoLink to a file (relative to fileadmin/ or something)
 * or otherwise detects as an external URL
 */
class LegacyLinkBuilder extends AbstractTypolinkBuilder
{
    /**
     * @inheritdoc
     */
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        $tsfe = $this->getTypoScriptFrontendController();
        if ($linkDetails['file']) {
            $linkDetails['type'] = LinkService::TYPE_FILE;
            $linkLocation = $linkDetails['file'];
            // Setting title if blank value to link
            $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, rawurldecode($linkLocation));
            $linkLocation = (strpos($linkLocation, '/') !== 0 ? $tsfe->absRefPrefix : '') . $linkLocation;
            $url = $this->processUrl(UrlProcessorInterface::CONTEXT_FILE, $linkLocation, $conf) ?? '';
            $url = $this->forceAbsoluteUrl($url, $conf);
            $target = $target ?: $this->resolveTargetAttribute($conf, 'fileTarget', false, $tsfe->fileTarget);
        } elseif ($linkDetails['url']) {
            $linkDetails['type'] = LinkService::TYPE_URL;
            $target = $target ?: $this->resolveTargetAttribute($conf, 'extTarget', true, $tsfe->extTarget);
            $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, $linkDetails['url']);
            $url = $this->processUrl(UrlProcessorInterface::CONTEXT_EXTERNAL, $linkDetails['url'], $conf);
        } else {
            throw new UnableToLinkException('Unknown link detected, so ' . $linkText . ' was not linked.', 1490990031, null, $linkText);
        }
        return (new LinkResult((string)$linkDetails['type'], (string)$url))->withTarget($target)->withLinkConfiguration($conf)->withLinkText($linkText);
    }
}
