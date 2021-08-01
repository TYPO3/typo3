<?php

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

namespace TYPO3\CMS\Core\Database;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserResult;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class for processing of the default soft reference types for CMS:
 *
 * - 'substitute' : A full field value targeted for manual substitution (for import /export features)
 * - 'notify' : Just report if a value is found, nothing more.
 * - 'typolink' : references to page id or file, possibly with anchor/target, possibly commaseparated list.
 * - 'typolink_tag' : As typolink, but searching for <link> tag to encapsulate it.
 * - 'email' : Email highlight
 * - 'url' : URL highlights (with a scheme)
 * @deprecated will be removed in TYPO3 v12.0 in favor of SoftReferenceParserInterface
 */
class SoftReferenceIndex extends TypolinkSoftReferenceParser implements SingletonInterface
{
    public string $tokenID_basePrefix;
    protected SoftReferenceParserFactory $softReferenceParserFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SoftReferenceParserFactory $softReferenceParserFactory
    ) {
        parent::__construct($eventDispatcher);
        $this->softReferenceParserFactory = $softReferenceParserFactory;
        trigger_error(
            'SoftReferenceIndex will be removed in TYPO3 v12.0, use appropriate TYPO3\CMS\Core\DataHandling\SoftReference\* class instead.',
            E_USER_DEPRECATED
        );
    }

    /**
     * @deprecated since v11, will be removed in v12
     */
    public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '')
    {
        $this->parserKey = (string)$spKey;
        $this->setTokenIdBasePrefix($table, (string)$uid, $field, $structurePath);

        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser($spKey);
        $softReferenceParser->setParserKey($spKey, $spParams);
        return $softReferenceParser->parse($table, $field, $uid, $content, $structurePath)->toNullableArray();
    }

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        // does nothing
        return SoftReferenceParserResult::createWithoutMatches();
    }

    public function setParserKey(string $parserKey, array $parameters): void
    {
        // does nothing
    }

    /**
     * TypoLink value processing.
     * Will process input value as a TypoLink value.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns. value "linkList" will split the string by comma before processing.
     * @return array|null Result array on positive matches, see description above. Otherwise null
     * @see \TYPO3\CMS\Frontend\ContentObject::typolink()
     * @see getTypoLinkParts()
     */
    public function findRef_typolink($content, $spParams)
    {
        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser('typolink');
        $softReferenceParser->setParserKey('typolink', (array)$spParams);
        return $softReferenceParser->parse('', '', '', $content)->toNullableArray();
    }

    /**
     * TypoLink tag processing.
     * Will search for <link ...> and <a> tags in the content string and process any found.
     *
     * @param string $content The input content to analyze
     * @return array|null Result array on positive matches, see description above. Otherwise null
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink()
     * @see getTypoLinkParts()
     */
    public function findRef_typolink_tag($content)
    {
        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser('typolink_tag');
        $softReferenceParser->setParserKey('typolink_tag', []);
        return $softReferenceParser->parse('', '', '', $content)->toNullableArray();
    }

    /**
     * Finding email addresses in content and making them substitutable.
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_email($content, $spParams)
    {
        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser('email');
        $softReferenceParser->setParserKey('email', (array)$spParams);
        return $softReferenceParser->parse('', '', '', $content)->toNullableArray();
    }

    /**
     * Finding URLs in content
     *
     * @param string $content The input content to analyze
     * @param array $spParams Parameters set for the softref parser key in TCA/columns
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_url($content, $spParams)
    {
        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser('url');
        $softReferenceParser->setParserKey('url', (array)$spParams);
        return $softReferenceParser->parse('', '', '', $content)->toNullableArray();
    }

    /**
     * Finding reference to files from extensions in content, but only to notify about their existence. No substitution
     *
     * @param string $content The input content to analyze
     * @return array|null Result array on positive matches, see description above. Otherwise null
     */
    public function findRef_extension_fileref($content)
    {
        $softReferenceParser = $this->softReferenceParserFactory->getSoftReferenceParser('ext_fileref');
        $softReferenceParser->setParserKey('ext_fileref', []);
        return $softReferenceParser->parse('', '', '', $content)->toNullableArray();
    }
}
