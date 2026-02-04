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

namespace TYPO3\CMS\Backend\Preview;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Legacy preview rendering refactored from PageLayoutView.
 * Provided as default preview rendering mechanism via
 * StandardPreviewRendererResolver which detects the renderer
 * based on TCA configuration.
 *
 * Can be replaced and/or subclassed by custom implementations
 * by changing this TCA configuration.
 *
 * See also PreviewRendererInterface documentation.
 */
#[Autoconfigure(public: true)]
class StandardContentPreviewRenderer implements PreviewRendererInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected ?RecordFieldPreviewProcessor $fieldProcessor = null;
    protected ?TcaSchemaFactory $tcaSchemaFactory = null;
    protected ?LocalizationRepository $localizationRepository = null;
    protected ?BackendLayoutView $backendLayoutView = null;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * We use this workaround for the subclasses that do DI since TYPO3 v11, but do not call this constructor.
     * This is a backwards-compatible layer until we have a better API than PreviewRendererInterface.
     */
    private function initialize(): void
    {
        if (!isset($this->fieldProcessor)) {
            $this->fieldProcessor = GeneralUtility::makeInstance(RecordFieldPreviewProcessor::class);
        }
        if (!isset($this->tcaSchemaFactory)) {
            $this->tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
        }
        if (!isset($this->localizationRepository)) {
            $this->localizationRepository = GeneralUtility::makeInstance(LocalizationRepository::class);
        }
        if (!isset($this->backendLayoutView)) {
            $this->backendLayoutView = GeneralUtility::makeInstance(BackendLayoutView::class);
        }
    }

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        $this->initialize();
        $record = $item->getRecord();
        $request = $item->getContext()->getCurrentRequest();
        $schema = $this->tcaSchemaFactory->get($item->getTable());
        $outHeader = '';

        if ($record->has('header_layout')) {
            $headerLayout = (string)$record->get('header_layout');
            if ($headerLayout === '100') {
                $headerLayoutHiddenLabel = $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.6');
                $outHeader .= '<div class="element-preview-header-status">' . htmlspecialchars($headerLayoutHiddenLabel) . '</div>';
            }
        }

        $dateLabel = $this->fieldProcessor->prepareFieldWithLabel($record, 'date');
        if ($dateLabel) {
            $outHeader .= '<div class="element-preview-header-date">' . htmlspecialchars(strip_tags($dateLabel)) . ' </div>';
        }

        if ($schema->hasCapability(TcaSchemaCapability::Label)) {
            $labelFieldName = $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName();
            $label = $this->fieldProcessor->prepareText($record, $labelFieldName);
            if ($label !== null) {
                $outHeader .= '<div class="element-preview-header-header">' . $this->fieldProcessor->linkToEditForm($label, $record, $request) . '</div>';
            }
        }

        $subHeader = $this->fieldProcessor->prepareText($record, 'subheader');
        if ($subHeader !== null) {
            $outHeader .= '<div class="element-preview-header-subheader">' . $this->fieldProcessor->linkToEditForm($subHeader, $record, $request) . '</div>';
        }

        return $outHeader;
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $this->initialize();
        $recordObj = $item->getRecord();
        // This preview should only be used for tt_content records.
        if ($recordObj->getMainType() !== 'tt_content') {
            return '';
        }

        $languageService = $this->getLanguageService();
        $recordType = $recordObj->getRecordType();
        $schema = $this->tcaSchemaFactory->get($recordObj->getMainType());

        // If the record type is unknown, render a warning message.
        if (!$schema->hasSubSchema($recordType)) {
            $message = sprintf(
                $languageService->sL('core.core:labels.noMatchingValue'),
                $recordType
            );
            return '<span class="badge badge-warning">' . htmlspecialchars($message) . '</span>';
        }

        if (!$this->backendLayoutView->isCTypeAllowedInColPosByPage(
            $item->getRecordType(),
            $item->getColumn()->getColumnNumber() ?? 0,
            $item->getRecord()->getPid()
        )) {
            $message = sprintf(
                $languageService->sL('core.core:labels.typeNotAllowedInColumn'),
                $recordType
            );
            return '<span class="badge badge-warning">' . htmlspecialchars($message) . '</span>';
        }
        $subSchema = $schema->getSubSchema($recordType);
        $request = $item->getContext()->getCurrentRequest();

        // Draw preview of the item depending on its record type
        switch ($recordType) {
            case 'header':
                break;
            case 'shortcut':
                if ($recordObj->has('records') && ($records = $recordObj->get('records'))) {
                    $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                    $shortcutContent = '';
                    $shortcutRecords = $records instanceof \Traversable ? $records : [$records];
                    foreach ($shortcutRecords as $shortcutRecord) {
                        $shortcutTableName = $shortcutRecord->getMainType();
                        $row = $shortcutRecord->getRawRecord()?->toArray() ?? [];
                        if ($recordObj instanceof Record) {
                            $shortcutRecord = $this->translateShortcutRecord($recordObj, $shortcutRecord, $shortcutTableName);
                        }
                        $icon = $iconFactory->getIconForRecord($shortcutTableName, $row, IconSize::SMALL)->render();
                        $icon = BackendUtility::wrapClickMenuOnIcon(
                            $icon,
                            $shortcutTableName,
                            $shortcutRecord->getUid(),
                            '1'
                        );
                        $pathToContainingPage = BackendUtility::getRecordPath($row['pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0);
                        $title = BackendUtility::getRecordTitle($shortcutTableName, $row);
                        $itemContent = htmlspecialchars($title) . ' <span class="text-variant">[' . $recordObj->getUid() . '] ' . htmlspecialchars($pathToContainingPage) . '</span>';
                        $shortcutContent .= '<li class="list-group-item">'
                            . $icon
                            . ' '
                            . $this->fieldProcessor->linkToEditForm($itemContent, $shortcutRecord, $request)
                            . '</li>';
                    }
                    return $shortcutContent !== '' ? '<ul class="list-group">' . $shortcutContent . '</ul>' : '';
                }
                break;
            case 'menu_abstract':
            case 'menu_categorized_content':
            case 'menu_categorized_pages':
            case 'menu_pages':
            case 'menu_recently_updated':
            case 'menu_related_pages':
            case 'menu_section':
            case 'menu_section_pages':
            case 'menu_sitemap':
            case 'menu_sitemap_pages':
            case 'menu_subpages':
                $row = $recordObj->getRawRecord()?->toArray() ?? [];
                if ($recordType !== 'menu_sitemap' && (($row['pages'] ?? false) || ($row['selected_categories'] ?? false))) {
                    // Show pages/categories if the menu type is not "Sitemap"
                    $content = $this->generateListForMenuContentTypes($row, $recordType);
                    return $this->fieldProcessor->linkToEditForm($content, $recordObj, $request);
                }
                break;
            case 'bullets':
                $list = GeneralUtility::trimExplode(LF, $recordObj->get('bodytext'), true);
                if ($list !== []) {
                    switch ($recordObj->get('bullets_type')) {
                        case 0:
                            $list = array_map(
                                static fn(string $item) => '<li>' . htmlspecialchars($item) . '</li>',
                                $list
                            );
                            return '<ul>' . implode(LF, $list) . '</ul>';
                        case 1:
                            $list = array_map(
                                static fn(string $item) => '<li>' . htmlspecialchars($item) . '</li>',
                                $list
                            );
                            return '<ol>' . implode(LF, $list) . '</ol>';
                        case 2:
                            $list = array_map(
                                static fn(string $item): string =>
                                (static function () use ($item) {
                                    $split = GeneralUtility::trimExplode('|', $item, true, 2);

                                    return '<dt>' . htmlspecialchars($split[0]) . '</dt>'
                                        . '<dd>' . htmlspecialchars($split[1] ?? '') . '</dd>';
                                })(),
                                $list
                            );
                            return '<dl>' . implode(LF, $list) . '</dl>';
                    }
                }
                break;
            case 'html':
                $html = $this->fieldProcessor->preparePlainHtml($recordObj, 'bodytext');
                return $this->fieldProcessor->linkToEditForm($html, $recordObj, $request);
            default:
                $content = (string)$this->fieldProcessor->prepareText($recordObj, 'bodytext');
                foreach ($subSchema->getFieldsOfType(TableColumnType::FILE) as $field) {
                    $fieldName = $field->getName();
                    if ($recordObj->has($fieldName) && ($image = $recordObj->get($fieldName))) {
                        $content .= $this->fieldProcessor->prepareFiles($image);
                    }
                }
                return $this->fieldProcessor->linkToEditForm($content, $recordObj, $request);
        }
        return '';
    }

    /**
     * Render a footer for the record
     */
    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        $this->initialize();
        $info = [];
        $record = $item->getRecord();
        $schema = $this->tcaSchemaFactory->get($item->getTable());
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $info[] = $this->fieldProcessor->prepareFieldWithLabel($record, $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName());
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $info[] = $this->fieldProcessor->prepareFieldWithLabel($record, $schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName());
        }
        if ($schema->hasCapability(TcaSchemaCapability::RestrictionUserGroup)) {
            $info[] = $this->fieldProcessor->prepareFieldWithLabel($record, $schema->getCapability(TcaSchemaCapability::RestrictionUserGroup)->getFieldName());
        }
        if ($record->getMainType() === 'tt_content') {
            foreach (['space_before_class', 'space_after_class'] as $additionalFieldName) {
                $itm = $this->fieldProcessor->prepareFieldWithLabel($record, $additionalFieldName);
                if ($itm !== null) {
                    $info[] = $itm;
                }
            }
        }

        $info = array_filter($info);

        if ($info === []) {
            return '';
        }

        if ($schema->hasCapability(TcaSchemaCapability::InternalDescription)) {
            $item = $this->fieldProcessor->prepareField($record, $schema->getCapability(TcaSchemaCapability::InternalDescription)->getFieldName());
            if ($item !== null) {
                $info[] = $item;
            }
        }

        return implode('<br>', $info);
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        $previewHeader = $previewHeader ? '<div class="element-preview-header">' . $previewHeader . '</div>' : '';
        $previewContent = $previewContent ? '<div class="element-preview-content">' . $previewContent . '</div>' : '';
        return $previewHeader || $previewContent ? '<div class="element-preview">' . $previewHeader . $previewContent . '</div>' : '';
    }

    protected function translateShortcutRecord(Record $targetRecord, Record $shortcutRecord, string $tableName): RawRecord
    {
        $targetLanguage = ($targetRecord->getLanguageId() ?? 0);
        if ($targetLanguage === 0
            || !$this->tcaSchemaFactory->get($tableName)->isLanguageAware()
            || $targetLanguage === ($shortcutRecord->getLanguageId() ?? 0)
        ) {
            return $shortcutRecord->getRawRecord();
        }

        // record is localized - fetch the shortcut record translation, if available
        $shortcutRecordLocalization = $this->localizationRepository->getRecordTranslation($tableName, $shortcutRecord, $targetLanguage);
        return $shortcutRecordLocalization ?? $shortcutRecord->getRawRecord();
    }

    protected function getProcessedValue(GridColumnItem $item, string|array $fieldList, array &$info): void
    {
        $fieldArr = is_array($fieldList) ? $fieldList : explode(',', $fieldList);
        foreach ($fieldArr as $field) {
            $fieldValue = $this->fieldProcessor->prepareFieldWithLabel($item->getRecord(), $field);
            if ($fieldValue !== null) {
                $info[] = $fieldValue;
            }
        }
    }

    protected function getThumbCodeUnlinked(iterable|FileReference $fileReferences): string
    {
        return (string)$this->fieldProcessor->prepareFiles($fileReferences);
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
     *
     * @param string $input Input string
     * @return string Output string
     */
    protected function renderText(string $input): string
    {
        $input = strip_tags($input);
        $input = GeneralUtility::fixed_lgd_cs($input, 1500);
        return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
    }

    /**
     * Generates a list of selected pages or categories for the menu content types
     *
     * @param array $record row from pages
     */
    protected function generateListForMenuContentTypes(array $record, string $contentType): string
    {
        $table = 'pages';
        $field = 'pages';
        // get categories instead of pages
        if (str_contains($contentType, 'menu_categorized')) {
            $table = 'sys_category';
            $field = 'selected_categories';
        }
        if (trim($record[$field] ?? '') === '') {
            return '';
        }
        $content = '';
        $uidList = GeneralUtility::intExplode(',', $record[$field], true);
        foreach ($uidList as $uid) {
            $pageRecord = BackendUtility::getRecord($table, $uid);
            if ($pageRecord) {
                $title = BackendUtility::getRecordTitle($table, $pageRecord);
                $pathToContainingPage = BackendUtility::getRecordPath($pageRecord['pid'], $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW), 0);
                $content .= '<li class="list-group-item">' . htmlspecialchars($title) . ' <span class="text-variant">[' . $uid . '] ' . htmlspecialchars($pathToContainingPage) . '</span></li>';
            }
        }
        return $content ? '<ul class="list-group">' . $content . '</ul>' : '';
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the
     * RTE. Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor"
     * button
     *
     * @param string $linkText String to link. Must be prepared for HTML output.
     * @param RecordInterface $record The record.
     * @return string If the whole thing was editable and $linkText is not empty $linkText is returned with link
     *                around. Otherwise just $linkText.
     */
    protected function linkEditContent(string $linkText, RecordInterface $record): string
    {
        if (empty($linkText)) {
            return $linkText;
        }
        $table = $record->getMainType();
        $backendUser = $this->getBackendUser();
        if ($backendUser->check('tables_modify', $table)
            && $backendUser->checkRecordEditAccess($table, $record)->isAllowed
            && (new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $record->getPid()) ?? [])))->editContentPermissionIsGranted()
        ) {
            $urlParameters = [
                'edit' => [
                    $table => [
                        $record->getUid() => 'edit',
                    ],
                ],
                'module' => 'web_layout',
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() . '#element-' . $table . '-' . $record->getUid(),
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit')) . '">' . $linkText . '</a>';
        }
        return $linkText;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getIconFactory(): IconFactory
    {
        return GeneralUtility::makeInstance(IconFactory::class);
    }
}
