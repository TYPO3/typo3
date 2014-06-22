<?php
namespace TYPO3\CMS\Install\Configuration\ExtbaseObjectCache;

/**
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

use TYPO3\CMS\Install\Configuration;

/**
 * APC preset
 */
class ApcPreset extends Configuration\AbstractPreset {

	/**
	 * @var string Name of preset
	 */
	protected $name = 'Apc';

	/**
	 * @var integer Priority of preset
	 */
	protected $priority = 80;

	/**
	 * @var array Configuration values handled by this preset
	 */
	protected $configurationValues = array(
		'SYS/caching/cacheConfigurations/extbase_object' => array(
			'frontend' => 'TYPO3\CMS\Core\Cache\Frontend\VariableFrontend',
			'backend' => 'TYPO3\CMS\Core\Cache\Backend\ApcBackend',
			'options' => array(
				'defaultLifetime' => 0,
			),
			'groups' => array('system')
		)
	);

	/**
	 * APC preset is available if extension is loaded, if APC has ~100MB
	 * memory and if ~5MB are free.
	 *
	 * @return boolean TRUE
	 */
	public function isAvailable() {
		$result = FALSE;
		if (extension_loaded('apc')) {
			$memoryInfo = @apc_sma_info();
			$totalMemory = $memoryInfo['num_seg'] * $memoryInfo['seg_size'];
			$availableMemory = $memoryInfo['avail_mem'];

			// If more than 99MB in total and more than 5MB free
			if ($totalMemory > (99 * 1024 * 1024)
				&& $availableMemory > (5 * 1024 * 1024)) {
				$result = TRUE;
			}
		}
		return $result;
	}
}
