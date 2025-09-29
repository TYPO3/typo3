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

/**
 * Data object to collect sections the full response body is compiled from.
 * This is used and fed by various related middlewares.
 *
 * This object is attached as "frontend.page.parts" attribute to the frontend
 * application request object.
 *
 * This data object is highly experimental and marked as @internal since it
 * will likely change when the rendering and cache related parts of the frontend
 * middleware chain see further refactorings.
 *
 * @internal Experimental, will change.
 */
final class PageParts
{
    /**
     * Feed with the initial http body content string in TypoScriptFrontendInitialization
     * when content is fetched from cache. It is later used in FE RequestHandler to substitute
     * INT placeholders with their actual content (among other things) and then set as
     * Response HTTP body.
     *
     * @todo: This exists here since we need some place to park the initial content retrieved from cache.
     *        This property could (should?) vanish at some point when the middleware related classes
     *        TypoScriptFrontendInitialization, PrepareTypoScriptFrontendRendering and RequestHandler
     *        see further refactoring loops. Note there are events like AfterCacheableContentIsGeneratedEvent
     *        that already allow custom manipulation of events.
     */
    private string $content = '';

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
