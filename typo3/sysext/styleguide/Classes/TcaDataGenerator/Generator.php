<?php
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Manage a page tree with all test / demo styleguide data
 */
class Generator
{
    /**
     * @return void
     */
    public function create()
    {
        $newIdOfEntryPage = StringUtility::getUniqueId('NEW');
        $data = [
            'pages' => [
                $newIdOfEntryPage => [
                    'title' => 'styleguide TCA demo',
                    'pid' => 0 - $this->getUidOfLastTopLevelPage(),
                    // mark this page as entry point
                    'tx_styleguide_containsdemo' => 'tx_styleguide',
                    // have the "globus" icon for this page
                    'is_siteroot' => 1,
                ],
            ],
        ];
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();
        BackendUtility::setUpdateSignal('updatePageTree');
    }

    /**
     * @return void
     */
    public function delete()
    {
        $topUids = $this->getUidsOfStyleguideEntryPages();
        if (empty($topUids)) {
            return;
        }
        $command = [];
        foreach ($topUids as $topUid) {
            $command['pages'][(int)$topUid]['delete'] = 1;
        }
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->deleteTree = true;
        $dataHandler->start([], $command);
        $dataHandler->process_cmdmap();
        BackendUtility::setUpdateSignal('updatePageTree');
    }

    /**
     * Returns the uid of the last "top level" page (has pid 0)
     * in the page tree. This is either a positive integer or 0
     * if no page exists in the page tree at all.
     *
     * @return int
     */
    protected function getUidOfLastTopLevelPage(): int
    {
        $database = $this->getDatabase();
        $lastPage = $database->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            'pid = 0' . BackendUtility::deleteClause('pages'),
            '',
            'sorting DESC'
        );
        $uid = 0;
        if (is_array($lastPage) && count($lastPage) === 1) {
            $uid = (int)$lastPage['uid'];
        }
        return $uid;
    }

    /**
     * Returns a uid list of existing styleguide demo top level pages.
     * These are pages with pid=0 and tx_styleguide_containsdemo set to 'tx_styleguide'
     *
     * @return array
     */
    protected function getUidsOfStyleguideEntryPages(): array
    {
        $database = $this->getDatabase();
        $rows = $database->exec_SELECTgetRows(
            'uid',
            'pages',
            'pid = 0'
                . ' AND tx_styleguide_containsdemo=' . $database->fullQuoteStr('tx_styleguide', 'pages')
                . BackendUtility::deleteClause('pages')
        );
        $uids = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $uids[] = (int)$row['uid'];
            }
        }
        return $uids;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
