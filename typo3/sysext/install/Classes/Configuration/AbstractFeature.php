<?php
namespace TYPO3\CMS\Install\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract feature class implements common code
 */
abstract class AbstractFeature
{
    /**
     * @var string Name of feature
     */
    protected $name = '';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [];

    /**
     * @var array Holds instances of presets
     */
    protected $presetInstances = [];

    /**
     * @var array List of $POST values
     */
    protected $postValues = [];

    /**
     * Initialize presets of feature
     *
     * @param array $postValues List of $POST values of this feature
     * @throws Exception
     */
    public function initializePresets(array $postValues)
    {
        // Give feature sub array of $POST values to preset and set to own property
        $featurePostValues = [];
        if (!empty($postValues[$this->name])) {
            $featurePostValues = $postValues[$this->name];
        }
        $this->postValues = $featurePostValues;

        $isNonCustomPresetActive = false;
        $customPresetFound = false;
        foreach ($this->presetRegistry as $presetClass) {
            /** @var PresetInterface $presetInstance */
            $presetInstance = GeneralUtility::makeInstance($presetClass);
            if (!($presetInstance instanceof PresetInterface)) {
                throw new Exception(
                    'Preset ' . $presetClass . ' does not implement PresetInterface',
                    1378644821
                );
            }

            $presetInstance->setPostValues($featurePostValues);

            // Custom preset is set active if no preset before is active
            if ($presetInstance->isActive()) {
                $isNonCustomPresetActive = true;
            }
            if ($presetInstance instanceof CustomPresetInterface
                && !$isNonCustomPresetActive
            ) {
                // Throw Exception if two custom presets are registered
                if ($customPresetFound === true) {
                    throw new Exception(
                        'Preset ' . $presetClass . ' implements CustomPresetInterface, but another'
                            . ' custom preset is already registered',
                        1378645039
                    );
                }

                /** @var CustomPresetInterface $presetInstance */
                $presetInstance->setActive();
                $customPresetFound = true;
            }

            $this->presetInstances[] = $presetInstance;
        }
    }

    /**
     * Return presets ordered by priority
     *
     * @return array<PresetInterface>
     * @throws Exception
     */
    public function getPresetsOrderedByPriority()
    {
        if (empty($this->presetInstances)) {
            throw new Exception(
                'Presets not initialized',
                1378645155
            );
        }
        $orderedPresets = [];
        foreach ($this->presetInstances as $presetInstance) {
            /** @var PresetInterface $presetInstance */
            $orderedPresets[$presetInstance->getPriority()] = $presetInstance;
        }
        krsort($orderedPresets, SORT_NUMERIC);
        return $orderedPresets;
    }

    /**
     * Return name of feature
     *
     * @return string Name of feature
     */
    public function getName()
    {
        return $this->name;
    }
}
