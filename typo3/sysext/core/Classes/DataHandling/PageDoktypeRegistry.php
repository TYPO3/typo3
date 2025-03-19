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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
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
class PageDoktypeRegistry
{
    protected array $pageTypes = [
        PageRepository::DOKTYPE_BE_USER_SECTION => [
            'allowedTables' => '*',
        ],
        //  Doktype 254 is a 'Folder' - a general purpose storage folder for whatever you like.
        // In CMS context it's NOT a viewable page. Can contain any element.
        PageRepository::DOKTYPE_SYSFOLDER => [
            'allowedTables' => '*',
        ],
        PageRepository::DOKTYPE_MOUNTPOINT => [
        ],
        // Even though both options look contradictory, the "allowedTables" key is used for other $pageTypes
        // that have no custom definitions. So "allowedTables" works as a fallback for additional page types.
        'default' => [
            'allowedTables' => 'pages,sys_category,sys_file_reference,sys_file_collection',
            'onlyAllowedTables' => false,
        ],
    ];

    /**
     * @todo Using this to keep track of the initialization is just an intermediate solution.
     *       TCA should be extended so the add() and addAllowedRecordTypes() methods can be removed.
     */
    private bool $tcaHasBeenInitialized = false;

    public function __construct(protected readonly TcaSchemaFactory $tcaSchemaFactory) {}

    /**
     * Adds a specific configuration for a doktype. By default, it is NOT restricted to only allow tables that
     * have been explicitly added via addAllowedRecordTypes().
     */
    public function add(int $dokType, array $configuration): void
    {
        $this->initializeTca();
        $this->pageTypes[$dokType] = array_replace(['onlyAllowedTables' => false], $configuration);
    }

    public function addAllowedRecordTypes(array $recordTypes, ?int $doktype = null): void
    {
        if ($recordTypes === []) {
            return;
        }
        $this->initializeTca();
        $doktype ??= 'default';
        if (!isset($this->pageTypes[$doktype]['allowedTables'])) {
            $this->pageTypes[$doktype]['allowedTables'] = '';
        }
        $this->pageTypes[$doktype]['allowedTables'] .= ',' . implode(',', $recordTypes);
    }

    /**
     * Check if a record can be added on a page with a given $doktype.
     */
    public function isRecordTypeAllowedForDoktype(string $type, ?int $doktype): bool
    {
        $this->initializeTca();
        $doktype ??= 'default';
        $allowedTableList = $this->pageTypes[$doktype]['allowedTables'] ?? $this->pageTypes['default']['allowedTables'];
        return str_contains($allowedTableList, '*') || GeneralUtility::inList($allowedTableList, $type);
    }

    /**
     * @internal
     */
    public function getRegisteredDoktypes(): array
    {
        $this->initializeTca();
        $items = $this->pageTypes;
        unset($items['default']);
        return array_keys($items);
    }

    /**
     * Used to find out if a specific doktype is restricted to only allow a certain list of tables.
     * This list can be checked against via 'isRecordTypeAllowedForDoktype()'
     */
    public function doesDoktypeOnlyAllowSpecifiedRecordTypes(?int $doktype = null): bool
    {
        $this->initializeTca();
        $doktype = $doktype ?? 'default';
        return $this->pageTypes[$doktype]['onlyAllowedTables'] ?? false;
    }

    /**
     * @internal only to be used within TYPO3 Core
     */
    public function getAllowedTypesForDoktype(int $doktype): array
    {
        $this->initializeTca();
        $allowedTableList = $this->pageTypes[$doktype]['allowedTables'] ?? $this->pageTypes['default']['allowedTables'];
        return explode(',', $allowedTableList);
    }

    /**
     * @internal only to be used within TYPO3 Core
     */
    public function exportConfiguration(): array
    {
        $this->initializeTca();
        return $this->pageTypes;
    }

    /**
     * @return SelectItem[]
     */
    public function getAllDoktypes(): array
    {
        $doktypeLabelMap = [];

        foreach ($this->tcaSchemaFactory->get('pages')->getSubSchemaDivisorField()->getConfiguration()['items'] as $doktypeItemConfig) {
            $selectionItem = SelectItem::fromTcaItemArray($doktypeItemConfig);
            if ($selectionItem->isDivider()) {
                continue;
            }
            $doktypeLabelMap[] = $selectionItem;
        }
        return $doktypeLabelMap;
    }

    private function initializeTca(): void
    {
        if ($this->tcaHasBeenInitialized) {
            return;
        }
        $allowedRecordTypesForDefault = [];
        foreach ($this->tcaSchemaFactory->all() as $schemaName => $schema) {
            if ($schema->getRawConfiguration()['security']['ignorePageTypeRestriction'] ?? false) {
                $allowedRecordTypesForDefault[] = $schemaName;
            }
        }
        $this->tcaHasBeenInitialized = true;
        $this->addAllowedRecordTypes($allowedRecordTypesForDefault);
    }
}
