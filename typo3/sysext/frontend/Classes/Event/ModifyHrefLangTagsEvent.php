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

/**
 * Listeners to this Event will be able to modify the hreflang tags that will be generated. You can use this when you
 * have an edge case language scenario and need to alter the default hreflang tags.
 */
final class ModifyHrefLangTagsEvent
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $hrefLangs = [];

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getHrefLangs(): array
    {
        return $this->hrefLangs;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Set the hreflangs. This should be an array in format:
     *  [
     *     'en-US' => 'https://example.com',
     *     'nl-NL' => 'https://example.com/nl'
     *  ]
     * @param array $hrefLangs
     */
    public function setHrefLangs(array $hrefLangs): void
    {
        $this->hrefLangs = $hrefLangs;
    }

    /**
     * Add a hreflang tag to the current list of hreflang tags
     *
     * @param string $languageCode The language of the hreflang tag you would like to add. For example: nl-NL
     * @param string $url The URL of the translation. For example: https://example.com/nl
     */
    public function addHrefLang(string $languageCode, string $url): void
    {
        $this->hrefLangs[$languageCode] = $url;
    }
}
