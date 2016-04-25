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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * On "new" command, initialize new database row with default data
 */
class DatabaseRowInitializeNew implements FormDataProviderInterface
{
    /**
     * Initialize new row with default values from various sources
     * There are 4 sources of default values. Mind the order, the last takes precedence.
     *
     * @param array $result
     * @return array
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        if ($result['command'] !== 'new') {
            return $result;
        }
        if (!is_array($result['databaseRow'])) {
            throw new \UnexpectedValueException(
                'databaseRow of table ' . $result['tableName'] . ' is not an array',
                1444431128
            );
        }

        $result = $this->setDefaultsFromUserTsConfig($result);
        $result = $this->setDefaultsFromPageTsConfig($result);
        $result = $this->setDefaultsFromNeighborRow($result);
        $result = $this->setDefaultsFromDevVals($result);
        $result = $this->setDefaultsFromInlineRelations($result);
        $result = $this->setPid($result);

        return $result;
    }

    /**
     * Set defaults defined by user ts "TCAdefaults"
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setDefaultsFromUserTsConfig(array $result)
    {
        $tableNameWithDot = $result['tableName'] . '.';
        // Apply default values from user typo script "TCAdefaults" if any
        if (isset($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot])
            && is_array($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot])
        ) {
            foreach ($result['userTsConfig']['TCAdefaults.'][$tableNameWithDot] as $fieldName => $fieldValue) {
                if (isset($result['processedTca']['columns'][$fieldName])) {
                    $result['databaseRow'][$fieldName] = $fieldValue;
                }
            }
        }
        return $result;
    }

    /**
     * Set defaults defined by page ts "TCAdefaults"
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setDefaultsFromPageTsConfig(array $result)
    {
        $tableNameWithDot = $result['tableName'] . '.';
        if (isset($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot])
            && is_array($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot])
        ) {
            foreach ($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot] as $fieldName => $fieldValue) {
                if (isset($result['processedTca']['columns'][$fieldName])) {
                    $result['databaseRow'][$fieldName] = $fieldValue;
                }
            }
        }
        return $result;
    }

    /**
     * If a neighbor row is given (if vanillaUid was negative), field can be initialized with values
     * from neighbor for fields registered in TCA['ctrl']['useColumnsForDefaultValues'].
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setDefaultsFromNeighborRow(array $result)
    {
        if (is_array($result['neighborRow'])
            && !empty($result['processedTca']['ctrl']['useColumnsForDefaultValues'])
        ) {
            $defaultColumns = GeneralUtility::trimExplode(',', $result['processedTca']['ctrl']['useColumnsForDefaultValues'], true);
            foreach ($defaultColumns as $fieldName) {
                if (isset($result['processedTca']['columns'][$fieldName])
                    && isset($result['neighborRow'][$fieldName])
                ) {
                    $result['databaseRow'][$fieldName] = $result['neighborRow'][$fieldName];
                }
            }
        }
        return $result;
    }

    /**
     * Apply default values from GET / POST
     *
     * @todo: Fetch this stuff from request object as soon as modules were moved to PSR-7,
     * @todo: or hand values over via $result array, so the _GP access is transferred to
     * @todo: controllers concern.
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setDefaultsFromDevVals(array $result)
    {
        $tableName = $result['tableName'];
        $defaultValuesFromGetPost = GeneralUtility::_GP('defVals');
        if (isset($defaultValuesFromGetPost[$tableName])
            && is_array($defaultValuesFromGetPost[$tableName])
        ) {
            foreach ($defaultValuesFromGetPost[$tableName] as $fieldName => $fieldValue) {
                if (isset($result['processedTca']['columns'][$fieldName])) {
                    $result['databaseRow'][$fieldName] = $fieldValue;
                }
            }
        }
        return $result;
    }

    /**
     * Inline scenario if a new intermediate record to an existing child-child is
     * compiled. Set "foreign_selector" field of this intermediate row to given
     * "childChildUid". See TcaDataCompiler array comment of inlineChildChildUid
     * for more details.
     *
     * @param array $result Result array
     * @return array Modified result array
     * @throws \UnexpectedValueException
     */
    protected function setDefaultsFromInlineRelations(array $result)
    {
        if ($result['inlineChildChildUid'] === null) {
            return $result;
        }
        if (!is_int($result['inlineChildChildUid'])) {
            throw new \UnexpectedValueException(
                'An inlineChildChildUid is given for table ' . $result['tableName'] . ', but is not an integer',
                1444434103
            );
        }
        if (!isset($result['inlineParentConfig']['foreign_selector'])) {
            throw new \UnexpectedValueException(
                'An inlineChildChildUid is given for table ' . $result['tableName'] . ', but no foreign_selector in inlineParentConfig',
                1444434102
            );
        }
        $selectorFieldName = $result['inlineParentConfig']['foreign_selector'];
        if (!isset($result['processedTca']['columns'][$selectorFieldName]['config']['type'])
            || ($result['processedTca']['columns'][$selectorFieldName]['config']['type'] !== 'select'
                && $result['processedTca']['columns'][$selectorFieldName]['config']['type'] !== 'group'
            )
        ) {
            throw new \UnexpectedValueException(
                $selectorFieldName . ' is target type of a foreign_selector field to table ' . $result['tableName'] . ' and must be either a select or group type field',
                1444434104
            );
        }

        if ($result['inlineChildChildUid']) {
            $result['databaseRow'][$selectorFieldName] = $result['inlineChildChildUid'];
        }

        return $result;
    }

    /**
     * Set the pid. This is either the vanillaUid (see description in FormDataCompiler),
     * or a pid given by pageTsConfig for inline children.
     *
     * @param array $result Result array
     * @return array Modified result array
     * @throws \UnexpectedValueException
     */
    protected function setPid(array $result)
    {
        // Set pid to vanillaUid. This can be a negative value
        // if the record is added relative to another record.
        $result['databaseRow']['pid'] = $result['vanillaUid'];

        // In case a new inline record is created, the pid can be set to a different value
        // by pageTsConfig, but not by userTsConfig. This overrides the above pid selection
        // and forces the pid of new inline children.
        $tableNameWithDot = $result['tableName'] . '.';
        if ($result['isInlineChild'] && isset($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot]['pid'])) {
            if (!MathUtility::canBeInterpretedAsInteger($result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot]['pid'])) {
                throw new \UnexpectedValueException(
                    'page TSConfig setting TCAdefaults.' . $tableNameWithDot . 'pid must be a number, but given string '
                    . $result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot]['pid'] . ' can not be interpreted as integer',
                    1461598332
                );
            }
            $result['databaseRow']['pid'] = (int)$result['pageTsConfig']['TCAdefaults.'][$tableNameWithDot]['pid'];
        }

        return $result;
    }
}
