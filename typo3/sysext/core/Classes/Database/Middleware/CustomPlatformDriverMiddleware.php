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

namespace TYPO3\CMS\Core\Database\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use TYPO3\CMS\Core\Database\Driver\CustomPlatformDriverDecorator;

/**
 * Wraps the driver to ensure extended *Platform classes are used for connections.
 */
final class CustomPlatformDriverMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new CustomPlatformDriverDecorator($driver);
    }
}
