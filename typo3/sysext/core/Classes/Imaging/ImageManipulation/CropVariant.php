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

namespace TYPO3\CMS\Core\Imaging\ImageManipulation;

use TYPO3\CMS\Core\Resource\FileInterface;

class CropVariant
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var Area
     */
    protected $cropArea;

    /**
     * @var Ratio[]
     */
    protected $allowedAspectRatios;

    /**
     * @var string
     */
    protected $selectedRatio;

    /**
     * @var Area|null
     */
    protected $focusArea;

    /**
     * @var Area[]|null
     */
    protected $coverAreas;

    /**
     * @param string $id
     * @param string $title
     * @param Area $cropArea
     * @param Ratio[] $allowedAspectRatios
     * @param string|null $selectedRatio
     * @param Area|null $focusArea
     * @param Area[]|null $coverAreas
     * @throws InvalidConfigurationException
     */
    public function __construct(
        string $id,
        string $title,
        Area $cropArea,
        array $allowedAspectRatios = null,
        string $selectedRatio = null,
        Area $focusArea = null,
        array $coverAreas = null
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->cropArea = $cropArea;
        if ($allowedAspectRatios) {
            $this->setAllowedAspectRatios(...$allowedAspectRatios);
            if ($selectedRatio && isset($this->allowedAspectRatios[$selectedRatio])) {
                $this->selectedRatio = $selectedRatio;
            } else {
                $this->selectedRatio = current($this->allowedAspectRatios)->getId();
            }
        }
        $this->focusArea = $focusArea;
        if ($coverAreas !== null) {
            $this->setCoverAreas(...$coverAreas);
        }
    }

    /**
     * @param string $id
     * @param array $config
     * @return CropVariant
     * @throws InvalidConfigurationException
     */
    public static function createFromConfiguration(string $id, array $config): CropVariant
    {
        try {
            return new self(
                $id,
                $config['title'] ?? '',
                Area::createFromConfiguration($config['cropArea']),
                isset($config['allowedAspectRatios']) ? Ratio::createMultipleFromConfiguration($config['allowedAspectRatios']) : null,
                $config['selectedRatio'] ?? null,
                isset($config['focusArea']) ? Area::createFromConfiguration($config['focusArea']) : null,
                isset($config['coverAreas']) ? Area::createMultipleFromConfiguration($config['coverAreas']) : null
            );
        } catch (\Throwable $throwable) {
            throw new InvalidConfigurationException(sprintf('Invalid type in configuration for crop variant: %s', $throwable->getMessage()), 1485278693, $throwable);
        }
    }

    /**
     * @return array
     * @internal
     */
    public function asArray(): array
    {
        $coverAreasAsArray = null;
        $allowedAspectRatiosAsArray = [];
        foreach ($this->allowedAspectRatios ?? [] as $id => $allowedAspectRatio) {
            $allowedAspectRatiosAsArray[$id] = $allowedAspectRatio->asArray();
        }
        if ($this->coverAreas !== null) {
            $coverAreasAsArray = [];
            foreach ($this->coverAreas as $coverArea) {
                $coverAreasAsArray[] = $coverArea->asArray();
            }
        }
        return [
            'id' => $this->id,
            'title' => $this->title,
            'cropArea' => $this->cropArea->asArray(),
            'allowedAspectRatios' => $allowedAspectRatiosAsArray,
            'selectedRatio' => $this->selectedRatio,
            'focusArea' => $this->focusArea ? $this->focusArea->asArray() : null,
            'coverAreas' => $coverAreasAsArray ?? null,
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return Area
     */
    public function getCropArea(): Area
    {
        return $this->cropArea;
    }

    /**
     * @return Area|null
     */
    public function getFocusArea()
    {
        return $this->focusArea;
    }

    /**
     * @param FileInterface $file
     * @return CropVariant
     */
    public function applyRatioRestrictionToSelectedCropArea(FileInterface $file): CropVariant
    {
        if (!$this->selectedRatio) {
            return $this;
        }
        $newVariant = clone $this;
        $newArea = $this->cropArea->makeAbsoluteBasedOnFile($file);
        $newArea = $newArea->applyRatioRestriction($this->allowedAspectRatios[$this->selectedRatio]);
        $newVariant->cropArea = $newArea->makeRelativeBasedOnFile($file);
        return $newVariant;
    }

    /**
     * @param Ratio ...$ratios
     * @throws InvalidConfigurationException
     */
    protected function setAllowedAspectRatios(Ratio ...$ratios)
    {
        $this->allowedAspectRatios = [];
        foreach ($ratios as $ratio) {
            $this->addAllowedAspectRatio($ratio);
        }
    }

    /**
     * @param Ratio $ratio
     * @throws InvalidConfigurationException
     */
    protected function addAllowedAspectRatio(Ratio $ratio)
    {
        if (isset($this->allowedAspectRatios[$ratio->getId()])) {
            throw new InvalidConfigurationException(sprintf('Ratio with with duplicate ID (%s) is configured. Make sure all configured ratios have different ids.', $ratio->getId()), 1485274618);
        }
        $this->allowedAspectRatios[$ratio->getId()] = $ratio;
    }

    /**
     * @param Area ...$areas
     * @throws InvalidConfigurationException
     */
    protected function setCoverAreas(Area ...$areas)
    {
        $this->coverAreas = [];
        foreach ($areas as $area) {
            $this->addCoverArea($area);
        }
    }

    /**
     * @param Area $area
     * @throws InvalidConfigurationException
     */
    protected function addCoverArea(Area $area)
    {
        $this->coverAreas[] = $area;
    }
}
