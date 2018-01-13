<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Updates\RowUpdater;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Migrate values for database records having columns
 * using "l10n_mode" set to "mergeIfNotBlank".
 */
class ImageCropUpdater implements RowUpdaterInterface
{
    /**
     * List of tables with information about to migrate fields.
     * Created during hasPotentialUpdateForTable(), used in updateTableRow()
     *
     * @var array
     */
    protected $payload = [];

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Migrate values in sys_file_reference crop field';
    }

    /**
     * Return true if a table needs modifications.
     *
     * @param string $tableName Table name to check
     * @return bool True if this table has fields to migrate
     */
    public function hasPotentialUpdateForTable(string $tableName): bool
    {
        $result = false;
        $payload = $this->getPayloadForTable($tableName);
        if (count($payload) !== 0) {
            $this->payload[$tableName] = $payload;
            $result = true;
        }
        return $result;
    }

    /**
     * Update single row if needed
     *
     * @param string $tableName
     * @param array $inputRow Given row data
     * @return array Modified row data
     */
    public function updateTableRow(string $tableName, array $inputRow): array
    {
        $tablePayload = $this->payload[$tableName];

        foreach ($tablePayload['fields'] as $field) {
            if (strpos($inputRow[$field], '{"x":') === 0) {
                $cropArray = json_decode($inputRow[$field], true);
                if (is_array($cropArray)) {
                    $file = $this->getFile($inputRow, $tablePayload['fileReferenceField'] ?: 'uid_local');
                    if (null === $file) {
                        continue;
                    }

                    $cropArea = Area::createFromConfiguration(json_decode($inputRow[$field], true));
                    $cropVariantCollectionConfig = [
                        'default' => [
                            'cropArea' => $cropArea->makeRelativeBasedOnFile($file)->asArray(),
                        ]
                    ];
                    $inputRow[$field] = json_encode($cropVariantCollectionConfig);
                }
            }
        }

        return $inputRow;
    }

    /**
     * Retrieves field names grouped per table name having "l10n_mode" set
     * to a relevant value that shall be migrated in database records.
     *
     * Resulting array is structured like this:
     * + fields: [field a, field b, ...]
     * + sources
     *   + source uid: [localization uid, localization uid, ...]
     *
     * @param string $tableName Table name
     * @return array Payload information for this table
     * @throws \RuntimeException
     */
    protected function getPayloadForTable(string $tableName): array
    {
        if (!is_array($GLOBALS['TCA'][$tableName])) {
            throw new \RuntimeException(
                'Globals TCA of given table name must exist',
                1485386982
            );
        }
        $tableDefinition = $GLOBALS['TCA'][$tableName];

        if (
            empty($tableDefinition['columns'])
            || !is_array($tableDefinition['columns'])
        ) {
            return [];
        }

        $fields = [];
        $fileReferenceField = null;
        foreach ($tableDefinition['columns'] as $fieldName => $fieldConfiguration) {
            if (
                !empty($fieldConfiguration['config']['type'])
                && $fieldConfiguration['config']['type'] === 'group'
                && !empty($fieldConfiguration['config']['internal_type'])
                && $fieldConfiguration['config']['internal_type'] === 'db'
                && !empty($fieldConfiguration['config']['allowed'])
                && $fieldConfiguration['config']['allowed'] === 'sys_file'
            ) {
                $fileReferenceField = $fieldName;
            }
            if (
                !empty($fieldConfiguration['config']['type'])
                && $fieldConfiguration['config']['type'] === 'imageManipulation'
            ) {
                $fields[] = $fieldName;
            }
        }

        if (empty($fields)) {
            return [];
        }

        $payload = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($fields as $fieldName) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();

            $query = $queryBuilder
                ->from($tableName)
                ->count($fieldName)
                ->where(
                    $queryBuilder->expr()->like(
                        $fieldName,
                        $queryBuilder->createNamedParameter('{"x":%', \PDO::PARAM_STR)
                    )
                );
            if ((int)$query->execute()->fetchColumn(0) > 0) {
                $payload['fields'][] = $fieldName;
                if (isset($fileReferenceField)) {
                    $payload['fileReferenceField'] = $fileReferenceField;
                } else {
                    $payload['fileReferenceField'] = null;
                }
            }
        }
        return $payload;
    }

    /**
     * Get file object
     *
     * @param array $row
     * @param string $fieldName
     * @return \TYPO3\CMS\Core\Resource\File|null
     */
    private function getFile(array $row, $fieldName)
    {
        $file = null;
        $fileUid = !empty($row[$fieldName]) ? $row[$fieldName] : null;
        if (is_array($fileUid) && isset($fileUid[0]['uid'])) {
            $fileUid = $fileUid[0]['uid'];
        }
        if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
            try {
                $file = ResourceFactory::getInstance()->getFileObject((int)$fileUid);
            } catch (FileDoesNotExistException $e) {
            } catch (\InvalidArgumentException $e) {
            }
        }
        return $file;
    }
}
