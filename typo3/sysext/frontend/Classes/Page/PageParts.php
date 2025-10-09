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

    /**
     * "Last change" is page record "SYS_LASTCHANGED", initialized with pages record "tstamp", whichever is higher.
     * This value is later modified by ContentObjectRenderer when records are rendered. The pages record column
     * "SYS_LASTCHANGED" is then written to the highest value at the end of FE rendering.
     * The main goal of pages "SYS_LASTCHANGED" is to have a DB field on pages that "knows" when a record that is
     * displayed on the page is last changed. This information is for instance used in the ext:seo sitemap XML.
     *
     * This approach is flawed for multimple reasons: The "last updated" value can only be "gathered" during FE
     * rendering since BE does not necessarily know all elements rendered on a page when for example a news plugin
     * fetches record from "elsewhere", e.g. a record storage page. This means the "final" value of pages "SYS_LASTCHANGED"
     * is only ready after all content elements have been rendered. It also relies on such a plugin actively taking
     * care of updating $lastChanged here (@see ContentObjectRenderer->lastChanged()). Rendering a "Last updated"
     * value on a page thus only works when it is output at the end (or as USER_INT which are calculated in the end).
     * Additionally, a plugin like the ext:seo pages sitemap (which only gives a list of pages but does not actually
     * render all pages) can only render a correct value after a page containing a "just changed" content element
     * has been FE rendered at least once to have the newest "SYS_LASTCHANGED" value in DB.
     */
    private int $lastChanged = 0;

    /**
     * Becomes the HTTP Response header 'Content-Type'.
     * This is obviously Response and not Response body-stream related, but we currently "park" it here for
     * applications like Extbase that need to reset to something like application/json.
     *
     * @todo: This should be remodeled. This should be part of a construct that gathers page parts and
     *        Response related details to finally compile a Response in the end. "Parking" that information
     *        here is a temporary measure on the way to a better solution where for instance single
     *        cObj return a data object instead of a string.
     */
    private string $httpContentType = 'text/html; charset=utf-8';

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setLastChanged(int $timestamp): void
    {
        $this->lastChanged = $timestamp;
    }

    public function getLastChanged(): int
    {
        return $this->lastChanged;
    }

    public function setHttpContentType(string $contentType): void
    {
        $this->httpContentType = $contentType;
    }

    public function getHttpContentType(): string
    {
        return $this->httpContentType;
    }
}
