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

/**
 * Builds a TypoLink to an external URL
 */
class ExternalUrlLinkBuilder extends AbstractTypolinkBuilder
{
    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        $url = $linkDetails['url'] ?? '';
        // issue https://forge.typo3.org/issues/101083 forces absolute path URLs
        // like `/path/some-file.png` to be handled as external URL, and that's
        // why the URL is forced to contain a fully qualified domain name as well
        $url = $this->forceAbsoluteUrl($url, $conf);
        $linkText = $this->encodeFallbackLinkTextIfLinkTextIsEmpty($linkText, $url);
        return (new LinkResult(LinkService::TYPE_URL, (string)$url))
            ->withLinkConfiguration($conf)
            ->withTarget(
                $target ?: $this->resolveTargetAttribute($conf, 'extTarget'),
            )
            ->withLinkText($linkText);
    }
}
