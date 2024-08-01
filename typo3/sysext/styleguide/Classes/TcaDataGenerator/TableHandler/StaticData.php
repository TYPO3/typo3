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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandler;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;
use TYPO3\CMS\Styleguide\TcaDataGenerator\TableHandlerInterface;

/**
 * Generate data for table tx_styleguide_staticdata
 *
 * @internal
 */
final class StaticData extends AbstractTableHandler implements TableHandlerInterface
{
    /**
     * @var string Table name to match
     */
    protected $tableName = 'tx_styleguide_staticdata';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly RecordFinder $recordFinder,
    ) {}

    /**
     * Adds rows
     *
     * @param string $tableName
     */
    public function handle(string $tableName): void
    {
        // tx_styleguide_staticdata is used in other TCA demo fields. We need some default
        // rows to later connect other fields to these rows.
        $pid = $this->recordFinder->findPidOfMainTableRecord('tx_styleguide_staticdata');
        $this->connectionPool
            ->getConnectionForTable('tx_styleguide_staticdata')
            ->bulkInsert(
                'tx_styleguide_staticdata',
                [
                    [ $pid, 'foo' ],
                    [ $pid, 'bar' ],
                    [ $pid, 'foofoo' ],
                    [ $pid, 'foobar' ],
                ],
                [ 'pid', 'value_1'],
            );
    }
}
