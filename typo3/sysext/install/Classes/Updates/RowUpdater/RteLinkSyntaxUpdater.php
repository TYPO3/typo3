<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Updates\RowUpdater;

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

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

/**
 * Move '<link ...' syntax to '<a href' in rte fields
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RteLinkSyntaxUpdater implements RowUpdaterInterface
{
    /**
     * Table list with field list that may have links them
     *
     * @var array
     */
    protected $tableFieldListToConsider = [];

    /**
     * @var array Table names that should be ignored.
     */
    protected $blackListedTables = [
        'sys_log',
        'sys_history',
        'sys_template',
    ];

    /**
     * Regular expressions to match the <link ...>content</link> inside
     * @var array
     */
    protected $regularExpressions = [
        'default' => '#
            (?\'tag\'<link\\s++(?\'typolink\'[^>]+)>)
            (?\'content\'(?:[^<]++|<(?!/link>))*+)
            </link>
            #xumsi',
        'flex' => '#
            (?\'tag\'&lt;link\\s++(?\'typolink\'(?:[^&]++|&(?!gt;))++)&gt;)
            (?\'content\'(?:[^&]++|&(?!lt;/link&gt;))*+)
            &lt;/link&gt;
            #xumsi'
    ];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Get title
     *
     * @return string Title
     */
    public function getTitle(): string
    {
        return 'Scan for old "<link>" syntax in richtext and text fields and update to "<a href>"';
    }

    /**
     * Return true if a table may have RTE fields
     *
     * @param string $tableName Table name to check
     * @return bool True if this table potentially has RTE fields
     */
    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        if (!is_array($GLOBALS['TCA'][$tableName])) {
            throw new \RuntimeException(
                'Globals TCA of ' . $tableName . ' must be an array',
                1484173035
            );
        }
        $result = false;
        if (in_array($tableName, $this->blackListedTables, true)) {
            return $result;
        }
        $tcaOfTable = $GLOBALS['TCA'][$tableName];
        if (!is_array($tcaOfTable['columns'])) {
            return $result;
        }
        foreach ($tcaOfTable['columns'] as $fieldName => $fieldConfiguration) {
            if (isset($fieldConfiguration['config']['type'])
                && in_array($fieldConfiguration['config']['type'], ['input', 'text', 'flex'], true)
            ) {
                $result = true;
                if (!is_array($this->tableFieldListToConsider[$tableName])) {
                    $this->tableFieldListToConsider[$tableName] = [];
                }
                $this->tableFieldListToConsider[$tableName][] = $fieldName;
            }
        }
        return $result;
    }

    /**
     * Update "<link" tags in RTE fields
     *
     * @param string $tableName Table name
     * @param array $row Given row data
     * @return array Modified row data
     */
    public function updateTableRow(string $tableName, array $row): array
    {
        if (!is_array($this->tableFieldListToConsider)) {
            throw new \RuntimeException(
                'Parent should not call me with a table name I do not consider relevant for update',
                1484173650
            );
        }
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $fieldsToScan = $this->tableFieldListToConsider[$tableName];
        foreach ($fieldsToScan as $fieldName) {
            $row[$fieldName] = $this->transformLinkTagsIfFound(
                $tableName,
                $fieldName,
                $row,
                $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['type'] === 'flex'
            );
        }
        return $row;
    }

    /**
     * Finds all <link> tags and calls the typolink codec service and the link service (twice) to get a string
     * representation of the href part, and then builds an anchor tag.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param array $row
     * @param bool $isFlexformField If true the content is htmlspecialchar()'d and must be treated as such
     * @return mixed the modified content
     */
    protected function transformLinkTagsIfFound(string $tableName, string $fieldName, array $row, bool $isFlexformField)
    {
        $content = $row[$fieldName];
        if (is_string($content)
            && !empty($content)
            && (stripos($content, '<link') !== false || stripos($content, '&lt;link') !== false)
        ) {
            $result = preg_replace_callback(
                $this->regularExpressions[$isFlexformField ? 'flex' : 'default'],
                function ($matches) use ($isFlexformField) {
                    $typoLink = $isFlexformField ? htmlspecialchars_decode($matches['typolink']) : $matches['typolink'];
                    $typoLinkParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($typoLink);
                    $anchorTagAttributes = [
                        'target' => $typoLinkParts['target'],
                        'class' => $typoLinkParts['class'],
                        'title' => $typoLinkParts['title'],
                    ];

                    $link = $typoLinkParts['url'];
                    if (!empty($typoLinkParts['additionalParams'])) {
                        $link .= (strpos($link, '?') === false ? '?' : '&') . ltrim($typoLinkParts['additionalParams'], '&');
                    }

                    try {
                        $linkService = GeneralUtility::makeInstance(LinkService::class);
                        // Ensure the old syntax is converted to the new t3:// syntax, if necessary
                        $linkParts = $linkService->resolve($link);
                        $anchorTagAttributes['href'] = $linkService->asString($linkParts);
                        $newLink = '<a ' . GeneralUtility::implodeAttributes($anchorTagAttributes, true) . '>' .
                            ($isFlexformField ? htmlspecialchars_decode($matches['content']) : $matches['content']) .
                            '</a>';
                        if ($isFlexformField) {
                            $newLink = htmlspecialchars($newLink);
                        }
                    } catch (UnknownLinkHandlerException $e) {
                        $newLink = $matches[0];
                    } catch (UnknownUrnException $e) {
                        $newLink = $matches[0];
                    }

                    return $newLink;
                },
                $content
            );
            if ($result !== null) {
                $content = $result;
            } else {
                $this->logger->error('Converting links failed due to PCRE error', [
                    'table' => $tableName,
                    'field' => $fieldName,
                    'uid' => $row['uid'] ?? null,
                    'errorCode' => preg_last_error()
                ]);
            }
        }
        return $content;
    }
}
