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

namespace TYPO3\CMS\Backend\Form\Container;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyFileReferenceControlsEvent;
use TYPO3\CMS\Backend\Form\Event\ModifyFileReferenceEnabledControlsEvent;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Render a single file reference.
 *
 * This container is called by FilesControlContainer to render a single file (reference). The container is also
 * called by FormEngine for an incoming ajax request to expand an existing file (reference) or to create a new one.
 *
 * This container creates the outer HTML of single file (references) - e.g. drag and drop and delete buttons.
 * For rendering of the record itself processing is handed over to FullRecordContainer.
 */
class FileReferenceContainer extends AbstractContainer
{
    public const NODE_TYPE_IDENTIFIER = 'fileReferenceContainer';

    private const FILE_REFERENCE_TABLE = 'sys_file_reference';
    private const FOREIGN_SELECTOR = 'uid_local';

    /**
     * File reference data used for JSON output
     */
    protected array $fileReferenceData = [];

    protected InlineStackProcessor $inlineStackProcessor;
    protected IconFactory $iconFactory;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
    }

    public function render(): array
    {
        $inlineStackProcessor = $this->inlineStackProcessor;
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

        // Send a mapping information to the browser via JSON:
        // e.g. data[<curTable>][<curId>][<curField>] => data-<pid>-<parentTable>-<parentId>-<parentField>-<curTable>-<curId>-<curField>
        $formPrefix = $inlineStackProcessor->getCurrentStructureFormPrefix();
        $domObjectId = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

        $this->fileReferenceData = $this->data['inlineData'];
        $this->fileReferenceData['map'][$formPrefix] = $domObjectId;

        $resultArray = $this->initializeResultArray();
        $resultArray['inlineData'] = $this->fileReferenceData;

        $html = '';
        $classes = [];
        $combinationHtml = '';
        $record = $this->data['databaseRow'];
        $uid = $record['uid'] ?? 0;
        $appendFormFieldNames = '[' . self::FILE_REFERENCE_TABLE . '][' . $uid . ']';
        $objectId = $domObjectId . '-' . self::FILE_REFERENCE_TABLE . '-' . $uid;
        $isNewRecord = $this->data['command'] === 'new';
        $hiddenFieldName = (string)($this->data['processedTca']['ctrl']['enablecolumns']['disabled'] ?? '');
        if (!$this->data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            if ($isNewRecord || $this->data['isInlineChildExpanded']) {
                $fileReferenceData = $this->renderFileReference($this->data);
                $html = $fileReferenceData['html'];
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fileReferenceData, false);
            } else {
                // This class is the marker for the JS-function to check if the full content has already been loaded
                $classes[] = 't3js-not-loaded';
            }
            if ($isNewRecord) {
                // Add pid of file reference as hidden field
                $html .= '<input type="hidden" name="data' . htmlspecialchars($appendFormFieldNames)
                    . '[pid]" value="' . (int)$record['pid'] . '"/>';
                // Tell DataHandler this file reference is expanded
                $ucFieldName = 'uc[inlineView]'
                    . '[' . $this->data['inlineTopMostParentTableName'] . ']'
                    . '[' . $this->data['inlineTopMostParentUid'] . ']'
                    . htmlspecialchars($appendFormFieldNames);
                $html .= '<input type="hidden" name="' . htmlspecialchars($ucFieldName)
                    . '" value="' . (int)$this->data['isInlineChildExpanded'] . '" />';
            } else {
                // Set additional field for processing for saving
                $html .= '<input type="hidden" name="cmd' . htmlspecialchars($appendFormFieldNames)
                    . '[delete]" value="1" disabled="disabled" />';
                if ($hiddenFieldName !== ''
                    && (!$this->data['isInlineChildExpanded']
                        || !in_array($hiddenFieldName, $this->data['columnsToProcess'], true))
                ) {
                    $isHidden = (bool)($record[$hiddenFieldName] ?? false);
                    $html .= '<input type="checkbox" class="d-none" data-formengine-input-name="data'
                        . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenFieldName) . ']" value="1"'
                        . ($isHidden ? ' checked="checked"' : '') . ' />';
                    $html .= '<input type="input" class="d-none" name="data' . htmlspecialchars($appendFormFieldNames)
                        . '[' . htmlspecialchars($hiddenFieldName) . ']" value="' . (int)$isHidden . '" />';
                }
            }
            // If this file reference should be shown collapsed
            $classes[] = $this->data['isInlineChildExpanded'] ? 'panel-visible' : 'panel-collapsed';
        }
        $hiddenFieldHtml = implode("\n", $resultArray['additionalHiddenFields'] ?? []);

        if ($this->data['inlineParentConfig']['renderFieldsOnly'] ?? false) {
            // Render "body" part only
            $resultArray['html'] = $html . $hiddenFieldHtml . $combinationHtml;
            return $resultArray;
        }

        // Render header row and content (if expanded)
        if ($this->data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            $classes[] = 't3-form-field-container-inline-placeHolder';
        }
        if ($record[$hiddenFieldName] ?? false) {
            $classes[] = 't3-form-field-container-inline-hidden';
        }
        if ($isNewRecord) {
            $classes[] = 'isNewFileReference';
        }

        // The hashed object id needs a non-numeric prefix, the value is used as ID selector in JavaScript
        $hashedObjectId = 'hash-' . md5($objectId);
        $containerAttributes = [
            'id' => $objectId . '_div',
            'class' => 'form-irre-object panel panel-default panel-condensed ' . trim(implode(' ', $classes)),
            'data-object-uid' => $record['uid'] ?? 0,
            'data-object-id' => $objectId,
            'data-object-id-hash' => $hashedObjectId,
            'data-object-parent-group' => $domObjectId . '-' . self::FILE_REFERENCE_TABLE,
            'data-field-name' => $appendFormFieldNames,
            'data-topmost-parent-table' => $this->data['inlineTopMostParentTableName'],
            'data-topmost-parent-uid' => $this->data['inlineTopMostParentUid'],
            'data-placeholder-record' => $this->data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ? '1' : '0',
        ];

        $ariaControls = htmlspecialchars($objectId . '_fields', ENT_QUOTES | ENT_HTML5);
        $resultArray['html'] = '
            <div ' . GeneralUtility::implodeAttributes($containerAttributes, true) . '>
                <div class="panel-heading" data-bs-toggle="formengine-file" id="' . htmlspecialchars($hashedObjectId, ENT_QUOTES | ENT_HTML5) . '_header" data-expandSingle="' . (($this->data['inlineParentConfig']['appearance']['expandSingle'] ?? false) ? 1 : 0) . '">
                    <div class="form-irre-header">
                        <div class="form-irre-header-cell form-irre-header-icon">
                            <span class="caret"></span>
                        </div>
                        ' . $this->renderFileHeader('aria-expanded="' . (($this->data['isInlineChildExpanded'] ?? false) ? 'true' : 'false') . '" aria-controls="' . $ariaControls . '"') . '
                    </div>
                </div>
                <div class="panel-collapse" id="' . $ariaControls . '">' . $html . $hiddenFieldHtml . $combinationHtml . '</div>
            </div>';

        return $resultArray;
    }

    protected function renderFileReference(array $data): array
    {
        $data['tabAndInlineStack'][] = [
            'inline',
            $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($data['inlineFirstPid'])
            . '-'
            . $data['tableName']
            . '-'
            . $data['databaseRow']['uid'],
        ];

        return $this->nodeFactory->create(array_replace_recursive($data, [
            'inlineData' => $this->fileReferenceData,
            'renderType' => 'fullRecordContainer',
        ]))->render();
    }

    /**
     * Renders the HTML header for the file, such as the title, toggle-function, drag'n'drop, etc.
     * Later on the command-icons are inserted here, too.
     */
    protected function renderFileHeader(string $ariaAttributesString): string
    {
        $languageService = $this->getLanguageService();

        $databaseRow = $this->data['databaseRow'];
        $recordTitle = $this->getRecordTitle();

        if (empty($recordTitle)) {
            $recordTitle = '<em>[' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</em>';
        }

        $objectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid'])
            . '-' . self::FILE_REFERENCE_TABLE
            . '-' . ($databaseRow['uid'] ?? 0);

        $altText = BackendUtility::getRecordIconAltText($databaseRow, self::FILE_REFERENCE_TABLE);

        // Renders a thumbnail for the header
        $thumbnail = '';
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) {
            $fileUid = $databaseRow[self::FOREIGN_SELECTOR][0]['uid'] ?? null;
            if (!empty($fileUid)) {
                try {
                    $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
                    if ($fileObject->isMissing()) {
                        $thumbnail = '
                            <span class="badge badge-danger">'
                                . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing')) . '
                            </span>&nbsp;
                            ' . htmlspecialchars($fileObject->getName()) . '
                            <br />';
                    } elseif ($fileObject->isImage() || $fileObject->isMediaFile()) {
                        $imageSetup = $this->data['inlineParentConfig']['appearance']['headerThumbnail'] ?? [];
                        $cropVariantCollection = CropVariantCollection::create($databaseRow['crop'] ?? '');
                        if (!$cropVariantCollection->getCropArea()->isEmpty()) {
                            $imageSetup['crop'] = $cropVariantCollection->getCropArea()->makeAbsoluteBasedOnFile($fileObject);
                        }
                        $processedImage = $fileObject->process(
                            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                            array_merge(['maxWidth' => '145', 'maxHeight' => '45'], $imageSetup)
                        );
                        // Only use a thumbnail if the processing process was successful by checking if image width is set
                        if ($processedImage->getProperty('width')) {
                            $imageUrl = $processedImage->getPublicUrl() ?? '';
                            $thumbnail = '<img src="' . htmlspecialchars($imageUrl) . '" ' .
                                'width="' . $processedImage->getProperty('width') . '" ' .
                                'height="' . $processedImage->getProperty('height') . '" ' .
                                'alt="' . $altText . '" ' .
                                'title="' . $altText . '" ' .
                                'loading="lazy">';
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $fileObject = null;
                }
            }
        }

        if ($thumbnail !== '') {
            $headerImage = '
                <div class="form-irre-header-thumbnail" id="' . $objectId . '_thumbnailcontainer">
                    ' . $thumbnail . '
                </div>';
        } else {
            $headerImage = '
                <div class="form-irre-header-icon" id="' . $objectId . '_iconcontainer">
                    ' . $this->iconFactory
                        ->getIconForRecord(self::FILE_REFERENCE_TABLE, $databaseRow, Icon::SIZE_SMALL)
                        ->setTitle($altText)
                        ->render() . '
                </div>';
        }

        // @todo check classes and change to dedicated file related ones if possible
        return '
            <button class="form-irre-header-cell form-irre-header-button" ' . $ariaAttributesString . '>
                ' . $headerImage . '
                <div class="form-irre-header-body">
                    <span id="' . $objectId . '_label">' . $recordTitle . '</span>
                </div>
            </button>
            <div class="form-irre-header-cell form-irre-header-control t3js-formengine-file-header-control">
                ' . $this->renderFileReferenceHeaderControl() . '
            </div>';
    }

    /**
     * Render the control-icons for a file reference (e.g. create new, sorting, delete, disable/enable).
     */
    protected function renderFileReferenceHeaderControl(): string
    {
        $controls = [];
        $databaseRow = $this->data['databaseRow'];
        $databaseRow += [
            'uid' => 0,
        ];
        $parentConfig = $this->data['inlineParentConfig'];
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();
        $isNewItem = str_starts_with((string)$databaseRow['uid'], 'NEW');
        $fileReferenceTableTca = $GLOBALS['TCA'][self::FILE_REFERENCE_TABLE];
        $calcPerms = new Permission(
            $backendUser->calcPerms(BackendUtility::readPageAccess(
                (int)($this->data['parentPageRow']['uid'] ?? 0),
                $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
            ))
        );
        $event = $this->eventDispatcher->dispatch(
            new ModifyFileReferenceEnabledControlsEvent($this->data, $databaseRow)
        );
        if ($this->data['isInlineDefaultLanguageRecordInLocalizedParentContext']) {
            $controls['localize'] = $this->iconFactory
                ->getIcon('actions-edit-localize-status-low', Icon::SIZE_SMALL)
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize.isLocalizable'))
                ->render();
        }
        if ($event->isControlEnabled('info')) {
            if ($isNewItem) {
                $controls['info'] = '
                    <span class="btn btn-default disabled">
                        ' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '
                    </span>';
            } else {
                $controls['info'] = '
                    <button type="button" class="btn btn-default" data-action="infowindow" data-info-table="' . htmlspecialchars('_FILE') . '" data-info-uid="' . (int)$databaseRow['uid_local'][0]['uid'] . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo')) . '">
                        ' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
        }
        // If the table is NOT a read-only table, then show these links:
        if (!($parentConfig['readOnly'] ?? false)
            && !($fileReferenceTableTca['ctrl']['readOnly'] ?? false)
            && !($this->data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)
        ) {
            if ($event->isControlEnabled('sort')) {
                $icon = 'actions-move-up';
                $class = '';
                if ((int)$parentConfig['inline']['first'] === (int)$databaseRow['uid']) {
                    $class = ' disabled';
                    $icon = 'empty-empty';
                }
                $controls['sort.up'] = '
                    <button type="button" class="btn btn-default' . $class . '" data-action="sort" data-direction="up" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveUp')) . '">
                        ' . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render() . '
                    </button>';

                $icon = 'actions-move-down';
                $class = '';
                if ((int)$parentConfig['inline']['last'] === (int)$databaseRow['uid']) {
                    $class = ' disabled';
                    $icon = 'empty-empty';
                }
                $controls['sort.down'] = '
                    <button type="button" class="btn btn-default' . $class . '" data-action="sort" data-direction="down" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveDown')) . '">
                        ' . $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
            if (!$isNewItem
                && ($languageField = ($GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'] ?? false))
                && $backendUser->check('tables_modify', 'sys_file_metadata')
                && $event->isControlEnabled('edit')
            ) {
                $languageId = (int)(is_array($databaseRow[$languageField] ?? null)
                    ? ($databaseRow[$languageField][0] ?? 0)
                    : ($databaseRow[$languageField] ?? 0));
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('sys_file_metadata');
                $metadataRecord = $queryBuilder
                    ->select('uid')
                    ->from('sys_file_metadata')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'file',
                            $queryBuilder->createNamedParameter((int)$databaseRow['uid_local'][0]['uid'], Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            $languageField,
                            $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                        )
                    )
                    ->setMaxResults(1)
                    ->executeQuery()
                    ->fetchAssociative();
                if (!empty($metadataRecord)) {
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                        'edit[sys_file_metadata][' . (int)$metadataRecord['uid'] . ']' => 'edit',
                        'returnUrl' => $this->data['returnUrl'],
                    ]);
                    $controls['edit'] = '
                        <a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata')) . '">
                            ' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '
                        </a>';
                }
            }
            if ($event->isControlEnabled('delete') && $calcPerms->editContentPermissionIsGranted()) {
                $recordInfo = $this->data['databaseRow']['uid_local'][0]['title'] ?? $this->data['recordTitle'] ?? '';
                if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [' . $this->data['tableName'] . ':' . $this->data['vanillaUid'] . ']';
                }
                $controls['delete'] = '
                    <button type="button" class="btn btn-default t3js-editform-delete-file-reference" data-record-info="' . htmlspecialchars(trim($recordInfo)) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:delete')) . '">
                        ' . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '
                    </button>';
            }
            if (($hiddenField = (string)($fileReferenceTableTca['ctrl']['enablecolumns']['disabled'] ?? '')) !== ''
                && ($fileReferenceTableTca['columns'][$hiddenField] ?? false)
                && $event->isControlEnabled('hide')
                && (
                    !($fileReferenceTableTca['columns'][$hiddenField]['exclude'] ?? false)
                    || $backendUser->check('non_exclude_fields', self::FILE_REFERENCE_TABLE . ':' . $hiddenField)
                )
            ) {
                if ($databaseRow[$hiddenField] ?? false) {
                    $controls['hide'] = '
                        <button type="button" class="btn btn-default t3js-toggle-visibility-button" data-hidden-field="' . htmlspecialchars($hiddenField) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:unHide')) . '">
                            ' . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '
                        </button>';
                } else {
                    $controls['hide'] = '
                        <button type="button" class="btn btn-default t3js-toggle-visibility-button" data-hidden-field="' . htmlspecialchars($hiddenField) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:hide')) . '">
                            ' . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '
                        </button>';
                }
            }
            if (($parentConfig['appearance']['useSortable'] ?? false) && $event->isControlEnabled('dragdrop')) {
                $controls['dragdrop'] = '
                    <span class="btn btn-default sortableHandle" data-id="' . (int)$databaseRow['uid'] . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move')) . '">
                        ' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
                    </span>';
            }
        } elseif (($this->data['isInlineDefaultLanguageRecordInLocalizedParentContext'] ?? false)
            && MathUtility::canBeInterpretedAsInteger($this->data['inlineParentUid'])
            && $event->isControlEnabled('localize')
        ) {
            $controls['localize'] = '
                <button type="button" class="btn btn-default t3js-synchronizelocalize-button" data-type="' . htmlspecialchars((string)$databaseRow['uid']) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:localize')) . '">
                    ' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
                </button>';
        }
        if ($lockInfo = BackendUtility::isRecordLocked(self::FILE_REFERENCE_TABLE, $databaseRow['uid'])) {
            $controls['locked'] = '
				<button type="button" class="btn btn-default" title="' . htmlspecialchars($lockInfo['msg']) . '">
					' . $this->iconFactory->getIcon('status-user-backend', Icon::SIZE_SMALL, 'overlay-edit')->render() . '
				</button>';
        }

        // Get modified controls. This means their markup was modified, new controls were added or controls got removed.
        $controls = $this->eventDispatcher->dispatch(
            new ModifyFileReferenceControlsEvent($controls, $this->data, $databaseRow)
        )->getControls();

        $out = '';
        if (($controls['edit'] ?? false) || ($controls['hide'] ?? false) || ($controls['delete'] ?? false)) {
            $out .= '
                <div class="btn-group btn-group-sm" role="group">
                    ' . ($controls['edit'] ?? '') . ($controls['hide'] ?? '') . ($controls['delete'] ?? '') . '
                </div>';
            unset($controls['edit'], $controls['hide'], $controls['delete']);
        }
        if (($controls['info'] ?? false) || ($controls['new'] ?? false) || ($controls['sort.up'] ?? false) || ($controls['sort.down'] ?? false) || ($controls['dragdrop'] ?? false)) {
            $out .= '
                <div class="btn-group btn-group-sm" role="group">
                    ' . ($controls['info'] ?? '') . ($controls['new'] ?? '') . ($controls['sort.up'] ?? '') . ($controls['sort.down'] ?? '') . ($controls['dragdrop'] ?? '') . '
                </div>';
            unset($controls['info'], $controls['new'], $controls['sort.up'], $controls['sort.down'], $controls['dragdrop']);
        }
        if ($controls['localize'] ?? false) {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $controls['localize'] . '</div>';
            unset($controls['localize']);
        }
        if ($controls !== [] && ($remainingControls = trim(implode('', $controls))) !== '') {
            $out .= '<div class="btn-group btn-group-sm" role="group">' . $remainingControls . '</div>';
        }
        return $out;
    }

    protected function getRecordTitle(): string
    {
        $databaseRow = $this->data['databaseRow'];
        $fileRecord = $databaseRow['uid_local'][0]['row'] ?? null;

        if ($fileRecord === null) {
            return $this->data['recordTitle'] ?: (string)$databaseRow['uid'];
        }

        $value = '';

        $recordTitle = $this->getTitleForRecord($databaseRow, $fileRecord);
        $recordName = $this->getLabelFieldForRecord($databaseRow, $fileRecord, 'name');

        $labelField = !empty($recordTitle) ? 'title' : 'name';

        if (!empty($recordTitle)) {
            $value .= $recordTitle . ' (' . $recordName . ')';
        } else {
            $value .= $recordName;
        }

        $title = '
            <dt class="col">
                ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.' . $labelField)) . '
            </dt>
            <dd class="col text-truncate">
                ' . $value . '
            </dd>';

        // In debug mode, add the table name to the record title
        if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
            $title .= '<div class="col"><code class="m-0">[' . self::FILE_REFERENCE_TABLE . ']</code></div>';
        }

        return '<dl class="row row-cols-auto gx-2">' . $title . '</dl>';
    }

    protected function getTitleForRecord(array $databaseRow, array $fileRecord): string
    {
        $fullTitle = '';
        if (isset($databaseRow['title'])) {
            $fullTitle = $databaseRow['title'];
        } elseif ($fileRecord['uid'] ?? false) {
            try {
                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                $metaData = $metaDataRepository->findByFileUid($fileRecord['uid']);
                $fullTitle = $metaData['title'] ?? '';
            } catch (InvalidUidException $e) {
            }
        }

        if ($fullTitle === '') {
            return '';
        }

        return BackendUtility::getRecordTitlePrep($fullTitle);
    }

    protected function getLabelFieldForRecord(array $databaseRow, array $fileRecord, string $field): string
    {
        $value = '';

        if (isset($databaseRow[$field])) {
            $value = htmlspecialchars((string)$databaseRow[$field]);
        } elseif (isset($fileRecord[$field])) {
            $value = BackendUtility::getRecordTitlePrep($fileRecord[$field]);
        }

        return $value;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
