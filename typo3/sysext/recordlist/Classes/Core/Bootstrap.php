<?php
namespace TYPO3\CMS\Recordlist\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andreas Wolf <andreas.wolf@typo3.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bootstrap for recordlist controllers
 */
class Bootstrap extends \TYPO3\CMS\Extbase\Core\Bootstrap {

	protected $extbaseConfiguration = array(
		'vendorName' => 'TYPO3\CMS',
		'extensionName' => 'Recordlist',
		'pluginName' => 'List',
	);

	/**
	 * @var array
	 */
	protected $currentGetArguments;

	public function callModule($moduleSignature) {
		if ($moduleSignature !== 'Recordlist') {
			return FALSE;
		}

		$content = $this->run('', $this->extbaseConfiguration);
		print $content;
		return TRUE;
	}

}
?>