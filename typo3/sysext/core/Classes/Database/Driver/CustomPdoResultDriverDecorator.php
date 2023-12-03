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

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use TYPO3\CMS\Core\Database\Driver\DriverConnection as Typo3PdoDriverConnection;

/**
 * @internal this implementation is not part of TYPO3's Public API.
 */
final class CustomPdoResultDriverDecorator extends AbstractDriverMiddleware
{
    public function connect(#[\SensitiveParameter] array $params): DriverConnection
    {
        return new Typo3PdoDriverConnection(parent::connect($params));
    }
}
