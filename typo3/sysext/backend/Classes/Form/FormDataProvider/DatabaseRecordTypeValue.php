<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Determine the final TCA type value
 */
class DatabaseRecordTypeValue implements FormDataProviderInterface
{
    /**
     * TCA type value depends on several parameters. The simple case is
     * a direct lookup in the database row, which then just needs handling
     * in case the row is a localization overlay.
     * More complex is the field:field syntax that can look up the actual
     * value in a different table.
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if (!isset($result['processedTca']['types'])
            || !is_array($result['processedTca']['types'])
            || empty($result['processedTca']['types'])
        ) {
            throw new \UnexpectedValueException(
                'At least one "types" array must be defined for table ' . $result['tableName'] . ', preferred "0"',
                1438185331
            );
        }

        // Guard clause to suppress any calculation if record type value has been set from outside already
        if ($result['recordTypeValue'] !== '') {
            return $result;
        }

        $recordTypeValue = '0';
        if (!empty($result['processedTca']['ctrl']['type'])) {
            $tcaTypeField = $result['processedTca']['ctrl']['type'];

            if (strpos($tcaTypeField, ':') === false) {
                // $tcaTypeField is the name of a field in database row
                if (!array_key_exists($tcaTypeField, $result['databaseRow'])) {
                    throw new \UnexpectedValueException(
                        'TCA table ' . $result['tableName'] . ' ctrl[\'type\'] is set to ' . $tcaTypeField . ', but'
                        . ' this field does not exist in the database of this table',
                        1438183881
                    );
                }
                $recordTypeValue = $result['databaseRow'][$tcaTypeField];
            } else {
                // If type is configured as localField:foreignField, fetch the type value from
                // a foreign table. localField then point to a group or select field in the own table,
                // this points to a record in a foreign table and the value of foreignField is then
                // used as type field. This was introduced for some FAL scenarios.
                list($pointerField, $foreignTableTypeField) = explode(':', $tcaTypeField);

                $relationType = $result['processedTca']['columns'][$pointerField]['config']['type'];
                if ($relationType !== 'select' && $relationType !== 'group') {
                    throw new \UnexpectedValueException(
                        'TCA foreign field pointer fields are only allowed to be used with group or select field types.'
                        . ' Handling field ' . $pointerField . ' with type configured as ' . $tcaTypeField,
                        1325862241
                    );
                }

                $foreignUid = $result['databaseRow'][$pointerField];
                // Resolve the foreign record only if there is a uid, otherwise fall back 0
                if (!empty($foreignUid)) {
                    // Determine table name to fetch record from
                    if ($relationType === 'select') {
                        $foreignTable = $result['processedTca']['columns'][$pointerField]['config']['foreign_table'] ?? '';
                    } else {
                        $allowedTables = explode(',', $result['processedTca']['columns'][$pointerField]['config']['allowed']);
                        // Always take the first configured table.
                        $foreignTable = $allowedTables[0];
                    }
                    if (empty($foreignTable)) {
                        throw new \UnexpectedValueException(
                            'No target table defined for type config field ' . $pointerField . ' of table ' . $result['tableName'],
                            1438253614
                        );
                    }
                    if (!MathUtility::canBeInterpretedAsInteger($foreignUid) && is_array($foreignUid[0])) {
                        // A group relation - has been resolved to array by TcaGroup data provider already
                        $foreignUid = $foreignUid[0]['uid'];
                    }
                    // Fetch field of this foreign row from db
                    $foreignRow = $this->getDatabaseRow($foreignTable, $foreignUid, $foreignTableTypeField);
                    if ($foreignRow[$foreignTableTypeField]) {
                        // @todo: It might be necessary to fetch the value from default language record as well here,
                        // @todo: this was buggy in the "old" implementation and never worked. It was therefor left out here for now.
                        // @todo: To implement that, see if the foreign row is a localized overlay, fetch default and merge exclude
                        $recordTypeValue = $foreignRow[$foreignTableTypeField];
                    }
                }
            }
        }

        // Throw another exception if determined value and '0' and '1' do not exist
        if (empty($result['processedTca']['types'][$recordTypeValue])
            && empty($result['processedTca']['types']['0'])
            && empty($result['processedTca']['types']['1'])
        ) {
            throw new \UnexpectedValueException(
                'Type value ' . $recordTypeValue . ' from database record not defined in TCA of table '
                . $result['tableName'] . ' and neither 0 nor 1 are defined as fallback.',
                1438185437
            );
        }

        // Check the determined value actually exists as types key, otherwise fall back to 0 or 1, 1 for "historical reasons"
        if (empty($result['processedTca']['types'][$recordTypeValue])) {
            $recordTypeValue = !empty($result['processedTca']['types']['0']) ? '0' : '1';
        }

        $result['recordTypeValue'] = (string)$recordTypeValue;
        return $result;
    }

    /**
     * Retrieve the requested row from the database
     *
     * @param string $tableName
     * @param int $uid
     * @param string $fieldName
     * @return array
     */
    protected function getDatabaseRow(string $tableName, int $uid, string $fieldName): array
    {
        $row = BackendUtility::getRecord($tableName, $uid, $fieldName);

        return $row ?: [];
    }
}
