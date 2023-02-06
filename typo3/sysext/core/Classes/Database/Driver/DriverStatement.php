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

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;

/**
 * TYPO3's custom Statement decorator object for Database statements based on Doctrine DBAL in TYPO3's drivers.
 *
 * This is a low-level wrapper around PDOStatement for TYPO3 based drivers to ensure the PDOStatement is put into
 * TYPO3's DriverResult object, and not in Doctrine's Result object. If Doctrine DBAL had a factory
 * for DriverResults this class could be removed.
 *
 * @internal this implementation is not part of TYPO3's Public API.
 */
class DriverStatement extends AbstractStatementMiddleware
{
    /**
     * {@inheritdoc}
     */
    public function execute(): ResultInterface
    {
        return new DriverResult(parent::execute());
    }
}
