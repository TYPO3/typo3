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

namespace TYPO3\CMS\Linkvalidator;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserResult;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\Event\BeforeRecordIsAnalyzedEvent;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;

/**
 * This class provides Processing plugin implementation
 * @internal
 */
class LinkAnalyzer
{
    /**
     * Array of tables and fields to search for broken links
     *
     * @var array<string, array<int, string>>
     */
    protected array $searchFields = [];

    /**
     * List of page uids (rootline downwards)
     *
     * @var int[]
     */
    protected array $pids = [];

    /**
     * Array of tables and the number of external links they contain
     */
    protected array $linkCounts = [];

    /**
     * Array of tables and the number of broken external links they contain
     */
    protected array $brokenLinkCounts = [];

    /**
     * Array for hooks for own checks
     *
     * @var LinktypeInterface[]
     */
    protected array $hookObjectsArr = [];

    /**
     * The currently active TSconfig. Will be passed to the init function.
     *
     * @var array
     */
    protected $tsConfig = [];

    protected EventDispatcherInterface $eventDispatcher;
    protected BrokenLinkRepository $brokenLinkRepository;
    protected SoftReferenceParserFactory $softReferenceParserFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        BrokenLinkRepository $brokenLinkRepository,
        SoftReferenceParserFactory $softReferenceParserFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->brokenLinkRepository = $brokenLinkRepository;
        $this->softReferenceParserFactory = $softReferenceParserFactory;
        $this->getLanguageService()->includeLLFile('EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf');
    }

    /**
     * Store all the needed configuration values in class variables
     *
     * @param array $searchFields List of fields in which to search for links
     * @param int[] $pidList List of page uids in which to search for links
     * @param array $tsConfig The currently active TSconfig.
     */
    public function init(array $searchFields, array $pidList, $tsConfig)
    {
        $this->searchFields = $searchFields;
        $this->pids = $pidList;
        $this->tsConfig = $tsConfig;

        // Hook to handle own checks
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $key => $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof LinktypeInterface) {
                continue;
            }
            $this->hookObjectsArr[$key] = $hookObject;
            $options = $tsConfig['linktypesConfig.'][$key . '.'] ?? [];
            // setAdditionalConfig might use global configuration, so still call it, even if options are empty
            $this->hookObjectsArr[$key]->setAdditionalConfig($options);
        }
    }

    /**
     * Find all supported broken links and store them in tx_linkvalidator_link
     *
     * @param array<int,string> $linkTypes List of hook object to activate
     * @param bool $considerHidden Defines whether to look into hidden fields
     */
    public function getLinkStatistics(array $linkTypes = [], $considerHidden = false)
    {
        if (empty($linkTypes) || empty($this->pids)) {
            return;
        }

        $this->brokenLinkRepository->removeAllBrokenLinksOfRecordsOnPageIds(
            $this->pids,
            $linkTypes
        );

        // Traverse all configured tables
        foreach ($this->searchFields as $table => $fields) {
            // If table is not configured, assume the extension is not installed
            // and therefore no need to check it
            if (!is_array($GLOBALS['TCA'][$table] ?? null)) {
                continue;
            }

            // Re-init selectFields for table
            $selectFields = array_merge(['uid', 'pid', $GLOBALS['TCA'][$table]['ctrl']['label']], $fields);
            if ($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false) {
                $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['type'] ?? false) {
                if (isset($GLOBALS['TCA'][$table]['columns'][$GLOBALS['TCA'][$table]['ctrl']['type']])) {
                    $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['type'];
                }
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);

            if ($considerHidden) {
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            }
            $queryBuilder->select(...$selectFields)->from($table);

            // We need to do the work in chunks, as it may be quite huge and would hit the one
            // or other limit depending on the used dbms - and we also avoid placeholder usage
            // as they are hard to calculate beforehand because of some magic handling of dbal.
            $maxChunk = PlatformInformation::getMaxBindParameters(
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($table)
                    ->getDatabasePlatform()
            );
            foreach (array_chunk($this->pids, (int)floor($maxChunk / 2)) as $pageIdsChunk) {
                $statement = clone $queryBuilder;
                $statement->where(
                    $statement->expr()->in(
                        ($table === 'pages' ? 'uid' : 'pid'),
                        $statement->quoteArrayBasedValueListToIntegerList($pageIdsChunk)
                    )
                );
                $result = $statement->executeQuery();

                // @todo #64091: only select rows that have content in at least one of the relevant fields (via OR)
                while ($row = $result->fetchAssociative()) {
                    $results = [];
                    $this->analyzeRecord($results, $table, $fields, $row);
                    $this->checkLinks($results, $linkTypes);
                }
            }
        }
    }

    /**
     * @param array $links
     * @param array<int,string> $linkTypes
     */
    protected function checkLinks(array $links, array $linkTypes)
    {
        foreach ($this->hookObjectsArr as $key => $hookObj) {
            if (!is_array($links[$key] ?? false) || (!in_array($key, $linkTypes, true))) {
                continue;
            }

            //  Check them
            foreach ($links[$key] as $entryValue) {
                $table = $entryValue['table'];
                $record = [];
                $record['headline'] = BackendUtility::getRecordTitle($table, $entryValue['row']);
                $record['record_pid'] = $entryValue['row']['pid'];
                $record['record_uid'] = $entryValue['uid'];
                $record['table_name'] = $table;
                $record['link_type'] = $key;
                $record['link_title'] = $entryValue['link_title'] ?? '';
                $record['field'] = $entryValue['field'];
                $record['last_check'] = time();
                $typeField = $GLOBALS['TCA'][$table]['ctrl']['type'] ?? false;
                if (isset($entryValue['row'][$typeField])) {
                    $record['element_type'] = (string)$entryValue['row'][$typeField];
                }
                $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false;
                if ($languageField && isset($entryValue['row'][$languageField])) {
                    $record['language'] = $entryValue['row'][$languageField];
                } else {
                    $record['language'] = -1;
                }
                if (!empty($entryValue['pageAndAnchor'] ?? '')) {
                    // Page with anchor, e.g. 18#1580
                    $url = $entryValue['pageAndAnchor'];
                } else {
                    $url = $entryValue['substr']['tokenValue'];
                }
                $record['url'] = $url;

                if (!($this->linkCounts[$table] ?? false)) {
                    $this->linkCounts[$table] = 0;
                }

                if (!($this->brokenLinkCounts[$table] ?? false)) {
                    $this->brokenLinkCounts[$table] = 0;
                }

                $this->linkCounts[$table]++;
                $checkUrl = $hookObj->checkLink($url, $entryValue, $this);

                // Broken link found
                if (!$checkUrl) {
                    $this->brokenLinkRepository->addBrokenLink($record, false, $hookObj->getErrorParams());
                    $this->brokenLinkCounts[$table]++;
                }
            }
        }
    }

    /**
     * Recheck for broken links for one field in table for record.
     *
     * @param array $checkOptions
     * @param string $recordUid uid of record to check
     * @param string $table
     * @param string $field
     * @param int $timestamp - only recheck if timestamp changed
     * @param bool $considerHidden
     */
    public function recheckLinks(
        array $checkOptions,
        string $recordUid,
        string $table,
        string $field,
        int $timestamp,
        bool $considerHidden = true
    ): void {
        // If table is not configured, assume the extension is not installed
        // and therefore no need to check it
        if (!is_array($GLOBALS['TCA'][$table])) {
            return;
        }

        // get all links for $record / $table / $field combination
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        if ($considerHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $row = $queryBuilder->select('uid', 'pid', $GLOBALS['TCA'][$table]['ctrl']['label'], $field, 'tstamp')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if (!$row) {
            // missing record: remove existing links
            $this->brokenLinkRepository->removeBrokenLinksForRecord($table, $recordUid);
            return;
        }
        if (($row['tstamp'] ?? 0) && $timestamp && ((int)($row['tstamp']) < $timestamp)) {
            // timestamp has not changed: no need to recheck
            return;
        }
        $resultsLinks = [];
        $this->analyzeRecord($resultsLinks, $table, [$field], $row);
        if ($resultsLinks) {
            // remove existing broken links from table
            $this->brokenLinkRepository->removeBrokenLinksForRecord($table, $recordUid);
            // find all broken links for list of links
            $this->checkLinks($resultsLinks, $checkOptions);
        }
    }

    /**
     * Find all supported broken links for a specific record
     *
     * @param array $results Array of broken links
     * @param string $table Table name of the record
     * @param array $fields Array of fields to analyze
     * @param array $record Record to analyze
     */
    public function analyzeRecord(array &$results, $table, array $fields, array $record)
    {
        $event = new BeforeRecordIsAnalyzedEvent($table, $record, $fields, $this, $results);
        $this->eventDispatcher->dispatch($event);
        $results = $event->getResults();
        $record = $event->getRecord();

        // Put together content of all relevant fields
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $idRecord = $record['uid'];
        // Get all references
        foreach ($fields as $field) {
            $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
            $valueField = $record[$field];

            // Check if a TCA configured field has soft references defined (see TYPO3 Core API document)
            if (!($conf['softref'] ?? false) || (string)$valueField === '') {
                continue;
            }
            // Traverse soft references
            // set subst such that findRef will return substitutes for urls, emails etc
            $softRefParams = ['subst'];
            foreach ($this->softReferenceParserFactory->getParsersBySoftRefParserList($conf['softref'], $softRefParams) as $softReferenceParser) {
                $parserResult = $softReferenceParser->parse($table, $field, $idRecord, $valueField);
                if (!$parserResult->hasMatched()) {
                    continue;
                }

                if ($softReferenceParser->getParserKey() === 'typolink_tag') {
                    $this->analyzeTypoLinks($parserResult, $results, $htmlParser, $record, $field, $table);
                } else {
                    $this->analyzeLinks($parserResult, $results, $record, $field, $table);
                }
            }
        }
    }

    /**
     * Find all supported broken links for a specific link list
     *
     * @param SoftReferenceParserResult $parserResult findRef parsed records
     * @param array $results Array of broken links
     * @param array $record UID of the current record
     * @param string $field The current field
     * @param string $table The current table
     */
    protected function analyzeLinks(SoftReferenceParserResult $parserResult, array &$results, array $record, $field, $table)
    {
        foreach ($parserResult->getMatchedElements() as $element) {
            $reference = $element['subst'] ?? [];
            $type = '';
            $idRecord = $record['uid'];
            if (empty($reference)) {
                continue;
            }

            foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
                $type = $hookObj->fetchType($reference, $type, $keyArr);
                // Store the type that was found
                // This prevents overriding by internal validator
                if (!empty($type)) {
                    $reference['type'] = $type;
                }
            }
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $reference['tokenID']]['substr'] = $reference;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $reference['tokenID']]['row'] = $record;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $reference['tokenID']]['table'] = $table;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $reference['tokenID']]['field'] = $field;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $reference['tokenID']]['uid'] = $idRecord;
        }
    }

    /**
     * Find all supported broken links for a specific typoLink
     *
     * @param SoftReferenceParserResult $parserResult findRef parsed records
     * @param array $results Array of broken links
     * @param HtmlParser $htmlParser Instance of html parser
     * @param array $record The current record
     * @param string $field The current field
     * @param string $table The current table
     */
    protected function analyzeTypoLinks(SoftReferenceParserResult $parserResult, array &$results, $htmlParser, array $record, $field, $table)
    {
        $linkTags = $htmlParser->splitIntoBlock('a,link', $parserResult->getContent());
        $idRecord = $record['uid'];
        $type = '';
        $title = '';
        $countLinkTags = count($linkTags);
        for ($i = 1; $i < $countLinkTags; $i += 2) {
            $currentR = [];
            $referencedRecordType = '';
            foreach ($parserResult->getMatchedElements() as $element) {
                $type = '';
                $r = $element['subst'];
                if (empty($r['tokenID']) || substr_count($linkTags[$i], $r['tokenID']) === 0) {
                    continue;
                }

                // Type of referenced record
                if (str_contains($r['recordRef'] ?? '', 'pages')) {
                    $currentR = $r;
                    // Contains number of the page
                    $referencedRecordType = $r['tokenValue'];
                    $wasPage = true;
                } elseif (str_contains($r['recordRef'] ?? '', 'tt_content') && (isset($wasPage) && $wasPage === true)) {
                    $referencedRecordType = $referencedRecordType . '#c' . $r['tokenValue'];
                    $wasPage = false;
                } else {
                    $currentR = $r;
                }
                $title = strip_tags($linkTags[$i]);
            }
            // @todo Should be checked why it could be that $currentR stays empty which breaks further processing with
            //       chained PHP array access errors in hooks fetchType() and the $result[] build lines below. Further
            //       $currentR could be overwritten in the inner loop, thus not checking all elements.
            if (empty($currentR)) {
                continue;
            }
            foreach ($this->hookObjectsArr as $keyArr => $hookObj) {
                $type = $hookObj->fetchType($currentR, $type, $keyArr);
                // Store the type that was found
                // This prevents overriding by internal validator
                if (!empty($type)) {
                    $currentR['type'] = $type;
                }
            }
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['substr'] = $currentR;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['row'] = $record;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['table'] = $table;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['field'] = $field;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['uid'] = $idRecord;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['link_title'] = $title;
            $results[$type][$table . ':' . $field . ':' . $idRecord . ':' . $currentR['tokenID']]['pageAndAnchor'] = $referencedRecordType;
        }
    }

    /**
     * Fill a marker array with the number of links found in a list of pages
     *
     * @return array array with the number of links found
     */
    public function getLinkCounts()
    {
        return $this->brokenLinkRepository->getNumberOfBrokenLinksForRecordsOnPages($this->pids, $this->searchFields);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
