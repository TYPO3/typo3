<?php

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

namespace TYPO3\CMS\Install\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Configuration\Cache\CacheFeature;
use TYPO3\CMS\Install\Configuration\Context\ContextFeature;
use TYPO3\CMS\Install\Configuration\Image\ImageFeature;
use TYPO3\CMS\Install\Configuration\Mail\MailFeature;
use TYPO3\CMS\Install\Configuration\PasswordHashing\PasswordHashingFeature;

/**
 * Instantiate and configure all known features and presets
 * @internal only to be used within EXT:install
 */
class FeatureManager
{
    /**
     * @var array List of feature class names
     */
    protected $featureRegistry = [
        CacheFeature::class,
        ContextFeature::class,
        ImageFeature::class,
        MailFeature::class,
        PasswordHashingFeature::class,
    ];

    /**
     * Get initialized list of features with possible presets
     *
     * @param array $postValues List of $POST values
     * @return FeatureInterface[]
     * @throws Exception
     */
    public function getInitializedFeatures(array $postValues = [])
    {
        $features = [];
        foreach ($this->featureRegistry as $featureClass) {
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
            $featureName = $feature->getName();
            $presets = $feature->getPresetsOrderedByPriority();
            foreach ($presets as $preset) {
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
            $presets = $feature->getPresetsOrderedByPriority();
            foreach ($presets as $preset) {
                // Only choose "normal" presets, no custom presets
                if ($preset instanceof CustomPresetInterface) {
                    break;
                }

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
