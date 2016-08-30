<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This class provides Check Link Handler plugin implementation
 */
class LinkHandler extends AbstractLinktype
{
    /**
     * @var string
     */
    const DELETED = 'deleted';

    /**
     * @var string
     */
    const DISABLED = 'disabled';

    /**
     * Checks a given URL for validity
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of that url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     */
    public function checkLink($url, $softRefEntry, $reference)
    {
        $response = true;
        $errorType = '';
        $errorParams = [];
        $parts = explode(':', $url);
        if (count($parts) !== 3) {
            return $response;
        }

        list(, $tableName, $rowid) = $parts;
        $rowid = (int)$rowid;

        $row = null;
        $tsConfig = $reference->getTSConfig();
        $reportHiddenRecords = (bool)$tsConfig['linkhandler.']['reportHiddenRecords'];

        // First check, if we find a non disabled record if the check
        // for hidden records is enabled.
        if ($reportHiddenRecords) {
            $row = $this->getRecordRow($tableName, $rowid, 'disabled');
            if ($row === null) {
                $response = false;
                $errorType = self::DISABLED;
            }
        }

        // If no enabled record was found or we did not check that see
        // if we can find a non deleted record.
        if ($row === null) {
            $row = $this->getRecordRow($tableName, $rowid, 'deleted');
            if ($row === null) {
                $response = false;
                $errorType = self::DELETED;
            }
        }

        // If we did not find a non deleted record, check if we find a
        // deleted one.
        if ($row === null) {
            $row = $this->getRecordRow($tableName, $rowid, 'all');
            if ($row === null) {
                $response = false;
                $errorType = '';
            }
        }

        if (!$response) {
            $errorParams['errorType'] = $errorType;
            $errorParams['tablename'] = $tableName;
            $errorParams['uid'] = $rowid;
            $this->setErrorParams($errorParams);
        }

        return $response;
    }

    /**
     * Type fetching method, based on the type that softRefParserObj returns
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string fetched type
     */
    public function fetchType($value, $type, $key)
    {
        if ($value['type'] === 'string' && StringUtility::beginsWith(strtolower($value['tokenValue']), 'record:')) {
            $type = 'linkhandler';
        }
        return $type;
    }

    /**
     * Generate the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams)
    {
        $errorType = $errorParams['errorType'];
        $tableName = $errorParams['tablename'];
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['title'])) {
            $title = $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']);
        } else {
            $title = $tableName;
        }
        switch ($errorType) {
            case self::DISABLED:
                $response = $this->getTranslatedErrorMessage('list.report.rownotvisible', $errorParams['uid'], $title);
                break;
            case self::DELETED:
                $response = $this->getTranslatedErrorMessage('list.report.rowdeleted', $errorParams['uid'], $title);
                break;
            default:
                $response = $this->getTranslatedErrorMessage('list.report.rownotexisting', $errorParams['uid']);
        }
        return $response;
    }

    /**
     * Fetches the translation with the given key and replaces the ###uid### and ###title### markers
     *
     * @param string $translationKey
     * @param int $uid
     * @param string $title
     * @return string
     */
    protected function getTranslatedErrorMessage($translationKey, $uid, $title = null)
    {
        $message = $this->getLanguageService()->getLL($translationKey);
        $message = str_replace('###uid###', (int)$uid, $message);
        if (isset($title)) {
            $message = str_replace('###title###', htmlspecialchars($title), $message);
        }
        return $message;
    }

    /**
     * Fetches the record with the given UID from the given table.
     *
     * The filter option accepts two values:
     *
     * "disabled" will filter out disabled and deleted records.
     * "deleted" filters out deleted records but will return disabled records.
     * If nothing is specified all records will be returned (including deleted).
     *
     * @param string $tableName The name of the table from which the record should be fetched.
     * @param int $uid The UID of the record that should be fetched.
     * @param string $filter A filter setting, can be empty or "disabled" or "deleted".
     * @return array|NULL The result row as associative array or NULL if nothing is found.
     */
    protected function getRecordRow($tableName, $uid, $filter = '')
    {
        $whereStatement = 'uid = ' . (int)$uid;

        switch ($filter) {
            case self::DISABLED:
                $whereStatement .= BackendUtility::BEenableFields($tableName) . BackendUtility::deleteClause($tableName);
                break;
            case self::DELETED:
                $whereStatement .= BackendUtility::deleteClause($tableName);
                break;
        }

        $row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            $tableName,
            $whereStatement
        );

        // Since exec_SELECTgetSingleRow can return NULL or FALSE we
        // make sure we always return NULL if no row was found.
        if ($row === false) {
            $row = null;
        }

        return $row;
    }
}
