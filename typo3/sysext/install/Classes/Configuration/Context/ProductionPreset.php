<?php
namespace TYPO3\CMS\Install\Configuration\Context;

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
 * Production preset
 */
class ProductionPreset extends Configuration\AbstractPreset {

	/**
	 * @var string Name of preset
	 */
	protected $name = 'Production';

	/**
	 * @var integer Priority of preset
	 */
	protected $priority = 50;

	/**
	 * @var array Configuration values handled by this preset
	 */
	protected $configurationValues = array(
		'BE/debug' => FALSE,
		'FE/debug' => FALSE,
		'SYS/devIPmask' => '',
		'SYS/displayErrors' => 0,
		'SYS/enableDeprecationLog' => FALSE,
		'SYS/sqlDebug' => 0,
		'SYS/systemLogLevel' => 2,
		'SYS/clearCacheSystem' => FALSE,
	);

	/**
	 * Production preset is always available
	 *
	 * @return boolean TRUE if mbstring PHP module is loaded
	 */
	public function isAvailable() {
		return TRUE;
	}

	/**
	 * If context is set to production, priority
	 * of this preset is raised.
	 *
	 * @return integer Priority of preset
	 */
	public function getPriority() {
		$context = \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext();
		$priority = $this->priority;
		if ($context->isProduction()) {
			$priority = $priority + 20;
		}
		return $priority;
	}
}
