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

use Doctrine\DBAL\Driver as DoctrineDriver;
use Doctrine\DBAL\Driver\Middleware as DoctrineDriverMiddleware;
use TYPO3\CMS\Core\Database\Driver\CustomPlatformDriverDecorator;

/**
 * Wraps the driver to ensure extended *Platform classes are used for connections.
 * @internal only and not part of public core API.
 */
final class CustomPlatformDriverMiddleware implements DoctrineDriverMiddleware
{
    public function wrap(DoctrineDriver $driver): DoctrineDriver
    {
        return new CustomPlatformDriverDecorator($driver);
    }
}
