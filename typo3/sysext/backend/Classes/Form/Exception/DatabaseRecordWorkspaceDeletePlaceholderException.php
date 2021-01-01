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

namespace TYPO3\CMS\Backend\Form\Exception;

use TYPO3\CMS\Backend\Form\Exception;

/**
 * Thrown if a workspace delete placeholder is being edited, which is not allowed.
 */
class DatabaseRecordWorkspaceDeletePlaceholderException extends Exception
{
    /**
     * @var string Table name
     */
    protected string $tableName;

    /**
     * @var int Table row uid
     */
    protected int $uid;

    /**
     * Constructor overwrites default constructor.
     *
     * @param string $message Human readable error message
     * @param int $code Exception code timestamp
     * @param string $tableName Table name query was working on
     * @param int $uid Table row uid
     */
    public function __construct(string $message, int $code, string $tableName, int $uid)
    {
        parent::__construct($message, $code);
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
