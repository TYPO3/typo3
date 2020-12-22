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

namespace TYPO3\CMS\Install\Service;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\BootService;

/**
 * @internal This is NOT an API class, it is for internal use in the install tool only.
 */
class LateBootService extends BootService
{
    public function getContainer(bool $allowCaching = false): ContainerInterface
    {
        return parent::getContainer($allowCaching);
    }

    public function loadExtLocalconfDatabaseAndExtTables(bool $resetContainer = true, bool $allowCaching = false): ContainerInterface
    {
        return parent::loadExtLocalconfDatabaseAndExtTables($resetContainer, $allowCaching);
    }
}
