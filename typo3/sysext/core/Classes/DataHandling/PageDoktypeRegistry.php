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

namespace TYPO3\CMS\Core\DataHandling;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This object defines the various types of pages (field: doktype) the system
 * can handle and what restrictions may apply to them when adding records.
 * Here you can define which tables are allowed on a certain pagetype (doktype).
 *
 * NOTE: The 'default' entry array is the 'base' for all types, and for every type the
 * entries simply overrides the entries in the 'default' type!
 *
 * You can fully use this once TCA is properly loaded (e.g. in ext_tables.php).
 */
#[Autoconfigure(public: true)]
readonly class PageDoktypeRegistry
{
    public function __construct(protected TcaSchemaFactory $tcaSchemaFactory) {}

    /**
     * Check if a record can be added on a page with a given $doktype.
     */
    public function isRecordTypeAllowedForDoktype(string $type, int $doktype): bool
    {
        $allowedRecordTypes = $this->getAllowedTypesForDoktype($doktype);
        if (in_array('*', $allowedRecordTypes, true)) {
            return true;
        }
        return in_array($type, $allowedRecordTypes, true);
    }

    /**
     * @internal only to be used within TYPO3 Core
     */
    public function getAllowedTypesForDoktype(int $doktype): array
    {
        $pageTypes = $this->exportConfiguration();
        if (($pageTypes[$doktype]['allowedTables'] ?? []) !== []) {
            return $pageTypes[$doktype]['allowedTables'];
        }
        $hardDefaults = ['pages', 'sys_category', 'sys_file_reference', 'sys_file_collection'];
        $defaultAllowedRecordTypes = $this->tcaSchemaFactory->get('pages')->getRawConfiguration()['defaultAllowedRecordTypes'] ?? [];
        $mergedDefault = array_merge($hardDefaults, $defaultAllowedRecordTypes);
        return array_unique($mergedDefault);
    }

    /**
     * @internal only to be used within TYPO3 Core
     */
    public function exportConfiguration(): array
    {
        $pageTypes = [];
        foreach ($this->tcaSchemaFactory->get('pages')->getSubSchemata() as $pageType => $pageTypeSchema) {
            $allowedRecordTypes = $pageTypeSchema->getRawConfiguration()['allowedRecordTypes'] ?? [];
            $pageTypes[$pageType]['allowedTables'] = $allowedRecordTypes;
        }
        return $pageTypes;
    }

    /**
     * @return SelectItem[]
     */
    public function getAllDoktypes(): array
    {
        $doktypeLabelMap = [];
        $schema = $this->tcaSchemaFactory->get('pages');
        // @todo Does not work for dynamic items, in case SubSchemaDivisorField is no StaticSelectFieldType!
        $subSchemaField = $schema->getSubSchemaTypeInformation()->getFieldName();
        foreach ($schema->getField($subSchemaField)->getConfiguration()['items'] ?? [] as $doktypeItemConfig) {
            $selectionItem = SelectItem::fromTcaItemArray($doktypeItemConfig);
            if ($selectionItem->isDivider()) {
                continue;
            }
            $doktypeLabelMap[] = $selectionItem;
        }
        return $doktypeLabelMap;
    }

    /**
     * Check if a page type is viewable based on TCA configuration only.
     * Does NOT consider pageTsConfig overrides.
     *
     * By default, all page types are viewable unless explicitly set to false
     * via the TCA option "isViewable".
     */
    public function isPageTypeViewable(int $doktype): bool
    {
        $pageSchema = $this->tcaSchemaFactory->get('pages');
        if ($pageSchema->hasSubSchema((string)$doktype)) {
            $subSchema = $pageSchema->getSubSchema((string)$doktype);
            $config = $subSchema->getRawConfiguration();
            if (isset($config['isViewable'])) {
                return (bool)$config['isViewable'];
            }
        }
        // Default: viewable
        return true;
    }

    /**
     * Check if a page is viewable, considering both TCA and pageTsConfig.
     * Respects TCEMAIN.preview.disableButtonForDokType TSconfig.
     */
    public function isPageViewable(int $doktype, int $pageId): bool
    {
        // check TSconfig (same logic as PreviewUriBuilder::isPreviewableDoktype)
        $TSconfig = BackendUtility::getPagesTSconfig($pageId)['TCEMAIN.']['preview.'] ?? [];
        if (isset($TSconfig['disableButtonForDokType'])) {
            $excludeDokTypes = GeneralUtility::intExplode(',', (string)$TSconfig['disableButtonForDokType'], true);
            return !in_array($doktype, $excludeDokTypes, true);
        }

        // fallback to check TCA
        if (!$this->isPageTypeViewable($doktype)) {
            return false;
        }
        return true;
    }

    /**
     * Returns array of non-viewable doktype integers based on TCA only.
     * Used for JavaScript tree configuration.
     *
     * @return int[]
     */
    public function getNonViewableDoktypes(): array
    {
        $nonViewable = [];
        foreach ($this->tcaSchemaFactory->get('pages')->getSubSchemata() as $doktype => $schema) {
            $isViewable = $schema->getRawConfiguration()['isViewable'] ?? true;
            if (!$isViewable) {
                $nonViewable[] = (int)$doktype;
            }
        }
        return $nonViewable;
    }
}
