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

use Doctrine\DBAL\Driver\Result;
use PDO;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class Connection extends \Doctrine\DBAL\Driver\PDO\Connection
{
    /**
     * @internal The connection can be only instantiated by its driver.
     *
     * {@inheritdoc}
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [Statement::class, []]);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($name = null)
    {
        if ($name === null) {
            return parent::lastInsertId($name);
        }

        $stmt = $this->prepare('SELECT CONVERT(VARCHAR(MAX), current_value) FROM sys.sequences WHERE name = ?');
        $stmt->execute([$name]);

        if ($stmt instanceof Result) {
            return $stmt->fetchOne();
        }

        return $stmt->fetchColumn();
    }
}
