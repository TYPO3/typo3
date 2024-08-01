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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=select fields.
 * Special field for 'foreign_table' of table typeforeign
 *
 * @internal
 */
final class TypeSelectRenderTypeSingleForeignTableForType extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldName' => 'foreign_table',
        'fieldConfig' => [
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_type',
            ],
        ],
    ];

    public function __construct(private readonly ConnectionPool $connectionPool) {}

    public function generate(array $data): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_styleguide_type');
        return (int)$queryBuilder
            ->select('uid')
            ->from('tx_styleguide_type')
            ->executeQuery()
            ->fetchOne();
    }
}
