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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\LinkHandling\LinkService;

/**
 * Builds a TypoLink to a file (relative to fileadmin/ or something)
 * or otherwise detects as an external URL
 */
class LegacyLinkBuilder extends AbstractTypolinkBuilder implements TypolinkBuilderInterface
{
    public function buildLink(array $linkDetails, array $configuration, ServerRequestInterface $request, string $linkText = ''): LinkResultInterface
    {
        $target = $linkDetails['target'] ?? '';
        if ($linkDetails['file'] ?? false) {
            $linkDetails['type'] = LinkService::TYPE_FILE;
            $linkLocation = $linkDetails['file'];
            // Setting title if blank value to link
            $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, rawurldecode($linkLocation));
            $linkLocation = (!str_starts_with($linkLocation, '/') ? $this->getAbsRefPrefix($request) : '') . $linkLocation;
            $url = $linkLocation;
            $url = $this->forceAbsoluteUrl($url, $configuration, $request);
            $target = $target ?: $this->resolveTargetAttribute($configuration, 'fileTarget', $request->getAttribute('currentContentObject'));
        } elseif ($linkDetails['url'] ?? false) {
            $linkDetails['type'] = LinkService::TYPE_URL;
            $target = $target ?: $this->resolveTargetAttribute($configuration, 'extTarget', $request->getAttribute('currentContentObject'));
            $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, $linkDetails['url']);
            $url = $linkDetails['url'];
        } else {
            throw new UnableToLinkException('Unknown link detected, so ' . $linkText . ' was not linked.', 1490990031, null, $linkText);
        }
        return (new LinkResult((string)$linkDetails['type'], (string)$url))->withTarget($target)->withLinkConfiguration($configuration)->withLinkText($linkText);
    }
}
