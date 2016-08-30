<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Utility\ArrayUtility;

/**
 * Generation of image manipulation TCEform element
 */
class ImageManipulationElement extends AbstractFormElement
{
    /**
     * Default element configuration
     *
     * @var array
     */
    protected $defaultConfig = [
        'file_field' => 'uid_local',
        'enableZoom' => false,
        'allowedExtensions' => null, // default: $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        'ratios' => [
            '1.7777777777777777' => 'LLL:EXT:lang/locallang_wizards.xlf:imwizard.ratio.16_9',
            '1.3333333333333333' => 'LLL:EXT:lang/locallang_wizards.xlf:imwizard.ratio.4_3',
            '1' => 'LLL:EXT:lang/locallang_wizards.xlf:imwizard.ratio.1_1',
            'NaN' => 'LLL:EXT:lang/locallang_wizards.xlf:imwizard.ratio.free',
        ]
    ];

    /**
     * This will render an imageManipulation field
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();
        $languageService = $this->getLanguageService();

        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];

        // If ratios are set do not add default options
        if (isset($parameterArray['fieldConf']['config']['ratios'])) {
            unset($this->defaultConfig['ratios']);
        }
        $config = ArrayUtility::arrayMergeRecursiveOverrule($this->defaultConfig, $parameterArray['fieldConf']['config']);

        // By default we allow all image extensions that can be handled by the GFX functionality
        if ($config['allowedExtensions'] === null) {
            $config['allowedExtensions'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        }

        if ($config['readOnly']) {
            $options = [];
            $options['parameterArray'] = [
                'fieldConf' => [
                    'config' => $config,
                ],
                'itemFormElValue' => $parameterArray['itemFormElValue'],
            ];
            $options['renderType'] = 'none';
            return $this->nodeFactory->create($options)->render();
        }

        $file = $this->getFile($row, $config['file_field']);
        if (!$file) {
            return $resultArray;
        }

        $content = '';
        $preview = '';
        if (GeneralUtility::inList(strtolower($config['allowedExtensions']), strtolower($file->getExtension()))) {

            // Get preview
            $preview = $this->getPreview($file, $parameterArray['itemFormElValue']);

            // Check if ratio labels hold translation strings
            foreach ((array)$config['ratios'] as $ratio => $label) {
                $config['ratios'][$ratio] = $languageService->sL($label, true);
            }

            $formFieldId = StringUtility::getUniqueId('formengine-image-manipulation-');
            $wizardData = [
                'zoom' => $config['enableZoom'] ? '1' : '0',
                'ratios' => json_encode($config['ratios']),
                'file' => $file->getUid(),
            ];
            $wizardData['token'] = GeneralUtility::hmac(implode('|', $wizardData), 'ImageManipulationWizard');

            $buttonAttributes = [
                'data-url' => BackendUtility::getAjaxUrl('wizard_image_manipulation', $wizardData),
                'data-severity' => 'notice',
                'data-image-name' => $file->getNameWithoutExtension(),
                'data-image-uid' => $file->getUid(),
                'data-file-field' => $config['file_field'],
                'data-field' => $formFieldId,
            ];

            $button = '<button class="btn btn-default t3js-image-manipulation-trigger"';
            foreach ($buttonAttributes as $key => $value) {
                $button .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
            $button .= '><span class="t3-icon fa fa-crop"></span>';
            $button .= $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.open-editor', true);
            $button .= '</button>';

            $inputField = '<input type="hidden" '
                . 'id="' . $formFieldId . '" '
                . 'name="' . $parameterArray['itemFormElName'] . '" '
                . 'value="' . htmlspecialchars($parameterArray['itemFormElValue']) . '" />';

            $content .= $inputField . $button;

            $content .= $this->getImageManipulationInfoTable($parameterArray['itemFormElValue']);

            $resultArray['requireJsModules'][] = [
                'TYPO3/CMS/Backend/ImageManipulation' => 'function(ImageManipulation){ImageManipulation.initializeTrigger()}'
            ];
        }

        $content .= '<p class="text-muted"><em>' . $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.supported-types-message', true) . '<br />';
        $content .= strtoupper(implode(', ', GeneralUtility::trimExplode(',', $config['allowedExtensions'])));
        $content .= '</em></p>';

        $item = '<div class="media">';
        $item .= $preview;
        $item .= '<div class="media-body">' . $content . '</div>';
        $item .= '</div>';

        $resultArray['html'] = $item;
        return $resultArray;
    }

    /**
     * Get file object
     *
     * @param array $row
     * @param string $fieldName
     * @return NULL|\TYPO3\CMS\Core\Resource\File
     */
    protected function getFile(array $row, $fieldName)
    {
        $file = null;
        $fileUid = !empty($row[$fieldName]) ? $row[$fieldName] : null;
        if (strpos($fileUid, 'sys_file_') === 0) {
            if (strpos($fileUid, '|')) {
                // @todo: uid_local is a group field that was resolved to table_uid|target - split here again
                // @todo: this will vanish if group fields are moved to array
                $fileUid = explode('|', $fileUid);
                $fileUid = $fileUid[0];
            }
            $fileUid = substr($fileUid, 9);
        }
        if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
            try {
                $file = ResourceFactory::getInstance()->getFileObject($fileUid);
            } catch (FileDoesNotExistException $e) {
            } catch (\InvalidArgumentException $e) {
            }
        }
        return $file;
    }

    /**
     * Get preview image if cropping is set
     *
     * @param File $file
     * @param string $crop
     * @return string
     */
    public function getPreview(File $file, $crop)
    {
        $thumbnail = '';
        $maxWidth = 150;
        $maxHeight = 200;
        if ($crop) {
            $imageSetup = ['maxWidth' => $maxWidth, 'maxHeight' => $maxHeight, 'crop' => $crop];
            $processedImage = $file->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
            // Only use a thumbnail if the processing process was successful by checking if image width is set
            if ($processedImage->getProperty('width')) {
                $imageUrl = $processedImage->getPublicUrl(true);
                $thumbnail = '<img src="' . $imageUrl . '" ' .
                    'class="thumbnail thumbnail-status" ' .
                    'width="' . $processedImage->getProperty('width') . '" ' .
                    'height="' . $processedImage->getProperty('height') . '" >';
            }
        }

        $preview = '<div class="media-left">';
        $preview .= '<div class="t3js-image-manipulation-preview media-object' . ($thumbnail ? '' : ' hide') . '" ';
        // Set preview width/height needed by cropper
        $preview .= 'data-preview-width="' . $maxWidth . '" data-preview-height="' . $maxHeight . '">';
        $preview .= $thumbnail;
        $preview .= '</div></div>';

        return $preview;
    }

    /**
     * Get image manipulation info table
     *
     * @param string $rawImageManipulationValue
     * @return string
     */
    protected function getImageManipulationInfoTable($rawImageManipulationValue)
    {
        $content = '';
        $imageManipulation = null;
        $x = $y = $width = $height = 0;

        // Determine cropping values
        if ($rawImageManipulationValue) {
            $imageManipulation = json_decode($rawImageManipulationValue);
            if (is_object($imageManipulation)) {
                $x = (int)$imageManipulation->x;
                $y = (int)$imageManipulation->y;
                $width = (int)$imageManipulation->width;
                $height = (int)$imageManipulation->height;
            } else {
                $imageManipulation = null;
            }
        }
        $languageService = $this->getLanguageService();

        $content .= '<div class="table-fit-block table-spacer-wrap">';
        $content .= '<table class="table table-no-borders t3js-image-manipulation-info' . ($imageManipulation === null ? ' hide' : '') . '">';
        $content .= '<tr><td>' . $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.crop-x', true) . '</td>';
        $content .= '<td class="t3js-image-manipulation-info-crop-x">' . $x . 'px</td></tr>';
        $content .= '<tr><td>' . $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.crop-y', true) . '</td>';
        $content .= '<td class="t3js-image-manipulation-info-crop-y">' . $y . 'px</td></tr>';
        $content .= '<tr><td>' . $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.crop-width', true) . '</td>';
        $content .= '<td class="t3js-image-manipulation-info-crop-width">' . $width . 'px</td></tr>';
        $content .= '<tr><td>' . $languageService->sL('LLL:EXT:lang/locallang_wizards.xlf:imwizard.crop-height', true) . '</td>';
        $content .= '<td class="t3js-image-manipulation-info-crop-height">' . $height . 'px</td></tr>';
        $content .= '</table>';
        $content .= '</div>';

        return $content;
    }
}
