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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataGroup\SiteConfigurationDataGroup;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolve and prepare site language data
 *
 * @internal This FormDataProvider is only used in the site configuration module and is not public API
 */
class TcaSiteLanguage extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    private const FOREIGN_TABLE = 'site_language';
    private const FOREIGN_FIELD = 'languageId';
    public function __construct(
        private readonly SiteFinder $siteFinder,
    ) {}

    public function addData(array $result): array
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (($fieldConfig['config']['type'] ?? '') !== 'siteLanguage') {
                continue;
            }

            if (!($GLOBALS['TCA'][self::FOREIGN_TABLE] ?? false)) {
                throw new \RuntimeException('Table ' . self::FOREIGN_TABLE . ' does not exists', 1624029932);
            }

            $childConfiguration = $GLOBALS['TCA'][self::FOREIGN_TABLE]['columns'][self::FOREIGN_FIELD]['config'] ?? [];

            if (($childConfiguration['type'] ?? '') !== 'select') {
                throw new \UnexpectedValueException(
                    'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points to field '
                    . self::FOREIGN_FIELD . ' of table ' . self::FOREIGN_TABLE . ', but this field '
                    . 'is either not defined or is not of type select',
                    1624029933
                );
            }

            if (!($childConfiguration['itemsProcFunc'] ?? false)) {
                throw new \UnexpectedValueException(
                    'Table ' . $result['tableName'] . ' field ' . $fieldName . ' points to field '
                    . self::FOREIGN_FIELD . ' of table ' . self::FOREIGN_TABLE . '. This field must define '
                    . 'an \'itemsProcFunc\'.',
                    1624029934
                );
            }

            $result = $this->addInlineRelatedConfig($result, $fieldName);
            $result = $this->initializeMinMaxItems($result, $fieldName);
            $result = $this->initializeAppearance($result, $fieldName);
            $result = $this->addInlineFirstPid($result);
            $result = $this->resolveSiteLanguageChildren($result, $fieldName);
            $result = $this->addUniquePossibleRecords($result, $fieldName);
        }

        return $result;
    }

    protected function addInlineRelatedConfig(array $result, string $fieldName): array
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];
        $config['foreign_table'] = self::FOREIGN_TABLE;
        $config['foreign_selector'] = self::FOREIGN_FIELD;
        $result['processedTca']['columns'][$fieldName]['config'] = $config;

        return $result;
    }

    protected function initializeMinMaxItems(array $result, string $fieldName): array
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];
        $config['minitems'] = isset($config['minitems']) ? MathUtility::forceIntegerInRange($config['minitems'], 1) : 1;
        $config['maxitems'] = isset($config['maxitems']) ? MathUtility::forceIntegerInRange($config['maxitems'], 2) : 99999;
        $result['processedTca']['columns'][$fieldName]['config'] = $config;

        return $result;
    }

    protected function initializeAppearance(array $result, string $fieldName): array
    {
        $config = $result['processedTca']['columns'][$fieldName]['config'];
        if (!is_array($config['appearance'] ?? false)) {
            $config['appearance'] = [];
        }
        $config['appearance']['showPossibleLocalizationRecords'] = false;
        $config['appearance']['collapseAll'] = true;
        $config['appearance']['expandSingle'] = false;
        $config['appearance']['enabledControls'] = [
            'info' => false,
            'new' => false,
            'dragdrop' => false,
            'sort' => false,
            'hide' => false,
            'delete' => true,
            'localize' => false,
        ];

        $config['size'] = (int)($config['size'] ?? 4);

        $result['processedTca']['columns'][$fieldName]['config'] = $config;

        return $result;
    }

    protected function addInlineFirstPid(array $result): array
    {
        if (($result['inlineFirstPid'] ?? null) !== null || ($result['tableName'] ?? '') !== self::FOREIGN_TABLE) {
            return $result;
        }

        $pid = $result['databaseRow']['pid'] ?? 0;

        if (!MathUtility::canBeInterpretedAsInteger($pid) || !str_starts_with($pid, 'NEW')) {
            throw new \RuntimeException(
                'inlineFirstPid should either be an integer or a "NEW..." string',
                1624310264
            );
        }

        $result['inlineFirstPid'] = $pid;

        return $result;
    }

    protected function resolveSiteLanguageChildren(array $result, string $fieldName): array
    {
        $connectedUids = [];
        $result['processedTca']['columns'][$fieldName]['children'] = [];

        if ($result['command'] === 'edit') {
            $siteConfiguration = [];
            try {
                $site = $this->siteFinder->getSiteByRootPageId((int)($result['databaseRow']['rootPageId'][0] ?? 0));
                $siteConfiguration = $site->getConfiguration();
            } catch (SiteNotFoundException $e) {
            }
            if (is_array($siteConfiguration[$fieldName] ?? false)) {
                // Add uids of existing site languages
                $connectedUids = array_keys($siteConfiguration[$fieldName]);
            }
        } elseif ($result['command'] === 'new') {
            // If new, *always* force a relation to the default language ("0")
            $child = $this->compileDefaultSiteLanguageChild($result, $fieldName);
            $connectedUids[] = $child['databaseRow']['uid'];
            $result['processedTca']['columns'][$fieldName]['children'][] = $child;
        }

        // Add connected uids as csv field value
        $result['databaseRow'][$fieldName] = implode(',', $connectedUids);

        if ($result['inlineCompileExistingChildren']) {
            foreach ($connectedUids as $uid) {
                // Compile existing (persisted) site languages
                if (!str_starts_with((string)$uid, 'NEW')) {
                    $compiledChild = $this->compileChild($result, $fieldName, $uid);
                    $result['processedTca']['columns'][$fieldName]['children'][] = $compiledChild;
                }
            }
        }

        if ($result['command'] === 'edit') {
            // If edit, find out if a default language ("0") exists, else add it on top
            $defaultSysSiteLanguageChildFound = false;
            foreach ($result['processedTca']['columns'][$fieldName]['children'] as $child) {
                if (isset($child['databaseRow']['languageId'][0]) && (int)$child['databaseRow']['languageId'][0] === 0) {
                    $defaultSysSiteLanguageChildFound = true;
                }
            }
            if (!$defaultSysSiteLanguageChildFound) {
                // Compile and add child as first child, since non exists yet
                $child = $this->compileDefaultSiteLanguageChild($result, $fieldName);
                $result['databaseRow'][$fieldName] = $child['databaseRow']['uid'] . ',' . $result['databaseRow'][$fieldName];
                array_unshift($result['processedTca']['columns'][$fieldName]['children'], $child);
            }
        }

        return $result;
    }

    protected function compileDefaultSiteLanguageChild(array $result, string $parentFieldName): array
    {
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        return GeneralUtility::makeInstance(FormDataCompiler::class)
            ->compile(
                [
                    'request' => $result['request'],
                    'command' => 'new',
                    'tableName' => self::FOREIGN_TABLE,
                    'vanillaUid' => $result['inlineFirstPid'],
                    'databaseRow' => $this->getDefaultDatabaseRow(),
                    'returnUrl' => $result['returnUrl'],
                    'isInlineChild' => true,
                    'inlineStructure' => [],
                    'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
                    'inlineFirstPid' => $result['inlineFirstPid'],
                    'inlineParentConfig' => $result['processedTca']['columns'][$parentFieldName]['config'],
                    'inlineParentUid' => $result['databaseRow']['uid'],
                    'inlineParentTableName' => $result['tableName'],
                    'inlineParentFieldName' => $parentFieldName,
                    'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: ($inlineTopMostParent['uid'] ?? null),
                    'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: ($inlineTopMostParent['table'] ?? ''),
                    'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: ($inlineTopMostParent['field'] ?? ''),
                    'inlineChildChildUid' => 0,
                ],
                GeneralUtility::makeInstance(SiteConfigurationDataGroup::class)
            );
    }

    protected function compileChild(array $result, string $parentFieldName, int $childUid): array
    {
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($result['inlineStructure']);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        return GeneralUtility::makeInstance(FormDataCompiler::class)
            ->compile(
                [
                    'request' => $result['request'],
                    'command' => 'edit',
                    'tableName' => self::FOREIGN_TABLE,
                    'vanillaUid' => $childUid,
                    'returnUrl' => $result['returnUrl'],
                    'isInlineChild' => true,
                    'inlineStructure' => $result['inlineStructure'],
                    'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
                    'inlineFirstPid' => $result['inlineFirstPid'],
                    'inlineParentConfig' => $result['processedTca']['columns'][$parentFieldName]['config'],
                    'inlineParentUid' => $result['databaseRow']['uid'],
                    'inlineParentTableName' => $result['tableName'],
                    'inlineParentFieldName' => $parentFieldName,
                    'inlineTopMostParentUid' => $result['inlineTopMostParentUid'] ?: ($inlineTopMostParent['uid'] ?? null),
                    'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'] ?: ($inlineTopMostParent['table'] ?? ''),
                    'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'] ?: ($inlineTopMostParent['field'] ?? ''),
                ],
                GeneralUtility::makeInstance(SiteConfigurationDataGroup::class)
            );
    }

    protected function addUniquePossibleRecords(array $result, string $fieldName): array
    {
        $formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
        $formDataGroup->setProviderList([TcaSelectItems::class]);

        // Add unique possible records, so they can be used in the selector field
        $result['processedTca']['columns'][$fieldName]['config']['uniquePossibleRecords'] = GeneralUtility::makeInstance(FormDataCompiler::class)
            ->compile(
                [
                    'request' => $result['request'],
                    'command' => 'new',
                    'tableName' => self::FOREIGN_TABLE,
                    'pageTsConfig' => $result['pageTsConfig'],
                    'userTsConfig' => $result['userTsConfig'],
                    'databaseRow' => $result['databaseRow'],
                    'processedTca' => [
                        'ctrl' => [],
                        'columns' => [
                            self::FOREIGN_FIELD => [
                                'config' => $GLOBALS['TCA'][self::FOREIGN_TABLE]['columns'][self::FOREIGN_FIELD]['config'],
                            ],
                        ],
                    ],
                    'inlineExpandCollapseStateArray' => $result['inlineExpandCollapseStateArray'],
                ],
                $formDataGroup
            )['processedTca']['columns'][self::FOREIGN_FIELD]['config']['items'] ?? [];

        return $result;
    }

    /**
     * Create the database row for the default site language based
     * on an already existing default language from another site.
     */
    protected function getDefaultDatabaseRow(): array
    {
        $defaultDatabaseRow = [];

        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                if ($language->getLanguageId() === 0) {
                    $defaultDatabaseRow['locale'] = $language->getLocale()->posixFormatted();
                    if ($language->getTitle() !== '') {
                        $defaultDatabaseRow['title'] = $language->getTitle();
                    }
                    if ($language->getNavigationTitle() !== '') {
                        $defaultDatabaseRow['navigationTitle'] = $language->getNavigationTitle();
                    }
                    if ($language->getHreflang(true) !== '') {
                        $defaultDatabaseRow['hreflang'] = $language->getHreflang();
                    }
                    if (str_starts_with($language->getFlagIdentifier(), 'flags-')) {
                        $flagIdentifier = str_replace('flags-', '', $language->getFlagIdentifier());
                        $defaultDatabaseRow['flag'] = ($flagIdentifier === 'multiple') ? 'global' : $flagIdentifier;
                    }
                    break 2;
                }
            }
        }

        return $defaultDatabaseRow;
    }
}
