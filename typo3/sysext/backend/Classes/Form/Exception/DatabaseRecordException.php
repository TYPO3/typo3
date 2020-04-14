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

namespace TYPO3\CMS\Backend\Form\Exception;

use TYPO3\CMS\Backend\Form\Exception;

/**
 * A record could not be fetched from database, maybe it vanished meanwhile.
 */
class DatabaseRecordException extends Exception
{
    /**
     * @var string Table name
     */
    protected $tableName;

    /**
     * @var int Table row uid
     */
    protected $uid;

    /**
     * Constructor overwrites default constructor.
     *
     * @param string $message Human readable error message
     * @param int $code Exception code timestamp
     * @param \Exception $previousException Possible exception from database layer
     * @param string $tableName Table name query was working on
     * @param int $uid Table row uid
     */
    public function __construct($message, $code, \Exception $previousException = null, $tableName, $uid)
    {
        parent::__construct($message, $code, $previousException);
        $this->tableName = $tableName;
        $this->uid = $uid;
    }

    /**
     * Return table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Return row uid
     *
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }
}
