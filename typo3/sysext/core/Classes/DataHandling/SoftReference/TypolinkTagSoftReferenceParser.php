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

namespace TYPO3\CMS\Core\DataHandling\SoftReference;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TypoLink tag processing.
 * Will search for <link ...> and <a> tags in the content string and process any found.
 */
class TypolinkTagSoftReferenceParser extends AbstractSoftReferenceParser
{
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);

        // Parse string for special TYPO3 <link> tag:
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $linkTags = $htmlParser->splitTags('a', $content);
        // Traverse result:
        $elements = [];
        foreach ($linkTags as $key => $foundValue) {
            if ($key % 2 && preg_match('/href="([^"]+)"/', $foundValue, $matches)) {
                try {
                    $linkDetails = $linkService->resolve($matches[1]);
                    if ($linkDetails['type'] === LinkService::TYPE_FILE && preg_match('/file\?uid=(\d+)/', $matches[1], $fileIdMatch)) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'sys_file:' . $fileIdMatch[1],
                            'tokenID' => $token,
                            'tokenValue' => $matches[1],
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_PAGE && preg_match('/page\?(?:[^#]*?&(?:amp;)?)?uid=(\d+)[^#]*(?:#(\d+)$)?/', $matches[1], $pageAndAnchorMatches, PREG_OFFSET_CAPTURE)) {
                        $token = $this->makeTokenID((string)$key);
                        // Only the page uid and a content element uid in the fragment are tokenized,
                        // the link keeps its scheme, parameters and fragment
                        $tokenizedHref = $matches[1];
                        $elements[$key]['matchString'] = $foundValue;
                        $elements[$key]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'pages:' . ($linkDetails['pageuid'] ?? 0),
                            'tokenID' => $token,
                            'tokenValue' => $linkDetails['pageuid'] ?? '',
                        ];
                        if (isset($pageAndAnchorMatches[2]) && $pageAndAnchorMatches[2][1] >= 0) {
                            // Anchor is assumed to point to a content element:
                            [$contentElementUid, $contentElementUidOffset] = $pageAndAnchorMatches[2];
                            // Initialize a new entry because we have a new relation:
                            $newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $key);
                            $elements[$newTokenID . ':' . $key] = [];
                            $elements[$newTokenID . ':' . $key]['matchString'] = 'Anchor Content Element: ' . $contentElementUid;
                            $tokenizedHref = substr_replace($tokenizedHref, '{softref:' . $newTokenID . '}', $contentElementUidOffset, strlen($contentElementUid));
                            $elements[$newTokenID . ':' . $key]['subst'] = [
                                'type' => 'db',
                                'recordRef' => 'tt_content:' . $contentElementUid,
                                'tokenID' => $newTokenID,
                                'tokenValue' => $contentElementUid,
                            ];
                        }
                        [$pageUid, $pageUidOffset] = $pageAndAnchorMatches[1];
                        $tokenizedHref = substr_replace($tokenizedHref, '{softref:' . $token . '}', $pageUidOffset, strlen($pageUid));
                        $linkTags[$key] = str_replace($matches[1], $tokenizedHref, $foundValue);
                    } elseif ($linkDetails['type'] === LinkService::TYPE_RECORD) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $recordTable = $this->resolveRecordLinkTable((string)$linkDetails['identifier'], $table, $uid);
                        $elements[$key]['subst'] = [
                            'type' => 'db',
                            'recordRef' => $recordTable . ':' . $linkDetails['uid'],
                            'tokenID' => $token,
                            'tokenValue' => $matches[1],
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_URL) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'external',
                            'tokenID' => $token,
                            'tokenValue' => (string)($linkDetails['url'] ?? ''),
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_EMAIL) {
                        $token = $this->makeTokenID((string)$key);
                        $tokenValue = (string)($linkDetails['email'] ?? '');
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], $this->tokenizeLinkValue($matches[1], $tokenValue, $token), $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'string',
                            'tokenID' => $token,
                            'tokenValue' => $tokenValue,
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_TELEPHONE) {
                        $token = $this->makeTokenID((string)$key);
                        $tokenValue = (string)($linkDetails['telephone'] ?? '');
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], $this->tokenizeLinkValue($matches[1], $tokenValue, $token), $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'string',
                            'tokenID' => $token,
                            'tokenValue' => $tokenValue,
                        ];
                    } else {
                        $token = $this->makeTokenID((string)$key);
                        $event = new AppendLinkHandlerElementsEvent($linkDetails, $content, $elements, $key, $token);
                        $this->eventDispatcher->dispatch($event);

                        if (!$event->isResolved()) {
                            continue;
                        }

                        $elements = $event->getElements();
                    }
                } catch (\Exception $e) {
                    // skip invalid links
                }
            }
        }
        // Return output:
        return SoftReferenceParserResult::create(
            implode('', $linkTags),
            $elements
        );
    }

    /**
     * Substitutes only the resolved value inside the href, so that a scheme like "mailto:"
     * or "tel:" and any trailing parameters are not part of the token and therefore survive
     * a later import, which replaces the token with the bare tokenValue again.
     */
    private function tokenizeLinkValue(string $href, string $tokenValue, string $token): string
    {
        $position = $tokenValue === '' ? false : strpos($href, $tokenValue);
        if ($position === false) {
            return '{softref:' . $token . '}';
        }
        return substr_replace($href, '{softref:' . $token . '}', $position, strlen($tokenValue));
    }
}
