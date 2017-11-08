<?php
declare(strict_types = 1);

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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Special data provider for the sites configuration module.
 *
 * Handle inline children of 'site'
 */
class SiteTcaInline extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    /**
     * Resolve inline fields
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result): array
    {
        $result = $this->addInlineFirstPid($result);
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!$this->isInlineField($fieldConfig)) {
                continue;
            }
            $childTableName = $fieldConfig['config']['foreign_table'] ?? '';
            if (!in_array($childTableName, ['site_errorhandling', 'site_language', 'site_route', 'site_base_variant'], true)) {
                throw new \RuntimeException('Inline relation to other tables not implemented', 1522494737);
            }
            $result['processedTca']['columns'][$fieldName]['children'] = [];
            $result = $this->resolveSiteRelatedChildren($result, $fieldName);
            $result = $this->addForeignSelectorAndUniquePossibleRecords($result, $fieldName);
        }

        return $result;
    }

    /**
     * Is column of type "inline"
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isInlineField(array $fieldConfig): bool
    {
        return !empty($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'inline';
    }

    /**
     * The "entry" pid for inline records. Nested inline records can potentially hang around on different
     * pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes place on the page structure.
     *
     * @param array $result Incoming result
     * @return array Modified result
     * @todo: Find out when and if this is different from 'effectivePid'
     */
    protected function addInlineFirstPid(array $result): array
    {
        if ($result['inlineFirstPid'] === null) {
            $table = $result['tableName'];
            $row = $result['databaseRow'];
            // If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
            if ($table === 'pages') {
                $liveVersionId = BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
                $pid = $liveVersionId ?? $row['uid'];
            } elseif (($row['pid'] ?? 0) < 0) {
                $prevRec = BackendUtility::getRecord($table, abs($row['pid']));
                $pid = $prevRec['pid'];
            } else {
                $pid = $row['pid'] ?? 0;
            }
            if (MathUtility::canBeInterpretedAsInteger($pid)) {
                $pageRecord = BackendUtility::getRecord('pages', (int)$pid);
                if ((int)$pageRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] > 0) {
                    $pid = (int)$pageRecord[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']];
                }
            } elseif (strpos($pid, 'NEW') !== 0) {
                throw new \RuntimeException(
                    'inlineFirstPid should either be an integer or a "NEW..." string',
                    1521220141
                );
            }
            $result['inlineFirstPid'] = $pid;
        }
        return $result;
    }

    /**
     * Substitute the value in databaseRow of this inline field with an array
     * that contains the databaseRows of currently connected records and some meta information.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function resolveSiteRelatedChildren(array $result, string $fieldName): array
    {
        $connectedUids = [];
        if ($result['command'] === 'edit') {
            $siteConfigurationForPageUid = (int)$result['databaseRow']['rootPageId'][0];
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            try {
                $site = $siteFinder->getSiteByRootPageId($siteConfigurationForPageUid);
            } catch (SiteNotFoundException $e) {
                $site = null;
            }
            $siteConfiguration = $site ? $site->getConfiguration() : [];
            if (is_array($siteConfiguration[$fieldName])) {
                $connectedUids = array_keys($siteConfiguration[$fieldName]);
            }
        }

        // If we are dealing with site_language, we *always* force a relation to sys_language "0"
        $foreignTable = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];
        if ($foreignTable === 'site_language' && $result['command'] === 'new') {
            // If new, just add a new default child
            $child = $this->compileDefaultSysSiteLanguageChild($result, $fieldName);
            $connectedUids[] = $child['databaseRow']['uid'];
            $result['processedTca']['columns'][$fieldName]['children'][] = $child;
        }

        $result['databaseRow'][$fieldName] = implode(',', $connectedUids);
        if ($result['inlineCompileExistingChildren']) {
            foreach ($connectedUids as $uid) {
                if (strpos((string)$uid, 'NEW') !== 0) {
                    $compiledChild = $this->compileChild($result, $fieldName, $uid);
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                }
            }
        }

        // If we are dealing with site_language, we *always* force a relation to sys_language "0"
        if ($foreignTable === 'site_language' && $result['command'] === 'edit') {
            // If edit, find out if a child using sys_language "0" exists, else add it on top
            $defaultSysSiteLanguageChildFound = false;
            foreach ($result['processedTca']['columns'][$fieldName]['children'] as $child) {
                if (isset($child['databaseRow']['languageId']) && (int)$child['databaseRow']['languageId'][0] == 0) {
                    $defaultSysSiteLanguageChildFound = true;
                }
            }
            if (!$defaultSysSiteLanguageChildFound) {
                // Compile and add child as first child
                $child = $this->compileDefaultSysSiteLanguageChild($result, $fieldName);
                $result['databaseRow'][$fieldName] = $child['databaseRow']['uid'] . ',' . $result['databaseRow'][$fieldName];
                array_unshift($result['processedTca']['columns'][$fieldName]['children'], $child);
            }
        }

        return $result;
    }

    /**
     * If there is a foreign_selector or foreign_unique configuration, fetch
     * the list of possible records that can be connected and attach them to the
     * inline configuration.
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @return array Modified item array
     */
    protected function addForeignSelectorAndUniquePossibleRecords(array $result, string $fieldName): array
    {
        if (!is_array($result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'])) {
            return $result;
        }

        $selectorOrUniqueConfiguration = $result['processedTca']['columns'][$fieldName]['config']['selectorOrUniqueConfiguration'];
        $foreignFieldName = $selectorOrUniqueConfiguration['fieldName'];
        $selectorOrUniquePossibleRecords = [];

        if ($selectorOrUniqueConfiguration['config']['type'] === 'select') {
            // Compile child table data for this field only
            $selectDataInput = [
                'tableName' => $result['processedTca']['columns'][$fieldName]['config']['foreign_table'],
                'command' => 'new',
                // Since there is no existing record that may have a type, it does not make sense to
                // do extra handling of pageTsConfig merged here. Just provide "parent" pageTS as is
                'pageTsConfig' => $result['pageTsConfig'],
                'userTsConfig' => $result['userTsConfig'],
                'databaseRow' => $result['databaseRow'],
                'processedTca' => [
                    'ctrl' => [],
                    'columns' => [
                        $foreignFieldName => [
                            'config' => $selectorOrUniqueConfiguration['config'],
                        ],
                    ],
                ],
                'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
            ];
            $formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
            $formDataGroup->setProviderList([TcaSelectItems::class]);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $compilerResult = $formDataCompiler->compile($selectDataInput);
            $selectorOrUniquePossibleRecords = $compilerResult['processedTca']['columns'][$foreignFieldName]['config']['items'];
        }

        $result['processedTca']['columns'][$fieldName]['config']['selectorOrUniquePossibleRecords'] = $selectorOrUniquePossibleRecords;

        return $result;
    }

    /**
     * Compile a full child record
     *
     * @param array $result Result array of parent
     * @param string $parentFieldName Name of parent field
     * @param int $childUid Uid of child to compile
     * @return array Full result array
     */
    protected function compileChild(array $result, string $parentFieldName, int $childUid): array
    {
        $parentConfig = $result['processedTca']['columns'][$parentFieldName]['config'];
        $childTableName = $parentConfig['foreign_table'];

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $childTableName,
            'vanillaUid' => $childUid,
            // Give incoming returnUrl down to children so they generate a returnUrl back to
            // the originally opening record, also see "originalReturnUrl" in inline container
            // and FormInlineAjaxController
            'returnUrl' => $result['returnUrl'],
            'isInlineChild' => true,
            'inlineStructure' => $result['inlineStructure'],
            'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
            'inlineFirstPid' => $result['inlineFirstPid'],
            'inlineParentConfig' => $parentConfig,

            // values of the current parent element
            // it is always a string either an id or new...
            'inlineParentUid' => $result['databaseRow']['uid'],
            'inlineParentTableName' => $result['tableName'],
            'inlineParentFieldName' => $parentFieldName,

            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: $inlineTopMostParent['field'],
        ];

        if ($parentConfig['foreign_selector'] && ($parentConfig['appearance']['useCombination'] ?? false)) {
            throw new \RuntimeException('useCombination not implemented in sites module', 1522493097);
        }
        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * Compile default site_language child using sys_language uid "0"
     *
     * @param array $result
     * @param string $parentFieldName
     * @return array
     */
    protected function compileDefaultSysSiteLanguageChild(array $result, string $parentFieldName): array
    {
        $parentConfig = $result['processedTca']['columns'][$parentFieldName]['config'];
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);
        $formDataGroup = GeneralUtility::makeInstance(SiteConfigurationDataGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'new',
            'tableName' => 'site_language',
            'vanillaUid' => $result['inlineFirstPid'],
            'returnUrl' => $result['returnUrl'],
            'isInlineChild' => true,
            'inlineStructure' => [],
            'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
            'inlineFirstPid' => $result['inlineFirstPid'],
            'inlineParentConfig' => $parentConfig,
            'inlineParentUid' => $result['databaseRow']['uid'],
            'inlineParentTableName' => $result['tableName'],
            'inlineParentFieldName' => $parentFieldName,
            'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: $inlineTopMostParent['field'],
            // The sys_language uid 0
            'inlineChildChildUid' => 0,
        ];
        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
