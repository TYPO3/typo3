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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

class FeatureManager {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager = NULL;

	protected $featureRegistry = array(
		'TYPO3\\CMS\\Install\\Configuration\\Charset\\CharsetFeature',
	);

	public function getFeatures() {
		$features = array();
		foreach ($this->featureRegistry as $featureClass) {
			$featureInstance = $this->objectManager->get($featureClass);
			$featureInstance->initializePresets();
			$features[] = $featureInstance;
		}
		return $features;
	}

	public function getConfigurationForSelectedFeaturePresets(array $postValues) {
		$localConfigurationValuesToSet = array();
		$features = $this->getFeatures();
		foreach ($features as $feature) {
			$featureName = $feature->getName();
			$presets = $feature->getPresetsOrderedByPriority();
			foreach ($presets as $preset) {
				$presetName = $preset->getName();
				if (!empty($postValues[$featureName]['enable'])
					&& $postValues[$featureName]['enable'] === $presetName
					&& (!$preset->isActive())
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
}