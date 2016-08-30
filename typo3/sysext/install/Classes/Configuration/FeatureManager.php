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
 * Instantiate and configure all known features and presets
 */
class FeatureManager
{
    /**
     * @var array List of feature class names
     */
    protected $featureRegistry = [
        \TYPO3\CMS\Install\Configuration\Context\ContextFeature::class,
        \TYPO3\CMS\Install\Configuration\Image\ImageFeature::class,
        \TYPO3\CMS\Install\Configuration\ExtbaseObjectCache\ExtbaseObjectCacheFeature::class,
        \TYPO3\CMS\Install\Configuration\Mail\MailFeature::class,
    ];

    /**
     * Get initialized list of features with possible presets
     *
     * @param array $postValues List of $POST values
     * @return array<FeatureInterface>
     * @throws Exception
     */
    public function getInitializedFeatures(array $postValues)
    {
        $features = [];
        foreach ($this->featureRegistry as $featureClass) {
            /** @var FeatureInterface $featureInstance */
            $featureInstance = GeneralUtility::makeInstance($featureClass);
            if (!($featureInstance instanceof FeatureInterface)) {
                throw new Exception(
                    'Feature ' . $featureClass . ' does not implement FeatureInterface',
                    1378644593
                );
            }
            $featureInstance->initializePresets($postValues);
            $features[] = $featureInstance;
        }
        return $features;
    }

    /**
     * Get configuration values to be set to LocalConfiguration from
     * list of selected $POST feature presets
     *
     * @param array $postValues List of $POST values
     * @return array List of configuration values
     */
    public function getConfigurationForSelectedFeaturePresets(array $postValues)
    {
        $localConfigurationValuesToSet = [];
        $features = $this->getInitializedFeatures($postValues);
        foreach ($features as $feature) {
            /** @var FeatureInterface $feature */
            $featureName = $feature->getName();
            $presets = $feature->getPresetsOrderedByPriority();
            foreach ($presets as $preset) {
                /** @var PresetInterface $preset */
                $presetName = $preset->getName();
                if (!empty($postValues[$featureName]['enable'])
                    && $postValues[$featureName]['enable'] === $presetName
                    && (!$preset->isActive() || $preset instanceof CustomPresetInterface)
                ) {
                    $localConfigurationValuesToSet = array_merge(
                        $localConfigurationValuesToSet,
                        $preset->getConfigurationValues()
                    );
                }
            }
        }
        return $localConfigurationValuesToSet;
    }

    /**
     * Cycle through features and get settings. First matching
     * preset (highest priority) will be selected.
     *
     * @return array Configuration settings
     */
    public function getBestMatchingConfigurationForAllFeatures()
    {
        $localConfigurationValuesToSet = [];
        $features = $this->getInitializedFeatures([]);
        foreach ($features as $feature) {
            /** @var FeatureInterface $feature */
            $presets = $feature->getPresetsOrderedByPriority();
            foreach ($presets as $preset) {
                // Only choose "normal" presets, no custom presets
                if ($preset instanceof CustomPresetInterface) {
                    break;
                }

                /** @var PresetInterface $preset */
                if ($preset->isAvailable()) {
                    $localConfigurationValuesToSet = array_merge(
                        $localConfigurationValuesToSet,
                        $preset->getConfigurationValues()
                    );
                    // Setting for this feature done, go to next feature
                    break;
                }
            }
        }
        return $localConfigurationValuesToSet;
    }
}
