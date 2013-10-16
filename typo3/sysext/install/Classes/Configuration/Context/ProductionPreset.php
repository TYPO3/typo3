<?php
namespace TYPO3\CMS\Install\Configuration\Context;

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
		'SYS/displayErrors' => FALSE,
		'SYS/enableDeprecationLog' => FALSE,
		'SYS/sqlDebug' => 0,
		'SYS/systemLogLevel' => 2,
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
