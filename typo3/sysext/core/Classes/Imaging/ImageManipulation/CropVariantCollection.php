<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Imaging\ImageManipulation;

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

use TYPO3\CMS\Core\Resource\FileInterface;

class CropVariantCollection
{
    /**
     * @var CropVariant[]
     */
    protected $cropVariants;

    /**
     * @param CropVariant[] cropVariants
     * @throws \TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException
     */
    public function __construct(array $cropVariants)
    {
        $this->setCropVariants(...$cropVariants);
    }

    /**
     * @param string $jsonString
     * @param array $tcaConfig
     * @return CropVariantCollection
     */
    public static function create(string $jsonString, array $tcaConfig = []): CropVariantCollection
    {
        $persistedCollectionConfig = empty($jsonString) ? [] : json_decode($jsonString, true);
        if (empty($persistedCollectionConfig) && empty($tcaConfig)) {
            return self::createEmpty();
        }
        try {
            if ($tcaConfig === []) {
                $tcaConfig = (array)$persistedCollectionConfig;
            } else {
                if (!is_array($persistedCollectionConfig)) {
                    $persistedCollectionConfig = [];
                }
                // Merge selected areas with crop tool configuration
                reset($persistedCollectionConfig);
                foreach ($tcaConfig as $id => &$cropVariantConfig) {
                    if (!isset($persistedCollectionConfig[$id])) {
                        $id = key($persistedCollectionConfig);
                        next($persistedCollectionConfig);
                    }
                    if (isset($persistedCollectionConfig[$id]['cropArea'])) {
                        $cropVariantConfig['cropArea'] = $persistedCollectionConfig[$id]['cropArea'];
                    }
                    if (isset($persistedCollectionConfig[$id]['focusArea'], $cropVariantConfig['focusArea'])) {
                        $cropVariantConfig['focusArea'] = $persistedCollectionConfig[$id]['focusArea'];
                    }
                    if (isset($persistedCollectionConfig[$id]['selectedRatio'], $cropVariantConfig['allowedAspectRatios'][$persistedCollectionConfig[$id]['selectedRatio']])) {
                        $cropVariantConfig['selectedRatio'] = $persistedCollectionConfig[$id]['selectedRatio'];
                    }
                }
                unset($cropVariantConfig);
            }
            $cropVariants = [];
            foreach ($tcaConfig as $id => $cropVariantConfig) {
                $cropVariants[] = CropVariant::createFromConfiguration($id, $cropVariantConfig);
            }
            return new self($cropVariants);
        } catch (\Throwable $throwable) {
            return self::createEmpty();
        }
    }

    /**
     * @return array
     * @internal
     */
    public function asArray(): array
    {
        $cropVariantsAsArray = [];
        foreach ($this->cropVariants as $id => $cropVariant) {
            $cropVariantsAsArray[$id] = $cropVariant->asArray();
        }
        return $cropVariantsAsArray;
    }

    /**
     * @param FileInterface $file
     * @return CropVariantCollection
     */
    public function applyRatioRestrictionToSelectedCropArea(FileInterface $file): CropVariantCollection
    {
        $newCollection = clone $this;
        foreach ($this->cropVariants as $id => $cropVariant) {
            $newCollection->cropVariants[$id] = $cropVariant->applyRatioRestrictionToSelectedCropArea($file);
        }
        return $newCollection;
    }

    public function __toString()
    {
        $filterNonPersistentKeys = function ($key) {
            if (in_array($key, ['id', 'title', 'allowedAspectRatios', 'coverAreas'], true)) {
                return false;
            }
            return true;
        };
        $cropVariantsAsArray = [];
        foreach ($this->cropVariants as $id => $cropVariant) {
            $cropVariantsAsArray[$id] = array_filter($cropVariant->asArray(), $filterNonPersistentKeys, ARRAY_FILTER_USE_KEY);
        }
        return json_encode($cropVariantsAsArray);
    }

    /**
     * @param string $id
     * @return Area
     */
    public function getCropArea(string $id = 'default'): Area
    {
        if (isset($this->cropVariants[$id])) {
            return $this->cropVariants[$id]->getCropArea();
        }
        return Area::createEmpty();
    }

    /**
     * @param string $id
     * @return Area
     */
    public function getFocusArea(string $id = 'default'): Area
    {
        if (isset($this->cropVariants[$id]) && $this->cropVariants[$id]->getFocusArea() !== null) {
            return $this->cropVariants[$id]->getFocusArea();
        }
        return Area::createEmpty();
    }

    /**
     * @return CropVariantCollection
     */
    protected static function createEmpty(): CropVariantCollection
    {
        return new self([]);
    }

    /**
     * @param CropVariant[] ...$cropVariants
     * @throws \TYPO3\CMS\Core\Imaging\ImageManipulation\InvalidConfigurationException
     */
    protected function setCropVariants(CropVariant ...$cropVariants)
    {
        $this->cropVariants = [];
        foreach ($cropVariants as $cropVariant) {
            $this->addCropVariant($cropVariant);
        }
    }

    /**
     * @param CropVariant $cropVariant
     * @throws InvalidConfigurationException
     */
    protected function addCropVariant(CropVariant $cropVariant)
    {
        if (isset($this->cropVariants[$cropVariant->getId()])) {
            throw new InvalidConfigurationException(sprintf('Crop variant with with duplicate ID (%s) is configured. Make sure all configured cropVariants have different ids.', $cropVariant->getId()), 1485284352);
        }
        $this->cropVariants[$cropVariant->getId()] = $cropVariant;
    }
}
