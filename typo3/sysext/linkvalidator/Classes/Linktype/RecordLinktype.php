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

namespace TYPO3\CMS\Linkvalidator\Linktype;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * This class checks links to database records using the record link handler, for example:
 *
 * - t3://record?identifier=tx_news&uid=99999
 *
 * The table of the record is taken from the result of the configured soft reference parsers,
 * which resolve the link identifier via the "TCEMAIN.linkHandler" page TSconfig.
 *
 * Only the database record is checked. It is neither verified that a public URL can be
 * generated for it, for example via a detail page, nor that this URL can be fetched.
 *
 * @internal
 */
final class RecordLinktype extends AbstractLinktype
{
    public const DELETED = 'deleted';
    public const HIDDEN = 'hidden';
    public const NOTEXISTING = 'notExisting';
    public const TABLENOTEXISTING = 'tableNotExisting';

    protected string $identifier = 'record';

    public function __construct(
        private readonly TcaSchemaFactory $tcaSchemaFactory,
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Type fetching method, based on the type that softRefParserObj returns.
     *
     * We support record link targets of the format t3://record?identifier=<identifier>&uid=<uid>
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string Fetched type
     */
    public function fetchType(array $value, string $type, string $key): string
    {
        if (($value['type'] ?? false) === 'db'
            && str_contains(strtolower((string)($value['tokenValue'] ?? '')), 't3://record?')
        ) {
            $type = $this->getIdentifier();
        }
        return $type;
    }

    /**
     * Checks a given record link. The soft reference entry typically contains:
     *
     * - type: record
     * - recordRef: <table>:<uid> (the identifier of the link can differ from the table)
     * - tokenValue: t3://record?identifier=<identifier>&uid=<uid>
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of that url
     * @param LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     */
    public function checkLink(string $url, array $softRefEntry, LinkAnalyzer $reference): bool
    {
        $this->errorParams = [];

        if (($softRefEntry['substr']['type'] ?? '') !== $this->getIdentifier()) {
            return true;
        }
        [$table, $uid] = explode(':', (string)($softRefEntry['substr']['recordRef'] ?? ''), 2) + ['', ''];
        if ($table === '') {
            return true;
        }
        $reportHiddenRecords = (bool)($reference->getTSConfig()['reportHiddenRecords'] ?? true);

        return $this->checkRecord($table, (int)$uid, $reportHiddenRecords);
    }

    /**
     * Checks that the record exists, is not deleted and (optionally) is visible.
     */
    private function checkRecord(string $table, int $uid, bool $reportHiddenRecords): bool
    {
        if (!$this->tcaSchemaFactory->has($table)) {
            // The table falls back to the link identifier, if no link handler configuration resolves it
            return $this->setError(self::TABLENOTEXISTING, $uid, '', $table);
        }
        $schema = $this->tcaSchemaFactory->get($table);
        $deletedField = $this->getFieldName($schema, TcaSchemaCapability::SoftDelete);
        $hiddenField = $this->getFieldName($schema, TcaSchemaCapability::RestrictionDisabledField);
        $starttimeField = $this->getFieldName($schema, TcaSchemaCapability::RestrictionStartTime);
        $endtimeField = $this->getFieldName($schema, TcaSchemaCapability::RestrictionEndTime);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        // All fields are fetched, as the record title may depend on the record type,
        // "label_alt" or a label generator
        $row = $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if (!$row) {
            return $this->setError(self::NOTEXISTING, $uid);
        }

        if ($deletedField !== '' && (int)$row[$deletedField] !== 0) {
            return $this->setError(self::DELETED, $uid, BackendUtility::getRecordTitle($table, $row));
        }
        if ($reportHiddenRecords && $this->isHidden($row, $hiddenField, $starttimeField, $endtimeField)) {
            return $this->setError(self::HIDDEN, $uid, BackendUtility::getRecordTitle($table, $row));
        }
        return true;
    }

    private function isHidden(array $row, string $hiddenField, string $starttimeField, string $endtimeField): bool
    {
        $now = $GLOBALS['EXEC_TIME'];
        return ($hiddenField !== '' && (int)$row[$hiddenField] !== 0)
            || ($starttimeField !== '' && (int)$row[$starttimeField] > $now)
            || ($endtimeField !== '' && (int)$row[$endtimeField] !== 0 && (int)$row[$endtimeField] <= $now);
    }

    private function getFieldName(TcaSchema $schema, TcaSchemaCapability $capability): string
    {
        return $schema->hasCapability($capability) ? $schema->getCapability($capability)->getFieldName() : '';
    }

    private function setError(string $errorType, int $uid, string $title = '', string $table = ''): false
    {
        $this->errorParams = ['errorType' => $errorType, 'uid' => $uid, 'title' => $title, 'table' => $table];
        return false;
    }

    /**
     * Generates the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage(array $errorParams): string
    {
        $lang = $this->getLanguageService();
        $label = match ($errorParams['errorType'] ?? '') {
            self::DELETED => 'list.report.contentdeleted',
            self::HIDDEN => 'list.report.contentnotvisible',
            self::NOTEXISTING => 'list.report.contentnotexisting',
            self::TABLENOTEXISTING => 'list.report.recordtablenotexisting',
            default => 'list.report.noinformation',
        };
        return str_replace(
            ['###title###', '###uid###', '###table###'],
            [(string)($errorParams['title'] ?? ''), (string)($errorParams['uid'] ?? ''), (string)($errorParams['table'] ?? '')],
            $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf:' . $label)
        );
    }
}
