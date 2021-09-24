<?php

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

namespace TYPO3\CMS\Backend\Form\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
        'text' => ['cols', 'rows', 'wrap', 'max', 'readOnly'],
        'check' => ['cols', 'readOnly'],
        'select' => ['size', 'autoSizeMax', 'maxitems', 'minitems', 'readOnly', 'treeConfig', 'fileFolderConfig'],
        'category' => ['size', 'maxitems', 'minitems', 'readOnly', 'treeConfig'],
        'group' => ['size', 'autoSizeMax', 'max_size', 'maxitems', 'minitems', 'readOnly'],
        'inline' => ['appearance', 'behaviour', 'foreign_label', 'foreign_selector', 'foreign_unique', 'maxitems', 'minitems', 'size', 'autoSizeMax', 'symmetric_label', 'readOnly'],
        'imageManipulation' => ['ratios', 'cropVariants'],
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
            $type = $fieldConfig['type'] ?? '';
            if (isset($TSconfig['config']) && is_array($TSconfig['config']) && is_array(static::$allowOverrideMatrix[$type])) {
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
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $cache = $runtimeCache->get('formEngineUtilityTsConfigForTableRow') ?: [];
        $cacheIdentifier = $table . ':' . $row['uid'];
        if (!isset($cache[$cacheIdentifier])) {
            $cache[$cacheIdentifier] = BackendUtility::getTCEFORM_TSconfig($table, $row);
            $runtimeCache->set('formEngineUtilityTsConfigForTableRow', $cache);
        }
        if ($field && isset($cache[$cacheIdentifier][$field])) {
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
        $absoluteFilePath = GeneralUtility::getFileAbsFileName($icon);
        if (!empty($absoluteFilePath) && is_file($absoluteFilePath)) {
            return '<img'
                . ' loading="lazy" '
                . ' src="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($absoluteFilePath)) . '"'
                . ' alt="' . htmlspecialchars($alt) . '" '
                . ($title ? 'title="' . htmlspecialchars($title) . '"' : '')
                . ' />';
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return '<span title="' . htmlspecialchars($title) . '">'
            . $iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render()
            . '</span>';
    }
}
