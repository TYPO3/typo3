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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * TypoLink value processing.
 * Will process input value as a TypoLink value.
 * References to page id or file, possibly with anchor/target, possibly commaseparated list.
 */
class TypolinkSoftReferenceParser extends AbstractSoftReferenceParser
{
    use PublicMethodDeprecationTrait;

    private $deprecatedPublicMethods = [
        'getTypoLinkParts' => 'getTypoLinkParts() is for internal usage only. It is implicitly called by the parse() method. Calling getTypoLinkParts() will throw and error in v12.',
        'setTypoLinkPartsElement' => 'setTypoLinkPartsElement() is for internal usage only. It is implicitly called by the parse() method. Calling setTypoLinkPartsElement() will throw an error in v12.',
    ];

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);

        // First, split the input string by a comma if the "linkList" parameter is set.
        // An example: the link field for images in content elements of type "textpic" or "image". This field CAN be configured to define a link per image, separated by comma.
        if (in_array('linkList', $this->parameters, true)) {
            // Preserving whitespace on purpose.
            $linkElement = explode(',', $content);
        } else {
            // If only one element, just set in this array to make it easy below.
            $linkElement = [$content];
        }
        // Traverse the links now:
        $elements = [];
        foreach ($linkElement as $k => $typolinkValue) {
            $tLP = $this->getTypoLinkParts($typolinkValue, $table, $uid);
            $linkElement[$k] = $this->setTypoLinkPartsElement($tLP, $elements, $typolinkValue, $k);
        }

        return SoftReferenceParserResult::create(
            implode(',', $linkElement),
            $elements
        );
    }

    /**
     * Analyze content as a TypoLink value and return an array with properties.
     * TypoLinks format is: <link [typolink] [browser target] [css class] [title attribute] [additionalParams]>.
     * See TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * The syntax of the [typolink] part is: [typolink] = [page id][,[type value]][#[anchor, if integer = tt_content uid]]
     * The extraction is based on how \TYPO3\CMS\Frontend\ContentObject::typolink() behaves.
     *
     * @param string $typolinkValue TypoLink value.
     * @param string $referenceTable The reference table
     * @param int $referenceUid The UID of the reference record
     * @return array Array with the properties of the input link specified. The key "type" will reveal the type. If that is blank it could not be determined.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * @see setTypoLinkPartsElement()
     */
    protected function getTypoLinkParts(string $typolinkValue, string $referenceTable, int $referenceUid)
    {
        $finalTagParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($typolinkValue);

        $link_param = $finalTagParts['url'];
        // we define various keys below, "url" might be misleading
        unset($finalTagParts['url']);

        if (stripos(rawurldecode(trim($link_param)), 'phar://') === 0) {
            throw new \RuntimeException(
                'phar scheme not allowed as soft reference target',
                1530030672
            );
        }

        $linkService = GeneralUtility::makeInstance(LinkService::class);
        try {
            $linkData = $linkService->resolve($link_param);
            switch ($linkData['type']) {
                case LinkService::TYPE_RECORD:
                    $referencePageId = $referenceTable === 'pages'
                        ? $referenceUid
                        : (int)(BackendUtility::getRecord($referenceTable, $referenceUid)['pid'] ?? 0);
                    if ($referencePageId) {
                        $pageTsConfig = BackendUtility::getPagesTSconfig($referencePageId);
                        $table = $pageTsConfig['TCEMAIN.']['linkHandler.'][$linkData['identifier'] . '.']['configuration.']['table'] ?? $linkData['identifier'];
                    } else {
                        // Backwards compatibility for the old behaviour, where the identifier was saved as the table.
                        $table = $linkData['identifier'];
                    }
                    $finalTagParts['table'] = $table;
                    $finalTagParts['uid'] = $linkData['uid'];
                    break;
                case LinkService::TYPE_PAGE:
                    $linkData['pageuid'] = (int)$linkData['pageuid'];
                    if (isset($linkData['pagetype'])) {
                        $linkData['pagetype'] = (int)$linkData['pagetype'];
                    }
                    if (isset($linkData['fragment'])) {
                        $finalTagParts['anchor'] = $linkData['fragment'];
                    }
                    break;
                case LinkService::TYPE_FILE:
                case LinkService::TYPE_UNKNOWN:
                    if (isset($linkData['file'])) {
                        $finalTagParts['type'] = LinkService::TYPE_FILE;
                        $linkData['file'] = $linkData['file'] instanceof FileInterface ? $linkData['file']->getUid() : $linkData['file'];
                    } else {
                        $pU = parse_url($link_param);
                        parse_str($pU['query'] ?? '', $query);
                        if (isset($query['uid'])) {
                            $finalTagParts['type'] = LinkService::TYPE_FILE;
                            $finalTagParts['file'] = (int)$query['uid'];
                        }
                    }
                    break;
            }
            return array_merge($finalTagParts, $linkData);
        } catch (UnknownLinkHandlerException $e) {
            // Cannot handle anything
            return $finalTagParts;
        }
    }

    /**
     * Recompile a TypoLink value from the array of properties made with getTypoLinkParts() into an elements array
     *
     * @param array $tLP TypoLink properties
     * @param array $elements Array of elements to be modified with substitution / information entries.
     * @param string $content The content to process.
     * @param int $idx Index value of the found element - user to make unique but stable tokenID
     * @return string The input content, possibly containing tokens now according to the added substitution entries in $elements
     * @see getTypoLinkParts()
     */
    protected function setTypoLinkPartsElement($tLP, &$elements, $content, $idx)
    {
        // Initialize, set basic values. In any case a link will be shown
        $tokenID = $this->makeTokenID('setTypoLinkPartsElement:' . $idx);
        $elements[$tokenID . ':' . $idx] = [];
        $elements[$tokenID . ':' . $idx]['matchString'] = $content;
        // Based on link type, maybe do more:
        switch ((string)$tLP['type']) {
            case LinkService::TYPE_EMAIL:
                // Mail addresses can be substituted manually:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'string',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['email'],
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case LinkService::TYPE_TELEPHONE:
                // phone number can be substituted manually:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'string',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['telephone'],
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case LinkService::TYPE_URL:
                // URLs can be substituted manually
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'external',
                    'tokenID' => $tokenID,
                    'tokenValue' => $tLP['url'],
                ];
                // Output content will be the token instead:
                $content = '{softref:' . $tokenID . '}';
                break;
            case LinkService::TYPE_FOLDER:
                // This is a link to a folder...
                unset($elements[$tokenID . ':' . $idx]);
                return $content;
            case LinkService::TYPE_FILE:
                // Process files referenced by their FAL uid
                if (isset($tLP['file'])) {
                    $fileId = $tLP['file'] instanceof FileInterface ? $tLP['file']->getUid() : $tLP['file'];
                    // Token and substitute value
                    $elements[$tokenID . ':' . $idx]['subst'] = [
                        'type' => 'db',
                        'recordRef' => 'sys_file:' . $fileId,
                        'tokenID' => $tokenID,
                        'tokenValue' => 'file:' . $fileId,
                    ];
                    // Output content will be the token instead:
                    $content = '{softref:' . $tokenID . '}';
                } elseif ($tLP['identifier'] ?? false) {
                    $linkHandlerValue = explode(':', trim($tLP['identifier']), 2)[1];
                    if (MathUtility::canBeInterpretedAsInteger($linkHandlerValue)) {
                        // Token and substitute value
                        $elements[$tokenID . ':' . $idx]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'sys_file:' . $linkHandlerValue,
                            'tokenID' => $tokenID,
                            'tokenValue' => $tLP['identifier'],
                        ];
                        // Output content will be the token instead:
                        $content = '{softref:' . $tokenID . '}';
                    } else {
                        // This is a link to a folder...
                        return $content;
                    }
                } else {
                    return $content;
                }
                break;
            case LinkService::TYPE_PAGE:
                // Rebuild page reference typolink part:
                $content = '';
                // Set page id:
                if ($tLP['pageuid']) {
                    $content .= '{softref:' . $tokenID . '}';
                    $elements[$tokenID . ':' . $idx]['subst'] = [
                        'type' => 'db',
                        'recordRef' => 'pages:' . $tLP['pageuid'],
                        'tokenID' => $tokenID,
                        'tokenValue' => $tLP['pageuid'],
                    ];
                }
                // Add type if applicable
                if ((string)($tLP['pagetype'] ?? '') !== '') {
                    $content .= ',' . $tLP['pagetype'];
                }
                // Add anchor if applicable
                if ((string)($tLP['anchor'] ?? '') !== '') {
                    // Anchor is assumed to point to a content elements:
                    if (MathUtility::canBeInterpretedAsInteger($tLP['anchor'])) {
                        // Initialize a new entry because we have a new relation:
                        $newTokenID = $this->makeTokenID('setTypoLinkPartsElement:anchor:' . $idx);
                        $elements[$newTokenID . ':' . $idx] = [];
                        $elements[$newTokenID . ':' . $idx]['matchString'] = 'Anchor Content Element: ' . $tLP['anchor'];
                        $content .= '#{softref:' . $newTokenID . '}';
                        $elements[$newTokenID . ':' . $idx]['subst'] = [
                            'type' => 'db',
                            'recordRef' => 'tt_content:' . $tLP['anchor'],
                            'tokenID' => $newTokenID,
                            'tokenValue' => $tLP['anchor'],
                        ];
                    } else {
                        // Anchor is a hardcoded string
                        $content .= '#' . $tLP['anchor'];
                    }
                }
                break;
            case LinkService::TYPE_RECORD:
                $elements[$tokenID . ':' . $idx]['subst'] = [
                    'type' => 'db',
                    'recordRef' => $tLP['table'] . ':' . $tLP['uid'],
                    'tokenID' => $tokenID,
                    'tokenValue' => $content,
                ];

                $content = '{softref:' . $tokenID . '}';
                break;
            default:
                $event = new AppendLinkHandlerElementsEvent($tLP, $content, $elements, $idx, $tokenID);
                $this->eventDispatcher->dispatch($event);

                $elements = $event->getElements();
                $tLP = $event->getLinkParts();
                $content = $event->getContent();

                if (!$event->isResolved()) {
                    $elements[$tokenID . ':' . $idx]['error'] = 'Couldn\'t decide typolink mode.';
                    return $content;
                }
        }
        // Finally, for all entries that was rebuild with tokens, add target, class, title and additionalParams in the end
        $tLP['url'] = $content;
        // Return rebuilt typolink value
        return GeneralUtility::makeInstance(TypoLinkCodecService::class)->encode($tLP);
    }
}
