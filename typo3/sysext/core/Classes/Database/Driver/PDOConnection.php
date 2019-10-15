<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Driver;

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

use Doctrine\DBAL\Driver\PDOConnection as DoctrineDbalPDOConnection;
use Doctrine\DBAL\Driver\PDOException;
use PDO;

/**
 * This is a full "clone" of the class of package doctrine/dbal. Scope is to use the PDOConnection of TYPO3.
 * All private methods have to be checked on every release of doctrine/dbal.
 */
class PDOConnection extends DoctrineDbalPDOConnection
{
    /**
     * {@inheritdoc}
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        try {
            parent::__construct($dsn, $user, $password, $options);
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatement::class, []]);
        } catch (\PDOException $exception) {
            throw new PDOException($exception);
        }
    }
}
