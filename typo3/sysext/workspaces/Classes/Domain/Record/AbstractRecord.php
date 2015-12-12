<?php
namespace TYPO3\CMS\Workspaces\Domain\Record;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * Combined record class
 */
abstract class AbstractRecord
{
    /**
     * @var array
     */
    protected $record;

    protected static function fetch($tableName, $uid)
    {
        $record = static::getDatabaseConnection()->exec_SELECTgetSingleRow('*', $tableName, 'deleted=0 AND uid=' . (int)$uid);
        if (empty($record)) {
            throw new \RuntimeException('Record "' . $tableName . ': ' . $uid . '" not found');
        }
        return $record;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected static function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param array $record
     */
    public function __construct(array $record)
    {
        $this->record = $record;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUid();
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return (int)$this->record['uid'];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string)$this->record['title'];
    }

    /**
     * @return StagesService
     */
    protected function getStagesService()
    {
        return GeneralUtility::makeInstance(StagesService::class);
    }
}
