<?php
namespace TYPO3\CMS\Install\Database;

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
 * A "mock" to suppress database calls on $GLOBALS['TYPO3_DB'].
 * Used in TestSetup install tool action to prevent caching in \TYPO3\CMS\Core\Imaging\GraphicalFunctions
 */
class DatabaseConnectionMock
{
    /**
     * Get single row mock
     *
     * @return NULL
     */
    public function exec_SELECTgetSingleRow()
    {
        return null;
    }

    /**
     * Insert row mock
     *
     * @return bool TRUE
     */
    public function exec_INSERTquery()
    {
        return true;
    }

    /**
     * Quote string mock
     *
     * @param string $string
     * @return string
     */
    public function fullQuoteStr($string)
    {
        return $string;
    }

    /**
     * Error mock
     *
     * @return string Empty string
     */
    public function sql_error()
    {
        return '';
    }
}
