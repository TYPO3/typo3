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

namespace TYPO3\CMS\Core\Configuration\Parser;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * A TS-Config parsing class which performs condition evaluation.
 *
 * This class does parsing of a compiled TSconfig string, and applies matching() based on the
 * Context (FE or BE) in it, allowing to be fully agnostic to the outside world.
 */
class PageTsConfigParser
{
    /**
     * @var TypoScriptParser
     */
    protected $typoScriptParser;

    /**
     * @var FrontendInterface
     */
    protected $cache;

    public function __construct(TypoScriptParser $typoScriptParser, FrontendInterface $cache)
    {
        $this->typoScriptParser = $typoScriptParser;
        $this->cache = $cache;
    }

    /**
     * Parses and matches a given string
     * Adds entries to the cache:
     * - when an exact on the conditions are there
     * - when a parse is there, then matches are happening anyway, and it is checked if this can be cached as well.
     *
     * If a site is provided the settings stored in the site's configuration is available as constants for the TSconfig.
     *
     * @param string $content pageTSconfig, usually accumulated by the PageTsConfigLoader
     * @param ConditionMatcherInterface $matcher an instance to match strings
     * @param Site|null $site The current site the page TSconfig is parsed for
     * @return array the
     */
    public function parse(string $content, ConditionMatcherInterface $matcher, ?Site $site = null): array
    {
        if ($site) {
            $siteSettings = $site->getConfiguration()['settings'] ?? [];
            if (!empty($siteSettings)) {
                $siteSettings = ArrayUtility::flattenPlain($siteSettings);
            }
            if (!empty($siteSettings)) {
                // Recursive substitution of site settings (up to 10 nested levels)
                // note: this code is more or less a duplicate of \TYPO3\CMS\Core\TypoScript\TemplateService::substituteConstants
                for ($i = 0; $i < 10; $i++) {
                    $beforeSubstitution = $content;
                    $content = preg_replace_callback(
                        '/\\{\\$(.[^}]*)\\}/',
                        static function (array $matches) use ($siteSettings): string {
                            return isset($siteSettings[$matches[1]]) && !is_array($siteSettings[$matches[1]])
                                ? (string)$siteSettings[$matches[1]] : $matches[0];
                        },
                        $content
                    );
                    if ($beforeSubstitution === $content) {
                        break;
                    }
                }
            }
        }

        $hashOfContent = md5('PAGES:' . $content);
        $cachedContent = $this->cache->get($hashOfContent);
        // Something about this content has been cached before, lets verify the matchings, if they also apply
        if (is_array($cachedContent) && is_array($cachedContent[0])) {
            // Cache entry found, see if the "matching" section matches with the matcher
            $storedData = $cachedContent[0];
            $storedMD5 = $cachedContent[1];
            $storedData['match'] = $this->matching($storedData['sections'] ?? [], $matcher);
            $hashOfDataWithMatches = md5(json_encode($storedData));
            // The matches are the same, so nothing to do here
            if ($hashOfDataWithMatches === $storedMD5) {
                $result = $storedData['TSconfig'];
            } else {
                // Create a hash out of the content-hash PLUS the matching information and try again
                $shash = md5($hashOfDataWithMatches . $hashOfContent);
                $storedData = $this->cache->get($shash);
                if (is_array($storedData)) {
                    $result = $storedData['TSconfig'];
                } else {
                    // Create a new content with the matcher, and cache it as a new entry
                    $parsedAndMatchedData = $this->parseAndMatch($content, $matcher);
                    // Now store the full data from the parser (with matches)
                    $this->cache->set($shash, $parsedAndMatchedData, ['pageTSconfig'], 0);
                    $result = $parsedAndMatchedData['TSconfig'];
                }
            }
            return $result;
        }

        // Nothing found in cache for this content string, let's do everything.
        $parsedAndMatchedData = $this->parseAndMatch($content, $matcher);
        // ALL parts, including the matching part is cached.
        $md5 = md5(json_encode($parsedAndMatchedData));
        $this->cache->set($hashOfContent, [$parsedAndMatchedData, $md5], ['pageTSconfig'], 0);
        return $parsedAndMatchedData['TSconfig'];
    }

    /**
     * Does the actual parsing using the TypoScriptParser "parse" method by applying a condition matcher.
     *
     * @param string $content The TSConfig being parsed
     * @param ConditionMatcherInterface $matcher
     * @return array Array containing the parsed TSConfig, the encountered sections, the matched sections. This is stored in cache.
     */
    protected function parseAndMatch(string $content, ConditionMatcherInterface $matcher): array
    {
        $this->typoScriptParser->parse($content, $matcher);
        return [
            'TSconfig' => $this->typoScriptParser->setup,
            'sections' => $this->typoScriptParser->sections,
            'match' => $this->typoScriptParser->sectionsMatch,
        ];
    }

    /**
     * Is just going through an array of conditions to determine which are matching (for getting correct cache entry)
     *
     * @param array $sectionsToMatch An array containing the sections to match
     * @param ConditionMatcherInterface $matcher
     * @return array The input array with matching sections to be filled into the "match" key
     */
    protected function matching(array $sectionsToMatch, ConditionMatcherInterface $matcher): array
    {
        $matches = [];
        foreach ($sectionsToMatch ?? [] as $key => $pre) {
            if ($matcher->match($pre)) {
                $matches[$key] = $pre;
            }
        }
        return $matches;
    }
}
