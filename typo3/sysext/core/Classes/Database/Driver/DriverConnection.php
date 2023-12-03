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

namespace TYPO3\CMS\Core\Database\Driver;

use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;

/**
 * DriverConnection decorator to replace the DriverResult with a custom class directly
 * in the connection and the driver statement class.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class DriverConnection extends AbstractConnectionMiddleware
{
    public function prepare(string $sql): StatementInterface
    {
        return new DriverStatement(parent::prepare($sql));
    }

    public function query(string $sql): ResultInterface
    {
        return new DriverResult(parent::query($sql));
    }
}
