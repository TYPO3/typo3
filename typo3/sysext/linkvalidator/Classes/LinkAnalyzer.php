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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
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
use TYPO3\CMS\Core\Schema\Capability\LabelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\Event\BeforeRecordIsAnalyzedEvent;
use TYPO3\CMS\Linkvalidator\Linktype\LinktypeRegistry;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;

/**
 * This class provides Processing plugin implementation
 * @internal
 */
#[Autoconfigure(public: true)]
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
     * The currently active TSconfig. Will be passed to the init function.
     */
    protected array $tsConfig = [];

    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly BrokenLinkRepository $brokenLinkRepository,
        protected readonly SoftReferenceParserFactory $softReferenceParserFactory,
        protected readonly LinktypeRegistry $linktypeRegistry,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

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

        foreach ($this->linktypeRegistry->getLinktypes() as $identifier => $linktype) {
            if (is_array($tsConfig['linktypesConfig.'][$identifier . '.'] ?? false)) {
                // setAdditionalConfig might use global configuration, so still call it, even if options are empty
                $linktype->setAdditionalConfig($tsConfig['linktypesConfig.'][$identifier . '.']);
            }
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
            if (!$this->tcaSchemaFactory->has($table)) {
                continue;
            }
            $schema = $this->tcaSchemaFactory->get($table);

            $selectFields = ['uid', 'pid'];
            // Only add fields which are defined in the Schema
            foreach ($fields as $field) {
                if ($schema->hasField($field)) {
                    $selectFields[] = $field;
                }
            }

            if ($schema->hasCapability(TcaSchemaCapability::Label)) {
                /** @var LabelCapability $labelCapability */
                $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
                if ($labelCapability->hasPrimaryField()) {
                    $selectFields[] = $labelCapability->getPrimaryField()->getName();
                }
                foreach ($labelCapability->getAdditionalFields() as $additionalField) {
                    $selectFields[] = $additionalField->getName();
                }
            }

            if ($schema->isLanguageAware()) {
                $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
                $selectFields[] = $languageCapability->getLanguageField()->getName();
            }

            if ($schema->getSubSchemaDivisorField() !== null) {
                $selectFields[] = $schema->getSubSchemaDivisorField()->getName();
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
     * @param array<int,string> $linkTypes
     */
    protected function checkLinks(array $links, array $linkTypes)
    {
        foreach ($this->linktypeRegistry->getLinktypes() as $key => $linkType) {
            if (!is_array($links[$key] ?? false) || (!in_array($key, $linkTypes, true))) {
                continue;
            }

            // Check them
            foreach ($links[$key] as $entryValue) {
                $table = $entryValue['table'];
                if (!$this->tcaSchemaFactory->has($table)) {
                    continue;
                }
                $schema = $this->tcaSchemaFactory->get($table);

                $record = [];
                $record['headline'] = BackendUtility::getRecordTitle($table, $entryValue['row']);
                $record['record_pid'] = $entryValue['row']['pid'];
                $record['record_uid'] = $entryValue['uid'];
                $record['table_name'] = $table;
                $record['link_type'] = $key;
                $record['link_title'] = $entryValue['link_title'] ?? '';
                $record['field'] = $entryValue['field'];
                $record['last_check'] = time();
                $typeField = $schema->getSubSchemaDivisorField()?->getName() ?? false;
                if ($typeField && isset($entryValue['row'][$typeField])) {
                    $record['element_type'] = (string)$entryValue['row'][$typeField];
                }
                $languageFieldName = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName() : false;
                if ($languageFieldName && isset($entryValue['row'][$languageFieldName])) {
                    $record['language'] = $entryValue['row'][$languageFieldName];
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
                $checkUrl = $linkType->checkLink($url, $entryValue, $this);

                // Broken link found
                if (!$checkUrl) {
                    $this->brokenLinkRepository->addBrokenLink($record, false, $linkType->getErrorParams() ?: []);
                    $this->brokenLinkCounts[$table]++;
                }
            }
        }
    }

    /**
     * Recheck for broken links for one field in table for record.
     *
     * @param string|int $recordUid uid of record to check
     * @param int $timestamp - only recheck if timestamp changed
     */
    public function recheckLinks(
        array $checkOptions,
        string|int $recordUid,
        string $table,
        string $field,
        int $timestamp,
        bool $considerHidden = true
    ): void {
        // If table is not configured, assume the extension is not installed
        // and therefore no need to check it
        if (!$this->tcaSchemaFactory->has($table)) {
            return;
        }

        // get all links for $record / $table / $field combination
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        if ($considerHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $schema = $this->tcaSchemaFactory->get($table);

        $selectFields = ['uid', 'pid', $field];
        if ($schema->hasCapability(TcaSchemaCapability::Label)) {
            /** @var LabelCapability $labelCapability */
            $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
            if ($labelCapability->hasPrimaryField()) {
                $selectFields[] = $labelCapability->getPrimaryField()->getName();
            }
            foreach ($labelCapability->getAdditionalFields() as $additionalField) {
                $selectFields[] = $additionalField->getName();
            }
        }

        $updatedFieldName = null;
        if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $updatedFieldName = $schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName();
            $selectFields[] = $updatedFieldName;
        }

        $row = $queryBuilder
            ->select(...$selectFields)
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
            $this->brokenLinkRepository->removeBrokenLinksForRecord($table, (int)$recordUid);
            return;
        }
        if ($updatedFieldName && $row[$updatedFieldName] && $timestamp && ((int)($row[$updatedFieldName]) < $timestamp)) {
            // timestamp has not changed: no need to recheck
            return;
        }
        $resultsLinks = [];
        $this->analyzeRecord($resultsLinks, $table, [$field], $row);
        if ($resultsLinks) {
            // remove existing broken links from table
            $this->brokenLinkRepository->removeBrokenLinksForRecord($table, (int)$recordUid);
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

        $schema = $this->tcaSchemaFactory->get($table);
        // Put together content of all relevant fields
        $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
        $idRecord = $record['uid'];
        // Get all references
        foreach ($fields as $field) {
            if (!$schema->hasField($field)) {
                continue;
            }
            $fieldInformation = $schema->getField($field);
            $conf = $fieldInformation->getConfiguration();
            $valueField = $record[$field];

            // @todo: check for 'type' => 'file' as well and update in documentation?

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

            foreach ($this->linktypeRegistry->getLinktypes() as $keyArr => $linkType) {
                $type = $linkType->fetchType($reference, $type, $keyArr);
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
    protected function analyzeTypoLinks(SoftReferenceParserResult $parserResult, array &$results, HtmlParser $htmlParser, array $record, string $field, string $table)
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
            foreach ($this->linktypeRegistry->getLinktypes() as $keyArr => $linkType) {
                $type = $linkType->fetchType($currentR, $type, $keyArr);
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
    public function getLinkCounts(): array
    {
        return $this->brokenLinkRepository->getNumberOfBrokenLinksForRecordsOnPages($this->pids, $this->searchFields);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
