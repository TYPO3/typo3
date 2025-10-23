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

namespace TYPO3\CMS\Reports\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck;

/**
 * Service for collecting database record statistics
 *
 * @internal This is not part of the public API and may change at any time
 */
#[Autoconfigure(public: true)]
final readonly class RecordStatisticsService
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected PageDoktypeRegistry $pageDoktypeRegistry,
    ) {}

    /**
     * Collects page statistics including total, translated, hidden, and deleted pages
     *
     * @return array<string, array{icon: string, count: int}>
     */
    public function collectPageStatistics(): array
    {
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->genTree(0);

        return [
            'total_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], IconSize::SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageIdArray()),
            ],
            'translated_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], IconSize::SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageTranslatedPageIDArray()),
            ],
            'hidden_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['hidden' => 1], IconSize::SMALL)->render(),
                'count' => $databaseIntegrityCheck->getRecStats()['hidden'] ?? 0,
            ],
            'deleted_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['deleted' => 1], IconSize::SMALL)->render(),
                'count' => isset($databaseIntegrityCheck->getRecStats()['deleted']['pages']) ? count($databaseIntegrityCheck->getRecStats()['deleted']['pages']) : 0,
            ],
        ];
    }

    /**
     * Collects statistics for different page doktypes
     *
     * @return array<int, array{icon: string, title: string, count: int}>
     */
    public function collectDoktypeStatistics(): array
    {
        $languageService = $this->getLanguageService();
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->genTree(0);

        $doktypes = [];
        foreach ($this->pageDoktypeRegistry->getAllDoktypes() as $doktype) {
            if ($doktype->isDivider()) {
                continue;
            }
            $doktypes[] = [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $doktype->getValue()], IconSize::SMALL)->render(),
                'title' => $languageService->sL($doktype->getLabel()) . ' (' . $doktype->getValue() . ')',
                'count' => (int)($databaseIntegrityCheck->getRecStats()['doktype'][$doktype->getValue()] ?? 0),
            ];
        }

        return $doktypes;
    }

    /**
     * Collects statistics for all TCA tables including lost records
     *
     * @return array<string, array{icon: string, title: string, count: int|string, lostRecords: string}>
     */
    public function collectTableStatistics(): array
    {
        $languageService = $this->getLanguageService();
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->genTree(0);

        // Tables and lost records
        $id_list = implode(',', array_merge([0], array_keys($databaseIntegrityCheck->getPageIdArray())));
        $databaseIntegrityCheck->lostRecords($id_list);

        $tableStatistic = [];
        $countArr = $databaseIntegrityCheck->countRecords($id_list);

        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            if ($schema->hasCapability(TcaSchemaCapability::HideInUi)) {
                continue;
            }
            if ($table === 'pages' && $databaseIntegrityCheck->getLostPagesList() !== '') {
                $lostRecordCount = count(explode(',', $databaseIntegrityCheck->getLostPagesList()));
            } else {
                $lostRecordCount = isset($databaseIntegrityCheck->getLRecords()[$table]) ? count($databaseIntegrityCheck->getLRecords()[$table]) : 0;
            }
            $recordCount = 0;
            if ($countArr['all'][$table] ?? false) {
                $recordCount = (int)($countArr['non_deleted'][$table] ?? 0) . '/' . $lostRecordCount;
            }
            $lostRecordList = [];
            foreach ($databaseIntegrityCheck->getLRecords()[$table] ?? [] as $data) {
                if (!GeneralUtility::inList($databaseIntegrityCheck->getLostPagesList(), $data['pid'])) {
                    $lostRecordList[] =
                        '<div class="record">' .
                        $this->iconFactory->getIcon('status-dialog-error', IconSize::SMALL)->render() .
                        'uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                        '</div>';
                } else {
                    $lostRecordList[] =
                        '<div class="record-noicon">' .
                        'uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                        '</div>';
                }
            }
            $tableStatistic[$table] = [
                'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL)->render(),
                'title' => $schema->getTitle($languageService->sL(...)),
                'count' => $recordCount,
                'lostRecords' => implode(LF, $lostRecordList),
            ];
        }

        return $tableStatistic;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
