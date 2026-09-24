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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * A generic parser class useful if tokenID prefixes are needed.
 */
abstract class AbstractSoftReferenceParser implements SoftReferenceParserInterface
{
    protected string $tokenID_basePrefix = '';
    protected string $parserKey = '';
    protected array $parameters = [];

    /**
     * Make Token ID for input index.
     *
     * @param string $index Suffix value.
     * @return string Token ID
     */
    public function makeTokenID(string $index = ''): string
    {
        return md5($this->tokenID_basePrefix . ':' . $index);
    }

    /**
     * @param string $parserKey The softref parser key.
     * @param array $parameters Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
     */
    public function setParserKey(string $parserKey, array $parameters): void
    {
        $this->parserKey = $parserKey;
        $this->parameters = $parameters;
    }

    public function getParserKey(): string
    {
        return $this->parserKey;
    }

    /**
     * Resolves the database table of a record link (t3://record?identifier=...). The identifier is
     * mapped to a table via the link handler configuration of the page TSconfig of the referencing
     * record, otherwise the identifier is used as table name (behaviour before the mapping existed).
     */
    protected function resolveRecordLinkTable(string $identifier, string $referenceTable, int $referenceUid): string
    {
        $referencePageId = $referenceTable === 'pages'
            ? $referenceUid
            : (int)(BackendUtility::getRecord($referenceTable, $referenceUid)['pid'] ?? 0);
        if (!$referencePageId) {
            return $identifier;
        }
        $pageTsConfig = BackendUtility::getPagesTSconfig($referencePageId);
        return $pageTsConfig['TCEMAIN.']['linkHandler.'][$identifier . '.']['configuration.']['table'] ?? $identifier;
    }

    protected function setTokenIdBasePrefix(string $table, string $uid, string $field, string $structurePath): void
    {
        $this->tokenID_basePrefix = implode(':', [$table, $uid, $field, $structurePath, $this->getParserKey()]);
    }
}
