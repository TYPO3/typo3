<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures;

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

/**
 * Disable getRecordWSOL and getRecordTitle dependency by returning stable results
 */
class ProcessedValueForGroupWithMultipleAllowedTablesFixture extends \TYPO3\CMS\Backend\Utility\BackendUtility
{
    /**
     * Get record WSOL
     */
    public static function getRecordWSOL($table, $uid, $fields = '*', $where = '', $useDeleteClause = true, $unsetMovePointers = false)
    {
        static $called = 0;
        ++$called;
        if ($called === 1) {
            return ['title' => 'Page 1'];
        }
        if ($called === 2) {
            return ['header' => 'Configuration 2'];
        }
    }

    /**
     * Get record title
     */
    public static function getRecordTitle($table, $row, $prep = false, $forceResult = true)
    {
        static $called = 0;
        ++$called;
        if ($called === 1) {
            return 'Page 1';
        }
        if ($called === 2) {
            return 'Configuration 2';
        }
    }
}
