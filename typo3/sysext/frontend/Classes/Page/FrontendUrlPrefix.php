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

namespace TYPO3\CMS\Frontend\Page;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A small utility layer to determine "absRefPrefix" from TypoScript,
 * the string that is prepended to all relative URLs in the frontend,
 * such as links and images.
 *
 * @internal It is highly possible that this class will not be needed anymore, so use it with care, if really needed
 */
final readonly class FrontendUrlPrefix
{
    public function getUrlPrefix(ServerRequestInterface $request): string
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')?->getConfigArray();
        $normalizedParams = $request->getAttribute('normalizedParams');
        // TypoScript config.forceAbsoluteUrls overrides config.absRefPrefix
        if ($typoScriptConfigArray['forceAbsoluteUrls'] ?? false) {
            return $normalizedParams->getSiteUrl();
        }
        $absRefPrefix = trim($typoScriptConfigArray['absRefPrefix'] ?? '');
        return $absRefPrefix === 'auto' ? $normalizedParams->getSitePath() : $absRefPrefix;
    }
}
