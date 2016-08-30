<?php
namespace TYPO3\CMS\Backend\Form\Utility;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This is a static, internal and intermediate helper class for various
 * FormEngine related tasks.
 *
 * This class was introduced to help disentangling FormEngine and
 * its sub classes. It MUST NOT be used in other extensions and will
 * change or vanish without further notice.
 *
 * @internal
 * @todo: These helpers are target to be dropped if further FormEngine refactoring is done
 */
class FormEngineUtility
{
    /**
     * Whitelist that allows TCA field configuration to be overridden by TSconfig
     *
     * @see overrideFieldConf()
     * @var array
     */
    protected static $allowOverrideMatrix = [
        'input' => ['size', 'max', 'readOnly'],
        'text' => ['cols', 'rows', 'wrap', 'readOnly'],
        'check' => ['cols', 'showIfRTE', 'readOnly'],
        'select' => ['size', 'autoSizeMax', 'maxitems', 'minitems', 'readOnly', 'treeConfig'],
        'group' => ['size', 'autoSizeMax', 'max_size', 'show_thumbs', 'maxitems', 'minitems', 'disable_controls', 'readOnly'],
        'inline' => ['appearance', 'behaviour', 'foreign_label', 'foreign_selector', 'foreign_unique', 'maxitems', 'minitems', 'size', 'autoSizeMax', 'symmetric_label', 'readOnly'],
        'imageManipulation' => ['ratios']
    ];

    /**
     * Overrides the TCA field configuration by TSconfig settings.
     *
     * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
     * This overrides the setting in $GLOBALS['TCA'][<table>]['columns'][<field>]['config']['appearance']['useSortable'].
     *
     * @param array $fieldConfig $GLOBALS['TCA'] field configuration
     * @param array $TSconfig TSconfig
     * @return array Changed TCA field configuration
     * @internal
     */
    public static function overrideFieldConf($fieldConfig, $TSconfig)
    {
        if (is_array($TSconfig)) {
            $TSconfig = GeneralUtility::removeDotsFromTS($TSconfig);
            $type = $fieldConfig['type'];
            if (is_array($TSconfig['config']) && is_array(static::$allowOverrideMatrix[$type])) {
                // Check if the keys in TSconfig['config'] are allowed to override TCA field config:
                foreach ($TSconfig['config'] as $key => $_) {
                    if (!in_array($key, static::$allowOverrideMatrix[$type], true)) {
                        unset($TSconfig['config'][$key]);
                    }
                }
                // Override $GLOBALS['TCA'] field config by remaining TSconfig['config']:
                if (!empty($TSconfig['config'])) {
                    ArrayUtility::mergeRecursiveWithOverrule($fieldConfig, $TSconfig['config']);
                }
            }
        }
        return $fieldConfig;
    }

    /**
     * Returns TSconfig for given table and row
     *
     * @param string $table The table name
     * @param array $row The table row - Must at least contain the "uid" value, even if "NEW..." string.
     *                   The "pid" field is important as well, negative values will be interpreted as pointing to a record from the same table.
     * @param string $field Optionally specify the field name as well. In that case the TSconfig for this field is returned.
     * @return mixed The TSconfig values - probably in an array
     * @internal
     */
    public static function getTSconfigForTableRow($table, $row, $field = '')
    {
        static $cache;
        if (is_null($cache)) {
            $cache = [];
        }
        $cacheIdentifier = $table . ':' . $row['uid'];
        if (!isset($cache[$cacheIdentifier])) {
            $cache[$cacheIdentifier] = BackendUtility::getTCEFORM_TSconfig($table, $row);
        }
        if ($field) {
            return $cache[$cacheIdentifier][$field];
        }
        return $cache[$cacheIdentifier];
    }

