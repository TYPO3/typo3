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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Special type="user" element used in sys_file_storage is_public field
 *
 * @internal
 */
class UserSysFileStorageIsPublicElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    public function __construct(
        private readonly FlashMessageService $flashMessageService,
        private readonly StorageRepository $storageRepository,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * There are some edge cases where "is_public" can never be marked as true in the BE,
     * for instance, for storage located outside the document root or
     * for storages driven by special driver such as Flickr, ...
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $isPublic = (bool)$this->tcaSchemaFactory->get('sys_file_storage')->getField('is_public')->getDefaultValue();

        if ($this->data['command'] === 'edit') {
            // Make sure the storage object can be retrieved which is not the case when new storage.
            $lang = $this->getLanguageService();
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            try {
                $storage = $this->storageRepository->findByUid((int)$row['uid']);
                $storageRecord = $storage->getStorageRecord();
                $isPublic = $storage->isPublic() && $storageRecord['is_public'];

                // Display a warning to the BE User in case settings is not inline with storage capability.
                if ($storageRecord['is_public'] && !$storage->isPublic()) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.message.storage_is_no_public'),
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.header.storage_is_no_public'),
                        ContextualFeedbackSeverity::WARNING
                    );
                    $defaultFlashMessageQueue->enqueue($message);
                }
            } catch (InvalidPathException $e) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:filestorage.invalidpathexception.message'),
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:filestorage.invalidpathexception.title'),
                    ContextualFeedbackSeverity::ERROR
                );
                $defaultFlashMessageQueue->enqueue($message);
            }
        }

        $isPublicAsString = $isPublic ? '1' : '0';
        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->initializeResultArray();
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $checkboxParameters = $this->checkBoxParams(
            $parameterArray['itemFormElName'],
            $isPublic ? 1 : 0,
            0,
            1,
            $parameterArray['fieldChangeFunc'] ?? []
        );
        $checkboxId = htmlspecialchars(StringUtility::getUniqueId('formengine-fal-is-public-'));
        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] = $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-item-element">';
        $html[] =           '<div class="form-check form-switch">';
        $html[] =               '<input type="checkbox"';
        $html[] =                   ' class="form-check-input"';
        $html[] =                   ' value="1"';
        $html[] =                   ' data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] =                   ' id="' . $checkboxId . '"';
        $html[] =                   $checkboxParameters;
        $html[] =                   $isPublic ? ' checked="checked"' : '';
        $html[] =               '/>';
        $html[] =               '<label class="form-check-label" for="' . $checkboxId . '">';
        $html[] =                   $this->appendValueToLabelInDebugMode('', $isPublicAsString);
        $html[] =               '</label>';
        $html[] =               '<input type="hidden"';
        $html[] =                   ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $html[] =                   ' value="' . $isPublicAsString . '"';
        $html[] =               ' />';
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }
}
