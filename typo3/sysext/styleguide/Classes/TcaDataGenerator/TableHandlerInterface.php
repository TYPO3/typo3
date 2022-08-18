<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

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

/**
 * Interface for table handlers
 */
interface TableHandlerInterface
{
    /**
     * Return true if this table handler can handle given table name
     *
     * @param string $tableName Given table name
     * @return bool
     */
    public function match(string $tableName): bool;

    /**
     * Handle data for a given table
     *
     * @param string $tableName
     */
    public function handle(string $tableName): void;
}
