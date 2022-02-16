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

namespace TYPO3\CMS\Frontend\Event;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Event to allow listeners to disable the loading of cached page data when a page is requested.
 * Does not have any effect if "no_cache" is activated, or if there is no cached version of a page.
 */
final class ShouldUseCachedPageDataIfAvailableEvent
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly TypoScriptFrontendController $controller,
        private bool $shouldUseCachedPageData
    ) {
    }

    public function getController(): TypoScriptFrontendController
    {
        return $this->controller;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function shouldUseCachedPageData(): bool
    {
        return $this->shouldUseCachedPageData;
    }

    public function setShouldUseCachedPageData(bool $shouldUseCachedPageData): void
    {
        $this->shouldUseCachedPageData = $shouldUseCachedPageData;
    }
}
