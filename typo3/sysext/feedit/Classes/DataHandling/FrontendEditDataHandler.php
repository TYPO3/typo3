<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Feedit\DataHandling;

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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * Calls DataHandler and stores data
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:feedit and not part of TYPO3's Core API.
 */
class FrontendEditDataHandler
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var FrontendBackendUserAuthentication
     */
    protected $user;

    /**
     * FrontendEditDataHandler constructor.
     * @param array $configuration
     * @param FrontendBackendUserAuthentication|null $user
     */
    public function __construct(array $configuration, FrontendBackendUserAuthentication $user = null)
    {
        $this->user = $user ?: $GLOBALS['BE_USER'];
        $this->configuration = $configuration;
    }

    /**
     * Management of the on-page frontend editing forms and edit panels.
     * Basically taking in the data and commands and passes them on to the proper classes as they should be.
     *
     * @throws \UnexpectedValueException if configuration[cmd] is not a valid command
     */
    public function editAction()
    {
        // Commands
        list($table, $uid) = explode(':', $this->configuration['record']);
        $uid = (int)$uid;
        $cmd = $this->configuration['cmd'];
        // Look for some configuration data that indicates we should save.
        if (($this->configuration['doSave'] || $this->configuration['update'] || $this->configuration['update_close']) && is_array($this->configuration['data'])) {
            $cmd = 'save';
        }
        if ($cmd === 'save' || $cmd && $table && $uid && isset($GLOBALS['TCA'][$table])) {
            // Perform the requested editing command.
            $cmdAction = 'do' . ucwords($cmd);
            if (method_exists($this, $cmdAction)) {
                call_user_func_array([$this, $cmdAction], [$table, $uid]);
            } else {
                throw new \UnexpectedValueException('The specified frontend edit command (' . $cmd . ') is not valid.', 1225818110);
            }
        }
    }

    /**
     * Hides a specific record.
     *
     * @param string $table The table name for the record to hide.
     * @param int $uid The UID for the record to hide.
     */
    protected function doHide(string $table, int $uid)
    {
        $hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
        if ($hideField) {
            $recData = [];
            $recData[$table][$uid][$hideField] = 1;
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($recData, []);
            $dataHandler->process_datamap();
        }
    }

    /**
     * Unhides (shows) a specific record.
     *
     * @param string $table The table name for the record to unhide.
     * @param int $uid The UID for the record to unhide.
     */
    protected function doUnhide(string $table, int $uid)
    {
        $hideField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
        if ($hideField) {
            $recData = [];
            $recData[$table][$uid][$hideField] = 0;
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($recData, []);
            $dataHandler->process_datamap();
        }
    }

    /**
     * Moves a record up.
     *
     * @param string $table The table name for the record to move.
     * @param int $uid The UID for the record to hide.
     */
    protected function doUp(string $table, int $uid)
    {
        $this->move($table, $uid, 'up');
    }

    /**
     * Moves a record down.
     *
     * @param string $table The table name for the record to move.
     * @param int $uid The UID for the record to move.
     */
    protected function doDown(string $table, int $uid)
    {
        $this->move($table, $uid, 'down');
    }

    /**
     * Moves a record after a given element. Used for drag.
     *
     * @param string $table The table name for the record to move.
     * @param int $uid The UID for the record to move.
     */
    protected function doMoveAfter(string $table, int $uid)
    {
        $afterUID = (int)$this->configuration['moveAfter'];
        $this->move($table, $uid, '', $afterUID);
    }

    /**
     * Moves a record
     *
     * @param string $table The table name for the record to move.
     * @param int $uid The UID for the record to move.
     * @param string $direction The direction to move, either 'up' or 'down'.
     * @param int $afterUID The UID of record to move after. This is specified for dragging only.
     */
    protected function move(string $table, int $uid, string $direction = '', int $afterUID = 0)
    {
        $dataHandlerCommands = [];
        $sortField = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
        if ($sortField) {
            // Get the current record
            // Only fetch uid, pid and the fields that are necessary to detect the sorting factors
            if (isset($GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'])) {
                $copyAfterDuplicateFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['copyAfterDuplFields'], true);
            } else {
                $copyAfterDuplicateFields = [];
            }

            $fields = $copyAfterDuplicateFields;
            $fields[] = 'uid';
            $fields[] = 'pid';
            $fields[] = $sortField;
            $fields = array_unique($fields);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();

            $currentRecord = $queryBuilder
                ->select(...$fields)
                ->from($table)
                ->where($queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ))
                ->execute()
                ->fetch();

            if (is_array($currentRecord)) {
                // Fetch the record before or after the current one
                // to define the data handler commands
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);

                $queryBuilder
                    ->select('uid', 'pid')
                    ->from($table)
                    ->where($queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($currentRecord['pid'], \PDO::PARAM_INT)
                    ))
                    ->setMaxResults(2);

                // Disable the default restrictions (but not all) if the admin panel is in preview mode
                if ($this->user->adminPanel instanceof AdminPanelView && $this->user->adminPanel->extGetFeAdminValue('preview')) {
                    $queryBuilder->getRestrictions()
                        ->removeByType(StartTimeRestriction::class)
                        ->removeByType(EndTimeRestriction::class)
                        ->removeByType(HiddenRestriction::class)
                        ->removeByType(FrontendGroupRestriction::class);
                }

                if (!empty($copyAfterDuplicateFields)) {
                    foreach ($copyAfterDuplicateFields as $fieldName) {
                        $queryBuilder->andWhere($queryBuilder->expr()->eq(
                            $fieldName,
                            $queryBuilder->createNamedParameter($currentRecord[$fieldName], \PDO::PARAM_STR)
                        ));
                    }
                }
                if (!empty($direction)) {
                    if ($direction === 'up') {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->lt(
                                $sortField,
                                $queryBuilder->createNamedParameter($currentRecord[$sortField], \PDO::PARAM_INT)
                            )
                        );
                        $queryBuilder->orderBy($sortField, 'DESC');
                    } else {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->gt(
                                $sortField,
                                $queryBuilder->createNamedParameter($currentRecord[$sortField], \PDO::PARAM_INT)
                            )
                        );
                        $queryBuilder->orderBy($sortField, 'ASC');
                    }
                }

                $result = $queryBuilder->execute();
                if ($recordBefore = $result->fetch()) {
                    if ($afterUID) {
                        $dataHandlerCommands[$table][$uid]['move'] = -$afterUID;
                    } elseif ($direction === 'down') {
                        $dataHandlerCommands[$table][$uid]['move'] = -$recordBefore['uid'];
                    } elseif ($recordAfter = $result->fetch()) {
                        // Must take the second record above...
                        $dataHandlerCommands[$table][$uid]['move'] = -$recordAfter['uid'];
                    } else {
                        // ... and if that does not exist, use pid
                        $dataHandlerCommands[$table][$uid]['move'] = $currentRecord['pid'];
                    }
                } elseif ($direction === 'up') {
                    $dataHandlerCommands[$table][$uid]['move'] = $currentRecord['pid'];
                }
            }

            // If any data handler commands were set, execute the data handler command
            if (!empty($dataHandlerCommands)) {
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $dataHandlerCommands);
                $dataHandler->process_cmdmap();
            }
        }
    }

    /**
     * Deletes a specific record.
     *
     * @param string $table The table name for the record to delete.
     * @param int $uid The UID for the record to delete.
     */
    protected function doDelete(string $table, int $uid)
    {
        $cmdData[$table][$uid]['delete'] = 1;
        if (!empty($cmdData)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $cmdData);
            $dataHandler->process_cmdmap();
        }
    }

    /**
     * Saves a record based on its data array.
     *
     * @param string $table The table name for the record to save.
     * @param int $uid The UID for the record to save.
     */
    protected function doSave(string $table, int $uid)
    {
        $data = $this->configuration['data'];
        if (!empty($data)) {
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($data, []);
            $dataHandler->process_uploads($_FILES);
            $dataHandler->process_datamap();
            // Save the new UID back into configuration
            $newUID = $dataHandler->substNEWwithIDs['NEW'];
            if ($newUID) {
                $this->configuration['newUID'] = $newUID;
            }
        }
    }

    /**
     * Saves a record based on its data array and closes it.
     * Note: This method is only a wrapper for doSave() but is needed so
     *
     * @param string $table The table name for the record to save.
     * @param int $uid The UID for the record to save.
     */
    protected function doSaveAndClose(string $table, int $uid)
    {
        $this->doSave($table, $uid);
    }

    /**
     * Stub for closing a record. No real functionality needed since content
     * element rendering will take care of everything.
     *
     * @param string $table The table name for the record to close.
     * @param int $uid The UID for the record to close.
     */
    protected function doClose(string $table, int $uid)
    {
    }
}
