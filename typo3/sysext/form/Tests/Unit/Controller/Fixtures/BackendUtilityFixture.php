<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Tests\Unit\Controller\Fixtures;

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
 * Fixture for BackendUtility methods
 */
class BackendUtilityFixture
{

    /**
     * @param string $table
     * @param int $uid
     * @param string $fields
     * @param string $where
     * @param bool $useDeleteClause
     * @return array
     */
    public static function getRecord($table, $uid, $fields = '*', $where = '', $useDeleteClause = true)
    {
        return [
            'uid' => 1,
        ];
    }

    /**
     * @param string $table
     * @param array $row
     * @param bool $prep
     * @param bool $forceResult
     * @return string
     */
    public static function getRecordTitle($table, $row, $prep = false, $forceResult = true)
    {
        return 'record title';
    }

    /**
     * @param string $moduleName
     * @param array $urlParameters
     * @return string
     */
    public static function getModuleUrl($moduleName, $urlParameters = [])
    {
        return '/typo3/index.php?some=param';
    }
}
