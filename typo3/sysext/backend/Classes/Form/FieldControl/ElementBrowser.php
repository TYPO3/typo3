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

namespace TYPO3\CMS\Backend\Form\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the icon "select element via element browser",
 * typically used for type=group.
 */
class ElementBrowser extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $options = $this->data['renderData']['fieldControlOptions'];
        $parameterArray = $this->data['parameterArray'];
        $elementName = $parameterArray['itemFormElName'];
        $config = $parameterArray['fieldConf']['config'];
        $type = $config['type'];

        // Remove any white-spaces from the allowed extension lists
        $allowed = implode(',', GeneralUtility::trimExplode(',', (string)($config['allowed'] ?? ''), true));

        if (isset($config['readOnly']) && $config['readOnly']) {
            return [];
        }

        if ($options['title'] ?? false) {
            $title = $options['title'];
        } elseif ($type === 'group') {
            $title = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_db';
        } elseif ($type === 'folder') {
            $title = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_folder';
        } else {
            // FieldControl requires to provide a title -> Set default if non is given and custom TCA config is used
            $title = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_elements';
        }

        // Check against inline uniqueness - Create some onclick js for delete control and element browser
        // to override record selection in some FAL scenarios - See 'appearance' docs of group element
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $objectPrefix = '';
        if (($this->data['isInlineChild'] ?? false)
            && ($this->data['inlineParentUid'] ?? false)
            && ($this->data['inlineParentConfig']['foreign_table'] ?? false) === $table
            && ($this->data['inlineParentConfig']['foreign_unique'] ?? false) === $fieldName
        ) {
            $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $table;
        }

        if ($type === 'group') {
            if (($this->data['inlineParentConfig']['type'] ?? '') === 'file') {
                $elementBrowserType = 'file';
                // Remove any white-spaces from the allowed extension lists
                $allowed = implode(',', GeneralUtility::trimExplode(',', (string)($this->data['inlineParentConfig']['allowed'] ?? ''), true));
            } else {
                $elementBrowserType = 'db';
            }
        } else {
            $elementBrowserType = 'folder';
        }

        // Initialize link attributes
        $linkAttributes = [
            'class' => 't3js-element-browser',
            'data-mode' => $elementBrowserType,
            'data-params' => $elementName . '|||' . $allowed . '|' . $objectPrefix,
        ];

        // Add the default entry point - if found
        $linkAttributes = $this->addEntryPoint($table, $fieldName, $config, $linkAttributes);

        return [
            'iconIdentifier' => 'actions-insert-record',
            'title' => $title,
            'linkAttributes' => $linkAttributes,
        ];
    }

    /**
     * Try to resolve a configured default entry point - page / folder
     * to be expanded - and add it to the link attributes if found.
     */
    protected function addEntryPoint(string $table, string $fieldName, array $fieldConfig, array $linkAttributes): array
    {
        if (!isset($fieldConfig['elementBrowserEntryPoints']) || !is_array($fieldConfig['elementBrowserEntryPoints'])) {
            // Early return in case no entry points are defined
            return $linkAttributes;
        }

        // Fetch the configured default entry point (which might be a marker)
        $entryPoint = (string)($fieldConfig['elementBrowserEntryPoints']['_default'] ?? '');

        // In case no default entry point is given, check if we deal with type=db and only one allowed table
        if ($entryPoint === '') {
            if ($fieldConfig['type'] === 'folder') {
                // Return for type folder as this requires the "_default" key to be set
                return $linkAttributes;
            }
            // Check for the allowed tables, if only one table is allowed check if an entry point is defined for it
            $allowed = GeneralUtility::trimExplode(',', $fieldConfig['allowed'] ?? '', true);
            if (count($allowed) === 1 && isset($fieldConfig['elementBrowserEntryPoints'][$allowed[0]])) {
                // Use the entry point for the single table as default
                $entryPoint = (string)$fieldConfig['elementBrowserEntryPoints'][$allowed[0]];
            }
            if ($entryPoint === '') {
                // Return if still empty
                return $linkAttributes;
            }
        }

        // Check and resolve possible marker
        if (str_starts_with($entryPoint, '###') && str_ends_with($entryPoint, '###')) {
            if ($entryPoint === '###CURRENT_PID###') {
                // Use the current pid
                $entryPoint = (string)$this->data['effectivePid'];
            } elseif ($entryPoint === '###SITEROOT###' && ($this->data['site'] ?? null) instanceof Site) {
                // Use the root page id from the current site
                $entryPoint = (string)$this->data['site']->getRootPageId();
            } else {
                // Check for special TSconfig marker
                $TSconfig = BackendUtility::getTCEFORM_TSconfig($table, ['pid' => $this->data['effectivePid']]);
                $keyword = substr($entryPoint, 3, -3);
                if (str_starts_with($keyword, 'PAGE_TSCONFIG_')) {
                    $entryPoint = (string)($TSconfig[$fieldName][$keyword] ?? '');
                } else {
                    $entryPoint = (string)($TSconfig['_' . $keyword] ?? '');
                }
            }
        }

        // Add the entry point to the link attribute - if resolved
        if ($entryPoint !== '') {
            $linkAttributes['data-entry-point'] = $entryPoint;
        }

        return $linkAttributes;
    }
}
