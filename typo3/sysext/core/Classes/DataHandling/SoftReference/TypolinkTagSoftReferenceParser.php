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

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * TypoLink tag processing.
 * Will search for <link ...> and <a> tags in the content string and process any found.
 */
class TypolinkTagSoftReferenceParser extends AbstractSoftReferenceParser
{
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
                            'tokenValue' => 'file:' . ($linkDetails['file'] instanceof File ? $linkDetails['file']->getUid() : $fileIdMatch[1]),
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_PAGE && preg_match('/page\?uid=(\d+)#?(\d+)?/', $matches[1], $pageAndAnchorMatches)) {
                        $token = $this->makeTokenID((string)$key);
                        $content = '{softref:' . $token . '}';
                        $elements[$key]['matchString'] = $foundValue;
                        $elements[$key]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'pages:' . $linkDetails['pageuid'],
                            'tokenID' => $token,
                            'tokenValue' => $linkDetails['pageuid'],
                        ];
                        if (isset($pageAndAnchorMatches[2]) && $pageAndAnchorMatches[2] !== '') {
                            // Anchor is assumed to point to a content elements:
                            if (MathUtility::canBeInterpretedAsInteger($pageAndAnchorMatches[2])) {
                                // Initialize a new entry because we have a new relation:
                                $newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $key);
                                $elements[$newTokenID . ':' . $key] = [];
                                $elements[$newTokenID . ':' . $key]['matchString'] = 'Anchor Content Element: ' . $pageAndAnchorMatches[2];
                                $content .= '#{softref:' . $newTokenID . '}';
                                $elements[$newTokenID . ':' . $key]['subst'] = [
                                    'type' => 'db',
                                    'recordRef' => 'tt_content:' . $pageAndAnchorMatches[2],
                                    'tokenID' => $newTokenID,
                                    'tokenValue' => $pageAndAnchorMatches[2],
                                ];
                            } else {
                                // Anchor is a hardcoded string
                                $content .= '#' . $pageAndAnchorMatches[2];
                            }
                        }
                        $linkTags[$key] = str_replace($matches[1], $content, $foundValue);
                    } elseif ($linkDetails['type'] === LinkService::TYPE_URL) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'external',
                            'tokenID' => $token,
                            'tokenValue' => $linkDetails['url'],
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_EMAIL) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'string',
                            'tokenID' => $token,
                            'tokenValue' => $linkDetails['email'],
                        ];
                    } elseif ($linkDetails['type'] === LinkService::TYPE_TELEPHONE) {
                        $token = $this->makeTokenID((string)$key);
                        $elements[$key]['matchString'] = $foundValue;
                        $linkTags[$key] = str_replace($matches[1], '{softref:' . $token . '}', $foundValue);
                        $elements[$key]['subst'] = [
                            'type' => 'string',
                            'tokenID' => $token,
                            'tokenValue' => $linkDetails['telephone'],
                        ];
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
}
