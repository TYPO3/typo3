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

namespace TYPO3\CMS\Form\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class is subjected to change.
 * **Do NOT subclass**
 *
 * Scope: frontend / backend
 * @internal
 */
class DatabaseService
{
    /**
     * Returns an array with all sys_refindex database rows which be
     * connected to a formDefinition identified by $persistenceIdentifier
     *
     * $persistenceIdentifier string can contain:
     * - number     -> interpreted as a sys_file reference UID to a FAL-stored YAML file (user-generated content)
     * - EXT:...    -> interpreted as a NON-FAL extension-based file
     * - any string -> interpreted as FAL-based filename
     *
     * Note that we explicitly do NOT check for file existence here,
     * because we want to be able to reveal sys_refindex entries to files
     * that have been deleted meanwhile!
     *
     * @internal
     */
    public function getReferencesByPersistenceIdentifier(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1472238493);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $constraints = [$queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier'))];

        // Indicator whether the string-based lookup in sys_refindex shall be performed (true; non FAL-based) or not (false; FAL-based)
        $useStringReference = false;

        // Check what $persistenceIdentifier contains.
        if (PathUtility::isExtensionPath($persistenceIdentifier)) {
            // Uses "EXT:" notation, so it cannot be a FAL identifier.
            // We pass the whole "EXT:..." lookup through to the sys_refindex query
            // due to its constraint on softref_key=formPersistenceIdentifier,
            // we expect no false entries even with "weird" string notations. If sys_refindex
            // has it, we yield it.
            $useStringReference = true;
        } else {
            // Anything else would be either a notation like "/fileadmin/something.form.yaml"
            // or a numeric identifier for a sys_file.
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

            try {
                // We use this "bulk method" because this is the best-bet from resourceFactory
                // to resolve both an integer-ish input value or a FAL value. There is no
                // substitute for an "only get a file, not a directory" lookup.
                $file = $resourceFactory->retrieveFileOrFolderObject($persistenceIdentifier);

                if ($file === null) {
                    // The associated identifier could (no longer) be retrieved via FAL.
                    // However, we do want to see existing entries to such stale entries to
                    // be able to reveal bad references, either by its ref_string or ref_uid

                    if (MathUtility::canBeInterpretedAsInteger($persistenceIdentifier)) {
                        $constraints[] = $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($persistenceIdentifier, Connection::PARAM_INT));
                    } else {
                        $useStringReference = true;
                    }
                } elseif ($file instanceof File) {
                    // We succeeded in retrieving the FAL file object.
                    $constraints[] = $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT));
                } else {
                    // We might have retrieved a "Folder" object. Fall back to passthrough
                    // with the intent, to retrieve all possible sys_refindex entries.
                    // If that fails, it's ok to return an empty array.
                    $useStringReference = true;
                }
            } catch (ResourceDoesNotExistException) {
                // This exception gets triggered when $persistenceIdentifier is not something
                // that could be resolved by the bulk-method.
                // As above, we want to retrieve all the possible sys_refindex entries,
                // so we fall back again to "ref_string".
                // This should happen when $persistenceIdentifier is set to a string like '/fileadmin/somefile.form.yaml',
                // and a FAL storage could be retrieved, but not the actual file.
                $useStringReference = true;
            }
        }

        if ($useStringReference) {
            $constraints[] = $queryBuilder->expr()->eq('ref_string', $queryBuilder->createNamedParameter($persistenceIdentifier));
        }

        return $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns an array with all form definition persistenceIdentifiers
     * as keys and their reference counts as values.
     *
     * @internal
     */
    public function getAllReferencesForPersistenceIdentifier(): array
    {
        $items = [];
        foreach ($this->getAllReferences('ref_string') as $item) {
            $items[$item['identifier']] = $item['items'];
        }
        return $items;
    }

    /**
     * Returns an array with all form definition file uids as keys
     * and their reference counts as values.
     *
     * @internal
     */
    public function getAllReferencesForFileUid(): array
    {
        $items = [];
        foreach ($this->getAllReferences('ref_uid') as $item) {
            $items[$item['identifier']] = $item['items'];
        }
        return $items;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getAllReferences(string $column): array
    {
        if ($column !== 'ref_string' && $column !== 'ref_uid') {
            throw new \InvalidArgumentException('$column must not be "ref_string" or "ref_uid".', 1535406600);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');

        $constraints = [
            $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier')),
        ];

        if ($column === 'ref_string') {
            $constraints[] = $queryBuilder->expr()->neq('ref_string', $queryBuilder->createNamedParameter(''));
        } else {
            $constraints[] = $queryBuilder->expr()->gt('ref_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }

        return $queryBuilder
            ->select($column . ' AS identifier')
            ->addSelectLiteral('COUNT(' . $queryBuilder->quoteIdentifier($column) . ') AS ' . $queryBuilder->quoteIdentifier('items'))
            ->from('sys_refindex')
            ->where(...$constraints)
            ->groupBy($column)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
