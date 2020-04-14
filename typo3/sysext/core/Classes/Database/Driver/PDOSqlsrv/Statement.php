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

namespace TYPO3\CMS\Core\Database\Driver\PDOSqlsrv;

use Doctrine\DBAL\ParameterType;
use PDO;
use TYPO3\CMS\Core\Database\Driver\PDOStatement;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class Statement extends PDOStatement
{
    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = ParameterType::STRING, $length = null, $driverOptions = null)
    {
        if (($type === ParameterType::LARGE_OBJECT || $type === ParameterType::BINARY)
            && $driverOptions === null
        ) {
            $driverOptions = PDO::SQLSRV_ENCODING_BINARY;
        }

        return parent::bindParam($column, $variable, $type, $length, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        return $this->bindParam($param, $value, $type);
    }
}
