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

namespace TYPO3\CMS\Reports\Report;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck;
use TYPO3\CMS\Reports\RequestAwareReportInterface;

#[Autoconfigure(public: true)]
final readonly class RecordStatistics implements RequestAwareReportInterface
{
    public function __construct(
        protected BackendViewFactory $backendViewFactory,
        protected IconFactory $iconFactory,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected PageDoktypeRegistry $pageDoktypeRegistry,
    ) {}

    /**
     * Takes care of creating / rendering the status report
     *
     * @param ServerRequestInterface|null $request the currently handled request
     * @return string The status report as HTML
     */
    public function getReport(?ServerRequestInterface $request = null): string
    {
        $view = $this->backendViewFactory->create($request);
        $languageService = $this->getLanguageService();
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->genTree(0);

        // Page stats
        $pageStatistic = [
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

        // doktypes stats
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

        $view->assignMultiple([
            'pages' => $pageStatistic,
            'doktypes' => $doktypes,
            'tables' => $tableStatistic,
        ]);
        return $view->render('RecordStatistics');
    }

    public function getIdentifier(): string
    {
        return 'recordStatistics';
    }

    public function getTitle(): string
    {
        return 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf:recordStatistics.title';
    }

    public function getDescription(): string
    {
        return 'LLL:EXT:reports/Resources/Private/Language/locallang.xlf:recordStatistics.description';
    }

    public function getIconIdentifier(): string
    {
        return 'module-reports';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

}
