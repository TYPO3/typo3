<?php
namespace TYPO3\CMS\Install\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Instantiate and configure all known features and presets
 */
class FeatureManager {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager = NULL;

	/**
	 * @var array List of feature class names
	 */
	protected $featureRegistry = array(
		'TYPO3\\CMS\\Install\\Configuration\\Charset\\CharsetFeature',
		'TYPO3\\CMS\\Install\\Configuration\\Context\\ContextFeature',
		'TYPO3\\CMS\\Install\\Configuration\\Image\\ImageFeature',
		'TYPO3\\CMS\\Install\\Configuration\\ExtbaseObjectCache\\ExtbaseObjectCacheFeature',
	);

	/**
	 * Get initialized list of features with possible presets
	 *
	 * @param array $postValues List of $POST values
	 * @return array<FeatureInterface>
	 * @throws Exception
	 */
	public function getInitializedFeatures(array $postValues) {
		$features = array();
		foreach ($this->featureRegistry as $featureClass) {
			/** @var FeatureInterface $featureInstance */
			$featureInstance = $this->objectManager->get($featureClass);
			if (!($featureInstance instanceof FeatureInterface)) {
				throw new Exception(
					'Feature ' . $featureClass . ' doen not implement FeatureInterface',
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
	public function getConfigurationForSelectedFeaturePresets(array $postValues) {
		$localConfigurationValuesToSet = array();
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
	public function getBestMatchingConfigurationForAllFeatures() {
		$localConfigurationValuesToSet = array();
		$features = $this->getInitializedFeatures(array());
		foreach ($features as $feature) {
			/** @var FeatureInterface $feature */
			$featureName = $feature->getName();
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
