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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Use this to render certain fields (label + values) as kind of shortcut or helper
 * methods when implementing your own Preview Renderer.
 *
 * If you need your custom rendering, build your own renderer for your own PreviewRenderer
 * class that you can then inject.
 *
 * The result is always HTML, and it's always HSCed -> ready to be rendered.
 */
final class RecordFieldPreviewProcessor
{
    private array $itemLabels = [];

    public function __construct(
        private readonly TcaSchemaFactory $schemaFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly IconFactory $iconFactory,
    ) {}

    /**
     * Prepare the value of a field but prepend the label before.
     */
    public function prepareFieldWithLabel(RecordInterface $record, string $fieldName): ?string
    {
        if ($record->has($fieldName)) {
            $table = $record->getMainType();
            $value = $record->get($fieldName);
            if ($value !== '' && $value !== null) {
                $itemLabels = $this->getItemLabels($record);
                $fieldValue = BackendUtility::getProcessedValue($table, $fieldName, $value, 0, false, false, $record->getUid(), true, $record->getPid(), $record->getRawRecord()->toArray()) ?? '';
                return htmlspecialchars((string)($itemLabels[$fieldName] ?? '')) . ': ' . htmlspecialchars((string)$fieldValue);
            }
        }
        return null;
    }

    /**
     * Prepare the value of a field if it exists and it is not empty.
     */
    public function prepareField(RecordInterface $record, string $fieldName): ?string
    {
        if ($record->has($fieldName)) {
            $table = $record->getMainType();
            $value = $record->get($fieldName);
            if ($value !== '' && $value !== null) {
                $fieldValue = BackendUtility::getProcessedValue($table, $fieldName, $value, 0, false, false, $record->getUid(), true, $record->getPid(), $record->getRawRecord()->toArray()) ?? '';
                return htmlspecialchars((string)$fieldValue);
            }
        }
        return null;
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping, etc.
     */
    public function prepareText(RecordInterface $record, string $fieldName, int $maxLength = 1500): ?string
    {
        if ($record->has($fieldName)) {
            $input = $record->get($fieldName);
            if (is_string($input) && $input !== '') {
                $input = strip_tags($input);
                $input = GeneralUtility::fixed_lgd_cs($input, $maxLength);
                return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
            }
        }
        return null;
    }

    public function preparePlainHtml(RecordInterface $record, string $fieldName, int $maxLines = 100): ?string
    {
        if ($record->has($fieldName)) {
            $html = GeneralUtility::trimExplode(LF, (string)$record->get($fieldName), true);
            if ($html !== []) {
                $html = array_slice($html, 0, $maxLines);
                return str_replace(LF, '<br />', htmlspecialchars(implode(LF, $html)));
            }
        }
        return null;
    }

    /**
     * Render thumbnails for a file collection or files.
     */
    public function prepareFiles(iterable|FileReference $fileReferences): ?string
    {
        $thumbData = [];
        $fileReferences = $fileReferences instanceof FileReference ? [$fileReferences] : $fileReferences;
        foreach ($fileReferences as $fileReferenceObject) {
            // Do not show previews of hidden references
            if ($fileReferenceObject->getProperty('hidden')) {
                continue;
            }
            $fileObject = $fileReferenceObject->getOriginalFile();
            if ($fileObject->isMissing()) {
                $thumbData[] = $this->iconFactory
                    ->getIcon('mimetypes-other-other', IconSize::MEDIUM, 'overlay-missing')
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing') . ' ' . $fileObject->getName())
                    ->render();
                continue;
            }

            // Preview web image or media elements
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']
                && ($fileReferenceObject->getOriginalFile()->isImage() || $fileReferenceObject->getOriginalFile()->isMediaFile())
            ) {
                $cropVariantCollection = CropVariantCollection::create((string)$fileReferenceObject->getProperty('crop'));
                $cropArea = $cropVariantCollection->getCropArea();
                $processingConfiguration = [
                    'maxWidth' => 64,
                    'maxHeight' => 64,
                ];
                if (!$cropArea->isEmpty()) {
                    $processingConfiguration = [
                        'maxWidth' => 64,
                        'maxHeight' => 64,
                        'crop' => $cropArea->makeAbsoluteBasedOnFile($fileReferenceObject),
                    ];
                }
                $processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);
                $attributes = [
                    'src' => $processedImage->getPublicUrl() ?? '',
                    'width' => $processedImage->getProperty('width'),
                    'height' => $processedImage->getProperty('height'),
                    'alt' => $fileReferenceObject->getAlternative() ?: $fileReferenceObject->getName(),
                    'loading' => 'lazy',
                ];
                $thumbData[] = '<img ' . GeneralUtility::implodeAttributes($attributes, true) . '/>';
            } else {
                $thumbData[] = $this->iconFactory->getIconForResource($fileObject)->setTitle($fileObject->getName())->render();
            }
        }

        if ($thumbData !== []) {
            $result = '';
            foreach ($thumbData as $thumbDataItem) {
                $result .= '<div class="preview-thumbnails-element"><div class="preview-thumbnails-element-image">' . $thumbDataItem . '</div></div>';
            }
            return '<div class="preview-thumbnails">' . $result . '</div>';
        }
        return null;
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the
     * RTE. Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor"
     * button
     *
     * @param string $linkText String to link. Must be prepared for HTML output.
     * @return string If the whole thing was editable and $linkText is not empty $linkText is returned with the link
     *                around. Otherwise just $linkText.
     */
    public function linkToEditForm(string $linkText, RecordInterface $record, ServerRequestInterface $request): string
    {
        if ($linkText === '') {
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
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri() . '#element-' . $table . '-' . $record->getUid(),
            ];
            $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:edit')) . '">' . $linkText . '</a>';
        }
        return $linkText;
    }

    private function getItemLabels(RecordInterface $record): array
    {
        if (!isset($this->itemLabels[$record->getMainType()])) {
            $this->itemLabels[$record->getMainType()] = [];
            foreach ($this->schemaFactory->get($record->getMainType())->getFields() as $field) {
                $this->itemLabels[$record->getMainType()][$field->getName()] = $this->getLanguageService()->sL($field->getLabel());
            }
        }
        return $this->itemLabels[$record->getMainType()];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
