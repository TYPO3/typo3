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

namespace TYPO3\CMS\Backend\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render cropped thumbnails for default and additional preview
 * languages by respecting the corresponding cropping configuration.
 *
 * @internal
 */
class OtherLanguageThumbnails extends AbstractNode
{
    /**
     * Render cropped thumbnails from other language rows
     *
     * @return array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();
        $fieldConfig = $this->data['parameterArray']['fieldConf'];
        $l10nDisplay = $fieldConfig['l10n_display'] ?? '';
        $cropVariants = $fieldConfig['config']['cropVariants'] ?? ['default' => []];
        $defaultLanguageRow = $this->data['defaultLanguageRow'] ?? null;

        if (!is_array($defaultLanguageRow)
            || !is_array($cropVariants)
            || $cropVariants === []
            || $fieldConfig['config']['type'] !== 'imageManipulation'
            || GeneralUtility::inList($l10nDisplay, 'hideDiff')
            || GeneralUtility::inList($l10nDisplay, 'defaultAsReadonly')
        ) {
            return $result;
        }

        $html = [];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $languages = [$defaultLanguageRow['sys_language_uid'] => $defaultLanguageRow] + ($this->data['additionalLanguageRows'] ?? []);

        foreach ($languages as $sysLanguageUid => $languageRow) {
            $file = null;
            $fileUid = (int)($languageRow['uid_local'] ?? 0);

            if (!$fileUid || $languageRow['table_local'] !== 'sys_file') {
                continue;
            }

            try {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObject($fileUid);
            } catch (FileDoesNotExistException|\InvalidArgumentException $e) {
                continue;
            }

            $processedImages = [];
            $cropVariantCollection = CropVariantCollection::create((string)($languageRow['crop'] ?? ''), $cropVariants);

            foreach (array_keys($cropVariants) as $variant) {
                $processedImages[] = FormEngineUtility::getIconHtml(
                    $file
                        ->process(
                            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                            [
                                'maxWidth' => '145',
                                'maxHeight' => '45',
                                'crop' => $cropVariantCollection->getCropArea($variant)->makeAbsoluteBasedOnFile($file),
                            ]
                        )
                        ->getPublicUrl() ?? '',
                    $languageRow['title'] ?? $file->getProperty('title') ?? '',
                    $languageRow['alternative'] ?? $file->getProperty('alternative') ?? ''
                );
            }

            if ($processedImages !== []) {
                $iconIdentifier = $this->data['systemLanguageRows'][(int)$sysLanguageUid]['flagIconIdentifier'] ?? 'flags-multiple';
                $html[] = '<div class="t3-form-original-language">';
                $html[] =   $iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
                $html[] =   implode(LF, $processedImages);
                $html[] = '</div>';
            }
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