    /**
     * Renders the $icon, supports a filename for skinImg or sprite-icon-name
     *
     * @param string $icon The icon passed, could be a file-reference or a sprite Icon name
     * @param string $alt Alt attribute of the icon returned
     * @param string $title Title attribute of the icon return
     * @return string A tag representing to show the asked icon
     * @internal
     */
    public static function getIconHtml($icon, $alt = '', $title = '')
    {
        $icon = (string)$icon;
        $iconFile = '';
        $iconInfo = false;

        if (StringUtility::beginsWith($icon, 'EXT:')) {
            $absoluteFilePath = GeneralUtility::getFileAbsFileName($icon);
            if (!empty($absoluteFilePath) && is_file($absoluteFilePath)) {
                $iconFile = '../' . PathUtility::stripPathSitePrefix($absoluteFilePath);
                $iconInfo = (StringUtility::endsWith($absoluteFilePath, '.svg'))
                    ? true
                    : getimagesize($absoluteFilePath);
            }
        } elseif (StringUtility::beginsWith($icon, '../')) {
            // @TODO: this is special modList, files from folders and selicon
            $iconFile = GeneralUtility::resolveBackPath($icon);
            if (is_file(PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3)))) {
                $iconInfo = (StringUtility::endsWith($icon, '.svg'))
                    ? true
                    : getimagesize((PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3))));
            }
        }

        if ($iconInfo !== false && is_file(GeneralUtility::resolveBackPath(PATH_typo3 . $iconFile))) {
            return '<img'
                . ' src="' . htmlspecialchars($iconFile) . '"'
                . ' alt="' . htmlspecialchars($alt) . '" '
                . ($title ? 'title="' . htmlspecialchars($title) . '"' : '')
            . ' />';
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return '<span alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">'
            . $iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render()
            . '</span>';
    }

    /**
     * Update expanded/collapsed states on new inline records if any.
     *
     * @param array $uc The uc array to be processed and saved (by reference)
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tce Instance of FormEngine that saved data before
     * @return void
     * @internal
     */
    public static function updateInlineView(&$uc, $tce)
    {
        $backendUser = static::getBackendUserAuthentication();
        if (isset($uc['inlineView']) && is_array($uc['inlineView'])) {
            $inlineView = (array)unserialize($backendUser->uc['inlineView']);
            foreach ($uc['inlineView'] as $topTable => $topRecords) {
                foreach ($topRecords as $topUid => $childElements) {
                    foreach ($childElements as $childTable => $childRecords) {
                        $uids = array_keys($tce->substNEWwithIDs_table, $childTable);
                        if (!empty($uids)) {
                            $newExpandedChildren = [];
                            foreach ($childRecords as $childUid => $state) {
                                if ($state && in_array($childUid, $uids)) {
                                    $newChildUid = $tce->substNEWwithIDs[$childUid];
                                    $newExpandedChildren[] = $newChildUid;
                                }
                            }
                            // Add new expanded child records to UC (if any):
                            if (!empty($newExpandedChildren)) {
                                $inlineViewCurrent = &$inlineView[$topTable][$topUid][$childTable];
                                if (is_array($inlineViewCurrent)) {
                                    $inlineViewCurrent = array_unique(array_merge($inlineViewCurrent, $newExpandedChildren));
                                } else {
                                    $inlineViewCurrent = $newExpandedChildren;
                                }
                            }
                        }
                    }
                }
            }
            $backendUser->uc['inlineView'] = serialize($inlineView);
            $backendUser->writeUC();
        }
    }

    /**
     * Compatibility layer for methods not in FormEngine scope.
     *
     * databaseRow was a flat array with single elements in select and group fields as comma separated list.
     * With new data handling in FormEngine, this is now an array of element values. There are however "old"
     * methods that still expect the flat array.
     * This method implodes the array again to fake the old behavior of a database row before it is given
     * to those methods.
     *
     * @param array $row Incoming array
     * @return array Flat array
     * @internal
     */
    public static function databaseRowCompatibility(array $row)
    {
        $newRow = [];
        foreach ($row as $fieldName => $fieldValue) {
            if (!is_array($fieldValue)) {
                $newRow[$fieldName] = $fieldValue;
            } else {
                $newElementValue = [];
                foreach ($fieldValue as $itemNumber => $itemValue) {
                    if (is_array($itemValue) && array_key_exists(1, $itemValue)) {
                        $newElementValue[] = $itemValue[1];
                    } else {
                        $newElementValue[] = $itemValue;
                    }
                }
                $newRow[$fieldName] = implode(',', $newElementValue);
            }
        }
        return $newRow;
    }

    /**
     * @return LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected static function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
