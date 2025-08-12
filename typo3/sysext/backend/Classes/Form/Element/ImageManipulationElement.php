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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Ratio;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of image manipulation FormEngine element.
 * This is typically used in FAL relations to cut images.
 */
class ImageManipulationElement extends AbstractFormElement
{
    private string $wizardRouteName = 'ajax_wizard_image_manipulation';

    /**
     * Default element configuration
     *
     * @var array
     */
    protected static $defaultConfig = [
        'file_field' => 'uid_local',
        'allowedExtensions' => null, // default: $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
        'cropVariants' => [
            'default' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
                'allowedAspectRatios' => [
                    '16:9' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.16_9',
                        'value' => 16 / 9,
                    ],
                    '3:2' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.3_2',
                        'value' => 3 / 2,
                    ],
                    '4:3' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.4_3',
                        'value' => 4 / 3,
                    ],
                    '1:1' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.1_1',
                        'value' => 1.0,
                    ],
                    'NaN' => [
                        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.ratio.free',
                        'value' => 0.0,
                    ],
                ],
                'selectedRatio' => 'NaN',
                'cropArea' => [
                    'x' => 0.0,
                    'y' => 0.0,
                    'width' => 1.0,
                    'height' => 1.0,
                ],
            ],
        ],
    ];

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

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageThumbnails' => [
            'renderType' => 'otherLanguageThumbnails',
            'after' => [
                'localizationStateSelector',
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageThumbnails',
            ],
        ],
    ];

    public function __construct(
        private readonly BackendViewFactory $backendViewFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ResourceFactory $resourceFactory,
        private readonly HashService $hashService,
    ) {}

    /**
     * This will render an imageManipulation field
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     * @throws InvalidConfigurationException
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $config = $this->populateConfiguration($parameterArray['fieldConf']['config']);

        $file = $this->getFile($this->data['databaseRow'], $config['file_field']);
        if (!$file) {
            // Early return in case we do not find a file
            return $resultArray;
        }

        $config = $this->processConfiguration($config, $parameterArray['itemFormElValue'], $file);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $arguments = [
            'fieldInformation' => $fieldInformationHtml,
            'fieldControl' => $fieldControlHtml,
            'fieldWizard' => $fieldWizardHtml,
            'isAllowedFileExtension' => in_array(strtolower($file->getExtension()), GeneralUtility::trimExplode(',', strtolower($config['allowedExtensions'])), true),
            'image' => $file,
            'formEngine' => [
                'field' => [
                    'value' => $parameterArray['itemFormElValue'],
                    'name' => $parameterArray['itemFormElName'],
                ],
                'validation' => '[]',
            ],
            'config' => $config,
            'wizardUri' => $this->getWizardUri(),
            'wizardPayload' => json_encode($this->getWizardPayload($config['cropVariants'], $file)),
            'previewUrl' => $this->eventDispatcher->dispatch(
                new ModifyImageManipulationPreviewUrlEvent($this->data['databaseRow'], $config, $file)
            )->getPreviewUrl(),
        ];

        if ($arguments['isAllowedFileExtension']) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
                '@typo3/backend/image-manipulation.js'
            )->invoke('initializeTrigger');
            $arguments['formEngine']['field']['id'] = StringUtility::getUniqueId('formengine-image-manipulation-');
            if ($config['required'] ?? false) {
                $arguments['formEngine']['validation'] = $this->getValidationDataAsJsonString(['required' => true]);
            }
        }
        $view = $this->backendViewFactory->create($this->data['request']);
        $view->assignMultiple($arguments);
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend($view->render('Form/ImageManipulationElement'));

        return $resultArray;
    }

    /**
     * Get file object
     *
     * @param string $fieldName
     * @return File|null
     */
    protected function getFile(array $row, $fieldName)
    {
        $file = null;
        $fileUid = !empty($row[$fieldName]) ? $row[$fieldName] : null;
        if (is_array($fileUid) && isset($fileUid[0]['uid'])) {
            $fileUid = $fileUid[0]['uid'];
        }
        if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
            } catch (FileDoesNotExistException|\InvalidArgumentException) {
            }
        }
        return $file;
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function populateConfiguration(array $baseConfiguration)
    {
        $defaultConfig = self::$defaultConfig;

        // If ratios are set do not add default options
        if (isset($baseConfiguration['cropVariants'])) {
            unset($defaultConfig['cropVariants']);
        }

        $config = array_replace_recursive($defaultConfig, $baseConfiguration);

        if (!is_array($config['cropVariants'])) {
            throw new InvalidConfigurationException('Crop variants configuration must be an array', 1485377267);
        }

        $cropVariants = [];
        foreach ($config['cropVariants'] as $id => $cropVariant) {
            // Filter allowed aspect ratios
            $cropVariant['allowedAspectRatios'] = array_filter($cropVariant['allowedAspectRatios'] ?? [], static function (array $aspectRatio): bool {
                return !(bool)($aspectRatio['disabled'] ?? false);
            });

            // Aspect ratios may not contain a "." character, see Ratio::__construct()
            // To match them again properly, same replacement is required here.
            $preparedAllowedAspectRatios = [];
            foreach ($cropVariant['allowedAspectRatios'] as $aspectRatio => $aspectRatioDefinition) {
                $preparedAllowedAspectRatios[Ratio::prepareAspectRatioId($aspectRatio)] = $aspectRatioDefinition;
            }
            $cropVariant['allowedAspectRatios'] = $preparedAllowedAspectRatios;

            // Ignore disabled crop variants
            if (!empty($cropVariant['disabled'])) {
                continue;
            }

            if (empty($cropVariant['allowedAspectRatios'])) {
                throw new InvalidConfigurationException('Crop variants configuration ' . $id . ' contains no allowed aspect ratios', 1620147893);
            }

            // Enforce a crop area (default is full image)
            if (empty($cropVariant['cropArea'])) {
                $cropVariant['cropArea'] = Area::createEmpty()->asArray();
            }

            $cropVariants[$id] = $cropVariant;
        }

        $config['cropVariants'] = $cropVariants;

        // By default we allow all image extensions that can be handled by the GFX functionality
        $config['allowedExtensions'] ??= $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        return $config;
    }

    /**
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function processConfiguration(array $config, string &$elementValue, File $file)
    {
        $cropVariantCollection = CropVariantCollection::create($elementValue, $config['cropVariants']);
        if (empty($config['readOnly']) && !empty($file->getProperty('width'))) {
            $cropVariantCollection = $cropVariantCollection->applyRatioRestrictionToSelectedCropArea($file);
            $elementValue = (string)$cropVariantCollection;
        }
        $config['cropVariants'] = $cropVariantCollection->asArray();
        $config['allowedExtensions'] = implode(', ', GeneralUtility::trimExplode(',', $config['allowedExtensions'], true));
        return $config;
    }

    protected function getWizardUri(): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute($this->wizardRouteName);
    }

    protected function getWizardPayload(array $cropVariants, File $image): array
    {
        $uriArguments = [];
        $arguments = [
            'cropVariants' => $cropVariants,
            'image' => $image->getUid(),
        ];
        $uriArguments['arguments'] = json_encode($arguments);
        $uriArguments['signature'] = $this->hashService->hmac((string)$uriArguments['arguments'], $this->wizardRouteName);

        return $uriArguments;
    }
}
